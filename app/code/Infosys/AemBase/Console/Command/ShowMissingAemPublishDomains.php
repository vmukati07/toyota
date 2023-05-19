<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Console\Command;

use Infosys\AemBase\Model\AemBaseConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provide a Console Command that displays stores that don't have AEM Publisher Domain set
 */
class ShowMissingAemPublishDomains extends Command
{
	/** @var ScopeConfigInterface */
	private ScopeConfigInterface $scopeConfigInterface;

	/** @var StoreManagerInterface */
	private StoreManagerInterface $storeManager;

	/**
	 * @param ScopeConfigInterface $scopeConfig
	 * @param StoreManagerInterface $storeManager
	 * @param string|null $name
	 */
	public function __construct(
		ScopeConfigInterface $scopeConfig,
		StoreManagerInterface $storeManager,
		string $name = null
	) {
		$this->scopeConfigInterface = $scopeConfig;
		$this->storeManager = $storeManager;

		parent::__construct($name);
	}

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this->setDescription('Display a table of websites without AEM Publish Domain configured');

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

		$defaultPublishDomain = $this->scopeConfigInterface
			->getValue(AemBaseConfigProvider::AEM_DOMAIN);

		$output->writeln(sprintf(
			"<info>Default AEM Publish Domain: %s</info>",
			$defaultPublishDomain
		));

		$output->writeln(sprintf(
			"Stores with an AEM Publish Domain equal to the default will need to be addressed."
		));

		foreach ($storeIds as $storeId) {
			$publishDomain = $this->scopeConfigInterface->getValue(
				AemBaseConfigProvider::AEM_DOMAIN,
				'stores',
				$storeId
			);

			if ($defaultPublishDomain === $publishDomain) {
				$rowData[] = [
					$storeId,
					$publishDomain
				];
			}
		}

		if (count($rowData) > 0) {
			$table = new Table($output);
			$table->setHeaderTitle('Stores With Default AEM Publish Domain');
			$table->setHeaders(['Store Id', 'Publish Domain']);
			$table->setRows($rowData);
			$table->render();

			$output->writeln("<error>Missing AEM Domain Paths! Please correct!</error>");

		} else {
			$output->writeln("<info>No missing AEM Domain Paths</info>");
		}


		return Cli::RETURN_SUCCESS;
	}
}
