<?php

/**
 * @package   Infosys/CustomerSSO
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\CustomerSSO\Controller\Saml2;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Pitbulk\SAML2\Controller\AbstractCustomController;
use Pitbulk\SAML2\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Store\Model\StoreManagerInterface;
use Infosys\CustomerSSO\Helper\Data as SsoHelper;
use Infosys\CustomerSSO\Logger\DCSLogger;
use Magento\Csp\Api\CspAwareActionInterface;

class Login extends AbstractCustomController implements CspAwareActionInterface
{
    /**
     * @var RedirectInterface
     */
    protected $redirect;
    /**
     * @var TokenModelFactory
     */
    protected $tokenFactory;

     /**
     * @var ssoHelper
     */
    protected $ssoHelper;

    /**
     * @var ssoLogger
     */
    protected $ssoLogger;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;
    /**
     * Constructor function
     *
     * @param Context $context
     * @param Session $session
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param FormKey $formKey
     * @param RedirectInterface $redirect
     * @param TokenModelFactory $tokenFactory
     * @param SsoHelper $ssoHelper
     * @param StoreManagerInterface $storeManager
     * @param DCSLogger $ssoLogger
     */
    public function __construct(
        Context $context,
        Session $session,
        Data $helper,
        LoggerInterface $logger,
        FormKey $formKey,
        RedirectInterface $redirect,
        TokenModelFactory $tokenFactory,
        SsoHelper $ssoHelper,
        StoreManagerInterface $storeManager,
        DCSLogger $ssoLogger
    ) {
        $this->redirect = $redirect;
        $this->tokenFactory = $tokenFactory;
        $this->ssoHelper = $ssoHelper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->ssoLogger = $ssoLogger;
        parent::__construct($context, $session, $helper, $logger, $formKey);
    }
    /**
     * SSO login
     *
     * @return void
     */
    public function execute()
    {
        $helper = $this->_getHelper();
        $customerSession = $this->_getCustomerSession();
        $errorMsg = null;
        $moduleEnabled = $helper->checkEnabledModule();
        if ($moduleEnabled) {
            $redirectTo = $this->getRequest()->getParam('url');
            if (!$redirectTo) {
                $redirectTo = $helper->getBaseStoreUrl();
            }
            $this->ssoLogger->info('SSO login redirect url'.$redirectTo);
            if (!$customerSession->isLoggedIn()) {
                $idpSSOBinding = $helper->getConfigIdP('sso_binding');
                $auth = $this->_getSAMLAuth();
                if (isset($auth)) {
                    $idpSSOBinding = $helper->getConfigIdP('sso_binding');
                    if ($idpSSOBinding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                        $data = $auth->postLogin(null, null, $redirectTo);
                        $this->_view->loadLayout();
                        $this->_view->getLayout()->unsetElement('sso_saml2_login_postback');
                        $block = $this->_view->getLayout()->getBlock('sso_saml2_login_post');
                        $block->setData('target', $data["ssoURL"]);
                        $block->setData('parameters', $data["parameters"]);
                        $this->_view->renderLayout();
                        return;
                    } else {
                        $ssoURL = $auth->login($redirectTo, [], false, false, true);
                        $resultRedirect = $this->resultRedirectFactory->create();
                        $resultRedirect->setUrl($ssoURL);
                        return $resultRedirect;
                    }
                } else {
                    $errorMsg = "You tried to start a SSO process but" .
                        " SAML2 module has wrong settings";
                }
            } else {
                $sessionId = $customerSession->getSessionId();
                $destination = $this->getDestination($redirectTo);
                $this->_view->loadLayout();
                $this->_view->getLayout()->unsetElement('sso_saml2_login_post');
                $block = $this->_view->getLayout()->getBlock('sso_saml2_login_postback');
                $block->setData('action', $destination);
                $block->setData('sessionId', $sessionId);
                $this->_view->renderLayout();
                return;
            }
        } else {
            $errorMsg = "You tried to start a SSO process but" .
                " SAML2 module has disabled status";
        }

        if (isset($errorMsg)) {
            $this->processError($errorMsg);
        }
    }

    /**
     * Returns input action with /jcr:content.login before any url parameters
     *
     * @param String $destination
     * @return String
     */
    private function getDestination($destination)
    {
        $destinations = explode("?", $destination);
        $destination = rtrim($destinations[0], "/") . "/jcr:content.login";
        if (array_key_exists(1, $destinations)) {
            $destination .= "?" . $destinations[1];
        }
        return $destination;
    }

     /**
     * Process Error
     *
     * @param string $errorMsg
     * @param string $extraInfo
     * @return void
     */
    public function processError($errorMsg, $extraInfo = null)
    {
        $this->ssoLogger->error($errorMsg);
        if (isset($extraInfo)) {
            $this->ssoLogger->error($extraInfo);
        }
        $storeId = $this->storeManager->getStore()->getId();
        $redirectTo =  $this->ssoHelper->getSsoRedirectionUrl($storeId);
        $this->ssoLogger->info('SSO login redirect error url'.$redirectTo);
        return $this->_redirect($redirectTo);
    }

    /**
     * Removes form-action section of CSP header for this controller.
     * This page needs to post back to the aem frontend.
     * Because each dealer has its own domain, we would need to dynamically whitelist all domains
     * while this would be possible, it would make the header larger than 8KB which is too big for some web servers
     */
    public function modifyCsp(array $appliedPolicies): array
    {
        unset($appliedPolicies['form-action']);
        return $appliedPolicies;
    }

}
