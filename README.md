# AlgDB
A database system for organising Rubik's cube algorithms.

### Installation Instructions

##### Standalone

 1. Extract the [algdb](https://github.com/Cride5/visualcube) folder to your web space
 3. Edit the algdb_config.php config file
 2. Extract the [visualcube](https://github.com/Cride5/visualcube) folder at the same level
 3. Edit the visualcube_config.php config file
 3. Visit the algdb page with the 'install' parameter. For example:
        http://www.mysite.com/algdb/algdb.php?install




##### As a MediaWiki Extension
Tested on [MediaWiki](http://www.mediawiki.org) version 1.19.14

 1. Extract the [algdb](https://github.com/Cride5/visualcube) folder into your 'extensions' folder in your mediawiki installation
 2. Download the additional cube_lib.php file from the [visualcube](https://github.com/Cride5/visualcube) project as follows:
```
cd <algdb_folder>
rm cube_lib.php
wget https://raw.githubusercontent.com/Cride5/visualcube/master/cube_lib.php
```
 3. Edit the algdb_config.php config file
 4. Edit the LocalSettings.php of the MediaWiki installation and add the following to the end:
```
if(!$wgCommandLineMode){ require_once "$IP/extensions/algdb/MediawikiAlgDB.php";}
```
 5. Visit the installer page to set up the database:
```
http://www.mysite.com/wiki/index.php/Special:MediawikiAlgDB?install
```
