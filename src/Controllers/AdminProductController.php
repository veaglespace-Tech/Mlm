<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminProductController
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

        $products = $db->fetchAll("SELECT * FROM products ORDER BY id DESC");

        $data = [
            'page_title' => 'Product Settings',
            'active_nav' => 'products',
            'products' => $products,
            'prod_msg' => $_SESSION['prod_msg'] ?? ''
        ];
        unset($_SESSION['prod_msg']);

        View::render('admin/products/index', $data, 'admin/layouts/admin');
    }

    public function create()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();

        $prodname = $_POST['prodname'] ?? '';
        $proddetail = $_POST['proddetail'] ?? '';
        $prodicon = $_POST['prodicon'] ?? '';
        $prodemoji = $_POST['prodemoji'] ?? '';
        if(empty($prodemoji)) {
            $prodemoji = "🌟";
        }

        if (strlen($prodname) > 1) {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, icon, emoji, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$prodname, $proddetail, $prodicon, $prodemoji]);
            $_SESSION['prod_msg'] = "Product created successfully.";
        }
        
        header("Location: /?route=admin.products");
        exit;
    }

    public function update()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();

        $id = (int)($_POST['prod_id'] ?? 0);
        $prodname = $_POST['prodname'] ?? '';
        $proddetail = $_POST['proddetail'] ?? '';
        $prodicon = $_POST['prodicon'] ?? '';
        $prodemoji = $_POST['prodemoji'] ?? '';
        if(empty($prodemoji)) {
            $prodemoji = "🌟";
        }

        if ($id > 0 && strlen($prodname) > 1) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, icon=?, emoji=? WHERE id=?");
            $stmt->execute([$prodname, $proddetail, $prodicon, $prodemoji, $id]);
            $_SESSION['prod_msg'] = "Product updated successfully.";
        }

        header("Location: /?route=admin.products");
        exit;
    }

    public function delete()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();

        $id = (int)($_POST['prod_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            $_SESSION['prod_msg'] = "Product deleted successfully.";
        }
        header("Location: /?route=admin.products");
        exit;
    }

    public function toggle()
    {
        $this->checkAuth();
        $db = Database::getInstance();
        $pdo = $db->connection();
        
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['prod_id'] ?? 0);
        
        if ($id > 0 && in_array($action, ['activate', 'deactivate'])) {
            $active = ($action === 'activate') ? 1 : 0;
            $pdo->prepare("UPDATE products SET active = ? WHERE id = ?")->execute([$active, $id]);
            $_SESSION['prod_msg'] = "Product status toggled successfully.";
        }
        header("Location: /?route=admin.products");
        exit;
    }
}
