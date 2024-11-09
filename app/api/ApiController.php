<?php
// api/ApiController.php
namespace Api;

class ApiController {
    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    protected function authenticate() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            $this->json(['error' => 'No API key provided'], 401);
        }
        
        $apiKey = str_replace('Bearer ', '', $headers['Authorization']);
        $apiKeyModel = new \Models\ApiKey();
        
        if (!$apiKeyModel->validate($apiKey)) {
            $this->json(['error' => 'Invalid API key'], 401);
        }
    }
}