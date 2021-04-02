<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__.'/payjs/payjs.class.php';
require_once __DIR__.'/payjs/payjs.func.php';

function payjs_MetaData() {
    return [
        'DisplayName' => 'PayJS for WHMCS',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

function payjs_config() {
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PayJS 微信扫码支付',
        ],
        'merch_id' => [
            'FriendlyName' => '商户号',
            'Type' => 'text',
            'Size' => '16',
            'Default' => '',
            'Description' => '这里输入商户号，一般是 10 位数字',
        ],
        'key' => [
            'FriendlyName' => '通信密钥',
            'Type' => 'password',
            'Size' => '32',
            'Default' => '',
            'Description' => '这里输入通信密钥，一般是 16 位随机字符串',
        ],
        'prefix' => [
            'FriendlyName' => '订单号前缀',
            'Type' => 'text',
            'Size' => '6',
            'Default' => 'A',
            'Description' => '这里输入订单号前缀，最大 6 位',
        ],
    ];
}

function payjs_link($params) {
    // 获取系统 URL
    $system_url = $params['systemurl'];

    // 判断是否在 viewinvoice 页面
    if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
        // 返回微信支付图片
        return '<img style="width: 150px;" src="'.$system_url.'/modules/gateways/payjs/wechat.png">';
    }

    // 获取 PayJS Gateway 配置
    $merch_id = $params['merch_id'];
    $key = $params['key'];
    $prefix = $params['prefix'];

    // 获取订单信息
    $invoice_id = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];

    // 生成回调地址
    $callback = $system_url . '/modules/gateways/callback/payjs.php';

    // 初始化 PayJS 对象
    $payjs = new PayJS($merch_id, $key);

    // 构造请求参数
    $request = [
        'total_fee'    => intval($amount * 100),
        'out_trade_no' => generate_trade_no($prefix, $invoice_id),
        'body'         => $description,
        'notify_url'   => $callback,
        'attach'       => $invoice_id,
    ];

    // 发送 Native 支付请求
    $resp = $payjs->native($request);

    // 处理响应
    if ($resp['return_code'] != 1) {
        // 请求失败
        return '';
    }

    // 构造 HTML
    $html = '<div class="payjs" style="text-align: center;">';
    $html .= '<div id="payjs-qrcode" style="border: 1px solid #AAA; border-radius: 4px; overflow: hidden; width: 202px; margin: 10px auto;">';
    $html .= '<img class="img-responsive pad" src="'.$resp['qrcode'].'" style="width: 250px; height: 200px;">';
    $html .= '</div>';
    $html .= '<a href="javascript:void(0);" id="payjs-tip" class="btn btn-success" style="width: auto;">微信扫一扫付款</a>';
    $html .= '</div>';

    // 构造状态检查脚本
    $script = '<script>';
    $script .= 'window.setInterval(function () {';
    $script .= 'var xhr = new XMLHttpRequest();';
    $script .= 'xhr.onreadystatechange = function () {';
    $script .= 'if (xhr.readyState == 4 && xhr.status == 200) {';
    $script .= 'if (xhr.responseText == "success") {';
    $script .= 'document.getElementById("payjs-qrcode").style.display = "none";';
    $script .= 'document.getElementById("payjs-tip").innerHTML = "支付成功";';
    $script .= 'window.setTimeout(function () {';
    $script .= 'window.location.href = "'.$system_url.'/viewinvoice.php?id='.$invoice_id.'";';
    $script .= '}, 2000);}}};';
    $script .= 'xhr.open("GET", "'.$system_url.'/modules/gateways/payjs/status.php?invoice_id='.$invoice_id.'", true);';
    $script .= 'xhr.send();';
    $script .= '}, 3000);';
    $script .= '</script>';

    return $html.$script;
}
