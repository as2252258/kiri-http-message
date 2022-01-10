<?php

namespace Kiri\Message\Interceptor;


use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Handler\AuthorizationInterface;


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
