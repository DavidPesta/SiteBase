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
	protected $_curViewTag = null;
	protected $_images = array();
	protected $_viewTagAssetHashes = null;
	protected $_document = null;
	
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
		
		ob_start();
		
		if( ! is_array( $this->_pageView ) ) throw new Exception( "You must call addPageView() for your view object to configure page view data" );
		elseif( isset( $this->_pageView[ 'layout' ] ) ) $this->content( 'layout' );
		elseif( isset( $this->_pageView[ 'content' ] ) ) $this->content( 'content' );
		else throw new Exception( "Page view data is not configured properly in your view object" );
		
		$this->_document = ob_get_contents();
		
		ob_clean();
		
		$this->buildCache();
		$this->renderHead();
		
		echo $this->_document;
		
		ob_end_flush();
	}
	
	public function content( $viewTag )
	{
		$prevViewTag = $this->_curViewTag;
		$this->_curViewTag = $viewTag;
		
		if( ! is_array( $this->_images[ $this->_curViewTag ] ) ) $this->_images[ $this->_curViewTag ] = array();
		
		if( isset( $this->_pageView[ $viewTag ] ) ) include SOURCE . "/" . $this->_pageView[ $viewTag ];
		else throw new Exception( "File tag '" . $viewTag . "' not found in page view data" );
		
		$this->_curViewTag = $prevViewTag;
	}
	
	public function image( $image, $style = "default" )
	{
		$this->_images[ $this->_curViewTag ][ $image ] = $image; // Using $image as the array key prevents redundancy
		
		// We can add multiple different kinds of $style presets here, where "default" is the default preset
		if( "default" == $style ) $style = " style=\"display: inline-block;\""; // This style represents standard image behavior
		else $style = " style=\"" . $style . "\"";
		
		return "<div" . $style . " class=\"" . $this->_curViewTag . "-image-" . md5( $image ) . "\"></div>";
	}
	
	public function head()
	{
		return "<INSERT_HEAD_DATA>";
	}
	
	protected function buildCache()
	{
		$viewTagAssetHashes = $this->getViewTagAssetHashes();
		
		foreach( $viewTagAssetHashes as $viewTag => $hash ) {
			$filename = WEBROOT . "/css/" . $viewTag . "-" . $hash . ".css";
			
			if( ! is_file( $filename ) ) {
				$css = "";
				
				foreach( $this->_images[ $viewTag ] as $image ) {
					$imgData = getimagesize( SOURCE . "/" . $image );
					
					$rawImage = file_get_contents( SOURCE . "/" . $image );
					$base64 = base64_encode( $rawImage );
					
					$css .= "
						." . $viewTag . "-image-" . md5( $image ) . " {
							width: " . $imgData[ 0 ] . "px;
							height: " . $imgData[ 1 ] . "px;
							background-image: url(\"data:" . $imgData[ 'mime' ] . ";base64," . $base64 . "\");
						}
					";
				}
				
				file_put_contents( $filename, $css );
			}
		}
	}
	
	protected function getViewTagAssetHashes()
	{
		if( null == $this->_viewTagAssetHashes ) {
			$viewTagAssetHashes = array();
			
			foreach( $this->_images as $viewTag => $images ) {
				ksort( $images );
				if( ! empty( $images ) ) $viewTagAssetHashes[ $viewTag ] = md5( implode( ":", $images ) );
			}
			
			$this->_viewTagAssetHashes = $viewTagAssetHashes;
			
			return $viewTagAssetHashes;
		}
		else {
			return $this->_viewTagAssetHashes;
		}
	}
	
	protected function renderHead()
	{
		$viewTagAssetHashes = $this->getViewTagAssetHashes();
		
		$head = "";
		
		foreach( $viewTagAssetHashes as $viewTag => $hash ) {
			$head .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/" . $viewTag . "-" . $hash . ".css\" />\n";
		}
		
		$this->_document = str_replace( "<INSERT_HEAD_DATA>", $head, $this->_document );
	}
}
