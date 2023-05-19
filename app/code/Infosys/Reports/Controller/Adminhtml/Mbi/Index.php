<?php

/**
 * @package     Infosys/Reports
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\Reports\Controller\Adminhtml\Mbi;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Provides link to MBI Login
 */
class Index extends Action
{
    /**
     * Path to config value with URL to MBI login page.
     *
     * @var string
     */
    private $urlMBIloginConfigPath = 'reports/links/mbi_login';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Infosys_Reports::report_sales_performance';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Provides link to MBI login
     *
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute()
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->config->getValue($this->urlMBIloginConfigPath)
        );
    }

    /**
     * Allow as per ACL
     * 
     * @return bool
     */
    protected function _isAllowed():bool
    {
        return $this->_authorization->isAllowed('Infosys_Reports::report_sales_performance');
    }
}
