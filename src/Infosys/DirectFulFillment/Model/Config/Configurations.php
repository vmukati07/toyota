<?php

/**
 * @package Infosys/DirectFulFillment
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DirectFulFillment\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class to get store configuration values
 */
class Configurations
{
    const XML_REJECTED_EMAIL_ENABLE = 'df_config/rejected_notification_emails/rejected_emails_enable';
    const XML_NOTIFICATION_EMAIL = 'general/store_information/store_email';
    const XML_EMAIL_SENDER = 'df_config/rejected_notification_emails/email_sender';

    protected ScopeConfigInterface $scopeConfig;

    /**
     * Constructor function
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Method to check rejected notification email enabled or disabled
     *
     * @param int $storeId
     * @return boolean
     */
    public function isRejectedEmailsEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_REJECTED_EMAIL_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Method to get notification email address
     *
     * @param int $storeId
     * @return string|null
     */
    public function getNotificationEmailAddress($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_NOTIFICATION_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Method to get email sender
     *
     * @param int $storeId
     * @return string|null
     */
    public function getEmailSender($storeId): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
