<?php

namespace Kiri\Message\Aspect;



class TestAspect extends AbstractsAspect
{


	/**
	 * @param OnJoinPointInterface $joinPoint
	 * @return mixed
	 */
	public function process(OnJoinPointInterface $joinPoint): mixed
	{

		$result = $joinPoint->process();

		var_dump('111');

		return  $result;
	}

}
