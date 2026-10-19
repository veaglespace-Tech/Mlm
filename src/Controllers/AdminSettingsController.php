<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminSettingsController
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

    public function general()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $settings = $db->fetch("SELECT * FROM settings WHERE sno = 0 LIMIT 1");

        $data = [
            'page_title' => 'General Settings',
            'active_nav' => 'settings',
            'settings' => $settings,
            'set_msg' => $_SESSION['set_msg'] ?? ''
        ];
        unset($_SESSION['set_msg']);

        View::render('admin/settings/general', $data, 'admin/layouts/admin');
    }

    public function updateGeneral()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();

        $wlink = $_POST['wlink'] ?? '';
        $coname = $_POST['coname'] ?? '';
        $coemail = $_POST['coemail'] ?? '';
        $codetail = $_POST['codetail'] ?? '';
        $payemail = $_POST['payemail'] ?? ''; 
        $fb = $_POST['fblink'] ?? '';
        $tw = $_POST['twitterlink'] ?? '';
        $sno = $_POST['sno'] ?? 0;
        $maintain = $_POST['maintain'] ?? 0;
        $hdrtext = $_POST['hdrtext'] ?? '';
        $ftrtext = $_POST['ftrtext'] ?? '';
        $payzaid = $_POST['payzaid'] ?? ''; 
        $solidid = $_POST['solidid'] ?? '';
        $solidbutton = $_POST['solidbuttonid'] ?? '';
        $alwdpayment = $_POST['alwdpayment'] ?? 2; 

        $referral_bonus_referrer = (float)($_POST['referral_bonus_referrer'] ?? 50.00);
        $admin_referral_bonus = (float)($_POST['admin_referral_bonus'] ?? 25.00);

        $sql = "UPDATE settings SET 
                header = ?, footer = ?, wlink = ?, coname = ?, invoicedetails = ?, email = ?, 
                fblink = ?, twitterlink = ?, paypalid = ?, maintain = ?, alwdpayment = ?, 
                payzaid = ?, solidtrustid = ?, solidbutton = ?, referral_bonus_referrer = ?, 
                admin_referral_bonus = ? WHERE sno = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $hdrtext, $ftrtext, $wlink, $coname, $codetail, $coemail, $fb, $tw, $payemail, 
            $maintain, $alwdpayment, $payzaid, $solidid, $solidbutton, $referral_bonus_referrer, 
            $admin_referral_bonus, $sno
        ]);

        $alwdpaypal = $_POST['alwdpaypal'] ?? 0;
        $alwdpayza = $_POST['alwdpayza'] ?? 0;
        $alwdsolid = $_POST['alwdsolid'] ?? 0;
        
        $pdo->prepare("UPDATE paymethods SET active = ? WHERE id = 1")->execute([$alwdpaypal]);
        $pdo->prepare("UPDATE paymethods SET active = ? WHERE id = 2")->execute([$alwdpayza]);
        $pdo->prepare("UPDATE paymethods SET active = ? WHERE id = 3")->execute([$alwdsolid]);

        $_SESSION['set_msg'] = "Settings successfully updated.";
        header("Location: /?route=admin.settings.general");
        exit;
    }

    public function email()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $settings = $db->fetch("SELECT * FROM settings WHERE sno = 0 LIMIT 1");

        $data = [
            'page_title' => 'Email SMTP Settings',
            'active_nav' => 'settings',
            'settings' => $settings,
            'email_msg' => $_SESSION['email_msg'] ?? ''
        ];
        unset($_SESSION['email_msg']);

        View::render('admin/settings/email', $data, 'admin/layouts/admin');
    }

    public function updateEmail()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $code = $_POST['code'] ?? '';
            $etext = $_POST['etext'] ?? '';
            if (!empty($code) && !empty($etext)) {
                $stmt = $pdo->prepare("UPDATE emailtext SET code = ?, etext = ? WHERE id = ?");
                $stmt->execute([$code, $etext, $id]);
                $_SESSION['email_msg'] = "Template updated successfully.";
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM emailtext WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['email_msg'] = "Template deleted successfully.";
        } elseif ($action === 'create') {
            $code = $_POST['new_code'] ?? '';
            $etext = $_POST['new_etext'] ?? '';
            if (!empty($code) && !empty($etext)) {
                $stmt = $pdo->prepare("INSERT INTO emailtext (code, etext, emailactive) VALUES (?, ?, 1)");
                $stmt->execute([$code, $etext]);
                $_SESSION['email_msg'] = "New template created successfully.";
            }
        }

        header("Location: /?route=admin.settings.email");
        exit;
    }

    public function notifications()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $notifications = $db->fetchAll("SELECT * FROM notifications ORDER BY id DESC");

        $data = [
            'page_title' => 'Platform Notifications',
            'active_nav' => 'notifications',
            'notifications' => $notifications,
            'noti_msg' => $_SESSION['noti_msg'] ?? ''
        ];
        unset($_SESSION['noti_msg']);

        View::render('admin/settings/notifications', $data, 'admin/layouts/admin');
    }

    public function postNotification()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $nhead = $_POST['notihead'] ?? '';
        $nbody = $_POST['notibody'] ?? '';

        if (strlen($nhead) > 1 && strlen($nbody) > 3) {
            $stmt = $pdo->prepare("INSERT INTO notifications (subject, body, posteddate, valid) VALUES (?, ?, NOW(), 1)");
            $stmt->execute([$nhead, $nbody]);
            $_SESSION['noti_msg'] = "Notification posted successfully.";
        } else {
            $_SESSION['noti_msg'] = "Error: Subject must be >1 char and Body >3 chars.";
        }

        header("Location: /?route=admin.settings.notifications");
        exit;
    }

    public function deleteNotification()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();

        $id = (int)($_POST['notisub'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['noti_msg'] = "Notification deleted successfully.";
        }

        header("Location: /?route=admin.settings.notifications");
        exit;
    }
}
