<?php

namespace Http\Handler;

use Annotation\Aspect;
use Closure;
use Http\Aspect\JoinPoint;
use Http\Aspect\OnAspectInterface;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Di\NoteManager;
use Kiri\Kiri;

class Handler
{


	public string $route = '';


	public array|Closure|null $callback;


	public ?array $params = [];


	public ?array $_middlewares = [];


	/**
	 * @param string $route
	 * @param array|Closure $callback
	 * @throws \ReflectionException
	 */
	public function __construct(string $route, array|Closure $callback)
	{
		$this->route = $route;
		$this->_injectParams($callback);

		$this->_middlewares = MiddlewareManager::get($callback);
		$aspect = NoteManager::getSpecify_annotation(Aspect::class, $callback[0], $callback[1]);

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
	 * @throws \ReflectionException
	 */
	private function _injectParams(array|Closure $callback)
	{
		$container = Kiri::getDi();
		if (!($callback instanceof Closure)) {
			$this->params = $container->getMethodParameters($callback[0], $callback[1]);
		} else {
			$this->params = $container->getFunctionParameters($callback);
		}
	}
}
