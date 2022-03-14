<?php

declare(strict_types=1);

namespace DigPHP\Router;

class Builder
{
    protected $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function build(string $name, array $params = [], string $methods = 'GET'): string
    {
        list($staticRouteMap, $variableRouteData) = $this->generator->getData();
        $methods = explode('|', strtoupper($methods));

        foreach ($staticRouteMap as $_method => $routes) {
            if ($_method != '*' && !in_array($_method, $methods)) {
                continue;
            }
            foreach ($routes as $route) {
                if ($route['name'] != $name) {
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
                        $uri .= urlencode((string)$querys[$part[0]]);
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

        foreach ($variableRouteData as $_method => $chunks) {
            if ($_method != '*' && !in_array($_method, $methods)) {
                continue;
            }
            foreach ($chunks as $chunk) {
                foreach ($chunk['routeMap'] as $route) {
                    if ($route['name'] != $name) {
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
        }

        return $this->getSiteRoot() . $name . ($params ? '?' . http_build_query($params) : '');
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

    private function getSiteRoot(): string
    {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $port = null;
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = 'http://' . $_SERVER['HTTP_HOST'];
            $parts = parse_url($uri);
            if (false !== $parts) {
                $host = isset($parts['host']) ? $parts['host'] : null;
                $port = isset($parts['port']) ? $parts['port'] : null;
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'];
        }

        if (is_null($port) && isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        }

        $site_base = $scheme . '://' . $host . (in_array($port, [null, 80, 443]) ? '' : ':' . $port);
        if (strpos($_SERVER['REQUEST_URI'] ?? '', $_SERVER['SCRIPT_NAME']) === 0) {
            $site_path = $_SERVER['SCRIPT_NAME'];
        } else {
            $dir_script = dirname($_SERVER['SCRIPT_NAME']);
            $site_path = strlen($dir_script) > 1 ? $dir_script : '';
        }
        return $site_base . $site_path;
    }
}
