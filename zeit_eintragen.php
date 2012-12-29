<?php

# Globale Variablen auslesen bzw. initialisieren
$mitarbeiter = $_POST['mitarbeiter'];
$aktion = $_POST['aktion'];
$zustand = $_POST['zustand'];
$error = false;

# Datum und Uhrzeit ermitteln
$datum = date("Y:m:d");
$zeit = date("H:i:s");

# Mit Datenbank verbinden
require "dbConfig.php";
$dbh = mysql_connect($host, $user, $password);


#------------------------------------------------------------------------------
#
# Klasse AZ_Eintrag
#
#------------------------------------------------------------------------------

class AZ_Eintrag {
  public $mitarbeiter;
  public $tag;
  public $beginn;
  public $ende;
  public $pause;
  public $buero;

  function AZ_Eintrag($datum, $ma) {
    $this->tag = $datum;
    $this->mitarbeiter = $ma;
    $this->pause = 0;
    $this->buero = 0;
  }

  function eintragen() {
    global $dbh;
  
    $insert = "tag, kuerzel";
    $values = "'".$this->tag."', '".$this->mitarbeiter."'";
    if($this->beginn != 0) {
      $insert .= ", beginn";
      $values .= ", '".$this->beginn."'";
    }
    if($this->ende != 0) {
      $insert .= ", ende";
      $values .= ", '".$this->ende."'";
    }
    if($this->pause != 0) {
      $insert .= ", pause";
      $values .= ", '".date("H:i:s", $this->pause)."'";
    }
    if($this->buero != 0) {
      $insert .= ", buero";
      $values .= ", '".date("H:i:s", $this->buero)."'";
    }

    $sql = "insert into zeiterfassung.azdump (".$insert.")";
    $sql .= " values (".$values.");";

    $res = mysql_query($sql, $dbh);
    if($res) {
      return true;
    }
    else {
      return false;
    }
  }
}
      




#------------------------------------------------------------------------------
#
# Hilfsfunktionen
#
#------------------------------------------------------------------------------

# Erzeugt einen neuen Datensatz in der Tabelle azlog und trägt die 'beginn' Zeit ein.
# Der Typ wird übergeben. Die Werte 'mitarbeiter' und 'tag' und 'beginn' werden von den globalen
# Werten übernommen.
function beginn_eintragen($typ) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  $sql = "insert into zeiterfassung.azlog (tag, kuerzel, typ, beginn)";
  $sql .= " values ('".$datum."', '".$mitarbeiter."', '".$typ."', '".$zeit."');";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $error = false;
    return true;
  }
  else {
    $error = true;
    return false;
  }
}


# Traegt die Ende-Zeit bei einem Log Datensatz ein. Der Datensatz muß existieren und die 'ende' Zeit
# muß zudem leer sein. Die Felder 'mitarbeiter' und 'tag' müssen zu den aktuellen Werten passen. Der 'typ'
# muss zu dem übergebenen Typ passen. 
function ende_eintragen($typ) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  $sql = "update zeiterfassung.azlog set ende = '".$zeit."'";
  $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and typ = '".$typ."' and ende is null";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $error = false;
    return true;
  }
  else {
    $error = true;
    return false;
  }
}


# Setzt den Zustand eines Mitarbeiters in der 'mitarbeiter' Tabelle
function setze_zustand($zustand) {
  global $dbh, $error, $mitarbeiter; 

  $sql = "update zeiterfassung.mitarbeiter set ze_zustand = '".$zustand."' where kuerzel = '".$mitarbeiter."';";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $error = false;
    return true;
  }
  else {
    $error = true;
    return false;
  }
}



# Erzeugt einen Eintrag in der Tabelle 'azdump'. Wird aufgerufen, wenn
# in den Zustand 'abwesend' zurückgekehrt wird. Kann mehrfach pro Tag aufgerufen
# werden.
function schreibe_arbeitszeiten() {
  global $dbh, $error, $mitarbeiter, $datum;

  # Ermittle alle Datensätze, die noch nicht weggeschrieben wurden  
  $sql = "select * from zeiterfassung.azlog";
  $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and dump_flag = 0";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $count = mysql_num_rows($res);
  }
  else {
    $error = true;
    return false;
  }
  
  # Erzeuge eine Instanz der Klasse AZ_Eintrag
  $eintrag = new AZ_Eintrag($datum, $mitarbeiter);

  # Gehe durch die Datensätze und trage die Werte in $eintrag ein
  for($i = 0; $i < $count; $i++) {
    $row[$i] = mysql_fetch_assoc($res);

    switch($row[$i]['typ']) {

      case 'arbeit':
        $eintrag->beginn = $row[$i]['beginn'];
        $eintrag->ende = $row[$i]['ende'];
        break;

      case 'pause':
        $eintrag->pause += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        break;

      case 'buero':
        $eintrag->buero += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        break;

    }
  }


  if(!$eintrag->eintragen()) {
    $error = true;
    return false;
  }

  # Setze das dump_flag
  $sql = "update zeiterfassung.azlog set dump_flag = 1";
  $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and dump_flag = 0";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $error = false;
    return true;
  }
  else {
    $error = true;
    return false;
  }
}




#------------------------------------------------------------------------------
#
# Hauptskript
#
#------------------------------------------------------------------------------

switch($zustand) {

  # -------------
  # abwesend
  # -------------
  case 'abwesend':

    switch($aktion) {

      case 'arbeit_beginn':
        beginn_eintragen('arbeit'); 
        setze_zustand('arbeit');
        break;    

      case 'buero_beginn':
        beginn_eintragen('arbeit');
        beginn_eintragen('buero'); 
        setze_zustand('buero');
        break;    

      default:
        break;
    }

    break;


  # -----------
  # arbeit
  # -----------
  case 'arbeit':

    switch($aktion) {
 
      case 'arbeit_ende':
        ende_eintragen('arbeit');
        setze_zustand('abwesend');
        schreibe_arbeitszeiten();
        break;    

      case 'buero_beginn':
        beginn_eintragen('buero'); 
        setze_zustand('buero');
        break;    

      case 'pause_beginn':
        beginn_eintragen('pause'); 
        setze_zustand('pause');
        break;    

      default:
        break;
    }

    break;


  # -----------
  # buero
  # -----------
  case 'buero':

    switch($aktion) {
 
      case 'buero_ende':
        ende_eintragen('arbeit');
        ende_eintragen('buero');
        setze_zustand('abwesend');
        schreibe_arbeitszeiten();
        break;    

      case 'arbeit_beginn':
        ende_eintragen('buero'); 
        setze_zustand('arbeit');
        break;    

      default:
        break;
    }

    break;

  # -----------
  # pause
  # -----------
  case 'pause':

    switch($aktion) {
 
      case 'pause_ende':
        ende_eintragen('pause');
        setze_zustand('arbeit');
        break;    

      default:
        break;
    }

    break;
}


mysql_close($dbh);

?>

<!DOCTYPE HTML>

<html>
<head>
  <title>Zeit Eintragen</title>
  <link rel="stylesheet" type="text/css" href="zeiterfassung.css"/>
  <?php if (!$error) { ?>
    <meta http-equiv="refresh" content="2; URL=.">
  <?php } ?>
</head>
<body>

  <?php if ($error) { ?>
  
    <div class="errorMsg">Fehler bei der Zeiteintragung!</div>
    <button onClick="clickStart()">Zum Startbildschirm</button>
    
  <?php } else { ?>
  
    <div class="successMsg">Zeit erfolgreich eingetragen!</div>
    Automatische Weiterleitung auf den Startbildschirm in wenigen Sekunden.
    
  <?php } ?>

</body>
</html>