<?php

/*
* Copyright (c) 2012 David Pesta, https://github.com/DavidPesta/SiteBase
* Licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/

class View
{
	//ob_start( 'ob_gzhandler' );
	// Page rendering should use gzip for data sent to client, but ajax calls that don't use view shouldn't
	// This is because pages are generally large where gzip cuts down on size a lot, for less bandwidth
	// But ajax calls are generally tiny where gzip overhead actually makes the size larger, for more bandwidth
	//ob_end_flush();
}
