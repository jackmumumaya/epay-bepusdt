# epay-bepusdt

彩虹易支付，集成了bepusdt，新建商户没有usdt结算的功能。

## 简介

此款插件是基于 [Epay](https://github.com/v03413/Epay) 开发，其它版本易支付不保证100%可用！

## 前置要求

- 彩虹易支付系统（基于PHP）
- BEpusdt支付服务
- MySQL数据库
- 支持cURL的PHP环境

## 安装步骤

1. 下载插件并解压
2. 将文件夹命名为bepusdt，放到彩虹易支付的plugins目录下
3. 刷新插件列表并启用
4. 配置支付方式和支付通道
5. 配置密钥参数

## 使用教程

1. 点击下载插件之后解压
2. 把文件夹命名为bepusdt，然后放到彩虹易支付的网站根目录plugins目录下
3. 彩虹易支付后台，支付接口 -> 支付插件，点击刷新插件列表；之后在插件名称列表里面如果发现bepusdt插件，说明安装成功
4. 彩虹易支付后台，支付接口 -> 支付方式，点击新增；调用值就是对接交易类型，请按需挑选；其它参数按需填写，保存启用
5. 彩虹易支付后台，支付接口 -> 支付通道，点击新增；支付方式选择刚才新增的，支付插件选择BEpusdt，其它参数按需填写，确认保存
6. 彩虹易支付后台，支付接口 -> 支付通道，找到刚才新增的通道点击配置密钥，按提示填写BEpusdt的相关参数，保存并启用，随后便可以开始测试

## 支持的交易类型

- tron.trx - TRX代币
- bsc.bnb - BSC链BNB
- ethereum.eth - 以太坊
- usdt.trc20 - TRC20 USDT
- usdc.trc20 - TRC20 USDC
- usdt.polygon - Polygon USDT
- usdc.polygon - Polygon USDC
- usdt.arbitrum - Arbitrum USDT
- usdc.arbitrum - Arbitrum USDC
- usdt.erc20 - ERC20 USDT
- usdc.erc20 - ERC20 USDC
- usdt.bep20 - BEP20 USDT
- usdc.bep20 - BEP20 USDC
- usdt.xlayer - XLayer USDT
- usdc.xlayer - XLayer USDC
- usdc.base - Base USDC
- usdt.solana - Solana USDT
- usdc.solana - Solana USDC
- usdt.aptos - Aptos USDT
- usdc.aptos - Aptos USDC
- usdt.plasma - Plasma USDT

## 签名算法

签名算法采用MD5加密，流程如下：

1. 移除签名参数：删除参数数组中的 signature 字段
2. 参数排序：使用 ksort() 对参数进行升序排序
3. 过滤空值：跳过值为空的参数
4. 拼接字符串：使用 key=value& 格式拼接所有参数
5. 移除末尾符号：删除字符串末尾的 &
6. 追加密钥：在字符串末尾追加认证Token
7. MD5加密：对最终字符串进行MD5加密

## 作者

- V03413

## 相关链接

- [BEpusdt](https://github.com/v03413/BEpusdt)
- [Epay](https://github.com/v03413/Epay)
