<?php
// csrf_helper.php - Robust CSRF token protection & Secure Session Setup

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    if (PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function is_csrf_valid() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $postToken = $_POST['csrf_token'] ?? '';
    $sessToken = $_SESSION['csrf_token'] ?? '';
    return (!empty($postToken) && !empty($sessToken) && hash_equals($sessToken, $postToken));
}

function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_csrf_valid()) {
            http_response_code(403);
            die("
            <div style='font-family:sans-serif;text-align:center;padding:50px;'>
                <h2 style='color:#dc2626;'>403 Forbidden - Security Token Mismatch</h2>
                <p style='color:#475569;'>Your session or security token (CSRF) has expired. Please go back, refresh the page, and try again.</p>
                <a href='javascript:history.back()' style='display:inline-block;margin-top:15px;padding:10px 20px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;'>Go Back</a>
            </div>
            ");
        }
    }
}
