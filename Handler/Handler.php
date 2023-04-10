<?php

namespace Kiri\Message\Handler;

use Closure;
use Kiri;
use Kiri\Annotation\Aspect;
use Kiri\Di\TargetManager;
use Kiri\Message\Aspect\JoinPoint;
use Kiri\Message\Aspect\OnAspectInterface;
use Kiri\Message\Constrict\ResponseInterface as HttpResponseInterface;
use Kiri\Message\Handler\Abstracts\MiddlewareManager;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;

class Handler
{

	public Dispatcher $dispatch;

	public array $params;


	/**
	 * @param string $route
	 * @param array|Closure $callback
	 * @param array $middlewares
	 * @throws ReflectionException
	 */
	public function __construct(public string $route, array|Closure $callback, array $middlewares = [])
	{
		$this->params = $this->_injectParams($callback);
		if (is_array($callback) && is_callable($callback, true)) {
			$middlewares = array_values(array_unique([...$middlewares, ...$this->_manager($callback)], SORT_REGULAR));
			$callback = $this->setAspect($callback);
		}
		$this->dispatch = new Dispatcher();
		$this->dispatch->response = Kiri::getDi()->get(HttpResponseInterface::class);
		$this->dispatch->with($this->resetMiddlewares($middlewares), $callback, $this->params);
		$this->params = [];
	}


	/**
	 * @param array $middlewares
	 * @return array
	 */
	private function resetMiddlewares(array $middlewares): array
	{
//		foreach ($middlewares as $key => $middleware) {
//			$middlewares[$key] = di($middleware);
//		}
		return $middlewares;
	}


	/**
	 * @param $callback
	 * @return array
	 */
	private function _manager($callback): array
	{
		$lists = MiddlewareManager::get($callback);
		if (empty($lists)) {
			return [];
		}
		return $lists;
	}


	/**
	 * @param $callback
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function setAspect($callback): mixed
	{
		$aspect = TargetManager::get($callback[0])->searchNote($callback[1], Aspect::class);
		if (!empty($aspect) && is_array($aspect)) {
			$aspect = current($aspect)->newInstance();
		}
		$callback[0] = Kiri::getDi()->get($callback[0]);
		if (!is_null($aspect)) {
			return $this->recover($aspect, $callback);
		} else {
			return $callback;
		}
	}


	/**
	 * @param Aspect $aspect
	 * @param $callback
	 * @return mixed
	 */
	public function recover(Aspect $aspect, $callback): mixed
	{
		$aspect = Kiri::getDi()->get($aspect->aspect);
		if ($aspect instanceof OnAspectInterface) {
			$this->params = [new JoinPoint($callback, $this->params)];
			$callback = [$aspect, 'process'];
		}
		return $callback;
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
