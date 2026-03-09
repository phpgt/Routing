<?php
namespace Gt\Routing\LogicStream;

/**
 * @phpstan-type PhpTokenTuple array{int, string, int}
 */
class InternalClassNamePrefixerState {
	public bool $expectClassName = false;
	public bool $expectTypeName = false;
	public bool $inUseStatement = false;
	public int $topLevelBraceDepth = 0;

	/**
	 * @param string|PhpTokenTuple $token
	 */
	public function updateBraceDepth(string|array $token):void {
		if($token === "{") {
			$this->topLevelBraceDepth++;
			return;
		}
		if($token === "}") {
			$this->topLevelBraceDepth = max(0, $this->topLevelBraceDepth - 1);
		}
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	public function shouldHandleAsUseStatement(string|array $token):bool {
		if(!is_array($token)) {
			return false;
		}

		return $token[0] === T_USE && $this->topLevelBraceDepth === 0;
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	public function updateUseStatement(string|array $token):void {
		if($token === ";") {
			$this->inUseStatement = false;
		}
	}

	/**
	 * @param string|PhpTokenTuple $token
	 */
	public function updateExpectations(string|array $token):void {
		if(!is_array($token)) {
			$this->updateExpectationFromSymbol($token);
			return;
		}

		if(in_array($token[0], [T_NEW, T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS, T_CATCH], true)) {
			$this->expectClassName = true;
		}
		if($token[0] === T_DOUBLE_COLON) {
			$this->expectClassName = false;
			$this->expectTypeName = false;
		}
		if(in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true) && $this->expectClassName) {
			$this->expectClassName = false;
		}
		if($token[0] === T_VARIABLE) {
			$this->expectTypeName = false;
		}
	}

	/**
	 * @param array<int, string|PhpTokenTuple> $tokens
	 */
	public function isClassNameExpected(array $tokens, int $index):bool {
		if($this->expectClassName || $this->expectTypeName) {
			return true;
		}

		return $this->isStaticClassReference($tokens, $index);
	}

	private function updateExpectationFromSymbol(string $symbol):void {
		if(in_array($symbol, [":", "|", "&", "?", ","], true)) {
			$this->expectTypeName = true;
			return;
		}
		if(in_array($symbol, [")", "="], true)) {
			$this->expectTypeName = false;
		}
	}

	/**
	 * @param array<int, string|PhpTokenTuple> $tokens
	 */
	private function isStaticClassReference(array $tokens, int $index):bool {
		for($tokenIndex = $index + 1; isset($tokens[$tokenIndex]); $tokenIndex++) {
			$token = $tokens[$tokenIndex];
			if($this->isIgnorable($token)) {
				continue;
			}

			return is_array($token) && $token[0] === T_DOUBLE_COLON;
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
}
