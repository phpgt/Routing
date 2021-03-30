<?php
namespace Gt\Routing;

class Handler {
	/** @var string[] */
	private array $viewExtensionList;
	private string $defaultBasename;

	public function __construct(
		private string $name,
		string...$viewExtension
	) {
		$this->viewExtensionList = $viewExtension;
	}

	public function setDefaultBasename(string $basename):void {
		$this->defaultBasename = $basename;
	}
}
