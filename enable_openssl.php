<?php
$ini_path = 'C:\Users\HP\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.ini';
$content = file_get_contents($ini_path);

// Uncomment extension_dir="ext"
$content = preg_replace('/^;?extension_dir = "ext"/m', 'extension_dir = "ext"', $content);

// Uncomment extension=openssl
$content = preg_replace('/^;?extension=openssl/m', 'extension=openssl', $content);

file_put_contents($ini_path, $content);
echo "php.ini updated to enable openssl!\n";
?>
