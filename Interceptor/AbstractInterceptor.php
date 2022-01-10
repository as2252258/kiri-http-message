<?php

namespace Kiri\Message\Interceptor;



use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Annotation\Inject;

abstract class AbstractInterceptor implements InterceptorInterface
{


	/**
	 * @var ResponseInterface
	 */
	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;



}
