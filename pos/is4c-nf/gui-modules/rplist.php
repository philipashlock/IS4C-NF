<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

if (!class_exists("NoInputPage")) include_once($CORE_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("tDataConnect")) include($CORE_PATH."lib/connect.php");
if (!function_exists("reprintReceipt")) include($CORE_PATH."lib/reprint.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class rplist extends NoInputPage {

	function preprocess(){
		global $CORE_PATH;
		if (isset($_REQUEST['selectlist'])){
			if (!empty($_REQUEST['selectlist']))
				reprintReceipt($_REQUEST['selectlist']);
			header("Location: {$CORE_PATH}gui-modules/pos2.php");
			return False;
		}
		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist option:selected').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
		$this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
		$this->add_onload_command("\$('#selectlist').focus();\n");
	}
	
	function body_content(){
		global $CORE_LOCAL;
		$query = "select register_no, emp_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
		."from localtranstoday where register_no = ".$CORE_LOCAL->get("laneno")." and emp_no = ".$CORE_LOCAL->get("CashierNo")
		." group by register_no, emp_no, trans_no order by trans_no desc";
		if ($CORE_LOCAL->get("DBMS") == "mysql" && $CORE_LOCAL->get("store") != "wfc")
			$query = "select register_no,emp_no,trans_no,total from rp_list where register_no = ".$CORE_LOCAL->get("laneno")." and emp_no = ".$CORE_LOCAL->get("CashierNo")." order by trans_no desc";

		$db = tDataConnect();
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		?>

		<div class="baseHeight">
		<div class="listbox">
		<form name="selectform" method="post" id="selectform" 
			action="<?php echo $_SERVER['PHP_SELF']; ?>" >
		<select name="selectlist" size="10" id="selectlist"
			onblur="$('#selectlist').focus()" >

		<?php
		$selected = "selected";
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			echo "<option value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."'";
			echo $selected;
			echo ">lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
				." #".$row["trans_no"]." -- $".
				sprintf("\$%.2f",$row["total"]);
			$selected = "";
		}
		$db->db_close();
		?>

		</select>
		</form>
		</div>
		<div class="listboxText centerOffset">
		use arrow keys to navigate<br />[enter] to reprint receipt<br />[clear] to cancel
		</div>
		<div class="clear"></div>
		</div>

		<?php
		$CORE_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new rplist();

?>
