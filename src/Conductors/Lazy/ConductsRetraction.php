<?php

namespace Silber\Bouncer\Conductors\Lazy;

class ConductsRetraction
{
    /**
     * The conductor handling the role removal.
     *
     * @var \Silber\Bouncer\Conductors\Concerns\ConductsRoles
     */
    protected $conductor;

    /**
     * The authority to retract the roles from.
     *
     * @var \Illuminate\Database\Eloquent\Model|array|int
     */
    protected $authority;

    /**
     * The restricted models the role is retracted from.
     *
     * @var \Illuminate\Database\Eloquent\Model|array
     */
    protected $restrictedModels = null;

    /**
     * Constructor.
     *
     * @param  \Silber\Bouncer\Conductors\Concerns\ConductsRoles  $conductor
     * @param  \Illuminate\Database\Eloquent\Model|array|int  $authority
     */
    public function __construct($conductor, $authority)
    {
        $this->conductor = $conductor;
        $this->authority = $authority;
    }

    /**
     * Sets the restricted model to retract the roles from.
     *
     * @return void
     */
    public function for($restrictedModels)
    {
        $this->restrictedModels = $restrictedModels;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->conductor->from(
            $this->authority,
            $this->restrictedModels,
        );
    }
}
