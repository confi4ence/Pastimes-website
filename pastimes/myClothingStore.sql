
-- myClothingStore.sql
-- Full schema + sample data for Pasttime ClothingStore

CREATE DATABASE IF NOT EXISTS ClothingStore;
USE ClothingStore;

-- ---- Drop existing tables (children before parents) ----
DROP TABLE IF EXISTS tblMessage;
DROP TABLE IF EXISTS tblOrderLine;
DROP TABLE IF EXISTS tblAorder;
DROP TABLE IF EXISTS tblClothes;
DROP TABLE IF EXISTS tblUser;
DROP TABLE IF EXISTS tblAdmin;


-- tblAdmin
CREATE TABLE tblAdmin (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    username   VARCHAR(80)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Admin record — use setup.php to insert with a real hash, or generate one:
-- echo password_hash('admin123', PASSWORD_DEFAULT);
-- NOTE: Run this PHP snippet to get the hash for your own setup:
-- echo password_hash('admin123', PASSWORD_DEFAULT);
-- The hash below is a placeholder — replace with your generated hash.
INSERT INTO tblAdmin (full_name, email, username, password) VALUES
('Super Admin', 'admin@pasttime.co.za', 'admin', '$2y$10$exampleHashReplaceWithRealHash1234567890AbCdEfGhIj');

-- tblUser
CREATE TABLE tblUser (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    username   VARCHAR(80)   NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('buyer','seller') NOT NULL DEFAULT 'buyer',
    status     ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sample users (passwords hashed — run createTable.php to generate real hashes)
INSERT INTO tblUser (full_name, email, username, password, role, status) VALUES
('John Doe',    'john@example.com',   'johndoe',   '$2y$10$placeholder1', 'buyer',  'approved'),
('Jane Smith',  'jane@example.com',   'janesmith',  '$2y$10$placeholder2', 'buyer',  'approved'),
('Mike Brown',  'mike@example.com',   'mikebrown',  '$2y$10$placeholder3', 'seller', 'approved'),
('Sarah Lee',   'sarah@example.com',  'sarahlee',   '$2y$10$placeholder4', 'buyer',  'approved'),
('Tom Wilson',  'tom@example.com',    'tomwilson',  '$2y$10$placeholder5', 'seller', 'approved'),
('Amy Carter',  'amy@example.com',    'amycarter',  '$2y$10$placeholder6', 'buyer',  'pending'),
('Ben Nkosi',   'ben@example.com',    'bennkosi',   '$2y$10$placeholder7', 'seller', 'pending');


-- tblClothes
CREATE TABLE tblClothes (
    clothes_id  INT AUTO_INCREMENT PRIMARY KEY,
    item_name   VARCHAR(150)  NOT NULL,
    brand       VARCHAR(100),
    category    VARCHAR(80),
    size        VARCHAR(20),
    item_condition VARCHAR(50),
    price       DECIMAL(10,2) NOT NULL,
    quantity    INT NOT NULL DEFAULT 10,
    description TEXT,
    image_path  VARCHAR(255),
    seller_id   INT,
    status      ENUM('pending','available','sold') NOT NULL DEFAULT 'available',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES tblUser(user_id) ON DELETE SET NULL
);

INSERT INTO tblClothes (item_name, brand, category, size, item_condition, price, quantity, description, image_path, seller_id, status) VALUES
('Blue Denim Jacket',     'Levi\'s',   'Jackets',    'M',   'Good',       349.99, 8,  'Classic blue denim jacket, slightly worn.',       'images/blue-denim-jacket.jpg', 3, 'available'),
('White Sneakers',        'Nike',      'Shoes',      '8',   'Like New',   599.00, 5,  'Barely worn white Nike sneakers.',                'images/white-sneakers.jpg', 5, 'available'),
('Black Hoodie',          'H&M',       'Tops',       'L',   'Excellent',  199.99, 12, 'Cosy black pullover hoodie.',                     'images/black-hoodie.jpg', 3, 'available'),
('Floral Summer Dress',   'Zara',      'Dresses',    'S',   'Like New',   450.00, 6,  'Light floral dress, perfect for summer.',         'images/floral-summer-dress.jpg', 5, 'available'),
('Grey Skinny Jeans',     'Woolworths','Bottoms',    '32',  'Good',       280.00, 10, 'Comfortable grey skinny jeans.',                  'images/grey-skinny-jeans.jpg', 3, 'available'),
('Checked Blazer',        'Markham',   'Jackets',    'L',   'Excellent',  520.00, 4,  'Smart checked blazer for formal occasions.',      'images/checked-blazer.jpg', 5, 'available'),
('Red Polo Shirt',        'Lacoste',   'Tops',       'M',   'Good',       375.00, 9,  'Classic Lacoste polo, red.',                      'images/red-polo-shirt.jpg', 3, 'available'),
('Khaki Cargo Shorts',    'Mr Price',  'Bottoms',    '34',  'Good',       120.00, 15, 'Sturdy cargo shorts with side pockets.',          'images/khaki-cargo-shorts.jpg', 5, 'available'),
('Ankle Boots',           'Steve Madden','Shoes',    '7',   'Like New',   680.00, 3,  'Stylish ankle boots, barely worn.',               'images/ankle-boots.jpg', 3, 'available'),
('Striped T-Shirt',       'Cotton On', 'Tops',       'S',   'Excellent',  89.99,  20, 'Casual striped tee, great condition.',            'images/striped-tshirt.jpg', 5, 'available'),
('Winter Puffer Jacket',  'The Fix',   'Jackets',    'XL',  'Good',       799.00, 7,  'Warm puffer jacket for cold days.',               'images/puffer-jacket.jpg', 3, 'available'),
('Pleated Midi Skirt',    'Edgars',    'Bottoms',    'M',   'Like New',   310.00, 11, 'Elegant pleated skirt, cream colour.',            'images/midi-skirt.jpg', 5, 'available'),
('Vintage Leather Jacket','Wrangler',  'Jackets',    'M',   'Good',       450.00, 1,  'A sample seller submission awaiting admin approval — demonstrates the request-to-sell workflow.', '', 3, 'pending');


-- tblAorder  (one row per checkout / order placed)
CREATE TABLE tblAorder (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    order_ref   VARCHAR(30)   NOT NULL UNIQUE,
    session_id  VARCHAR(100),
    total_price DECIMAL(10,2) NOT NULL,
    order_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
    status      ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES tblUser(user_id) ON DELETE CASCADE
);

INSERT INTO tblAorder (user_id, order_ref, session_id, total_price, status) VALUES
(1, 'ORD-20260101-0001', 'sess_demo_001', 349.99, 'delivered'),
(2, 'ORD-20260102-0002', 'sess_demo_002', 450.00, 'confirmed'),
(4, 'ORD-20260103-0003', 'sess_demo_003', 599.00, 'shipped'),
(1, 'ORD-20260104-0004', 'sess_demo_004', 199.99, 'pending'),
(2, 'ORD-20260105-0005', 'sess_demo_005', 280.00, 'delivered');


-- tblOrderLine  (one row per item inside an order)
CREATE TABLE tblOrderLine (
    orderline_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    clothes_id   INT NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    unit_price   DECIMAL(10,2) NOT NULL,
    line_total   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)    REFERENCES tblAorder(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (clothes_id)  REFERENCES tblClothes(clothes_id)  ON DELETE CASCADE
);

INSERT INTO tblOrderLine (order_id, clothes_id, quantity, unit_price, line_total) VALUES
(1, 1, 1, 349.99, 349.99),
(2, 4, 1, 450.00, 450.00),
(3, 2, 1, 599.00, 599.00),
(4, 3, 1, 199.99, 199.99),
(5, 5, 1, 280.00, 280.00);


-- tblMessage  (admin communication with sellers and buyers)

CREATE TABLE tblMessage (
    message_id  INT AUTO_INCREMENT PRIMARY KEY,
    sender_type ENUM('admin','user') NOT NULL,
    user_id     INT NOT NULL,
    order_id    INT,
    subject     VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES tblUser(user_id)   ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES tblAorder(order_id) ON DELETE SET NULL
);

INSERT INTO tblMessage (sender_type, user_id, order_id, subject, message, is_read) VALUES
('admin', 1, 1, 'Order Delivered', 'Hi John, your Blue Denim Jacket has been delivered. Thank you for shopping with Pasttime!', 1),
('admin', 3, NULL, 'Item Condition Check', 'Hi Mike, please confirm the condition of the Checked Blazer before we list it.', 0),
('user',  3, NULL, 'Re: Item Condition Check', 'Hi, the blazer is in excellent condition with no marks.', 0);