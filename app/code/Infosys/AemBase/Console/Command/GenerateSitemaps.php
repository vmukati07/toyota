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
use Magento\Sitemap\Model\SitemapFactory;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Console Command that allows for mass creation of missing Sitemaps
 */
class GenerateSitemaps extends Command
{
	const DEFAULT_FILENAME = 'sitemap.xml';
	const DEFAULT_PATH = '/media/sitemap/';

	/** @var SiteMapFactory */
	private SiteMapFactory $sitemapFactory;

	/** @var StoreManagerInterface */
	private StoreManagerInterface $storeManager;

	/** @var CollectionFactory */
	private CollectionFactory $collectionFactory;

	/**
	 * @param SitemapFactory $sitemapFactory
	 * @param CollectionFactory $collectionFactory
	 * @param StoreManagerInterface $storeManager
	 * @param string|null $name
	 */
	public function __construct(
		SitemapFactory $sitemapFactory,
		CollectionFactory $collectionFactory,
		StoreManagerInterface $storeManager,
		string $name = null
	) {
		$this->collectionFactory = $collectionFactory;
		$this->sitemapFactory = $sitemapFactory;
		$this->storeManager = $storeManager;

		parent::__construct($name);
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this->setDescription('Generate a Sitemap for each store without one');

		parent::configure();
	}

	/**
	 * Render a table that shows stores without a corresponding Sitemap
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws NoSuchEntityException
	 * @throws \Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$stores = $this->storeManager->getStores();
		$storeIds = array_map(function ($store) {
			return $store->getId();
		}, $stores);

		$sitemaps = $this->collectionFactory->create()->getItems();
		$sitemapStoreIds = array_map(function ($sitemap) {
			return $sitemap->getData('store_id');
		}, $sitemaps);

		$helper = $this->getHelper('question');

		$filenameQuestion = new Question(
			sprintf("Sitemap filename? (default: %s) ",  self::DEFAULT_FILENAME),
			self::DEFAULT_FILENAME
		);
		$sitemapFilename = $helper->ask($input, $output, $filenameQuestion);

		$pathQuestion = new Question(
			sprintf("Sitemap path? (default: %s) ", self::DEFAULT_PATH),
			self::DEFAULT_PATH
		);
		$sitemapPath = $helper->ask($input, $output, $pathQuestion);

		foreach ($storeIds as $storeId) {
			if (!in_array($storeId, $sitemapStoreIds)) {
				$this->generateSitemap(
					$this->storeManager->getStore($storeId)->getCode(),
					(int) $storeId,
					$sitemapFilename,
					$sitemapPath
				);

				$output->writeln(
					sprintf(
						"<info>Sitemap generated for store code '%s'</info>",
						$this->storeManager->getStore($storeId)->getCode()
					)
				);
			}
		}

		return Cli::RETURN_SUCCESS;
	}

	/**
	 * Generate a single sitemap
	 *
	 * @param string $storeCode
	 * @param int $storeId
	 * @param string $filename
	 * @param string $path
	 * @throws \Exception
	 */
	private function generateSitemap(
		string $storeCode,
		int $storeId,
		string $filename,
		string $path
	): void {
		$data = [
			'sitemap_filename' => sprintf('%s_%s', $storeCode, $filename),
			'sitemap_path' => $path,
			'store_id' => $storeId
		];

		$sitemap = $this->sitemapFactory->create();
		$sitemap->setData($data);
		$sitemap->save();
	}
}
