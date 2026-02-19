<?php

class usdt_transfer_plugin
{
    public static $info = [
        'name'     => 'usdt_transfer',
        'showname' => 'USDT/USDC 转账',
        'author'   => 'Epay',
        'link'     => '',
        'types'    => ['transfer'],
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
        ],
        'select'   => null,
        'note'     => '支持USDT/USDC多链路转账',
    ];

    public static function submit(): array
    {
        global $channel, $bizParam;

        $usdt_chain = $bizParam['usdt_chain'] ?? '';
        $out_biz_no = $bizParam['out_biz_no'];
        $payee_account = $bizParam['payee_account'];
        $payee_real_name = $bizParam['payee_real_name'];
        $money = $bizParam['money'];
        $desc = $bizParam['transfer_desc'] ?? '';

        if(empty($usdt_chain)){
            return ['code'=>-1, 'msg'=>'请选择USDT结算链路'];
        }

        $parameter = [
            'trade_type'   => $usdt_chain,
            'order_id'     => $out_biz_no,
            'name'         => $desc ?: '转账',
            'amount'       => $money,
            'address'      => $payee_account,
            'notify_url'   => $GLOBALS['conf']['localurl'] . 'pay/transfer_notify/' . $out_biz_no . '/',
        ];

        $parameter['signature'] = self::_toSign($parameter, $channel['appkey']);

        $url = trim($channel['appurl']) . 'api/v1/order/create-transaction';
        $data = self::_post($url, $parameter);

        if (!is_array($data)) {
            return ['code'=>-1, 'msg'=>'请求失败，请检查服务器是否能正常请求 BEpusdt 网关！'];
        }

        if ($data['status_code'] != 200) {
            return ['code'=>-1, 'msg'=>'请求失败，错误信息：' . $data['message']];
        }

        return [
            'code' => 0,
            'status' => 0,
            'orderid' => $data['data']['trade_id'],
            'paydate' => date('Y-m-d H:i:s'),
            'biz_no' => $out_biz_no,
            'out_biz_no' => $out_biz_no,
            'msg' => '提交成功！请等待转账处理。'
        ];
    }

    public static function query(): array
    {
        global $channel, $bizParam;

        $out_biz_no = $bizParam['out_biz_no'];
        $orderid = $bizParam['orderid'];

        $parameter = [
            'order_id' => $out_biz_no,
        ];

        $parameter['signature'] = self::_toSign($parameter, $channel['appkey']);

        $url = trim($channel['appurl']) . 'api/v1/order/query-transaction';
        $data = self::_post($url, $parameter);

        if (!is_array($data)) {
            return ['code'=>-1, 'msg'=>'查询失败'];
        }

        if ($data['status_code'] != 200) {
            return ['code'=>-1, 'msg'=>'查询失败：' . $data['message']];
        }

        $status = $data['data']['status'] ?? 0;
        $result_status = 0;

        if ($status == 2) {
            $result_status = 1;
        } elseif ($status == 3) {
            $result_status = 2;
        }

        return [
            'code' => 0,
            'status' => $result_status,
            'errmsg' => $data['message'] ?? '',
        ];
    }

    private static function _toSign(array $parameter, string $token): string
    {
        ksort($parameter);

        $sign = '';

        foreach ($parameter as $key => $val) {
            if ($val == '') continue;
            if ($key != 'signature') {
                if ($sign != '') {
                    $sign .= "&";
                }
                $sign .= "$key=$val";
            }
        }

        return md5($sign . $token);
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