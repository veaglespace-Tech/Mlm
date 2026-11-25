<?php
include_once(dirname(__DIR__) . "/z_db.php");

if (!isset($_SESSION['adminidusername'])) {
    header("Location: ../index.php");
    exit;
}

backup_tables($pdo);

/**
 * Export all database tables using PDO and stream as SQL download.
 */
function backup_tables(PDO $pdo, $tables = '*')
{
    $return = "-- MLM Platform Database Backup\n";
    $return .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    if ($tables === '*') {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    foreach ($tables as $table) {
        $safeTable = str_replace('`', '``', $table);
        $stmt = $pdo->query("SELECT * FROM `{$safeTable}`");
        if (!$stmt) {
            continue;
        }

        $return .= "DROP TABLE IF EXISTS `{$safeTable}`;\n";
        $createRow = $pdo->query("SHOW CREATE TABLE `{$safeTable}`")->fetch(PDO::FETCH_NUM);
        if (!empty($createRow[1])) {
            $return .= $createRow[1] . ";\n\n";
        }

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $values = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $pdo->quote((string)$val);
                }
            }
            $return .= "INSERT INTO `{$safeTable}` VALUES(" . implode(',', $values) . ");\n";
        }
        $return .= "\n\n";
    }

    $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'db-backup-' . date('Ymd-His') . '-' . substr(md5(implode(',', $tables)), 0, 12) . '.sql';
    $filepath = __DIR__ . '/' . $filename;
    file_put_contents($filepath, $return);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}
