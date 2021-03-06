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

if (!class_exists("BasicPage")) include_once($CORE_PATH."gui-class-lib/BasicPage.php");
if (!class_exists("ScrollItems")) include_once($CORE_PATH."parser-class-lib/parse/ScrollItems.php");
if (!function_exists("addItem")) include_once($CORE_PATH."lib/additem.php");
if (!function_exists("setMember")) include_once($CORE_PATH."lib/prehkeys.php");
if (!function_exists("printfooter")) include_once($CORE_PATH."lib/drawscreen.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");


/* wraps around an undone transaction to limit editing options
   CL cancels the attempt (wraps to input "CN")
   {Enter} finishes the transaction (wraps to input "0CA")
*/
class undo_confirm extends BasicPage {
	var $box_color;
	var $msg;

	function body_content(){
		global $CORE_LOCAL;
		echo $this->input_header();
		?>
		<div class="baseHeight">
		<?php 
			if (empty($this->msg))
				echo lastpage(); 
			else {
				echo $this->msg;	
			}
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
		$this->add_onload_command("\$('#reginput').keyup(function(ev){
					switch(ev.keyCode){
					case 33:
						\$('#reginput').val('U11');
						\$('#formlocal').submit();
						break;
					case 38:
						\$('#reginput').val('U');
						\$('#formlocal').submit();
						break;
					case 34:
						\$('#reginput').val('D11');
						\$('#formlocal').submit();
						break;
					case 40:
						\$('#reginput').val('D');
						\$('#formlocal').submit();
						break;
					}
				});\n");
		$this->add_onload_command("\$('#reginput').focus();");
		$CORE_LOCAL->set("beep","noScan");
	}

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		$this->msg = "";
		if (isset($_REQUEST['reginput'])){
			switch(strtoupper($_REQUEST['reginput'])){
			case 'CL':
				// zero removes password check I think
				$CORE_LOCAL->set("runningTotal",0);
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered","CN");
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
				break;
			case '':
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered","0CA");
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
				break;
			case 'U':
			case 'U11':
			case 'D':
			case 'D11':
				// just use the parser module here
				// for simplicity; all its really
				// doing is updating a couple session vars
				$si = new ScrollItems();
				$json = $si->parse($_REQUEST['reginput']);
				$this->msg = $json['output'];
				break;
			default:
				break;
			}
		}
		return True;
	}
}

new undo_confirm();
