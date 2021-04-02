<?php

/**
 * 生成商户订单号
 * @param $prefix string 前缀
 * @param $invoice_id string 系统内订单号
 * @return string 商户订单号
 */
function generate_trade_no($prefix, $invoice_id) {
    $rest_len = 32 - strlen($prefix);
    return $prefix.sprintf('%0'.$rest_len.'d', $invoice_id);
}
