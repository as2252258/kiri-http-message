<?php

namespace Kiri\Message;


use Exception;
use Kiri;
use Kiri\Abstracts\AbstractServer;
use Kiri\Abstracts\Config;
use Kiri\Context;
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
		CoordinatorManager::utility(Coordinator::WORKER_START)->status(true);
	}


	/**
	 * @return void
	 */
	public function onEndWaite(): void
	{
		CoordinatorManager::utility(Coordinator::WORKER_START)->status(false);
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
			[$PsrRequest, $PsrResponse] = $this->initRequestResponse($request);
			$handler = $this->router->find($request->server['request_uri'], $request->getMethod());

			if (is_null($handler)) {
				$PsrResponse->withStatus(404)->withContent('Page not found[' . $request->server['request_uri'] . '].');
			} else if (is_integer($handler)) {
				$PsrResponse->withStatus(405)->withContent('Allow Method[' . $request->getMethod() . '].');
			} else {
				$PsrResponse = $this->dispatcher->with($handler)->handle($PsrRequest);
			}
		} catch (\Throwable $throwable) {
			$this->logger->error($throwable->getMessage(), [$throwable]);
			$PsrResponse = $this->exception->emit($throwable, Kiri::getDi()->get(Constrict\Response::class));
		} finally {
			$this->emitter->sender($response, $PsrResponse->withContentType($this->contentType));
		}
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		$PsrResponse = Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());

		/** @var ServerRequest $PsrRequest */
		$PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

		return [$PsrRequest, $PsrResponse];
	}


}
