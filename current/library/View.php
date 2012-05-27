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
	protected $_components = null;
	
	public function __construct( $pageView = null, $components = null )
	{
		if( $pageView != null ) $this->addPageView( $pageView );
		if( $components != null ) $this->addComponents( $components );
	}
	
	public function addPageView( $pageView )
	{
		if( ! is_array( $pageView ) ) $pageView = array( "content" => $pageView );
		if( ! is_array( $this->_pageView ) ) $this->_pageView = array();
		$this->_pageView = array_merge( $this->_pageView, $pageView );
	}
	
	public function addComponents( $components )
	{
		if( ! is_array( $components ) ) $components = array( $components );
		if( ! is_array( $this->_components ) ) $this->_components = array();
		$this->_components = array_merge( $this->_components, $components );
	}
	
	public function addComponent( $component )
	{
		$this->addComponents( $component );
	}
	
	public function show()
	{
		ob_start( 'ob_gzhandler' );
		
		if( ! is_array( $this->_pageView ) ) throw new Exception( "You must call addPageView() for your view object to configure page view data" );
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
