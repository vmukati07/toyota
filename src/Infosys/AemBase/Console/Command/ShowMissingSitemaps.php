<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provide a Console Command that displays stores that don't have a Sitemap specified
 */
class ShowMissingSitemaps extends Command
{
	/** @var StoreManagerInterface */
	private StoreManagerInterface $storeManager;

	/** @var CollectionFactory */
	private CollectionFactory $collectionFactory;

	/**
	 * @param CollectionFactory $collectionFactory
	 * @param StoreManagerInterface $storeManager
	 * @param string|null $name
	 */
	public function __construct(
		CollectionFactory $collectionFactory,
		StoreManagerInterface $storeManager,
		string $name = null
	) {
		$this->collectionFactory = $collectionFactory;
		$this->storeManager = $storeManager;

		parent::__construct($name);
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this->setDescription('Display a table of websites without configured Sitemaps');

		parent::configure();
	}

	/**
	 * Render a table that shows stores without a corresponding Sitemap
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws NoSuchEntityException
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$rowData = [];

		$stores = $this->storeManager->getStores();
		$storeIds = array_map(function ($store) {
			return $store->getId();
		}, $stores);

		$sitemaps = $this->collectionFactory->create()->getItems();
		$sitemapStoreIds = array_map(function ($sitemap) {
			return $sitemap->getData('store_id');
		}, $sitemaps);

		foreach ($storeIds as $storeId) {
			if (!in_array($storeId, $sitemapStoreIds)) {
				$rowData[] = [
					'store_id' => $storeId,
					'store_code' => $this->storeManager->getStore($storeId)->getCode()
				];
			}
		}


		if (count($rowData) > 0) {
			$table = new Table($output);
			$table->setHeaderTitle('Stores Missing Sitemaps');
			$table->setHeaders(['Store Id', 'Store Code']);
			$table->setRows($rowData);
			$table->render();

			$output->writeln("<error>Missing Sitemaps! Use infosys:aem:generate to generate</error>");

		} else {
			$output->writeln("<info>No missing Sitemaps</info>");
		}


		return Cli::RETURN_SUCCESS;
	}
}
