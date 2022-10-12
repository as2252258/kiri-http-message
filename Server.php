<?php

namespace Kiri\Message;


use Exception;
use Kiri;
use Kiri\Abstracts\AbstractServer;
use Kiri\Abstracts\Config;
use Kiri\Context;
use Kiri\Message\Response as Psr7Response;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Message\Abstracts\ExceptionHandlerInterface;
use Kiri\Message\Abstracts\ResponseHelper;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\Handler\DataGrip;
use Kiri\Message\Handler\Dispatcher;
use Kiri\Message\Handler\RouterCollector;
use Kiri\Server\Events\OnAfterWorkerStart;
use Kiri\Server\Events\OnBeforeWorkerStart;
use Psr\Container\ContainerExceptionInterface;
use Kiri\Di\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Kiri\Abstracts\CoordinatorManager;
use Kiri\Coordinator;
use Swoole\Http\Response;


/**
 *
 */
class Server extends AbstractServer implements OnRequestInterface
{

	use ResponseHelper;

	public RouterCollector $router;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exception;


	private ContentType $contentType;

	public Emitter $emitter;


	/**
	 * @param ContainerInterface $container
	 * @param Dispatcher $dispatcher
	 * @param EventProvider $provider
	 * @param DataGrip $dataGrip
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(
		public ContainerInterface $container,
		public Dispatcher         $dispatcher,
		public EventProvider      $provider,
		public DataGrip           $dataGrip,
		array                     $config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @throws ConfigException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	public function init()
	{
		$this->emitter = $this->container->get(Emitter::class);

		$exception = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exception))) {
			$exception = ExceptionHandlerDispatcher::class;
		}
		$this->exception = $this->container->get($exception);

		$this->provider->on(OnBeforeWorkerStart::class, [$this, 'onStartWaite']);
		$this->provider->on(OnAfterWorkerStart::class, [$this, 'onEndWaite']);

		$this->contentType = Config::get('response.format', ContentType::JSON);
		$this->router = $this->dataGrip->get('http');
	}


	/**
	 * @return void
	 */
	public function onStartWaite(): void
	{
		CoordinatorManager::utility(Coordinator::WORKER_START)->waite();
	}


	/**
	 * @return void
	 */
	public function onEndWaite(): void
	{
		CoordinatorManager::utility(Coordinator::WORKER_START)->done();
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response): void
	{
		try {
			CoordinatorManager::utility(Coordinator::WORKER_START)->yield();

			/** @var ServerRequest $PsrRequest */
			/** @var Psr7Response $PsrResponse */
			[$PsrRequest, $PsrResponse] = $this->initRequestResponse($request);

			$PsrResponse = $this->executed($request, $PsrRequest, $PsrResponse);
		} catch (\Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [$throwable]);
			$PsrResponse = $this->exception->emit($throwable, di(Constrict\Response::class));
		} finally {
			Context::clearAll();
			$this->emitter->sender($response, $PsrResponse);
		}
	}


	/**
	 * @param Request $request
	 * @param ServerRequest $PsrRequest
	 * @param Psr7Response $PsrResponse
	 * @return PsrResponseInterface
	 * @throws Exception
	 */
	private function executed(Request $request, ServerRequest $PsrRequest, Psr7Response $PsrResponse): PsrResponseInterface
	{
		$dispatcher = $this->router->find($request->server['request_uri'], $request->getMethod());
		if (is_null($dispatcher)) {
			return $PsrResponse->withStatus(404)->withContent('Page not found[' . $request->server['request_uri'] . '].');
		} else if (is_integer($dispatcher)) {
			return $PsrResponse->withStatus(405)->withContent('Allow Method[' . $request->getMethod() . '].');
		} else {
			return $dispatcher->dispatch->recover($PsrRequest);
		}
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		/** @var ResponseInterface $PsrResponse */
		$PsrResponse = Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());
		$PsrResponse->withContentType($this->contentType);

		$serverRequest = (new ServerRequest())->withData($request->getData())
			->withServerParams($request->server)
			->withServerTarget($request)
			->withCookieParams($request->cookie ?? [])
			->withUri(Uri::parseUri($request))
			->withQueryParams($request->get)
			->withUploadedFiles($request->files)
			->withMethod($request->getMethod())
			->withParsedBody($request->post);

		/** @var ServerRequest $PsrRequest */
		$PsrRequest = Context::setContext(RequestInterface::class, $serverRequest);

		return [$PsrRequest, $PsrResponse];
	}


}
