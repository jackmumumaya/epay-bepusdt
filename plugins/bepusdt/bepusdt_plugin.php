<?php

class bepusdt_plugin
{
    public static $info = [
        'name'     => 'bepusdt',
        'showname' => 'BEpusdt 个人加密货币收款',
        'author'   => 'V03413',
        'link'     => 'https://github.com/v03413/BEpusdt',
        'types'    => [
            // 此列表可能存在变动，以此为准 https://github.com/v03413/BEpusdt/blob/main/docs/trade-type.md
            'tron.trx',
            'bsc.bnb',
            'ethereum.eth',
            'usdt.trc20',
            'usdc.trc20',
            'usdt.polygon',
            'usdc.polygon',
            'usdt.arbitrum',
            'usdc.arbitrum',
            'usdt.erc20',
            'usdc.erc20',
            'usdt.bep20',
            'usdc.bep20',
            'usdt.xlayer',
            'usdc.xlayer',
            'usdc.base',
            'usdt.solana',
            'usdc.solana',
            'usdt.aptos',
            'usdc.aptos',
            'usdt.plasma',
        ],
        'inputs'   => [
            'appurl'  => [
                'name' => '接口地址',
                'type' => 'input',
                'note' => '必须以http://或https://开头，以/结尾',
            ],
            'appkey'  => [
                'name' => '认证Token',
                'type' => 'input',
                'note' => '搭建BEpusdt时填写的 auth_token 参数',
            ],
            'address' => [
                'name' => '收款地址',
                'type' => 'input',
                'note' => '可以留空 留空则由BEpusdt自动分配，切勿乱填 注意空格',
            ],
            'timeout' => [
                'name' => '订单超时',
                'type' => 'input',
                'note' => '可以留空 填写整数(单位秒)、推荐 1200',
            ],
            'rate'    => [
                'name' => '订单汇率',
                'type' => 'input',
                'note' => '可以留空 例如：7.4 ~1.02 ~0.98（不明白切勿乱填）',
            ],
            'fiat'    => [
                'name'    => '交易法币',
                'type'    => 'select',
                'options' => [
                    'CNY' => '人民币 (CNY)',
                    'USD' => '美元 (USD)',
                    'EUR' => '欧元 (EUR)',
                    'GBP' => '英镑 (GBP)',
                    'JPY' => '日元 (JPY)',
                ],
            ],
        ],
        'select'   => null,
        'note'     => '', //支付密钥填写说明
    ];

    public static function submit(): array
    {
        global $siteurl, $channel, $order, $conf;

        // 只传递6个核心参数（与可用版本完全相同）
        $parameter = [
            'order_id'     => TRADE_NO,
            'amount'       => floatval($order['realmoney']),
            'notify_url'   => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
            'redirect_url' => $siteurl . 'pay/return/' . TRADE_NO . '/',
            'trade_type'   => $order['typename'],
            'fiat'         => 'CNY', // 固定为CNY（与可用版本相同）
            // 不传递：address, name, timeout, rate
        ];
        
        // 调试日志
        $log = "[BEPUSDT DEBUG] " . date('Y-m-d H:i:s') . "\n";
        $log .= "核心参数: " . json_encode($parameter, JSON_UNESCAPED_UNICODE) . "\n";
        $log .= "Token: " . $channel['appkey'] . "\n";
        
        // 计算签名（完全模仿可用版本）
        $signature = self::_toSign($parameter, $channel['appkey']);
        $parameter['signature'] = $signature;
        
        $log .= "签名: " . $signature . "\n";
        
        // API路径（与可用版本相同）
        $url = trim($channel['appurl']) . 'api/v1/order/create-transaction';
        $log .= "API路径: " . $url . "\n";
        $log .= "完整请求: " . json_encode($parameter, JSON_UNESCAPED_UNICODE) . "\n";
        
        // 发送请求
        $data = self::_post($url, $parameter);
        
        $log .= "响应: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        $log .= "========================================\n";
        
        // 写入日志
        file_put_contents(__DIR__ . '/bepusdt_debug.log', $log, FILE_APPEND);
        
        if (!is_array($data)) {
            return ['type' => 'error', 'msg' => '请求失败，请检查服务器是否能正常请求 BEpusdt 网关！'];
        }

        if ($data['status_code'] != 200) {
            return ['type' => 'error', 'msg' => '请求失败，错误信息：' . $data['message']];
        }

        return ['type' => 'jump', 'url' => $data['data']['payment_url']];
    }

    public static function notify()
    {
        global $channel, $order;

        ob_clean();
        header('Content-Type: plain/text; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);
        $sign = $data['signature'] ?? '';
        
        if ($sign != self::_toSign($data, $channel['appkey'])) {
            exit('fail - sign error');
        }

        $out_trade_no = $data['order_id'];
        $trade_no     = $data['trade_id'];
        $buyer        = mb_substr($data['buyer'], -28);
        if ($data['status'] === 2 && $out_trade_no == TRADE_NO) {
            processNotify($order, $trade_no, $buyer);
            exit('ok');
        }

        exit('fail - status error');
    }

    public static function return(): array
    {
        return ['type' => 'page', 'page' => 'return'];
    }

    /**
     * 签名算法（完全模仿可用版本的实现）
     */
    private static function _toSign(array $parameter, string $token): string
    {
        // 1. 移除signature参数
        if (isset($parameter['signature'])) {
            unset($parameter['signature']);
        }
        
        // 2. 对参数进行ksort排序
        ksort($parameter);
        
        // 3. 遍历所有参数，跳过空值
        $sign = '';
        foreach ($parameter as $k => $v) {
            if ($v == '') {
                continue;
            }
            // 4. 使用 $k . '=' . $v . '&' 格式拼接
            $sign .= $k . '=' . $v . '&';
        }
        
        // 5. 移除末尾的&
        $sign = trim($sign, '&');
        
        // 6. 追加token并进行md5加密
        $final_string = $sign . $token;
        $signature = md5($final_string);
        
        // 记录签名过程
        $log = "[BEPUSDT SIGN DEBUG] " . date('Y-m-d H:i:s') . "\n";
        $log .= "排序后参数: " . json_encode($parameter, JSON_UNESCAPED_UNICODE) . "\n";
        $log .= "签名字符串: " . $sign . "\n";
        $log .= "最终字符串: " . $final_string . "\n";
        $log .= "MD5结果: " . $signature . "\n";
        file_put_contents(__DIR__ . '/bepusdt_debug.log', $log, FILE_APPEND);
        
        return $signature;
    }

    private static function _post(string $url, array $json)
    {
        $header[] = 'Accept: */*';
        $header[] = 'Accept-Language: zh-CN,zh;q=0.8';
        $header[] = 'Connection: close';
        $header[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $resp = curl_exec($ch);
        curl_close($ch);

        return json_decode($resp, true);
    }
}
