<?php
namespace Gt\Routing;

use RuntimeException;
use Throwable;

class RoutingException extends RuntimeException {
	const DEFAULT_MESSAGE = "";

	public function __construct(
		string $message = self::DEFAULT_MESSAGE,
		int $code = 0,
		?Throwable $previous = null
	) {
		parent::__construct($message, $code, $previous);
	}
}
