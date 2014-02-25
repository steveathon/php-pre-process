<?php

	global $RequestMade,$ResponseMade;
	
	if ( isset($_GET['debug']) ) {
		@error_reporting(E_ALL);
	}
	
	$RequestMade = array();
	$ResponseMade = array();
	
	$RequestMade['params'] = array();
	$ResponseMade['params'] = array();
	
	
	// We filter up here, because there's an issue with how fetchParam() works if we don't.
	if ( isset($_GET['filter']) && (is_array($_GET['filter']) || strlen($_GET['filter']) > 0 ) ) {
		$RequestMade['params']['filtered'] = 1;
	}
	else {
		$RequestMade['params']['filtered'] = 0;
	}
	
	if ( isset($_GET['filter']) ) {
		if ( is_array($_GET['filter']) ) {
			$Filters = "";
			foreach ($_GET['filter'] as $theFilter ) {
				if ( strlen($Filters) < 1 ) {
					$Filters .= $theFilter;
				}
				else {
					$Filters .= "|" . $theFilter;
				}
			}
			$_GET['filter'] = $Filters;
		}
		$RequestMade['params']['filter'] = $_GET['filter'];
		$ResponseMade['filter'] = $_GET['filter'];
	}
	
	class ProcEye {
		
		private $Params = array();
		private $intParams = array();
		private $strParams = array();
		public $Method = false;
		
		function __construct() {
			
			
			global $RequestMade,$ResponseMade;
			if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
				$this->Params = $_GET;
				$this->Method = 'get';
			}
			elseif ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
				$this->Params = $_POST + $_GET;
				if ( @count($_POST) > 0 ) {
					$this->Method = 'post';
					
					if ( $this->Params['signed_request'] && $_SERVER['HTTP_ORIGIN'] ) {
						$this->Facebook = TRUE;
					}
				}
			}
			else {
				$this->Params = array();
			}
			
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
			
			if ( isset($this->Params['num']) ) {
				if ( $this->Params['num'] > 0 && $this->Params['num'] <= 100 ) {
					$ResponseMade['params']['num'] = $this->fetchParam('num',validnum);
				}
				else {
					$ResponseMade['params']['num'] = 10;
					$this->Params['num'] = 10;
				}
			}
			else {
				$ResponseMade['params']['num'] = 10;
				$this->Params['num'] = 10;
			}
		
			
			if ( isset($this->Params['start']) ) {
				if ( $this->Params['start'] > 0 && ($this->Params['start'] + $this->Params['num'] ) <= 1000 ) {
					$ResponseMade['params']['start'] = $this->fetchParam('start',validstart);
				}
				else {
					$ResponseMade['params']['start'] = 0;
					$this->Params['start'] = 0;
				}
			}
			else {
				$ResponseMade['params']['start'] = 0;
				$this->Params['start'] = 0;
			}
			
			define ('validint','int');
			define ('validstring','string');
			define ('validlongtext','string');
			define ('validgeo','point');
			define ('validdouble','double');
		}
		
		function fetchParam($ParamName = NULL, $SanatizeAS = NULL) {
			$return = false;
			if ( isset($ParamName) && strlen($ParamName) > 0 && !is_numeric($ParamName) ) {
				if ( isset($this->Params[$ParamName]) ) {
					$return = $this->Params[$ParamName];
				}
			}
			return $return;
		}
		
		function hasPost() { 
			$return = false;
			if ( $this->Method == 'post' ) {
				$return = true;
			}
			return $return;
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
	
	$ProcEye = new ProcEye();
	
	// all fields sanitized for safety.
	$RequestMade['server'] = array();
	$ResponseMade['server'] = array();
	
	$RequestMade['params']['num'] = $ProcEye->fetchParam('num',validint);
	$RequestMade['params']['start'] = $ProcEye->fetchParam('start',validint);
	
	if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
		// Then for each match the request.
		$RequestMade['server']['method'] = 'get';
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'POST' ) {
		$RequestMade['server']['method'] = 'post';
	}
	
	// Is this a balanced connection;
	if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		$RequestMade['server']['client'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		$RequestMade['server']['bcache'] = $_SERVER['HTTP_CACHE_CONTROL'];
		$RequestMade['server']['balancer'] = $_SERVER['REMOTE_ADDR'];
	}
	else {
		$RequestMade['server']['client'] = $_SERVER['REMOTE_ADDR'];
	}
	
	$RequestMade['server']['host'] = $_SERVER['HTTP_HOST'];
	$RequestMade['server']['useragent'] = urlencode($_SERVER['HTTP_USER_AGENT']);
	$RequestMade['server']['accept'] = urlencode($_SERVER['HTTP_ACCEPT']);
	$RequestMade['server']['language'] = urlencode($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$RequestMade['server']['encode'] = urlencode($_SERVER['HTTP_ACCEPT_ENCODING']);
	$RequestMade['server']['charset'] = urlencode($_SERVER['HTTP_ACCEPT_CHARSET']);
	$RequestMade['server']['keepalive'] = $_SERVER['HTTP_KEEP_ALIVE'];
	$RequestMade['server']['connection'] = $_SERVER['HTTP_CONNECTION'];
	$RequestMade['server']['cookie'] = urlencode($_SERVER['HTTP_COOKIE']);
	$RequestMade['server']['cache'] = urlencode($_SERVER['HTTP_CACHE_CONTROL']);
	$RequestMade['server']['requestport'] = $_SERVER['SERVER_PORT'];
	$RequestMade['server']['baseurl'] = urlencode($_SERVER['REDIRECT_URL']);
	$RequestMade['server']['protocol'] = 'http'; // TODO: Fix this and make it support HTTPS;s
	$RequestMade['server']['query'] = urlencode($_SERVER['QUERY_STRING']);
	$RequestMade['server']['request'] = urlencode($_SERVER['REQUEST_URI']);
	$RequestMade['server']['time'] = $_SERVER['REQUEST_TIME'];
	$RequestMade['server']['argv'] = urlencode(serialize($_SERVER['argv']));
	
	
	if ( $ProcEye->fetchParam('output',validstring) ) {
		$RequestMade['params']['output'] = $ProcEye->fetchParam('output',validstring);
		if ( $RequestMade['params']['output'] == 'xml' || $RequestMade['params']['output'] == 'html' || $RequestMade['params']['output'] == 'json' ) {
			$ResponseMade['params']['output'] = $RequestMade['params']['output'];
		}
	}
	
	// host, http/s, url, uri, clean
	$RequestMade['location'] = array();
	// lat, long, center, bounds, geo name, reference, etc.
	
	if ( is_array($Geo->Client) ) {
		$GeoClient = $Geo->Client;
		$GeoClientEnc = serialize($GeoClient);
		$RequestMade['location'] = $GeoClient;
	}
	
	// output, q, filter, sort, num, start, bounds, key, signed
	// layer, site, order
	
	if ( isset($_GET['q'] ) ) {
		if ( strlen($_GET['q']) > 0 ) {
			$RequestMade['params']['q'] = $_GET['q'];
		}
	}
	
	// Sort (if any)
	if ( isset($_GET['sort'] ) ) {
		if ( strlen($_GET['sort']) > 0 ) {
			if ( @count(@explode(':',$_GET['sort'])) == 2 ) {
				$RequestMade['params']['sort'] = $_GET['sort'];
			}
			else {
				$RequestMade['params']['sort'] = 'since:d';
			}
		}
		else {
			$RequestMade['params']['sort'] = 'since:d';
		}
	}
	else {
		$RequestMade['params']['sort'] = 'since:d';
	}
	
	$ResponseMade['params']['sort'] = $RequestMade['params']['sort'];
		

	
	// Where to start the looking
	if ( isset($_GET['start']) ) {
		if ( strlen($_GET['start']) > 0 && is_numeric($_GET['start']) && $_GET['start'] > 0 && $_GET['start'] < 1000 ){
			$RequestMade['params']['start'] = (int)$_GET['start'];
		}
		else { 
			$RequestMade['params']['start'] = 0;
		}
	}
	
	// Output
	if ( isset($_GET['output']) ) {
		if ( $_GET['output'] == 'json' ) {
			$RequestMade['params']['output'] = 'json';
			$ResponseMade['params']['output'] = 'json';
		}
		elseif ( $_GET['output'] == 'xml' ) {
			$RequestMade['params']['output'] = 'xml';
			$ResponseMade['params']['output'] = 'xml';
		}
		elseif ( $_GET['output'] == 'js' ) {
			$RequestMade['params']['output'] = 'js';
			$ResponseMade['params']['output'] = 'js';
		}
		elseif ( $_GET['output'] == 'php' ) {
			$RequestMade['params']['output'] = 'php';
			$ResponseMade['params']['output'] = 'php';
		}
		else {
			$ResponseMade['params']['output'] = 'html';
		}
	}
	else {
		$ResponseMade['params']['output'] = 'html';
	}
	
	// API KEY
	if ( isset($_GET['apikey']) && strlen($_GET['apikey']) > 0 ) {
		$RequestMade['apikey'] = $_GET['apikey'];
	}
	
	
	// Signature.
	if ( isset($_GET['signature']) && strlen($_GET['signature']) > 0 ) {
		$RequestMade['signature'] = $_GET['signature'];
	}