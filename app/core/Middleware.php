<?php
namespace Core;

class Middleware {
    private $middlewares = [];

    public function add($middleware) {
        $this->middlewares[] = $middleware;
    }

    public function run(Request $request, callable $next) {
        $middleware = array_shift($this->middlewares);

        if ($middleware === null) {
            return $next($request);
        }

        return $middleware->handle($request, function($request) use ($next) {
            return $this->run($request, $next);
        });
    }
}