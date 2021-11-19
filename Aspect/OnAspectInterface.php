<?php

namespace Http\Aspect;

interface OnAspectInterface
{


	/**
	 * @param OnJoinPointInterface $joinPoint
	 * @return mixed
	 */
	public function process(OnJoinPointInterface $joinPoint): mixed;


}
