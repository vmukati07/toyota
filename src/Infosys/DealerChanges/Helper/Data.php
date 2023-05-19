<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerChanges\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\AuthorizationInterface;

/**
 * Class to get check national website or dealer website
 */
class Data extends AbstractHelper
{
    const XML_ADVANCED_REPORTING_RESOURCE = 'Infosys_DealerChanges::view_dashboard_advanced_reporting';

    protected Session $authSession;

    protected AuthorizationInterface $authorization;

    /**
     * Constructor function
     *
     * @param Session $authSession
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        Session $authSession,
        AuthorizationInterface $authorization
    ) {
        $this->authSession = $authSession;
        $this->authorization = $authorization;
    }

    /**
     * Method to check national website or dealer website
     *
     * @return boolean
     */
    public function isDealerLogin(): bool
    {
        $loginUserAccess = $this->authSession->getUser()->getData('all_website');
        if (!empty($loginUserAccess) && $loginUserAccess == 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check Signifyd Permission
     *
     * @return boolean
     */
    public function checkSignifydPermission(): bool
    {
        return $this->authorization->isAllowed('Magento_Integration::config_signifyd');
    }

    /**
     * Check advanced reporting link permission
     *
     * @return boolean
     */
    public function checkAdvancedReportingPermission(): bool
    {
        return $this->authorization->isAllowed(self::XML_ADVANCED_REPORTING_RESOURCE);
    }
}
