CREATE TABLE flex_test (
    id SERIAL PRIMARY KEY,
    signdate TIMESTAMP NOT NULL,
    title VARCHAR(45) NOT NULL
);

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

