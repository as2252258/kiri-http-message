<?php
declare(strict_types=1);

namespace Kiri\Message\Handler\Abstracts;


use Exception;
use Kiri\Abstracts\Component;
use Kiri;


/**
 * Class HttpService
 * @package Http\Abstracts
 */
abstract class HttpService extends Component
{

	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name): mixed
	{
		if (method_exists($this, $name)) {
			return $this->{$name}();
		}
		$handler = 'get' . ucfirst($name);
		if (method_exists($this, $handler)) {
			return $this->{$handler}();
		}
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		$message = sprintf('method %s::%s not exists.', static::class, $name);
		throw new Exception($message);
	}

}
