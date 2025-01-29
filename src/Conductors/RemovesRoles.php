<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Conductors\Concerns\ConductsRoles;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Queries\RolesForRestriction;
use Silber\Bouncer\Helpers;

class RemovesRoles
{
    use ConductsRoles;

    /**
     * The roles to be removed.
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
     * Remove the role from the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @param  \Illuminate\Database\Eloquent\Model|array|null  $restrictedModels
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsRetraction|void
     */
    public function from($authority, $restrictedModels = null)
    {
        if (! ($roleIds = $this->getRoleIds())) {
            return;
        }

        if ($this->shouldConductLazy(...func_get_args())) {
            return $this->conductLazyFrom($authority);
        }

        $authorities = is_array($authority) ? $authority : [$authority];
        $restrictions = $this->getRestrictions($restrictedModels);

        foreach (Helpers::mapAuthorityByClass($authorities) as $class => $keys) {
            $this->retractRoles($roleIds, $class, $keys, $restrictions);
        }
    }

    /**
     * Get the IDs of any existing roles provided.
     *
     * @return array
     */
    protected function getRoleIds()
    {
        [$models, $names] = Helpers::partition($this->roles, function ($role) {
            return $role instanceof Model;
        });

        $ids = $models->map(function ($model) {
            return $model->getKey();
        });

        if ($names->count()) {
            $ids = $ids->merge($this->getRoleIdsFromNames($names->all()));
        }

        return $ids->all();
    }

    /**
     * Get the IDs of the roles with the given names.
     *
     * @param  string[]  $names
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRoleIdsFromNames(array $names)
    {
        $key = Models::role()->getKeyName();

        return Models::role()
            ->whereIn('name', $names)
            ->get([$key])
            ->pluck($key);
    }

    /**
     * Retract the given roles from the given authorities.
     *
     * @param  array  $roleIds
     * @param  string  $authorityClass
     * @param  array  $authorityIds
     * @param  array  $restrictions
     * @return void
     */
    protected function retractRoles($roleIds, $authorityClass, $authorityIds, $restrictions)
    {
        $query = $this->newPivotTableQuery();

        $morphType = (new $authorityClass)->getMorphClass();

        foreach ($roleIds as $roleId) {
            foreach ($authorityIds as $authorityId) {
                if (! empty($restrictions)) {
                    foreach ($restrictions as $restriction) {
                        $query->orWhere($this->getDetachQueryConstraint(
                            $roleId, $authorityId, $morphType, $restriction
                        ));
                    }
                } else {
                    $query->orWhere($this->getDetachQueryConstraint(
                        $roleId, $authorityId, $morphType
                    ));
                }
            }
        }

        $query->delete();
    }

    /**
     * Get a constraint for the detach query for the given parameters.
     *
     * @param  mixed  $roleId
     * @param  mixed  $authorityId
     * @param  string  $morphType
     * @param  mixed  $restriction
     * @return \Closure
     */
    protected function getDetachQueryConstraint($roleId, $authorityId, $morphType, $restriction = null)
    {
        return function ($query) use ($roleId, $authorityId, $morphType, $restriction) {
            $query->where(Models::scope()->getAttachAttributes() 
                + RolesForRestriction::getAttachAttributes($restriction)
                + [
                    'role_id' => $roleId,
                    'entity_id' => $authorityId,
                    'entity_type' => $morphType,
                ]
            );
        };
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
