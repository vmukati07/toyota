<?php
declare(strict_types=1);

namespace Infosys\AdminRole\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;

/**
 * This class is to pull configurations for a simulation mode to facilitate with testing locally
 * See README for more details
 */
class Simulator
{

    const XML_SIMULATOR_ENABLED = 'pitbulk_saml2_admin/debug/simulator_enabled';
    const XML_SIMULATOR_RESPONSE = 'pitbulk_saml2_admin/debug/simulator_response';

    protected State $appState;
    protected ScopeConfigInterface $config;

    /**
     * @param State $appState
     * @param ScopeConfigInterface $config
     */
    public function __construct(State $appState, ScopeConfigInterface $config)
    {
        $this->appState = $appState;
        $this->config = $config;
    }

    /**
     * Is Simulation mode enabled.  Will only work in developer mode
     * @return bool
     */
    public function isEnabled() : bool
    {
        $isDeveloperMode = $this->appState->getMode() == State::MODE_DEVELOPER;
        $isEnabled = $this->config->isSetFlag(self::XML_SIMULATOR_ENABLED);
        return $isDeveloperMode && $isEnabled;
    }

    /**
     * Get Simulation Test response
     * @return string
     */
    public function getSimulationResponse() : string
    {
        return $this->config->getValue(self::XML_SIMULATOR_RESPONSE);
    }

}
