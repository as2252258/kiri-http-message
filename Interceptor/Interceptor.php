<?php

namespace Kiri\Message\Interceptor;


use Kiri\Events\EventProvider;
use Kiri;
use Kiri\Annotation\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor extends Attribute
{


	/**
	 * @param string|InterceptorInterface|array<InterceptorInterface> $interceptor
	 */
	public function __construct(public string|array $interceptor)
	{
		if (is_string($this->interceptor)) {
			$this->interceptor = [$this->interceptor];
		}
	}


	/**
	 * @param mixed $class
	 * @param mixed $method
	 * @return mixed
	 */
	public function execute(mixed $class, mixed $method = ''): mixed
	{
		return true;
	}


}
