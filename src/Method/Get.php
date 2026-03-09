<?php
namespace GT\Routing\Method;

use Attribute;
use GT\Routing\HttpRoute;

/** @codeCoverageIgnore  */
#[Attribute]
class Get extends HttpRouteMethod {
	public function __construct(
		?string $path = null,
		?string $function = null,
		?string $name = null,
		?string $accept = null
	) {
		parent::__construct(
			[HttpRoute::METHOD_GET],
			$path,
			$function,
			$name,
			$accept,
		);
	}
}
