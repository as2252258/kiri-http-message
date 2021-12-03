<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
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
	public function getRequest(): RequestInterface
	{
		return $this->container->get(RequestInterface::class);
	}


	/**
	 * @return ResponseInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getResponse(): ResponseInterface
	{
		return $this->container->get(ResponseInterface::class);
	}


	/**
	 * @return LoggerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getLogger(): LoggerInterface
	{
		return $this->container->get(LoggerInterface::class);
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
