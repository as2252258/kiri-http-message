<?php

namespace Kiri\Message\Handler;

use Closure;
use Exception;
use Kiri;


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
	public function scan_build_route()
	{
		scan_directory(CONTROLLER_PATH, 'app\Controller');

		$this->read_dir_file(APP_PATH . 'routes');
	}


	/**
	 * @param $path
	 * @return void
	 * @throws Exception
	 */
	private function read_dir_file($path)
	{
		$files = glob($path . '/*');
		for ($i = 0; $i < count($files); $i++) {
			$file = $files[$i];
			if (is_dir($file)) {
				$this->read_dir_file($file);
			} else {
				$this->resolve_file($file);
			}
		}
	}


	/**
	 * @param $files
	 * @throws Exception
	 */
	private function resolve_file($files)
	{
		try {
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($files, true);
			}
			include_once "$files";
		} catch (\Throwable $throwable) {
			var_dump($throwable->getMessage());
		}
	}


}
