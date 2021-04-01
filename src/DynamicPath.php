<?php
namespace Gt\Routing;

use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use Gt\TypeSafeGetter\TypeSafeGetter;

class DynamicPath implements TypeSafeGetter {
	use NullableTypeSafeGetter;

	public function __construct(
		private array $kvp = []
	) {
	}

	public function get(string $name):mixed {
		return $this->kvp[$name] ?? null;
	}
}
