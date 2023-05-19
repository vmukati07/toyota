<?php

/**
 * @package     Infosys/Reports
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace  Infosys\Reports\Controller\Adminhtml\Adobe;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Provides link to Adobe Analytics
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Path to config value with URL to Adobe Analytics page.
     *
     * @var string
     */
    private $urlBIEssentialsConfigPath = 'reports/links/web_analytics';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Infosys_Reports::report_web_analytics';

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
     * Provides link to Adobe Analytics
     *
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute()
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->config->getValue($this->urlBIEssentialsConfigPath)
        );
    }

    /**
     * Allow as per ACL
     * 
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Infosys_Reports::report_web_analytics');
    }
}
