<?php
/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\GPG;

class Utility
{
    /**
     * Undocumented function
     *
     * @param [type] $x
     * @return null|bool|int|float|string|void
     */
    public function b0($x)
    {
        return ($x & 0xff);
    }
    /**
     * Undocumented function
     *
     * @param [type] $x
     * @return null|bool|int|float|string|void
     */
    public function b1($x)
    {
        return (($x >> 0x8) & 0xff);
    }
    /**
     * Undocumented function
     *
     * @param [type] $x
     * @return null|bool|int|float|string|void
     */
    public function b2($x)
    {
        return (($x >> 0x10) & 0xff);
    }
    /**
     * Undocumented function
     *
     * @param [type] $x
     * @return null|bool|int|float|string|void
     */
    public function b3($x)
    {
        return (($x >> 0x18) & 0xff);
    }
    /**
     * Undocumented function
     *
     * @param [type] $x
     * @param [type] $s
     * @return void
     */
    public function zshift($x, $s)
    {
        $res = $x >> $s;
        
        $pad = 0;
        for ($i = 0; $i < 32 - $s; $i++) {
            $pad += (1 << $i);
        }
        
        return $res & $pad;
    }
    /**
     * Undocumented function
     *
     * @param [type] $octets
     * @return void
     */
    public function packoctets($octets)
    {
        $i = 0;
        $j = 0;
        $len = count($octets);
        $b = array_fill(0, $len / 4, 0);
        
        if (!$octets || $len % 4) {
            return;
        }
        
        for ($i = 0, $j = 0; $j < $len; $j += 4) {
            $b[$i++] = $octets[$j] | ($octets[$j + 1] << 0x8) | ($octets[$j + 2] << 0x10) | ($octets[$j + 3] << 0x18);
            
        }
        
        return $b;
    }
    /**
     * Undocumented function
     *
     * @param [type] $packed
     * @return void
     */
    public function unpackoctets($packed)
    {
        $j = 0;
        $i = 0;
        $l = count($packed);
        $r = array_fill(0, $l * 4, 0);
        
        for ($j = 0; $j < $l; $j++) {
            $r[$i++] = $this->b0($packed[$j]);
            $r[$i++] = $this->b1($packed[$j]);
            $r[$i++] = $this->b2($packed[$j]);
            $r[$i++] = $this->b3($packed[$j]);
        }
        
        return $r;
    }
    /**
     * Undocumented function
     *
     * @param [type] $h
     * @return void
     */
    public function hex2bin($h)
    {
        if (strlen($h) % 2) {
            $h += "0";
        }

        $r = "";
        $h1 = strlen($h);

        for ($i = 0; $i < $h1; $i += 2) {
            $r .= chr(intval($h[$i], 16) * 16 + intval($h[$i + 1], 16));
        }

        return $r;
    }
    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return null|bool|int|float|string|void
     */
    public function crc24($data)
    {
        $crc = 0xb704ce;
        $data1 = strlen($data);

        for ($n = 0; $n < $data1; $n++) {
            $crc ^= (ord($data[$n]) & 0xff) << 0x10;
            for ($i = 0; $i < 8; $i++) {
                $crc <<= 1;
                if ($crc & 0x1000000) {
                    $crc ^= 0x1864cfb;
                }
            }
        }
        
        return
            chr(($crc >> 0x10) & 0xff) .
            chr(($crc >> 0x8) & 0xff) .
            chr($crc & 0xff);
    }
    /**
     * Undocumented function
     *
     * @param [type] $len
     * @param [type] $textmode
     * @return null|bool|int|float|string|void
     */
    public function srandom($len, $textmode)
    {
        $r = "";
        for ($i = 0; $i < $len;) {
            $t = random_int(0, 0xff);
            if ($t == 0 && $textmode) {
                continue;
            }
            $i++;

            $r .= chr($t);
        }

        return $r;
    }
    /**
     * Undocumented function
     *
     * @return null|bool|int|float|string|void
     */
    public function crandom()
    {
        return random_int(0, 0xff);
    }
}
