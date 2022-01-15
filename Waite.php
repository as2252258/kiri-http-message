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


	public function yield(): void
	{
		if (!$this->waite) {
			return;
		}
		while (true) {
			if (!$this->waite) {
				break;
			}
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
