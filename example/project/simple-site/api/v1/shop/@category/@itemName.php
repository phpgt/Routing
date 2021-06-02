<?php
use Gt\Http\Request;

function go(Request $request):void {
	echo "Item Name GO!", PHP_EOL;
	echo "Item name is being accessed from", $request->getUri(), PHP_EOL;
}
