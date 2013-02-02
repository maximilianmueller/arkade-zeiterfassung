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
$dbh = mysql_connect($host, $user, $password);

# Schreibe Header
$output = "*** Arkade Zeiterfassung Export ***\r\n\r\n\r\n";
$output .="BEGIN header\r\n\r\n";
$output .="Monat: ".$monat."\r\n";
$output .="Jahr: ".$jahr."\r\n";
$output .="Datum: ".date("d.m.Y")."\r\n";
$output .="Uhrzeit: ".date("H:i:s")."\r\n\r\n";
$output .="END header\r\n\r\n";
fputs($fh, $output);

# Schreibe azdump
$output = "BEGIN azdump\r\n\r\n";
fputs($fh, $output);


$sql = "select kuerzel from zeiterfassung.mitarbeiter";
$resk = mysql_query($sql, $dbh);
if(!$resk) {
}

while($rowk = mysql_fetch_assoc($resk)) {
  $kuerzel = $rowk['kuerzel'];
  $output = "BEGIN ".$kuerzel."\r\n";
  fputs($fh, $output);

  $suchdatum = $jahr."-".$monat."-__";
  $dbh = mysql_connect($host, $user, $password);
  $sql = "select * from zeiterfassung.azdump";
  $sql .= " where tag like '".$suchdatum."' and kuerzel = '".$kuerzel."' order by tag, beginn;";
  $resd = mysql_query($sql, $dbh);
  if(!$resd) {
  }

  while($row = mysql_fetch_assoc($resd)) {
    $output = "T ".$row['tag'];
    $output .= " M ".$row['kuerzel'];
    if(!($row['beginn'] == null)) $output .= " B ".$row['beginn'];
    if(!($row['ende'] == null)) $output .= " E ".$row['ende'];
    if(!(($row['pause'] == null) || ($row['pause'] == "00:00:00"))) $output .= " P ".$row['pause'];
    if(!(($row['buero'] == null) || ($row['buero'] == "00:00:00"))) $output .= " O ".$row['buero'];
    if(!($row['status'] == null)) $output .= " S ".$row['status'];
    if(!($row['bemerkung'] == null)) {
      $bemerkung = implode("&%&", explode(" ", $row['bemerkung']));
      $output .= " R ".$bemerkung;
    }
    $output .= "\r\n";
    fputs($fh, $output);
  }

  $output = "END ".$kuerzel."\r\n\r\n";
  fputs($fh, $output);
}

$output = "END azdump\r\n\r\n";
fputs($fh, $output);

$output = "BEGIN uks\r\n\r\n";
fputs($fh, $output);

$suchdatum = $jahr."-".$monat."-__";
$sql = "select * from zeiterfassung.uks";
$sql .= " where beginn like '".$suchdatum."' or ende like '".$suchdatum."';";
$resd = mysql_query($sql, $dbh);
if(!$resd) {
}

while($row = mysql_fetch_assoc($resd)) {
  $output = "M ".$row['kuerzel'];
  $output .= " T ".$row['typ'];
  if(!($row['beginn'] == null)) $output .= " B ".$row['beginn'];
  if(!($row['ende'] == null)) $output .= " E ".$row['ende'];
  if(!($row['bemerkung'] == null)) {
    $bemerkung = implode("&%&", explode(" ", $row['bemerkung']));
    $output .= " R ".$bemerkung;
  }
  $output .= "\r\n";
  fputs($fh, $output);
}

$output = "\r\nEND uks\r\n\r\n";
fputs($fh, $output);


fclose($fh);

?>


</body>
</html>