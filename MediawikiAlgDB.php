<?php
# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/algdb/MediawikiAlgDB.php" );
EOT;
	exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'MediawikiAlgDB',
	'author' => 'Conrad Rider',
	'url' => 'http://localhost/mediawiki/index.php/Special:MediawikiAlgDB',
	'descriptionmsg' => "{{mediawikialgdb-desc}}",
	'version' => '1.1.0',
);

$wgAutoloadClasses['SpecialMediawikiAlgDB'] = __DIR__ . '/SpecialMediawikiAlgDB.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgMessagesDirs['MediawikiAlgDB'] = __DIR__ . "/i18n/"; # Location of localisation files (Tell MediaWiki to load them)
$wgExtensionMessagesFiles['MediawikiAlgDBAlias'] = __DIR__ . '/MediawikiAlgDB.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)
$wgSpecialPages['MediawikiAlgDB'] = 'SpecialMediawikiAlgDB'; # Tell MediaWiki about the new special page and its class name
