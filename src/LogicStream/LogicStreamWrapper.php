<?php
namespace Gt\Routing\LogicStream;

use Exception;
use SplFileObject;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @phpcs:disable Generic.NamingConventions.CamelCapsFunctionName
 */
class LogicStreamWrapper {
	const NAMESPACE_PREFIX = "Gt\\AppLogic";
	const STREAM_NAME = "gt-logic-stream";

	private int $position;
	private string $path;
	private string $contents;

	public function stream_open(string $path):bool {
		$this->position = 0;
		$this->path = substr($path, strpos($path, "//") + 2);
		$this->contents = "";
		$this->loadContents(new SplFileObject($this->path, "r"));
		return true;
	}

	public function stream_read(int $count):string {
		$ret = substr($this->contents, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	public function stream_tell():int {
		return $this->position;
	}

	public function stream_eof():bool {
		return $this->position >= strlen($this->contents);
	}

	public function stream_set_option():bool {
		return false;
	}

	/** @return array<int, string> File statistics, as per stat(). */
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
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function loadContents(SplFileObject $file):void {
		$this->processFileUntilNamespace($file);
		$this->appendRemainingContents($file);
	}

	/**
	 * Process the file until a namespace is found or added
	 */
	private function processFileUntilNamespace(SplFileObject $file): void {
		$foundNamespace = false;
		$withinBlockComment = false;
		$lineNumber = 0;

		while(!$file->eof() && !$foundNamespace) {
			$line = $file->fgets();

			if($lineNumber === 0) {
				$this->validateFirstLine($line);
			}

			$trimmedLine = trim($line);
			$withinBlockComment = $this->handleBlockComment($trimmedLine, $withinBlockComment);

			if(!$withinBlockComment && $lineNumber > 0) {
				$foundNamespace = $this->processNamespace($trimmedLine);
			}

			$this->contents .= $line;
			$lineNumber++;
		}
	}

	/**
	 * Validate that the first line of the file starts with <?php
	 */
	private function validateFirstLine(string $line): void {
		if(!str_starts_with($line, "<?php")) {
			throw new Exception(
				"Logic file at "
				. $this->path
				. " must start by opening a PHP tag. "
				. "See https://www.php.gt/routing/logic-stream-wrapper"
			);
		}
	}

	/**
	 * Handle block comments in the file
	 *
	 * @param string $trimmedLine The trimmed line to check
	 * @param bool $withinBlockComment Whether we're currently within a block comment
	 * @return bool Updated block comment status
	 */
	private function handleBlockComment(string $trimmedLine, bool $withinBlockComment): bool {
		if(str_starts_with($trimmedLine, "/*")) {
			$withinBlockComment = true;
		}

		if($withinBlockComment && str_contains($trimmedLine, "*/")) {
			$withinBlockComment = false;
		}

		return $withinBlockComment;
	}

	/**
	 * Process namespace declarations
	 *
	 * @param string $trimmedLine The trimmed line to check
	 * @return bool Whether a namespace was found or added
	 */
	private function processNamespace(string $trimmedLine): bool {
		if(str_starts_with($trimmedLine, "namespace")) {
			return true;
		}

		if($trimmedLine) {
			$namespace = new LogicStreamNamespace(
				$this->path,
				self::NAMESPACE_PREFIX
			);
			$this->contents .= "namespace $namespace;\t";
			return true;
		}

		return false;
	}

	/**
	 * Append the remaining contents of the file
	 */
	private function appendRemainingContents(SplFileObject $file): void {
		while(!$file->eof()) {
			$line = $file->fgets();
			$this->contents .= $line;
		}
	}
}
