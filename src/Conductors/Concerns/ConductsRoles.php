<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Silber\Bouncer\Conductors\Lazy;
use Workbench\App\Models\User;

trait ConductsRoles
{
    /**
     * Determines whether a call to "to" with the given parameters should be conducted lazily.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return bool
     */
    protected function shouldConductLazy($authorities)
    {
        // We'll only create a lazy conductor if we got a single
        // param, and that single param is either a single authority or
        // an array of authorities.

        if (func_num_args() > 1) {
            return false;
        }

        $authorities = is_array($authorities) ? $authorities : [$authorities];

        return (new Collection($authorities))
            ->every(function ($authority) {
                return $authority instanceof Model || $authority instanceof User;
            });
    }

    /**
     * Create a lazy role assignment conductor.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAssignment
     */
    protected function conductLazyTo($authority)
    {
        return new Lazy\ConductsAssignment($this, $authority);
    }

    /**
     * Create a lazy role retraction conductor.
     *
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsRetraction
     */
    protected function conductLazyFrom($authority)
    {
        return new Lazy\ConductsRetraction($this, $authority);
    }

    /**
     * Return an array of the given restricted models
     *
     * @param  Model|array|string  $restrictedModels
     * @return array
     */
    public static function getRestrictions($restrictedModels)
    {
        if ($restrictedModels === null) {
            return [];
        }

        $restrictions = collect(is_array($restrictedModels)
            ? $restrictedModels
            : [$restrictedModels]);

        return $restrictions->map(function ($entity) {

            if (! $entity instanceof Model) {
                return new $entity;
            }

            return $entity;

        })->all();
    }
}
