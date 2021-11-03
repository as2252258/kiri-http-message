<?php

namespace Http\Abstracts;

use Annotation\Inject;
use Http\Emitter;
use Http\Constrict\Response as CResponse;


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
