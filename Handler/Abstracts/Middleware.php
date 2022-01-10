<?php

namespace Kiri\Message\Handler\Abstracts;

use Kiri\Annotation\Inject;
use Psr\Http\Server\MiddlewareInterface;
use Kiri\Message\Constrict\ResponseInterface;


/**
 *
 */
abstract class Middleware implements MiddlewareInterface
{


	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;

}
