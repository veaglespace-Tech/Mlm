<?php
namespace App\Models;

use App\Core\Database;

class User
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function exists($username, $email, $mobile)
    {
        $sql = "SELECT COUNT(*) as count FROM affiliateuser WHERE username = :u OR email = :e OR mobile = :m";
        $result = $this->db->fetch($sql, [
            'u' => $username,
            'e' => $email,
            'm' => $mobile
        ]);
        return $result['count'] > 0;
    }

    public function checkSponsor($sponsorUsername)
    {
        $sql = "SELECT COUNT(*) as count FROM affiliateuser WHERE username = :u";
        $result = $this->db->fetch($sql, ['u' => $sponsorUsername]);
        return $result['count'] > 0;
    }

    // Additional user logic (create, update) goes here
}
