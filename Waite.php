<?php

namespace Kiri\Message;

class Waite
{


	private bool $waite = true;


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
	public function setWaite(bool $waite): void
	{
		$this->waite = $waite;
	}


}
