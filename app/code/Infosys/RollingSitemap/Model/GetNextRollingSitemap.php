<?php
/**
 * @package     Infosys/RollingSitemap
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\RollingSitemap\Model;

use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Sitemap\Model\Sitemap;
use Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory;

/**
 * Allows retrieval of the next Sitemap that will be generated for rolling sitemap generation
 */
class GetNextRollingSitemap
{
	/** @var CollectionFactory */
	private CollectionFactory $collectionFactory;

	/**
	 * @param CollectionFactory $collectionFactory
	 */
	public function __construct(
		CollectionFactory $collectionFactory
	) {
		$this->collectionFactory = $collectionFactory;
	}

	/**
	 * Return the next Sitemap that will be generated for the rolling sitemap generation
	 *
	 * @return DataObject
	 */
	public function execute(): DataObject
	{
		$collection = $this->collectionFactory->create();

		// 'NULL' gets sorted above any value here, so any sitemaps that have yet to be generated receive priority
		$collection->addOrder('sitemap_time', Collection::SORT_ORDER_ASC);

		/* @var $sitemap Sitemap */
		return $collection->getFirstItem();
	}
}
