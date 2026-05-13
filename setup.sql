CREATE DATABASE IF NOT EXISTS ethiopian_food_tracker;
USE ethiopian_food_tracker;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_name VARCHAR(100) NOT NULL,
    calories_per_100g INT NOT NULL,
    protein_g DECIMAL(5,2) DEFAULT 0,
    carbs_g DECIMAL(5,2) DEFAULT 0,
    fat_g DECIMAL(5,2) DEFAULT 0,
    food_type VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS food_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity_grams INT NOT NULL,
    meal_type VARCHAR(20),
    log_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

INSERT INTO foods (food_name, calories_per_100g, protein_g, carbs_g, fat_g, food_type) VALUES
('Injera', 190, 5.70, 40.10, 0.80, 'flatbread'),
('Doro Wat', 220, 18.50, 8.20, 13.40, 'stew'),
('Kitfo', 280, 20.00, 2.00, 21.00, 'meat'),
('Shiro Wat', 150, 8.50, 18.30, 5.20, 'stew'),
('Tibs', 310, 25.00, 4.50, 22.00, 'meat'),
('Misir Wat', 140, 9.00, 20.50, 3.50, 'stew'),
('Gomen', 95, 3.20, 14.00, 2.80, 'side_dish'),
('Ayibe', 180, 11.00, 3.00, 14.00, 'side_dish'),
('Genfo', 210, 6.00, 42.00, 2.50, 'porridge'),
('Chechebsa', 350, 8.00, 45.00, 16.00, 'flatbread'),
('Firfir', 200, 6.50, 35.00, 5.00, 'flatbread'),
('Ful Medames', 130, 7.00, 18.00, 3.00, 'stew'),
('Kategna', 320, 7.00, 42.00, 14.00, 'snack'),
('Timatim Salat', 60, 2.00, 10.00, 1.50, 'salad'),
('Bozena Shiro', 180, 10.00, 20.00, 7.00, 'stew'),
('Dulet', 260, 22.00, 3.00, 18.00, 'meat'),
('Alicha Wat', 120, 5.00, 16.00, 4.00, 'stew'),
('Teff Porridge', 198, 7.00, 38.00, 2.00, 'porridge'),
('Sambusa', 290, 9.00, 32.00, 14.00, 'snack'),
('Kolo', 380, 12.00, 60.00, 10.00, 'snack'),
('Tej (Honey Wine)', 85, 0.20, 8.00, 0.00, 'drink'),
('Buna (Ethiopian Coffee)', 5, 0.30, 0.00, 0.10, 'drink'),
('Enkulal Firfir', 230, 13.00, 20.00, 11.00, 'stew'),
('Yebeg Tibs', 290, 24.00, 3.00, 20.00, 'meat'),
('Gored Gored', 270, 21.00, 1.00, 20.00, 'meat');