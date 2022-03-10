<?php

declare(strict_types=1);

namespace DigPHP\Router;

class Dispatcher
{
    protected $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function dispatch(string $httpMethod, string $uri): Route
    {
        list($staticRouteMap, $varRouteMap) = $this->generator->getData();

        if (isset($staticRouteMap[$httpMethod][$uri])) {
            $staticRouteData = $staticRouteMap[$httpMethod][$uri];
            return new Route(true, true, $staticRouteData['handler'], $staticRouteData['middlewares'], $staticRouteData['params']);
        }

        if (isset($staticRouteMap['*'][$uri])) {
            $staticRouteData = $staticRouteMap['*'][$uri];
            return new Route(true, true, $staticRouteData['handler'], $staticRouteData['middlewares'], $staticRouteData['params']);
        }

        if ($httpMethod === 'HEAD') {
            if (isset($staticRouteMap['GET'][$uri])) {
                $staticRouteData = $staticRouteMap['GET'][$uri];
                return new Route(true, true, $staticRouteData['handler'], $staticRouteData['middlewares'], $staticRouteData['params']);
            }
        }

        if (isset($varRouteMap[$httpMethod])) {
            if ($result = $this->dispatchVariableRoute($varRouteMap[$httpMethod], $uri)) {
                return new Route(true, true, ...$result);
            }
        }

        if (isset($varRouteMap['*'])) {
            if ($result = $this->dispatchVariableRoute($varRouteMap['*'], $uri)) {
                return new Route(true, true, ...$result);
            }
        }

        if ($httpMethod === 'HEAD') {
            if (isset($varRouteMap['GET'])) {
                if ($result = $this->dispatchVariableRoute($varRouteMap['GET'], $uri)) {
                    return new Route(true, true, ...$result);
                }
            }
        }

        if ($httpMethod === 'HEAD') {
            $methods = [$httpMethod, 'GET', '*'];
        } else {
            $methods = [$httpMethod, '*'];
        }

        foreach ($staticRouteMap as $method => $uriMap) {
            if (in_array($method, $methods)) {
                continue;
            }

            if (isset($uriMap[$uri])) {
                return new Route(true, false, $uriMap[$uri]['handler'], $uriMap[$uri]['middlewares'], $uriMap[$uri]['params']);
            }
        }

        foreach ($varRouteMap as $method => $routeData) {
            if (in_array($method, $methods)) {
                continue;
            }

            if ($result = $this->dispatchVariableRoute($routeData, $uri)) {
                return new Route(true, false, ...$result);
            }
        }

        return new Route(false);
    }

    protected function dispatchVariableRoute(array $routeData, string $uri): ?array
    {
        foreach ($routeData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }
            $route = $data['routeMap'][count($matches)];
            $params = [];
            $i = 0;
            foreach ($route['variables'] as $varName) {
                $params[$varName] = $matches[++$i];
            }
            return [$route['handler'], $route['middlewares'], array_merge($params, $route['params'])];
        }
        return null;
    }
}
