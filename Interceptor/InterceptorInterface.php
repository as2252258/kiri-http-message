<?php

namespace Http\Interceptor;


use Http\Constrict\RequestInterface;
use Http\Handler\AuthIdentity;


/**
 *
 */
interface InterceptorInterface
{


	/**
	 * @param RequestInterface $request
	 * @return void
	 */
	public function process(RequestInterface $request): void;


}
