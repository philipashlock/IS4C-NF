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
if (!function_exists('scaleObject')) include_once($CORE_PATH.'lib/lib.php');
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class pos2 extends BasicPage {

	var $display;

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		$this->display = "";

		$sd = scaleObject();
		//$st = sigTermObject();

		$entered = "";
		if (isset($_REQUEST["reginput"])) {
			$entered = strtoupper(trim($_REQUEST["reginput"]));
		}

		if (substr($entered, -2) == "CL") $entered = "CL";

		if ($entered == "RI") $entered = $CORE_LOCAL->get("strEntered");

		if ($CORE_LOCAL->get("msgrepeat") == 1 && $entered != "CL") {
			$entered = $CORE_LOCAL->get("strRemembered");
		}
		$CORE_LOCAL->set("strEntered",$entered);

		$json = array();
		if ($entered != ""){
			/* this breaks the model a bit, but I'm putting
			 * putting the CC parser first manually to minimize
			 * code that potentially handles the PAN */
			include_once($CORE_PATH."cc-modules/lib/paycardEntered.php");
			$pe = new paycardEntered();
			if ($pe->check($entered)){
				$valid = $pe->parse($entered);
				$entered = "PAYCARD";
				$CORE_LOCAL->set("strEntered","");
				$json = $valid;
			}

			$CORE_LOCAL->set("quantity",0);
			$CORE_LOCAL->set("multiple",0);

			/* FIRST PARSE CHAIN:
			 * Objects belong in the first parse chain if they
			 * modify the entered string, but do not process it
			 * This chain should be used for checking prefixes/suffixes
			 * to set up appropriate $CORE_LOCAL variables.
			 */
			$parser_lib_path = $CORE_PATH."parser-class-lib/";
			if (!is_array($CORE_LOCAL->get("preparse_chain")))
				$CORE_LOCAL->set("preparse_chain",get_preparse_chain());

			foreach ($CORE_LOCAL->get("preparse_chain") as $cn){
				if (!class_exists("cn"))
					include_once($parser_lib_path."preparse/".$cn.".php");
				$p = new $cn();
				if ($p->check($entered))
					$entered = $p->parse($entered);
					if (!$entered || $entered == "")
						break;
			}

			if ($entered != "" && $entered != "PAYCARD"){
				/* 
				 * SECOND PARSE CHAIN
				 * these parser objects should process any input
				 * completely. The return value of parse() determines
				 * whether to call lastpage() [list the items on screen]
				 */
				if (!is_array($CORE_LOCAL->get("parse_chain")))
					$CORE_LOCAL->set("parse_chain",get_parse_chain());

				$result = False;
				foreach ($CORE_LOCAL->get("parse_chain") as $cn){
					if (!class_exists($cn))
						include_once($parser_lib_path."parse/".$cn.".php");
					$p = new $cn();
					if ($p->check($entered)){
						$result = $p->parse($entered);
						break;
					}
				}
				if ($result && is_array($result)){
					$json = $result;
					if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
						if (is_object($sd))
							$sd->WriteToScale($result['udpmsg']);
						/*
						if (is_object($st))
							$st->WriteToScale($result['udpmsg']);
						*/
					}
				}
				else {
					$arr = array(
						'main_frame'=>false,
						'target'=>'.baseHeight',
						'output'=>inputUnknown());
					$json = $arr;
					if (is_object($sd))
						$sd->WriteToScale('errorBeep');
				}
			}
		}
		$CORE_LOCAL->set("msgrepeat",0);
		if (isset($json['main_frame']) && $json['main_frame'] != False){
			header("Location: ".$json['main_frame']);
			return False;
		}
		if (isset($json['output']) && !empty($json['output']))
			$this->display = $json['output'];

		if (isset($json['retry']) && $json['retry'] != False){
			$this->add_onload_command("setTimeout(\"inputRetry('".$json['retry']."');\", 700);\n");
		}

		if (isset($json['receipt']) && $json['receipt'] != False){
			$this->add_onload_command("receiptFetch('".$json['receipt']."');\n");
		}

		return True;
	}

	function head_content(){
		global $CORE_LOCAL,$CORE_PATH;
		?>
		<script type="text/javascript" src="<?php echo $CORE_PATH; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.indexOf("tw") != -1 || str.indexOf("TW") != -1 || str.search(/^[0-9]+$/) == 0){
				$('#reginput').val('');
				runParser(str,'<?php echo $CORE_PATH; ?>');
				return false;
			}
			return true;
		}
		function parseWrapper(str){
			$('#reginput').val(str);
			$('#formlocal').submit();
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
		function receiptFetch(r_type){
			$.ajax({
				url: '<?php echo $CORE_PATH; ?>ajax-callbacks/ajax-end.php',
				type: 'get',
				data: 'receiptType='+r_type,
				cache: false,
				success: function(data){
				},
				error: function(e1){
				}
			});
		}
		function inputRetry(str){
			$('#reginput').val(str);
			submitWrapper();
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header('action="pos2.php" onsubmit="return submitWrapper();"');
		$this->add_onload_command("setTimeout('lockScreen()', 180000);\n");
		$this->add_onload_command("\$('#reginput').keydown(function(ev){
					switch(ev.which){
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
		/*
		if ($CORE_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		*/
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
		elseif (!empty($this->display))
			echo $this->display;
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
