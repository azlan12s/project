<?php
// $db_host = 'sql207.infinityfree.com';
// $db_user = 'if0_43031712';
// $db_pass = '1BQ6LNZkzwGPLm';
// $db_name = 'if0_43031712_fanhub';
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fan_hub_plus';
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
