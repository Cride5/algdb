<?php

	// MySQL databse
	$DB_NAME="<CHANGE_ME>";
	// MySQL database username
	$DB_USERNAME="<CHANGE_ME>";
	// MySQL database password
	$DB_PASSWORD="<CHANGE_ME>";

	// Location of visual cube image generator
	$VISCUBE="http://<CHANGE_ME>/visualcube.php";
	
	// Whether to use as a mediawiki extension (if not it runs as a standalone app)
	$USING_WIKI = true;

	//-------------------[ Wiki specific config ]--------------------

	// Full url of this script on the wiki
	$WIKI_PAGE = "http://<CHANGE_ME>/index.php/Special:MediawikiAlgDB";

	//---------------[ Standalone specific config ]-------------------
	// Administrative users (provides upload access, viewing access is public)
	// To log in as the given user add the user=<user> argument to the URL
	// for example: http://www.mysite.com/algdb/algdb.php?user=admin
	$USERS = Array(
		"admin"
	);
