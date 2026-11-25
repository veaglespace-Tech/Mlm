<?php
/**
 * SMTP Mail Helper for MLM Platform MLM Platform
 * Pure PHP SMTP client using sockets. No external dependencies.
 */

class MLMP_SMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption; // 'ssl', 'tls', or 'none'
    private $timeout = 10;
    private $logs = [];

    public function __construct($host, $port, $username, $password, $encryption = 'tls') {
        $this->host = $host;
        $this->port = (int)$port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = strtolower($encryption);
    }

    public function getLogs() {
        return implode("\n", $this->logs);
    }

    private function log($msg) {
        $this->logs[] = date('Y-m-d H:i:s') . " - " . trim($msg);
    }

    private function read($socket, $expectedCode) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        $this->log("S: " . $data);
        $code = substr($data, 0, 3);
        if ($code != $expectedCode) {
            throw new Exception("SMTP Error: Expected $expectedCode, got: " . $data);
        }
        return $data;
    }

    private function write($socket, $command) {
        $this->log("C: " . $command);
        fwrite($socket, $command . "\r\n");
    }

    public function send($to, $subject, $message, $fromEmail, $fromName = "MLM Platform") {
        $socket = null;
        try {
            $remoteHost = $this->host;
            if ($this->encryption === 'ssl') {
                $remoteHost = 'ssl://' . $this->host;
            }

            $this->log("Connecting to $remoteHost:{$this->port}");
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            $socket = @stream_socket_client("$remoteHost:{$this->port}", $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
            if (!$socket) {
                throw new Exception("Failed to connect: $errstr ($errno)");
            }

            // Connection greeting
            $this->read($socket, '220');

            // EHLO
            $this->write($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $ehloResponse = $this->read($socket, '250');

            // STARTTLS if TLS encryption
            if ($this->encryption === 'tls') {
                $this->write($socket, "STARTTLS");
                $this->read($socket, '220');
                
                // Enable cryptography on socket
                $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (!@stream_socket_enable_crypto($socket, true, $crypto_method)) {
                    throw new Exception("Failed to enable STARTTLS encryption");
                }
                
                // Send EHLO again post-TLS establishment
                $this->write($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
                $this->read($socket, '250');
            }

            // Authentication
            if (!empty($this->username) && !empty($this->password)) {
                $this->write($socket, "AUTH LOGIN");
                $this->read($socket, '334');

                $this->write($socket, base64_encode($this->username));
                $this->read($socket, '334');

                $this->write($socket, base64_encode($this->password));
                $this->read($socket, '235');
            }

            // Mail From
            $this->write($socket, "MAIL FROM:<" . $this->username . ">");
            $this->read($socket, '250');

            // Recipient To
            $this->write($socket, "RCPT TO:<$to>");
            $this->read($socket, '250');

            // Data
            $this->write($socket, "DATA");
            $this->read($socket, '354');

            // Headers & Body
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <" . $this->username . ">",
                "To: <$to>",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "Date: " . date('r'),
                "Message-ID: <" . md5(uniqid(microtime(), true)) . "@" . ($this->host) . ">"
            ];

            $emailBody = implode("\r\n", $headers) . "\r\n\r\n" . $message;
            // Clean periods at start of lines to prevent SMTP injection/truncation
            $emailBody = preg_replace('/^\./m', '..', $emailBody);

            $this->write($socket, $emailBody . "\r\n.");
            $this->read($socket, '250');

            // Quit
            $this->write($socket, "QUIT");
            $this->read($socket, '221');

            fclose($socket);
            $this->log("Email sent successfully to $to");
            return true;

        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage());
            $GLOBALS['mlmp_mail_last_error'] = $e->getMessage();
            if ($socket) {
                fclose($socket);
            }
            return false;
        }
    }
}

/**
 * Returns the last error recorded by mlmp_send_mail.
 */
function mlmp_get_last_mail_error() {
    return $GLOBALS['mlmp_mail_last_error'] ?? '';
}

/**
 * Global helper function to send email in MLM Platform.
 * Falls back to native mail() if SMTP is not enabled.
 */
function mlmp_send_mail($to, $subject, $message) {
    global $pdo;
    $GLOBALS['mlmp_mail_last_error'] = '';

    // Load active settings from $_ENV, fallback to DB if needed for wlink
    try {
        $stmt = $pdo->prepare("SELECT wlink, email, smtp_enabled, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption FROM settings WHERE sno = 0 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $settings = null;
    }

    $wlink = $settings['wlink'] ?? 'www.yourwebsite.com';
    $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? ($settings['email'] ?? "no-reply@$wlink");
    $fromName = "MLM Platform";

    $smtpEnabled = $_ENV['SMTP_ENABLED'] ?? ($settings['smtp_enabled'] ?? 0);

    if ($smtpEnabled == 1) {
        // Use custom SMTP
        // Priority: Use DB settings if configured, fallback to .env
        $smtpHost = !empty($settings['smtp_host']) ? $settings['smtp_host'] : ($_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
        $smtpPort = (int)(!empty($settings['smtp_port']) ? $settings['smtp_port'] : ($_ENV['SMTP_PORT'] ?? 587));
        $smtpUser = !empty($settings['smtp_username']) ? $settings['smtp_username'] : ($_ENV['SMTP_USERNAME'] ?? '');
        $smtpPass = !empty($settings['smtp_password']) ? $settings['smtp_password'] : ($_ENV['SMTP_PASSWORD'] ?? '');
        $smtpEnc  = !empty($settings['smtp_encryption']) ? $settings['smtp_encryption'] : ($_ENV['SMTP_ENCRYPTION'] ?? 'tls');

        $smtp = new MLMP_SMTP($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpEnc);
        $success = $smtp->send($to, $subject, $message, $fromEmail, $fromName);
        if ($success) {
            return true;
        } else {
            $logs = $smtp->getLogs();
            $GLOBALS['mlmp_mail_last_error'] = "SMTP Error: Failed to authenticate or dispatch via $smtpHost:$smtpPort.";
            error_log("MLMP SMTP Failed. Logs:\n" . $logs);
            
            // Write to local mail log for administrative audit
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            @file_put_contents($logDir . '/mail.log', "[" . date('Y-m-d H:i:s') . "] FAILED to $to | Subject: $subject\nLogs:\n$logs\n\n", FILE_APPEND);
        }
    }

    // Fallback to PHP native mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    
    // Ensure from email is not entirely broken which causes mail() to drop silently
    if (strpos($fromEmail, '@') === false) {
        $fromEmail = "noreply@localhost.localdomain";
    }
    
    $headers .= "From: $fromName <$fromEmail>\r\n";
    $mailResult = @mail($to, $subject, $message, $headers);
    if (!$mailResult) {
        if (empty($GLOBALS['mlmp_mail_last_error'])) {
            $GLOBALS['mlmp_mail_last_error'] = "PHP native mail() failed to deliver message to $to.";
        }
        error_log("MLMP Native Mail Failed. Recipient: $to. Ensure sendmail is configured or SMTP is enabled.");
    }
    return $mailResult;
}

