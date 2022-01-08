<?php

namespace Http\Handler;

use Closure;
use Http\Aspect\JoinPoint;
use Http\Aspect\OnAspectInterface;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Di\AnnotationManager;
use Kiri\Kiri;
use Kiri\Annotation\Aspect;
use ReflectionException;

class Handler
{


	public string $route = '';


	public array|Closure|null $callback;


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
			$this->middlewares = $middlewares;
		}
		if ($callback instanceof Closure || count($callback) == 1) {
			$this->callback = $callback;
			return;
		}
		$this->middlewares = MiddlewareManager::get($callback);

		$aspect = AnnotationManager::getSpecify_annotation(Aspect::class, $callback[0], $callback[1]);
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
	public function recover(Aspect $aspect, $callback)
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
			return $container->getMethodParameters($callback[0], $callback[1]);
		} else {
			return $container->getFunctionParameters($callback);
		}
	}
}
