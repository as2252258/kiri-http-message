<?php

namespace Kiri\Message\Abstracts;

use Kiri\Message\Constrict\Response;
use Throwable;
use Kiri\Message\Constrict\ResponseInterface;

/**
 *
 */
interface ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param Response $response
	 * @return ResponseInterface
	 */
	public function emit(Throwable $exception, Response $response): ResponseInterface;

}
