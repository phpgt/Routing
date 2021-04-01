<?php
namespace Gt\Routing\Method;

use Attribute;
use Gt\Routing\Route;

#[Attribute]
class Post extends RouteMethod {
	public function __construct(
		?string $path = null,
		?string $name = null,
		?string $accept = null
	) {
		parent::__construct(
			[Route::METHOD_POST],
			$path,
			$name,
			$accept,
		);
	}
}
