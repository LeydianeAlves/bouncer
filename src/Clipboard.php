<?php

namespace Silber\Bouncer;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Queries\Abilities;

class Clipboard extends BaseClipboard
{
    /**
     * Determine if the given authority has the given ability, and return the ability ID.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return int|bool|null
     */
    public function checkGetId(Model $authority, $ability, $model = null, $restrictedModel = null)
    {
        if ($this->isForbidden($authority, $ability, $model, $restrictedModel)) {
            return false;
        }
        $ability = $this->getAllowingAbility($authority, $ability, $model, $restrictedModel);

        return $ability ? $ability->getKey() : null;
    }

    /**
     * Determine whether the given ability request is explicitely forbidden.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return bool
     */
    protected function isForbidden(Model $authority, $ability, $model = null, $restrictedModel = null)
    {
        return $this->getHasAbilityQuery(
            $authority, $ability, $model, $allowed = false, $restrictedModel
        )->exists();
    }

    /**
     * Get the ability model that allows the given ability request.
     *
     * Returns null if the ability is not allowed.
     *
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getAllowingAbility(Model $authority, $ability, $model = null, $restrictedModel)
    {
        return $this->getHasAbilityQuery(
            $authority, $ability, $model, $allowed = true, $restrictedModel
        )->first();
    }

    /**
     * Get the query for where the given authority has the given ability.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  string  $ability
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  bool  $allowed
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $restrictedModel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getHasAbilityQuery($authority, $ability, $model, $allowed, $restrictedModel)
    {

        // skips the `authority` and `everyone` constraints when checking for a restricted model only
        // if allowed is false, always check all abilities for the user
        $query = $restrictedModel && $allowed
            ? Abilities::restrictedForAuthority($authority, $allowed, $restrictedModel)
            : Abilities::forAuthority($authority, $allowed);

        if (! $this->isOwnedBy($authority, $model)) {
            $query->where('only_owned', false);
        }

        if (is_null($model)) {
            return $this->constrainToSimpleAbility($query, $ability);
        }

        return $query->byName($ability)->forModel($model);
    }

    /**
     * Constrain the query to the given non-model ability.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ability
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function constrainToSimpleAbility($query, $ability)
    {
        return $query->where(function ($query) use ($ability) {
            $query->where('name', $ability)->whereNull('entity_type');

            $query->orWhere(function ($query) {
                $query->where('name', '*')->where(function ($query) {
                    $query->whereNull('entity_type')->orWhere('entity_type', '*');
                });
            });
        });
    }
}
