<?php

namespace Kiri\Message\Handler;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Kiri\Message\Handler\Abstracts\Handler;

/**
 *
 */
class Dispatcher extends Handler
{


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->execute($request);
	}
}
