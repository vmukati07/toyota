<?php
/**
 * @package     Infosys/RollingSitemap
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\RollingSitemap\ViewModel\Sitemap;

use Infosys\RollingSitemap\Model\GetNextRollingSitemap;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provide data for the warning.phtml template
 */
class Warning implements ArgumentInterface
{
	/** @var GetNextRollingSitemap */
	private GetNextRollingSitemap $getNextRollingSitemap;

	/** @var StoreManagerInterface */
	private StoreManagerInterface $storeManager;

	/** @var UrlInterface */
	private UrlInterface $url;

	/**
	 * @param GetNextRollingSitemap $getNextRollingSitemap
	 * @param StoreManagerInterface $storeManager
	 * @param UrlInterface $url
	 */
	public function __construct(
		GetNextRollingSitemap $getNextRollingSitemap,
		StoreManagerInterface $storeManager,
		UrlInterface $url
	) {
		$this->getNextRollingSitemap = $getNextRollingSitemap;
		$this->storeManager = $storeManager;
		$this->url = $url;
	}

	/**
	 * Return a link to the sitemap configuration as a convenience to the user
	 *
	 * @return string
	 */
	public function getConfigurationLink(): string
	{
		return $this->url->getUrl('adminhtml/system_config/edit', ['section' => 'sitemap']);
	}

	/**
	 * Return the store code of the next store that will be generated
	 *
	 * @return string
	 * @throws NoSuchEntityException
	 */
	public function getNextStoreCode(): string
	{
		return $this->storeManager->getStore(
			$this->getNextRollingSitemap->execute()->getStoreId()
		)->getCode();
	}
}
