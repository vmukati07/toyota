<?php
/**
 * @package Infosys/XtentoOrderExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Infosys\XtentoOrderExport\Logger\OrderExportLogger;
use Infosys\XtentoOrderExport\Model\GPGEncrypt;

/**
 * Class to encrypt export file content before save
 */
class EncryptFilesBeforeSave implements ObserverInterface
{
    /**
     * @var \Infosys\XtentoOrderExport\Logger\OrderExportLogger
     */
    protected $logger;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPGEncrypt
     */
    protected $gpgEncrypt;

    /**
     * @var \Xtento\OrderExport\Model\DestinationFactory
     */
    protected $destinationFactory;

    /**
     * Initialize dependencies
     *
     * @param OrderExportLogger $logger
     * @param GPGEncrypt $gpgEncrypt
     * @param \Xtento\OrderExport\Model\DestinationFactory $destinationFactory
     * @return void
     */
    public function __construct(
        OrderExportLogger $logger,
        GPGEncrypt $gpgEncrypt,
        \Xtento\OrderExport\Model\DestinationFactory $destinationFactory
    ) {
        $this->logger = $logger;
        $this->gpgEncrypt = $gpgEncrypt;
        $this->destinationFactory = $destinationFactory;
    }

    /**
     * Method to encrypt file
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        try {
            //Custom code to get destination information
            $export = $observer->getExport();
            $generatedFiles = $export->getGeneratedFiles();

            $destinationIds = array_filter(explode("&", $export->getProfile()->getDestinationIds()));
            $destinationId = (int)$destinationIds[0];
            $destination = $this->destinationFactory->create()->load($destinationId);

            if ($destination->getId()) {
                $encryptFile = $destination->getEncryptFile();
                $encryptionKey = $destination->getEncPublicKey();
                $encryptionType = $destination->getEncryptionProtocol();

                //encrypting data using public key if encyption enabled
                if ((int)$encryptFile === 1) {
                    //Add encryption protocol according to backend configuration
                    switch ($encryptionType) {
                        case "pgp_encryption":
                            //encrypting file using pgp encryption
                            $generatedFiles = $this->gpgEncrypt->encryptExportFile($generatedFiles, $encryptionKey);
                            break;
                        default:
                            //default case
                            break;
                    }
                    $export->setGeneratedFiles($generatedFiles);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in encrypting file while orders export " . $e);
        }
    }
}
