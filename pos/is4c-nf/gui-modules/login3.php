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

ini_set('display_errors','1');

if (!class_exists("BasicPage")) include_once($CORE_PATH."gui-class-lib/BasicPage.php");
if (!function_exists("authenticate")) include($CORE_PATH."lib/authenticate.php");
if (!function_exists("scaleObject")) include($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class login3 extends BasicPage {

	var $color;
	var $img;
	var $msg;

	function preprocess(){
		global $CORE_PATH;
		$this->color = "#004080";
		$this->img = $CORE_PATH."graphics/bluekey4.gif";
		$this->msg = "please enter password";
		if (isset($_REQUEST['reginput'])){
			if (authenticate($_REQUEST['reginput'],4)){
				$sd = scaleObject();
				if (is_object($sd))
					$sd->ReadReset();
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
			}
			else {
				$this->color = "#800000";
				$this->img = $CORE_PATH."graphics/redkey4.gif";
				$this->msg = "password invalid, please re-enter";
			}
		}
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
		$style = "style=\"background: {$this->color};\"";
		$this->input_header();
		echo printheaderb();
		?>
		<div class="baseHeight">
			<div class="colored centeredDisplay" <?php echo $style;?>>
			<img src='<?php echo $this->img ?>' />
			<p />
			<?php echo $this->msg ?>
			<p />
			</div>
		</div>
		<?php
		addactivity(3);
		$CORE_LOCAL->set("scan","noScan");
		getsubtotals();
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
	} // END true_body() FUNCTION

}

new login3();

?>
