<!DOCTYPE HTML>
<?php require "dbConfig.php"; ?>
<html>

<head>
  <title>Arkade Arbeitszeiterfassung</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <script type="text/javascript" src="zeiterfassung.js"></script>
</head>

<body onLoad="show_time()">
  
  <div class="headline">Arkade Zeiterfassung</div>
  <?php
  $sql = "select * from zeiterfassung.mitarbeiter";
  $dbh = mysql_connect($host, $user, $password);
  $res = mysql_query($sql, $dbh);
  while ($row = mysql_fetch_assoc($res))
    {
    $kuerzel = $row['kuerzel'];
    $manummer = $row['manummer'];
    $zustand = $row['ze_zustand'];
    $md5Hash = md5 ($kuerzel . (string) $manummer);
    echo <<<END_USER
    <a href="javascript:clickUser('$kuerzel', '$md5Hash')"><div class="userWrapper"><div class="userBox userBox_$zustand">
      $kuerzel<br><span class="userState">[$zustand]</span>
    </div></div></a>
END_USER;
    }
  mysql_close($dbh);
  ?>
  <div class="headline" id="acttime">&nbsp;</div>
  <form name="forwardForm" action="codeeingabe.php" method="POST">
    <input type="hidden" name="mitarbeiter" value="">
    <input type="hidden" name="md5Hash" value="">
  </form>
  
</body>

<script type="text/javascript">
function clickUser (kuerzel, md5Hash)
  {
  document.forms["forwardForm"].elements["mitarbeiter"].value = kuerzel;
  document.forms["forwardForm"].elements["md5Hash"].value = md5Hash;
  document.forms["forwardForm"].submit();
  }
</script>

</html>
