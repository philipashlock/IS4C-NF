<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* HELP

   nightly.pcbatch.php

   This script triggers Price Change batches, a
   special type of batch that changes a group of
   items' regular price rather than setting a sale
   price. Batches with a discount type of zero
   are considered price change batches.

   This script performs price changes for
   batches with a startDate matching the current
   date. To work effectively, it must be run at
   least once a day.

   Changes are logged in prodUpdate if possible.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$chk_vital = array();
$chk_opt = array();

/* change prices
*/
$chk_vital[] = $sql->query("UPDATE products SET
		normal_price = l.salePrice
		FROM products AS p, batches AS b, batchList AS l
		WHERE l.batchID=b.batchID AND l.upc=p.upc
		AND l.upc NOT LIKE 'LC%'
		AND b.discountType = 0
		AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");

/* log changes in prodUpdate */
if ($sql->table_exists("prodUpdate")){
	$upQ = "INSERT INTO prodUpdate
		SELECT p.upc,description,p.normal_price,
		department,tax,foodstamp,scale,0,
		modified,0,qttyEnforced,discount,inUse
		FROM products AS p, batches AS b, batchList AS l
		WHERE l.batchID=b.batchID AND l.upc=p.upc
		AND l.upc NOT LIKE 'LC%'
		AND b.discountType = 0
		AND ".$sql->datediff($sql->now(),'b.startDate')." = 0";
	$chk_opt[] = $sql->query($upQ);
}

/* likecoded items differentiated
   for char concatenation
*/
if ($FANNIE_SERVER_DBMS == "MYSQL"){
	$chk_vital[] = $sql->query("UPDATE products SET normal_price = l.salePrice
		FROM products AS p LEFT JOIN
		likeCodeView AS v ON v.upc=p.upc LEFT JOIN
		batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
		LEFT JOIN batches AS b ON b.batchID = l.batchID
		WHERE l.upc LIKE 'LC%'
		AND b.discountType = 0
		AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");

	if ($sql->table_exists("prodUpdate")){
		$upQ = "INSERT INTO prodUpdate
			SELECT p.upc,description,p.normal_price,
			department,tax,foodstamp,scale,0,
			modified,0,qttyEnforced,discount,inUse
			FROM products AS p LEFT JOIN
			likeCodeView AS v ON v.upc=p.upc LEFT JOIN
			batchList AS l ON l.upc=concat('LC',convert(v.likeCode,char))
			LEFT JOIN batches AS b ON b.batchID = l.batchID
			WHERE l.upc LIKE 'LC%'
			AND b.discountType = 0
			AND ".$sql->datediff($sql->now(),'b.startDate')." = 0";
		$chk_opt[] = $sql->query($upQ);
	}
}
else {
	$chk_vital[] = $sql->query("UPDATE products SET normal_price = l.salePrice
		FROM products AS p LEFT JOIN
		likeCodeView AS v ON v.upc=p.upc LEFT JOIN
		batchList AS l ON l.upc='LC'+convert(varchar,v.likecode)
		LEFT JOIN batches AS b ON b.batchID = l.batchID
		WHERE l.upc LIKE 'LC%'
		AND b.discountType = 0
		AND ".$sql->datediff($sql->now(),'b.startDate')." = 0");

	if ($sql->table_exists("prodUpdate")){
		$upQ = "INSERT INTO prodUpdate
			SELECT p.upc,description,p.normal_price,
			department,tax,foodstamp,scale,0,
			modified,0,qttyEnforced,discount,inUse
			FROM products AS p LEFT JOIN
			likeCodeView AS v ON v.upc=p.upc LEFT JOIN
			batchList AS l ON l.upc='LC'+convert(varchar,v.likecode)
			LEFT JOIN batches AS b ON b.batchID = l.batchID
			WHERE l.upc LIKE 'LC%'
			AND b.discountType = 0
			AND ".$sql->datediff($sql->now(),'b.startDate')." = 0";
		$chk_opt[] = $sql->query($upQ);
	}
}

$success = true;
foreach($chk_vital as $chk){
	if ($chk === false)
		$success = false;
}
if ($success)
	echo cron_msg("Price change batches run successfully");
else
	echo cron_msg("Error running price change batches");

$success = true;
foreach($chk_opt as $chk){
	if ($chk === false)
		$success = false;
}
if ($success)
	echo cron_msg("Changes logged in prodUpdate");
else
	echo cron_msg("Error logging changes");
?>
