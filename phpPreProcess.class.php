<?php

	/**
	 * @author Steve King <steve@stevenking.com.au>
	 * 
	 * phpPreProcess
	 * 
	 * Work in progress.
	 *
	 */

	class phpPreProcess {
		
		private $Params = array();
		private $intParams = array();
		private $strParams = array();
		public $Method = false;
		
		function __construct() {
			
			if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
				$this->Params = $_GET;
				$this->Method = 'get';
			}
			elseif ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
				$this->Params = $_POST + $_GET;
				if ( @count($_POST) > 0 ) {
					$this->Method = 'post';
					// Support for you know what.
					if ( $this->Params['signed_request'] && $_SERVER['HTTP_ORIGIN'] ) {
						$this->Facebook = TRUE;
					}
				}
			}
			else {
				$this->Params = array();
			}
			
			if ( isset($this->Params['num']) ) {
				if ( $this->Params['num'] > 0 && $this->Params['num'] <= 100 ) {
					$this->Params['num'] = $this->fetchParam('num',validnum);
				}
				else {
					$this->Params['num'] = 10;
				}
			}
			else {
				$this->Params['num'] = 10;
			}
			
			if ( isset($this->Params['start']) ) {
				// This is a pagination protection step
				if ( $this->Params['start'] > 0 && ($this->Params['start'] + $this->Params['num'] ) <= 1000 ) {
					$this->Params['start'] = $this->fetchParam('start',validstart);
				}
				else {
					$this->Params['start'] = 0;
				}
			}
			else {
				$this->Params['start'] = 0;
			}
			
			define ('validint','int');
			define ('validstring','string');
			define ('validlongtext','string');
			define ('validgeo','point');
			define ('validdouble','double');
			
			// Detect the auth password
			if ( isset($_SERVER['PHP_AUTH_USER']) ) {
				$this->_Service['authn']['http']['username'] = $_SERVER['PHP_AUTH_USER'];
				if ( isset($_SERVER['PHP_AUTH_PW']) ) {
					$this->_Service['authn']['http']['password'] = $_SERVER['PHP_AUTH_PW'];
				}
			}
			
			// Dropping this down to capture at the end of all runs
			if ( is_array($this->Params) && @count($Params) > 0 ) {
				foreach(array_keys($this->Params) as $theParam) {
					if ( is_numeric($this->Params[$theParam]) ) {
						$this->intParams[] = $this->Params[$theParam];
					}
					elseif ( is_string($this->Params[$theParam]) ) {
						$this->strParams[] = $this->Params[$theParam];
					}
				}
			}
			
		}
		
		function fetchParam($ParamName = NULL, $SanatizeAS = NULL) {
			if ( isset($ParamName) && strlen($ParamName) > 0 && !is_numeric($ParamName) ) {
				if ( isset($this->Params[$ParamName]) ) {
					return $this->Params[$ParamName];
				}
			}
			return false;
		}
		
		function hasPost() { 
			if ( $this->Method == 'post' ) {
				return true;
			}
			return false;
		}
		
		function uploadedFiles() {
			if ( isset($_FILES) && @count($_FILES) > 0 ) {
				$FileImports = array();
				foreach ( array_keys($_FILES) as $theFile ) {
					if ( is_array($_FILES[$theFile]['name']) ) {
						$FileImports[$theFile] = array();
						// Ok, there's an array of them
						foreach ( array_keys($_FILES[$theFile]['name']) as $namedKey ) {
							if ( $_FILES[$theFile]['error'][$namedKey] == UPLOAD_ERR_NO_FILE ) {
								// Do nothing
							}
							elseif ( $_FILES[$theFile]['error'][$namedKey] == 0 ) {
								$theUploadedFile = array ( 'name' => $_FILES[$theFile]['name'][$namedKey],
															'tmp_name' => $_FILES[$theFile]['tmp_name'][$namedKey],
															'error' => $_FILES[$theFile]['error'][$namedKey],
															'size' => $_FILES[$theFile]['size'][$namedKey],
															'type' => $_FILES[$theFile]['type'][$namedKey]);
								$FileImports[$theFile][] = $theUploadedFile;
							}
							
						}
					}
					
					return $FileImports;
				}
			}
			return false;
		}
		
		function cleanAll() {
			$CleanArray = array();
			foreach ( array_keys($this->Params) as $ParamFetched ) {
				$CleanArray[$ParamFetched]['mysql'] = mysql_real_escape_string($this->Params[$ParamFetched]);
				$CleanArray[$ParamFetched]['string'] = (string)$this->Params[$ParamFetched];
				$CleanArray[$ParamFetched]['int'] = (int)$this->Params[$ParamFetched];
				$CleanArray[$ParamFetched]['bool'] = (bool)$this->Params[$ParamFetched];
			}
			$this->cleanParams = $CleanArray;
		}
		
	}