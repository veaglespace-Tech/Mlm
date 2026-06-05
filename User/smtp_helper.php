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
            $socket = @fsockopen($remoteHost, $this->port, $errno, $errstr, $this->timeout);
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
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
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
            if ($socket) {
                fclose($socket);
            }
            return false;
        }
    }
}

/**
 * Global helper function to send email in MLM Platform.
 * Falls back to native mail() if SMTP is not enabled.
 */
function mlmp_send_mail($to, $subject, $message) {
    global $pdo;

    // Load active settings from DB
    try {
        $stmt = $pdo->prepare("SELECT wlink, email, smtp_enabled, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption FROM settings WHERE sno = 0 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $settings = null;
    }

    $wlink = $settings['wlink'] ?? 'www.yourwebsite.com';
    $fromEmail = $settings['email'] ?? "no-reply@$wlink";
    $fromName = "MLM Platform";

    if ($settings && isset($settings['smtp_enabled']) && $settings['smtp_enabled'] == 1) {
        // Use custom SMTP
        $smtp = new MLMP_SMTP(
            $settings['smtp_host'],
            $settings['smtp_port'],
            $settings['smtp_username'],
            $settings['smtp_password'],
            $settings['smtp_encryption']
        );
        $success = $smtp->send($to, $subject, $message, $fromEmail, $fromName);
        if ($success) {
            return true;
        } else {
            // Write logs to temporary file for auditing in case of failure
            error_log("MLMP SMTP Failed. Logs:\n" . $smtp->getLogs());
        }
    }

    // Fallback to PHP native mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    return @mail($to, $subject, $message, $headers);
}

