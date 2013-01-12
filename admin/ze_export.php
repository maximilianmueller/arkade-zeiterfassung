<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Arkade Zeiterfassung - Administration</title>
  <link rel="stylesheet" type="text/css" href="../zeiterfassung.css"/>
  <link rel="stylesheet" type="text/css" href="zeiterfassung_admin.css"/>
</head>

<body>

<?php include "navi.php"; ?>
<br>
<h3>Arkade Zeiterfassung Admin - Datenexport nach Calc</h3>

<?php 

require "../dbConfig.php";
$monat = $_POST['monat'];
$jahr = $_POST['jahr'];

$dateiname = "AZ_Export_".$monat."_".$jahr.".txt";

$fh = fopen($dateiname, "wb");

# Schreibe Header
$output = "*** Arkade Zeiterfassung Export ***\r\n\r\n";
$output .="Monat: ".$monat."\r\n";
$output .="Jahr : ".$jahr."\r\n\r\n";
fputs($fh, $output);

# Schreibe azdump
$output = "BEGIN azdump\r\n";
fputs($fh, $output);

$suchdatum = $jahr."-".$monat."-__";
$dbh = mysql_connect($host, $user, $password);
$sql = "select * from zeiterfassung.azdump";
$sql .= " where tag like '".$suchdatum."';";
$res = mysql_query($sql, $dbh);
if(!$res) {
}


while($row = mysql_fetch_assoc($res)) {
  $output = "T ".$row['tag'];
  $output .= " M ".$row['kuerzel'];
  if(!($row['beginn'] == null)) $output .= " B ".$row['beginn'];
  if(!($row['ende'] == null)) $output .= " E ".$row['ende'];
  if(!($row['pause'] == null)) $output .= " P ".$row['pause'];
  if(!($row['buero'] == null)) $output .= " O ".$row['buero'];
  $output .= "\r\n";
  fputs($fh, $output);
}

$output = "END azdump\r\n";
fputs($fh, $output);

fclose($fh);

?>


</body>
</html>