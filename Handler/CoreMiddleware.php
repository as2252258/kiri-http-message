<?php

namespace Kiri\Message\Handler;

use Exception;
use Kiri\Message\Handler\Abstracts\Middleware;
use Kiri\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 *
 */
class CoreMiddleware extends Middleware
{


	/**
	 * @param ServerRequest $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$requestMethod = $request->getAccessControlRequestMethod();
		$allowHeaders = $request->getAccessControlAllowHeaders();

		if (empty($requestMethod)) $requestMethod = '*';
		if (empty($allowHeaders)) $allowHeaders = '*';

		$this->response->withAccessControlAllowOrigin('*')->withAccessControlRequestMethod($requestMethod)
			->withAccessControlAllowHeaders($allowHeaders);

		var_dump($request->getUri()->getPath());

		return $handler->handle($request);
	}

}
