<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Silber\Bouncer\Conductors\AssignsRoles;
use Silber\Bouncer\Conductors\RemovesRoles;
use Silber\Bouncer\Contracts\Clipboard;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Queries\Roles as RolesQuery;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Helpers;

trait HasRoles
{
    /**
     * Boot the HasRoles trait.
     *
     * @return void
     */
    public static function bootHasRoles()
    {
        static::deleted(function ($model) {
            if (! Helpers::isSoftDeleting($model)) {
                $model->roles()->detach();
            }
        });
    }

    /**
     * The roles relationship.
     */
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            Models::classname(Role::class),
            'entity',
            Models::table('assigned_roles')
        )->withPivot(['restricted_to_id', 'restricted_to_type', 'scope']);

        return Models::scope()->applyToRelation($relation);
    }

    /**
     * Get all of the model's assigned roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoles()
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->getRoles($this);
    }

    /**
     * Get all of the model's assigned roles for model.
     *
     * @param Model|string $restrictedModel
     * @return \Illuminate\Support\Collection
     */
    public function getRolesForRoleRestriction($restrictedModel)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->getRolesForRoleRestriction($this, $restrictedModel);
    }

    /**
     * Assign the given roles to the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|array  $roles
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return $this
     */
    public function assign($roles, $restrictedModel = null)
    {
        (new AssignsRoles($roles))->to($this, $restrictedModel);

        return $this;
    }

    /**
     * Retract the given roles from the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string|array  $roles
     * @param  \Illuminate\Database\Eloquent\Model|null  $restrictedModel
     * @return $this
     */
    public function retract($roles, $restrictedModel = null)
    {
        (new RemovesRoles($roles))->from($this, $restrictedModel);

        return $this;
    }

    /**
     * Check if the model has any of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isAn(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'or');
    }

    /**
     * Check if the model has any of the given roles.
     *
     * Alias for the "isAn" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isA(...$roles)
    {
        return $this->isAn(...$roles);
    }

    /**
     * Check if the model has none of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isNotAn(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'not');
    }

    /**
     * Check if the model has none of the given roles.
     *
     * Alias for the "isNotAn" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isNotA(...$roles)
    {
        return $this->isNotAn(...$roles);
    }

    /**
     * Check if the model has all of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function isAll(...$roles)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, $roles, 'and');
    }

    /**
     * Check if the model has any of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function isAFor($roles, $restrictedModel)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, Helpers::toArray($roles), 'or', $restrictedModel);
    }

    /**
     * Check if the model has any of the given roles for the given model.
     *
     * Alias for the "isAFor" method.
     * 
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function isAnFor($roles, $restrictedModel)
    {
        return $this->isAFor($roles, $restrictedModel);
    }

    /**
     * Check if the model has none of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function isNotAFor($roles, $restrictedModel)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, Helpers::toArray($roles), 'not', $restrictedModel);
    }

    /**
     * Check if the model has none of the given roles for the given model.
     *
     * Alias for the "isNotAFor" method.
     * 
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function isNotAnFor($roles, $restrictedModel)
    {
        return $this->isNotAFor($roles, $restrictedModel);
    }

     /**
     * Check if the model has all of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function isAllFor($roles, $restrictedModel)
    {
        return Container::getInstance()
            ->make(Clipboard::class)
            ->checkRole($this, Helpers::toArray($roles), 'all', $restrictedModel);
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIs($query, $role)
    {
        (new RolesQuery)->constrainWhereIs(...func_get_args());
    }

    /**
     * Constrain the given query by all provided roles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIsAll($query, $role)
    {
        (new RolesQuery)->constrainWhereIsAll(...func_get_args());
    }

    /**
     * Constrain the given query by the provided role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return void
     */
    public function scopeWhereIsNot($query, $role)
    {
        (new RolesQuery)->constrainWhereIsNot(...func_get_args());
    }
}
