<?php
function go(\Gt\Routing\Path\DynamicPath $path) {
	echo "The topic is: " . $path->get("id"), PHP_EOL;
}
