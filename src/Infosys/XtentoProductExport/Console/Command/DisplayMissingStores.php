<?php
/**
 * @package   Infosys/XtentoProductExport
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Infosys\XtentoProductExport\Model\CommonMethods;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Custom command to display all stores for which google profile not existed
 */
class DisplayMissingStores extends Command
{
    /**
     * @var CommonMethods
     */
    protected CommonMethods $common;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * Initialize dependencies
     *
     * @param CommonMethods $common
     * @param Json $json
     *
     * @return void
     */
    public function __construct(
        CommonMethods $common,
        Json $json
    ) {
        $this->common = $common;
        $this->json = $json;
        parent::__construct();
    }

    /**
     * Configure command options
     */
    protected function configure()
    {
        $this->setName('export:profile:missing')
            ->setDescription('Display stores for which google profile not existed');
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = [];
        $outputData = "";

        //get all stores for which profile not created
        $profileStores = $this->common->getStoresProfileNotExist();
        foreach ($profileStores as $store) {
            $data[] = $store['name'];
        }
        
        if ($data) {
            $outputData = $this->json->serialize($data);
        }

        $output->writeln("New profile needs to be created for stores: ".$outputData);
        
        return $this;
    }
}
