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
/*------------------------------------------------------------------------------
additem.php is called by the following files:

as include:
	login3.php
	authenticate3.php
	prehkeys.php
	upcscanned.php
	authenticate.php

additem.php is the bread and butter of IT CORE. addItem inserts the information
stream for each item scanned, entered or transaction occurence into localtemptrans.
Each of the above follows the following structure for entry into localtemptrans:
	$strupc, 
	$strdescription, 
	$strtransType, 
	$strtranssubType, 
	$strtransstatus, 
	$intdepartment, 
	$dblquantity, 
	$dblunitPrice, 
	$dbltotal, 
	$dblregPrice, 
	$intscale, 
	$inttax, 
	$intfoodstamp, 
	$dbldiscount, 
	$dblmemDiscount, 
	$intdiscountable, 
	$intdiscounttype, 
	$dblItemQtty, 
	$intvolDiscType, 
	$intvolume, 
	$dblVolSpecial, 
	$intmixMatch, 
	$intmatched, 
	$intvoided

Additionally, additem.php inserts entries into the activity log when a cashier 
signs in
-------------------------------------------------------------------------------*/
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!function_exists("nullwrap")) include($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");


//-------insert line into localtemptrans with standard insert string--------------

function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided, $cost=0, $numflag=0, $charflag='') {
	global $CORE_LOCAL;
	//$dbltotal = truncate2(str_replace(",", "", $dbltotal)); replaced by apbw 7/27/05 with the next 4 lines -- to fix thousands place errors

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	if ($CORE_LOCAL->get("refund") == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$dbldiscount = (-1 * $dbldiscount);
		$dblmemDiscount = (-1 * $dblmemDiscount);

		if ($strtransstatus != "V" && $strtransstatus != "D") $strtransstatus = "R" ;	// edited by apbw 6/04/05 to correct voiding of refunded items

		$CORE_LOCAL->set("refund",0);
		$CORE_LOCAL->set("refundComment","");
	}

	/* Nothing in the code can set $_SESSION["void"] to 1
	elseif ($_SESSION["void"] == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$strtransstatus = "V";
		$_SESSION["void"] = 0;
	}
	 */


	$intregisterno = $CORE_LOCAL->get("laneno");
	$intempno = $CORE_LOCAL->get("CashierNo");
	$inttransno = $CORE_LOCAL->get("transno");
	$strCardNo = $CORE_LOCAL->get("memberID");
	$memType = $CORE_LOCAL->get("memType");
	$staff = $CORE_LOCAL->get("isStaff");

	$db = tDataConnect();

	$datetimestamp = "";
	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
	}

	// this session variable never gets used
	//$_SESSION["datetimestamp"] = $datetimestamp;
	$CORE_LOCAL->set("LastID",$CORE_LOCAL->get("LastID") + 1);
	
	$trans_id = $CORE_LOCAL->get("LastID");

	$values = array(
		'datetime'	=> $datetimestamp,
		'register_no'	=> $intregisterno,
		'emp_no'	=> $intempno,
		'trans_no'	=> nullwrap($inttransno),
		'upc'		=> nullwrap($strupc),
		'description'	=> $strdescription,
		'trans_type'	=> nullwrap($strtransType),
		'trans_subtype'	=> nullwrap($strtranssubType),
		'trans_status'	=> nullwrap($strtransstatus),
		'department'	=> nullwrap($intdepartment),
		'quantity'	=> nullwrap($dblquantity),
		'cost'		=> nullwrap($cost),
		'unitPrice'	=> nullwrap($dblunitPrice),
		'total'		=> nullwrap($dbltotal),
		'regPrice'	=> nullwrap($dblregPrice),
		'scale'		=> nullwrap($intscale),
		'tax'		=> nullwrap($inttax),
		'foodstamp'	=> nullwrap($intfoodstamp),
		'discount'	=> nullwrap($dbldiscount),
		'memDiscount'	=> nullwrap($dblmemDiscount),
		'discountable'	=> nullwrap($intdiscountable),
		'discounttype'	=> nullwrap($intdiscounttype),
		'ItemQtty'	=> nullwrap($dblItemQtty),
		'volDiscType'	=> nullwrap($intvolDiscType),
		'volume'	=> nullwrap($intvolume),
		'VolSpecial'	=> nullwrap($dblVolSpecial),
		'mixMatch'	=> nullwrap($intmixMatch),
		'matched'	=> nullwrap($intmatched),
		'voided'	=> nullwrap($intvoided),
		'memType'	=> nullwrap($memType),
		'staff'		=> nullwrap($staff),
		'numflag'	=> nullwrap($numflag),
		'charflag'	=> $charflag,
		'card_no'	=> (string)$strCardNo
		);
	if ($CORE_LOCAL->get("DBMS") == "mssql" && $CORE_LOCAL->get("store") == "wfc"){
		unset($values["staff"]);
		$values["isStaff"] = nullwrap($staff);
	}

	$db->smart_insert("localtemptrans",$values);
	$db->close();

	if ($strtransType == "I" || $strtransType == "D") {
		$CORE_LOCAL->set("beep","goodBeep");
		if ($intscale == 1) {
			$CORE_LOCAL->set("screset","rePoll");
		}
		elseif ($CORE_LOCAL->get("weight") != 0) {
			$CORE_LOCAL->set("screset","rePoll");
		}
		$CORE_LOCAL->set("repeatable",1);
	}
	
	$CORE_LOCAL->set("msgrepeat",0);
	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("SNR",0);
	$CORE_LOCAL->set("wgtRequested",0);
	$CORE_LOCAL->set("nd",0);

	$CORE_LOCAL->set("ccAmtEntered",0);
	$CORE_LOCAL->set("ccAmt",0);

}

