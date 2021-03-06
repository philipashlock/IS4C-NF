<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include('functMem.php');
include('headerTest.php');

$mem = $_GET['memID'];
$col='#FFFF99';

$my = isset($_GET['my'])?$_GET['my']:date('Ym');

$query = "SELECT month(tdate),day(tdate),year(tdate),trans_num,
	sum(case when trans_type='T' then -total else 0 end) as tenderTotal,
	sum(case when department=990 then total else 0 end) as payment,
	sum(case when trans_subtype='MI' then total else 0 end) as charges,
	sum(case when department in (991,992) then total else 0 end) as stock,
	sum(case when trans_subtype='MA' then total else 0 end) as madcoupon,
	sum(case when upc='DISCOUNT' then total else 0 end) as discountTTL
	FROM trans_archive.dbo.dlog$my
	WHERE card_no=$mem
	GROUP BY year(tdate),month(tdate),day(tdate),trans_num
	ORDER BY year(tdate) DESC, month(tdate) DESC,
	day(tdate) DESC";
$result =$sql->query($query);

echo "<form action=memTrans.php id=myform method=get>";
echo "<input type=hidden name=memID value=\"$mem\" />";
$ts = mktime();
echo "<select name=my onchange=\"document.getElementById('myform').submit();\">";
while(True){
	$val = date("Ym",$ts);
	printf("<option value=\"%d\" %s>%s %d</option>",
		$val,($val==$my?"selected":""),
		date("F",$ts),date("Y",$ts));

	$ts = mktime(0,0,0,date("n",$ts)-1,1,date("Y",$ts));

	if (date("Y",$ts) == 2004 && date("n",$ts) == 9)
		break;	
}
echo "</select>";

$visits = 0;
$spending = 0.0;
echo "<table cellspacing=0 cellpadding=4 border=1 style=\"font-weight:bold;\">";
while($row = $sql->fetch_row($result)){
	echo "<tr>";
	printf("<td>%d/%d/%d</td>",$row[0],$row[1],$row[2]);
	printf("<td><a href=\"reprint.php?receipt=%s&month=%d&day=%d&year=%d\">%s</a></td>",
		$row[3],$row[0],$row[1],$row[2],$row[3]);
	printf("<td>\$%.2f</td>",$row[4]);
	echo "<td>";
	if ($row[5] != 0) echo "<span style=\"color:#bb44bb;\">P</span>";
	if ($row[6] != 0) echo "<span style=\"color:#0055aa;\">C</span>";
	if ($row[7] != 0) echo "<span style=\"color:#ff3300;\">S</span>";
	if ($row[8] != 0) echo "<span style=\"color:#003311;\">MC</span>";
	if ($row[9] != 0) echo "<span style=\"color:#003333;\">%</span>";
	echo "&nbsp;</td>";
	echo "</tr>";
	$spending += $row[4];
	$visits += 1;
}
echo "</table>";
printf("<b>Visits</b>: %d<br /><b>Spending</b>: \$%.2f
	<br /><b>Avg</b>: \$%.2f",
	$visits,$spending,
	($visits > 0 ? $spending/$visits : 0));



?>
