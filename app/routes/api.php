<?php
// routes/api.php
return [
    'GET /api/products' => ['Api\ProductApiController', 'index'],
    'GET /api/products/{id}' => ['Api\ProductApiController', 'show'],
    'GET /api/vendors' => ['Api\VendorApiController', 'index'],
    'GET /api/vendors/{id}' => ['Api\VendorApiController', 'show'],
    'GET /api/vendors/{id}/products' => ['Api\VendorApiController', 'products'],
    
    'POST /api/cart/add' => ['Api\CartApiController', 'add'],
    'PUT /api/cart/update' => ['Api\CartApiController', 'update'],
    'POST /api/cart/remove' => ['Api\CartApiController', 'remove'],
    'GET /api/cart' => ['Api\CartApiController', 'show'],
];