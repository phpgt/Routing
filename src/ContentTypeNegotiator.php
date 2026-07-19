<?php
namespace GT\Routing;

use Negotiation\Accept;
use Negotiation\BaseAccept;
use Negotiation\Negotiator;

class ContentTypeNegotiator {
	public function __construct(private readonly ?RouterConfig $config) {}

	/**
	 * @param array<RouterCallback> $callbackArray
	 */
	public function negotiate(
		string $acceptHeader,
		array $callbackArray
	):?RouterCallback {
		if($this->isWildcardOnlyAcceptHeader($acceptHeader)) {
			return $this->negotiateBestCallback(
				$this->getDefaultContentType($acceptHeader),
				$callbackArray
			)[0] ?? null;
		}

		$bestNegotiation = $this->negotiateBestCallback(
			$acceptHeader,
			$callbackArray
		);

		if($this->isLooseAcceptHeader($acceptHeader)) {
			$defaultContentType = $this->negotiateDefaultContentType(
				$acceptHeader
			);
			if($defaultContentType
			&& $defaultContentType->getQuality() >= ($bestNegotiation[1]?->getQuality() ?? 0)) {
				$defaultNegotiation = $this->negotiateBestCallback(
					$defaultContentType->getType(),
					$callbackArray
				);
				if($defaultNegotiation) {
					return $defaultNegotiation[0];
				}
			}
		}

		if($bestNegotiation) {
			return $bestNegotiation[0];
		}

		return null;
	}

	/**
	 * @param array<RouterCallback> $callbackArray
	 * @return null|array{RouterCallback, ?Accept}
	 */
	private function negotiateBestCallback(
		string $acceptHeader,
		array $callbackArray
	):?array {
		$bestQuality = -1;
		$bestCallback = null;
		$bestNegotiation = null;
		foreach($callbackArray as $callback) {
			if(!$callback->matchesAccept($acceptHeader)) {
				continue;
			}

			$best = $callback->getBestNegotiation($acceptHeader);
			$quality = $best?->getQuality() ?? 0;
			if($quality <= $bestQuality) {
				continue;
			}

			$bestQuality = $quality;
			$bestCallback = $callback;
			$bestNegotiation = $best;
		}

		if(!$bestCallback) {
			return null;
		}

		return [$bestCallback, $bestNegotiation];
	}

	private function getDefaultContentType(string $acceptHeader):string {
		return $this->config?->defaultContentType ?? $acceptHeader;
	}

	private function negotiateDefaultContentType(
		string $acceptHeader
	):?BaseAccept {
		$defaultContentType = $this->config?->defaultContentType;
		if(!$defaultContentType) {
			return null;
		}

		$negotiator = new Negotiator();
		$best = $negotiator->getBest(
			$acceptHeader,
			explode(",", $defaultContentType)
		);
		if(!$best instanceof BaseAccept) {
			return null;
		}

		return $best;
	}

	private function isLooseAcceptHeader(string $acceptHeader):bool {
		if($this->isWildcardOnlyAcceptHeader($acceptHeader)) {
			return false;
		}

		foreach(explode(",", $acceptHeader) as $acceptPart) {
			$mediaType = trim(explode(";", $acceptPart, 2)[0]);
			if(str_contains($mediaType, "*")) {
				return true;
			}
		}

		return false;
	}

	private function isWildcardOnlyAcceptHeader(string $acceptHeader):bool {
		$acceptHeader = trim($acceptHeader);
		if($acceptHeader === "") {
			return true;
		}

		foreach(explode(",", $acceptHeader) as $acceptPart) {
			$mediaType = trim(explode(";", $acceptPart, 2)[0]);
			if($mediaType !== "*/*") {
				return false;
			}
		}

		return true;
	}
}
