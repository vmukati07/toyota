<?php
/**
 * @package     Infosys/XtentoOrderExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\GPG;

class GpgGlobals
{

    /**
     * @var integer
     */
    protected $bs = 0;

    /**
     * @var integer
     */
    protected $bx2 = 0;

    /**
     * @var integer
     */
    protected $bm = 0;

    /**
     * @var integer
     */
    protected $bx = 0;

    /**
     * @var integer
     */
    protected $bd = 0;

    /**
     * @var integer
     */
    protected $bdm = 0;

    /**
     * Initialize dependencies
     */
    public function __construct()
    {
        $this->bs  = 28;
        $this->bx2 = 1 << $this->bs;
        $this->bm  = $this->bx2 - 1;
        $this->bx  = $this->bx2 >> 1;
        $this->bd  = $this->bs >> 1;
        $this->bdm = (1 << $this->bd) - 1;
    }

    /**
     * Method mpi2b
     *
     * @param mixed $s
     */
    public function mpi2b($s)
    {

        $bn = 1;
        $r  = [0];
        $rn = 0;
        $sb = 256;
        $c  = 0;
        $sn = strlen($s);
        if ($sn < 2) {
            return 0;
        }

        $len  = ($sn - 2) * 8;
        $bits = ord($s[0]) * 256 + ord($s[1]);
        if ($bits > $len || $bits < $len - 8) {
            return 0;
        }

        for ($n = 0; $n < $len; $n++) {
            if (($sb <<= 1) > 255) {
                $sb = 1;
                $c  = ord($s[--$sn]);
            }
            if ($bn > $this->bm) {
                $bn       = 1;
                $r[++$rn] = 0;
            }
            if ($c & $sb) {
                $r[$rn] |= $bn;
            }
            $bn <<= 1;
        }

        return $r;
    }

    /**
     * Method b2mpi
     *
     * @param mixed $b
     */
    public function b2mpi($b)
    {

        $bn   = 1;
        $bc   = 0;
        $r    = [0];
        $rb   = 1;
        $rn   = 0;
        $bits = count($b) * $this->bs;
        $n    = 0;
        $rr   = "";

        for ($n = 0; $n < $bits; $n++) {
            if ($b[$bc] & $bn) {
                $r[$rn] |= $rb;
            }
            if (($rb <<= 1) > 255) {
                $rb       = 1;
                $r[++$rn] = 0;
            }
            if (($bn <<= 1) > $this->bm) {
                $bn = 1;
                $bc++;
            }
        }

        while ($rn && $r[$rn] == 0) {
            $rn--;
        }

        $bn = 256;
        for ($bits = 8; $bits > 0; $bits--) {
            if ($r[$rn] & ($bn >>= 1)) {
                break;
            }
        }
        $bits += $rn * 8;

        $bits1 = $bits / 256;
        $bits1 = (int)$bits1;
        $bits2 = $bits % 256;
        $bits2 = (int)$bits2;
        $rr .= chr($bits1) . chr($bits2);
        if ($bits) {
            for ($n = $rn; $n >= 0; $n--) {
                $rr .= chr($r[$n]);
            }
        }

        return $rr;
    }

    /**
     * Method bmodexp
     *
     * @param mixed $xx
     * @param mixed $y
     * @param mixed $m
     */
    public function bmodexp($xx, $y, $m)
    {

        $r  = [1];
        $an = 0;
        $a  = 0;
        $x  = array_merge($xx);
        $n  = count($m) * 2;
        $mu = array_fill(0, $n + 1, 0);

        $mu[$n--] = 1;
        for (; $n >= 0; $n--) {
            $mu[$n] = 0;
        }
//        $dd = $this->bdiv($mu, $m);
//        $mu = $dd->_bdivQ;
        $this->bdiv($mu, $m);
        $mu = $this->_bdivQ;

        $y1= count($y);

        for ($n = 0; $n < $y1; $n++) {
            for ($a = 1, $an = 0; $an < $this->bs; $an++, $a <<= 1) {
                if ($y[$n] & $a) {
                    $r = $this->bmod2($this->bmul($r, $x), $m, $mu);
                }
                $x = $this->bmod2($this->bmul($x, $x), $m, $mu);
            }
        }

        return $r;
    }

    /**
     * Method simplemod
     *
     * @param mixed $i
     * @param mixed $m
     */
    public function simplemod($i, $m) // returns the mod where m < 2^bd
    {
        $c = 0;
        $v = 0;
        for ($n = count($i) - 1; $n >= 0; $n--) {
            $v = $i[$n];
            $c = (($v >> $this->bd) + ($c << $this->bd)) % $m;
            $c = (($v & $this->bdm) + ($c << $this->bd)) % $m;
        }

        return $c;
    }

    /**
     * Method bmod
     *
     * @param mixed $p
     * @param mixed $m
     */
    public function bmod($p, $m) // binary modulo
    {

        if (count($m) == 1) {
            if (count($p) == 1) {
                return [$p[0] % $m[0]];
            }
            if ($m[0] < $this->bdm) {
                return [$this->simplemod($p, $m[0])];
            }
        }

//        $r = $this->bdiv($p, $m);
//        return $r->_bdivMod;
        $this->bdiv($p, $m);
        return $this->_bdivMod;
    }

    /**
     * Method bmod2
     *
     * @param mixed $x
     * @param mixed $m
     * @param mixed $mu
     */
    public function bmod2($x, $m, $mu)
    {
        $xl = count($x) - (count($m) << 1);
        if ($xl > 0) {
            return $this->bmod2(
                array_concat(
                    array_slice($x, 0, $xl),
                    $this->bmod2(array_slice($x, $xl), $m, $mu)
                ),
                $m,
                $mu
            );
        }

        $ml1 = count($m) + 1;
        $ml2 = count($m) - 1;
        $rr  = 0;

        $q3 = array_slice($this->bmul(array_slice($x, $ml2), $mu), $ml1);
        $r1 = array_slice($x, 0, $ml1);
        $r2 = array_slice($this->bmul($q3, $m), 0, $ml1);

        $r = $this->bsub($r1, $r2);
        if (count($r) == 0) {
            $r1[$ml1] = 1;
            $r        = $this->bsub($r1, $r2);
        }
        for ($n = 0;; $n++) {
            $rr = $this->bsub($r, $m);
            if (count($rr) == 0) {
                break;
            }
            $r = $rr;
            if ($n >= 3) {
                return $this->bmod2($r, $m, $mu);
            }
        }

        return $r;
    }

    /**
     * Method toppart
     *
     * @param mixed $x
     * @param mixed $start
     * @param mixed $len
     */
    private function toppart($x, $start, $len)
    {

        $n = 0;
        while ($start >= 0 && $len-- > 0) {
            $n = $n * $this->bx2 + $x[$start--];
        }

        return $n;
    }

    /**
     * Method zeros
     *
     * @param mixed $n
     */
    private function zeros($n)
    {
        $r = array_fill(0, $n, 0);
        while ($n-- > 0) {
            $r[$n] = 0;
        }
        return $r;
    }

    /**
     * Method bsub
     *
     * @param mixed $a
     * @param mixed $b
     */
    private function bsub($a, $b)
    {

        $al = count($a);
        $bl = count($b);

        if ($bl > $al) {
            return [];
        }
        if ($bl == $al) {
            if ($b[$bl - 1] > $a[$bl - 1]) {
                return [];
            }
            if ($bl == 1) {
                return [$a[0] - $b[0]];
            }
        }

        $r = array_fill(0, $al, 0);
        $c = 0;

        for ($n = 0; $n < $bl; $n++) {
            $c += $a[$n] - $b[$n];
            $r[$n] = $c & $this->bm;
            $c >>= $this->bs;
        }
        for (; $n < $al; $n++) {
            $c += $a[$n];
            $r[$n] = $c & $this->bm;
            $c >>= $this->bs;
        }
        if ($c) {
            return [];
        }

        if ($r[$n - 1]) {
            return $r;
        }
        while ($n > 1 && $r[$n - 1] == 0) {
            $n--;
        }

        return array_slice($r, 0, $n);
    }

    /**
     * Method bmul
     *
     * @param mixed $a
     * @param mixed $b
     */
    public function bmul($a, $b)
    {

        $b    = array_merge($b, [0]);
        $al   = count($a);
        $bl   = count($b);
        $n    = 0;
        $nn   = 0;
        $aa   = 0;
        $c    = 0;
        $m    = 0;
        $g    = 0;
        $gg   = 0;
        $h    = 0;
        $hh   = 0;
        $ghh  = 0;
        $ghhb = 0;

        $r = $this->zeros($al + $bl + 1);

        for ($n = 0; $n < $al; $n++) {
            $aa = $a[$n];
            if ($aa) {
                $c  = 0;
                $hh = $aa >> $this->bd;
                $h  = $aa & $this->bdm;
                $m  = $n;
                for ($nn = 0; $nn < $bl; $nn++, $m++) {
                    $g    = $b[$nn];
                    $gg   = $g >> $this->bd;
                    $g    = $g & $this->bdm;
                    $ghh  = $g * $hh + $h * $gg;
                    $ghhb = $ghh >> $this->bd;
                    $ghh &= $this->bdm;
                    $c += $r[$m] + $h * $g + ($ghh << $this->bd);
                    $r[$m] = $c & $this->bm;
                    $c     = ($c >> $this->bs) + $gg * $hh + $ghhb;
                }
            }
        }
        $n = count($r);

        if ($r[$n - 1]) {
            return $r;
        }
        while ($n > 1 && $r[$n - 1] == 0) {
            $n--;
        }

        return array_slice($r, 0, $n);
    }

    /**
     * @var array
     */
    private $_bdivQ = [];

    /**
     * @var array
     */
    private $_bdivMod = [];

    /**
     * Method bdiv
     *
     * @param mixed $x
     * @param mixed $y
     */
    public function bdiv($x, $y)
    {
        $q = 0;

        $n   = count($x) - 1;
        $t   = count($y) - 1;
        $nmt = $n - $t;

        if ($n < $t || $n == $t && ($x[$n] < $y[$n] || $n > 0 && $x[$n] == $y[$n] && $x[$n - 1] < $y[$n - 1])) {
            $this->_bdivQ   = [0];
            $this->_bdivMod = [$x];
            return;
        }

        if ($n == $t && $this->toppart($x, $t, 2) / $this->toppart($y, $t, 2) < 4) {
            $qq = 0;
            $xx = 0;
            for (;;) {
                $xx = $this->bsub($x, $y);
                if (count($xx) == 0) {
                    break;
                }
                $x = $xx;
                $qq++;
            }
            $this->_bdivQ   = [$qq];
            $this->_bdivMod = $x;
            return;
        }

        $shift2 = floor(log($y[$t]) / M_LN2) + 1;
        $shift  = $this->bs - $shift2;
        if ($shift) {
            $x = array_merge($x);
            $y = array_merge($y);
            for ($i = $t; $i > 0; $i--) {
                $y[$i] = (($y[$i] << $shift) & $this->bm) | ($y[$i - 1] >> $shift2);
            }
            $y[0] = ($y[0] << $shift) & $this->bm;
            if ($x[$n] & (($this->bm << $shift2) & $this->bm)) {
                $x[++$n] = 0;
                $nmt++;
            }
            for ($i = $n; $i > 0; $i--) {
                $x[$i] = (($x[$i] << $shift) & $this->bm) | ($x[$i - 1] >> $shift2);
            }
            $x[0] = ($x[0] << $shift) & $this->bm;
        }

        $i  = 0;
        $j  = 0;
        $x2 = 0;
        $q  = $this->zeros($nmt + 1);
        $y2 = array_merge($this->zeros($nmt), $y);
        for (;;) {
            $x2 = $this->bsub($x, $y2);
            if (count($x2) == 0) {
                break;
            }
            $q[$nmt]++;
            $x = $x2;
        }

        $yt  = $y[$t];
        $top = $this->toppart($y, $t, 2);
        for ($i = $n; $i > $t; $i--) {
            $m = $i - $t - 1;
            if ($i >= count($x)) {
                $q[$m] = 1;
            } elseif ($x[$i] == $yt) {
                $q[$m] = $this->bm;
            } else {
                $q[$m] = floor($this->toppart($x, $i, 2) / $yt);
            }

            $topx = $this->toppart($x, $i, 3);
            while ($q[$m] * $top > $topx) {
                $q[$m]--;
            }

            $y2 = array_slice($y2, 1);
            $x2 = $this->bsub($x, $this->bmul([$q[$m]], $y2));
            if (count($x2) == 0) {
                $q[$m]--;
                $x2 = $this->bsub($x, $this->bmul([$q[m]], $y2));
            }
            $x = $x2;
        }

        if ($shift) {
            $x1 = count($x);
            for ($i = 0; $i < $x1 - 1; $i++) {
                $x[$i] = ($x[$i] >> $shift) | (($x[$i + 1] << $shift2) & $this->bm);
            }
            $x[count($x) - 1] >>= $shift;
        }
        $n = count($q);
        while ($n > 1 && $q[$n - 1] == 0) {
            $n--;
        }
        $this->_bdivQ = array_slice($q, 0, $n);
        $n            = count($x);
        while ($n > 1 && $x[$n - 1] == 0) {
            $n--;
        }
        $this->_bdivMod = array_slice($x, 0, $n);
    }
}
