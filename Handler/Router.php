<?php

namespace Http\Handler;

use Closure;
use Exception;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Error\Logger;
use Kiri\Kiri;
use Kiri\Annotation\Inject;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use Throwable;


const ROUTER_DEFAULT_TYPE = 'http';

class Router
{


	/**
	 * @var array|string[]
	 */
	const METHODS = ['GET', 'POST', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];


	/**
	 * @var string
	 */
	private static string $type = ROUTER_DEFAULT_TYPE;


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
	 * @throws
	 */
	public static function post(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['POST'], $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function get(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['GET'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function options(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['OPTIONS'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function any(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(self::METHODS, $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function delete(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['DELETE'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function head(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['HEAD'], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function put(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute(['PUT'], $route, $handler);
	}


	/**
	 * @param array|string $methods
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function addRoute(array|string $methods, string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		if (is_string($methods)) {
			$methods = [$methods];
		}
		$router->addRoute($methods, $route, $handler);
	}



	/**
	 * @param array $config
	 * @param Closure $closure
	 * @throws
	 */
	public static function group(array $config, Closure $closure)
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);

		$router->groupTack[] = $config;

		call_user_func($closure);

		array_pop($router->groupTack);
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
