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

ini_set('display_errors','1');
 
session_cache_limiter('nocache');

if (!class_exists("BasicPage")) include_once($CORE_PATH."gui-class-lib/BasicPage.php");

if (!function_exists("lastpage")) include($CORE_PATH."lib/listitems.php");
if (!function_exists("printheaderb")) include($CORE_PATH."lib/drawscreen.php");
if (!function_exists("tender")) include($CORE_PATH."lib/prehkeys.php");
if (!function_exists("drawerKick")) include_once($CORE_PATH."lib/printLib.php");
if (!function_exists("get_preparse_chain")) include_once($CORE_PATH."parser-class-lib/Parser.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class pos2 extends BasicPage {

	function head_content(){
		global $CORE_LOCAL,$CORE_PATH;
		?>
		<script type="text/javascript" src="<?php echo $CORE_PATH; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			runParser(str,'<?php echo $CORE_PATH; ?>');
			return false;
		}
		function parseWrapper(str){
			runParser(str,'<?php echo $CORE_PATH; ?>');
		}
		function lockScreen(){
			$.ajax({
				'url': '<?php echo $CORE_PATH; ?>ajax-callbacks/ajax-lock.php',
				'type': 'get',
				'cache': false,
				'success': function(){
					location = '<?php echo $CORE_PATH; ?>gui-modules/login3.php';
				}
			});
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header('onsubmit="return submitWrapper();"');
		$this->add_onload_command("setTimeout('lockScreen()', 180000);\n");
		$this->add_onload_command("\$('#reginput').keydown(function(ev){
					switch(ev.keyCode){
					case 33:
						\$('#reginput').val('U11');
						submitWrapper();
						break;
					case 38:
						\$('#reginput').val('U');
						submitWrapper();
						break;
					case 34:
						\$('#reginput').val('D11');
						submitWrapper();
						break;
					case 40:
						\$('#reginput').val('D');
						submitWrapper();
						break;
					}
				});\n");
		if ($CORE_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		?>
		<div class="baseHeight">
		<?php

		$CORE_LOCAL->set("quantity",0);
		$CORE_LOCAL->set("multiple",0);
		$CORE_LOCAL->set("casediscount",0);
		$CORE_LOCAL->set("away",0);

		// set memberID if not set already
		if (!$CORE_LOCAL->get("memberID")) {
			$CORE_LOCAL->set("memberID","0");
		}

		// handle messages
		if ( $CORE_LOCAL->get("msg") == "0") {
			$CORE_LOCAL->set("msg",99);
			$CORE_LOCAL->set("unlock",0);
		}

		if ($CORE_LOCAL->get("plainmsg") && strlen($CORE_LOCAL->get("plainmsg")) > 0) {
			echo printheaderb();
			echo "<div class=\"centerOffset\">";
			echo plainmsg($CORE_LOCAL->get("plainmsg"));
			$CORE_LOCAL->set("plainmsg",0);
			$CORE_LOCAL->set("msg",99);
			echo "</div>";
		}
		else
			echo lastpage();

		echo "</div>"; // end base height

		echo "<div id=\"footer\">";
		if ($CORE_LOCAL->get("away") == 1)
			echo printfooterb();
		else
			echo printfooter();
		echo "</div>";

		$CORE_LOCAL->set("away",0);
	} // END body_content() FUNCTION
}

new pos2();

?>
