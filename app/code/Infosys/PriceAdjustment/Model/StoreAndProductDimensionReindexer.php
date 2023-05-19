<?php
/**
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Model;

use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\Full;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\FullFactory;
use Magento\CatalogSearch\Model\Indexer\IndexerHandlerFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext as FulltextResource;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Store\Model\StoreDimensionProvider;

/**
 * Responsible for providing a service model that allows dimensional reindexing by storeid and products
 */
class StoreAndProductDimensionReindexer
{
	/** @var DimensionFactory */
	private DimensionFactory $dimensionFactory;

	/** @var Full */
	private Full $fullAction;

	/** @var FulltextResource */
	private FulltextResource $fulltextResource;

	/** @var IndexerHandlerFactory */
	private IndexerHandlerFactory $indexerHandlerFactory;

	/** @var PriceCalculationLogger */
	private PriceCalculationLogger $logger;

	/** @var array */
	private ?array $dimensions = null;

	/** @var IndexerInterface */
	private ?IndexerInterface $saveHandler = null;

	/**
	 * @param DimensionFactory $dimensionFactory
	 * @param FullFactory $fullFactory
	 * @param FulltextResource $fulltextResource
	 * @param IndexerHandlerFactory $indexerHandlerFactory
	 * @param PriceCalculationLogger $logger
	 */
	public function __construct(
		DimensionFactory $dimensionFactory,
		FullFactory $fullFactory,
		FulltextResource $fulltextResource,
		IndexerHandlerFactory $indexerHandlerFactory,
		PriceCalculationLogger $logger
	) {
		$this->dimensionFactory = $dimensionFactory;
		$this->fullAction = $fullFactory->create(['data' => '']);
		$this->fulltextResource = $fulltextResource;
		$this->indexerHandlerFactory = $indexerHandlerFactory;
		$this->logger = $logger;
	}

	/**
	 * Perform a dimensional reindex by store and products
	 *
	 * @param string $storeId
	 * @param array $productIds
	 */
	public function execute(string $storeId, array $productIds)
	{
		$this->getDimensions($storeId);
		$this->getSaveHandler();

		$entityIds = iterator_to_array(new \ArrayIterator($productIds));
		$mergedProductIds = array_unique(
			array_merge($entityIds, $this->fulltextResource->getRelationsByChild($entityIds))
		);

		$this->logger->info(__(
			"Starting reindex for store id (%1) for %2 products",
			$storeId,
			count($mergedProductIds))
		);

		if ($this->saveHandler->isAvailable($this->dimensions)) {
			$this->saveHandler->deleteIndex($this->dimensions, new \ArrayIterator($mergedProductIds));
			$this->saveHandler->saveIndex(
				$this->dimensions,
				$this->fullAction->rebuildStoreIndex($storeId, $mergedProductIds)
			);
		}

		$this->logger->info(__("Complete reindex by store id %1", $storeId));
	}

	/**
	 * Convenience method to cache our dimensions
	 *
	 * @param $storeId
	 * @return array
	 */
	private function getDimensions(string $storeId): array
	{
		if (!$this->dimensions) {
			$this->dimensions = [
				StoreDimensionProvider::DIMENSION_NAME => $this->dimensionFactory->create(
					StoreDimensionProvider::DIMENSION_NAME,
					$storeId
				)
			];
		}

		return $this->dimensions;
	}

	/**
	 * Convenience method to cache our saveHandler
	 *
	 * @return IndexerInterface
	 */
	private function getSaveHandler(): IndexerInterface
	{
		if (!$this->saveHandler) {
			$this->saveHandler = $this->indexerHandlerFactory->create(
				['data' => ['indexer_id' => 'catalogsearch_fulltext']] // Conscious decision to not make this a const
			);
		}

		return $this->saveHandler;
	}
}
