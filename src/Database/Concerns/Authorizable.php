<?php

namespace Silber\Bouncer\Database\Concerns;

use Illuminate\Container\Container;
use Silber\Bouncer\Contracts\Clipboard;
use Silber\Bouncer\Helpers;

trait Authorizable
{
    /**
     * Determine if the authority has a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        [$model, $restriction] = Helpers::parseArguments($arguments);

        return Container::getInstance()
            ->make(Clipboard::class)
            ->check($this, $ability, $model, $restriction);
    }

    /**
     * Determine if the authority does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cant($ability, $arguments = [])
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * Determine if the authority does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return $this->cant($ability, $arguments);
    }
}
