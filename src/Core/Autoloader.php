<?php
namespace App\Core;

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            // Project-specific namespace prefix
            $prefix = 'App\\';
            // Base directory for the namespace prefix
            $base_dir = __DIR__ . '/../../src/';

            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return; // Not handled by this autoloader
            }

            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Replace namespace separators with directory separators
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
