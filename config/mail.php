<?php
$mail_base_url = getenv('FHP_BASE_URL');
if (!$mail_base_url) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    if (!preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/D', $host)) {
        $host = 'localhost';
    }
    $scheme = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    $mail_base_url = $scheme . '://' . $host . site_base_path();
}

return array(
    'provider' => 'resend',
    'resend_api_key' => getenv('RESEND_API_KEY') ? getenv('RESEND_API_KEY') : '',
    'from_name' => getenv('FHP_MAIL_FROM_NAME') ? getenv('FHP_MAIL_FROM_NAME') : 'Fan Hub Plus',
    'from_address' => getenv('FHP_MAIL_FROM_ADDRESS') ? getenv('FHP_MAIL_FROM_ADDRESS') : '',
    'base_url' => rtrim($mail_base_url, '/'),
);
