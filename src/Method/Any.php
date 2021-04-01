<?php
namespace Gt\Routing\Method;

use Attribute;
use Gt\Routing\Route;

#[Attribute]
class Any extends RouteMethod {
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
