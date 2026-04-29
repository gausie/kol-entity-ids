<?php

namespace KolEntityIds;

class Setup {
	/** @var array<int, array{type: string, config: array}> namespace ID → entity config */
	public static array $nsMap = [];

	/** @var array<string, array> entity type name → config */
	public static array $entityTypes = [];

	public static function onRegistration(): void {
		global $wgKolEntityIdTypes, $wgExtraNamespaces, $wgNamespaceProtection;

		if ( !is_array( $wgKolEntityIdTypes ) ) {
			return;
		}

		foreach ( $wgKolEntityIdTypes as $name => $config ) {
			$nsId = (int)$config['namespaceId'];

			$wgExtraNamespaces[$nsId] = $name;
			$wgExtraNamespaces[$nsId + 1] = $name . '_talk';

			// Prevent anyone creating real pages in entity namespaces — they are redirect-only.
			// Existing protection settings are preserved if already stricter.
			if ( !isset( $wgNamespaceProtection[$nsId] ) ) {
				$wgNamespaceProtection[$nsId] = [ 'editprotected' ];
			}
			if ( !isset( $wgNamespaceProtection[$nsId + 1] ) ) {
				$wgNamespaceProtection[$nsId + 1] = [ 'editprotected' ];
			}

			self::$nsMap[$nsId] = [ 'type' => $name, 'config' => $config ];
		}

		self::$entityTypes = $wgKolEntityIdTypes;
	}
}
