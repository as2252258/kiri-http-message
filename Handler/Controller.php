<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Kiri;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerInterface $logger
 * @property ContainerInterface $container
 */
class Controller
{

	/**
	 * @return RequestInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function getRequest(): RequestInterface
	{
		return $this->getContainer()->get(RequestInterface::class);
	}


	/**
	 * @return ResponseInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function getResponse(): ResponseInterface
	{
		return $this->getContainer()->get(ResponseInterface::class);
	}


	/**
	 * @return LoggerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function getLogger(): LoggerInterface
	{
		return $this->getContainer()->get(LoggerInterface::class);
	}


	/**
	 * @return ContainerInterface
	 */
	#[Pure] protected function getContainer(): ContainerInterface
	{
		return Kiri::getDi();
	}


	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->{'get' . ucfirst($name)}();
	}


}
