<?php

namespace Kiri\Message\Handler;

use Closure;
use Kiri;
use Kiri\Annotation\Aspect;
use Kiri\Di\TargetManager;
use Kiri\Message\Aspect\JoinPoint;
use Kiri\Message\Aspect\OnAspectInterface;
use Kiri\Message\Handler\Abstracts\MiddlewareManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;

class Handler
{
	
	
	public string $route = '';
	
	
	public array|Closure|null $callback;
	
	public array|Closure|null $dispatch;
	
	
	public ?array $params = [];
	
	
	public ?array $middlewares = [];
	
	
	/**
	 * @param string $route
	 * @param array|Closure $callback
	 * @param array $middlewares
	 * @throws ReflectionException
	 */
	public function __construct(string $route, array|Closure $callback, array $middlewares = [])
	{
		$this->route = $route;
		$this->params = $this->_injectParams($callback);
		if (!empty($middlewares)) {
			$this->middlewares = $this->middlewareInstance($middlewares);
		}
		if ($callback instanceof Closure || !is_callable($callback, true)) {
			$this->callback = $callback;
		} else {
			$this->middlewares = $this->middlewareInstance(MiddlewareManager::get($callback));
			$this->setAspect($callback);
		}
	}
	
	
	/**
	 * @param array|null $middlewares
	 * @return array
	 */
	private function middlewareInstance(?array $middlewares): array
	{
		$data = [];
		if (is_null($middlewares)) {
			return [];
		}
		foreach ($middlewares as $middleware) {
			$middleware = Kiri::getDi()->get($middleware);
			if (!($middleware instanceof MiddlewareInterface)) {
				continue;
			}
			$data[] = $middleware;
		}
		return array_reverse($data);
	}
	
	
	/**
	 * @param $callback
	 * @return void
	 * @throws ReflectionException
	 */
	private function setAspect($callback): void
	{
		$aspect = TargetManager::get($callback[0])->searchNote($callback[1], Aspect::class);
		if (!empty($aspect) && is_array($aspect)) {
			$aspect = current($aspect)->newInstance();
		}
		$callback[0] = Kiri::getDi()->get($callback[0]);
		if (!is_null($aspect)) {
			$this->recover($aspect, $callback);
		} else {
			$this->callback = $callback;
		}
	}
	
	
	/**
	 * @param Aspect $aspect
	 * @param $callback
	 */
	public function recover(Aspect $aspect, $callback): void
	{
		$aspect = Kiri::getDi()->get($aspect->aspect);
		if ($aspect instanceof OnAspectInterface) {
			$this->params = [new JoinPoint($callback, $this->params)];
			$this->callback = [$aspect, 'process'];
		}
	}
	
	
	/**
	 * @param array|Closure $callback
	 * @return array|null
	 * @throws ReflectionException
	 */
	private function _injectParams(array|Closure $callback): ?array
	{
		$container = Kiri::getDi();
		if (!($callback instanceof Closure)) {
			if (!isset($callback[1])) {
				return [];
			}
			return $container->getArgs($callback[1], $callback[0]);
		} else {
			return $container->getArgs($callback);
		}
	}
}
