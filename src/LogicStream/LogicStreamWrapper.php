<?php
namespace Gt\Routing\LogicStream;

use Exception;
use SplFileObject;

class LogicStreamWrapper {
	const NAMESPACE_PREFIX = "Gt\\AppLogic";
	const STREAM_NAME = "gt-logic-stream";

	private int $position;
	private string $path;
	private string $contents;

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	function stream_open(string $path):bool {
		$this->position = 0;
		$this->path = substr($path, strpos($path, "//") + 2);
		$this->contents = "";
		$this->loadContents(new SplFileObject($this->path, "r"));
		return true;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	function stream_read(int $count):string {
		$ret = substr($this->contents, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	function stream_tell():int {
		return $this->position;
	}

	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName
	function stream_eof():bool {
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
		$this->initFileParsing($file);

		while(!$file->eof() && !$foundNamespace) {
			$line = $file->fgets();
			$foundNamespace = $this->processLine($line);
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

	private function processLine(string $line):bool {
		static $withinBlockComment = false;
		static $lineNumber = 0;

		$trimmedLine = trim($line);
		$lineNumber++;

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
			if(str_starts_with($line, "namespace")) {
				$this->contents .= $originalLine;
				return true;
			}
			if($line) {
				$namespace = new LogicStreamNamespace($this->path, self::NAMESPACE_PREFIX);
				$this->contents .= "namespace $namespace;\t";
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
}
