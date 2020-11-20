/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : toko

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2020-04-20 23:21:47
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `produk`
-- ----------------------------
DROP TABLE IF EXISTS `produk`;
CREATE TABLE `produk` (
  `IdProduk` int(11) NOT NULL AUTO_INCREMENT,
  `KodeProduk` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NamaProduk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `HargaJual` int(11) DEFAULT NULL,
  `Stok` int(11) DEFAULT NULL,
  PRIMARY KEY (`IdProduk`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of produk
-- ----------------------------
INSERT INTO `produk` VALUES ('2', '8999999273378', 'CONERLLO CHOCOLUV 135', '25000', '100');
INSERT INTO `produk` VALUES ('3', '8999999275556', 'WALLS NINJA JELLY', '25000', '100');
INSERT INTO `produk` VALUES ('4', '8999999275563', 'NINJA EAGLE SURP', '25000', '100');
INSERT INTO `produk` VALUES ('5', '8999999275570', 'RAINBOW PEAK', '25000', '100');
INSERT INTO `produk` VALUES ('6', '8999999275587', 'PADDLE POP CHOKO KICK', '25000', '100');
INSERT INTO `produk` VALUES ('7', '8999999278458', 'CONELO DISC VAN', '25000', '100');

-- ----------------------------
-- Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `IdUser` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NamaLengkap` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NoHp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ApiKey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`IdUser`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', 'user', 'User', 'user@user.com', '0822222222222', 'user1234', 'TVXYQc8WVp1jJHLpVRLXvs4tuoXHVSg0');
