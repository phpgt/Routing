<?php
namespace Gt\Routing;

#[Attribute]
class Any extends Route {
	public function __construct(
		?string $path = null,
		?string $name = null,
		?string $accept = null
	) {
		parent::__construct(
			Route::METHODS_ALL,
			$path,
			$name,
			$accept,
		);
	}
}
