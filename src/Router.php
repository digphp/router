<?php

declare(strict_types=1);

namespace DigPHP\Router;

use Exception;

/**
 * @method Route dispatch(string $httpMethod, string $uri)
 * @method string build(string $name, array $params = [], string $method = 'GET')
 * @method Collector get(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector post(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector put(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector delete(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector patch(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector head(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector any(string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method Collector addGroup(string $prefix, callable $callback, array $middlewares = [], array $params = [])
 * @method Collector addRoute(array $methods, string $route, $handler, array $middlewares = [], array $params = [], string $name = null)
 * @method array getData()
 * @method array parse(string $route)
 */
class Router
{

    protected $parser;
    protected $generator;
    protected $collector;
    protected $dispatcher;
    protected $builder;

    public function __construct()
    {
        $this->parser = new Parser;
        $this->generator = new Generator;
        $this->builder = new Builder($this->generator);
        $this->collector = new Collector($this->parser, $this->generator);
        $this->dispatcher = new Dispatcher($this->generator);
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['dispatch'])) {
            return $this->dispatcher->$name(...$arguments);
        } elseif (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'head', 'any', 'addGroup', 'addRoute'])) {
            return $this->collector->$name(...$arguments);
        } elseif (in_array($name, ['build'])) {
            return $this->builder->$name(...$arguments);
        } elseif (in_array($name, ['getData'])) {
            return $this->generator->$name(...$arguments);
        } elseif (in_array($name, ['parse'])) {
            return $this->parser->$name(...$arguments);
        } else {
            throw new Exception('Call to undefined method DigPHP\Router\Router::' . $name . '()');
        }
    }
}
