<?php
namespace App\Routing;

use App\Utility\Response;

class Router
    {
        private array $routes = [];

        public function post(string $state, string $action, $handler): self{
            $key = $this->key($state, $action);

            if (isset($this->routes[$key])) {
                Response::json(9, 'Duplicate route: ' . $key);
            }

            $this->routes[$key] = $handler;
            return $this;
        }

        public function dispatch(string $state, string $action): void
        {
            $key = $this->key($state, $action);

            if (!isset($this->routes[$key])) {
                Response::json(9, 'Route not found');
            }

            $handler = $this->routes[$key];

            if (is_callable($handler)) {
                $handler();
                return;
            }

            if (!is_string($handler) || !is_file($handler)) {
                Response::json(9, 'Handler file not found');
            }

            require $handler;
        }

        private function key(string $state, string $action): string
        {
            return strtolower(trim($state)) . '.' . strtolower(trim($action));
        }
    }
?>