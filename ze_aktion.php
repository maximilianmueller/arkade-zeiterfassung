<?php
require "helpers.php";

$mitarbeiter = $_POST['mitarbeiter'];
$authHash = $_POST['authHash'];
$correctMode = false;
if (isset ($_POST['correctMode']) && $_POST['correctMode'] == "1")
  $correctMode = true;

require "dbConfig.php";

$sql = "select * from zeiterfassung.mitarbeiter where kuerzel = '" . $mitarbeiter . "'";
$dbh = mysql_connect($host, $user, $password);
$res = mysql_query($sql, $dbh);
$row = mysql_fetch_assoc($res);
$manummer = $row['manummer'];
$zustand = $row['ze_zustand'];
$buero = $row['buero'];
mysql_close($dbh);

if ($authHash != md5 ($mitarbeiter . "_authHash_" . (string) $manummer))
  die ("Der eingegebene Code ist falsch.");

echo <<<END_CODEEINGABE

<!DOCTYPE HTML>
<html>

<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
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
   <input type="hidden" name="authHash" value="$authHash">
   <input type="hidden" name="correctMode" value="">
 </form>
 <form name="entryForm" action="zeit_eintragen.php" method="POST">
   <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
   <input type="hidden" name="authHash" value="$authHash">
   <input type="hidden" name="zustand" value="$zustand">
   <input type="hidden" name="aktion" value="">
 </form>
  Bitte Aktion für $mitarbeiter auswählen!<br><br>
  
END_CODEEINGABE;

  $allActions = array (
    "arbeit_beginn" => "Arbeitsbeginn",
    "arbeit_ende"   => "Arbeitsende",
    "pause_beginn"  => "Pausenbeginn",
    "pause_ende"    => "Pausenende"
  );
  if ($buero == 'ja')
    {
    $allActions["buero_beginn"] = "Büro Beginn";
    $allActions["buero_ende"]   = "Büro Ende";
    }

  $expectedActions = array ();
  if ($zustand == 'abwesend' || $zustand == 'buero')
    array_push ($expectedActions, "arbeit_beginn");
  if ($zustand == 'arbeit')
    {
    array_push ($expectedActions, "arbeit_ende");
    array_push ($expectedActions, "pause_beginn");
    }
  if ($zustand == 'pause')
    array_push ($expectedActions, "pause_ende");
  if ($buero == 'ja')
    {
    if ($zustand == 'abwesend' || $zustand == 'arbeit')
      array_push ($expectedActions, "buero_beginn");
    if ($zustand == 'buero')
      array_push ($expectedActions, "buero_ende");
    }

  if ($correctMode)
    {
    $expectedText = enumerate ($expectedActions, ", ", " oder ", $allActions) . " ?";
    echo "<span class='warnMsg'>Hinweis: Bitte das vergessene Stempeln ($expectedText) auf dem Zettel \"Korrekturen / Bemerkungen\" eintragen!</span><br><br>";
    }

  foreach ($allActions as $actKey => $actText)
    {
    $isExpected = in_array ($actKey, $expectedActions);
    if ($isExpected && !$correctMode || $correctMode && !$isExpected)
      echo "<button onClick=\"clickAction('$actKey')\">$actText</button>";
    }

?>

  <br><br>
  <?php if (!$correctMode) { ?>
    <button onClick="clickOtherActions()">Andere Aktionen ?</button>
  <?php } ?>
  <button onClick="clickCancel()">Abbruch</button>
  
  <div class="headline" id="acttime">&nbsp;</div>

</body>

</html>