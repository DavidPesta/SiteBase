<?php

/*
* Copyright (c) 2012-2013 David Pesta, https://github.com/DavidPesta/SiteBase
* This file is licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/


// Load configuration

include __DIR__ . "/../config.php";


// Define constants

define( "ROOT", __DIR__ );
define( "LIBRARY", ROOT . DIRECTORY_SEPARATOR . "library" );
define( "SOURCE", ROOT . DIRECTORY_SEPARATOR . "source" );
define( "WEBROOT", ROOT . DIRECTORY_SEPARATOR . "webroot" );
define( "DATA", ROOT . DIRECTORY_SEPARATOR . "data" );
define( "ERRORS", ROOT . DIRECTORY_SEPARATOR . "errors" );


// Error Reporting

error_reporting( E_ALL & ~ ( E_STRICT | E_NOTICE ) );
if( PRODUCTION == true ) {
	ini_set( "display_errors", 0 );
	ini_set( "display_startup_errors", 0 );
}
else {
	ini_set( "display_errors", 1 );
	ini_set( "display_startup_errors", 1 );
}


// Miscellaneous initializations

ini_set( "max_execution_time", 300 );
date_default_timezone_set( "America/Chicago" );


// Include the libraries you wish to use for all controllers


// Instantiate objects and initialize static classes you wish to use for all controllers
