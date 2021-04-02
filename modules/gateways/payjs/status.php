<?php

// 引入 WHMCS 初始化文件
require_once __DIR__.'/../../../init.php';
use WHMCS\Database\Capsule;

// 获取订单号
$invoice_id = intval($_GET['invoice_id']);
if ($invoice_id < 0) {
    die('bad request');
}

// 获取用户 ID
$client_area = new WHMCS_ClientArea();
$user_id = $client_area->getUserID();
if ($user_id == 0) {
    die('unauthorized');
}

// 获取订单信息
$invoice = Capsule::table('tblinvoices')->where(['id' => $invoice_id, 'userid' => $user_id])->first();
if (!$invoice) {
    die('invoice not found');
}

// 判断订单状态
if ($invoice->status == 'Paid' && $invoice->paymentmethod == 'payjs') {
    echo 'success';
} else {
    echo 'wait';
}
