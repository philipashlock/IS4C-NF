<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
if (!function_exists("paycard_reset")) include_once($CORE_PATH."lib/paycardLib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class giftcardlist extends NoInputPage {

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		if (isset($_REQUEST["selectlist"])){
			$CORE_LOCAL->set("prefix",$_REQUEST["selectlist"]);
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
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist').val('');
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
	} // END head() FUNCTION

	function body_content() {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">gift card transaction</span>
		<form name="selectform" method="post" id="selectform"
			action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<select id="selectlist" name="selectlist" 
			onblur="$('#selectlist').focus()">
		<option value="">Sale
		<option value="AC">Activate
		<option value="AV">Add Value
		<option value="PV">Balance
		</select>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>
		<?php
		$CORE_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new giftcardlist();
?>
