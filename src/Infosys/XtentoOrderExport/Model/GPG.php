<?php

/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model;

use Infosys\XtentoOrderExport\Model\GPG\Utility;
use Infosys\XtentoOrderExport\Model\GPG\ExpandedKey;
use Infosys\XtentoOrderExport\Model\GPG\AES;
use Infosys\XtentoOrderExport\Model\GPG\PublicKey;
use Infosys\XtentoOrderExport\Model\GPG\GpgGlobals;

/**
 * Class for GPG library
 */
class GPG
{
    /**
     * @var integer
     */
    private $width = 16;

    /**
     * @var array
     */
    private $el = [3, 5, 9, 17, 513, 1025, 2049, 4097];

    /**
     * @var string
     */
    private $version = "1.6.1";

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\PublicKey
     */
    protected PublicKey $publicKey;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\GpgGlobals
     */
    protected GpgGlobals $gpgGlobals;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\AES
     */
    protected AES $aes;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\Utility
     */
    protected Utility $utility;

    /**
     * @var \Infosys\XtentoOrderExport\Model\GPG\ExpandedKey
     */
    protected ExpandedKey $expandedKey;

    /**
     * Initialize dependencies
     *
     * @param \Infosys\XtentoOrderExport\Model\GPG\PublicKey $publicKey
     * @param \Infosys\XtentoOrderExport\Model\GPG\GpgGlobals $gpgGlobals
     * @param \Infosys\XtentoOrderExport\Model\GPG\AES $aes
     * @param \Infosys\XtentoOrderExport\Model\GPG\Utility $utility
     * @param \Infosys\XtentoOrderExport\Model\GPG\ExpandedKey $expandedKey
     */
    public function __construct(
        PublicKey $publicKey,
        GpgGlobals $gpgGlobals,
        AES $aes,
        Utility $utility,
        ExpandedKey $expandedKey
    ) {
        $this->publicKey = $publicKey;
        $this->gpgGlobals = $gpgGlobals;
        $this->aes = $aes;
        $this->utility = $utility;
        $this->expandedKey = $expandedKey;
    }

    /**
     * Encrypt function
     *
     * @param [type] $key
     * @param [type] $text
     * @return void
     */
    private function gpgencrypt($key, $text)
    {
        $i = 0;
        $len = strlen($text);
        $iblock = array_fill(0, $this->width, 0);
        $rblock = array_fill(0, $this->width, 0);
        $ct = array_fill(0, $this->width + 2, 0);
     
        $cipher = "";

        if ($len % $this->width) {
            for ($i = ($len % $this->width); $i < $this->width; $i++) {
                $text .= "\0";
            }
        }
     
        $ekey = $this->expandedKey->getExpandedKey($key);

        for ($i = 0; $i < $this->width; $i++) {
            $iblock[$i] = 0;
            $rblock[$i] = $this->utility->crandom();
        }

        $len1 = strlen($text);

        for ($n = 0; $n < $len1; $n += $this->width) {
            $iblock = $this->aes->encrypt($iblock, $ekey);
            for ($i = 0; $i < $this->width; $i++) {
                $iblock[$i] ^= ord($text[$n + $i]);
                $cipher .= chr($iblock[$i]);
            }
        }
     
        return substr($cipher, 0, $len);
    }

    /**
     * GPG header function
     *
     * @param [type] $tag
     * @param [type] $len
     * @return void
     */
    private function gpgheader($tag, $len)
    {
        $h = "";
        if ($len < 0x100) {
            $h .= chr($tag);
            $h .= chr($len);
        } elseif ($len < 0x10000) {
            $tag+=1;
            $h .= chr($tag);
            $h .= $this->writeNumber($len, 2);
        } else {
            $tag+=2;
            $h .= chr($tag);
            $h .= $this->writeNumber($len, 4);
        }
        return $h;
    }

    /**
     * Write number function
     *
     * @param [type] $n
     * @param [type] $bytes
     * @return void
     */
    private function writeNumber($n, $bytes)
    {
        // credits for this function go to OpenPGP.js
        $b = '';
        for ($i = 0; $i < $bytes; $i++) {
            $b .= chr(($n >> (8 * ($bytes - $i - 1))) & 0xff);
        }
        return $b;
    }

