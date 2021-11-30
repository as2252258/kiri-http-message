<?php

namespace Http\Handler\Abstracts;

use Note\Inject;
use Psr\Http\Server\MiddlewareInterface;
use Http\Constrict\ResponseInterface;


/**
 *
 */
abstract class Middleware implements MiddlewareInterface
{


	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;

}
