<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die('Cannot connect to MySQL: ' . mysqli_connect_error());
}

$existing = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'fan_hub_plus'");
if (!$existing) {
    die('Could not check whether the fan_hub_plus database exists. No installation changes were made.');
}
if (mysqli_num_rows($existing) > 0) {
    http_response_code(409);
    die('The fan_hub_plus database already exists. Installation was stopped to protect existing data. Remove the database in phpMyAdmin only if you intentionally want a clean reinstall.');
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) {
    die('schema.sql is missing.');
}

$ok = mysqli_multi_query($conn, $sql);
if (!$ok) {
    die('Install failed: ' . mysqli_error($conn));
}

do {
    $res = mysqli_store_result($conn);
    if ($res) {
        mysqli_free_result($res);
    }
} while (mysqli_more_results($conn) && mysqli_next_result($conn));

if (mysqli_error($conn)) {
    die('Install error: ' . mysqli_error($conn));
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fan Hub Plus install</title></head><body style="font-family:sans-serif;padding:40px;background:#0b1020;color:#fff">';
echo '<h1>Database ready</h1>';
echo '<p>Imported schema.sql into <strong>fan_hub_plus</strong> using simple mysqli queries.</p>';
echo '<p>Demo account credentials are listed in docs/README.docx.</p>';
echo '<p><a href="' . e(site_url('/index.php')) . '" style="color:#7dd3fc">Open homepage</a></p>';
echo '</body></html>';

