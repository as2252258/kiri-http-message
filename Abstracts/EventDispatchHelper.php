<?php

namespace Http\Abstracts;

use Note\Inject;
use Kiri\Events\EventDispatch;

trait EventDispatchHelper
{

	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


}
