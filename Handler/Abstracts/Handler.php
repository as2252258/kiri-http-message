<?php

namespace Kiri\Message\Handler\Abstracts;


use Exception;
use Kiri;
use Kiri\Annotation\Inject;
use Kiri\Core\Help;
use  Kiri\Message\ContentType;
use Kiri\Message\Constrict\ResponseInterface as HttpResponseInterface;
use Kiri\Message\Handler\Handler as CHl;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;


abstract class Handler implements RequestHandlerInterface
{

	protected int $offset = 0;


	public array|\Closure $handler;


	public HttpResponseInterface $response;


	public array $middlewares = [];
	public array $params = [];


	/**
	 * @param array $middlewares
	 * @param array|\Closure $closure
	 * @param mixed $params
	 * @return $this
	 */
	public function with(array $middlewares, array|\Closure $closure, mixed $params): static
	{
		$this->offset = 0;
		$this->params = $params;
		$this->middlewares = $middlewares;
		$this->handler = $closure;
		if (count($middlewares) > 0) {
			$this->handler = function (ServerRequestInterface $request) {
				return $this->_execute($request);
			};
		}
		return $this;
	}


	/**
	 * @param $request
	 * @return ResponseInterface
	 */
	public function recover($request): ResponseInterface
	{
		$this->offset = 0;
		return $this->handle($request);
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function execute(ServerRequestInterface $request): ResponseInterface
	{
		$response = call_user_func($this->handler, ...$this->params);
		if ($response instanceof ResponseInterface) {
			return $response;
		}
		return $this->transferToResponse($response);
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function _execute(ServerRequestInterface $request): ResponseInterface
	{
		if (!isset($this->middlewares[$this->offset])) {
			return $this->dispatcher();
		}
		$middleware = $this->middlewares[$this->offset] ?? null;
		$this->offset++;
		if ($middleware instanceof MiddlewareInterface) {
			return $middleware->process($request, $this);
		} else {
			return $this->dispatcher();
		}
	}


	/**
	 * @return ResponseInterface
	 */
	public function dispatcher(): ResponseInterface
	{
		$response = call_user_func($this->handler, ...$this->params);
		if ($response instanceof ResponseInterface) {
			return $response;
		}
		return $this->transferToResponse($response);
	}


	/**
	 * @param mixed $responseData
	 * @return ResponseInterface
	 */
	private function transferToResponse(mixed $responseData): ResponseInterface
	{
		if (is_string($responseData)) {
			return $this->response->withStatus(200)->withContent($responseData);
		}
		if ($responseData instanceof Kiri\ToArray) {
			$responseData = $responseData->toArray();
		}
		return $this->response->withStatus(200)->withContent(Json::encode($responseData));
	}


}
