# 일반 테스트용 
CREATE TABLE public.flex_test (
	_id serial4 NOT NULL,
	signdate timestamp NOT NULL,
	title varchar(45) NOT NULL,
	view_count int4 DEFAULT 0 NOT NULL,
	CONSTRAINT flex_test_pkey PRIMARY KEY (_id)
);

# Join 테스트용 
CREATE TABLE customers (
    customer_id SERIAL PRIMARY KEY,
    name VARCHAR(50),
    city VARCHAR(50)
);

INSERT INTO customers (name, city) VALUES
('Alice', 'New York'),
('Bob', 'New York'),
('Charlie', 'New Orleans'),
('Dave', 'Newark'),
('Eve', 'New York'),
('Frank', 'New Haven');

CREATE TABLE orders (
    order_id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(customer_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    product VARCHAR(50),
    amount DECIMAL(10, 2)
);

INSERT INTO orders (customer_id, product, amount) VALUES
(1, 'Laptop', 1200.00),
(1, 'Phone', 800.00),
(2, 'Tablet', 500.00),
(3, 'Monitor', 300.00);


#-- pgcrypto 확장 활성화
# CREATE EXTENSION IF NOT EXISTS pgcrypto;
# 암호화 테스트용
CREATE TABLE users (
    _id SERIAL PRIMARY KEY,
    username VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    passwd VARCHAR(255) NOT NULL
);

