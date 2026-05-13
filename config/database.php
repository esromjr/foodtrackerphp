<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '!@#123qweasdZXC');
define('DB_NAME', 'ethiopian_food_tracker');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>