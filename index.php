<!DOCTYPE HTML>
<?php require "dbConfig.php"; ?>
<html>

<head>
  <title>Arkade Arbeitszeiterfassung</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <script type="text/javascript" src="zeiterfassung.js"></script>
	<link href="jquery/css/vader/jquery-ui-1.9.2.custom.css" rel="stylesheet">
	<script src="jquery/js/jquery-1.8.3.js"></script>
	<script src="jquery/js/jquery-ui-1.9.2.custom.js"></script>
  <script type="text/javascript" src="md5.js"></script>
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
    <input type="hidden" id="md5Hash_$kuerzel" value="$md5Hash">
    <a href="javascript:clickUser('$kuerzel')"><div class="userWrapper"><div class="userBox userBox_$zustand">
      $kuerzel<br><span class="userState">[$zustand]</span>
    </div></div></a>
END_USER;
    }
  mysql_close($dbh);
  ?>
  <div class="headline" id="acttime">&nbsp;</div>

  <div id="zehnertastatur">
    Bitte Code f�r <span id="mitarbeiterName">&nbsp;</span> eingeben!
    <table class="zehnertastatur">
      <tr>
        <td><button onclick="add_digit('1')">1</button></td>
        <td><button onclick="add_digit('2')">2</button></td>
        <td><button onclick="add_digit('3')">3</button></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td><button onclick="add_digit('4')">4</button></td>
        <td><button onclick="add_digit('5')">5</button></td>
        <td><button onclick="add_digit('6')">6</button></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td><button onclick="add_digit('7')">7</button></td>
        <td><button onclick="add_digit('8')">8</button></td>
        <td><button onclick="add_digit('9')">9</button></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td><button onclick="add_digit('0')">0</button></td>
        <td colspan="2"><button onclick="del_digit()">L�schen</button></td>
        <td colspan="2"><button onclick="clickCancel()">Abbruch</button></td>
      </tr>
      <tr>
        <td colspan="3"><input type="text" maxlength="4" id="eingabe"></td>
        <td colspan="2"><button onclick="clickOK()">OK</button></td>
      </tr>
    </table>
  </div>

  <form name="forwardForm" method="POST">
    <input type="hidden" name="mitarbeiter" value="">
  </form>
  
</body>

<script type="text/javascript">

var jq = jQuery;
var md5Hash;
var mitarbeiter;

jq(function() {
  jq("#zehnertastatur").dialog({
    autoOpen: false,
    width: "450px",
    modal: true
  });
});

function clickUser (kuerzel)
  {
  mitarbeiter = kuerzel;
  md5Hash = document.getElementById("md5Hash_" + kuerzel).value;
  jq("#mitarbeiterName").text(kuerzel);
  jq("#zehnertastatur").dialog("open");
  }
function add_digit (digit)
  {
  var eingabe = document.getElementById("eingabe");
  eingabe.value = eingabe.value + digit;
  }
function del_digit ()
  {
  var eingabe = document.getElementById("eingabe");
  eingabe.value = eingabe.value.substring(0, eingabe.value.length-1);
  }
function clickOK ()
  {
  var co = document.getElementById("eingabe");
  var _md5Hash = MD5 (mitarbeiter + co.value);
  if (_md5Hash == md5Hash)
    {
    document.forms["forwardForm"].action = "ze_aktion.php";
    document.forms["forwardForm"].elements["mitarbeiter"].value = mitarbeiter;
    document.forms["forwardForm"].submit();
    }
  else
    alert ("Der eingegebene Code ist nicht korrekt.");
  }
function clickCancel ()
  {
  jq("#zehnertastatur").dialog("close");
  }
</script>

</html>
