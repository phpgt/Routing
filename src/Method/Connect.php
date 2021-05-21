<?php
namespace Gt\Routing\Method;

use Attribute;
use Gt\Routing\HttpRoute;

#[Attribute]
class Connect extends HttpRouteMethod {
	public function __construct(
		?string $path = null,
		?string $function = null,
		?string $name = null,
		?string $accept = null
	) {
		parent::__construct(
			[HttpRoute::METHOD_CONNECT],
			$path,
			$function,
			$name,
			$accept,
		);
	}
}
