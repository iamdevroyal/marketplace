<?php
namespace Core;

class Request {
    private $uri;
    private $method;
    private $params = [];
    private $query = [];
    private $body = [];
    private $headers = [];

    public function __construct() {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->query = $_GET;
        $this->body = $_POST;
        $this->headers = getallheaders();
    }

    public function getUri() {
        return $this->uri;
    }

    public function method() {
        return $this->method;
    }

    public function isPost() {
        return $this->method === 'POST';
    }

    public function getQuery($key = null, $default = null) {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function getBody($key = null, $default = null) {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function setParams($params) {
        $this->params = $params;
    }

    public function getParam($key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    public function hasHeader($key) {
        return isset($this->headers[$key]);
    }

    public function getHeader($key) {
        return $this->headers[$key] ?? null;
    }

    public function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Validate CSRF token
     * @return bool
     */
    public function validateCsrf() {
        $token = $this->getBody('_token');
        return csrf_verify($token);
    }
}