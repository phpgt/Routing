<?php
namespace Gt\Routing\LogicStream;

use Exception;
use ReflectionClass;
use SplFileObject;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class LogicStreamWrapper {
	const NAMESPACE_PREFIX = "Gt\\AppLogic";
	const STREAM_NAME = "gt-logic-stream";

	private int $position;
	private string $path;
	private string $contents;
	private bool $namespaceInjected;
	/** @var array<string, true> */
	private array $internalClassMap;
	public mixed $context;

	public function __construct() {
		$this->internalClassMap = $this->buildInternalClassMap();
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_open(string $path):bool {
		$this->position = 0;
		$this->path = substr($path, strpos($path, "//") + 2);
		$this->contents = "";
		$this->namespaceInjected = false;
		$this->loadContents(new SplFileObject($this->path, "r"));
		if($this->namespaceInjected) {
			$this->contents = $this->prefixInternalClassNames($this->contents);
		}
		return true;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_read(int $count):string {
		$ret = substr($this->contents, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_tell():int {
		return $this->position;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_eof():bool {
		return $this->position >= strlen($this->contents);
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_set_option():bool {
		return false;
	}

	/** @return array<int, string> File statistics, as per stat(). */
	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	public function stream_stat():array {
		return [];
	}

	/**
	 * This is the main purpose of this class. It allows scripts to be
	 * required that only declare a single function. Without this wrapper,
	 * when a developer declares another function of the same name (that
	 * will execute in the same context as another), PHP would fail stating
	 * "cannot redeclare function".
	 *
	 * This function checks for a namespace declaration at the top of the
	 * script, and if there is no declaration, it will inject one that
	 * matches the current path.
	 */
	private function loadContents(SplFileObject $file):void {
		$foundNamespace = false;
		$withinBlockComment = false;
		$lineNumber = 0;
		$this->initFileParsing($file);

		while(!$file->eof() && !$foundNamespace) {
			$line = $file->fgets();
			$lineNumber++;
			$foundNamespace = $this->processLine($line, $lineNumber, $withinBlockComment);
		}

		// Append the remaining file content
		$this->appendRemainingFileContent($file);
	}

	private function initFileParsing(SplFileObject $file):void {
		$line = $file->fgets();
		if(!str_starts_with($line, "<?php")) {
			throw new Exception(
				"Logic file at " . $this->path . " must start by opening a PHP tag. " .
				"See https://www.php.gt/routing/logic-stream-wrapper"
			);
		}
		$this->contents .= $line;
	}

	private function processLine(
		string $line,
		int $lineNumber,
		bool &$withinBlockComment,
	):bool {
		$trimmedLine = trim($line);

		if($this->startsBlockComment($trimmedLine)) {
			$withinBlockComment = true;
		}
		if($withinBlockComment) {
			$withinBlockComment = !$this->endsBlockComment($trimmedLine);
			$this->contents .= $line;
			return false;
		}

		return $this->checkAndAppendNamespace($trimmedLine, $lineNumber, $line);
	}

	private function startsBlockComment(string $line):bool {
		return str_starts_with($line, "/*");
	}

	private function endsBlockComment(string $line):bool {
		return str_contains($line, "*/");
	}

	private function checkAndAppendNamespace(
		string $line,
		int $lineNumber,
		string $originalLine,
	):bool {
		if($lineNumber > 0) {
			if(str_contains($line, "\tnamespace")) {
				$this->contents .= $originalLine;
				return true;
			}

			if($line) {
				if($this->contents === "<?php\n") {
					$this->contents = "<?php\t";
				}
				$namespace = new LogicStreamNamespace($this->path, self::NAMESPACE_PREFIX);
				$this->namespaceInjected = true;
				$this->contents .= "namespace $namespace;\n$originalLine";
				return true;
			}
		}

		$this->contents .= $originalLine;
		return false;
	}

	private function appendRemainingFileContent(SplFileObject $file):void {
		while(!$file->eof()) {
			$this->contents .= $file->fgets();
		}
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

			$map[strtolower($reflectionClass->getShortName())] = true;
		}

		return $map;
	}

	private function prefixInternalClassNames(string $code):string {
		$tokens = \PhpToken::tokenize($code);
		$imports = $this->parseUseImports($tokens);

		$output = "";
		$expectClassName = false;
		$expectTypeName = false;
		$inUseStatement = false;
		$topLevelBraceDepth = 0;
		$tokenCount = count($tokens);

		for($i = 0; $i < $tokenCount; $i++) {
			$token = $tokens[$i];

			if($token->text === "{") {
				$topLevelBraceDepth++;
			}
			elseif($token->text === "}") {
				$topLevelBraceDepth = max(0, $topLevelBraceDepth - 1);
			}

			if($token->id === T_USE && $topLevelBraceDepth === 0) {
				$inUseStatement = true;
				$output .= $token->text;
				continue;
			}
			if($inUseStatement) {
				$output .= $token->text;
				if($token->text === ";") {
					$inUseStatement = false;
				}
				continue;
			}

			if(in_array($token->id, [T_NEW, T_INSTANCEOF, T_EXTENDS, T_IMPLEMENTS, T_CATCH], true)) {
				$expectClassName = true;
				$output .= $token->text;
				continue;
			}

			if($token->id === T_DOUBLE_COLON) {
				$expectClassName = false;
				$expectTypeName = false;
				$output .= $token->text;
				continue;
			}

			if(in_array($token->text, [":", "|", "&", "?", ","], true)) {
				$expectTypeName = true;
				$output .= $token->text;
				continue;
			}

			if($token->id === T_VARIABLE || $token->text === ")" || $token->text === "=") {
				$expectTypeName = false;
			}

			if($this->shouldPrefixToken($tokens, $i, $expectClassName, $expectTypeName, $imports)) {
				$output .= "\\" . $token->text;
			}
			else {
				$output .= $token->text;
			}

			if($expectClassName && ($token->id === T_STRING || $token->id === T_NAME_QUALIFIED)) {
				$expectClassName = false;
			}
		}

		return $output;
	}

	/**
	 * @param array<int, \PhpToken> $tokens
	 * @param array<string, true> $imports
	 */
	private function shouldPrefixToken(
		array $tokens,
		int $tokenIndex,
		bool $expectClassName,
		bool $expectTypeName,
		array $imports,
	):bool {
		$token = $tokens[$tokenIndex];
		if($token->id !== T_STRING && $token->id !== T_NAME_QUALIFIED) {
			return false;
		}

		if(!$expectClassName && !$expectTypeName && !$this->isStaticClassReference($tokens, $tokenIndex)) {
			return false;
		}

		if(str_contains($token->text, "\\")) {
			return false;
		}

		$shortName = strtolower($token->text);
		if(!isset($this->internalClassMap[$shortName])) {
			return false;
		}
		if(isset($imports[$shortName])) {
			return false;
		}
		if($this->hasNamespaceSeparatorPrefix($tokens, $tokenIndex)) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<int, \PhpToken> $tokens
	 */
	private function isStaticClassReference(array $tokens, int $tokenIndex):bool {
		for($i = $tokenIndex + 1; isset($tokens[$i]); $i++) {
			if(in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}

			return $tokens[$i]->id === T_DOUBLE_COLON;
		}

		return false;
	}

	/**
	 * @param array<int, \PhpToken> $tokens
	 */
	private function hasNamespaceSeparatorPrefix(array $tokens, int $tokenIndex):bool {
		for($i = $tokenIndex - 1; $i >= 0; $i--) {
			if(in_array($tokens[$i]->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}

			return $tokens[$i]->id === T_NS_SEPARATOR;
		}

		return false;
	}

	/**
	 * @param array<int, \PhpToken> $tokens
	 * @return array<string, true>
	 */
	private function parseUseImports(array $tokens):array {
		$imports = [];
		$topLevelBraceDepth = 0;
		$tokenCount = count($tokens);

		for($i = 0; $i < $tokenCount; $i++) {
			$token = $tokens[$i];
			if($token->text === "{") {
				$topLevelBraceDepth++;
			}
			elseif($token->text === "}") {
				$topLevelBraceDepth = max(0, $topLevelBraceDepth - 1);
			}

			if($token->id !== T_USE || $topLevelBraceDepth !== 0) {
				continue;
			}

			$importName = "";
			$alias = "";
			$inAlias = false;
			for($j = $i + 1; isset($tokens[$j]); $j++) {
				$next = $tokens[$j];
				if($next->text === ";") {
					break;
				}

				if($next->id === T_AS) {
					$inAlias = true;
					continue;
				}

				if($next->id === T_STRING || $next->id === T_NAME_QUALIFIED || $next->id === T_NS_SEPARATOR) {
					if($inAlias) {
						$alias .= $next->text;
					}
					else {
						$importName .= $next->text;
					}
				}
			}

			$shortName = strtolower($alias ?: preg_replace('/^.*\\\\/', "", $importName));
			if($shortName) {
				$imports[$shortName] = true;
			}
		}

		return $imports;
	}
}
