<?php
/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\GPG;

use Infosys\XtentoOrderExport\Model\GPG\Utility;
use Infosys\XtentoOrderExport\Model\GPG\Cipher;

class ExpandedKey
{

    /**
     * Rounds variable
     *
     * @var [type]
     */
    public $rounds;
    /**
     * RK variable
     *
     * @var [type]
     */
    public $rk;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\Utility
     */
    protected Utility $utility;

    /**
     * Initialize dependencies
     *
     * @param \Infosys\XtentoOrderExport\Model\GPG\Utility $utility
     */
    public function __construct(
        Utility $utility
    ) {
        $this->utility = $utility;
    }

    /**
     * Key function
     *
     * @param [type] $key
     */
    public function getExpandedKey($key)
    {
        $RCON = Cipher::$RCON;
        $S = Cipher::$S;

        $maxkc = Cipher::$maxkc;
        $maxrk = Cipher::$maxrk;

        $kc = 0;
        $i = 0;
        $j = 0;
        $r = 0;
        $t = 0;
        $rounds = 0;
        $keySched = array_fill(0, $maxrk + 1, 0);
        $keylen = strlen($key);
        $k = array_fill(0, $maxkc, 0);
        $tk = array_fill(0, $maxkc, 0);
        $rconpointer = 0;

        if ($keylen == 16) {
            $rounds = 10;
            $kc = 4;
        } elseif ($keylen == 24) {
            $rounds = 12;
            $kc = 6;
        } elseif ($keylen == 32) {
            $rounds = 14;
            $kc = 8;
        } else {
            return;
        }

        for ($i = 0; $i < $maxrk + 1; $i++) {
            $keySched[$i] = array_fill(0, 4, 0);
        }
        for ($i = 0, $j = 0; $j < $keylen; $j++, $i += 4) {
            if ($i < $keylen) {
                $k[$j] = ord($key[$i]) | (ord($key[$i + 1]) << 0x8) |
                    (ord($key[$i + 2]) << 0x10) | (ord($key[$i + 3]) << 0x18);
            } else {
                $k[$j] = 0;
            }
        }
        for ($j = $kc - 1; $j >= 0; $j--) {
            $tk[$j] = $k[$j];
        }

        $r = 0;
        $t = 0;
        for ($j = 0; ($j < $kc) && ($r < $rounds + 1);) {
            for (; ($j < $kc) && ($t < 4); $j++, $t++) {
                $keySched[$r][$t] = $tk[$j];
            }
            if ($t == 4) {
                $r++;
                $t = 0;
            }
        }

        while ($r < $rounds + 1) {
            $temp = $tk[$kc - 1];

            $tk[0] ^= hexdec($S[$this->utility->b1($temp)]) | (hexdec($S[$this->utility->b2($temp)]) << 0x8) |
                (hexdec($S[$this->utility->b3($temp)]) << 0x10) | (hexdec($S[$this->utility->b0($temp)]) << 0x18);
            $tk[0] ^= $RCON[$rconpointer++];

            if ($kc != 8) {
                for ($j = 1; $j < $kc; $j++) {
                    $tk[$j] ^= $tk[$j - 1];
                }
            } else {
                for ($j = 1; $j < $kc / 2; $j++) {
                    $tk[$j] ^= $tk[$j - 1];
                }
 
                $temp = $tk[$kc / 2 - 1];
                $tk[$kc / 2] ^= hexdec($S[$this->utility->b0($temp)]) | (hexdec($S[$this->utility->b1($temp)]) << 0x8) |
                    (hexdec($S[$this->utility->b2($temp)] << 0x10)) | (hexdec($S[$this->utility->b3($temp)]) << 0x18);

                for ($j = $kc / 2 + 1; $j < $kc; $j++) {
                    $tk[$j] ^= $tk[$j - 1];
                }
            }

            for ($j = 0; ($j < $kc) && ($r < $rounds + 1);) {
                for (; ($j < $kc) && ($t < 4); $j++, $t++) {
                    $keySched[$r][$t] = $tk[$j];
                }
                if ($t == 4) {
                    $r++;
                    $t = 0;
                }
            }
        }
    
        $this->rounds = $rounds;
        $this->rk = $keySched;
        return $this;
    }
}
