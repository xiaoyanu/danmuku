-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-11-02 15:03:20
-- 服务器版本： 5.7.44-log
-- PHP 版本： 7.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `ttt`
--

-- --------------------------------------------------------

--
-- 表的结构 `danmu`
--

CREATE TABLE `danmu` (
  `id` int(11) NOT NULL COMMENT '弹幕唯一ID',
  `player` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视频MD5',
  `text` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '颜色',
  `time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '出现时间',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '位置',
  `timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '发送时间戳',
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '修改时间',
  `reserved_field1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '保留字段1',
  `reserved_field2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '保留字段2'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `danmu`
--
ALTER TABLE `danmu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player` (`player`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `danmu`
--
ALTER TABLE `danmu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '弹幕唯一ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
