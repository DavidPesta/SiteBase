<?php

/*
* Copyright (c) 2012-2013 David Pesta, https://github.com/DavidPesta/SiteBase
* This file is licensed under the MIT License.
* You should have received a copy of the MIT License along with this program.
* If not, see http://www.opensource.org/licenses/mit-license.php
*/

class Page {
	private static $_cssOrder = [];
	private static $_jsOrder = [];
	
	protected $_statics = [ "html", "ico", "png", "jpg", "gif" ];
	
	protected $_variables = [];
	protected $_viewFiles = [];
	protected $_document = null;
	protected $_cssMd5 = null;
	protected $_jsMd5 = null;
	
	public static function init( $settings = [] ) {
		$settings += array(
			'cssOrder' => [],
			'jsOrder'  => []
		);
		
		self::$_cssOrder = $settings[ 'cssOrder' ];
		self::$_jsOrder = $settings[ 'jsOrder' ];
	}
	
	public function __construct( $viewFiles = null ) {
		if( $viewFiles != null ) $this->addViewFiles( $viewFiles );
		
		$serialized = file_get_contents( DATA . "/Page/resources.md5" );
		if( false === $serialized ) {
			$this->compileScan();
			return;
		}
		
		$resources = unserialize( $serialized );
		if( false === $resources ) {
			$this->compileScan();
			return;
		}
		
		$this->_cssMd5 = $resources[ 'cssMd5' ];
		$this->_jsMd5 = $resources[ 'jsMd5' ];
		
		if( ! is_file( WEBROOT . "/" . $this->_cssMd5 . ".css" ) || ! is_file( WEBROOT . "/" . $this->_jsMd5 . ".js" ) ) {
			$this->compileScan();
			return;
		}
		
		if( false === PRODUCTION ) {
			$this->compileScan();
			return;
		}
	}
	
	public function __set( $name, $value ) {
		$this->_variables[ $name ] = $value;
	}
	
	public function __get( $name ) {
		return $this->_variables[ $name ];
	}
	
	public function __isset( $name ) {
		return isset( $this->_variables[ $name ] );
	}
	
	public function __unset( $name ) {
		unset( $this->_variables[ $name ] );
	}
	
	public function getVariables() {
		return $this->_variables;
	}
	
	public function addViewFiles( $viewFiles ) {
		if( ! is_array( $viewFiles ) ) $viewFiles = [ "content" => $viewFiles ];
		$this->_viewFiles = array_merge( $this->_viewFiles, $viewFiles );
	}
	
	public function show() {
		ob_start();
		
		if( ! is_array( $this->_viewFiles ) ) throw new Exception( "You must call addViewFiles() for your view object to configure page view data" );
		elseif( isset( $this->_viewFiles[ 'layout' ] ) ) $this->content( 'layout' );
		elseif( isset( $this->_viewFiles[ 'content' ] ) ) $this->content( 'content' );
		else throw new Exception( "Page view data is not configured properly in your view object" );
		
		$this->_document = ob_get_contents();
		
		ob_end_clean();
		
		if( true === PRODUCTION ) ob_start( 'ob_gzhandler' );
		echo $this->_document;
		if( true === PRODUCTION ) ob_end_flush();
	}
	
	public function content( $viewTag ) {
		if( isset( $this->_viewFiles[ $viewTag ] ) && file_exists( SOURCE . "/" . $this->_viewFiles[ $viewTag ] ) ) {
			extract( $this->_variables );
			include SOURCE . "/" . $this->_viewFiles[ $viewTag ];
		}
		else {
			throw new Exception( "File tag '" . $viewTag . "' not found in page view data" );
		}
	}
	
	public function folder() {
		return str_replace( SOURCE, "", dirname( debug_backtrace()[ 0 ][ 'file' ] ) );
	}
	
