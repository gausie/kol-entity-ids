<?php

namespace KolEntityIds;

use CargoSQLQuery;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

class Hooks {
	public static function onBeforeInitialize(
		Title &$title,
		$article,
		OutputPage $output,
		User $user,
		WebRequest $request
	): void {
		$nsId = $title->getNamespace();

		if ( !isset( Setup::$nsMap[$nsId] ) ) {
			return;
		}

		$text = $title->getText();
		if ( !ctype_digit( $text ) || $text === '' ) {
			return;
		}

		$id = (int)$text;
		$config = Setup::$nsMap[$nsId]['config'];

		$linkField = $config['cargoLinkField'] ?? null;
		$fields = implode( ',', array_filter( [ $linkField, '_pageName' ] ) );

		$query = CargoSQLQuery::newFromValues(
			$config['cargoTable'],
			$fields,
			$config['cargoIdField'] . '=' . $id,
			'', '', '', '', '1', ''
		);
		$results = $query->run();

		if ( !$results ) {
			return;
		}

		$row = reset( $results );

		// CargoSQLQuery::run() returns HTML-escaped values; decode before use as a title.
		$link = $linkField !== null ? htmlspecialchars_decode( $row[$linkField] ?? '' ) : '';
		$pageName = htmlspecialchars_decode( $row['_pageName'] ?? '' );
		$target = $link !== '' ? $link : preg_replace( '/^Data:/', '', $pageName );

		if ( $target === '' ) {
			return;
		}

		$targetTitle = Title::newFromText( $target );
		if ( $targetTitle === null ) {
			return;
		}

		$request->response()->header( 'Location: ' . $targetTitle->getFullURL(), true, 301 );
		exit;
	}
}
