<?php

// 引入 WHMCS 文件
require_once __DIR__.'/../../../init.php';
require_once __DIR__.'/../../../includes/gatewayfunctions.php';
require_once __DIR__.'/../../../includes/invoicefunctions.php';
require_once __DIR__.'/../payjs/payjs.class.php';

// 获取 Gateway 参数
$gateway_module = 'payjs';
$gateway_params = getGatewayVariables($gateway_module);
if (!$gateway_params['type']) {
    die('Module Not Activated');
}

// 获取异步通知数据
$notify_data = [
    'return_code'    => intval($_POST['return_code']), // 1：支付成功
    'total_fee'      => intval($_POST['total_fee']),   // 金额。单位：分
    'out_trade_no'   => $_POST['out_trade_no'],        // 用户端自主生成的订单号
    'payjs_order_id' => $_POST['payjs_order_id'],      // PAYJS 订单号
    'transaction_id' => $_POST['transaction_id'],      // 微信用户手机显示订单号
    'time_end'       => $_POST['time_end'],            // 支付成功时间
    'openid'         => $_POST['openid'],              // 用户OPENID标示，本参数没有实际意义，旨在方便用户端区分不同用户
    'mchid'          => $_POST['mchid'],               // 商户号
    'sign'           => $_POST['sign'],                // 数据签名
    'attach'         => $_POST['attach'],              // 用户自定义数据，这里应该是 invoice_id
];
$invoice_id = intval($notify_data['attach']);
$transaction_id = $notify_data['payjs_order_id'];
$transaction_status = $notify_data['return_code'] == 1 ? 'Success' : 'Failure';

// 验签
$key = $gateway_params['key'];
$payjs = new PayJS($notify_data['mchid'], $key);
if (!$payjs->checkSign($_POST)) {
    $transaction_status = 'Hash Verification Failure';
}

// 检查 invoide_id
$invoice_id = checkCbInvoiceID($invoice_id, $gateway_params['name']);

// 检查 transaction_id
checkCbTransID($transaction_id);

// 记录交易记录
logTransaction($gateway_params['name'], $notify_data, $transaction_status);

// 记录付款记录
if ($transaction_status == 'Success') {
    addInvoicePayment(
        $invoice_id,
        $transaction_id,
        0,
        0,
        $gateway_module
    );
}
