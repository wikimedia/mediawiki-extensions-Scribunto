<?php

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Extension\Produnto\Updater\ProduntoPlatformVersionsHook;

/**
 * Hooks from the Produnto extension
 */
class ProduntoHooks implements ProduntoPlatformVersionsHook {
	public function __construct( private EngineFactory $engineFactory ) {
	}

	public function onProduntoPlatformVersions( array &$versions ): void {
		$versions += $this->engineFactory->getDefaultEngine()->getPlatformVersions();
	}
}
