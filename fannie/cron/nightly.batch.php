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
 
   nightly.batch.php

   This script sets up sales. First it takes all items
   off sale, then it applies sales batches with a 
   start and end date including the current day.

   This script should run daily. Because batch start
   and end dates are inclusive, scheduling the script
   after midnight will give the most sensible results.

   THIS SCRIPT IS CURRENTLY DISABLED TO AVOID CONFLICTS
   AT WFC. IF YOU WANT TO USE IT, REMOVE THE "exit"
   LINE.
*/

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

exit;

/*
// update batch merge table 

// unsale everything  
$sql->query("UPDATE products SET
		special_price=0,
		specialpricemethod=0,
		specialquantity=0,
		specialgroupprice=0,
		discounttype=0,
		start_date='1900-01-01',
		end_date='1900-01-01'");

// resale things that should be on sale 
if ($FANNIE_SERVER_DBMS == "MYSQL"){
	$sql->query("UPDATE products AS p
		INNER JOIN upcLike AS u ON p.upc=u.upc
		SET p.mixmatchcode=convert(u.likeCode+500,char)");

	$sql->query("UPDATE products AS p
		LEFT JOIN batchPriority AS b ON p.upc=b.upc
		SET
		p.special_price = b.salePrice,
		p.specialpricemethod = b.pricemethod,
		p.specialgroupprice=CASE WHEN b.salePrice < 0 THEN -1*b.salePrice ELSE b.salePrice END,
		p.specialquantity = b.quantity,
		p.start_date = b.startDate,
		p.end_date = b.endDate,
		p.discounttype = b.discountType,
		p.mixmatchcode = CASE 
			WHEN b.pricemethod IN (3,4) AND b.salePrice >= 0 THEN convert(b.batchID,char)
			WHEN l.pricemethod IN (3,4) AND b.salePrice < 0 THEN convert(-1*b.batchID,char)
			ELSE p.mixmatchcode 
		END	
		WHERE b.upc NOT LIKE 'LC%'
		AND b.discountType <> 0");

	$sql->query("UPDATE products AS p LEFT JOIN
		likeCodeView AS v ON v.upc=p.upc 
		batchPriority AS b ON b.upc=concat('LC',convert(v.likeCode,char))
		SET p.special_price = b.salePrice,
		p.end_date = b.endDate,p.start_date=b.startDate,
		p.specialgroupprice=CASE WHEN l.salePrice < 0 THEN -1*b.salePrice ELSE b.salePrice END,
		p.specialquantity=b.quantity,
		p.specialpricemethod=b.pricemethod,
		p.discounttype = b.discountType,
		p.mixmatchcode = CASE 
			WHEN b.pricemethod IN (3,4) AND b.salePrice >= 0 THEN convert(b.batchID,char)
			WHEN b.pricemethod IN (3,4) AND b.salePrice < 0 THEN convert(-1*b.batchID,char)
			ELSE p.mixmatchcode 
		END	
		WHERE b.upc LIKE 'LC%'
		AND b.discountType <> 0");
}
else {
	$sql->query("UPDATE products
		SET mixmatchcode=convert(varchar,u.likecode+500)
		FROM 
		products AS p
		INNER JOIN upcLike AS u
		ON p.upc=u.upc");
	$sql->query("UPDATE products 
		SET
		special_price = b.salePrice,
		specialpricemethod = b.pricemethod,
		specialgroupprice=CASE WHEN b.salePrice < 0 THEN -1*b.salePrice ELSE b.salePrice END,
		specialquantity = b.quantity,
		start_date = b.startDate,
		end_date = b.endDate,
		discounttype = b.discountType,
		mixmatchcode = CASE 
			WHEN b.pricemethod IN (3,4) AND b.salePrice >= 0 THEN convert(varchar,b.batchID)
			WHEN b.pricemethod IN (3,4) AND b.salePrice < 0 THEN convert(varchar,-1*b.batchID)
			ELSE p.mixmatchcode 
		END
		FROM products AS p
		LEFT JOIN batchPriority AS b ON p.upc=b.upc
		WHERE b.upc NOT LIKE 'LC%'
		AND b.discountType <> 0");

	$sql->query("UPDATE products SET special_price = b.salePrice,
		end_date = b.enddate,start_date=b.startdate,
		specialgroupprice=CASE WHEN b.salePrice < 0 THEN -1*b.salePrice ELSE b.salePrice END,
		specialquantity=b.quantity,
		specialpricemethod=b.pricemethod,
		discounttype = b.discounttype,
		mixmatchcode = CASE 
			WHEN b.pricemethod IN (3,4) AND b.salePrice >= 0 THEN convert(varchar,b.batchID)
			WHEN b.pricemethod IN (3,4) AND b.salePrice < 0 THEN convert(varchar,-1*b.batchID)
			ELSE p.mixmatchcode 
		END
		FROM products AS p LEFT JOIN
		likeCodeView AS v ON v.upc=p.upc LEFT JOIN
		batchPriority AS b ON b.upc='LC'+convert(varchar,v.likecode)
		WHERE b.upc LIKE 'LC%'
		AND b.discountType <> 0");
}
*/

?>
