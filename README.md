# epay-bepusdt
彩虹易支付，集成了bepusdt，新建商户没有usdt结算的功能。
# 彩虹易支付 对接 BEpusdt - 开发文档

此款插件是基于 https://github.com/v03413/Epay 开发，其它版本易支付不保证100%可用！

使用之前默认你已经正常安装好了彩虹易支付和BEpusdt，这里不再赘述。

## 目录

- [快速开始](#快速开始)
- [配置截图](#配置截图)
- [使用教程](#使用教程)
- [技术架构](#技术架构)
- [签名算法详解](#签名算法详解)
- [开发过程与问题解决](#开发过程与问题解决)
- [数据库结构](#数据库结构)
- [API接口说明](#api接口说明)
- [部署指南](#部署指南)
- [常见问题排查](#常见问题排查)
- [版本历史](#版本历史)

## 快速开始

### 前置要求

- 彩虹易支付系统（基于PHP）
- BEpusdt支付服务
- MySQL数据库
- 支持cURL的PHP环境

### 安装步骤

1. 下载插件并解压
2. 将文件夹命名为`bepusdt`，放到彩虹易支付的`plugins`目录下
3. 刷新插件列表并启用
4. 配置支付方式和支付通道
5. 配置密钥参数

## 配置截图

![bepusdt](./doc/screenshot.png)

## 使用教程

1. 点击[下载插件](https://github.com/v03413/Epay-BEpusdt/archive/refs/heads/main.zip)之后解压，
   把文件夹命名为`bepusdt`，然后放到彩虹易支付的网站根目录`plugins`目录下。
2. 彩虹易支付后台，`支付接口 -> 支付插件`，点击`刷新插件列表`；之后在插件名称列表里面如果发现`bepusdt`插件，
   说明安装成功。
3. 彩虹易支付后台，`支付接口 -> 支付方式`，点击`新增`；调用值就是对接交易类型，请从[这里](https://github.com/v03413/BEpusdt/blob/main/docs/trade-type.md)按需挑选；其它参数按需填写，保存启用。  
4. 彩虹易支付后台，`支付接口 -> 支付通道`，点击`新增`；支付方式选择刚才新增的，支付插件选择`BEpusdt`，其它参数按需填写，确认保存。
5. 彩虹易支付后台，`支付接口 -> 支付通道`，找到刚才新增的通道点击`配置密钥`，按提示填写BEpusdt的相关参数，保存并启用，随后便可以开始测试。

## 技术架构

### 核心组件

1. **插件主文件**：`bepusdt_plugin.php`
   - 插件信息定义
   - 支付提交处理
   - 回调通知处理
   - 返回跳转处理

2. **签名算法**：`Signature` 类
   - MD5加密签名
   - 参数排序与拼接
   - 空值过滤

3. **HTTP请求**：`_post` 方法
   - JSON格式请求
   - cURL封装
   - SSL证书验证

### 支持的交易类型

- `tron.trx` - TRX代币
- `bsc.bnb` - BSC链BNB
- `ethereum.eth` - 以太坊
- `usdt.trc20` - TRC20 USDT
- `usdc.trc20` - TRC20 USDC
- `usdt.polygon` - Polygon USDT
- `usdc.polygon` - Polygon USDC
- `usdt.arbitrum` - Arbitrum USDT
- `usdc.arbitrum` - Arbitrum USDC
- `usdt.erc20` - ERC20 USDT
- `usdc.erc20` - ERC20 USDC
- `usdt.bep20` - BEP20 USDT
- `usdc.bep20` - BEP20 USDC
- `usdt.xlayer` - XLayer USDT
- `usdc.xlayer` - XLayer USDC
- `usdc.base` - Base USDC
- `usdt.solana` - Solana USDT
- `usdc.solana` - Solana USDC
- `usdt.aptos` - Aptos USDT
- `usdc.aptos` - Aptos USDC
- `usdt.plasma` - Plasma USDT

## 签名算法详解

### 算法流程

1. **移除签名参数**：删除参数数组中的 `signature` 字段
2. **参数排序**：使用 `ksort()` 对参数进行升序排序
3. **过滤空值**：跳过值为空的参数
4. **拼接字符串**：使用 `key=value&` 格式拼接所有参数
5. **移除末尾符号**：删除字符串末尾的 `&`
6. **追加密钥**：在字符串末尾追加认证Token
7. **MD5加密**：对最终字符串进行MD5加密

### 代码实现

```php
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
    
    return $signature;
}
```

### 签名示例

**输入参数**：
```json
{
    "order_id": "2026021919122626305",
    "amount": 1,
    "notify_url": "http://192.168.31.130/pay/notify/2026021919122626305/",
    "redirect_url": "http://192.168.31.130/pay/return/2026021919122626305/",
    "trade_type": "usdt.trc20",
    "fiat": "CNY"
}
```

**排序后参数**：
```
amount=1
fiat=CNY
notify_url=http://192.168.31.130/pay/notify/2026021919122626305/
order_id=2026021919122626305
redirect_url=http://192.168.31.130/pay/return/2026021919122626305/
trade_type=usdt.trc20
```

**签名字符串**：
```
amount=1&fiat=CNY&notify_url=http://192.168.31.130/pay/notify/2026021919122626305/&order_id=2026021919122626305&redirect_url=http://192.168.31.130/pay/return/2026021919122626305/&trade_type=usdt.trc20
```

**最终字符串（追加Token）**：
```
amount=1&fiat=CNY&notify_url=http://192.168.31.130/pay/notify/2026021919122626305/&order_id=2026021919122626305&redirect_url=http://192.168.31.130/pay/return/2026021919122626305/&trade_type=usdt.trc20{YOUR_TOKEN}
```

**MD5结果**：
```
3b85795b671dbde471f8aa58dc9a75c5
```

## 开发过程与问题解决

### 问题1：签名错误

**错误信息**：
```
请求失败，错误信息：签名错误
```

**问题原因**：
1. 参数数量不匹配：传递了10个参数，但服务端只期望6个核心参数
2. 参数格式不一致：某些参数的格式与服务端期望的不同
3. 签名计算基础不同：基于不同的参数集计算签名

**解决方案**：
1. 只传递6个核心参数：
   - `order_id` - 订单号
   - `amount` - 订单金额
   - `notify_url` - 异步通知地址
   - `redirect_url` - 同步跳转地址
   - `trade_type` - 交易类型
   - `fiat` - 法币类型（固定为 "CNY"）

2. 移除额外参数：
   - `address` - 收款地址
   - `name` - 订单名称
   - `timeout` - 订单超时
   - `rate` - 订单汇率

3. 完全模仿可用版本的签名算法实现

### 问题2：支付通道中没有USDT选项

**问题原因**：
- 插件未正确安装到 `plugins` 目录
- 插件列表未刷新
- 支付方式未正确配置

**解决方案**：
1. 确认插件文件夹命名为 `bepusdt` 并放在 `plugins` 目录下
2. 后台刷新插件列表
3. 新增支付方式，调用值填写交易类型（如 `usdt.trc20`）
4. 新增支付通道，选择对应的支付方式和BEpusdt插件

### 问题3：数据库字段缺失

**错误信息**：
```
添加商户失败！[1054]Unknown column 'usdt_chain' in 'field list'
```

**问题原因**：
- 数据库结构未同步更新
- 缺少 `usdt_chain` 字段

**解决方案**：
执行以下SQL语句更新数据库：

```sql
-- 为用户表添加USDT链路字段
ALTER TABLE `pay_user`
ADD COLUMN `usdt_chain` varchar(50) DEFAULT NULL COMMENT 'USDT结算链路类型';


-- 添加索引以提高查询性能
ALTER TABLE `pay_user`
ADD INDEX `usdt_chain` (`usdt_chain`);

ALTER TABLE `pay_settle`
ADD INDEX `usdt_chain` (`usdt_chain`);

-- 添加USDT转账配置
INSERT INTO `pay_config` (`k`, `v`) VALUES ('transfer_usdt', '0') ON DUPLICATE KEY UPDATE `v` = '0';
```


**解决方案**：
1. 在宝塔面板中查看或重置MySQL root密码
2. 使用面板的数据库管理工具导入SQL文件
3. 确认数据库用户名和密码配置正确

### 问题5：数据库不存在



**解决方案**：
1. 通过宝塔面板创建 `epay` 数据库
2. 或使用命令行创建：`CREATE DATABASE epay;`
3. 导入安装SQL文件

## 数据库结构

### 用户表 (pay_user)

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 用户ID |
| username | varchar | 用户名 |
| account | varchar | 账户 |
| settle_id | int | 结算ID |
| usdt_chain | varchar(50) | USDT结算链路类型 |
| ... | ... | ... |

### 结算表 (pay_settle)

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 结算ID |
| type | varchar | 支付类型 |
| usdt_chain | varchar(50) | USDT结算链路类型 |
| account | varchar | 结算账户 |
| username | varchar | 账户名称 |
| ... | ... | ... |

### 配置表 (pay_config)

| 字段名 | 类型 | 说明 |
|--------|------|------|
| k | varchar | 配置键 |
| v | varchar | 配置值 |

## API接口说明

### 创建订单接口

**接口地址**：
```
POST {appurl}api/v1/order/create-transaction
```

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | string | 是 | 订单号 |
| amount | float | 是 | 订单金额 |
| notify_url | string | 是 | 异步通知地址 |
| redirect_url | string | 是 | 同步跳转地址 |
| trade_type | string | 是 | 交易类型 |
| fiat | string | 是 | 法币类型（如 "CNY"） |
| signature | string | 是 | 签名 |

**响应示例**：

```json
{
    "status_code": 200,
    "message": "success",
    "data": {
        "order_id": "2026021919122626305",
        "amount": "1.00",
        "fiat": "CNY",
        "actual_amount": "0.13888889",
        "payment_url": "https://example.com/pay/xxx",
        "expire_time": "2026-02-19 19:32:26"
    }
}
```

### 异步回调接口

**回调地址**：
```
POST {notify_url}
```

**回调参数**：

| 参数名 | 类型 | 说明 |
|--------|------|------|
| order_id | string | 订单号 |
| trade_id | string | 交易ID |
| status | int | 订单状态（2为成功） |
| amount | string | 订单金额 |
| actual_amount | string | 实际支付金额 |
| buyer | string | 支付地址 |
| signature | string | 签名 |

**响应**：
```
ok
```

## 部署指南

### 宝塔面板部署

1. **环境准备**
   - 安装Nginx/Apache
   - 安装PHP 7.4+
   - 安装MySQL 5.7+
   - 安装Redis（可选）

2. **网站配置**
   - 创建网站
   - 配置SSL证书
   - 设置伪静态规则

3. **数据库配置**
   - 创建数据库
   - 导入SQL文件
   - 配置数据库连接

4. **插件安装**
   - 上传插件到 `plugins` 目录
   - 刷新插件列表
   - 配置支付通道

### Laragon部署

1. **环境准备**
   - 下载并安装Laragon
   - 启动Apache和MySQL

2. **项目配置**
   - 将项目放到 `www` 目录
   - 配置虚拟主机
   - 修改数据库配置

3. **插件安装**
   - 同宝塔面板部署步骤

## 常见问题排查

### 1. 插件未显示

**排查步骤**：
1. 确认插件文件夹命名为 `bepusdt`
2. 确认插件文件存在 `bepusdt_plugin.php`
3. 后台刷新插件列表
4. 检查PHP错误日志

### 2. 签名错误

**排查步骤**：
1. 检查Token配置是否正确
2. 查看调试日志 `bepusdt_debug.log`
3. 确认参数数量和格式
4. 验证签名算法实现

### 3. 订单创建失败

**排查步骤**：
1. 检查API地址是否正确
2. 验证网络连接
3. 查看BEpusdt服务日志
4. 确认订单参数格式

### 4. 回调未触发

**排查步骤**：
1. 检查回调地址是否可访问
2. 验证防火墙设置
3. 查看BEpusdt服务回调日志
4. 确认回调URL格式正确

### 5. 支付成功但订单未更新

**排查步骤**：
1. 检查回调处理逻辑
2. 验证签名验证代码
3. 查看彩虹支付日志
4. 确认订单状态更新逻辑

## 版本历史

### v1.0.0 (2026-02-19)
- 初始版本发布
- 支持多种加密货币交易类型
- 实现签名算法
- 支持异步回调
- 解决签名错误问题
- 优化参数传递逻辑

### 已知问题
- 暂无

### 待开发功能
- 支持更多交易类型
- 优化错误处理
- 添加更多日志记录
- 支持多语言

## 技术支持

- GitHub Issues: https://github.com/v03413/Epay-BEpusdt/issues
- BEpusdt文档: https://github.com/v03413/BEpusdt
- 彩虹易支付: https://github.com/v03413/Epay

## 许可证

本项目基于MIT许可证开源。

## 致谢

感谢以下开源项目：
- [BEpusdt](https://github.com/v03413/BEpusdt) - 个人加密货币收款系统
- [Epay](https://github.com/v03413/Epay) - 彩虹易支付系统
