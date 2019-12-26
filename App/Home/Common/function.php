<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 14:11
 */

function getKouStep ($true, $show, $max_v,$bi, $step=1){
    if (empty($bi)) return $step; // 如果比为0，则代表不扣量
    if (($true+$step) <= $max_v) {return $step;}
    $bi = $bi/1000;
    $idend = $true ? $true : 1 ;
    $true_bi = ($true - $show ) / $idend;
    if ($true_bi<$bi) { // 直接扣，没的说
        return  $step-floor($step * $bi);
    }
    return $step;
}

