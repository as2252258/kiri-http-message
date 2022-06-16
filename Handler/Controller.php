<?php
declare(strict_types=1);

namespace Kiri\Message\Handler;


use Kiri;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Di\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class WebController
 * @package Kiri\Web
 */
abstract class Controller
{


	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @param LoggerInterface $logger
	 * @param ContainerInterface $container
	 */
	public function __construct(
		public RequestInterface   $request,
		public ResponseInterface  $response,
		public LoggerInterface    $logger,
		public ContainerInterface $container)
	{
	}


}
