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
if (!function_exists("percentDiscount")) include_once($CORE_PATH."lib/prehkeys.php");
if (!function_exists("boxMsg")) include_once($CORE_PATH."lib/drawscreen.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class DiscountApplied extends Parser {
	var $ret;
	function check($str){
		global $CORE_LOCAL;
		$this->ret = $this->default_json();
		if (substr($str,-2) == "DA"){
			$strl = substr($str,0,strlen($str)-2);
			if (substr($str,0,2) == "VD")
				$this->ret = percentDiscount(0,$this->ret);
			elseif (!is_numeric($strl)) 
				return False;
			elseif ($CORE_LOCAL->get("tenderTotal") != 0) 
				$this->ret['output'] = boxMsg("discount not applicable after tender");
			elseif ($strl > 50) 
				$this->ret['output'] = boxMsg("discount exceeds maximum");
			elseif ($strl <= 0) 
				$this->ret['output'] = boxMsg("discount must be greater than zero");
			elseif ($strl == 15 && $CORE_LOCAL->get("isStaff") == 0 && (substr($CORE_LOCAL->get("memberID"), 0, 1) != "9" || strlen($CORE_LOCAL->get("memberID")) != 6)) 
				$this->ret['output'] = boxMsg("Staff discount not applicable");
			elseif ($strl == 10 && $CORE_LOCAL->get("isMember") == 0) 
				$this->ret['output'] = boxMsg("Member discount not applicable");
			elseif ($strl <= 50 and $strl > 0) 
				$this->ret = percentDiscount($strl,$this->ret);
			else 
				return False;
			return True;
		}
		return False;
	}

	function parse($str){
		return $this->ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>DA</td>
				<td>Add a percent discount of the specified
				amount <i>number</i></td>
			</tr>
			</table>";
	}
}

?>
