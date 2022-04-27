<?php

namespace Kiri\Message\Handler\Abstracts;


use Exception;
use Kiri;
use Kiri\Annotation\Inject;
use Kiri\Core\Help;
use Kiri\Message\Constrict\ResponseInterface as HttpResponseInterface;
use Kiri\Message\Handler\Handler as CHl;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;



abstract class Handler implements RequestHandlerInterface
{

    protected int $offset = 0;


    public CHl $handler;


    #[Inject(HttpResponseInterface::class)]
    public HttpResponseInterface $response;


    /**
     * @param CHl $handler
     * @return $this
     */
    public function with(CHl $handler): static
    {
        $this->offset = 0;
        $this->handler = $handler;
        return $this;
    }


    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    protected function execute(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->handler->middlewares) || !isset($this->handler->middlewares[$this->offset])) {
            return $this->dispatcher($this->handler);
        }

        $middleware = Kiri::getDi()->get($this->handler->middlewares[$this->offset]);
        if (!($middleware instanceof MiddlewareInterface)) {
            throw new Exception('get_implements_class($middleware) not found method process.');
        }

        $this->offset++;

        return $middleware->process($request, $this);
    }


    /**
     * @param CHl $handler
     * @return mixed
     */
    public function dispatcher(CHl $handler): mixed
    {
        $response = call_user_func($handler->callback, ...$handler->params);

        $format = Config::get('response.format', 'application/json; charset=utf-8');
        $this->response->withContentType($format);
        if (is_null($response) && $this->response->getBody()->getSize() > 0) {
            return $this->response;
        }
        if (!($response instanceof ResponseInterface)) {
            $response = $this->transferToResponse($response, $format);
        }
        return $response;
    }


    /**
     * @param mixed $responseData
     * @param $format
     * @return ResponseInterface
     */
    private function transferToResponse(mixed $responseData, $format): ResponseInterface
    {
        $interface = $this->response->withStatus(200);
        if (is_string($responseData)) {
            return $interface->withContent($responseData);
        } else if (str_contains($format, 'xml')) {
            return $interface->withContent(Help::toXml($responseData));
        } else {
            return $interface->withContent(Json::encode($responseData));
        }
    }


}
