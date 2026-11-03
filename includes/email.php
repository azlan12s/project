<?php
/**
 * Native Gmail SMTP Mailer for Fan Hub Plus
 * No Composer / No external libraries / No .env required
 */

function send_gmail_smtp($to, $subject, $html_body, $text_body = '', $config = null)
{
    if (!$config) {
        $config = require __DIR__ . '/../config/mail.php';
    }

    $smtp_user = trim(isset($config['smtp_user']) ? $config['smtp_user'] : '');
    $smtp_pass = trim(isset($config['smtp_pass']) ? $config['smtp_pass'] : '');
    // Google app passwords are 16 letters, often grouped with spaces (e.g. "abcd efgh ijkl mnop")
    $smtp_pass_clean = str_replace(' ', '', $smtp_pass);

    // 1. Check if user has entered their credentials
    if (empty($smtp_user) || $smtp_user === 'your-email@gmail.com' || empty($smtp_pass_clean) || $smtp_pass_clean === 'your-16-char-app-password') {
        return array(
            'ok' => false,
            'error' => 'credentials_missing',
            'message' => 'Gmail credentials not configured in config/mail.php. Please add your Gmail ID and 16-character App Password.'
        );
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return array(
            'ok' => false,
            'error' => 'invalid_recipient',
            'message' => 'Recipient email address is invalid.'
        );
    }

    $from_name = !empty($config['from_name']) ? trim($config['from_name']) : 'Fan Hub Plus Support';
    $from_email = (!empty($config['from_email']) && filter_var($config['from_email'], FILTER_VALIDATE_EMAIL))
        ? trim($config['from_email'])
        : $smtp_user;

    $ports_to_try = array();
    $primary_port = (int) (isset($config['smtp_port']) ? $config['smtp_port'] : 465);
    $ports_to_try[] = ($primary_port === 587) ? 587 : 465;
    $ports_to_try[] = ($ports_to_try[0] === 465) ? 587 : 465; // fallback port

    $last_error = '';

    foreach ($ports_to_try as $port) {
        $result = smtp_deliver_gmail(
            $config['smtp_host'],
            $port,
            $smtp_user,
            $smtp_pass_clean,
            $from_name,
            $from_email,
            $to,
            $subject,
            $html_body,
            $text_body
        );

        if ($result['ok']) {
            return $result;
        }

        $last_error = $result['message'];

        // If error is authentication failure, no need to retry another port (credentials are wrong)
        if ($result['error'] === 'auth_failed') {
            return $result;
        }
    }

    return array(
        'ok' => false,
        'error' => 'smtp_failed',
        'message' => $last_error
    );
}

