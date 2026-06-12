<?php
require 'User/z_db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Convert table to InnoDB and fix Primary Key
    $sql = "ALTER TABLE `affiliateuser` 
            DROP PRIMARY KEY,
            DROP INDEX `Id`,
            ADD PRIMARY KEY (`Id`),
            ADD UNIQUE INDEX `username` (`username`),
            ENGINE=InnoDB;";
            
    $pdo->exec($sql);
    echo "Successfully updated table schema to use Id as PRIMARY KEY and converted to InnoDB.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
