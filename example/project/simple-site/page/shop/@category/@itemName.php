<?php
use Gt\Http\Request;
use Gt\Routing\Path\DynamicPath;

function go(DynamicPath $path):void {
	echo "SHOP ITEM: " . $path->get("itemName"), PHP_EOL;
}
