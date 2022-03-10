<?php

declare(strict_types=1);

namespace DigPHP\Router;

use LogicException;

class Generator
{
    protected $staticRoutes = [];
    protected $methodToRegexToRoutesMap = [];

    public function addRoute(
        string $httpMethod,
        array $routeData,
        $handler,
        array $middlewares = [],
        array $params = [],
        string $name = null
    ) {
        ksort($params);
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $middlewares, $params, $name);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $middlewares, $params, $name);
        }
    }

    public function getData(): array
    {
        if (empty($this->methodToRegexToRoutesMap)) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    protected function getApproxChunkSize(): int
    {
        return 10;
    }

    protected function processChunk(array $regexToRoutesMap): array
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $route) {
            $numVariables = count($route['variables']);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);
            $routeMap[$numGroups + 1] = $route;

            ++$numGroups;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return [
            'regex' => $regex,
            'routeMap' => $routeMap,
        ];
    }

    private function generateVariableRouteData(): array
    {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks);
        }
        return $data;
    }

    private function computeChunkSize(int $count): int
    {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));
        return (int) ceil($count / $numParts);
    }

    private function isStaticRoute(array $routeData): bool
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    private function addStaticRoute(
        string $httpMethod,
        array $routeData,
        $handler,
        array $middlewares = [],
        array $params = [],
        string $name = null
    ) {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            return;
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if (preg_match('~^' . $route['regex'] . '$~', $routeStr)) {
                    throw new LogicException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                        $routeStr,
                        $route['regex'],
                        $httpMethod
                    ));
                }
            }
        }

        $this->staticRoutes[$httpMethod][$routeStr] = [
            'name' => $name,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'params' => $params,
            'routeStr' => $routeStr,
            'routeData' => $routeData,
        ];
    }

    private function addVariableRoute(
        string $httpMethod,
        array $routeData,
        $handler,
        array $middlewares = [],
        array $params = [],
        string $name = null
    ) {
        list($regex, $variables) = $this->buildRegexForRoute($routeData);

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            return;
        }

        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = [
            'name' => $name,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'params' => $params,
            'regex' => $regex,
            'routeData' => $routeData,
            'variables' => $variables,
        ];
    }

    private function buildRegexForRoute(array $routeData): array
    {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart) = $part;

            if (isset($variables[$varName])) {
                throw new LogicException(sprintf(
                    'Cannot use the same placeholder "%s" twice',
                    $varName
                ));
            }

            if ($this->regexHasCapturingGroups($regexPart)) {
                throw new LogicException(sprintf(
                    'Regex "%s" for parameter "%s" contains a capturing group',
                    $regexPart,
                    $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return [$regex, $variables];
    }

    private function regexHasCapturingGroups(string $regex): bool
    {
        if (false === strpos($regex, '(')) {
            return false;
        }

        return (bool) preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }
}
