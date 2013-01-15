<?php

/*
* Copyright (c) 2012-2013 David Pesta, https://github.com/DavidPesta/SiteBase
* This file is licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/

function checkErrors() {
	$lastError = error_get_last();
	
	if( $lastError !== null ) {
		$errors = array(
			'lastError' => $lastError,
			'GET' => $_GET,
			'POST' => $_POST,
			'SESSION' => $_SESSION,
			'COOKIE' => $_COOKIE,
			'SERVER' => $_SERVER,
			'backTrace' => debug_backtrace()
		);
		
		if( ! is_dir( ERRORS ) ) mkdir( ERRORS, 0777, true );
		
		list( $seconds, $micro ) = explode( ".", microtime( true ) );
		$dateTime = date( "Y-m-d H:i:s", $seconds ) . "." . $micro;
		
		$content = "------------------------\n" . $dateTime . "\n\n";
		$content .= var_export( $errors, true );
		$content .= "\n\n";
		
		$fileHash = md5( serialize( $lastError ) );
		
		file_put_contents( ERRORS . "/" . $fileHash . ".log", $content, FILE_APPEND );
	}
}

register_shutdown_function( 'checkErrors' );

if( strpos( $_SERVER[ 'REQUEST_URI' ], "?" ) !== false ) {
	list( $url, $params ) = explode( "?", $_SERVER[ 'REQUEST_URI' ] );
	parse_str( $params, $_GET );
	$_REQUEST = array_merge( $_REQUEST, $_GET );
}
else {
	$url = $_SERVER[ 'REQUEST_URI' ];
}

include "../bootstrap.php";

$controller = str_replace( ".php", "", trim( $url, "/" ) );
define( "CONTROLLER", $controller );

if( is_file( SOURCE . "/" . CONTROLLER . ".php" ) ) include SOURCE . "/" . CONTROLLER . ".php";
elseif( is_file( SOURCE . "/" . CONTROLLER . "/index.php" ) ) include SOURCE . "/" . CONTROLLER . "/index.php";
else throw new Exception( "Controller not found for \"" . CONTROLLER . "\"" );
