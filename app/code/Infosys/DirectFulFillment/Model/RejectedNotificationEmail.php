<?php

/**
 * @package Infosys/DirectFulFillment
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DirectFulFillment\Model;

use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Infosys\DirectFulFillment\Model\Config\Configurations;
use Infosys\DirectFulFillment\Logger\DDOALogger;

/**
 * Class to send rejected item notification email
 */
class RejectedNotificationEmail
{
    const XML_EMAIL_TEMPLATE = 'rejected_item_email_template';

    /**
     * @var Configurations
     */
    protected Configurations $config;

    /**
     * @var TransportBuilder
     */
    protected TransportBuilder $transportBuilder;

    /**
     * @var StateInterface
     */
    protected StateInterface $inlineTranslation;

    /**
     * @var DDOALogger
     */
    protected DDOALogger $logger;

    /**
     * Constructor function
     *
     * @param Configurations $config
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param DDOALogger $logger
     */
    public function __construct(
        Configurations $config,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        DDOALogger $logger
    ) {
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
    }

    /**
     * Method to send rejected item notification email to dealer
     *
     * @param object $order
     * @param array $orderItemData
     * @return void
     */
    public function sendRejectedNotificationEmail($order, $orderItemData): void
    {
        try {
            $storeId = $order->getStore()->getId();
            $sender = $this->config->getEmailSender($storeId);
            $toEmail = $this->config->getNotificationEmailAddress($storeId);

            $this->inlineTranslation->suspend();
            $vars = [
                'order_number' => $order->getIncrementId(),
                'sku' => $orderItemData['sku'],
                'quantity' => $orderItemData['qty'],
                'df_status' => $orderItemData['direct_fulfillment_status'],
                'reason' => $orderItemData['order_history_comment_item_level']
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier(
                self::XML_EMAIL_TEMPLATE
            )->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ]
            )->setTemplateVars(
                $vars
            )->setFromByScope(
                $sender,
                $storeId
            )->addTo(
                $toEmail
            )->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->error("Error while sending rejected item notification email " . $e);
        }
    }
}
