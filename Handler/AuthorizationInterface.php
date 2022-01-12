<?php
declare(strict_types=1);


namespace Kiri\Message\Handler;


/**
 * Interface AuthorizationInterface
 * @package Kiri\Http
 */
interface AuthorizationInterface
{


	/**
	 * @return string|int
	 * 获取唯一识别码
	 */
	public function getUniqueId(): string|int;



	/**
	 * @param string $key
	 * @param int $timeout
	 * @return bool
	 */
	public function lock(string $key, int $timeout): bool;



	/**
	 * @param string $key
	 * @return bool
	 */
	public function unlock(string $key): bool;


}
