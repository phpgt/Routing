<?php
namespace Gt\Routing;

class RouterConfig {
	public function __construct(
		public readonly int $redirectResponseCode,
		public readonly string $defaultContentType,
	) {}
}
