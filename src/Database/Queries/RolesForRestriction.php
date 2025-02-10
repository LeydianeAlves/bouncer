<?php

namespace Silber\Bouncer\Database\Queries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Silber\Bouncer\Helpers;

class RolesForRestriction
{
    /**
     * Constrain a query to a global role or restricted to a model.
     * If a model is passed, global roles are excluded
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|null  $restrictedModel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function constrain($query, $restrictedModel = null, $table = '')
    {
        if (is_null($restrictedModel)) {
            return self::constrainToGlobalRoles($query, $table);
        }
        return $query->where(function ($query) use ($restrictedModel, $table) {
            return $query->where(self::constrainRoleToRoleRestriction($restrictedModel, $table));
        });
    }

    /**
     * Constrain the query to global roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function constrainToGlobalRoles($query, $table)
    {
        return $query->whereNull("$table.restricted_to_id")
            ->whereNull("$table.restricted_to_type");
    }

     /**
     * Get the constraint for restricted roles.
     *
     * @param  string  $table
     * @return \Closure
     */
    protected static function constrainRoleToRoleRestriction(Model $restrictedModel, $table)
    {
        return function ($query) use ($restrictedModel, $table) {
            $query->where("$table.restricted_to_type", $restrictedModel->getMorphClass());

            $query->where(self::restrictedModelSubqueryConstraint($restrictedModel, $table));
        };
    }

    /**
     * Get the constraint for the restricted model subquery.
     *
     * @param  string  $table
     * @return \Closure
     */
    protected static function restrictedModelSubqueryConstraint(Model $restrictedModel, $table)
    {
        return function ($query) use ($restrictedModel, $table) {
            if (! $restrictedModel->exists) {
                $query->whereNull("$table.restricted_to_id");
            }

            if ($restrictedModel->exists) {
                $query->orWhere("$table.restricted_to_id", $restrictedModel->getKey());
            }
        };
    }

 
    /**
     * Apply restrictions to a query
     * 
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array   $restrictions
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    public static function applyRestrictionsToQuery($query, $restrictions, $table = '')
    {
        if ( !empty($restrictions)) {
            [$models, $strings] = self::partitionTypes($restrictions);

            $restrictionIds = Helpers::mapItemsBy($models, 'getKey')->all();
            $morphTypes = Helpers::mapItemsBy($strings, 'getMorphClass')
                ->merge(Helpers::mapItemsBy($models, 'getMorphClass'))
                ->all();
           
            return $query->where(function ($query) use ($table, $restrictionIds) {
                return $query->whereIn("$table.restricted_to_id", $restrictionIds)
                    ->orWhereNull("$table.restricted_to_id");
            })->whereIn("$table.restricted_to_type", $morphTypes);  
        }

        return $query->whereNull("$table.restricted_to_id")
            ->whereNull("$table.restricted_to_type");
    }

    /**
     * Partition the restricted models by types
     * 
     * @param  array  $restrictions
     * @return Collection
     */
    static function partitionTypes($restrictions)
    {
        return Helpers::partition($restrictions, function($entity) {
            return $entity instanceof Model;
        });
    }

    /**
     * Get the additional attributes for this model.
     * 
     * @param Model|string|null $restrictedModel
     * @return array
     */
    public static function getAttachAttributes($restrictedModel)
    {
         if ( is_null($restrictedModel)) {
             return [
                 'restricted_to_id' => null,
                 'restricted_to_type' => null
             ];
         }
 
         return [
             'restricted_to_id' => $restrictedModel->exists ? $restrictedModel->getKey() : null,
             'restricted_to_type' => $restrictedModel->getMorphClass(),
         ];
    }
}