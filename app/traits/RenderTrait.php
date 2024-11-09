<?php
// traits/RenderTrait.php
namespace Traits;

trait RenderTrait {
    protected function render($template, $data = []) {
        // Default CSRF token generation if not provided
        if (!isset($data['token'])) {
            $data['token'] = csrf_generate(); // Implement this function in your helpers
        }
        
        // Extract data to make variables available in the view
        extract($data);
        
        // Render the view
        $templatePath = BASE_PATH . "/views/{$template}.php";
        
        if (!file_exists($templatePath)) {
            throw new \Exception("View template not found: {$templatePath}");
        }
        
        include $templatePath;
    }
}