<?php

namespace Kiri\Message;


use Exception;
use Kiri\Message\Abstracts\EventDispatchHelper;
use Kiri\Message\Abstracts\ExceptionHandlerInterface;
use Kiri\Message\Abstracts\ResponseHelper;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\Handler\DataGrip;
use Kiri\Message\Handler\Dispatcher;
use Kiri\Message\Handler\RouterCollector;
use Kiri\Abstracts\AbstractServer;
use Kiri\Abstracts\Config;
use Kiri\Context;
use Kiri\Exception\ConfigException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 *
 */
class Server extends AbstractServer implements OnRequestInterface
{

	use EventDispatchHelper;
	use ResponseHelper;

	public RouterCollector $router;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exception;


	/**
	 * @throws ConfigException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function init()
	{
		$exception = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exception))) {
			$exception = ExceptionHandlerDispatcher::class;
		}
		$this->exception = $this->container->get($exception);
		$this->responseEmitter = $this->container->get(ResponseEmitter::class);

		$this->router = $this->container->get(DataGrip::class)->get('http');
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response): void
	{
		try {
			[$PsrRequest, $PsrResponse] = $this->initRequestResponse($request);
			$handler = $this->router->find($request->server['request_uri'], $request->getMethod());
			if (is_integer($handler)) {
				$this->fail($PsrResponse, 'Allow Method[' . $request->getMethod() . '].', $handler);
			} else if (is_null($handler)) {
				$this->fail($PsrResponse, 'Page not found.', 404);
			} else {
				$PsrResponse = $this->getContainer()->get(Dispatcher::class)->with($handler)->handle($PsrRequest);
			}
		} catch (\Throwable $throwable) {
			$PsrResponse = $this->exception->emit($throwable, $this->response);
		} finally {
			if ($request->server['request_method'] == 'HEAD') {
				$PsrResponse->getBody()->write('');
			}
			$this->responseEmitter->sender($response, $PsrResponse);
		}
	}


	/**
	 * @param $PsrResponse
	 * @param $message
	 * @param $code
	 * @return void
	 */
	private function fail($PsrResponse, $message, $code)
	{
		$PsrResponse->getBody()->write($message);
		$PsrResponse->withStatus($code);
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		$PsrResponse = Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());

		$PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));
		if ($PsrRequest->isMethod('OPTIONS')) {
			$request->server['request_uri'] = '/*';
		}
		return [$PsrRequest, $PsrResponse];
	}


}
