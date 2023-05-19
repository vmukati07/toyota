<?php

/**
 * @package     Infosys/SignifydFingerprintCart
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\SignifydFingerprintCart\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Model\StoreManagerInterface;
use Signifyd\Connect\Logger\Logger;
use Signifyd\Connect\Helper\ConfigHelper;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Filesystem\Driver\File;
use Signifyd\Connect\Model\Casedata;
use Signifyd\Connect\Model\CasedataFactory;
use Signifyd\Connect\Model\ResourceModel\Casedata as CasedataResourceModel;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Signifyd\Connect\Controller\Webhooks\Index as SignifydIndex;

/**
 * Controller action for handling webhook posts from Signifyd service
 */
class Index extends SignifydIndex
{
    /**
     * Constructor function
     *
     * @param Context $context
     * @param DateTime $dateTime
     * @param Logger $logger
     * @param ConfigHelper $configHelper
     * @param FormKey $formKey
     * @param File $file
     * @param CasedataFactory $casedataFactory
     * @param CasedataResourceModel $casedataResourceModel
     * @param OrderResourceModel $orderResourceModel
     * @param JsonSerializer $jsonSerializer
     * @param ResourceConnection $resourceConnection
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        Logger $logger,
        ConfigHelper $configHelper,
        FormKey $formKey,
        File $file,
        CasedataFactory $casedataFactory,
        CasedataResourceModel $casedataResourceModel,
        OrderResourceModel $orderResourceModel,
        JsonSerializer $jsonSerializer,
        ResourceConnection $resourceConnection,
        Emulation $emulation,
        StoreManagerInterface $storeManagerInterface
    ) {
        parent::__construct(
            $context,
            $dateTime,
            $logger,
            $configHelper,
            $formKey,
            $file,
            $casedataFactory,
            $casedataResourceModel,
            $orderResourceModel,
            $jsonSerializer,
            $resourceConnection,
            $emulation,
            $storeManagerInterface
        );
    }

    public function processRequest($request, $hash, $topic)
    {
        if (empty($hash) || empty($request)) {
            $this->getResponse()->appendBody("You have successfully reached the webhook endpoint");
            $this->getResponse()->setStatusCode(Http::STATUS_CODE_200);
            return;
        }

        try {
            $requestJson = (object) $this->jsonSerializer->unserialize($request);
        } catch (\InvalidArgumentException $e) {
            $message = 'Invalid JSON provided on request body';
            $this->getResponse()->appendBody($message);
            $this->logger->debug("WEBHOOK: {$message}");
            $this->getResponse()->setStatusCode(Http::STATUS_CODE_400);
            return;
        }

        if (isset($requestJson->caseId) === false) {
            $httpCode = Http::STATUS_CODE_200;
            throw new LocalizedException(__("Invalid body, no 'caseId' field found on request"));
        }

        /** @var $case \Signifyd\Connect\Model\Casedata */
        $case = $this->casedataFactory->create();

        try {
            $this->casedataResourceModel->loadForUpdate($case, (string) $requestJson->caseId, 'code');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        switch ($topic) {
            case 'cases/test':
                // Test is only verifying that the endpoint is reachable. So we just complete here
                $this->getResponse()->setStatusCode(Http::STATUS_CODE_200);
                return;

            case 'cases/creation':
                if ($this->configHelper->isScoreOnly() === false) {
                    $message = 'Case creation will not be processed by Magento';
                    $this->getResponse()->appendBody($message);
                    $this->logger->debug("WEBHOOK: {$message}");
                    $this->getResponse()->setStatusCode(Http::STATUS_CODE_200);
                    return;
                }
                break;
        }

        $this->emulation->startEnvironmentEmulation(0, 'adminhtml');

        try {
            $httpCode = null;

            if ($case->isEmpty()) {
                $httpCode = Http::STATUS_CODE_400;
                throw new LocalizedException(__("Case {$requestJson->caseId} on request not found on Magento"));
            }

            $signifydWebhookApi = $this->configHelper->getSignifydWebhookApi($case);

            if ($signifydWebhookApi->validWebhookRequest($request, $hash, $topic) == false) {
                $httpCode = Http::STATUS_CODE_403;
                throw new LocalizedException(__("Invalid webhook request"));
            } elseif ($this->configHelper->isEnabled($case) == false) {
                $httpCode = Http::STATUS_CODE_400;
                throw new LocalizedException(__('Signifyd plugin it is not enabled'));
            } elseif ($case->getMagentoStatus() == Casedata::WAITING_SUBMISSION_STATUS) {
                $httpCode = Http::STATUS_CODE_400;
                throw new LocalizedException(__("Case {$requestJson->caseId} it is not ready to be updated"));
            } elseif ($case->getMagentoStatus() == Casedata::PRE_AUTH) {
                $httpCode = Http::STATUS_CODE_200;
                throw new LocalizedException(
                    __("Case {$requestJson->caseId} already completed by synchronous response, no action will be taken")
                );
            }

            $this->logger->info("WEBHOOK: Processing case {$case->getId()}");

            $this->emulation->startEnvironmentEmulation(0, 'adminhtml');

            //Checking Order object on Case
            if ($case->getOrder()) {
                $this->storeManagerInterface->setCurrentStore($case->getOrder()->getStore()->getStoreId());
            }

            $currentCaseHash = sha1(implode(',', $case->getData()));
            $case->updateCase($requestJson);
            $newCaseHash = sha1(implode(',', $case->getData()));

            if ($currentCaseHash == $newCaseHash) {
                $httpCode = Http::STATUS_CODE_200;
                throw new LocalizedException(
                    __("Case {$requestJson->caseId} already update with this data, no action will be taken")
                );
            }

            $case->updateOrder();

            $this->casedataResourceModel->save($case);
        } catch (\Exception $e) {
            // Triggering case save to unlock case
            if ($case instanceof \Signifyd\Connect\Model\ResourceModel\Casedata) {
                $this->casedataResourceModel->save($case);
            }

            $httpCode = empty($httpCode) ? 403 : $httpCode;
            $this->getResponse()->appendBody($e->getMessage());
            $this->logger->error("WEBHOOK: {$e->getMessage()}");
        }

        $httpCode = empty($httpCode) ? 200 : $httpCode;
        $this->getResponse()->setStatusCode($httpCode);
        $this->emulation->stopEnvironmentEmulation();
    }
}
