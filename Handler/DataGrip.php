<?php

namespace Http\Handler;

use Kiri\Kiri;

class DataGrip
{

	private array $servers = [];


	/**
	 * @param $type
	 * @return RouterCollector
	 */
	public function get($type): RouterCollector
	{
		if (!isset($this->servers[$type])) {
			$this->servers[$type] = Kiri::getDi()->create(RouterCollector::class);
		}
		return $this->servers[$type];
	}


}
