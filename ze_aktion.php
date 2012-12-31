<?php
require "helpers.php";

$mitarbeiter = $_POST['mitarbeiter'];
$authHash = $_POST['authHash'];
$correctMode = (isset ($_POST['correctMode']) && $_POST['correctMode'] == "1");
$showTimes = (isset ($_POST['showTimes']) && $_POST['showTimes'] == "1");

require "dbConfig.php";
$dbh = mysql_connect($host, $user, $password);

$sql = "select * from zeiterfassung.mitarbeiter where kuerzel = '" . $mitarbeiter . "'";
$res = mysql_query($sql, $dbh);
$row = mysql_fetch_assoc($res);
$manummer = $row['manummer'];
$zustand = $row['ze_zustand'];
$buero = $row['buero'];

if ($authHash != md5 ($mitarbeiter . "_authHash_" . (string) $manummer))
  die ("Der eingegebene Code ist falsch.");

echo <<<END_BLOCK

<!DOCTYPE HTML>
<html>

<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
  <title>Aktion</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <script type="text/javascript" src="zeiterfassung.js"></script>
</head>

<body onLoad="show_time()">
  <form name="refreshForm" action="ze_aktion.php" method="POST">
    <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
    <input type="hidden" name="authHash" value="$authHash">
    <input type="hidden" name="correctMode" value="$correctMode">
    <input type="hidden" name="showTimes" value="$showTimes">
  </form>
  <form name="entryForm" action="zeit_eintragen.php" method="POST">
    <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
    <input type="hidden" name="authHash" value="$authHash">
    <input type="hidden" name="zustand" value="$zustand">
    <input type="hidden" name="aktion" value="">
  </form>

END_BLOCK;
?>

  <table>
    <tr>

      <td valign="top">
        Bitte Aktion für <?php echo $mitarbeiter; ?> auswählen!<br><br>
        <?php
        $allActions = array ();
        $allActions["arbeit_beginn"] = "Arbeitsbeginn";
        $allActions["pause_beginn"]  = "Pausenbeginn";
        $allActions["pause_ende"]    = "Pausenende";
        $allActions["arbeit_ende"]   = "Arbeitsende";
        if ($buero == 'ja')
          {
          $allActions["buero_beginn"] = "Büro Beginn";
          $allActions["buero_ende"]   = "Büro & Arbeit beenden";
          }
      
        $expectedActions = array ();
        if ($zustand == 'abwesend' || $zustand == 'buero')
          array_push ($expectedActions, "arbeit_beginn");
        if ($zustand == 'arbeit')
          {
          array_push ($expectedActions, "pause_beginn");
          array_push ($expectedActions, "arbeit_ende");
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
        <?php if (!$showTimes) { ?>
          <button onClick="clickShowTimes()">Zeiten anzeigen</button>
        <?php } ?>
        <button onClick="clickCancel()">Abbruch</button>
      </td>
      
      <td valign="top" style="padding-left: 25px;">
        <?php
        if ($showTimes)
          {
          $sql = "select * from zeiterfassung.azdump where kuerzel = '" . $mitarbeiter . "'";
          $sql .= " and tag like '" . date ('Y-m-') . "%'";
          $sql .= " order by tag asc, beginn asc";
          $res = mysql_query($sql, $dbh);
          if($res)
            {
            echo "Zeiten des aktuellen Monats:<br><br><table cellspacing='0' class='monthTimesheet'>";
            echo "<tr>";
            echo "<td>Tag</td>";
            echo "<td>Beginn</td>";
            echo "<td>Ende</td>";
            echo "<td>Pause</td>";
            if ($buero == 'ja')
              echo "<td>Büro</td>";
            echo "</tr>";
            $count = mysql_num_rows($res);
            for($i = 0; $i < $count; $i++)
              {
              $row = mysql_fetch_assoc($res);
              echo "<tr>";
              echo "<td><nobr>{$row['tag']}</nobr></td>";
              echo "<td>{$row['beginn']}</td>";
              echo "<td>{$row['ende']}</td>";
              echo "<td>{$row['pause']}</td>";
              if ($buero == 'ja')
                echo "<td>{$row['buero']}</td>";
              echo "</tr>";
              }
            echo "</table>";
            }
          }
        ?>
      </td>
      
    </tr>
  </table>
  
  <div class="headline" id="acttime">&nbsp;</div>

</body>

<script type="text/javascript">
  function clickOtherActions ()
    {
    document.forms["refreshForm"].elements["correctMode"].value = "1";
    document.forms["refreshForm"].submit();
    }
  function clickShowTimes ()
    {
    document.forms["refreshForm"].elements["showTimes"].value = "1";
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

</html>
<?php
mysql_close($dbh);
?>