	public function compileScan() {
		// Test to make sure the coffee and less compilers are accessible
		
		// TODO: The following is very platform and configuration specific! This needs to be moved into a config file or generalized to just "coffee --help"
		exec( "C:\\npm\\coffee --help", $coffeeTest );
		$coffeeTest = implode( $coffeeTest );
		if( false === strpos( $coffeeTest, "coffee [options]" ) ) {
			throw new Exception( "CoffeeScript compiler could not be found in Page.compileScan" );
		}
		
		// TODO: The following is very platform and configuration specific! This needs to be moved into a config file or generalized to just "lessc --help"
		exec( "C:\\npm\\lessc --help", $lessTest );
		$lessTest = implode( $lessTest );
		if( false === strpos( $lessTest, "lessc [options]" ) ) {
			throw new Exception( "LESS compiler could not be found in Page.compileScan" );
		}
		
		// Empty out the webroot folder of everything except the "router.php" file
		
		$directory = new RecursiveDirectoryIterator( WEBROOT );
		$iterator = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );
		foreach( $iterator as $filePath => $fileObject ) {
			if( basename( $filePath ) == "router.php" ) continue;
			if( is_file( $filePath ) ) unlink( $filePath );
			if( is_dir( $filePath ) ) rmdir( $filePath );
		}
		
		// Recursively scan through every folder in "source" and process files into local arrays for further processing and eventual placement into webroot
		
		$cssFiles = [];
		$jsFiles = [];
		$directory = new RecursiveDirectoryIterator( SOURCE );
		$iterator = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );
		foreach( $iterator as $filePath => $fileObject ) {
			$ext = strtolower( pathinfo($filePath, PATHINFO_EXTENSION) );
			$relPath = str_replace( "\\", "/", str_replace( SOURCE, "", $filePath ) );
			
			if( in_array( $ext, $this->_statics ) ) {
				$source = $filePath;
				$destination = str_replace( SOURCE, WEBROOT, $filePath );
				$folder = dirname( $destination );
				if( ! is_dir( $folder ) ) mkdir( $folder, 0777, true );
				copy( $source, $destination );
			}
			
			if( "css" == $ext ) {
				$cssFiles[ $relPath ] = file_get_contents( $filePath );
			}
			
			if( "js" == $ext ) {
				$jsFiles[ $relPath ] = file_get_contents( $filePath );
			}
			
			if( "less" == $ext ) {
				exec( "C:\\npm\\lessc -x \"$filePath\"", $lessCompiled );
				$cssFiles[ $relPath ] = implode( "\n", $lessCompiled );
			}
			
			if( "coffee" == $ext ) {
				exec( "C:\\npm\\coffee -p \"$filePath\"", $coffeeCompiled );
				$jsFiles[ $relPath ] = implode( "\n", $coffeeCompiled );
			}
		}
		
		// Build the CSS document string that shall become the contents of the CSS file
		
		$cssString = "";
		
		foreach( self::$_cssOrder as $relPath ) {
			if( $cssString != "" ) $cssString .= " ";
			$cssString .= $cssFiles[ $relPath ];
			unset( $cssFiles[ $relPath ] );
		}
		
		foreach( $cssFiles as $cssFile ) {
			if( $cssString != "" ) $cssString .= " ";
			$cssString .= $cssFile;
		}
		
		// Build the JS document string that shall become the contents of the JS file
		
		$jsString = "";
		
		foreach( self::$_jsOrder as $relPath ) {
			if( $jsString != "" ) $jsString .= " ";
			$jsString .= $jsFiles[ $relPath ];
			unset( $jsFiles[ $relPath ] );
		}
		
		foreach( $jsFiles as $jsFile ) {
			if( $jsString != "" ) $jsString .= " ";
			$jsString .= $jsFile;
		}
		
		// Keep track of the md5 signatures of the CSS and JS document strings
		
		$this->_cssMd5 = md5( $cssString );
		$this->_jsMd5 = md5( $jsString );
		
		// Save the CSS and JS files with the md5 signatures as the filenames
		
		file_put_contents( WEBROOT . "/" . $this->_cssMd5 . ".css", $cssString );
		file_put_contents( WEBROOT . "/" . $this->_jsMd5 . ".js", $jsString );
		
		// Save the md5 signatures of the CSS and JS files where it can be found later
		
		if( ! is_dir( DATA . "/Page" ) ) mkdir( DATA . "/Page", 0777, true );
		$resources = [
			"cssMd5" => $this->_cssMd5,
			"jsMd5" => $this->_jsMd5
		];
		file_put_contents( DATA . "/Page/resources.md5", serialize( $resources ) );
	}
}
