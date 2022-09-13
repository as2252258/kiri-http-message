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
		$this->response = Kiri::getDi()->get(HttpResponseInterface::class);
		$this->params = $params;
		$this->middlewares = $middlewares;
		$this->handler = $closure;
		return $this;
	}
	
	
	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function execute(ServerRequestInterface $request): ResponseInterface
	{
		if (empty($this->middlewares)) {
			return $this->dispatcher();
		}
		$middleware = $this->middlewares[$this->offset] ?? null;
		if (is_null($middleware)) {
			return $this->dispatcher();
		} else {
			return $middleware->process($request, $this);
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
		$interface = $this->response->withStatus(200);
		if ($responseData instanceof Kiri\ToArray) {
			$responseData = $responseData->toArray();
		}
		if (is_array($responseData)) {
			return $interface->withContent(Json::encode($responseData));
		}
		return $interface->withContent((string)$responseData);
	}
	
	
}
