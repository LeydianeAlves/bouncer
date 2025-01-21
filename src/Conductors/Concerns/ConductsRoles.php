<?php

namespace Silber\Bouncer\Conductors\Concerns;

use Illuminate\Support\Collection;
use Silber\Bouncer\Conductors\Lazy;
use Silber\Bouncer\Helpers;

trait ConductsRoles
{
    /**
     * Determines whether a call to "to" with the given parameters should be conducted lazily.
     *
     * @param  mixed  $authority
     * @return bool
     */
    protected function shouldConductLazy($authority)
    {
        // We'll only create a lazy conductor if we got a single
        // param, and that single param is an array of authorities.
        if (func_num_args() > 1) {
            return false;
        }
        return ! is_array($authority) || ! Helpers::isIndexedArray($authority);
    }

    /**
     * Create a lazy role assignment conductor.
     *
     * @param  mixed  $authority
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsAssignment
     */
    protected function conductLazyTo($authority)
    {
        return new Lazy\ConductsAssignment($this, $authority);
    }

    /**
     * Create a lazy role retraction conductor.
     *
     * @param  mixed  $authority
     * @return \Silber\Bouncer\Conductors\Lazy\ConductsRetraction
     */
    protected function conductLazyFrom($authority)
    {
        return new Lazy\ConductsRetraction($this, $authority);
    }
}
