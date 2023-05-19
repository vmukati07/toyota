<?php
/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\GPG;

use Magento\Framework\Exception\LocalizedException;

define("PK_TYPE_ELGAMAL", 1);
define("PK_TYPE_RSA", 0);
define("PK_TYPE_UNKNOWN", -1);

/**
 * Public key class for GPG Encryption
 */
class PublicKey
{
    /**
     * @var mixed
     */
    public $version;

    /**
     * @var mixed
     */
    public $fp;

    /**
     * @var mixed
     */
    public $key_id;

    /**
     * @var mixed
     */
    public $user;

    /**
     * @var mixed
     */
    public $public_key;

    /**
     * @var mixed
     */
    public $type;

    /**
     * Isvalid function
     *
     * @return void
     */
    public function isValid()
    {
        return $this->version != -1 && $this->getKeyType() != PK_TYPE_UNKNOWN;
    }
    /**
     * Getkeytype function
     *
     * @return void
     */
    public function getKeyType()
    {
        if (!strcmp($this->type, "ELGAMAL")) {
            return PK_TYPE_ELGAMAL;
        }
        if (!strcmp($this->type, "RSA")) {
            return PK_TYPE_RSA;
        }
        return PK_TYPE_UNKNOWN;
    }
    /**
     * Fingerprint function
     *
     * @return void
     */
    public function getFingerprint()
    {
        return strtoupper(trim(chunk_split($this->fp, 4, ' ')));
    }
    /**
     * Keyid function
     *
     * @return void
     */
    public function getKeyId()
    {
        return (strlen($this->key_id) == 16) ? strtoupper($this->key_id) : '0000000000000000';
    }
    /**
     * Getpublic key function
     *
     * @return void
     */
    public function getPublicKey()
    {
        return str_replace("\n", "", $this->public_key);
    }

