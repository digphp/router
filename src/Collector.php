<?php

declare(strict_types=1);

namespace DigPHP\Router;

class Collector
{
    protected $parser;
    protected $generator;

    protected $currentGroupPrefix;
    protected $currentMiddlewares;
    protected $currentParams;

    public function __construct(Parser $parser, Generator $generator)
    {
        $this->parser = $parser;
        $this->generator = $generator;
        $this->currentGroupPrefix = '';
        $this->currentMiddlewares = [];
        $this->currentParams = [];
    }

    public function get(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['GET'], $route, $handler, $middlewares, $params, $name);
    }

    public function post(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['POST'], $route, $handler, $middlewares, $params, $name);
    }

    public function put(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['PUT'], $route, $handler, $middlewares, $params, $name);
    }

    public function delete(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['DELETE'], $route, $handler, $middlewares, $params, $name);
    }

    public function patch(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['PATCH'], $route, $handler, $middlewares, $params, $name);
    }

    public function head(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['HEAD'], $route, $handler, $middlewares, $params, $name);
    }

    public function any(string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        return $this->addRoute(['*'], $route, $handler, $middlewares, $params, $name);
    }

    public function addGroup(string $prefix, callable $callback, array $middlewares = [], array $params = []): self
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentMiddlewares;
        $previousParams = $this->currentParams;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        if ($middlewares) {
            array_push($this->currentMiddlewares, ...$middlewares);
        }
        $this->currentParams = array_merge($this->currentParams, $params);
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentMiddlewares = $previousMiddlewares;
        $this->currentParams = $previousParams;
        return $this;
    }

    public function addRoute(array $methods, string $route, $handler, array $middlewares = [], array $params = [], string $name = null): self
    {
        if ($this->currentMiddlewares) {
            array_push($middlewares, ...$this->currentMiddlewares);
        }
        $route = $this->currentGroupPrefix . $route;
        $routeDatas = $this->parser->parse($route);
        $params = array_merge($params, $this->currentParams);
        foreach ($methods as $method) {
            foreach ($routeDatas as $routeData) {
                $this->generator->addRoute($method, $routeData, $handler, $middlewares, $params, $name);
            }
        }
        return $this;
    }
}
