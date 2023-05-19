<?php
declare(strict_types=1);

namespace Infosys\AdminRole\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use  Magento\TwoFactorAuth\Api\TfaSessionInterface;

class BackendAuthUserLoginSuccess implements ObserverInterface
{
    public const CONFIG_PATH_BYPASS_TWO_FACTOR = 'pitbulk_saml2_admin/options/bypass_two_factor';
    
    public const ENABLE_TWO_FACTOR_AUTH = 'pitbulk_saml2_admin/options/enable_two_factor';

    private ScopeConfigInterface $scopeConfig;
    
    private TfaSessionInterface $tfaSession;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TfaSessionInterface $tfaSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TfaSessionInterface $tfaSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->tfaSession = $tfaSession;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {   
        //Disable 2FA for DF validation     
        if (!$this->scopeConfig->isSetFlag(self::ENABLE_TWO_FACTOR_AUTH)) {
            $this->tfaSession->grantAccess();
        }

        //Enable/Disable 2FA for SSO Login, by default 2FA is disabled for SSO Login
        $user = $observer->getUser();
        if ($user->getSamlLogin() && $this->scopeConfig->isSetFlag(self::CONFIG_PATH_BYPASS_TWO_FACTOR)) {
            $this->tfaSession->grantAccess();
        }
    }
}
