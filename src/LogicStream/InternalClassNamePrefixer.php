<?php
namespace Gt\Routing\LogicStream;

use ReflectionClass;

/**
 * @phpstan-type PhpTokenTuple array{int, string, int}
 */
class InternalClassNamePrefixer {
	/** @var array<string, true> */
	private array $internalClassMap;

	public function __construct() {
		$this->internalClassMap = $this->buildInternalClassMap();
	}

	public function prefix(string $code):string {
		$tokens = token_get_all($code);
		$imports = $this->parseUseImports($tokens);
		$state = new InternalClassNamePrefixerState();

		$output = "";
		foreach($tokens as $index => $token) {
			$output .= $this->renderToken($tokens, $index, $token, $imports, $state);
		}

		return $output;
	}

	/**
	 * @return array<string, true>
	 */
	private function buildInternalClassMap():array {
		$map = [];
		foreach(get_declared_classes() as $className) {
			$reflectionClass = new ReflectionClass($className);
			if(!$reflectionClass->isInternal()) {
				continue;
			}

			$shortName = strtolower($reflectionClass->getShortName());
			$map[$shortName] = true;
		}

		return $map;
	}

	/**
	 * @param array<int, string|array{int, string, int}> $tokens
	 * @param array<string, true> $imports
	 * @param string|array{int, string, int} $token
	 */
	private function renderToken(
		array $tokens,
		int $index,
		string|array $token,
		array $imports,
		InternalClassNamePrefixerState $state,
	):string {
		$state->updateBraceDepth($token);
		if($state->shouldHandleAsUseStatement($token)) {
			$state->inUseStatement = true;
			return $this->tokenText($token);
		}
		if($state->inUseStatement) {
			$state->updateUseStatement($token);
			return $this->tokenText($token);
		}

		$state->updateExpectations($token);
		if(!$this->shouldPrefixToken($tokens, $index, $token, $imports, $state)) {
			return $this->tokenText($token);
		}

		return "\\" . $this->tokenText($token);
	}

	/**
	 * @param array<int, string|array{int, string, int}> $tokens
	 * @param array<string, true> $imports
	 * @param string|array{int, string, int} $token
	 */
	private function shouldPrefixToken(
		array $tokens,
		int $tokenIndex,
		string|array $token,
		array $imports,
		InternalClassNamePrefixerState $state,
	):bool {
		if(!$this->isClassNameToken($token)) {
			return false;
		}

		if(!$state->isClassNameExpected($tokens, $tokenIndex)) {
			return false;
		}

		$tokenText = $this->tokenText($token);
		if(str_contains($tokenText, "\\")) {
			return false;
		}

		$shortName = strtolower($tokenText);
		if(!isset($this->internalClassMap[$shortName])) {
			return false;
		}
		if(isset($imports[$shortName])) {
			return false;
		}

		return !$this->hasNamespaceSeparatorPrefix($tokens, $tokenIndex);
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function isClassNameToken(string|array $token):bool {
		if(!is_array($token)) {
			return false;
		}

		return in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true);
	}

	/**
	 * @param array<int, string|PhpTokenTuple> $tokens
	 */
	private function hasNamespaceSeparatorPrefix(array $tokens, int $tokenIndex):bool {
		for($index = $tokenIndex - 1; $index >= 0; $index--) {
			$token = $tokens[$index];
			if($this->isIgnorable($token)) {
				continue;
			}

			return is_array($token) && $token[0] === T_NS_SEPARATOR;
		}

		return false;
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function isIgnorable(string|array $token):bool {
		if(!is_array($token)) {
			return false;
		}

		return in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function tokenText(string|array $token):string {
		if(is_string($token)) {
			return $token;
		}

		return $token[1];
	}

	/**
	 * @param array<int, string|PhpTokenTuple> $tokens
	 * @return array<string, true>
	 */
	private function parseUseImports(array $tokens):array {
		$imports = [];
		$braceDepth = 0;
		$tokenCount = count($tokens);

		for($index = 0; $index < $tokenCount; $index++) {
			$token = $tokens[$index];
			$braceDepth = $this->updateBraceDepth($token, $braceDepth);
			if(!$this->isTopLevelUse($token, $braceDepth)) {
				continue;
			}

			[$importName, $alias] = $this->readUseStatement($tokens, $index + 1);
			$shortName = $this->getImportShortName($importName, $alias);
			if($shortName) {
				$imports[$shortName] = true;
			}
		}

		return $imports;
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function updateBraceDepth(string|array $token, int $braceDepth):int {
		if($token === "{") {
			return $braceDepth + 1;
		}
		if($token === "}") {
			return max(0, $braceDepth - 1);
		}

		return $braceDepth;
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function isTopLevelUse(string|array $token, int $braceDepth):bool {
		if(!is_array($token)) {
			return false;
		}

		return $token[0] === T_USE && $braceDepth === 0;
	}

	/**
	 * @param array<int, string|PhpTokenTuple> $tokens
	 * @return array{string, string}
	 */
	private function readUseStatement(array $tokens, int $startIndex):array {
		$importName = "";
		$alias = "";
		$inAlias = false;

		for($index = $startIndex; isset($tokens[$index]); $index++) {
			$token = $tokens[$index];
			if($token === ";") {
				break;
			}
			if(is_array($token) && $token[0] === T_AS) {
				$inAlias = true;
				continue;
			}

			$this->appendUseTokenText($token, $importName, $alias, $inAlias);
		}

		return [$importName, $alias];
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	private function appendUseTokenText(
		string|array $token,
		string &$importName,
		string &$alias,
		bool $inAlias,
	):void {
		if(!is_array($token)) {
			return;
		}
		if(!in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
			return;
		}
		if($inAlias) {
			$alias .= $token[1];
			return;
		}

		$importName .= $token[1];
	}

	private function getImportShortName(string $importName, string $alias):string {
		$selectedName = $alias ?: preg_replace('/^.*\\\\/', "", $importName);
		if(!$selectedName) {
			return "";
		}

		return strtolower($selectedName);
	}
}
