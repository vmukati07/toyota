<?php
declare(strict_types=1);

namespace Infosys\AdminRole\Plugin\Backend\OneLogin\Saml2;

use Infosys\AdminRole\Model\Simulator;

/**
 * This class is for local testing purposes to simulate an SSO response
 * See README for more details
 */
class Auth
{
    /**
     * @var Simulator
     */
    private Simulator $simulator;

    /**
     * @param Simulator $simulator
     */
    public function __construct(Simulator $simulator)
    {
        $this->simulator = $simulator;
    }

    public function beforeProcessResponse(\OneLogin\Saml2\Auth $subject)
    {
        if ($this->simulator->isEnabled()) {
            $_POST['SAMLResponse'] = $this->simulator->getSimulationResponse();
        }
        return null;
    }
}
