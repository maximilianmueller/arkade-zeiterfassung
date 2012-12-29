<?php

$mitarbeiter = $_POST['mitarbeiter'];
$correctMode = false;
if (isset ($_POST['correctMode']) && $_POST['correctMode'] == "1")
  $correctMode = true;

require "dbConfig.php";

$sql = "select * from zeiterfassung.mitarbeiter where kuerzel = '".$mitarbeiter."'";
$dbh = mysql_connect($host, $user, $password);
$res = mysql_query($sql, $dbh);
$row = mysql_fetch_assoc($res);
$zustand=$row['ze_zustand'];
$buero=$row['buero'];
mysql_close($dbh);

echo <<<END_CODEEINGABE

<!DOCTYPE HTML>
<html>

<head>
  <title>Aktion</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <script type="text/javascript" src="zeiterfassung.js"></script>
  <script type="text/javascript">
    function clickOtherActions ()
      {
      document.forms["refreshForm"].elements["correctMode"].value = "1";
      document.forms["refreshForm"].submit();
      }
    function clickAction (actionName)
      {
      document.forms["entryForm"].elements["aktion"].value = actionName;
      document.forms["entryForm"].submit();
      }
    function clickCancel ()
      {
      document.location.href = ".";
      }   
  </script>
</head>

<body onLoad="show_time()">
 <form name="refreshForm" action="ze_aktion.php" method="POST">
   <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
   <input type="hidden" name="correctMode" value="">
 </form>
 <form name="entryForm" action="zeit_eintragen.php" method="POST">
   <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
   <input type="hidden" name="zustand" value="$zustand">
   <input type="hidden" name="aktion" value="">
 </form>
  Bitte Aktion für $mitarbeiter auswählen!<br><br>
  
END_CODEEINGABE;

  if ($correctMode)
    echo "<span class='warnMsg'>Hinweis: Bitte das vergessene Stempeln auf dem Zettel \"Korrekturen / Bemerkungen\" eintragen!</span><br><br>";
  
  if ($zustand == 'abwesend' || $zustand == 'buero' || $correctMode)
    echo "<button onClick=\"clickAction('arbeit_beginn')\">Arbeitsbeginn</button>";

  if ($zustand == 'arbeit' || $correctMode)
    {
    echo "<button onClick=\"clickAction('arbeit_ende')\">Arbeitsende</button>";
    echo "<button onClick=\"clickAction('pause_beginn')\">Pausenbeginn</button>";
    }
  
  if ($zustand == 'pause' || $correctMode)
    echo "<button onClick=\"clickAction('pause_ende')\">Pausenende</button>";
  
  if (($zustand == 'abwesend' || $zustand == 'arbeit' || $correctMode) && $buero == 'ja')
    echo "<button onClick=\"clickAction('buero_beginn')\">Beginn Büroarbeit</button>";
  
  if ($zustand == 'buero' || $correctMode)
    echo "<button onClick=\"clickAction('buero_ende')\">Ende Büro</button>";

?>

  <br><br>
  <?php if (!$correctMode) { ?>
    <button onClick="clickOtherActions()">Andere Aktionen ?</button>
  <?php } ?>
  <button onClick="clickCancel()">Abbruch</button>
  
  <div class="headline" id="acttime">&nbsp;</div>

</body>

</html>