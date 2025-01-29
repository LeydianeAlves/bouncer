<?php

namespace Silber\Bouncer\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Clipboard
{
    /**
     * Determine if the given authority has the given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return bool
     */
    public function check(Model $authority, $ability, $model = null, $restrictedModel = null);

    /**
     * Determine if the given authority has the given ability, and return the ability ID.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return int|bool|null
     */
    public function checkGetId(Model $authority, $ability, $model = null, $restrictedModel = null);

    /**
     * Check if an authority has the given roles.
     *
     * @param  array|string  $roles
     * @param  string  $boolean
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or', $restrictedModel = null);

    /**
     * Get the given authority's roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority);

    /**
     * Get the given authority's roles.
     *
     * @param  Model|string  $restrictedModel
     * @return \Illuminate\Support\Collection
     */
    public function getRolesForRestrictedModel(Model $authority, $restrictedModel);

    /**
     * Get a list of the authority's abilities.
     *
     * @param  bool  $allowed
     * @param Model|string|null $restrictedModel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true, $restrictedModel = null);

    /**
     * Get a list of the authority's forbidden abilities.
     *
     * @param Model|string|null $restrictedModel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForbiddenAbilities(Model $authority, $restrictedModel = null);

    /**
     * Get a list of the authority's abilities for restricted model.
     *
     * @param  bool  $allowed
     * @param  Model|string  $restrictedModel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilitiesForRestrictedModel(Model $authority, $allowed = true, $restrictedModel);
}
