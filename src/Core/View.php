<?php
namespace App\Core;

class View
{
    public static function render($view, $data = [], $layout = 'app')
    {
        // Extract variables to be available in the view
        global $pdo, $con;
        extract($data);

        // Start capturing view content
        ob_start();
        $viewFile = __DIR__ . "/../../src/Views/{$view}.view.php";
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View {$viewFile} not found.";
        }
        
        $content = ob_get_clean();

        // If no layout is specified, just return the content
        if (!$layout) {
            echo $content;
            return;
        }

        // Require the layout and pass the captured $content to it
        if (strpos($layout, '/') !== false) {
            $layoutFile = __DIR__ . "/../../src/Views/{$layout}.view.php";
        } else {
            $layoutFile = __DIR__ . "/../../src/Views/layouts/{$layout}.view.php";
        }
        
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}
