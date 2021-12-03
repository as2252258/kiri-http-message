<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use Kiri\Abstracts\BaseObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerInterface $logger
 */
class Controller extends BaseObject
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

}