    /**
     * GPG session function
     *
     * @param [type] $key_id
     * @param [type] $key_type
     * @param [type] $session_key
     * @param [type] $public_key
     * @return void
     */
    private function gpgsession($key_id, $key_type, $session_key, $public_key)
    {

        $mod = [];
        $exp = [];
        $enc = "";
     
        $s = base64_decode($public_key);
        $l = floor((ord($s[0]) * 256 + ord($s[1]) + 7) / 8);
        $l = (int)$l;
        $mod = $this->gpgGlobals->mpi2b(substr($s, 0, $l + 2));
        if ($key_type) {
            $grp = [];
            $y = [];
            $B = [];
            $C = [];

            $l2 = floor((ord($s[$l + 2]) * 256 + ord($s[$l + 3]) + 7) / 8) + 2;
            $l2 = (int)$l2;
            $grp = $this->gpgGlobals->mpi2b(substr($s, $l + 2, $l2));
            $y = $this->gpgGlobals->mpi2b(substr($s, $l + 2 + $l2));
            $exp[0] = $this->el[$this->utility->crandom() & 7];
            $B = $this->gpgGlobals->bmodexp($grp, $exp, $mod);
            $C = $this->gpgGlobals->bmodexp($y, $exp, $mod);
        } else {
            $exp = $this->gpgGlobals->mpi2b(substr($s, $l + 2));
        }

        $c = 0;
        $lsk = strlen($session_key);
        for ($i = 0; $i < $lsk; $i++) {
            $c += ord($session_key[$i]);
        }
        $c &= 0xffff;

        $lm = ($l - 2) * 8 + 2;
        $lm1 = $lm / 256;
        $lm1 = (int)$lm1;
        $lm2 = $lm % 256;
        $lm2 = (int)$lm2;
        $c1 = $c / 256;
        $c1 = (int)$c1;
        $m = chr($lm1) . chr($lm2) .
            chr(2) . $this->utility->srandom($l - $lsk - 6, 1) . "\0" .
            chr(7) . $session_key .
            chr($c1) . chr($c & 0xff);

        if ($key_type) {
            $enc = $this->gpgGlobals->b2mpi($B) . $this->gpgGlobals->b2mpi(
                $this->gpgGlobals->bmod($this->gpgGlobals->bmul($this->gpgGlobals->mpi2b($m), $C), $mod)
            );
            return $this->gpgheader(0x84, strlen($enc) + 10) .
                chr(3) . $key_id . chr(16) . $enc;
        } else {
            $enc = $this->gpgGlobals->b2mpi($this->gpgGlobals->bmodexp($this->gpgGlobals->mpi2b($m), $exp, $mod));
            return $this->gpgheader(0x84, strlen($enc) + 10) .
                chr(3) . $key_id . chr(1) . $enc;
        }
    }

    /**
     * GPG Literal function
     *
     * @param [type] $text
     * @return void
     */
    private function gpgliteral($text)
    {
        if (strpos($text, "\r\n") === false) {
            $text = str_replace("\n", "\r\n", $text);
        }

        return chr(11 | 0xC0) . chr(255) . $this->writeNumber(strlen($text) + 10, 4) . "t" . chr(4) .
        "file\0\0\0\0" . $text;
    }

    /**
     * GPG data function
     *
     * @param [type] $key
     * @param [type] $text
     * @return void
     */
    private function gpgdata($key, $text)
    {
        $prefix = $this->utility->srandom($this->width, 0);
        $prefix .= substr($prefix, -2);
        $mdc="\xD3\x14".hash('sha1', $prefix.$this->gpgliteral($text)."\xD3\x14", true);
        $enc = $this->gpgencrypt($key, $prefix.$this->gpgliteral($text).$mdc);
        return chr(0x12 | 0xC0) . chr(255) . $this->writeNumber(1+strlen($enc), 4) . chr(1) . $enc;
    }

    /**
     * GPG Encypts a message to the provided public key
     *
     * @param GPG_Public_Key $pk
     * @param string $plaintext
     * @param string $versionHeader
     * @return string encrypted text
     */
    public function encrypt($pk, $plaintext, $versionHeader = null)
    {
        // normalize the public key
        $key_id = $this->publicKey->getKeyId();
        $key_type = $this->publicKey->getKeyType();
        $public_key = $this->publicKey->getPublicKey();

        $session_key = $this->utility->srandom($this->width, 0);
        $key_id = $this->utility->hex2bin($key_id);
        $cp = $this->gpgsession($key_id, $key_type, $session_key, $public_key) .
        $this->gpgdata($session_key, $plaintext);

        $code = base64_encode($cp);
        $code = wordwrap($code, 64, "\n", true);

        if ($versionHeader===null) {
            $versionHeader="Version: VerySimple PHP-GPG v" . $this->version . "\n\n";
        } elseif (strlen($versionHeader)>0) {
            $versionHeader="Version: " . $versionHeader . "\n\n";
        }

        return
            "-----BEGIN PGP MESSAGE-----\n" .
            $versionHeader .
            $code . "\n=" . base64_encode($this->utility->crc24($cp)) .
            "\n-----END PGP MESSAGE-----\n";
    }
}
