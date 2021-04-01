<?php
namespace Gt\Routing;

use Attribute;

#[Attribute]
abstract class Route {
	const METHOD_GET = "GET";
	const METHOD_HEAD = "HEAD";
	const METHOD_POST = "POST";
	const METHOD_PUT = "PUT";
	const METHOD_DELETE = "DELETE";
	const METHOD_CONNECT = "CONNECT";
	const METHOD_OPTIONS = "OPTIONS";
	const METHOD_TRACE = "TRACE";
	const METHOD_PATCH = "PATCH";

	const METHODS_ALL = [
		self::METHOD_GET,
		self::METHOD_HEAD,
		self::METHOD_POST,
		self::METHOD_PUT,
		self::METHOD_DELETE,
		self::METHOD_CONNECT,
		self::METHOD_OPTIONS,
		self::METHOD_TRACE,
		self::METHOD_PATCH,
	];

	/**
	 * @var string[] An HTTP method is idempotent if an identical request
	 * can be made once or several times in a row with the same effect while
	 * leaving the server in the same state. In other words, an idempotent
	 * method should not have any side-effects (except for keeping
	 * statistics). Implemented correctly, the GET, HEAD, PUT, and DELETE
	 * methods are idempotent, but not the POST method. All safe methods
	 * are also idempotent.
	 */
	const METHODS_IDEMPOTENT = [
		self::METHOD_GET,
		self::METHOD_HEAD,
		self::METHOD_PUT,
		self::METHOD_DELETE,
	];

	/**
	 * @var string[] An HTTP method is safe if it doesn't alter the state of
	 * the server. In other words, a method is safe if it leads to a
	 * read-only operation.
	 */
	const METHODS_SAFE = [
		self::METHOD_GET,
		self::METHOD_HEAD,
		self::METHOD_OPTIONS,
	];

	/**
	 * @var string[] A cacheable response is an HTTP response that can be
	 * cached, that is stored to be retrieved and used later, saving a new
	 * request to the server.
	 */
	const METHODS_CACHEABLE = [
		self::METHOD_GET,
		self::METHOD_HEAD,
	];

	public function __construct(
		protected array $methods = self::METHODS_ALL,
		protected ?string $path = null,
		protected ?string $name = null,
		protected ?string $accept = null,
	) {
	}
}
