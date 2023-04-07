<?php

namespace Kiri\Message\Handler;


use Closure;
use Exception;
use Kiri;
use Kiri\Annotation\Inject;
use Kiri\Exception\ConfigException;
use Kiri\Message\Handler\Abstracts\MiddlewareManager;
use Kiri\Message\Handler\TreeHelper\HashTree;
use Kiri\Message\Handler\TreeHelper\MethodDelete;
use Kiri\Message\Handler\TreeHelper\MethodGet;
use Kiri\Message\Handler\TreeHelper\MethodHead;
use Kiri\Message\Handler\TreeHelper\MethodOptions;
use Kiri\Message\Handler\TreeHelper\MethodPost;
use Kiri\Message\Handler\TreeHelper\TreeLeafInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;
use Traversable;
use Kiri\Annotation\Route\RequestMethod;

/**
 *
 */
class RouterCollector implements \ArrayAccess, \IteratorAggregate
{


	private array $_item = [];


	/**
	 * @var LoggerInterface
	 */
	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;


	private array $globalMiddlewares = [];


	public array $groupTack = [];


	/**
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new \ArrayIterator($this->_item);
	}


	/**
	 * @return array<MiddlewareInterface>
	 */
	public function getGlobalMiddlewares(): array
	{
		return $this->globalMiddlewares;
	}


	/**
	 * @param string $handler
	 * @return void
	 * @throws Exception
	 */
	public function addGlobalMiddlewares(string $handler): void
	{
		$handler = Kiri::getDi()->get($handler);
		if (!in_array(MiddlewareInterface::class, class_implements($handler))) {
			throw new Exception('The Middleware must instance ' . MiddlewareInterface::class);
		}
		$this->globalMiddlewares[] = $handler;
	}


