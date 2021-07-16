<?php
namespace Gt\Routing\Path;

use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;

class DirectoryExpander {
	/**
	 * @return Generator<string, SplFileObject> The key is the file path,
	 * the value is the current file object.
	 */
	public function generate(string $dirPath):Generator {
		if(!is_dir($dirPath)) {
			return;
		}

		$directoryIterator = new RecursiveDirectoryIterator(
			$dirPath,
			FilesystemIterator::KEY_AS_PATHNAME
			| FilesystemIterator::CURRENT_AS_FILEINFO
			| FilesystemIterator::FOLLOW_SYMLINKS
			| FilesystemIterator::SKIP_DOTS
		);

		$iterator = new RecursiveIteratorIterator(
			$directoryIterator,
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach($iterator as $filePath => $file) {
			/** @var SplFileObject $file */
//			$fileName = $file->getFilename();
			if($file->isDir()) {
				continue;
			}

			yield $filePath => $file;
		}
	}
}
