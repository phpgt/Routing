<?php
namespace Gt\Routing;

use Attribute;

#[Attribute]
class Route {
	const METHODS_ALL = ["get", "head", "post", "put", "delete"];

	public function __construct(
		protected array $methods = self::METHODS_ALL,
		protected ?string $path = null,
		protected ?string $name = null,
		protected ?string $accept = null,
	) {

	}
}
