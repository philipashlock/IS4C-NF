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

if (!function_exists("addcomment")) include_once($CORE_PATH."lib/additem.php");
if (!function_exists("array_to_json")) include_once($CORE_PATH."lib/array_to_json.php");
if (!function_exists("paycard_reset")) include_once($CORE_PATH."lib/paycardLib.php");
if (!function_exists("sigTermObject")) include_once($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

// send the request
$result = 0; // 0 is never returned, so we use it to make sure it changes
$myObj = 0;
$json = array();
$json['main_frame'] = $CORE_PATH.'gui-modules/paycardSuccess.php';
$json['receipt'] = false;
foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
	if (!class_exists($rpc)) include_once($CORE_PATH."cc-modules/$rpc.php");
	$myObj = new $rpc();
	if ($myObj->handlesType($CORE_LOCAL->get("paycard_type"))){
		break;
	}
}

$st = sigTermObject();

$result = $myObj->doSend($CORE_LOCAL->get("paycard_mode"));
if ($result == PAYCARD_ERR_OK){
	paycard_wipe_pan();
	$json = $myObj->cleanup($json);
	$CORE_LOCAL->set("strRemembered","");
	$CORE_LOCAL->set("msgrepeat",0);
	if (is_object($st))
		$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
}
else {
	paycard_reset();
	$CORE_LOCAL->set("msgrepeat",0);
	$json['main_frame'] = $CORE_PATH.'gui-modules/boxMsg2.php';
	if (is_object($st))
		$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
}

echo array_to_json($json);
