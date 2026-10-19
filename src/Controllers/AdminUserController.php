<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminUserController
{
    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['adminidusername'])) {
            header("Location: /?route=admin");
            exit;
        }
    }

    public function index()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        // Stats
        $total_users = $db->fetch("SELECT COUNT(*) as cnt FROM affiliateuser WHERE level = 2")['cnt'];
        $active_users = $db->fetch("SELECT COUNT(*) as cnt FROM affiliateuser WHERE level = 2 AND active = 1")['cnt'];
        $inactive_users = $db->fetch("SELECT COUNT(*) as cnt FROM affiliateuser WHERE level = 2 AND active = 0")['cnt'];

        // Pagination
        $per_page = 20;
        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $total_pages = max(1, (int)ceil($total_users / $per_page));
        if ($current_page > $total_pages) $current_page = $total_pages;
        $offset = ($current_page - 1) * $per_page;

        // Fetch users
        $users = $db->fetchAll("
            SELECT u.*, p.name as package_name 
            FROM affiliateuser u 
            LEFT JOIN packages p ON u.pcktaken = p.id 
            WHERE u.level = 2 
            ORDER BY u.doj DESC
            LIMIT $per_page OFFSET $offset
        ");

        $data = [
            'page_title' => 'Users Directory',
            'active_nav' => 'users',
            'total_users' => $total_users,
            'active_users' => $active_users,
            'inactive_users' => $inactive_users,
            'users' => $users,
            'current_page' => $current_page,
            'total_pages' => $total_pages
        ];

        View::render('admin/users/index', $data, 'admin/layouts/admin');
    }

    public function approvals()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $per_page = 15;
        $current_page = max(1, (int)($_GET['page'] ?? 1));
        
        $total_approvals = $db->fetch("SELECT COUNT(*) as cnt FROM pending_registrations p JOIN affiliateuser a ON p.referedby = a.username WHERE a.level = 1 AND p.admin_approval_status = 'Pending'")['cnt'];
        $total_pages = max(1, (int)ceil($total_approvals / $per_page));
        if ($current_page > $total_pages) $current_page = $total_pages;
        $offset = ($current_page - 1) * $per_page;

        $pending_users = $db->fetchAll("
            SELECT p.*, a.username as admin_username 
            FROM pending_registrations p 
            JOIN affiliateuser a ON p.referedby = a.username 
            WHERE a.level = 1 AND p.admin_approval_status = 'Pending'
            ORDER BY p.doj DESC, p.id DESC
            LIMIT $per_page OFFSET $offset
        ");

        $data = [
            'page_title' => 'Approval Requests',
            'active_nav' => 'approvals',
            'pending_users' => $pending_users,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'approval_msg' => $_SESSION['approval_msg'] ?? ''
        ];
        unset($_SESSION['approval_msg']);

        View::render('admin/users/approvals', $data, 'admin/layouts/admin');
    }

    public function approve()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $action = $_POST['action'] ?? '';
        $username = $_POST['username'] ?? '';

        if (empty($action) || empty($username)) {
            $_SESSION['approval_msg'] = "Missing parameters.";
            header("Location: /?route=admin.users.approvals");
            exit;
        }

        $stmtPending = $pdo->prepare("SELECT p.*, a.level FROM pending_registrations p JOIN affiliateuser a ON p.referedby = a.username WHERE p.username = ? AND a.level = 1");
        $stmtPending->execute([$username]);
        $pendingUser = $stmtPending->fetch();

        if (!$pendingUser) {
            $_SESSION['approval_msg'] = "Pending registration not found or invalid sponsor.";
            header("Location: /?route=admin.users.approvals");
            exit;
        }

        require_once __DIR__ . "/../../User/smtp_helper.php";
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = '';

        if ($action === 'reject') {
            $stmtHistory = $pdo->prepare("INSERT INTO rejected_registrations (username, fname, email, referedby, mobile, country) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtHistory->execute([
                $pendingUser['username'], $pendingUser['fname'], $pendingUser['email'],
                $pendingUser['referedby'], $pendingUser['mobile'], $pendingUser['country']
            ]);

            $pdo->prepare("UPDATE pending_registrations SET admin_approval_status = 'Rejected' WHERE username = ?")->execute([$username]);

            $rejectedLink = $protocol . '://' . $host . $basePath . '/rejected.php?username=' . urlencode($username);
            $rejectSubject = "Application Rejected";
            $rejectMessage = "<p>Your application was rejected. View status: <a href='$rejectedLink'>$rejectedLink</a></p>";
            mlmp_send_mail($pendingUser['email'], $rejectSubject, $rejectMessage);

            $_SESSION['approval_msg'] = "User @$username REJECTED.";
        } elseif ($action === 'approve') {
            $pdo->prepare("UPDATE pending_registrations SET admin_approval_status = 'Approved' WHERE username = ?")->execute([$username]);

            $paymentLink = $protocol . '://' . $host . $basePath . '/payu_payment.php?retry_username=' . urlencode($username);
            $approvalSubject = "Application Approved - Action Required";
            $approvalMessage = "<p>Your application was approved! Please complete payment here: <a href='$paymentLink'>$paymentLink</a></p>";
            mlmp_send_mail($pendingUser['email'], $approvalSubject, $approvalMessage);

            $_SESSION['approval_msg'] = "User @$username APPROVED.";
        }

        header("Location: /?route=admin.users.approvals");
        exit;
    }

    public function toggleActive()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $action = $_POST['action'] ?? '';
        $username = $_POST['username'] ?? '';

        if (!empty($username) && in_array($action, ['activate', 'deactivate'])) {
            $active = ($action === 'activate') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE affiliateuser SET active = ? WHERE username = ?");
            $stmt->execute([$active, $username]);
            $_SESSION['admin_msg'] = "User @$username " . ($active ? "ACTIVATED" : "DEACTIVATED") . " successfully.";
        }

        header("Location: /?route=admin.users");
        exit;
    }
}
