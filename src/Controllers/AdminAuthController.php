<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminAuthController
{
    public function showLogin()
    {
        // Standalone login page, doesn't need layout
        View::render('admin/auth/login', [], false);
    }

    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /?route=admin");
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (strlen($username) < 8 || strlen($password) < 8) {
            echo "<font face='Verdana' size='2' color=red>Username or Password must be more than 8 char length<BR></font><br><input type='button' value='Retry' onClick='history.go(-1)'>";
            return;
        }

        $db = Database::getInstance();
        $adminRow = $db->fetch("SELECT password FROM affiliateuser WHERE username = :u AND active = 1 AND level = 1 LIMIT 1", ['u' => $username]);

        require_once __DIR__ . '/../../User/password_helper.php';

        if ($adminRow && mlmp_password_matches($password, $adminRow['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['adminidusername'] = $username;
            header("Location: /?route=admin.dashboard");
            exit;
        } else {
            echo "<br/><br/><br/><br/><br/><center>Username And Password Not Matched Or May Be Your Account Is Inactive. <br/> Contact Admin For More Information.</center>";
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['adminidusername']);
        header("Location: /?route=admin");
        exit;
    }
}
