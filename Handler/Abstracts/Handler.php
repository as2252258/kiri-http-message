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


	public CHl $handler;


	#[Inject(HttpResponseInterface::class)]
	public HttpResponseInterface $response;
	
	
	public array $middlewares = [];


	/**
	 * @param CHl $handler
	 * @return $this
	 */
	public function with(CHl $handler): static
	{
		$this->offset = 0;
		$this->middlewares = $handler->middlewares;
		$this->handler = $handler;
		return $this;
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function execute(ServerRequestInterface $request): ResponseInterface
	{
		$middleware = array_pop($this->middlewares);
		if (empty($middleware)) {
			return $this->dispatcher($this->handler);
		}
		return $middleware->process($request, $this);
	}


	/**
	 * @param CHl $handler
	 * @return ResponseInterface
	 * @throws Kiri\Exception\ConfigException
	 */
	public function dispatcher(CHl $handler): ResponseInterface
	{
		$response = call_user_func($handler->callback, ...$handler->params);
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
