<?php

namespace Http;


use Http\Handler\RouterCollector;
use Kiri\Abstracts\Component;
use Exception;
use Http\Abstracts\EventDispatchHelper;
use Http\Abstracts\ExceptionHandlerInterface;
use Http\Abstracts\ResponseHelper;
use Http\Constrict\RequestInterface;
use Http\Constrict\ResponseInterface;
use Http\Handler\DataGrip;
use Http\Handler\Dispatcher;
use Http\Handler\Handler;
use Http\Handler\Router;
use Http\Message\ServerRequest;
use Http\Message\Stream;
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
class Server extends Component implements OnRequestInterface
{

	use EventDispatchHelper;
	use ResponseHelper;

	public RouterCollector $router;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws ConfigException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function init()
	{
		$exceptionHandler = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = $this->container->get($exceptionHandler);
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
				$PsrResponse->stream->write('Allow Method[' . $request->getMethod() . '].');
				$PsrResponse->withStatus($handler);
			} else if (is_null($handler)) {
				$PsrResponse->stream->write('Page not found.');
				$PsrResponse->withStatus(404);
			} else {
				$PsrResponse = $this->handler($handler, $PsrRequest);
			}
		} catch (\Throwable $throwable) {
			$PsrResponse = $this->exceptionHandler->emit($throwable, $this->response);
		} finally {
			if ($request->server['request_method'] == 'HEAD') {
				$PsrResponse->getBody()->write('');
			}
			$this->responseEmitter->sender($response, $PsrResponse);
		}
	}


	/**
	 * @param Handler $handler
	 * @param $PsrRequest
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function handler(Handler $handler, $PsrRequest): \Psr\Http\Message\ResponseInterface
	{
		$dispatcher = new Dispatcher($handler, $handler->middlewares);
		return $dispatcher->handle($PsrRequest);
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		$PsrResponse = Context::setContext(ResponseInterface::class, new Message\Response());

		$PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));
		if ($PsrRequest->isMethod('OPTIONS')) {
			$request->server['request_uri'] = '/*';
		}
		return [$PsrRequest, $PsrResponse];
	}


}
