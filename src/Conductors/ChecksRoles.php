<?php

namespace Silber\Bouncer\Conductors;

use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Contracts\Clipboard;
use Silber\Bouncer\Helpers;

class ChecksRoles
{
    /**
     * The authority against which to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * The bouncer clipboard instance.
     *
     * @var \Silber\Bouncer\Contracts\Clipboard
     */
    protected $clipboard;

    /**
     * Constructor.
     */
    public function __construct(Model $authority, Clipboard $clipboard)
    {
        $this->authority = $authority;
        $this->clipboard = $clipboard;
    }

    /**
     * Check if the authority has any of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function a(...$roles)
    {
        return $this->clipboard->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Check if the authority doesn't have any of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function notA(...$roles)
    {
        return $this->clipboard->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Alias to the "a" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function an(...$roles)
    {
        return $this->clipboard->checkRole($this->authority, $roles, 'or');
    }

    /**
     * Alias to the "notA" method.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function notAn(...$roles)
    {
        return $this->clipboard->checkRole($this->authority, $roles, 'not');
    }

    /**
     * Check if the authority has all of the given roles.
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function all(...$roles)
    {
        return $this->clipboard->checkRole($this->authority, $roles, 'and');
    }

    /**
     * Check if the authority has any of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function aFor($roles, $restrictedModel)
    {
        return $this->clipboard
            ->checkRole($this->authority, Helpers::toArray($roles), 'or', $restrictedModel);
    }

    /**
     * Alias to the "isAFor" method..
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function anFor($roles, $restrictedModel)
    {
        return $this->aFor($roles, $restrictedModel);
    }

    /**
     * Check if the authority doesn't have any of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function notAFor($roles, $restrictedModel)
    {
        return $this->clipboard
            ->checkRole($this->authority, Helpers::toArray($roles), 'not', $restrictedModel);
    }

    /**
     * Alias to the "isNotAFor" method..
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function notAnFor($roles, $restrictedModel)
    {
        return $this->notAFor($roles, $restrictedModel);
    }

    /**
     * Check if the authority has all of the given roles for the given model.
     *
     * @param  array|string  $roles
     * @param  Model|string  $restrictedModel
     * @return bool
     */
    public function allFor($roles, $restrictedModel)
    {
        return $this->clipboard
            ->checkRole($this->authority, Helpers::toArray($roles), 'and', $restrictedModel);
    }
}
