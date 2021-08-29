<?php
use Gt\Routing\Path\DynamicPath;

function go(DynamicPath $dynamicPath):void {
	echo "Category index GO! - category name: ", $dynamicPath->get("category"), PHP_EOL;
	echo "THIS SHOULD NOT EXECUTE WHEN THERE'S A NAMED CATEGORY!", PHP_EOL;
}
