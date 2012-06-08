<?php

/*
* Copyright (c) 2012 David Pesta, https://github.com/DavidPesta/SiteBase
* Licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/

class View
{
	protected $_variables = array();
	protected $_pageView = null;
	protected $_components = null;
	protected $_curViewTag = null;
	protected $_assets = array();
	protected $_scripts = array();
	protected $_viewTagAssetHashes = null;
	protected $_viewTagScriptHashes = null;
	protected $_document = null;
	
	public function __construct( $pageView = null, $components = null )
	{
		if( $pageView != null ) $this->addPageView( $pageView );
		if( $components != null ) $this->addComponents( $components );
	}
	
	public function __set( $name, $value )
	{
		$this->_variables[ $name ] = $value;
	}
	
	public function __get( $name )
	{
		return $this->_variables[ $name ];
	}
	
	public function __isset( $name )
	{
		return isset( $this->_variables[ $name ] );
	}
	
	public function __unset( $name )
	{
		unset( $this->_variables[ $name ] );
	}
	
	public function getVariables()
	{
		return $this->_variables;
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
		
		if( ! isset( $this->_assets[ $this->_curViewTag ] ) ) $this->_assets[ $this->_curViewTag ] = array();
		if( ! isset( $this->_scripts[ $this->_curViewTag ] ) ) $this->_scripts[ $this->_curViewTag ] = array();
		
		if( isset( $this->_pageView[ $viewTag ] ) ) $this->safeInclude( SOURCE . "/" . $this->_pageView[ $viewTag ] );
		else throw new Exception( "File tag '" . $viewTag . "' not found in page view data" );
		
		$this->_curViewTag = $prevViewTag;
	}
	
	public function image( $image, $style = "default", $useDataUri = null )
	{
		if( is_bool( $useDataUri ) !== true ) $useDataUri = USE_DATA_URIS;
		
		// We can add multiple different kinds of $style presets here, where "default" is the default preset
		if( "default" == $style ) {
			if( true === $useDataUri ) $style = " style=\"display: inline-block;\""; // This style represents standard image behavior
			else $style = "";
		}
		else $style = " style=\"" . $style . "\"";
		
		if( true === $useDataUri ) {
			$this->_assets[ $this->_curViewTag ][ $image ] = $image; // Using $image as the array key prevents redundancy
			return "<div" . $style . " class=\"" . $this->_curViewTag . "-image-" . md5( $image ) . "\"></div>";
		}
		else {
			$this->ensureImageInWebroot( $image );
			return "<img src=\"/images/" . ltrim( $image, "/" ) . "\"" . $style . ">";
		}
	}
	
	public function resource( $resource, $useDataUri = null )
	{
		if( is_bool( $useDataUri ) !== true ) $useDataUri = USE_DATA_URIS;
		
		if( true === $useDataUri ) {
			return $this->dataURI( $resource );
		}
		else {
			$this->ensureImageInWebroot( $resource );
			return "/images/" . ltrim( $resource, "/" );
		}
	}
	
	public function dataURI( $resource )
	{
		$imgData = getimagesize( SOURCE . "/" . $resource );
		
		$rawImage = file_get_contents( SOURCE . "/" . $resource );
		$base64 = base64_encode( $rawImage );
		
		return "data:" . $imgData[ 'mime' ] . ";base64," . $base64;
	}
	
	public function ensureImageInWebroot( $image )
	{
		$source = SOURCE . "/" . ltrim( $image, "/" );
		$destination = WEBROOT . "/images/" . ltrim( $image, "/" );
		
		if( true !== PRODUCTION || ! is_file( $destination ) ) {
			$imagePath = pathinfo( $destination, PATHINFO_DIRNAME );
			if( ! is_dir( $imagePath ) ) mkdir( $imagePath, 0777, true );
			copy( $source, $destination );
		}
	}
	
	public function addCSS( $css )
	{
		$this->_assets[ $this->_curViewTag ][ $css ] = $css; // Using $css as the array key prevents redundancy
	}
	
	public function addJS( $js )
	{
		$this->_scripts[ $this->_curViewTag ][ $js ] = $js; // Using $js as the array key prevents redundancy
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
			
			if( true !== PRODUCTION || ! is_file( $filename ) ) {
				$css = "";
				
				foreach( $this->_assets[ $viewTag ] as $asset ) {
					if( "css" == strtolower( pathinfo( $asset, PATHINFO_EXTENSION ) ) ) {
						$css .= "\n" . file_get_contents( SOURCE . "/" . $asset ) . "\n";
					}
					elseif( "pcss" == strtolower( pathinfo( $asset, PATHINFO_EXTENSION ) ) ) {
						ob_start();
						$this->safeInclude( SOURCE . "/" . $asset );
						$css .= "\n" . ob_get_contents() . "\n";
						ob_clean();
					}
					else {
						$imgData = getimagesize( SOURCE . "/" . $asset );
						
						$rawImage = file_get_contents( SOURCE . "/" . $asset );
						$base64 = base64_encode( $rawImage );
						
						$css .= "
							." . $viewTag . "-image-" . md5( $asset ) . " {
								width: " . $imgData[ 0 ] . "px;
								height: " . $imgData[ 1 ] . "px;
								background-image: url(\"data:" . $imgData[ 'mime' ] . ";base64," . $base64 . "\");
							}
						";
					}
				}
				
				if( ! is_dir( WEBROOT . "/css" ) ) mkdir( WEBROOT . "/css", 0777, true );
				file_put_contents( $filename, $css );
			}
		}
		
		$viewTagScriptHashes = $this->getViewTagScriptHashes();
		
		foreach( $viewTagScriptHashes as $viewTag => $hash ) {
			$filename = WEBROOT . "/js/" . $viewTag . "-" . $hash . ".js";
			
			if( true !== PRODUCTION || ! is_file( $filename ) ) {
				$js = "";
				
				foreach( $this->_scripts[ $viewTag ] as $script ) {
					if( "js" == strtolower( pathinfo( $script, PATHINFO_EXTENSION ) ) ) {
						$js .= "\n" . file_get_contents( SOURCE . "/" . $script ) . "\n";
					}
					elseif( "pjs" == strtolower( pathinfo( $script, PATHINFO_EXTENSION ) ) ) {
						ob_start();
						$this->safeInclude( SOURCE . "/" . $script );
						$js .= "\n" . ob_get_contents() . "\n";
						ob_clean();
					}
				}
				
				if( ! is_dir( WEBROOT . "/js" ) ) mkdir( WEBROOT . "/js", 0777, true );
				file_put_contents( $filename, $js );
			}
		}
	}
	
	protected function renderHead()
	{
		$viewTagAssetHashes = $this->getViewTagAssetHashes();
		
		$head = "";
		
		foreach( $viewTagAssetHashes as $viewTag => $hash ) {
			$head .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/" . $viewTag . "-" . $hash . ".css\" />\n";
		}
		
		$viewTagScriptHashes = $this->getViewTagScriptHashes();
		
		foreach( $viewTagScriptHashes as $viewTag => $hash ) {
			$head .= "<script type=\"text/javascript\" src=\"/js/" . $viewTag . "-" . $hash . ".js\"></script>\n";
		}
		
		$this->_document = str_replace( "<INSERT_HEAD_DATA>", $head, $this->_document );
	}
	
	protected function getViewTagAssetHashes()
	{
		if( null == $this->_viewTagAssetHashes ) {
			$viewTagAssetHashes = array();
			
			foreach( $this->_assets as $viewTag => $assets ) {
				ksort( $assets );
				if( ! empty( $assets ) ) $viewTagAssetHashes[ $viewTag ] = md5( implode( ":", $assets ) );
			}
			
			$this->_viewTagAssetHashes = $viewTagAssetHashes;
			
			return $viewTagAssetHashes;
		}
		else {
			return $this->_viewTagAssetHashes;
		}
	}
	
	protected function getViewTagScriptHashes()
	{
		if( null == $this->_viewTagScriptHashes ) {
			$viewTagScriptHashes = array();
			
			foreach( $this->_scripts as $viewTag => $scripts ) {
				ksort( $scripts );
				if( ! empty( $scripts ) ) $viewTagScriptHashes[ $viewTag ] = md5( implode( ":", $scripts ) );
			}
			
			$this->_viewTagScriptHashes = $viewTagScriptHashes;
			
			return $viewTagScriptHashes;
		}
		else {
			return $this->_viewTagScriptHashes;
		}
	}
	
	protected function safeInclude( $fileForSafeInclude )
	{
		extract( $this->_variables );
		include $fileForSafeInclude;
	}
}
