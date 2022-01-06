<?php

namespace Http\Interceptor;



use Http\Constrict\ResponseInterface;
use Note\Inject;

abstract class AbstractInterceptor implements InterceptorInterface
{


	/**
	 * @var ResponseInterface
	 */
	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;



}
