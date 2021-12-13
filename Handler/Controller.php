<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Di\Container;
use Kiri\Kiri;
use Note\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerInterface $logger
 * @property ContainerInterface|Container $container
 */
class Controller
{

	/**
	 * @var RequestInterface
	 */
	#[Inject(RequestInterface::class)]
	public RequestInterface $request;


	/**
	 * @var ResponseInterface
	 */
	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;


	/**
	 * @var LoggerInterface
	 */
	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;


	/**
	 * @return ContainerInterface
	 */
	#[Pure] public function getContainer(): ContainerInterface
	{
		return Kiri::getDi();
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name): mixed
	{
		return $this->{'get' . ucfirst($name)}();
	}


}
