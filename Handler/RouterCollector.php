<?php

namespace Kiri\Message\Handler;


use Closure;
use Exception;
use Kiri\Message\Handler\Abstracts\MiddlewareManager;
use Kiri;
use Kiri\Annotation\Inject;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;
use Traversable;

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


	public array $groupTack = [];


	/**
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new \ArrayIterator($this->_item);
	}


	/**
	 * @param array $method
	 * @param string $route
	 * @param string|Closure|array $closure
	 * @throws
	 */
	public function addRoute(array $method, string $route, string|Closure|array $closure)
	{
		try {
			$route = $this->_splicing_routing($route);
			if ($closure instanceof Closure) {
				$middlewares = $this->loadMiddlewares($closure, $route);
			} else if (is_string($closure)) {
				$this->_route_analysis($closure);
			}
			foreach ($method as $value) {
				$this->_item[$route][$value] = new Handler($route, $closure, $middlewares ?? []);
			}
		} catch (Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [error_trigger_format($throwable)]);
		}
	}


	/**
	 * @param string|Closure $closure
	 */
	private function _route_analysis(string|Closure &$closure)
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
		foreach ($middleware as $value) {
			if (is_string($value)) {
				$value = [$value];
			}
			foreach ($value as $item) {
				MiddlewareManager::add($closure[0], $closure[1], $item);
			}
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
	 * @param string $path
	 * @param string $method
	 * @return Handler|int|null
	 */
	public function find(string $path, string $method): Handler|int|null
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
