<?php

namespace Http\Handler;

use Annotation\Inject;
use Closure;
use Exception;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Error\Logger;
use Kiri\Kiri;
use ReflectionException;
use Throwable;

class Router
{


	protected array $groupTack = [];


	protected array $methods = ['GET', 'POST', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];


	#[Inject(Logger::class)]
	public Logger $logger;


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function socket($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('SOCKET', $route, $handler);
	}


	/**
	 * @param $service
	 * @param Closure $callback
	 * @param string $version
	 */
	public static function addService($service, Closure $callback, string $version = '2.0')
	{
		$default = ['prefix' => '.rpc/' . $service . '/' . $version];
		static::group($default, $callback);
	}


	/**
	 * @param $method
	 * @param $handler
	 * @throws ReflectionException
	 */
	public static function jsonp($method, $handler)
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('json-rpc', $method, $handler);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function post($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('POST', $route, $handler);
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function get($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('GET', $route, $handler);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function options($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('OPTIONS', $route, $handler);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @throws
	 */
	public static function any($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		foreach ($router->methods as $method) {
			$router->addRoute($method, $route, $handler);
		}
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function delete($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('DELETE', $route, $handler);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws Exception
	 */
	public static function head($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('HEAD', $route, $handler);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function put($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute('PUT', $route, $handler);
	}


	/**
	 * @param string|array $method
	 * @param string $route
	 * @param string|Closure $closure
	 * @throws
	 */
	public function addRoute(string|array $method, string $route, string|Closure $closure)
	{
		try {
			if (!is_array($method)) $method = [$method];
			$route = $this->getPath($route);
			if (is_string($closure)) {
				$closure = explode('@', $closure);
				$closure[0] = $this->addNamespace($closure[0]);
				if (!class_exists($closure[0])) {
					return;
				}
				$this->addMiddlewares(...$closure);
			}
			foreach ($method as $value) {
				HandlerManager::add($route, $value, new Handler($route, $closure));
			}
		} catch (Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [
				'file' => $throwable->getFile(),
				'line' => $throwable->getLine(),
			]);
		}
	}


	/**
	 * @param array $config
	 * @param Closure $closure
	 * @throws ReflectionException
	 */
	public static function group(array $config, Closure $closure)
	{
		$router = Kiri::getDi()->get(Router::class);

		array_push($router->groupTack, $config);

		call_user_func($closure, $router);

		array_pop($router->groupTack);
	}


	/**
	 * @param string $route
	 * @return string
	 */
	protected function getPath(string $route): string
	{
		$route = ltrim($route, '/');
		$prefix = array_column($this->groupTack, 'prefix');
		if (empty($prefix = array_filter($prefix))) {
			return '/' . $route;
		}
		return '/' . implode('/', $prefix) . '/' . $route;
	}


	/**
	 * @param $controller
	 * @param $method
	 * @throws ReflectionException
	 */
	protected function addMiddlewares($controller, $method)
	{
		$middleware = array_column($this->groupTack, 'middleware');
		if (empty($middleware = array_filter($middleware))) {
			return;
		}
		foreach ($middleware as $value) {
			MiddlewareManager::add($controller, $method, $value);
		}
	}


	/**
	 * @param $class
	 * @return string|null
	 */
	protected function addNamespace($class): ?string
	{
		$middleware = array_column($this->groupTack, 'namespace');
		if (empty($middleware = array_filter($middleware))) {
			return $class;
		}
		$middleware[] = $class;
		return implode('\\', array_map(function ($value) {
			return trim($value, '\\');
		}, $middleware));
	}


	/**
	 * @throws Exception
	 */
	public function read_files()
	{
		$this->loadRouteDir(APP_PATH . 'routes');
	}


	/**
	 * @param $path
	 * @throws Exception
	 * 加载目录下的路由文件
	 */
	private function loadRouteDir($path)
	{
		$files = glob($path . '/*');
		for ($i = 0; $i < count($files); $i++) {
			$this->_load($files[$i]);
		}
	}


	/**
	 * @param $files
	 * @throws Exception
	 */
	private function _load($files): void
	{
		if (!is_dir($files)) {
			$this->loadRouterFile($files);
		} else {
			$this->loadRouteDir($files);
		}
	}


	/**
	 * @param $files
	 * @throws Exception
	 */
	private function loadRouterFile($files)
	{
		include_once "$files";
	}


}
