CREATE TABLE `flex_test` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '고유번호',
  `signdate` datetime NOT NULL COMMENT '등록일',
  `title` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '제목',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;