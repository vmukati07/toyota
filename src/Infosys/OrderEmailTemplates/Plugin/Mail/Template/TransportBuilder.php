<?php

/**
 * @package Infosys/OrderEmailTemplates
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\OrderEmailTemplates\Plugin\Mail\Template;

use Infosys\OrderEmailTemplates\Model\Config\Configuration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Infosys\OrderEmailTemplates\Logger\OrderEmailLogger;


/**
 * TransportBuilder for Mail Templates
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class TransportBuilder
{
	protected Template $paramData;

	protected Configuration $config;

	protected StoreManagerInterface $storeManager;

	protected OrderEmailLogger $logger;

	protected OrderRepository $orderRepository;

	private InvoiceRepositoryInterface $invoiceRepository;

	private ShipmentRepositoryInterface $shipmentRepository;

	private CreditmemoRepositoryInterface $creditmemoRepository;


	/**
	 * Constructor function
	 *
	 * @param Template $paramData
	 * @param Configuration $config
	 * @param StoreManagerInterface $storeManager
	 * @param OrderEmailLogger $logger
	 * @param OrderRepository $orderRepository
	 * @param InvoiceRepositoryInterface $invoiceRepository
	 * @param ShipmentRepositoryInterface $shipmentRepository
	 * @param CreditmemoRepositoryInterface $creditmemoRepository
	 */
	public function __construct(
		Template $paramData,
		Configuration $config,
		StoreManagerInterface $storeManager,
		OrderEmailLogger $logger,
		OrderRepository $orderRepository,
		InvoiceRepositoryInterface $invoiceRepository,
		ShipmentRepositoryInterface $shipmentRepository,
		CreditmemoRepositoryInterface $creditmemoRepository
	) {
		$this->paramData = $paramData;
		$this->config = $config;
		$this->storeManager = $storeManager;
		$this->logger = $logger;
		$this->orderRepository = $orderRepository;
		$this->invoiceRepository = $invoiceRepository;
		$this->shipmentRepository = $shipmentRepository;
		$this->creditmemoRepository = $creditmemoRepository;
	}

	/**
	 * Set Reply to email on header
	 *
	 * @param \Magento\Framework\Mail\Template\TransportBuilder $subject
	 */
	public function afterAddTo(
		\Magento\Framework\Mail\Template\TransportBuilder $subject
	) {
		$storeId = '';
		$orderId = $this->paramData->getRequest()->getParam('order_id');
		$shipmentId = $this->paramData->getRequest()->getParam('shipment_id');
		$invoiceId = $this->paramData->getRequest()->getParam('invoice_id');
		$creditmemoId = $this->paramData->getRequest()->getParam('creditmemo_id');
		$this->logger->info("------------------------");
		if (!empty($orderId)) {
			$order = $this->orderRepository->get($orderId);
			$storeId = $order->getStoreId();
			$this->logger->info("order_id: " . $orderId . " store_id: " . $storeId);
		} else if (!empty($shipmentId)) {
			$shipment = $this->shipmentRepository->get($shipmentId);
			$storeId = $shipment->getOrder()->getStoreId();
			$this->logger->info("shipment_id: " . $shipmentId . " store_id: " . $storeId);
		} else if (!empty($invoiceId)) {
			$invoice = $this->invoiceRepository->get($invoiceId);
			$storeId = $invoice->getOrder()->getStoreId();
			$this->logger->info("invoice_id: " . $invoiceId . " store_id: " . $storeId);
		} else if (!empty($creditmemoId)) {
			$creditmemo = $this->creditmemoRepository->get($creditmemoId);
			$storeId = $creditmemo->getOrder()->getStoreId();
			$this->logger->info("creditmemo_id: " . $creditmemoId . "store_id: " . $storeId);
		}

		if (!empty($storeId)) {
			$dealerEmail = $this->config->getDealerEmail($storeId);
			$this->logger->info("Dealer Email: " . $dealerEmail);
			if (!empty($dealerEmail) && is_string($dealerEmail)) {
				$subject->setReplyTo($dealerEmail);
			} else {
				$this->logger->error("Please Configure the dealer email for the Store ID :- " . $storeId);
			}
		}
		$this->logger->info("------------------------");
		return $subject;
	}
}
