<?php

namespace Http\Handler;

use Closure;
use Exception;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Error\Logger;
use Kiri\Kiri;
use Note\Inject;
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
	const METHODS = ['GET', 'POST', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];


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
		$router->_addRoute(['POST'], $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function get(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(['GET'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function options(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(['OPTIONS'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function any(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(self::METHODS, $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function delete(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(['DELETE'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function head(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(['HEAD'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function put(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->_addRoute(['PUT'], $route, $handler);
	}


	/**
	 * @param array|string $methods
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws ReflectionException
	 */
	public static function addRoute(array|string $methods, string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		if (is_string($methods)) {
			$methods = [$methods];
		}
		$router->_addRoute($methods, $route, $handler);
	}


	/**
	 * @param array $method
	 * @param string $route
	 * @param string|Closure $closure
	 * @throws
	 */
	private function _addRoute(array $method, string $route, string|Closure $closure)
	{
		try {
			$route = $this->_splicing_routing($route);
			if ($closure instanceof Closure) {
				$middlewares = $this->loadMiddlewares($closure, $route);
			} else if (is_string($closure)) {
				$this->_route_analysis($closure);
			}
			foreach ($method as $value) {
				$this->handlers[$route][$value] = new Handler($route, $closure, $middlewares ?? []);
			}
		} catch (Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [
				'file' => $throwable->getFile(),
				'line' => $throwable->getLine(),
			]);
		}
	}


	/**
	 * @param string|Closure $closure
	 * @throws ReflectionException
	 */
	private function _route_analysis(string|Closure $closure)
	{
		$closure = explode('@', $closure);
		$closure[0] = $this->addNamespace($closure[0]);
		if (!class_exists($closure[0])) {
			return;
		}
		$middleware = array_column($this->groupTack, 'middleware');
		if (empty($middleware = array_filter($middleware))) {
			return;
		}
		MiddlewareManager::add($closure[0], $closure[1], $middleware);
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
	protected function _splicing_routing(string $route): string
	{
		$route = ltrim($route, '/');
		$prefix = array_column($this->groupTack, 'prefix');
		if (empty($prefix = array_filter($prefix))) {
			return '/' . $route;
		}
		return '/' . implode('/', $prefix) . '/' . $route;
	}


	/**
	 * @param $closure
	 * @param $route
	 * @return array
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function loadMiddlewares($closure, $route): array
	{
		$middlewares = [];
		$close = new \ReflectionFunction($closure);
		if (!empty($close->getClosureThis())) {
			$this->logger->warning('[' . $route . '] Static functions are recommended as callback functions.');
		}
		$middleware = array_column($this->groupTack, 'middleware');
		$middleware = array_unique($middleware);
		if (!empty($middleware = array_filter($middleware))) {
			foreach ($middleware as $mi) {
				if (!is_array($mi)) {
					$mi = [$mi];
				}
				foreach ($mi as $item) {
					$item = Kiri::getDi()->get($item);
					if (!($item instanceof MiddlewareInterface)) {
						throw new Exception('The Middleware must instance ' . MiddlewareInterface::class);
					}
					$middlewares[$item::class] = $item;
				}
			}
			$middlewares = array_values($middlewares);
		}
		return $middlewares;
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