	/**
	 * @param RequestMethod[] $method
	 * @param string $route
	 * @param string|Closure|array $closure
	 * @throws
	 */
	public function addRoute(array $method, string $route, string|Closure|array $closure)
	{
		try {
			$route = $this->_splicing_routing($route);
			$middlewares = Kiri\Abstracts\Config::get('request.middlewares', []);
			if ($closure instanceof Closure) {
				$middlewares = $this->_getRouteMiddlewares();;
			} else if (is_string($closure)) {
				$this->_route_analysis($closure);
			}
			foreach ($method as $value) {
				$this->register($route, $value->getString(), $closure, $middlewares);
			}
		} catch (Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [throwable($throwable)]);
		}
	}


	/**
	 * @param string $path
	 * @param string $method
	 * @param $closure
	 * @param $middlewares
	 * @return void
	 * @throws ReflectionException
	 */
	public function register(string $path, string $method, $closure, $middlewares): void
	{
		$end = $this->getRouterContainer($method);

		$json = str_split($path,4);

		$handler = new Handler($path, $closure, $middlewares);
		foreach ($json as $item) {
			/** @var TreeLeafInterface $leaf */
			$leaf = new ($end::class)($item);
			$leaf->setPath($item);
			if (!$end->hasLeaf()) {
				$end = $end->addLeaf($item, $leaf);
			} else {
				$search = $end->searchLeaf($item);
				if ($search == null) {
					$end = $end->addLeaf($item, $leaf);
				} else {
					$end = $search;
				}
			}
		}
		$end->setHandler($handler);
	}


	/**
	 * @param string $path
	 * @param string $method
	 * @return HashTree|null
	 * @throws ConfigException
	 * @throws ReflectionException
	 */
	public function query(string $path, string $method): ?Handler
	{
		$parent = $this->getRouterContainer($method);
		
		$string = str_split($path,4);
		foreach ($string as $item) {
			$parent = $parent->searchLeaf($item);
			if ($parent === null) {
				return $this->NotFundHandler($path);
			}
		}
		return $parent->getHandler();
	}


	/**
	 * @param string $path
	 * @return Handler
	 * @throws ReflectionException
	 */
	private function NotFundHandler(string $path): Handler
	{
		$middlewares = Kiri\Abstracts\Config::get('request.middlewares', []);

		return new Handler($path, [NotFoundController::class, 'fail'], $middlewares);
	}


	/**
	 * @param string $method
	 * @return TreeLeafInterface
	 */
	public function getRouterContainer(string $method): TreeLeafInterface
	{
		$methods = [
			RequestMethod::REQUEST_DELETE->getString()  => MethodDelete::class,
			RequestMethod::REQUEST_PUT->getString()     => MethodGet::class,
			RequestMethod::REQUEST_POST->getString()    => MethodPost::class,
			RequestMethod::REQUEST_OPTIONS->getString() => MethodOptions::class,
			RequestMethod::REQUEST_GET->getString()     => MethodGet::class,
			RequestMethod::REQUEST_HEAD->getString()    => MethodHead::class,
		];
		return Kiri::getDi()->get($methods[$method]);
	}


	/**
	 * @param string $closure
	 * @throws Exception
	 */
	private function _route_analysis(string &$closure)
	{
		$closure = explode('@', $closure);
		$closure[0] = $this->addNamespace($closure[0]);
		if (class_exists($closure[0])) {
			$this->_registerMiddleware($closure[0], $closure[1]);
		}
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function _getRouteMiddlewares(): array
	{
		$middleware = array_column($this->groupTack, 'middleware');
		if (count($middleware = array_filter($middleware)) > 0) {
			foreach ($middleware as $value) {
				if (is_string($value)) {
					$value = [$value];
				}
				foreach ($value as $item) {
					if (!in_array(MiddlewareInterface::class, class_implements($item, true))) {
						throw new Exception("middleware handler must instance MiddlewareInterface::class");
					}
					$middleware[] = $item;
				}
			}
		}
		return $middleware;
	}


	/**
	 * @param string $class
	 * @param string $method
	 * @return void
	 * @throws Exception
	 */
	private function _registerMiddleware(string $class, string $method): void
	{
		$middleware = $this->_getRouteMiddlewares();
		foreach ($middleware as $value) {
			MiddlewareManager::add($class, $method, $value);
		}
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
		if (!empty($close->getClosureThis()) && env('environmental_workerId') == 0) {
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
					if (!in_array(MiddlewareInterface::class, class_implements($item))) {
						throw new Exception('The Middleware must instance ' . MiddlewareInterface::class);
					}
					$middlewares[$item] = $item;
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
	 * @param string $path
	 * @param string $method
	 * @return Handler|int|null
	 * @throws ReflectionException|Kiri\Exception\ConfigException
	 */
	public function find(string $path, string $method): Handler|int|null
	{
		$dispatcher = match ($method) {
			'OPTIONS' => $this->options($path, $method),
			default => $this->other($path, $method)
		};
		if (is_null($dispatcher)) {
			$middlewares = Kiri\Abstracts\Config::get('request.middlewares', []);

			$dispatcher = new Handler($path, [NotFoundController::class, 'fail'], $middlewares);
		} else if (is_integer($dispatcher)) {
			$middlewares = Kiri\Abstracts\Config::get('request.middlewares', []);

			$dispatcher = new Handler($path, [MethodErrorController::class, 'fail'], $middlewares);
		}
		return $dispatcher;
	}


	/**
	 * @param $path
	 * @param $method
	 * @return int|mixed
	 */
	public function other($path, $method): mixed
	{
		if (!isset($this->_item[$path])) {
			return 404;
		}
		$handler = $this->_item[$path][$method] ?? null;
		if (is_null($handler)) {
			return 405;
		}
		return $handler;
	}


	/**
	 * @param $path
	 * @param $method
	 * @return int|mixed
	 */
	public function options($path, $method): mixed
	{
		$handler = $this->_item[$path][$method] ?? null;
		if (is_null($handler)) {
			return $this->_item['/*'][$method] ?? 405;
		}
		return $handler;
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool
	{
		// TODO: Implement offsetExists() method.
		return isset($this->_item[$offset]);
	}


	/**
	 * @param mixed $offset
	 * @return Router|null
	 */
	public function offsetGet(mixed $offset): ?Router
	{
		if ($this->offsetExists($offset)) {
			return $this->_item[$offset];
		}
		return null;
	}


	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		// TODO: Implement offsetSet() method.
		$this->_item[$offset] = $value;
	}


	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->_item[$offset]);
	}
}