//________________________________end addItem()


//---------------------------------- insert tax line item --------------------------------------

function addtax() {
	global $CORE_LOCAL;

	addItem("TAX", "Tax", "A", "", "", 0, 0, 0, $CORE_LOCAL->get("taxTotal"), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//________________________________end addtax()


//---------------------------------- insert tender line item -----------------------------------

function addtender($strtenderdesc, $strtendercode, $dbltendered) {
	addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//_______________________________end addtender()


function addcomment($comment) {
	if (strlen($comment) > 30)
		$comment = substr($comment,0,30);
	addItem("",$comment, "C", "CM", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}


//--------------------------------- insert change line item ------------------------------------

function addchange($dblcashreturn) {
	addItem("", "Change", "T", "CA", "", 0, 0, 0, $dblcashreturn, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

//_______________________________end addchange()


//-------------------------------- insert foods stamp change item ------------------------------

function addfsones($intfsones) {
	addItem("", "FS Change", "T", "FS", "", 0, 0, 0, $intfsones, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

//_______________________________end addfsones()

function addEndofShift() {
	addItem("ENDOFSHIFT", "End of Shift", "S", "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//-------------------------------- insert deli discount (Wedge specific) -----------------------

function addscDiscount() {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("scDiscount") != 0) {
		addItem("DISCOUNT", "** 10% Deli Discount **", "I", "", "", 0, 1, truncate2(-1 * $CORE_LOCAL->get("scDiscount")), truncate2(-1 * $CORE_LOCAL->get("scDiscount")), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
//	addStaffCoffeeDiscount();

}

function addStaffCoffeeDiscount() {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("staffCoffeeDiscount") != 0) {
		addItem("DISCOUNT", "** Coffee Discount **", "I", "", "", 0, 1, truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
}

//_______________________________end addscDiscount()


//------------------------------- insert discount line -----------------------------------------

/***** jqh 09/29/05 changed adddiscount function to write the department to localtemptrans *****/
function adddiscount($dbldiscount,$department) {
	$strsaved = "** YOU SAVED $".truncate2($dbldiscount)." **";
	addItem("", $strsaved, "I", "", "D", $department, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2);
}

//_____________________________end adddiscount()


//------------------------------ insert Food Stamp Tax Exempt line -----------------------------


function addfsTaxExempt() {
	global $CORE_LOCAL;

	getsubtotals();
	addItem("FS Tax Exempt", " Fs Tax Exempt ", "C", "", "D", 0, 0, $CORE_LOCAL->get("fsTaxExempt"), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17);
}

//_____________________________end addfsTaxExempt()


//------------------------------ insert 'discount applied' line --------------------------------

function discountnotify($strl) {
	if ($strl == 10.01) {
		$strL = 10;
	}
	addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
}

//_____________________________end discountnotify()


//------------------------------- insert discount line -----------------------------------------

//------------------------------- insert tax exempt statement line -----------------------------

function addTaxExempt() {
	global $CORE_LOCAL;

	addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$CORE_LOCAL->set("TaxExempt",1);
	setglobalvalue("TaxExempt", 1);
}

//_____________________________end addTaxExempt()


//------------------------------ insert reverse tax exempt statement ---------------------------

function reverseTaxExempt() {
	global $CORE_LOCAL;
	addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$CORE_LOCAL->set("TaxExempt",0);
	setglobalvalue("TaxExempt", 0);
}

//_____________________________end reverseTaxExempt()

//------------------------------ insert case discount statement --------------------------------

function addcdnotify() {
	global $CORE_LOCAL;
	addItem("", "** ".$CORE_LOCAL->get("casediscount")."% Case Discount Applied", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//____________________________end addcdnotify()

//------------------------------ insert manufacturer coupon statement --------------------------

function addCoupon($strupc, $intdepartment, $dbltotal, $foodstamp=0) {
	addItem($strupc, " * Manufacturers Coupon", "I", "CP", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, $foodstamp, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);	
}

function addhousecoupon($strupc, $intdepartment, $dbltotal) {
	addItem($strupc, " * WFC Coupon", "I", "IC", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

function additemdiscount($intdepartment, $dbltotal) {
	$dbltotal *= -1;
	addItem('ITEMDISCOUNT'," * Item Discount", "I", "", "", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}


//___________________________end addCoupon()

//------------------------------ insert tare statement -----------------------------------------

function addTare($dbltare) {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("tare",$dbltare/100);
	addItem("", "** Tare Weight ".$CORE_LOCAL->get("tare")." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//___________________________end addTare()


//------------------------------- insert MAD coupon statement (WFC specific) -------------------

function addMadCoup() {
	global $CORE_LOCAL;

		$madCoup = -1 * $CORE_LOCAL->get("madCoup");
		addItem("MAD Coupon", "Member Appreciation Coupon", "I", "CP", "C", 0, 1, $madCoup, $madCoup, $madCoup, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 17);
		
}

function addVirtualCoupon($id){
	global $CORE_LOCAL;
	$sql = pDataConnect();
	$fetchQ = "select name,type,value,max from VirtualCoupon WHERE flag=$id";
	$fetchR = $sql->query($fetchQ);
	$coupW = $sql->fetch_row($fetchR);

	$val = (double)$coupW["value"];
	$limit = (double)$coupW["max"];
	$type = $coupW["type"];
	$desc = substr($coupW["name"],0,35);
	switch(strtoupper($type)){
	case 'PERCENT':
		$val = $val * $CORE_LOCAL->get("discountableTotal");
		break;
	}
	if ($limit != 0 && $val > $limit)
		$val = $limit;
	$val *= -1;
	$upc = str_pad($id,13,'0',STR_PAD_LEFT);

	addItem($upc, $desc, "I", "CP", "C", 0, 1, $val, $val, $val, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}


//___________________________end addMadCoupon()

function addDeposit($quantity, $deposit, $foodstamp) {

	$total = $quantity * $deposit;
	$chardeposit = 100 * $deposit;
	if($foodstamp == 1){  //  ACG HARDCODED DEPARTMENTS....
		$dept = 43;
	}else{
		$dept = 42;
	}
	addItem("DEPOSIT" * $chardeposit, "Deposit", "I", "", "", $dept, $quantity, $deposit, $total, $deposit, 0, 0, $foodstamp, 0, 0, 0, 0, $quantity, 0, 0, 0, 0, 0, 0);
		
}

// ----------------------------- insert transaction discount -----------------------------------

function addtransDiscount() {
	global $CORE_LOCAL;
	addItem("DISCOUNT", "Discount", "I", "", "", 0, 1, truncate2(-1 * $CORE_LOCAL->get("transDiscount")), truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

// ---------------------------- insert stamp in activitytemplog --------------------------------

function addactivity($activity) {
	global $CORE_LOCAL;

	$timeNow = time();

	if ($CORE_LOCAL->get("CashierNo") > 0 && $CORE_LOCAL->get("CashierNo") < 256) {
		$intcashier = $CORE_LOCAL->get("CashierNo");
	}
	else {
		$intcashier = 0;
	}

	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$strqtime = "select max(datetime) as maxDateTime, getdate() as rightNow from activitytemplog";
	} else {
		$strqtime = "select max(datetime) as maxDateTime, now() as rightNow from activitytemplog";
	}


	$db = tDataConnect();
	$result = $db->query($strqtime);


	$row = $db->fetch_array($result);

	if (!$row || !$row[0]) {

		$interval = 0;
	}
	else {

		$interval = strtotime($row["rightNow"]) - strtotime($row["maxDateTime"]);
	}
		
	//$_SESSION["datetimestamp"] = strftime("%Y-%m-%d %H:%M:%S", $timeNow);
	$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", $timeNow);

	$values = array(
		'datetime'	=> nullwrap($datetimestamp),
		'LaneNo'	=> nullwrap($CORE_LOCAL->get("laneno")),
		'CashierNo'	=> nullwrap($intcashier),
		'TransNo'	=> nullwrap($CORE_LOCAL->get("transno")),
		'Activity'	=> nullwrap($activity),
		'Interval'	=> nullwrap($interval)
		);
		/*
	if ($CORE_LOCAL->get("DBMS")=="mysql"){
		unset($values['Interval']);
		$values['`Interval`'] = nullwrap($interval);
	}
	*/
	$result = $db->smart_insert("activitytemplog",$values);

	$db->close();

}

// ------------------------------------------------------------------------


?>