function smtp_deliver_gmail($host, $port, $user, $pass, $from_name, $from_email, $to, $subject, $html_body, $text_body)
{
    $timeout = 12;
    $context = stream_context_create(array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    ));

    $remote = ($port === 465) ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        return array(
            'ok' => false,
            'error' => 'connection_failed',
            'message' => "Could not connect to {$host}:{$port} ($errstr [{$errno}])"
        );
    }

    stream_set_timeout($socket, $timeout);

    // Initial greeting
    $greeting = smtp_get_lines($socket);
    if (substr($greeting, 0, 3) !== '220') {
        fclose($socket);
        return array('ok' => false, 'error' => 'greeting_failed', 'message' => "Invalid greeting: $greeting");
    }

    // EHLO
    fputs($socket, "EHLO localhost\r\n");
    $ehlo = smtp_get_lines($socket);
    if (substr($ehlo, 0, 3) !== '250') {
        fclose($socket);
        return array('ok' => false, 'error' => 'ehlo_failed', 'message' => "EHLO failed: $ehlo");
    }

    // STARTTLS for port 587
    if ($port === 587) {
        fputs($socket, "STARTTLS\r\n");
        $tls_resp = smtp_get_lines($socket);
        if (substr($tls_resp, 0, 3) !== '220') {
            fclose($socket);
            return array('ok' => false, 'error' => 'starttls_failed', 'message' => "STARTTLS failed: $tls_resp");
        }

        $crypto_ok = false;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
        }
        if (!$crypto_ok) {
            $crypto_ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
        }

        if (!$crypto_ok) {
            fclose($socket);
            return array('ok' => false, 'error' => 'crypto_failed', 'message' => 'TLS handshake failed on port 587.');
        }

        // Resend EHLO after TLS handshake
        fputs($socket, "EHLO localhost\r\n");
        $ehlo2 = smtp_get_lines($socket);
        if (substr($ehlo2, 0, 3) !== '250') {
            fclose($socket);
            return array('ok' => false, 'error' => 'ehlo_post_tls_failed', 'message' => "EHLO post-TLS failed: $ehlo2");
        }
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $auth_resp = smtp_get_lines($socket);
    if (substr($auth_resp, 0, 3) !== '334') {
        fclose($socket);
        return array('ok' => false, 'error' => 'auth_login_failed', 'message' => "AUTH LOGIN rejected: $auth_resp");
    }

    // Username
    fputs($socket, base64_encode($user) . "\r\n");
    $user_resp = smtp_get_lines($socket);
    if (substr($user_resp, 0, 3) !== '334') {
        fclose($socket);
        return array('ok' => false, 'error' => 'auth_failed', 'message' => "Gmail username rejected: $user_resp");
    }

    // Password
    fputs($socket, base64_encode($pass) . "\r\n");
    $pass_resp = smtp_get_lines($socket);
    if (substr($pass_resp, 0, 3) !== '235') {
        fclose($socket);
        return array(
            'ok' => false,
            'error' => 'auth_failed',
            'message' => "Gmail Authentication Failed: Google rejected username/password. Please verify your Gmail ID and 16-character App Password in config/mail.php. (Server: $pass_resp)"
        );
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM: <{$from_email}>\r\n");
    $from_resp = smtp_get_lines($socket);
    if (substr($from_resp, 0, 3) !== '250') {
        fclose($socket);
        return array('ok' => false, 'error' => 'mail_from_failed', 'message' => "MAIL FROM rejected: $from_resp");
    }

    // RCPT TO
    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $to_resp = smtp_get_lines($socket);
    if (substr($to_resp, 0, 3) !== '250') {
        fclose($socket);
        return array('ok' => false, 'error' => 'rcpt_to_failed', 'message' => "RCPT TO rejected: $to_resp");
    }

    // DATA
    fputs($socket, "DATA\r\n");
    $data_resp = smtp_get_lines($socket);
    if (substr($data_resp, 0, 3) !== '354') {
        fclose($socket);
        return array('ok' => false, 'error' => 'data_failed', 'message' => "DATA rejected: $data_resp");
    }

    // Compose MIME message
    $boundary = "=_fhp_" . md5(uniqid(microtime(true), true));
    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encoded_from_name = '=?UTF-8?B?' . base64_encode($from_name) . '?=';

    $headers = array();
    $headers[] = "Date: " . date('r');
    $headers[] = "From: {$encoded_from_name} <{$from_email}>";
    $headers[] = "Reply-To: <{$from_email}>";
    $headers[] = "To: <{$to}>";
    $headers[] = "Subject: {$encoded_subject}";
    $headers[] = "Message-ID: <" . md5(uniqid()) . "@smtp.gmail.com>";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
    $headers[] = "X-Mailer: FanHubPlus-Gmail-SMTP";

    $message_body = implode("\r\n", $headers) . "\r\n\r\n";

    // Plain text alternative
    if (!empty($text_body)) {
        $message_body .= "--{$boundary}\r\n";
        $message_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message_body .= chunk_split(base64_encode($text_body)) . "\r\n";
    }

    // HTML alternative
    $message_body .= "--{$boundary}\r\n";
    $message_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message_body .= chunk_split(base64_encode($html_body)) . "\r\n";
    $message_body .= "--{$boundary}--\r\n";

    // Dot termination on its own line
    $message_body .= ".\r\n";

    fputs($socket, $message_body);
    $send_resp = smtp_get_lines($socket);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    if (substr($send_resp, 0, 3) !== '250') {
        return array('ok' => false, 'error' => 'send_failed', 'message' => "Message rejected by Gmail: $send_resp");
    }

    return array('ok' => true, 'message' => 'Email sent successfully via Gmail SMTP.');
}

function smtp_get_lines($socket)
{
    $data = '';
    while ($str = fgets($socket, 515)) {
        $data .= $str;
        // SMTP completion check: 3 digits followed by a space
        if (isset($str[3]) && $str[3] === ' ') {
            break;
        }
    }
    return $data;
}
