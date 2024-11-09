<?php
// api/ProductApiController.php
namespace Api;

class ProductApiController extends ApiController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new \Models\Product();
    }
    
    public function index() {
        $this->authenticate();
        $products = $this->productModel->getAll();
        $this->json(['data' => $products]);
    }
    
    public function show($id) {
        $this->authenticate();
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
        }
        
        $this->json(['data' => $product]);
    }
}