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

	function stream_open(string $path):bool {
		$this->position = 0;
		$this->path = substr($path, strpos($path, "//") + 2);
		$this->contents = "";
		$this->loadContents(new SplFileObject($this->path, "r"));
		return true;
	}

	function stream_read(int $count):string {
		$ret = substr($this->contents, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	function stream_tell():int {
		return $this->position;
	}

	function stream_eof():bool {
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
	 */
	private function loadContents(SplFileObject $file):void {
		$foundNamespace = false;
		$withinBlockComment = false;
		$lineNumber = 0;

		while(!$file->eof() && !$foundNamespace) {
			$line = $file->fgets();
			if($lineNumber === 0) {
// TODO: Allow hashbangs before <?php
// Maybe this is possible by just skipping while the first character is a hash, and not increasing the line number.
				if(!str_starts_with($line, "<?php")) {
					throw new Exception("Logic file at " . $this->path . " must start by opening a PHP tag. See https://www.php.gt/routing/logic-stream-wrapper");
				}
			}
			$trimmedLine = trim($line);

			if(str_starts_with($trimmedLine, "/*")) {
				$withinBlockComment = true;
			}

			if($withinBlockComment) {
				if(str_contains($trimmedLine, "*/")) {
					$withinBlockComment = false;
				}
			}
			elseif($lineNumber > 0) {
				if(str_starts_with($trimmedLine, "namespace")) {
					$foundNamespace = true;
				}
				elseif($trimmedLine ) {
					$namespace = new LogicStreamNamespace(
						$this->path,
						self::NAMESPACE_PREFIX
					);
					$this->contents .= "namespace $namespace;\t";
					$foundNamespace = true;
				}
			}

			$this->contents .= $line;
			$lineNumber++;
		}

		while(!$file->eof()) {
			$line = $file->fgets();
			$this->contents .= $line;
		}
	}
}
