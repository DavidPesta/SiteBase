<?php

/*
* Copyright (c) 2012 David Pesta, https://github.com/DavidPesta/SiteBase
* Licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/

class View
{
	protected $_pageView = null;
	
	public function setPageView( $pageView )
	{
		$this->_pageView = $pageView;
	}
	
	public function show()
	{
		ob_start( 'ob_gzhandler' );
		
		if( ! isset( $this->_pageView ) ) throw new Exception( "You must call addPageView() for your view object to configure page view data" );
		
		if( ! is_array( $this->_pageView ) ) include SOURCE . "/" . $this->_pageView;
		elseif( isset( $this->_pageView[ 'layout' ] ) ) include SOURCE . "/" . $this->_pageView[ 'layout' ];
		elseif( isset( $this->_pageView[ 'content' ] ) ) include SOURCE . "/" . $this->_pageView[ 'content' ];
		else throw new Exception( "Page view data is not configured properly in your view object" );
		
		ob_end_flush();
	}
	
	public function content( $viewTag )
	{
		if( isset( $this->_pageView[ $viewTag ] ) ) include SOURCE . "/" . $this->_pageView[ $viewTag ];
		else throw new Exception( "File tag '" . $viewTag . "' not found in page view data" );
	}
}
