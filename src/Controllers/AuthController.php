<?php
namespace App\Controllers;

use App\Models\User;

class AuthController
{
    public function showSignup()
    {
        // Extract session data for view
        $data = $_SESSION['signup_data'] ?? [];
        $name = $data['fname'] ?? '';
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $ref = $data['referedby'] ?? $_GET['aff'] ?? '';
        $errormsg = $_SESSION['errormsg'] ?? '';
        unset($_SESSION['errormsg']); // flash message

        // Render view
        require __DIR__ . '/../Views/auth/signup.view.php';
    }

    public function processSignup()
    {
        $userModel = new User();
        $msg = "";
        
        // Ensure POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /?route=signup");
            exit;
        }

        // Basic validation
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $ref = $_POST['referral'] ?? '';

        if (strlen($username) < 6) {
            $msg .= "User Id Should Contain Minimum 6 CHARACTERS.<BR>";
        }

        if ($password !== $password2) {
            $msg .= "Both Passwords Are Not Matching.<BR>";
        }

        // Check DB
        if ($userModel->exists($username, $email, $mobile)) {
            $msg .= "Username, Email, or Mobile already exists.<BR>";
        }

        if (!$userModel->checkSponsor($ref)) {
            $msg .= "Sponsor not found.<BR>";
        }

        if ($msg !== "") {
            // Failed
            $_SESSION['errormsg'] = $msg;
            // Retain valid data
            $_SESSION['signup_data'] = $_POST;
            header("Location: /?route=signup");
            exit;
        }

        // Success - (Ideally save to DB here, but for PoC we just set success)
        // Normally we would hash password and store pending registration
        $_SESSION['signup_success'] = "Registration details captured (MVC style)!";
        header("Location: /select_product.php");
        exit;
    }
}
