<?php
/**
 * @package     Infosys/RollingSitemap
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\RollingSitemap\Rewrite\Magento\Sitemap\Model;

use Infosys\RollingSitemap\Model\GetNextRollingSitemap;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sitemap\Model\EmailNotification;
use Magento\Sitemap\Model\EmailNotification as SitemapEmail;
use Magento\Sitemap\Model\Observer as OriginalObserver;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Preference for \Magento\Sitemap\Model\Observer that regenerates the oldest sitemap each run
 *
 * @see \Magento\Sitemap\Model\Observer
 */
class Observer
{
	/** @var GetNextRollingSitemap */
	private GetNextRollingSitemap $getNextRollingSitemap;

	/** @var ScopeConfigInterface */
	private ScopeConfigInterface $scopeConfig;

	/** @var SitemapEmail */
	private SitemapEmail $emailNotification;

	/** @var Emulation */
	private Emulation $appEmulation;

	/** @var LoggerInterface */
	private LoggerInterface $logger;

	/**
	 * @param GetNextRollingSitemap $getNextRollingSitemap
	 * @param ScopeConfigInterface $scopeConfig
	 * @param EmailNotification $emailNotification
	 * @param Emulation $appEmulation
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		GetNextRollingSitemap $getNextRollingSitemap,
		ScopeConfigInterface $scopeConfig,
		SitemapEmail $emailNotification,
		Emulation $appEmulation,
		LoggerInterface $logger
	) {
		$this->getNextRollingSitemap = $getNextRollingSitemap;
		$this->scopeConfig = $scopeConfig;
		$this->emailNotification = $emailNotification;
		$this->appEmulation = $appEmulation;
		$this->logger = $logger;
	}

	/**
	 * Generate a sitemap to replace the oldest sitemap
	 */
	public function scheduledGenerateSitemaps()
	{
		if (!$this->scopeConfig->isSetFlag(
			OriginalObserver::XML_PATH_GENERATION_ENABLED,
			ScopeInterface::SCOPE_STORE
		)) {
			return;
		}

		$errors = [];
		$recipient = $this->scopeConfig->getValue(
			OriginalObserver::XML_PATH_ERROR_RECIPIENT,
			ScopeInterface::SCOPE_STORE
		);

		$sitemap = $this->getNextRollingSitemap->execute();
		$storeId = $sitemap->getStoreId();

		try {
			$this->appEmulation->startEnvironmentEmulation(
				$storeId,
				Area::AREA_FRONTEND,
				true
			);

			$this->logger->info(sprintf('Rolling sitemap generation for store id: %s starting', $storeId));
			$sitemap->generateXml();
			$this->logger->info(sprintf('Rolling sitemap generation for store id: %s complete', $storeId));

		} catch (\Exception $e) {
			$this->logger->info(sprintf('Rolling sitemap generation for %s caught exception', $storeId));
			$this->logger->info($e->getMessage());
			$errors[] = $e->getMessage();

		} finally {
			$this->appEmulation->stopEnvironmentEmulation();
		}

		if ($errors && $recipient) {
			$this->emailNotification->sendErrors($errors);
		}
	}
}
