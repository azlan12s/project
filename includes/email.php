<?php
function send_resend_email($to, $subject, $html, $text, $config)
{
    if (!function_exists('curl_init') || empty($config['resend_api_key'])) {
        error_log('Fan Hub Plus email is not configured: set RESEND_API_KEY and enable PHP cURL.');
        return false;
    }

    $from_name = trim(preg_replace('/[\r\n]+/', ' ', $config['from_name']));
    $from_address = trim($config['from_address']);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from_address, FILTER_VALIDATE_EMAIL)) {
        error_log('Fan Hub Plus email is not configured with valid sender/recipient addresses.');
        return false;
    }

    $payload = json_encode(array(
        'from' => $from_name . ' <' . $from_address . '>',
        'to' => array($to),
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ));
    if ($payload === false) {
        error_log('Fan Hub Plus could not encode an email request.');
        return false;
    }

    $curl = curl_init('https://api.resend.com/emails');
    curl_setopt_array($curl, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $config['resend_api_key'],
            'Content-Type: application/json',
            'Accept: application/json',
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ));

    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        error_log('Fan Hub Plus email API request failed: ' . $curl_error);
        return false;
    }
    if ($status < 200 || $status >= 300) {
        error_log('Fan Hub Plus email API returned HTTP ' . $status . ': ' . substr($response, 0, 1000));
        return false;
    }

    return true;
}
