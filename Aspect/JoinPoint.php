<?php

namespace Http\Aspect;


/**
 * 
 */
class JoinPoint implements OnJoinPointInterface
{


	/**
	 * @param array|\Closure $handler
	 * @param mixed $params
	 */
	public function __construct(public array|\Closure $handler, public array $params)
	{
	}


	/**
	 * @return mixed
	 */
	public function process(): mixed
	{
		return call_user_func($this->handler, ...$this->params);
	}

}
