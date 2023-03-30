<?php

namespace Kiri\Message\Handler;

use Closure;
use Exception;
use Kiri;
use Kiri\Annotation\Route\RequestMethod;


const ROUTER_DEFAULT_TYPE = 'http';

class Router
{


	/**
	 * @var array|string[]
	 */
	const METHODS = [
		RequestMethod::REQUEST_GET,
		RequestMethod::REQUEST_POST,
		RequestMethod::REQUEST_HEAD,
		RequestMethod::REQUEST_OPTIONS,
		RequestMethod::REQUEST_PUT,
		RequestMethod::REQUEST_DELETE
	];


	/**
	 * @var string
	 */
	private static string $type = ROUTER_DEFAULT_TYPE;


	/**
	 * @param string $name
	 * @param Closure $closure
	 */
	public static function addServer(string $name, Closure $closure): void
	{
		static::$type = $name;
		$closure();
		static::$type = ROUTER_DEFAULT_TYPE;
	}


	/**
	 * @param Closure $handler
	 */
	public static function jsonp(Closure $handler): void
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
		$router->addRoute([RequestMethod::REQUEST_POST], $route, $handler);
	}

	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function get(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute([RequestMethod::REQUEST_GET], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function options(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute([RequestMethod::REQUEST_OPTIONS], $route, $handler);
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
		$router->addRoute([RequestMethod::REQUEST_DELETE], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 */
	public static function head(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute([RequestMethod::REQUEST_HEAD], $route, $handler);
	}


	/**
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function put(string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		$router->addRoute([RequestMethod::REQUEST_PUT], $route, $handler);
	}


	/**
	 * @param array|RequestMethod $methods
	 * @param string $route
	 * @param string|Closure $handler
	 * @throws
	 */
	public static function addRoute(array|RequestMethod $methods, string $route, string|Closure $handler): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);
		if ($methods instanceof RequestMethod) {
			$methods = [$methods];
		}
		$router->addRoute($methods, $route, $handler);
	}


	/**
	 * @param array $config
	 * @param Closure $closure
	 * @throws
	 */
	public static function group(array $config, Closure $closure): void
	{
		$router = Kiri::getDi()->get(DataGrip::class)->get(static::$type);

		$router->groupTack[] = $config;

		call_user_func($closure);

		array_pop($router->groupTack);
	}


	/**
	 * @throws Exception
	 */
	public function scan_build_route(): void
	{
		scan_directory(APP_PATH, 'App');

		$this->read_dir_file(APP_PATH . 'routes');
	}


	/**
	 * @param $path
	 * @return void
	 * @throws Exception
	 */
	private function read_dir_file($path): void
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
	private function resolve_file($files): void
	{
		try {
			include "$files";
		} catch (\Throwable $throwable) {
			var_dump($throwable->getMessage());
		}
	}


}
