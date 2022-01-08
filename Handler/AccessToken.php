<?php

namespace Http\Handler;

use Kiri\Jwt\JWTAuthInterface;
use Kiri\Kiri;

trait AccessToken
{


	/**
	 * @return string
	 */
	public function getAccessToken(): string
	{
		$jwt = Kiri::getDi()->get(JWTAuthInterface::class);

		return $jwt->create($this->{$jwt->claim});
	}


}
