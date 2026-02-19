-- 添加USDT结算链路支持
-- 更新日期：2026-02-19

-- 为用户表添加USDT链路字段
ALTER TABLE `pre_user`
ADD COLUMN `usdt_chain` varchar(50) DEFAULT NULL COMMENT 'USDT结算链路类型';

-- 为结算表添加USDT链路字段
ALTER TABLE `pre_settle`
ADD COLUMN `usdt_chain` varchar(50) DEFAULT NULL COMMENT 'USDT结算链路类型';

-- 添加索引以提高查询性能
ALTER TABLE `pre_user`
ADD INDEX `usdt_chain` (`usdt_chain`);

ALTER TABLE `pre_settle`
ADD INDEX `usdt_chain` (`usdt_chain`);

-- 添加USDT转账配置
INSERT INTO `pre_config` (`k`, `v`) VALUES ('transfer_usdt', '0') ON DUPLICATE KEY UPDATE `v` = '0';