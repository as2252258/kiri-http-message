<?php

namespace Kiri\Message\Handler;

use Kiri\Jwt\JWTAuthInterface;
use Kiri;

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
