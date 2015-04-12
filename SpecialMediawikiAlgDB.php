<?php
class SpecialMediawikiAlgDB extends SpecialPage {
	
	function __construct() {
		parent::__construct('MediawikiAlgDB');
	}
 
	function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
 
		# Get request data from, e.g.
		$param = $request->getText( 'param' );

		$GLOBALS['ALGDB_OUTPUT'] = $output;
		
		require 'algdb.php';
		
	}
}