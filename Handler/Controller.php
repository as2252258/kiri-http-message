<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\BaseObject;
use Kiri\Kiri;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerInterface $logger
 * @property ContainerInterface $container
 */
class Controller extends BaseObject
{

	/**
	 * @return RequestInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	protected function getRequest(): RequestInterface
	{
		return $this->getContainer()->get(RequestInterface::class);
	}


	/**
	 * @return ResponseInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	protected function getResponse(): ResponseInterface
	{
		return $this->getContainer()->get(ResponseInterface::class);
	}


	/**
	 * @return LoggerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	protected function getLogger(): LoggerInterface
	{
		return $this->getContainer()->get(LoggerInterface::class);
	}



}
