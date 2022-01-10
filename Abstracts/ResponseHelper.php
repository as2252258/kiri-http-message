<?php

namespace Kiri\Message\Abstracts;

use Kiri\Annotation\Inject;
use Kiri\Message\Emitter;
use Kiri\Message\Constrict\Response as CResponse;


/**
 *
 */
trait ResponseHelper
{

	/** @var CResponse|mixed */
	#[Inject(CResponse::class)]
	public CResponse $response;


	public Emitter $responseEmitter;


}
