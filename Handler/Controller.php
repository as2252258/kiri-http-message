<?php
declare(strict_types=1);

namespace Http\Handler;


use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use Note\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Kiri\Web
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property LoggerInterface $
 */
class Controller
{


	/**
	 * @var ContainerInterface
	 */
	#[Inject(ContainerInterface::class)]
	public ContainerInterface $container;


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



	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;

}