    /**
     * Get public key function
     *
     * @param [type] $asc
     * @return void
     */
    public function generatePublicKey($asc)
    {
        $found = 0;
        
        // normalize line breaks
        $asc = str_replace("\r\n", "\n", $asc);
        
        if (strpos($asc, "-----BEGIN PGP PUBLIC KEY BLOCK-----\n") === false) {
            throw new LocalizedException(__("Missing header block in Public Key"));
        }

        if (strpos($asc, "\n\n") === false) {
            throw new LocalizedException(__("Missing body delimiter in Public Key"));
        }
        
        if (strpos($asc, "\n-----END PGP PUBLIC KEY BLOCK-----") === false) {
            throw new LocalizedException(__("Missing footer block in Public Key"));
        }
        
        // get rid of everything except the base64 encoded key
        $headerbody = explode("\n\n", str_replace("\n-----END PGP PUBLIC KEY BLOCK-----", "", $asc), 2);
        $asc = trim($headerbody[1]);
        $len = 0;
        $s =  base64_decode($asc);
        $sa = str_split($s);

        $s1 = strlen($s);
        
        for ($i = 0; $i < $s1;) {
            $tag = ord($sa[$i++]);
            // echo 'TAG=' . $tag . '/';
            if (($tag & 128) == 0) {
                break;
            }
            
            if ($tag & 64) {
                $tag &= 63;
                $len = ord($sa[$i++]);
                if ($len > 191 && $len < 224) {
                    $len = (($len - 192) << 8) + ord($sa[$i++]);
                } elseif ($len == 255) {
                    $len = (ord($sa[$i++]) << 24) + (ord($sa[$i++]) << 16) + (ord($sa[$i++]) << 8) + ord($sa[$i++]);
                } elseif ($len > 223 && $len < 255) {
                    $len = (1 << ($len & 0x1f));
                }
            } else {
                $len = $tag & 3;
                $tag = ($tag >> 2) & 15;
                if ($len == 0) {
                    $len = ord($sa[$i++]);
                } elseif ($len == 1) {
                    $len = (ord($sa[$i++]) << 8) + ord($sa[$i++]);
                } elseif ($len == 2) {
                    $oc1=isset($sa[$i++])?$sa[$i++]:null;
                    $oc2=isset($sa[$i++])?$sa[$i++]:null;
                    $oc3=isset($sa[$i++])?$sa[$i++]:null;
                    $oc4=isset($sa[$i++])?$sa[$i++]:null;

                    if ($oc1!==null) {
                        $len = ord($oc1)* pow(2, 24);
                    } else {
                        $len = (0* pow(2, 24));
                    }

                    if ($oc2!==null) {
                        $len += ord($oc2)* pow(2, 16);
                    } else {
                        $len += (0* pow(2, 16));
                    }

                    if ($oc3!==null) {
                        $len += ord($oc3)* pow(2, 8);
                    } else {
                        $len += (0* pow(2, 8));
                    }

                    if ($oc3!==null) {
                        $len += ord($oc4);
                    } else {
                        $len += (0);
                    }
                    // $len = (ord($sa[$i++])* pow(2,24)) + (ord($sa[$i++]) * pow(2,16)) + (0 * pow(2,8)) + 0;
                } else {
                    $len = strlen($s) - 1;
                }
            }
            
            // echo $tag . ' ';
            
            if ($tag == 6 || $tag == 14) {
                $k = $i;
                $version = ord($sa[$i++]);
                $found = 1;
                $this->version = $version;
                
                $time = (ord($sa[$i++]) << 24) + (ord($sa[$i++]) << 16) + (ord($sa[$i++]) << 8) + ord($sa[$i++]);
                
                if ($version == 2 || $version == 3) {
                    $valid = ord($sa[$i++]) << 8 + ord($sa[$i++]);
                }
                
                $algo = ord($sa[$i++]);
                
                if ($algo == 1 || $algo == 2) {
                    $m = $i;
                    $lm = floor((ord($sa[$i]) * 256 + ord($sa[$i + 1]) + 7) / 8);
                    $lm = (int)$lm;
                    $i += $lm + 2;
                    
                    $mod = substr($s, $m, $lm + 2);
                    $le = floor((ord($sa[$i]) * 256 + ord($sa[$i+1]) + 7) / 8);
                    $le = (int)$le;
                    $i += $le + 2;
                    
                    $this->public_key = base64_encode(substr($s, $m, $lm + $le + 4));
                    $this->type = "RSA";
                    
                    if ($version == 3) {
                        $this->fp = '';
                        $this->key_id = bin2hex(substr($mod, strlen($mod) - 8, 8));
                    } elseif ($version == 4) {
                        $headerPos = strpos($s, chr(0x04));
                        $delim = chr(0x01) . chr(0x00);
                        $delimPos = strpos($s, $delim) + (3-$headerPos);
                        // echo "POSITION: $delimPos\n";
                        $pkt = chr(0x99) . chr($len >> 8) . chr($len & 255) . substr($s, $k, $len); // use this for now
                        
                        $fp = sha1($pkt);
                        $this->fp = $fp;
                        $this->key_id = substr($fp, strlen($fp) - 16, 16);
                        
                        // uncomment to debug the start point for the signing string
                         /*for ($ii = 5; $ii > -1; $ii--) {
                             $pkt = chr(0x99) . chr($ii >> 8) . chr($ii & 255) . substr($s, $headerPos, $ii);
                            $fp = sha1($pkt);
                            echo "LENGTH=" . $headerPos . '->' . $ii . " CHR(" . ord(substr($s,$ii, 1)) . ") = " .
                                 substr($fp, strlen($fp) - 16, 16) . "\n";
                         }
                         echo "\n";*/
                        
                         // uncomment to debug the end point for the signing string
                         /*for ($ii = strlen($s); $ii > 1; $ii--) {
                             $pkt = chr(0x99) . chr($ii >> 8) . chr($ii & 255) . substr($s, $headerPos, $ii);
                             $fp = sha1($pkt);
                             echo "LENGTH=" . $headerPos . '->' . $ii . " CHR(" . ord(substr($s,$ii, 1)) . ") = "
                             . substr($fp, strlen($fp) - 16, 16) . "\n";
                         }*/
                    } else {
                        throw new LocalizedException(__('GPG Key Version ' . $version . ' is not supported'));
                    }
                    $found = 2;
                } elseif (($algo == 16 || $algo == 20) && $version == 4) {
                        $m = $i;
                        
                        $lp = floor((ord($sa[$i]) * 256 + ord($sa[$i +1]) + 7) / 8);
                        $lp = (int)$lp;
                        $i += $lp + 2;
                        
                        $lg = floor((ord($sa[$i]) * 256 + ord($sa[$i + 1]) + 7) / 8);
                        $lg = (int)$lg;
                        $i += $lg + 2;
                        
                        $ly = floor((ord($sa[$i]) * 256 + ord($sa[$i + 1]) + 7)/8);
                        $ly = (int)$ly;
                        $i += $ly + 2;
                        
                        $this->public_key = base64_encode(substr($s, $m, $lp + $lg + $ly + 6));
                        
                        // TODO: should this be adjusted as it was for RSA (above)..?
                        
                        $pkt = chr(0x99) . chr($len >> 8) . chr($len & 255) . substr($s, $k, $len);
                        $fp = sha1($pkt);
                        $this->fp = $fp;
                        $this->key_id = substr($fp, strlen($fp) - 16, 16);
                        $this->type = "ELGAMAL";
                        $found = 3;
                } else {
                    $i = $k + $len;
                }
            } elseif ($tag == 13) {
                    $this->user = substr($s, $i, $len);
                    $i += $len;
            } else {
                $i += $len;
            }
        }
        
        if ($found < 2) {
            throw new LocalizedException(__("Unable to parse Public Key"));
        }
    }
}
