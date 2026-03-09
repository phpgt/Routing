<?php
use Gt\Http\Request;
use GT\Routing\Path\DynamicPath;

function go(DynamicPath $path) {
	echo "SHOP ITEM: " . $path->get("itemName"), PHP_EOL;
}
