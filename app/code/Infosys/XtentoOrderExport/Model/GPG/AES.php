<?php
/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\GPG;

use Infosys\XtentoOrderExport\Model\GPG\Cipher;
use Infosys\XtentoOrderExport\Model\GPG\Utility;

/**
 * AES class for GPG Encryption
 */
class AES
{
    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\Cipher
     */
    protected Cipher $cipher;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\Utility
     */
    protected Utility $utility;

    /**
     * Initialize dependencies
     *
     * @param \Infosys\XtentoOrderExport\Model\GPG\Cipher $cipher
     * @param \Infosys\XtentoOrderExport\Model\GPG\Utility $utility
     */
    public function __construct(
        Cipher $cipher,
        Utility $utility
    ) {
        $this->cipher = $cipher;
        $this->utility = $utility;
    }

    /**
     * Encrypt function
     *
     * @param [type] $block
     * @param [type] $ctx
     * @return void
     */
    public function encrypt($block, $ctx)
    {
        $RCON = Cipher::$RCON;
        $S = Cipher::$S;
        
        $T1 = Cipher::$T1;
        $T2 = Cipher::$T2;
        $T3 = Cipher::$T3;
        $T4 = Cipher::$T4;
        
        $r = 0;
        $t0 = 0;
        $t1 = 0;
        $t2 = 0;
        $t3 = 0;
        
        $b = $this->utility->packoctets($block);
        $rounds = $ctx->rounds;
        $b0 = $b[0];
        $b1 = $b[1];
        $b2 = $b[2];
        $b3 = $b[3];
        
        for ($r = 0; $r < $rounds - 1; $r++) {
            $t0 = $b0 ^ $ctx->rk[$r][0];
            $t1 = $b1 ^ $ctx->rk[$r][1];
            $t2 = $b2 ^ $ctx->rk[$r][2];
            $t3 = $b3 ^ $ctx->rk[$r][3];
            
            $b0 = $T1[$t0 & 255] ^ $T2[($t1 >> 8) & 255]
                ^ $T3[($t2 >> 16) & 255] ^ $T4[$this->utility->zshift($t3, 24)];
            $b1 = $T1[$t1 & 255] ^ $T2[($t2 >> 8) & 255]
                ^ $T3[($t3 >> 16) & 255] ^ $T4[$this->utility->zshift($t0, 24)];
            $b2 = $T1[$t2 & 255] ^ $T2[($t3 >> 8) & 255]
                ^ $T3[($t0 >> 16) & 255] ^ $T4[$this->utility->zshift($t1, 24)];
            $b3 = $T1[$t3 & 255] ^ $T2[($t0 >> 8) & 255]
                ^ $T3[($t1 >> 16) & 255] ^ $T4[$this->utility->zshift($t2, 24)];
        }
        
        $r = $rounds - 1;
        
        $t0 = $b0 ^ $ctx->rk[$r][0];
        $t1 = $b1 ^ $ctx->rk[$r][1];
        $t2 = $b2 ^ $ctx->rk[$r][2];
        $t3 = $b3 ^ $ctx->rk[$r][3];
        
        $b[0] = $this->cipher->f1($t0, $t1, $t2, $t3) ^ $ctx->rk[$rounds][0];
        $b[1] = $this->cipher->f1($t1, $t2, $t3, $t0) ^ $ctx->rk[$rounds][1];
        $b[2] = $this->cipher->f1($t2, $t3, $t0, $t1) ^ $ctx->rk[$rounds][2];
        $b[3] = $this->cipher->f1($t3, $t0, $t1, $t2) ^ $ctx->rk[$rounds][3];
        
        return $this->utility->unpackoctets($b);
    }
}
