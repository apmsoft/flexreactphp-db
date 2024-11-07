# 일반 테스트용 

CREATE TABLE `flex_test` (
  `_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '고유번호',
  `signdate` datetime NOT NULL COMMENT '등록일',
  `title` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '제목',
  `view_count` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


# Join 테스트용 
CREATE TABLE customers (
    `customer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50),
    `city` VARCHAR(50),
    PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO customers (name, city) VALUES
('Alice', 'New York'),
('Bob', 'New York'),
('Charlie', 'New Orleans'),
('Dave', 'Newark'),
('Eve', 'New York'),
('Frank', 'New Haven');

CREATE TABLE orders (
    `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED,
    `product` VARCHAR(50),
    `amount` DECIMAL(10, 2),
    PRIMARY KEY (`order_id`),
    FOREIGN KEY (`customer_id`) REFERENCES customers(`customer_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO orders (customer_id, product, amount) VALUES
(1, 'Laptop', 1200.00),
(1, 'Phone', 800.00),
(2, 'Tablet', 500.00),
(3, 'Monitor', 300.00);

# -- mysql
#SET @current_mode = @@session.block_encryption_mode;
#SET @@session.block_encryption_mode = 
#    CASE 
#       WHEN @current_mode != 'aes-256-cbc' THEN 'aes-256-cbc'
#        ELSE @current_mode
#    END;
# 암호화 테스트용
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    passwd VARCHAR(255) NOT NULL
);