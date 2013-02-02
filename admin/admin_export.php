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
$monat = date("n");
if($monat == 1) $monat = 12;
else $monat = $monat - 1;
$jahr = date("Y");
if($monat ==12) $jahr = $jahr - 1;
?>


<form action="ze_export.php" method="post">

<select name="monat" size="1"> 
<?php 
$monate = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"); 
for($i = 1; $i <= 12; $i++) { 
    echo "<option value=";
    if($i < 10) echo "0";
    echo "$i"; 
    if($i==$monat) echo " selected";
    $j = $i-1; 
    echo ">$monate[$j]</option>"; 
} 
?> 
</select> 

<select name="jahr" size="1"> 
<?php 
for($i = 2012; $i <= 2023; $i++) { 
    echo "<option value=$i"; 
    if($i==$jahr) echo " selected";
    echo ">$i</option>"; 
} 
?> 

<input type="submit" value="Export Starten">

</form>

</body>
</html>