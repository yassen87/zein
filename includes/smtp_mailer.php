<?php
declare(strict_types=1);

/**
 * Simple SMTP mailer — works from Apache AND CLI without any libraries.
 * Uses direct socket connection to smtp.gmail.com:587 with STARTTLS + AUTH LOGIN.
 */
function smtp_send(string $to, string $subjectRaw, string $htmlBody): bool
{
    // Load from DB settings, fall back to hard-coded defaults
    $dbHost     = '';
    $dbPort     = '';
    $dbUser     = '';
    $dbPass     = '';
    $dbFromName = '';

    if (function_exists('medal_pdo')) {
        $pdo = medal_pdo();
        if ($pdo) {
            try {
                $rows = $pdo->query(
                    "SELECT setting_key, setting_value_en FROM settings
                     WHERE setting_key IN ('smtp_email','smtp_password','smtp_from_name','smtp_host','smtp_port')"
                )->fetchAll(PDO::FETCH_KEY_PAIR);
                $dbHost     = $rows['smtp_host']     ?? '';
                $dbPort     = $rows['smtp_port']     ?? '';
                $dbUser     = $rows['smtp_email']    ?? '';
                $dbPass     = $rows['smtp_password'] ?? '';
                $dbFromName = $rows['smtp_from_name'] ?? '';
            } catch (Throwable) {}
        }
    }

    // Fallback defaults (used when DB is empty)
    $host     = $dbHost     !== '' ? $dbHost     : 'smtp.gmail.com';
    $port     = $dbPort     !== '' ? (int)$dbPort : 587;
    $user     = $dbUser     !== '' ? $dbUser     : 'zeinperfume83@gmail.com';
    $pass     = $dbPass     !== '' ? $dbPass     : 'ertq mtip wbpr bzgy';
    $from     = $user;
    $fromName = $dbFromName !== '' ? $dbFromName : (defined('SITE_NAME') ? SITE_NAME : 'Zain Perfumes');

    $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';

    // Open socket
    $sock = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$sock) {
        error_log("SMTP: Cannot connect — $errstr ($errno)");
        return false;
    }
    stream_set_timeout($sock, 10);

    $read = function() use ($sock): string {
        $data = '';
        while ($line = fgets($sock, 515)) {
            $data .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $data;
    };

    $send = function(string $cmd) use ($sock): void {
        fwrite($sock, $cmd . "\r\n");
    };

    $read(); // 220 banner

    $send("EHLO localhost");
    $read();

    $send("STARTTLS");
    $read();

    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $send("EHLO localhost");
    $read();

    $send("AUTH LOGIN");
    $read();

    $send(base64_encode($user));
    $read();

    $send(base64_encode($pass));
    $response = $read();
    if (strpos($response, '235') === false) {
        error_log("SMTP: Auth failed — $response");
        fclose($sock);
        return false;
    }

    $send("MAIL FROM:<$from>");
    $read();

    $send("RCPT TO:<$to>");
    $read();

    $send("DATA");
    $read();

    $date     = date('r');
    $boundary = md5(uniqid((string)mt_rand(), true));

    $message  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
    $message .= "To: $to\r\n";
    $message .= "Subject: $subject\r\n";
    $message .= "Date: $date\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "\r\n";
    $message .= chunk_split(base64_encode($htmlBody));
    $message .= "\r\n.\r\n";

    fwrite($sock, $message);
    $response = $read();

    $send("QUIT");
    fclose($sock);

    if (strpos($response, '250') !== false) {
        return true;
    }
    error_log("SMTP: Send failed — $response");
    return false;
}
