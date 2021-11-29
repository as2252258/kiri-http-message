<?php

namespace Http\Handler;

use Annotation\Inject;
use Closure;
use Exception;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Error\Logger;
use Kiri\Kiri;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use Throwable;


const ROUTER_DEFAULT_TYPE = 'http';

class Router
{


	protected array $groupTack = [];


	/**
	 * @var array|string[]
	 */
	protected array $methods = ['GET', 'POST', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];


	/**
	 * @var string
	 */
	private static string $type = ROUTER_DEFAULT_TYPE;


	/**
	 * @var array
	 */
	private array $handlers = [];


	#[Inject(Logger::class)]
	public Logger $logger;


	/**
	 * @param string $name
	 * @param Closure $closure
	 */
	public static function addServer(string $name, Closure $closure)
	{
		static::$type = $name;
		$closure();
		static::$type = ROUTER_DEFAULT_TYPE;
	}


	/**
	 * @param Closure $handler
	 */
	public static function jsonp(Closure $handler)
	{
		static::$type = 'json-rpc';
		$handler();
		static::$type = ROUTER_DEFAULT_TYPE;
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function post(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('POST', $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function get(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('GET', $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function options(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('OPTIONS', $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function any(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute($router->methods, $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function delete(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('DELETE', $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function head(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('HEAD', $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function put(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute('PUT', $route, $handler);
	}


	/**
	 * @param array $methods
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function addRoute(array $methods, string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute($methods, $route, $handler);
	}


	/**
	 * @param string|array $method
	 * @param string $route
	 * @param string|Closure $closure
	 * @throws
	 */
	private function _addRoute(string|array $method, string $route, string|Closure $closure)
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
				$middlewares = [];
				if ($closure instanceof Closure) {
					$middleware = array_column($this->groupTack, 'middleware');
					$middleware = array_unique($middleware);
					if (!empty($middleware = array_filter($middleware))) {
						foreach ($middleware as $mi) {
							$mi = Kiri::getDi()->get($mi);
							if (!($mi instanceof MiddlewareInterface)) {
								throw new Exception();
							}
							$middlewares[] = [$mi, 'process'];
						}
					}
				}
				$this->handlers[$route][$value] = new Handler($route, $closure, $middlewares);
			}
		} catch (Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [
				'file' => $throwable->getFile(),
				'line' => $throwable->getLine(),
			]);
		}
	}


	/**
	 * @param string $path
	 * @param string $method
	 * @return Handler|int|null
	 */
	public function find(string $path, string $method): Handler|int|null
	{
		if (!isset($this->handlers[$path])) {
			return 404;
		}
		$handler = $this->handlers[$path][$method] ?? null;
		if (is_null($handler)) {
			return 405;
		}
		return $handler;
	}


	/**
	 * @param array $config
	 * @param Closure $closure
	 * @throws ReflectionException
	 */
	public static function group(array $config, Closure $closure)
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);

		array_push($router->groupTack, $config);

		call_user_func($closure);

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
