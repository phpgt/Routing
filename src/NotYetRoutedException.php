<?php
namespace Gt\Routing;

use Throwable;

class NotYetRoutedException extends RoutingException {
	const DEFAULT_MESSAGE = "A Router method is being called before route(). "
		. "For help, please visit: https://www.php.gt/routing/not-yet-routed-exception";
}
