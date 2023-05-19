<?php

/**
 * @package   Infosys/XtentoOrderExport
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Api;

/**
 * Encryption protocol interface
 */
interface EncryptInterface
{
    /**
     * Function to encrypt export file
     *
     * @param array $generatedFiles
     * @param string $encryptionKey
     * @return array
     */
    public function encryptExportFile($generatedFiles, $encryptionKey);
}
