<?php
/*
	File: algdb.php
	Date: 24/10/09
	Author(s): Conrad Rider (www.crider.co.uk)
	Description: Database for storing Rubik's cube algorithms

	This file is part of AlgDB.

	AlgDB is free software: you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	AlgDB is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public License
	along with AlgDB.  If not, see <http://www.gnu.org/licenses/>.
	
	Copyright (C) 2009 Conrad Rider


*/

	// Import config
	require "algdb_config.php";

	$GLOBALS['ALGDB_USING_WIKI'] = $USING_WIKI;
	
	$VERSION = "1.1.0";
	$UPDATED = "12 Apr 2015";
	
	$can_add = false;
	$can_mod = false;

	// Configure for wiki use
	if($USING_WIKI){
		global $wgRequest, $wgOut, $wgUser;
//		print_r($wgUser->getGroups());
		$can_add = $wgUser->isAllowed('edit');// in_array('user', $wgUser->getGroups());
		$can_mod = in_array('sysop', $wgUser->getGroups())
			|| in_array('bureaucrat', $wgUser->getGroups())
			|| in_array('developer', $wgUser->getGroups());


		$SCRIPT=$WIKI_PAGE;

	}else{
		$SCRIPT="algdb.php";
	}

	
	// Libraries
	require "algdb_lib.php";
	require "cube_lib.php";
	
	

	// --------------------------------[ Constant Defs ]-------------------------
	
	// XML definition
	$HTML_DEF = '<?xml version="1.0" encoding="iso-8859-1"?>'."\n".
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"'."\n".
            '   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
            
	$PUZZLES = Array(
//		1 => "Square-1",
		2 => "2x2x2",
		3 => "3x3x3",
//		4 => "4x4x4",
//		5 => "5x5x5",
//		6 => "6x6x6",
//		7 => "7x7x7",
//		101 => "Pyraminx",
//		103 => "Megaminx",
//		104 => "Gigaminx"
		);
		
		
	$MODES = Array("view", "groups", "cases", "algs", "info", "add", "bulk");
	
	$VIEWS = Array("default", "cube", /*"masked",*/ "transparent");
	
	// Used to determine whether AUF moves can be thrown away
	global $IS_LL;
	$IS_LL = Array('OLL' => true, 'PLL' => true, 'CLL' => true, 'ELL' => true, 'CMLL' => true, 'COLL' => true, 'ELS' => false, 'CLS' => false, 'ZBLL-T' => true, 'ZBLL-U' => true, 'ZBLL-L' => true, 'ZBLL-H' => true, 'ZBLL-Pi' => true, 'ZBLL-S' => true, 'ZBLL-AS' => true);

	// Connect to db
	mysql_connect("localhost", $DB_USERNAME, $DB_PASSWORD) or die("Unable to connect to database. USER:$DB_USERNAME");
	@mysql_select_db($DB_NAME) or die("Unable to select database");

	// Retrive admin variablbes
	if($USING_WIKI && $can_mod
	|| (!$USING_WIKI && array_key_exists('user', $_REQUEST) && in_array($_REQUEST['user'], $USERS))){
		$user = $_REQUEST['user'];
		$can_mod = true;
		$can_add = true;
		
		// Admin stuff...
		
		// Case management
		if(array_key_exists('ucid', $_REQUEST)
		&& array_key_exists('ugid', $_REQUEST)){
			$ucid = $_REQUEST['ucid'];
			$ugid = $_REQUEST['ugid'];
			if(array_key_exists('new_cname', $_REQUEST)){
				$query = "UPDATE algdb_cases SET case_name='".mysql_escape_string($_REQUEST['new_cname']).
				"' WHERE case_id=$ucid AND group_id=$ugid";
//println("UPDATE case_name: $query");
				mysql_query($query);
			}
	//	println($_REQUEST['new_cid']." uci=".$ucid);
			if(array_key_exists('new_cid', $_REQUEST) && $ucid != $_REQUEST['new_cid']){
				$new_cid = $_REQUEST['new_cid'];
	//println("case id stuff ");
				// If case ID less than 0 then delete case
				if($new_cid < 0){
					// Retrive all tags associated with case and delete
					$case_tags = get_array('mem_id', "SELECT mem_id FROM algdb_algmem WHERE algdb_algmem.case_id=$ucid AND algdb_algmem.group_id=$ugid");
					foreach($case_tags as $memid){
						$query = "DELETE FROM algdb_tags WHERE algdb_tags.mem_id=$memid";
						mysql_query($query);
					}
					// delete all associations with alg database (may create orphaned algs)
					$query = "DELETE FROM algdb_algmem WHERE case_id=$ucid AND group_id=$ugid";
					mysql_query($query);
					// delete case
					$query = "DELETE FROM algdb_cases WHERE case_id=$ucid AND group_id=$ugid";
					mysql_query($query);
	//	println($query);
				}
				// Otherwise, reposition
				else{
					// Check if a case already occupies the case id
					$query = "SELECT case_id FROM algdb_cases WHERE case_id=$new_cid AND group_id=$ugid";
					$swap_required = mysql_num_rows(mysql_query($query)) > 0;
					// Move case occupying position to end of table
					if($swap_required){
						// first find next available id in group
						$query = "SELECT MAX(case_id) FROM algdb_cases WHERE group_id=$ugid";
						$next_cid = get_array('MAX(case_id)', $query); $next_cid = $next_cid[0] + 1;
						// Move case there
						$query = "UPDATE algdb_algmem,algdb_cases SET algdb_algmem.case_id=$next_cid,algdb_cases.case_id=$next_cid".
							" WHERE algdb_cases.case_id=$new_cid AND algdb_cases.group_id=$ugid".
							" AND algdb_cases.case_id=algdb_algmem.case_id AND algdb_cases.group_id=algdb_algmem.group_id";
		//println("UPDATE case_id A: $query");
						mysql_query($query);
					}
					// Move object case into position
					$query = "UPDATE algdb_algmem,algdb_cases SET algdb_algmem.case_id=$new_cid,algdb_cases.case_id=$new_cid".
						" WHERE algdb_cases.case_id=$ucid AND algdb_cases.group_id=$ugid".
						" AND algdb_cases.case_id=algdb_algmem.case_id AND algdb_cases.group_id=algdb_algmem.group_id";
		//println("UPDATE case_id C: $query");
					mysql_query($query);
					// Move case swapped out into original case posiiton
					if($swap_required){
						$query = "UPDATE algdb_algmem,algdb_cases SET algdb_algmem.case_id=$ucid,algdb_cases.case_id=$ucid".
							" WHERE algdb_cases.case_id=$next_cid AND algdb_cases.group_id=$ugid".
							" AND algdb_cases.case_id=algdb_algmem.case_id AND algdb_cases.group_id=algdb_algmem.group_id";
		//println("UPDATE case_id B: $query");
						mysql_query($query);
					}
				}
			}
		}
		
		// Alg management
		if($can_mod && array_key_exists('del_alg', $_REQUEST)){
			$query = "DELETE FROM algdb_algs WHERE alg_id=".$_REQUEST['del_alg'].
			" AND alg_id NOT IN (SELECT ref_alg FROM algdb_cases WHERE ref_alg=".
			$_REQUEST['del_alg'].")";
			mysql_query($query);
		}
	}
	
	// Install DB if requested
	if($can_mod && array_key_exists('install', $_REQUEST)){
		/** Base table for storing algs
		All algs must be unique */
		$result = mysql_query('CREATE TABLE IF NOT EXISTS algdb_algs(
			alg_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			moves VARCHAR(100) NOT NULL,
			stm TINYINT UNSIGNED NOT NULL,
			htm TINYINT UNSIGNED NOT NULL,
			qtm TINYINT UNSIGNED NOT NULL,
			gen TINYINT UNSIGNED NOT NULL,
			PRIMARY KEY(alg_id),
			UNIQUE KEY(moves));');
		if (!$result){
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query;
			die($message);
		}
	
		


		/** Base table for storing algorithm groups
		Group name colissions are acceptable,
		so long as they are for different puzzles.
		If a groups is 'closed' it means it is not
		possible to add new cases to it */
		mysql_query('CREATE TABLE IF NOT EXISTS algdb_groups(
			group_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			group_name VARCHAR(20) NOT NULL,
			puzzle_id INT UNSIGNED NOT NULL,
			is_closed BOOL NOT NULL DEFAULT 0,
			PRIMARY KEY(group_id),
			UNIQUE KEY(group_name, puzzle_id))');


		/** Base table for storing specific cases within groups
		For each case there is an associated alg, which allows 
		any alg to be identified as belonging to it (within the context of that group)
		the state brought about by the alg is also stored to facilate state searches
		Cases may optionally be assigned names */
		mysql_query('CREATE TABLE IF NOT EXISTS algdb_cases(
			case_id INT UNSIGNED NOT NULL,
			group_id INT UNSIGNED NOT NULL,
			ref_alg INT UNSIGNED NOT NULL,
			state INT UNSIGNED NOT NULL,
			case_name VARCHAR(20),
			PRIMARY KEY(case_id, group_id),
			FOREIGN KEY(ref_alg) REFERENCES algdb_algs(alg_id),
			FOREIGN KEY(group_id) REFERENCES algdb_groups(group_id))');


		/** Membership of algs to groups 
		One alg may be in a number of groups,
		One group has many algs
		An alg/group tuple, may or may not belong to a single case */
		mysql_query('CREATE TABLE IF NOT EXISTS algdb_algmem(
			mem_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			alg_id INT UNSIGNED NOT NULL,
			group_id INT UNSIGNED NOT NULL,
			case_id INT UNSIGNED,
			PRIMARY KEY(mem_id),
			UNIQUE KEY(alg_id, group_id),
			FOREIGN KEY(case_id) REFERENCES algdb_cases(case_id),
			FOREIGN KEY(group_id) REFERENCES algdb_groups(group_id),
			FOREIGN KEY(alg_id) REFERENCES algdb_algs(alg_id))');


		/** Stores all tags associeated with each
		alg in a particular group. For the same
		alg in a different group, differnt tags will apply */
		mysql_query('CREATE TABLE IF NOT EXISTS algdb_tags(
			mem_id INT UNSIGNED NOT NULL,
			tag VARCHAR(20) NOT NULL,
			PRIMARY KEY(mem_id, tag),
			FOREIGN KEY (mem_id) REFERENCES algdb_algmem(mem_id))');
		
		printlh("CREATED DATABASE TABLES");

		// Insert base data
		mysql_query('INSERT INTO algdb_groups(group_name, puzzle_id, is_closed) VALUES
			("OLL", 2, 0),
			("PBL", 2, 0),
			("CLL", 2, 0),
			("OLL", 3, 0),
			("PLL", 3, 0),
			("CLL", 3, 0),
			("ELL", 3, 0),
			("CLS", 3, 0),
			("ELS", 3, 0),
			("CMLL", 3, 0),
			("COLL", 3, 0),
			("ZBLL-T", 3, 0),
			("ZBLL-U", 3, 0),
			("ZBLL-L", 3, 0),
			("ZBLL-H", 3, 0),
			("ZBLL-Pi", 3, 0),
			("ZBLL-S", 3, 0),
			("ZBLL-AS", 3, 0);');
		
		printlh("INSERTED BASE DATA");

		return;
	}


	// Retrive request variables
	if(array_key_exists('mode', $_REQUEST)
	&& in_array($_REQUEST['mode'], $MODES)) $mode = $_REQUEST['mode'];
	if(!$mode) $mode = $MODES[0];
	if(array_key_exists('view', $_REQUEST)
	&& in_array($_REQUEST['view'], $VIEWS)) $view = $_REQUEST['view'];
	if(!$view) $view = $VIEWS[0];
	if(array_key_exists('puzzle', $_REQUEST)) $puzzle = $_REQUEST['puzzle'];
	if(!array_key_exists($puzzle, $PUZZLES)) $puzzle = null;
	if(array_key_exists('tags', $_REQUEST)) $tags = preg_split('/,/', $_REQUEST['tags']);
	// Retrive alg insertion stuff
	if(array_key_exists('check', $_REQUEST)) $check_alg = true;
	if(array_key_exists('moves', $_REQUEST)) $moves = $_REQUEST['moves'];
	
	if($puzzle){
		// Get all alg groups for puzzle
		$groups = get_array("group_name", "SELECT group_id, group_name, is_closed FROM algdb_groups WHERE ".
			"puzzle_id=$puzzle ORDER BY group_id;");
		// Retrive group var from user
		if($groups){
			if(array_key_exists('group', $_REQUEST)) $group = $_REQUEST['group'];
			if(!in_array($group, $groups)) $group = null;
		}
	}
	
	if($puzzle && $group){
		// Get group ID and closed status
		$grp_details = get_arrays("SELECT group_id, group_name, is_closed FROM algdb_groups ".
			"WHERE puzzle_id=$puzzle AND group_name='$group'");
		$group_id = $grp_details[0]['group_id'];
		$g_closed = $grp_details[0]['is_closed'];

		// Get all cases for group
		$cases = get_arrays("SELECT case_id, case_name FROM algdb_cases, algdb_groups WHERE ".
			"algdb_cases.group_id=algdb_groups.group_id AND ".
			"algdb_groups.puzzle_id=$puzzle AND ".
			"algdb_groups.group_name='$group' GROUP BY case_id");
		
		// Set basic view config
		if($group == 'OLL')
			$vopts .= "&amp;sch=wddddd";
		// Set custom view optoins
		if($view){
			// Set masking
			if($view == 'default' || $view == 'masked' || $view == 'transparent' || $view == 'cube'){
				if($group == 'OLL' || $group == 'PLL' || substr($group,0,4) == 'ZBLL' || $group == 'ELL' || ($group == 'CLL' && $puzzle == 2))
					$vopts .= "&amp;stage=ll";
				else if($group == 'CMLL' || $group == 'CLL' && $puzzle == 3)
					$vopts .= "&amp;stage=cll";
				else if($group == 'COLL')
					$vopts .= "&amp;stage=coll";
			}
			// Set plan view if requried
			if($view == 'default'){
				if($group == 'OLL' || $group == 'PLL' || $group == 'CLL' || $group == 'COLL'
				|| $group == 'CMLL' || substr($group,0,4) == 'ZBLL' || $group== 'ELL')
					$vopts .= "&amp;view=plan";
				if($group == 'PBL')
					$vopts .= "&amp;co=30";
			}
			if($view == 'transparent'){
				$vopts .= "&amp;view=trans";
			}
		}
		
		// Retrive case variable from user
		if($cases){
			if(array_key_exists('cid', $_REQUEST)) $cid = $_REQUEST['cid'];
			if(!in_array2($cid, 'case_id', $cases)) $cid = null;
			else{	// Retrive case name if it exists
				foreach($cases as $c){
					if($c['case_id'] == $cid){
						$case = $c['case_name'];
						break;
					}
				}
			}
			// Retrive case from case name as a fallback
			if(!$cid){
				if(array_key_exists('case', $_REQUEST)) $case = $_REQUEST['case'];
				if(!in_array2($case, 'case_name', $cases)) $case = null;
			}
		}
	}
	
	
	// Start page render
	if(!$USING_WIKI){
		printnl($HTML_DEF);

		println('<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
		<head>
			<title>Algorithm Database (v<?php echo $VERSION; ?>)</title>
			<meta name="description"        content="A public database of puzzle algorithms"/>
			<meta name="keywords"           content="cube, puzzle, algorithm, rubik\'s, rubiks, algorithm database, algs, alg database, algdb, alg db, cube algs, cube algorithms, rubix, rubics, rubiks cube"/>
			<meta name="resource-type"      content="document"/>
			<meta name="language"           content="English"/>
			<meta name="rating"             content="general"/>
			<meta name="robots"             content="all"/>
			<meta name="expires"            content="never"/>
			<meta name="revisit-after"      content="14 days"/>
			<meta name="distribution"       content="global"/>
			<meta name="author"             content="Conrad Rider"/>
			<meta name="copyright"          content="Copyright Â© 2000-2008 Conrad Rider"/>
			<meta http-equiv="Content-Type" content="text/html; iso-8859-1"/>
		</head>
		<body>');
	}
	println('
			<style media="screen" type="text/css">
				@import url("algdb_style.css");
				table{
	margin:50px auto;
}
.list_table{
	border-spacing:0;
	
}
.rtn_cell{
	color:#888;
	text-align:right;
}
#case_table a{
	text-decoration:none;
}
table th{
	border:1px solid silver;
	padding:3px 5px;
}
table td{
	border:1px solid silver;
	padding:3px 5px;
}
#case_table{
	border-spacing:30px 20px;
}
img{	border:0; }
em{
	font-style:normal;
	font-weight:bold;
}
form .form_num{
	width:20px;
	height:20px;
}
form .form_short{
	width:50px;
	height:20px;

}
			</style>');
	
	// NAVIGATION ===================================================================
	println("\t\t<div id=\"nav\" class=\"toc\" style=\"display:inline-block\">");

	// validate mode
	if($mode == "add" && (!$puzzle || !$group || !$can_add)) $mode="view";
//printnl("puzzle=$puzzle, group=$group");

	// Append moves if in add mode
	if($mode == 'add' && $moves){
		$user_alg = "&amp;moves=".urlencode($moves);
		if($check_alg) $user_alg .= "&amp;check=1";
	}
	// Append admin user
	if($user){
		$user_logged = "&amp;user=$user";
	}

	$first = true;
	printnl("\t\t\tMode:\n");
	foreach($MODES as $mname){
		if(($mname == "add" || $mname == "bulk") && (!$puzzle || !$group || !$can_add)) continue;
		if(!$can_mod && ($mname == "groups" || $mname == "cases" || $mname == "algs")) continue;
		if($first) $first = false; else printnl("\n\t\t\t|\n");
		printnl("\t\t\t");
		if($mname != $mode) printnl("<a href='$SCRIPT?mode=$mname&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group$user_logged'>");
		else printnl("<em>");
		printnl($mname);
		if($mname != $mode) printnl("</a>");
		else printnl("</em>");
	}
	$first = true;
	printlh("\n\t\t\t");
	printnl("\t\t\tView:\n");
	foreach($VIEWS as $vname){
		if($first) $first = false; else printnl("\n\t\t\t|\n");
		printnl("\t\t\t");
		if($vname != $view) printnl("<a href='$SCRIPT?mode=$mode&amp;view=$vname&amp;puzzle=$puzzle&amp;group=$group$user_logged$user_alg'>");
		else printnl("<em>");
		printnl($vname);
		if($vname != $view) printnl("</a>");
		else printnl("</em>");
	}
	$first = true;
	printnl("\n\t\t\t<br/>\n");
	printnl("\t\t\tPuzzle: <a href='$SCRIPT?mode=$mode&amp;view=$view$user_logged'>All</a>\n");
	foreach($PUZZLES as $i => $pname){
		if($first) $first = false; else printnl("\n\t\t\t|\n");
		printnl("\t\t\t");
		if($i == $puzzle) printnl("<em>");
		printnl("<a href='$SCRIPT?mode=".($mode=="insert" ? "view" : $mode)."&amp;view=$view&amp;puzzle=$i$user_logged'>");
		printnl($pname);
		printnl("</a>");
		if($i == $puzzle) printnl("</em>");
	}
	printnl("\n\t\t\t<br/>\n");
	if($puzzle && $groups){
		$first = true;
		printnl("\t\t\tGroup:\n");
		foreach($groups as $gname){
			if($gname == 'ZBLL') continue; // Exclude ZBLL since it is broken up by OCLL case
			if($first) $first = false; else printnl("\n\t\t\t|\n");
			printnl("\t\t\t");
			$selected = $gname == $group;
			$nolink = $selected && ((($mode == "view" || $mode == "cases") && !$cid) || $mode == "groups" || $mode == "algs");
			if($selected) printnl("<em>");
			if(!$nolink)
				printnl("<a href='$SCRIPT?mode=".
				((($mode == "add" || $mode == "bulk")&& $group == $gname) || $mode == "info" ? "view" : $mode).
				"&amp;view=$view&amp;puzzle=$puzzle&amp;group=$gname".
				($mode=='add' && !$check_alg && $group != $gname ? "&amp;check=1" : '')."$user_logged$user_alg'>");
			printnl($gname);
			if(!$nolink) printnl("</a>");
			if($selected) printnl("</em>");
		}
		printnl("\n");
	}
	printnl("\t\t</div>\n\t\t<br/><br/><br/>\n");
	
	// CONTENT =================================================================
	printnl("\t\t<div id=\"algdb_content\">");

	//-------------------------------------------------[ ALG INSERTION ]---------------------------------------------------------  
	if(($mode == "add" || $mode == "bulk") && $puzzle && $group && $can_add){
		if($mode == "add") println("\n\t\t\t".genhead("Add $group Alg"));
		else println("\n\t\t\t".genhead("Upload $group Algs"));

	if($moves){
		if(!$puzzle || !$group) return;
		if($mode == "add"){
			$moves = format_alg($moves);
			printlh(insertAlg($moves, $puzzle, $group, $group_id, $check_alg, $view, $user_logged));
		}
		else{
			$mv_array = preg_split('/[\n\r]+/', $moves);
			printlh("<ol>");
			foreach($mv_array as $k => $mv){
				$mv = format_alg($mv);
				printlh("<li>" . $mv . " => " . insertAlg($mv, $puzzle, $group, $group_id, $check_alg, $view, $user_logged) . "</li>");
			}
			printlh("</ol>");
		}
		global $moves; // Set $moves to global value
		printlh("\t\t\t<br/><br/>\n");		
	}
	
	//-------------------------------------------------[ ALG INPUT FORM ]---------------------------------------------------------
	if(!($moves && $mode=="bulk")){
		if($mode=="bulk") println('			Enter algs, one per line:<br/><br/>');
	
		printnl(
	'			<form class="form" id="alg_input" action="'."$SCRIPT?mode=$mode".'" method="post" onsubmit="">
				<div class="form_content">
					<!--input type="hidden" name="mode" value="insert"/-->
					<input type="hidden" name="puzzle" value="'.$puzzle.'"/>
					<input type="hidden" name="view" value="'.$view.'"/>
					<input type="hidden" name="group" value="'.$group.'"/>');
		if($user)
			printnl("\t\t\t\t\t\t<input type=\"hidden\" name=\"user\" value=\"$user\"/>");

		printnl('
					<div class="form_record">
						<div class="data">
							'.($mode == "add" ? '<input type="text" name="moves" value="'.($failed || $check_alg ? expand_alg($moves) : '').'" size="50" maxlength="100"/>'
							:
							'<textarea name="moves" rows="20" cols="100" style="width:400px"></textarea>') .'
						</div>
					</div>
					<div class="form_submit">'.($mode == "add" ? '<input type="submit" name="check" value="Check"/>' : '').'<input type="submit" value="Add"/></div>
				</div>
				</form>');
			
		if($moves){
			printnl("<img src=\"$VISCUBE?fmt=png&amp;bg=w&amp;pzl=$puzzle$vopts&amp;case=$moves\"/>");
		}
	}
	if($mode == "add" || $moves)
		printnl("<div style='text-align:center'><em><a href='$SCRIPT?mode=bulk&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group$user_logged'>".($moves && $mode != "add" ? 'back to ' : '')."bulk upload</a></em></div>");
	else
		printnl("<div style='text-align:center'><em><a href='$SCRIPT?mode=add&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group$user_logged'>add single alg</a></em></div>");
	//-------------------------------------------------[ DISPLAY ALG BROWSER ]----------------------------------------------------
	}else if($mode == "algs"){ 
		printnl("\n\t\t\t".genhead("Algorithm Browser"));
		printnl('
			<table class="list_table">
				<tr><th>Puzzle</th><th>Group</th><th>Case</th><th>Case Name</th><th>Algorithm</th><th>Tags</th><th/></tr>');
	
		$select_fields = "algdb_groups.puzzle_id, algdb_groups.group_name, algdb_cases.case_id, algdb_cases.ref_alg, algdb_cases.case_name, algdb_algmem.mem_id, algdb_algs.alg_id, algdb_algs.moves";
	
		$tables_from = "algdb_algs, algdb_groups, algdb_cases, algdb_algmem";
	
		$join = "algdb_algmem.alg_id=algdb_algs.alg_id AND ".
			"algdb_algmem.group_id=algdb_groups.group_id AND ".
			"algdb_algmem.case_id=algdb_cases.case_id AND ".
			"algdb_cases.group_id=algdb_groups.group_id";

		$conds = $join;

		if($puzzle) $conds .= " AND algdb_groups.puzzle_id=$puzzle";
	
		if($group) $conds .= " AND algdb_groups.group_name='$group'";
	
		if($cid) $conds .= " AND algdb_cases.case_id=$cid";
		else if($case) $conds .= " AND algdb_cases.case_name='$case'";
	
		if($tags && count($tags) > 0){
			$tables_from .= ", algdb_tags";
			$conds .= " AND algdb_algmem.mem_id=algdb_tags.mem_id AND (algdb_tags.tag='".$tags[0]."'";
			foreach($tags as $i => $tag){
				$conds .= " OR algdb_tags.tag='$tag'";
			}
			$conds .= ")";
		}
	
		$algs_query = "SELECT $select_fields FROM $tables_from WHERE $conds ORDER BY algdb_groups.puzzle_id, algdb_groups.group_name, algdb_cases.case_id, algdb_algs.alg_id";
	
	//	printnl($algs_query);
	
		$algs_rows = mysql_query($algs_query);
	//	$algs_count = mysql_numrows($algs_rows);
		while($record = mysql_fetch_array($algs_rows, MYSQL_ASSOC)){
			$amid = $record['mem_id'];
			$pname = $PUZZLES[$record['puzzle_id']];
			$gname = $record['group_name'];
			$cid   = $record['case_id'];
			$cname = $record['case_name'];
			$ref_alg = $record['ref_alg'];
			$alg_id = $record['alg_id'];
			$alg = expand_alg($record['moves']);
			$tlist = get_list('tag', "SELECT algdb_tags.tag FROM algdb_tags WHERE mem_id=$amid");
			if(!$tlist) $tlist = "&nbsp;";
	//		$pzl = $PUZZLES[mysql_result($algs_rows, $i, 'puzzle_id')];
			printnl("\t\t\t\t<tr><td>$pname</td><td>$gname</td><td>$cid</td><td>$cname</td><td>$alg</td><td>$tlist</td>\n");
			if($can_mod && $ref_alg != $alg_id){
				printnl("<td><a href=\"$SCRIPT?mode=$mode&amp;view=$view&amp;puzzle=$puzzle".
					"&amp;group=$group&amp;del_alg=$alg_id$user_logged\">x</a></td>");
			}else{
				printnl("<td/>");
			}
			printnl("</tr>\n");
		}
		printnl(
'			</table>
			<h2>Orphaned Algs</h2>
			<table class="list_table">
				<tr><th>Alg ID</th><th>Algorithm</th>'.($can_mod ? "<th>&nbsp</th>" : '').'</tr>');

		$algs_query = "SELECT alg_id, moves FROM algdb_algs WHERE alg_id NOT IN (SELECT algdb_algs.alg_id FROM $tables_from WHERE $join)";
		$algs_rows = mysql_query($algs_query);
	//	$algs_count = mysql_numrows($algs_rows);
		while($record = mysql_fetch_array($algs_rows, MYSQL_ASSOC)){
			$alg_id = $record['alg_id'];
			$alg = expand_alg($record['moves']);
	//		$pzl = $PUZZLES[mysql_result($algs_rows, $i, 'puzzle_id')];
			printnl("\t\t\t\t<tr><td>$alg_id</td><td>$alg</td>");
			if($can_mod){
				printnl("<td><a href=\"$SCRIPT?mode=$mode&amp;view=$view&amp;puzzle=$puzzle".
					"&amp;group=$group&amp;del_alg=$alg_id$user_logged\">x</a></td>");
			}
			printnl("</tr>\n");
		}
		printnl('
			</table>');
	}else if($mode == "cases"){
	//-------------------------------------------------[ CASE BROWSER ]---------------------------------------------------------
		printnl("\n\t\t\t".genhead("Case Browser"));
		if(!$cid){
			println('
			<table class="list_table">
				<tr><th>Puzzle</th><th>Group</th><th>Case</th><th>Case Name</th>
				<th>Algs</th><th>State</th>'.($can_mod ? "<th>Admin</th>" : '').'</tr>');

		$select_fields = "algdb_groups.puzzle_id, algdb_groups.group_name, algdb_groups.group_id, algdb_cases.case_id, algdb_cases.case_name, algdb_cases.state,".
		" count(algdb_algmem.mem_id) AS alg_count";
	
		$tables_from = "algdb_groups, algdb_cases, algdb_algs, algdb_algmem";
	
		$conds = "algdb_algmem.alg_id=algdb_algs.alg_id AND ".
			"algdb_algmem.group_id=algdb_groups.group_id AND ".
			"algdb_algmem.case_id=algdb_cases.case_id AND ".
			"algdb_cases.group_id=algdb_groups.group_id";

		if($puzzle) $conds .= " AND algdb_groups.puzzle_id=$puzzle";
	
		if($group) $conds .= " AND algdb_groups.group_name='$group'";
	
		if($cid) $conds .= " AND algdb_cases.case_id=$cid";
		else if($case) $conds .= " AND algdb_cases.case_name='$case'";
	
		$query = "SELECT $select_fields FROM $tables_from WHERE $conds GROUP BY algdb_groups.group_id, algdb_cases.case_id ORDER BY algdb_groups.puzzle_id, algdb_groups.group_name, algdb_cases.case_id";
	
	//	printnl($query);
	
		$rows = mysql_query($query);
		while($record = mysql_fetch_array($rows, MYSQL_ASSOC)){
			$pname = $PUZZLES[$record['puzzle_id']];
			$gname = $record['group_name'];
			$cid   = $record['case_id'];
			$gid   = $record['group_id'];
			$cname = $record['case_name'];
			$cstat = $record['state'];
			$nalgs = $record['alg_count'];
			printnl("\t\t\t\t<tr><td>$pname</td><td>$gname</td><td>".
				"<a href=\"$SCRIPT?mode=$mode&amp;view=$view&amp;puzzle=".$record['puzzle_id'].
				"&amp;group=$gname&amp;cid=$cid$user_logged\">#$cid</a></td><td>$cname </td><td>$nalgs</td><td>$cstat</td>");
			if($can_mod){
				println('
					<td>
					<form class="form" id="case_edit" action="'."$SCRIPT?action=case".'" method="get" onsubmit="">
					<div class="form_content">
						<input type="hidden" name="mode"   value="'.$mode.'"/>
						<input type="hidden" name="puzzle" value="'.$puzzle.'"/>
						<input type="hidden" name="view"   value="'.$view.'"/>
						<input type="hidden" name="group"  value="'.$group.'"/>
						<input type="hidden" name="user"   value="'.$user.'"/>
						<input type="hidden" name="ucid"  value="'.$cid.'"/>
						<input type="hidden" name="ugid"  value="'.$gid.'"/>
						<input class="form_short" type="text"   name="new_cname" maxlength="20" value="'.$cname.'"/>
						<input class="form_num" type="text"   name="new_cid" maxlength="5" value="'.$cid.'"/>
						<input type="submit" value="Do"/>
					</div>
					</form>
					</td>');

			}
			printnl("\t\t\t\t</tr>\n");
		}
		printnl("\t\t\t</table>");
	}else{
		printnl('
			<h2>Case: '.$PUZZLES[$puzzle]." / $group / #$cid  $case".'</h2>
			<table class="list_table">
				<tr><th>Alg ID</th><th>Algorithm</th>'.($can_mod ? "<th>&nbsp</th>" : '').'</tr>');

		// Retrive alg id associated with case
		$case_alg = get_array('ref_alg', "SELECT ref_alg FROM algdb_cases WHERE case_id=$cid AND group_id=$group_id"); $case_alg = $case_alg[0];

		$conds = "algdb_algmem.alg_id=algdb_algs.alg_id AND ".
			"algdb_algmem.group_id=algdb_groups.group_id AND ".
			"algdb_algmem.case_id=algdb_cases.case_id AND ".
			"algdb_cases.group_id=algdb_groups.group_id AND ".
			"algdb_groups.puzzle_id=$puzzle AND algdb_groups.group_id=$group_id AND algdb_cases.case_id=$cid";
		
		$query = "SELECT algdb_algs.alg_id, algdb_algs.moves FROM algdb_algs,algdb_cases,algdb_algmem,algdb_groups WHERE $conds";
		//println($query);
		$algs_rows = mysql_query($query);
		while($record = mysql_fetch_array($algs_rows, MYSQL_ASSOC)){
			$alg_id = expand_alg($record['alg_id']);
			$alg = expand_alg($record['moves']);
			printnl("\t\t\t\t<tr><td>$alg_id</td><td>$alg</td>");
			if($can_mod){
				printnl("<td>");
				if($alg_id == $case_alg) printnl("RA");
				else printnl("<a href=\"$SCRIPT?mode=$mode&amp;view=$view&amp;puzzle=$puzzle".
					"&amp;group=$group&amp;cid=$cid&amp;del_alg=$alg_id$user_logged\">x</a>");
				printnl("</td>");
			}
			printnl("</tr>\n");
		}
		printnl("\t\t\t</table>");
}

	//-------------------------------------------------[ GROUP BROWSER ]---------------------------------------------------------
	}else if($mode == "groups"){
		printnl("\n\t\t\t".genhead("Group Browser"));
		println('
			<table class="list_table">
				<tr><th>Puzzle</th><th>Group</th><th>Cases</th><th>Algs</th><th>Closed</th></tr>');
		$select_fields = "algdb_groups.puzzle_id, algdb_groups.group_name, algdb_groups.is_closed,".
		" count(distinct(algdb_cases.case_id)) AS case_count, count(algdb_algmem.mem_id) AS alg_count";
	
		$tables_from = "algdb_algs, algdb_groups, algdb_cases, algdb_algmem";
	
		$conds = "algdb_algmem.alg_id=algdb_algs.alg_id AND ".
			"algdb_algmem.group_id=algdb_groups.group_id AND ".
			"algdb_algmem.case_id=algdb_cases.case_id AND ".
			"algdb_cases.group_id=algdb_groups.group_id";

		if($puzzle) $conds .= " AND algdb_groups.puzzle_id=$puzzle";
	
		if($group) $conds .= " AND algdb_groups.group_name='$group'";
	
		$query = "SELECT $select_fields FROM $tables_from WHERE $conds GROUP BY algdb_groups.group_id ORDER BY algdb_groups.puzzle_id, algdb_groups.group_name";


	
	//	printnl($query);
	
		$rows = mysql_query($query);
	//	$algs_count = mysql_numrows($algs_rows);
		while($record = mysql_fetch_array($rows, MYSQL_ASSOC)){
			$pname = $PUZZLES[$record['puzzle_id']];
			$gname = $record['group_name'];
			$gclsd  = $record['is_closed'];
			$ncases = $record['case_count'];
			$nalgs = $record['alg_count'];
			printnl("\t\t\t\t<tr><td>$pname</td><td>$gname</td><td>$ncases</td><td/>$nalgs</td><td>".($gclsd ? "Y" : "&nbsp;")."</td></tr>\n");
		}
		printnl("\t\t\t</table>");

	//-------------------------------------------------[ DISPLAY CASE VIEWER ]--------------------------------------------------
	}else if($mode == "view"){ if($group){ if(!$cid && !$case){
		printnl("\n\t\t\t".genhead('Viewing: '.$PUZZLES[$puzzle]." / $group"));
		println('
			<table id="case_table">');

	//	printnl("\t\t\t\tView:\n");
	//	printnl("<a href='$SCRIPT?puzzle=$puzzle&amp;group=$group&amp;view='>3D</a>\n");
	//	printnl("<a href='$SCRIPT?puzzle=$puzzle&amp;group=$group&amp;view='>Plan</a>\n");

		$select_fields = "algdb_cases.case_id, algdb_cases.case_name, algdb_algs.moves";
	
		$tables_from = "algdb_algs, algdb_groups, algdb_cases";
	
		$conds = "algdb_algs.alg_id=algdb_cases.ref_alg AND ".
			"algdb_cases.group_id=algdb_groups.group_id";

		if($puzzle){
			if($conds) $conds .= " AND";
			$conds .= " algdb_groups.puzzle_id=$puzzle";
		}
	
		if($group) $conds .= " AND algdb_groups.group_name='$group'";

		if($tags && count($tags) > 0){
			$tables_from .= ", algdb_tags";
			$conds .= " AND algdb_algmem.mem_id=algdb_tags.mem_id AND (algdb_tags.tag='".$tags[0]."'";
			foreach($tags as $i => $tag){
				$conds .= " OR algdb_tags.tag='$tag'";
			}
			$conds .= ")";
		}

		$algs_query = "SELECT $select_fields FROM $tables_from WHERE $conds ORDER BY algdb_cases.case_id";
	
	//	printnl($algs_query);
	
		$algs_rows = mysql_query($algs_query);
		$MAX_COLS = 4;
		$col = 0;
		while($record = mysql_fetch_array($algs_rows, MYSQL_ASSOC)){
			$cid = $record['case_id'];
			$cname = $record['case_name'];
			$alg = expand_alg($record['moves']);
			if($col == 0) printnl("\t\t\t\t<tr>\n");
			printnl("\t\t\t\t\t<td>\n".
			"\t\t\t\t\t\t<a href='$SCRIPT?mode=$mode&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group&amp;cid=$cid$user_logged'>#$cid<br/>\n".
			"\t\t\t\t\t\t\t<img src=\"$VISCUBE?fmt=png&amp;bg=w&amp;size=100&amp;pzl=$puzzle$vopts&amp;case=$alg\" alt=\"$alg\"/>\n".
			"\t\t\t\t\t\t</a><br/>$cname<br/><!--$alg-->\n".
			"\t\t\t\t\t</td>\n");
			$col = ($col + 1) % $MAX_COLS;
			if($col == 0) printnl("\t\t\t\t</tr>\n");
		}
		if($col != 0) printnl("\t\t\t\t</tr>\n");
		printnl("\t\t\t</table>");

		if($can_add) printnl("<div style='text-align:center'><em><a href='$SCRIPT?mode=add&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group$user_logged$user_alg'>upload $group algorithm</a></em></div>");

	//-------------------------------------------------[ DISPLAY INDIVIDUAL CASE ]----------------------------------------------
	}else{ // group=true, and case=true 
		println("\n\t\t\t".genhead('Viewing: '.$PUZZLES[$puzzle]." / $group / #$cid  $case"));

	//	printnl("<a href=\"$SCRIPT?mode=".($mode=="add"? "view" : $mode)."&amp;puzzle=$puzzle&amp;group=$group\">Back to $group</a>");
	
		$query = "SELECT algdb_algs.moves FROM algdb_algs, algdb_cases WHERE ".
			"algdb_cases.group_id=$group_id AND algdb_algs.alg_id=algdb_cases.ref_alg AND algdb_cases.case_id=$cid";
	//	printnl($query);
		$alg_ref = get_array('moves', $query);
	//	print_r($alg_ref);
	//	printnl("asdf".$alg_ref[0]);
		printnl("\t\t\t<div style=\"text-align:center;margin:0 auto; border:5px black;\">\n");
		printnl("\t\t\t\t<img src=\"$VISCUBE?fmt=png&amp;bg=w&amp;size=160&amp;pzl=$puzzle$vopts&amp;case="
			.$alg_ref[0]."\" alt=\"$cid\"/>\n");
		printnl("\t\t\t</div>");
		println('
			<table>
				<tr><th>Rt</th><th>Alg</th><th>STM</th><th>HTM</th><th>QTM</th><th>GEN</th><th>Tags</th></tr>');

		$select_fields = "algdb_cases.case_name, algdb_algs.alg_id, algdb_algs.moves, algdb_algs.stm, algdb_algs.htm, algdb_algs.qtm, algdb_algs.gen, algdb_algmem.mem_id";
	
		$tables_from = "algdb_algs, algdb_cases, algdb_groups, algdb_algmem";
	
		$conds = "algdb_algmem.alg_id=algdb_algs.alg_id AND ".
			"algdb_algmem.group_id=algdb_groups.group_id AND ".
			"algdb_algmem.case_id=algdb_cases.case_id AND ".
			"algdb_cases.group_id=algdb_groups.group_id";

		if($puzzle) $conds .= " AND algdb_groups.puzzle_id=$puzzle";
	
		if($group) $conds .= " AND algdb_groups.group_name='$group'";
	
		if($cid) $conds .= " AND algdb_cases.case_id='$cid'";
		else if($case) $conds .= " AND algdb_cases.case_name='$case'";
	
		if($tags && count($tags) > 0){
			$tables_from .= ", algdb_tags";
			$conds .= " AND algdb_algs.mem_id=algdb_tags.mem_id AND (algdb_tags.tag='".$tags[0]."'";
			foreach($tags as $i => $tag){
				$conds .= " OR algdb_tags.tag='$tag'";
			}
			$conds .= ")";
		}
	
		$algs_query = "SELECT $select_fields FROM $tables_from WHERE $conds ORDER BY algdb_groups.puzzle_id, algdb_groups.group_name, algdb_cases.case_name, algdb_algs.htm, algdb_algs.qtm, algdb_algs.gen";
	
	//	printnl($algs_query);
	
		$GARRON_CUBE = Array(2 => '2x2x2', 3 => '3x3x3');
		$garron_cube = $GARRON_CUBE[$puzzle];
		$GARRON_STAGE = Array(
			'OLL' => 'OLL', 'PLL' => 'PLL', 'CLL' => 'CLL', 'ELL' => 'ELL', 
			'CMLL' => 'CMLL', 'COLL' => 'COLL', 'ELS' => 'ELS', 'CLS' => 'CLS',
			'ZBLL-T' => 'ZBLL', 'ZBLL-U' => 'ZBLL', 'ZBLL-L' => 'ZBLL', 'ZBLL-H' => 'ZBLL',
			'ZBLL-Pi' => 'ZBLL', 'ZBLL-S' => 'ZBLL', 'ZBLL-AS' => 'ZBLL');
		$garron_stage = $GARRON_STAGE[$group];
		$algs_rows = mysql_query($algs_query);
	//	$algs_count = mysql_numrows($algs_rows);
		while($record = mysql_fetch_array($algs_rows, MYSQL_ASSOC)){
			$mid = $record['mem_id'];
			$pname = $PUZZLES[$record['puzzle_id']];
			$gname = $record['group_name'];
			$cname = $record['case_name'];
			$alg = $record['moves'];
			$garron_alg = preg_replace('/ /', '+', preg_replace('/\'/', '-', expand_alg($alg)));
			$algt = preg_replace('/^([xyz][2\']?)+/', '', $alg);
			$algh = substr($alg, 0, strlen($alg) - strlen($algt));
			$algt = preg_replace('/([xyz][2\']?)+$/', '', $algt); // remove final rotations for display
			$algt = expand_alg($algt); $algh = expand_alg($algh);
			$stm = $record['stm'];
			$htm = $record['htm'];
			$qtm = $record['qtm'];
			$gen = $record['gen'];
	//printnl("mid=$mid");
			$tlist = get_list('tag', "SELECT algdb_tags.tag FROM algdb_tags WHERE algdb_tags.mem_id=$mid");
			if(!$tlist) $tlist = "&nbsp;";
	//		$pzl = $PUZZLES[mysql_result($algs_rows, $i, 'puzzle_id')];
			printnl("\t\t\t\t<tr><td class='rtn_cell'>$algh</td><td><a href='http://alg.garron.us/?scheme=brogwy&amp;cube=$garron_cube&amp;stage=$garron_stage&amp;animtype=solve&amp;alg=$garron_alg' title='play animation...'>$algt</a></td><td>$stm</td><td>$htm</td><td>$qtm</td><td>$gen</td><td>$tlist</td></tr>\n");
		}
		printnl("\t\t\t</table>");
		
		if($can_add) printnl("<div style='text-align:center'><em><a href='$SCRIPT?mode=add&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group$user_logged$user_alg'>upload $group algorithm</a></em></div>");

	//-------------------------------------------------[ NO GROUP/CASE SELECTED]------------------------------------------------
	}
}else{ // group=false 
	if(!$puzzle) printnl("\n\t\t\tSelect puzzle...");
		else printnl("\n\t\t\tSelect group...");
}
	//-------------------------------------------------[ DISPLAY INFO ]--------------------------------------------------------- 
	}else if($mode == "info"){
		printnl("\n\t\t\t".genhead("About"));
		printnl('
			<em>Version ' . $VERSION . ' - last updated ' . $UPDATED . '</em>
			<br/><br/>To report a problem, request a feature, or for help using the system please use <a href="http://www.speedsolving.com/forum/showthread.php?t=20912">this thread</a> on the speedsolving forums.
			<br/><br/>
			<h2>Features</h2>
			<ul>
				<li><em>Algorithm Verification</em>: Input algorithms are executed and verified to belong to group they\'re intended for</li>
				<li><em>Duplicate Avoidance</em>: Algs are reduced to a common format to ensure no duplicates can be uploaded</li>
				<li><em>Rotation Detection</em>: All algs for each particular case are rotated to match the first case uploaded</li>
				<li><em>Automatic Organisation</em>: Algs are automatically organised by case</li>
				<li><em>Visual Case Previews</em>: Preview alg case before upload</li>
				<li><em>Multiple Case Views</em>: Plan and 3D cube views of cases, as well as animation of algs</li>
				<li><em>Admin Interface</em>: For moving/renaming/deleting cases/algs</li>
			</ul>
			<br/><br/>
			<h2>To-Do List</h2>
			Ordered by priority
			<ol>
				<li>Tag algs with user who uploaded them and time uploaded</li>
				<li>Allow users to remove algs, supported by moderator undelete function</li>
				<li>Allow searching/sorting of algs by (1) no. moves, (2) no. gen, (3) popularity</li>
				<li>Allow algorithm comments, (including links to execution vids)</li>
				<li>Allow custom user tagging of algs, including a standard used-by tag</li>
				<li>Allow users to select their preferred algs, used to build private alg-sheets and to determine alg popularity</li>
				<li>Allow search/filter of algs by tag</li>
				<li>Allow one-click rotation/AUF-ing of case orientations with automatic updates to algs</li>
				<li>Opening page with FAQs, help docs etc</li>
				<li>Visualisation of permutations in plan view (with arrows) - requires updates to VisualCube</li>
				<li>Algs for 4x4 and above, including sq-1 - requires updates to VisualCube</li>
				<li>More groups for 2x2 and 3x3 (at request)</li>
				<li>Pagination of long tables (if required)</li>
			</ol>
			<br/><br/>
			<h2>Updates</h2>
			<h3>12th Apr 2015, v1.0.2 => v1.1.0</h3>
			<ol>
				<li>Integrated with MediaWiki 1.19.14</li>
				<li>Seperated out config into a seperate file.</li>
				<li>Improved integration with visualcube.</li>
			</ol>
			<h3>4th May 2010, v1.0.1 => v1.0.2</h3>
			<ol>
				<li>BUGFIX: Swap CLS + ELS</li>
				<li>BUGFIX: Sorted out COLL/CLL/ELL plan view</li>
				<li>FEATURE: "add" link on view pages, for ease of access</li>
				<li>FEATURE: Allow removal of individual algs (currently admin only)</li>
				<li>FEATURE: Improved parsing of user input to allow: "`", "R\'2" and "Rw"</li>
				<li>BUGFIX: Fixed verification problem for algs ending in x2 rotations</li>
				<li>FEATURE: Improved duplicate detection, for duplicate algs posted with different rotations</li>
				<li>FEATURE: Bulk algorithm upload</li>
				<li>FEATURE: Link to alg.garron.us for algorithm animations</li>
				<li>FEATURE: Initial rotations in separate column, final rotations hidden</li>
				<li>FEATURE: All AUFs automatically replaced with y rotations for LL algs</li>
			</ol>
			');
}	

printnl('
		</div>');
if(!$USING_WIKI)
printnl('
		<hr/>
		<div id="footer">
			Copyright &copy; 2009 <a href="http://www.crider.co.uk">Conrad Rider</a>. All rights reserved. 
		</div>
	</body>
</html>');

// Disconnect from db
mysql_close();


function insertAlg($alg_moves, $puzzle, $group, $group_id, $check_alg, $view, $user_logged){
	global $moves, $IS_LL;

	$msg = '';

	$statemoves = gen_state($alg_moves, $puzzle, $group_id, $IS_LL[$group]);
	$state = $statemoves[0];
	$prtn = $statemoves[1];
	$frtn = $statemoves[2];

//	println("moves=$alg_moves, state=$state\n");
	
	if($state == 0){
		$msg .= "\t\t\t<em>ERROR:</em> Alg has no effect on the relevant parts of the cube.";
		$failed = true;
	}
	else if($state == -1){
		$msg .= "\t\t\t<em>ERROR:</em> Alg has extra side-effects on the cube beyond those allowed by " . $group;
		$failed = true;
	}
	// Otherwise the alg generates a legal cube state
	else{
	//pringlh($query);
		// Check alg isn't a duplicate
		if(is_dupe($alg_moves, $puzzle, $group_id)){
			$msg .= "\t\t\t<em>ERROR:</em> Alg is a duplicate.";
			$failed = true;
		}
		else{	
			// Establish if this alg belongs to given group
			$query = "SELECT case_id, moves FROM algdb_groups, algdb_cases, algdb_algs WHERE ".
				"algdb_groups.group_id=algdb_cases.group_id AND ".
				"algdb_cases.ref_alg=algdb_algs.alg_id AND ".
				"algdb_groups.puzzle_id=$puzzle AND ".
				"algdb_groups.group_id=$group_id AND algdb_cases.state=$state";
//pringlh($query);
			$case_arr = get_arrays($query);
			// If belongs to group insert alg into als table
			$new_case = count($case_arr) == 0;
			// If alg belongs to existing case, simply retrive case id
			if(!$new_case){
				// If this is not a new case create rotation to match base alg
				$alg_moves = orient_alg($prtn.trim_rotations($alg_moves, $IS_LL[$group]).$frtn, $case_arr[0]['moves'], $puzzle, $group_id);
				// Now check to make sure this is not a duplicate
				if(is_dupe($alg_moves, $puzzle, $group_id)){
					$msg .= "\t\t\t<em>ERROR:</em> Alg is a duplicate.";
					$failed = true;
				}
				// Retrive case ID for this alg
				else $case_id = $case_arr[0]['case_id'];
			}
			// If its a new case, the alg needs to satisfy strict criteria
			else{
				// If the group is closed, fail
				if($g_closed){
					$msg .= "\t\t\t<em>ERROR:</em> Alg doesn't generate any existing $group cases, and no new cases are permitted.";
					$failed = true;
				}
				// If matching state then make sure input alg uses
				// pre-rotations resulting in a valid state
				if(!$failed){
					$cube = case_cube($alg_moves);
					if(!is_member($cube, $group_id)){
						$msg .= "\t\t\t<em>ERROR:</em> Alg does not directly solve $group, ".
							"it is suggested that it is modified to: <em>".
							expand_alg($prtn.trim_rotations($alg_moves, $IS_LL[$group]).$frtn)."</em>";
						$failed = true;
					}
				}
				if(!$failed){
					// if its an LL alg, change any pre or final AUFs for y turns
					if($IS_LL[$group]) $alg_moves = remove_auf($alg_moves);
					// Get next available case_id
					$ncase_arr = get_array('MAX(case_id)', "SELECT MAX(case_id) FROM algdb_cases WHERE group_id=$group_id");
					$case_id = $ncase_arr[0] + 1;
				}
			}
		}
	}
	
	// Alg Insertion
	if(!$failed){
//println("INSERTING ALG");
		$stats = alg_stats($alg_moves);
//print_r2($alg_stats);
		if(!$check_alg){
			$query = "INSERT INTO algdb_algs(moves, stm, htm, qtm, gen) VALUES (\"$alg_moves\", ".
			$stats[0].", ".$stats[1].", ".$stats[2].", ".$stats[3].")";
//println($query);
			mysql_query($query);
			// Retrive alg id
			$query = get_array('alg_id', "SELECT alg_id FROM algdb_algs WHERE moves=\"$alg_moves\"");
			$alg_id = $query[0];
	
			// Insert alg/group mapping
//println("INSERTING MAPPING");
			$query = "INSERT INTO algdb_algmem(alg_id, group_id, case_id) VALUES ($alg_id, $group_id, $case_id)";
//println($query);
			mysql_query($query);
	
			$msg .= "\t\t\tSUCCESS: Algorithm uploaded.";
			// Create case entry if alg is new case
			if($new_case){
				$query = "INSERT INTO algdb_cases(case_id, group_id, ref_alg, state, case_name) ".
					"VALUES ($case_id, $group_id, $alg_id, $state, '$case_name')";
//println($query);
				mysql_query($query);
				$msg .= "\t\t\tNew $group case created: <a href='$SCRIPT?mode=view&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group&amp;cid=$case_id$user_logged'>#$case_id</a>";
			}else	$msg .= "\t\t\tAdded to existing case: <a href='$SCRIPT?mode=view&amp;view=$view&amp;puzzle=$puzzle&amp;group=$group&amp;cid=$case_id$user_logged'>#$case_id</a>".($case_name ? " ($case_name)" : '');
		}else{ // Just checking
			$msg .= "Algorithm OK: ";
			if($new_case) $msg .= "\t\t\tAlg will create new $group case: #$case_id";
			else $msg .= "\t\t\tAlg will be added to existing case: #$case_id".($case_name ? " ($case_name)" : '');
		}
	}
//echo "correct moves=|$alg_moves|";
	$moves = $alg_moves;
	return $msg;
}

