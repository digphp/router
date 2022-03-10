<?php

declare(strict_types=1);

namespace DigPHP\Router;

class Builder
{
    protected $staticRouteMap = [];
    protected $variableRouteData = [];

    public function __construct(Generator $generator)
    {
        list($staticRouteMap, $variableRouteData) = $generator->getData();
        foreach ($staticRouteMap as $method => $routes) {
            foreach ($routes as $route) {
                $route['method'] = $method;
                $this->staticRouteMap[$route['name'] ?: $route['routeStr']][] = $route;
            }
        }

        foreach ($variableRouteData as $method => $chunks) {
            foreach ($chunks as $chunk) {
                foreach ($chunk['routeMap'] as $route) {
                    if (!$route['name']) {
                        continue;
                    }
                    $route['method'] = $method;
                    $this->variableRouteData[$route['name']][] = $route;
                }
            }
        }
    }

    public function build(string $name, array $params = [], string $method = 'GET'): string
    {
        ksort($params);

        if (isset($this->staticRouteMap[$name])) {
            foreach ($this->staticRouteMap[$name] as $route) {
                if ($route['method'] != '*' && $route['method'] != $method) {
                    continue;
                }
                if (!$this->checkParams($route['params'], $params)) {
                    continue;
                }
                $query = array_diff_key($params, $route['params']);
                return $route['routeStr'] . ($query ? '?' . http_build_query($query) : '');
            }
        }

        $build = function (array $routeData, $querys): ?array {
            $uri = '';
            foreach ($routeData as $part) {
                if (is_array($part)) {
                    if (
                        isset($querys[$part[0]])
                        && preg_match('~^' . $part[1] . '$~', (string) $querys[$part[0]])
                    ) {
                        $uri .= urlencode($querys[$part[0]]);
                        unset($querys[$part[0]]);
                        continue;
                    } else {
                        return null;
                    }
                } else {
                    $uri .= $part;
                }
            }
            return [$uri, $querys];
        };

        if (isset($this->variableRouteData[$name])) {
            foreach ($this->variableRouteData[$name] as $route) {
                if ($route['method'] != '*' && $route['method'] != $method) {
                    continue;
                }
                if (!$this->checkParams($route['params'], $params)) {
                    continue;
                }
                $tmp = $build($route['routeData'], array_diff_key($params, $route['params']));
                if (!is_array($tmp)) {
                    continue;
                }
                return $tmp[0] . ($tmp[1] ? '?' . http_build_query($tmp[1]) : '');
            }
        }

        return $this->getWebRoot() . $name . ($params ? '?' . http_build_query($params) : '');
    }

    private function getWebRoot()
    {
        static $web_root;
        if (is_null($web_root)) {

            if (
                (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
                || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
            ) {
                $schema = 'https';
            } else {
                $schema = 'http';
            }

            $web_root = $schema . '://' . $_SERVER['HTTP_HOST'] . (function (): string {
                $script_name = '/' . implode('/', array_filter(
                    explode('/', $_SERVER['SCRIPT_NAME']),
                    function ($val) {
                        return strlen((string) $val) > 0 ? true : false;
                    }
                ));
                $request_uri = parse_url('/' . implode('/', array_filter(
                    explode('/', $_SERVER['REQUEST_URI']),
                    function ($val) {
                        return strlen((string) $val) > 0 ? true : false;
                    }
                )), PHP_URL_PATH);
                if (strpos($request_uri, $script_name) === 0) {
                    return $script_name;
                } else {
                    return strlen(dirname($script_name)) > 1 ? dirname($script_name) : '';
                }
            })();
        }
        return $web_root;
    }

    private function checkParams(array $route_params, array $build_params): bool
    {
        foreach ($route_params as $key => $value) {
            if (isset($build_params[$key]) && ($build_params[$key] != $value)) {
                return false;
            }
        }
        return true;
    }
}
