<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerChanges\Model\DashboardLinks;

use Magento\Framework\AuthorizationInterface;

/**
 * Class to get check national website or dealer website
 */
class Permissions
{
    /**
     * @var AuthorizationInterface
     */
    protected AuthorizationInterface $authorization;

    /**
     * Constructor function
     *
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * Method to check dashboard fastly tab permission
     *
     * @return boolean
     */
    public function checkFastlyPermission(): bool
    {
        return $this->authorization->isAllowed('Infosys_DealerChanges::view_dashboard_fastly');
    }

    /**
     * Check dahsboard yotpo tab permission
     *
     * @return boolean
     */
    public function checkYotpoReviewsPermission(): bool
    {
        return $this->authorization->isAllowed('Infosys_DealerChanges::view_dashboard_yotpo');
    }

    /**
     * Method to check dashboard customers tab permission
     *
     * @return boolean
     */
    public function checkCustomersTabPermission(): bool
    {
        return $this->authorization->isAllowed('Infosys_DealerChanges::view_dashboard_customers');
    }

    /**
     * Method to check dashboard new customers tab permission
     *
     * @return boolean
     */
    public function checkNewCustomersTabPermission(): bool
    {
        return $this->authorization->isAllowed('Infosys_DealerChanges::view_dashboard_new_customers');
    }
}
