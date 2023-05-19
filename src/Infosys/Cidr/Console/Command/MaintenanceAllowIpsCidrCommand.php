<?php
/**
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\Cidr\Console\Command;

use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Responsible for allowing a user to add IPs to the var/.maintenance.ip file via IP/CIDR string
 */
class MaintenanceAllowIpsCidrCommand extends Command
{
    private const INPUT_KEY_IP = 'ip';

    /** @var MaintenanceMode */
	private MaintenanceMode $maintenanceMode;

    /**
     * @param MaintenanceMode $maintenanceMode
     */
    public function __construct(
        MaintenanceMode $maintenanceMode
    ) {
        $this->maintenanceMode = $maintenanceMode;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $arguments = [
            new InputArgument(
                self::INPUT_KEY_IP,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Allowed IP addresses'
            ),
        ];

        $this->setName('infosys:allow-ips-cidr');
        $this->setDescription('Sets maintenance mode exempt IPs w/ CIDR suffixes');
        $this->setDefinition($arguments);

        parent::configure();
    }

    /**
     * Perform the additions, writing output as it progresses
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $ips = [];
        $ipsCidr = [];
        $ipsInvalid = [];

        $addresses = $input->getArgument(self::INPUT_KEY_IP);

        foreach ($addresses as $address) {
            $parts = explode("/", $address);

            if (count($parts) == 1) {
                if (filter_var($parts[0], FILTER_VALIDATE_IP)) {
                    $ips[] = $parts[0];
                    continue;
                }

            } elseif (count($parts) == 2) {
                if (filter_var($parts[0], FILTER_VALIDATE_IP)) {
                    $netmask = intval($parts[1]);

                    if ($netmask <= 32) {
                        $ipsCidr[] = [$parts[0], $parts[1]];
                        continue;
                    }
                }
            }

            $ipsInvalid[] = $address;
        }

        foreach ($ipsCidr as $ipCidr) {
            $start = ip2long($ipCidr[0]) & ((-1 << (32 - $ipCidr[1])));
            $startIp = long2ip($start);
            $end = ip2long($startIp) + pow(2, (32 - $ipCidr[1])) - 1;

            for ($i = $start; $i <= $end; $i++) {
                $ips[] = long2ip($i);
            }
        }

        foreach ($ips as $ip) {
            $output->writeln("<info>Added: " . $ip . "</info>");
        }

        foreach ($ipsInvalid as $ipInvalid) {
            $output->writeln("<error>Invalid: " . $ipInvalid . "</error>");
        }

        $mergedAddresses = array_unique(array_merge($this->maintenanceMode->getAddressInfo(), $ips));
        $this->maintenanceMode->setAddresses(implode(',', $mergedAddresses));

        return Cli::RETURN_SUCCESS;
    }
}
