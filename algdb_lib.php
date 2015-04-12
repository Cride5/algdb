<?php
/*
	File: algdb_lib.php
	Date: 24/10/09
	Author(s): Conrad Rider (www.crider.co.uk)
	Description: Library containing functions for algdb.php

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
	
	function in_array2($str, $field, $ary){
		foreach($ary as $i){
			if($i[$field] == $str) return true;
		}
		return false;
	}
/*	function array_copy($ary){
		if(!is_array($ary)) return $ary;
		$out = Array(count($ary));
		foreach($ary as $k => $v){
			$out[$k] = array_copy($v);
		}
		return $out;
	}
*/	function get_array($field_name, $query){
//echo "|$query|";
		$result = mysql_query($query);
		$count = mysql_numrows($result);
		if($count <= 0) return null;
		$ary = Array();
		$i = 0;
		while($record = mysql_fetch_array($result, MYSQL_ASSOC)){
			$ary[$i] = $record[$field_name];
			$i++;
		}
		return $ary;
	}
	// Print some txt
	function printnl($str){
		if($GLOBALS['ALGDB_USING_WIKI']){
			$GLOBALS['ALGDB_OUTPUT']->addHTML($str);
		}
		else{ echo $str; }
	}
	// Print a line
	function println($str){
		printnl("$str\n");
	}
	// Print an html line
	function printlh($str){
		printnl("$str<br/>\n");
	}
	// Generate a header
	function genhead($str){
//		global $USING_WIKI;
		if($USING_WIKI) return "<h2><span class=\"mw-headline\">$str</span></h2>";
		return "<h1>$str</h1>";
	}
	// Print array formatted for html
	function print_r2($ary){
		echo array_str($ary, "");
	}
	// Format printed array for html
	function array_str($ary, $tab){
		if(!is_array($ary)) return $ary;
		//return "asdf";
		$itab = "$tab&nbsp;&nbsp;&nbsp;&nbsp;";
		$str = "Array(<br/>\n";
		foreach($ary as $k => $v){
			$str .= $itab."[$k] => ".array_str($v, $itab)."<br/>\n";
		}
		return "$str$tab)";
	}
	// Check whether this alg is a duplicate
	function is_dupe($moves, $puzzle, $group_id){
		$query = "SELECT * FROM algdb_algs, algdb_groups, algdb_algmem WHERE ".
			"algdb_algs.moves=\"$moves\" AND ".
			"algdb_algs.alg_id=algdb_algmem.alg_id AND ".
			"algdb_groups.group_id=algdb_algmem.group_id AND ".
			"algdb_groups.puzzle_id=$puzzle AND ".
			"algdb_groups.group_id='$group_id'";
		return mysql_numrows(mysql_query($query)) > 0;
	}
	// Return result of sql query as array
	function get_arrays($query){
		$result = mysql_query($query);
		$count = mysql_numrows($result);
		if($count <= 0) return null;
		$ary = Array($count);
		$i = 0;
		while($record = mysql_fetch_array($result, MYSQL_ASSOC)){
			$ary[$i] = $record;
			$i++;
		}
		return $ary;
	}	
	// Return result of sql query of single field as single dimensional array
	function get_list($field_name, $query){
		$result = mysql_query($query);
		$count = mysql_numrows($result);
		if($count <= 0) return null;
		while($record = mysql_fetch_array($result, MYSQL_ASSOC)){
			if($str) $str .= ", ";
			$str.= $record[$field_name];
		}
		return $str;
	}

	// Calculate n! (god knows why php doesn't have this!)
	function fac($n){
		if($n < 1) return 0;
		if($n = 1) return 1;
		return $n * fac($n -1);
	}
	
?>
