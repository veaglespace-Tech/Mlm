<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminPackageController
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

        $packages = $db->fetchAll("SELECT * FROM packages ORDER BY id DESC");

        $data = [
            'page_title' => 'Package Settings',
            'active_nav' => 'packages',
            'packages' => $packages,
            'pac_msg' => $_SESSION['pac_msg'] ?? ''
        ];
        unset($_SESSION['pac_msg']);

        View::render('admin/packages/index', $data, 'admin/layouts/admin');
    }

    public function create()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $pname = trim($_POST['pckname'] ?? '');
        $pdetail = trim($_POST['pckdetail'] ?? '');
        $pprice = (float)($_POST['pckprice'] ?? 0);
        $pcurid = trim($_POST['currency'] ?? '');
        $pckmpay = (float)($_POST['pckmpay'] ?? 0);
        $pcksbonus = (float)($_POST['pcksbonus'] ?? 0);
        $pcktax = (float)($_POST['pcktax'] ?? 0);
        $renewdays = (int)($_POST['renewdays'] ?? 0);
        $binary_percent = (float)($_POST['binary_percent'] ?? 30);
        $sponsor_percent = (float)($_POST['sponsor_percent'] ?? 10);
        $capping_status = (int)($_POST['capping_status'] ?? 1);
        $capping_limit = (int)($_POST['capping_limit'] ?? 2);
        
        $levels = [];
        for ($i = 1; $i <= 20; $i++) {
            $levels[] = (float)($_POST["lev{$i}"] ?? 0);
        }

        if (strlen($pname) > 1 && $pprice >= 0) {
            $sql = "INSERT INTO packages (
                name, price, currency, details, tax, mpay, sbonus, cdate, active,
                binary_percent, sponsor_percent, capping_status, capping_limit,
                lev1, lev2, lev3, lev4, lev5, lev6, lev7, lev8, lev9, lev10,
                lev11, lev12, lev13, lev14, lev15, lev16, lev17, lev18, lev19, lev20, renewdays
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW(), 1,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
            $params = [
                $pname, $pprice, $pcurid, $pdetail, $pcktax, $pckmpay, $pcksbonus,
                $binary_percent, $sponsor_percent, $capping_status, $capping_limit
            ];
            $params = array_merge($params, $levels, [$renewdays]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['pac_msg'] = "Package created successfully.";
        } else {
            $_SESSION['pac_msg'] = "Error: Invalid package data.";
        }

        header("Location: /?route=admin.packages");
        exit;
    }

    public function update()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $pck_id = (int)($_POST['pck_id'] ?? 0);
        $pname = trim($_POST['pckname'] ?? '');
        $pdetail = trim($_POST['pckdetail'] ?? '');
        $pprice = (float)($_POST['pckprice'] ?? 0);
        $pckmpay = (float)($_POST['pckmpay'] ?? 0);
        $pcksbonus = (float)($_POST['pcksbonus'] ?? 0);
        $pcktax = (float)($_POST['pcktax'] ?? 0);
        $renewdays = (int)($_POST['renewdays'] ?? 0);
        $binary_percent = (float)($_POST['binary_percent'] ?? 30);
        $sponsor_percent = (float)($_POST['sponsor_percent'] ?? 10);
        $capping_status = (int)($_POST['capping_status'] ?? 1);
        $capping_limit = (int)($_POST['capping_limit'] ?? 2);
        
        $levels = [];
        for ($i = 1; $i <= 20; $i++) {
            $levels[] = (float)($_POST["lev{$i}"] ?? 0);
        }

        if ($pck_id > 0 && strlen($pname) > 1) {
            $sql = "UPDATE packages SET 
                name=?, price=?, details=?, tax=?, mpay=?, sbonus=?,
                binary_percent=?, sponsor_percent=?, capping_status=?, capping_limit=?,
                lev1=?, lev2=?, lev3=?, lev4=?, lev5=?, lev6=?, lev7=?, lev8=?, lev9=?, lev10=?,
                lev11=?, lev12=?, lev13=?, lev14=?, lev15=?, lev16=?, lev17=?, lev18=?, lev19=?, lev20=?, 
                renewdays=? WHERE id=?";
            $params = [
                $pname, $pprice, $pdetail, $pcktax, $pckmpay, $pcksbonus,
                $binary_percent, $sponsor_percent, $capping_status, $capping_limit
            ];
            $params = array_merge($params, $levels, [$renewdays, $pck_id]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['pac_msg'] = "Package updated successfully.";
        }

        header("Location: /?route=admin.packages");
        exit;
    }

    public function delete()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $id = (int)($_POST['pck_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM packages WHERE id = ?")->execute([$id]);
            $_SESSION['pac_msg'] = "Package deleted successfully.";
        }

        header("Location: /?route=admin.packages");
        exit;
    }

    public function toggle()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['pkg_id'] ?? 0);
        
        if ($id > 0 && in_array($action, ['activate', 'deactivate'])) {
            $active = ($action === 'activate') ? 1 : 0;
            $pdo->prepare("UPDATE packages SET active = ? WHERE id = ?")->execute([$active, $id]);
            $_SESSION['pac_msg'] = "Package status toggled successfully.";
        }

        header("Location: /?route=admin.packages");
        exit;
    }
}
