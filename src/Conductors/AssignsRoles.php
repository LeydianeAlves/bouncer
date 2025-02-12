<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Support\Collection;
use Silber\Bouncer\Conductors\Concerns\ConductsRoles;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Queries\RolesForRestriction;
use Silber\Bouncer\Helpers;

class AssignsRoles
{
    use ConductsRoles;

    /**
     * The roles to be assigned.
     *
     * @var array
     */
    protected $roles;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Support\Collection|\Silber\Bouncer\Database\Role|string  $roles
     */
    public function __construct($roles)
    {
        $this->roles = Helpers::toArray($roles);
    }

    /**
     * Assign the roles to the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAssignment|bool
     */
    public function to($authority, $restrictedModel = null)
    {
        if ($this->shouldConductLazy(...func_get_args())) {
            return $this->conductLazyTo($authority);
        }

        $authorities = is_array($authority) ? $authority : [$authority];

        $roles = Models::role()->findOrCreateRoles($this->roles);

        $restrictions = $this->getRestrictions($restrictedModel);

        foreach (Helpers::mapAuthorityByClass($authorities) as $class => $ids) {
            $this->assignRoles(
                $roles, $class, new Collection($ids), $restrictions
            );
        }

        return true;
    }

    /**
     * Assign the given roles to the given authorities.
     *
     * @param  string  $authorityClass
     * @param  array  $restrictions
     * @return void
     */
    protected function assignRoles(Collection $roles, $authorityClass, Collection $authorityIds, $restrictions)
    {
        $roleIds = $roles->map(function ($model) {
            return $model->getKey();
        });

        $morphType = (new $authorityClass)->getMorphClass();

        $records = $this->buildAttachRecords(
            $roleIds, $morphType, $authorityIds, $restrictions
        );

        $existing = $this->getExistingAttachRecords(
            $roleIds, $morphType, $authorityIds, $restrictions
        );

        $this->createMissingAssignRecords($records, $existing);
    }

    /**
     * Get the pivot table records for the roles already assigned.
     *
     * @param  \Illuminate\Support\Collection  $roleIds
     * @param  string  $morphType
     * @param  \Illuminate\Support\Collection  $authorityIds
     * @param  array  $restrictions
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingAttachRecords($roleIds, $morphType, $authorityIds, $restrictions)
    {
        $query = $this->newPivotTableQuery()
            ->whereIn('role_id', $roleIds->all())
            ->whereIn('entity_id', $authorityIds->all())
            ->where('entity_type', $morphType);

        Models::scope()->applyToRelationQuery($query, $query->from);
        RolesForRestriction::applyRestrictionsToQuery($query, $restrictions, $query->from);

        return new Collection($query->get());
    }

    /**
     * Build the raw attach records for the assigned roles pivot table.
     *
     * @param  \Illuminate\Support\Collection  $roleIds
     * @param  string  $morphType
     * @param  \Illuminate\Support\Collection  $authorityIds
     * @param  array  $restrictions
     * @return \Illuminate\Support\Collection
     */
    protected function buildAttachRecords($roleIds, $morphType, $authorityIds, $restrictions)
    {
        // if there any restrictions, create a record for each role-authority-restriction combinations
        // if none, create a record for each role-authority pairing instead
        if (! empty($restrictions)) {
            return collect($restrictions)
                ->crossJoin($roleIds, $authorityIds)
                ->mapSpread(function ($restriction, $roleId, $authorityId) use ($morphType) {
                    return $this->getAttachAttributes(
                        $roleId, $authorityId, $morphType, $restriction
                    );
                });
        }

        return $roleIds
            ->crossJoin($authorityIds)
            ->mapSpread(function ($roleId, $authorityId) use ($morphType) {
                return $this->getAttachAttributes(
                    $roleId, $authorityId, $morphType
                );
            });
    }

    /**
     * Get the base attach attributes for the raw records
     *
     * @param  int  $roleId
     * @param  int  $authorityId
     * @param  string  $morphType
     * @param  \Illuminate\Database\Eloquent\Model|null  $restriction
     * @return array
     */
    public function getAttachAttributes($roleId, $authorityId, $morphType, $restriction = null)
    {
        return
            Models::scope()->getAttachAttributes()
            + RolesForRestriction::getAttachAttributes($restriction)
            + [
                'role_id' => $roleId,
                'entity_id' => $authorityId,
                'entity_type' => $morphType,
            ];
    }

    /**
     * Save the non-existing attach records in the DB.
     *
     * @return void
     */
    protected function createMissingAssignRecords(Collection $records, Collection $existing)
    {
        $existing = $existing->keyBy(function ($record) {
            return $this->getAttachRecordHash((array) $record);
        });

        $records = $records->reject(function ($record) use ($existing) {
            return $existing->has($this->getAttachRecordHash($record));
        });

        $this->newPivotTableQuery()->insert($records->all());
    }

    /**
     * Get a string identifying the given attach record.
     *
     * @return string
     */
    protected function getAttachRecordHash(array $record)
    {
        $hash = $record['role_id']
            .$record['entity_id']
            .$record['entity_type'];

        if (isset($record['restricted_to_id'])) {
            $hash .= $record['restricted_to_id'];
        }

        if (isset($record['restricted_to_type'])) {
            $hash .= $record['restricted_to_type'];
        }

        return $hash;

    }

    /**
     * Get a query builder instance for the assigned roles pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotTableQuery()
    {
        return Models::query('assigned_roles');
    }
}
