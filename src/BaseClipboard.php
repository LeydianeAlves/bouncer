<?php

namespace Silber\Bouncer;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Queries\Abilities;

abstract class BaseClipboard implements Contracts\Clipboard
{
    /**
     * Determine if the given authority has the given ability.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return bool
     */
    public function check(Model $authority, $ability, $model = null, $restrictedModel = null)
    {
        return (bool) $this->checkGetId($authority, $ability, $model, $restrictedModel);
    }

    /**
     * Check if an authority has the given roles.
     *
     * @param  array|string  $roles
     * @param  string  $boolean
     * @param  Model|string|null  $restrictedModel
     * @return bool
     */
    public function checkRole(Model $authority, $roles, $boolean = 'or', $restrictedModel = null)
    {
        $count = $this->countMatchingRoles($authority, $roles, $restrictedModel);
        if ($boolean == 'or') {
            return $count > 0;
        } elseif ($boolean === 'not') {
            return $count === 0;
        }

        return $count == count((array) $roles);
    }

    /**
     * Count the authority's roles matching the given roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  array|string  $roles
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return int
     */
    protected function countMatchingRoles($authority, $roles, $restrictedModel)
    {
        $lookups = $this->getRolesLookup($authority, $restrictedModel);

        return count(array_filter($roles, function ($role) use ($lookups) {
            switch (true) {
                case is_string($role):
                    return $lookups['names']->has($role);
                case is_numeric($role):
                    return $lookups['ids']->has($role);
                case $role instanceof Model:
                    return $lookups['ids']->has($role->getKey());
            }

            throw new InvalidArgumentException('Invalid model identifier');
        }));
    }

    /**
     * Get the given authority's roles' IDs and names.
     *
     * @param Model|string|null $restrictedModel
     * @return array
     */
    public function getRolesLookup(Model $authority, $restrictedModel = null)
    {
        $roles = $authority
            ->roles()
            ->for($restrictedModel)
            ->get(['name', Models::role()->getQualifiedKeyName()])
            ->pluck('name', Models::role()->getKeyName());
     
        return ['ids' => $roles, 'names' => $roles->flip()];
    }

    /**
     * Get the given authority's roles' names.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        return $this->getRolesLookup($authority)['names']->keys();
    }

    /**
     * Get the given authority's roles' names for a restricted role.
     *
     * @param Model|string|null $restrictedModel
     * @return \Illuminate\Support\Collection
     */
    public function getRolesForRestrictedModel(Model $authority, $restrictedModel)
    {
        return $this->getRolesLookup($authority, $restrictedModel)
            ['names']
            ->keys();
    }

    /**
     * Get a list of the authority's abilities. This does not include forbidden abilities,
     * or abilities granted through a restricted role.
     *
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true, $restrictedModel = null)
    {
        return Abilities::forAuthority($authority, $allowed, $restrictedModel)->get();
    }

    /**
     * Get a list of the authority's forbidden abilities.
     *
     * @param Model|string|null $restrictedModel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForbiddenAbilities(Model $authority, $restrictedModel = null)
    {
        return $this->getAbilities($authority, false, $restrictedModel);
    }

    /**
     * Get a list of the authority's restricted abilities
     * 
     * @param bool $allowed
     * @param  \Illuminate\Database\Eloquent\Model|string  $restrictedModel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilitiesForRestrictedModel(Model $authority, $allowed = true, $restrictedModel)
    {
        return Abilities::restrictedForAuthority($authority, $restrictedModel, $allowed)
            ->get();
    }

    /**
     * Determine whether the authority owns the given model.
     *
     * @return bool
     */
    public function isOwnedBy($authority, $model)
    {
        return $model instanceof Model && Models::isOwnedBy($authority, $model);
    }
}
