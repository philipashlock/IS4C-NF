<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("Parser")) include_once($CORE_PATH."parser-class-lib/Parser.php");
if (!function_exists("staffCharge")) include_once($CORE_PATH."lib/prehkeys.php");

class StaffCharge extends Parser {
	var $left;

	function check($str){
		if (substr($str,-2) == "SC"){
			$left = substr($str,0,strlen($str)-2);
			$left = str_replace($left,"."," ");
			$left = str_replace($left,","," ");
			if (!is_numeric($left) || strlen($left != 6))
				return False;
			$this->left = $left;
			return True;
		}
		return False;
	}

	function parse($str){
		$ret = $this->default_json();
		staffCharge($this->left,$ret);
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>amount</i>SC</td>
				<td>Tender <i>amount</i> to staff
				charge</td>
			</tr>
			</table>";
	}
}

?>
