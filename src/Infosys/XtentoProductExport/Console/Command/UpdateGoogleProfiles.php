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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Infosys\XtentoProductExport\Model\CommonMethods;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Custom command to update existing Google feed profiles
 */
class UpdateGoogleProfiles extends Command
{
    public const PROFILE = "profile_id";

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
        $options = [
            new InputOption(
                self::PROFILE,
                null,
                InputOption::VALUE_REQUIRED,
                'PROFILE'
            )
        ];

        $this->setName('export:profile:update')
            ->setDescription('Update products export profile for existing stores by command line')
            ->setDefinition($options);
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
        $inputProfileId = $input->getOption(self::PROFILE);

        if (!$inputProfileId) {
            $output->writeln("Please provide a profile id.");
            return $this;
        }

        if (!$this->common->getProfileData($inputProfileId)) {
            $output->writeln("Please provide a valid profile id.");
            return $this;
        }
        
        try {
            //get input profile data
            $sourceProfile = $this->common->getProfileData($inputProfileId);

            //get all stores for which profiles already existed
            $existingProfiles = $this->common->getExistingProfiles($inputProfileId);

            $updatedProfiles = [];
            $outputData = "";
            
            foreach ($existingProfiles as $profile) {
                $profileId = (int) $profile['profile_id'];
                $storeId = $profile['store_id'];
                $storeName = (string) $profile['name'];
                
                $profileData = $sourceProfile;

                //unset profile id and set store level data
                unset($profileData['profile_id']);
                $profileData['name'] = $storeName.' - Google Shopping Feed';
                $profileData['store_id'] = $storeId;

                //stores for which profile needs to be updated
                $updatedProfiles[] = $storeName;
            
                //update profile data
                $this->common->updateProfileData($profileData, $profileId);
            }

            if ($updatedProfiles) {
                $outputData = $this->json->serialize($updatedProfiles);
            }
            $output->writeln("Google profiles updated successfully for stores: ".$outputData);

        } catch (\Exception $e) {
            $output->writeln($e);
        }
        
        return $this;
    }
}
