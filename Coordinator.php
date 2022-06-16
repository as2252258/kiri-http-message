<?php

namespace Kiri\Message;

class Coordinator
{

	const WORKER_START = 'worker:start';

	private bool $waite = true;


	private static array $_waite = [];


	/**
	 * @return bool
	 */
	public function isWaite(): bool
	{
		return $this->waite;
	}


	/**
	 * @return void
	 */
	public function yield(): void
	{
		if ($this->waite === false) {
			return;
		}
		while ($this->waite == true) {
			usleep(10 * 1000);
		}
	}


	/**
	 * @param bool $waite
	 */
	public function status(bool $waite): void
	{
		$this->waite = $waite;
	}


}
