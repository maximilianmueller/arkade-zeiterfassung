<?php
$mitarbeiter = $_GET['mitarbeiter'];
$md5Hash = $_GET['md5Hash'];


echo <<<END_CODEEINGABE





<!DOCTYPE HTML>

<html>

<head>
  <title>Codeeingabe</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <script type="text/javascript" src="zeiterfassung.js"></script>
  <script type="text/javascript" src="md5.js"></script>

  <script type="text/javascript">
    function add_digit(digit) {
      var eingabe = document.getElementById("eingabe");
      eingabe.value = eingabe.value + digit;
    }
    function del_digit() {
      var eingabe = document.getElementById("eingabe");
      eingabe.value = eingabe.value.substring(0, eingabe.value.length-1);
    }        
    function clickOK() {
      var co = document.getElementById("eingabe");
      var md5Hash = MD5 ("$mitarbeiter" + co.value);
      if (md5Hash == "$md5Hash")
        {
        document.forms["forwardForm"].action = "ze_aktion.php";
        document.forms["forwardForm"].submit();
        }
      else
        alert ("Der eingegebene Code ist nicht korrekt.");
    }
    function clickCancel ()
      {
      document.location.href = ".";
      }    

  </script>

</head>

<body>
  Bitte Code für $mitarbeiter eingeben!
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
      <td colspan="2"><button onclick="del_digit()">Löschen</button></td>
      <td colspan="2"><button onclick="clickCancel()">Abbruch</button></td>
    </tr>
    <tr>
      <td colspan="3"><input type="text" maxlength="4" id="eingabe"></td>
      <td colspan="2"><button onclick="clickOK()">OK</button></td>
    </tr>
   </table>
   <form name="forwardForm" method="POST">
     <input type="hidden" name="mitarbeiter" value="$mitarbeiter">
   </form>
</body>

</html>
END_CODEEINGABE;
?>

