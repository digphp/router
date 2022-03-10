<?php

declare(strict_types=1);

namespace DigPHP\Router;

class Route
{
    private $found = false;
    private $allowed = false;
    private $handler = null;
    private $middlewares = [];
    private $params = [];

    public function __construct(
        bool $found,
        bool $allowed = false,
        $handler = null,
        array $middlewares = [],
        array $params = []
    ) {
        $this->found = $found;
        $this->allowed = $allowed;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
        $this->params = $params;
    }

    public function setFound(bool $found): self
    {
        $this->found = $found;
        return $this;
    }

    public function setAllowed(bool $allowed): self
    {
        $this->allowed = $allowed;
        return $this;
    }

    public function setHandler($handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function setMiddlewares(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function isFound(): bool
    {
        return $this->found;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getMiddleWares(): array
    {
        return $this->middlewares;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
