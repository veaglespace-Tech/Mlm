<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use RuntimeException;

class AdminPaymentController
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

    public function history()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        // Ensure payout schema exists (via backward compatibility or explicitly)
        // require_once __DIR__ . '/../../User/schema_migrations.php';
        // ensure_payout_schema($db->connection());

        $per_page = 20;
        $current_page = max(1, (int)($_GET['page'] ?? 1));
        
        $total_payments = $db->fetch("SELECT COUNT(*) as cnt FROM payments WHERE payment_status = 1")['cnt'];
        $total_pages = max(1, (int)ceil($total_payments / $per_page));
        if ($current_page > $total_pages) $current_page = $total_pages;
        $offset = ($current_page - 1) * $per_page;

        $payments = $db->fetchAll("
            SELECT p.*, a.username, a.fname, a.bankname, a.accountno, a.ifsc 
            FROM payments p
            JOIN affiliateuser a ON p.userid = a.Id
            WHERE p.payment_status = 1
            ORDER BY p.id DESC
            LIMIT $per_page OFFSET $offset
        ");

        $data = [
            'page_title' => 'Payment History',
            'active_nav' => 'payments',
            'payments' => $payments,
            'current_page' => $current_page,
            'total_pages' => $total_pages
        ];

        View::render('admin/payments/history', $data, 'admin/layouts/admin');
    }

    public function requests()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $requests = $db->fetchAll("
            SELECT p.*, a.username, a.fname, a.bankname, a.accountno, a.ifsc, a.tamount as wallet_balance
            FROM payments p
            JOIN affiliateuser a ON p.userid = a.Id
            WHERE p.payment_status = 0
            ORDER BY p.id ASC
        ");

        $data = [
            'page_title' => 'Payout Requests',
            'active_nav' => 'payrequest',
            'requests' => $requests,
            'pay_msg' => $_SESSION['pay_msg'] ?? ''
        ];
        unset($_SESSION['pay_msg']);

        View::render('admin/payments/requests', $data, 'admin/layouts/admin');
    }

    public function makePayment()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $tomake = (int)($_POST["payid"] ?? 0);
        if ($tomake <= 0) {
            $_SESSION['pay_msg'] = "Invalid payment.";
            header("Location: /?route=admin.payments.requests");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $payment = mlmp_pdo_fetch($pdo, "SELECT id, userid, payment_amount, payment_status FROM payments WHERE id = ? LIMIT 1 FOR UPDATE", [$tomake]);
            if (!$payment) throw new \RuntimeException("Payment request not found.");
            if ((int)$payment['payment_status'] !== 0) throw new \RuntimeException("Only pending requests can be marked paid.");

            $amount = (float)$payment['payment_amount'];
            $userId = (int)$payment['userid'];

            $stmtDeduct = $pdo->prepare("UPDATE affiliateuser SET tamount = tamount - ? WHERE Id = ? AND tamount >= ?");
            $stmtDeduct->execute([$amount, $userId, $amount]);
            if ($stmtDeduct->rowCount() !== 1) throw new \RuntimeException("User balance is no longer sufficient for this payout.");

            mlmp_pdo_execute($pdo, "UPDATE payments SET payment_status = 1, reviewed_at = NOW(), review_note = ? WHERE id = ?", ["Marked paid by " . $_SESSION['adminidusername'], $tomake]);

            $pdo->commit();
            $_SESSION['pay_msg'] = "Status Updated and Wallet Deducted.";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['pay_msg'] = "Action could not be performed: " . $e->getMessage();
        }

        header("Location: /?route=admin.payments.requests");
        exit;
    }

    public function rejectPayment()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $toreject = (int)($_POST["payid"] ?? 0);
        if ($toreject <= 0) {
            $_SESSION['pay_msg'] = "Invalid payment.";
            header("Location: /?route=admin.payments.requests");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $payment = mlmp_pdo_fetch($pdo, "SELECT id, payment_status FROM payments WHERE id = ? LIMIT 1 FOR UPDATE", [$toreject]);
            if (!$payment) throw new \RuntimeException("Payment request not found.");
            if ((int)$payment['payment_status'] !== 0) throw new \RuntimeException("Only pending requests can be rejected.");

            mlmp_pdo_execute($pdo, "UPDATE payments SET payment_status = 2, reviewed_at = NOW(), review_note = ? WHERE id = ?", ["Rejected by " . $_SESSION['adminidusername'], $toreject]);

            $pdo->commit();
            $_SESSION['pay_msg'] = "Withdrawal request rejected.";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['pay_msg'] = "Action could not be performed: " . $e->getMessage();
        }

        header("Location: /?route=admin.payments.requests");
        exit;
    }
}
