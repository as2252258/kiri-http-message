<?php
declare(strict_types=1);

namespace Kiri\Message\Handler;


use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Di\Container;
use Kiri;
use Kiri\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Web
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
