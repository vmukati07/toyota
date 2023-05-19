<?php

/**
 * @package   Infosys/XtentoOrderExport
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model;

use Infosys\XtentoOrderExport\Api\EncryptInterface;
use Infosys\XtentoOrderExport\Model\GPG;
use Infosys\XtentoOrderExport\Model\GPG\PublicKey;
use Infosys\XtentoOrderExport\Logger\OrderExportLogger;

/**
 * Class to encrypt files
 */
class GPGEncrypt implements EncryptInterface
{
    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG
     */
    protected $gpg;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\PublicKey
     */
    protected $publicKey;

    /**
     * @var \Infosys\XtentoOrderExport\Logger\OrderExportLogger
     */
    protected OrderExportLogger $logger;

    /**
     * Initialize dependencies
     *
     * @param \Infosys\XtentoOrderExport\Model\GPG $gpg
     * @param \Infosys\XtentoOrderExport\Model\GPG\PublicKey $publicKey
     * @param OrderExportLogger $logger
     */
    public function __construct(
        GPG $gpg,
        PublicKey $publicKey,
        OrderExportLogger $logger
    ) {
        $this->gpg = $gpg;
        $this->publicKey = $publicKey;
        $this->logger = $logger;
    }

    /**
     * Function to encrypt export file
     *
     * @param array $generatedFiles
     * @param string $encryptionKey
     * @return array
     */
    public function encryptExportFile($generatedFiles, $encryptionKey): ?array
    {
        try {
            //encrypting file using pgp encryption
            $pub_key = $this->publicKey->generatePublicKey($encryptionKey);
            foreach ($generatedFiles as $filename => $data) {
                $encryptedData = $this->gpg->encrypt($pub_key, $data);
                $newFileExtension = "gpg";
                $fileArray = explode('.', $filename);
                $updatedFileName = $fileArray[0] . '.' . $newFileExtension;
                $generatedFiles[$updatedFileName] = $encryptedData;

                //unset xml file from array
                unset($generatedFiles[$filename]);
            }

            return $generatedFiles;
        } catch (\Exception $e) {
            $this->logger->error("Error while encypting export file " . $e);
        }
    }
}
