<?php

namespace Silber\Bouncer\Conductors\Lazy;

class ConductsAssignment
{
    /**
     * The conductor handling the assigning.
     *
     * @var \Silber\Bouncer\Conductors\Concerns\ConductsRoles
     */
    protected $conductor;

    /**
     * The authority to assign the roles to.
     *
     * @var \Illuminate\Database\Eloquent\Model|array|int
     */
    protected $authority;

    /**
     * The restricted models the role is assigned to.
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
     * Sets that the restricted model to assign the roles to.
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
        $this->conductor->to(
            $this->authority,
            $this->restrictedModels,
        );
    }
}
