<?php

namespace Kiri\Message\Handler;

use Kiri\Message\Constrict\ResponseInterface;

class MethodErrorController extends Controller
{


	/**
	 * @return ResponseInterface
	 */
	public function fail(): ResponseInterface
	{
		return $this->response->failure(405, "method allow.");
	}
}
