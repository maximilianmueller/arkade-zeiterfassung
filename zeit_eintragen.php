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
  public $status;

  function AZ_Eintrag($datum, $ma) {
    $this->tag = $datum;
    $this->mitarbeiter = $ma;
    $this->pause = 0;
    $this->buero = 0;
    $this->status = "ok";
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
    if($this->status != "ok") {
      $insert .= ", status";
      $values .= ", '".$this->status.'";
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

function gleicher_tag() {
  global $dbh, $mitarbeiter, $datum;

  # Ermittle alle Datens�tze vom Typ 'arbeit' in azlog, die heute angelegt wurden und f�r die 
  # noch kein Ende eingetragen wurde  
  $sql = "select * from zeiterfassung.azlog";
  $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and typ = 'arbeit'";
  $sql .= " and ende = '00:00:00'";
  $res = mysql_query($sql, $dbh);
  if($res) return true;  
  else return false;
}
  

# Erzeugt einen neuen Datensatz in der Tabelle azlog und tr�gt die 'beginn' Zeit ein 
# oder setzt diese auf "00:00:00". Der Typ wird �bergeben. Die Werte 'mitarbeiter' und 
# 'tag' und 'beginn' werden von den globalen Werten �bernommen.
function beginn_eintragen($typ, $beginn_null = true) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  if($beginn_null) $beginn_zeit = $zeit;
  else $beginn_zeit = "00:00:00";

  $sql = "insert into zeiterfassung.azlog (tag, kuerzel, typ, beginn)";
  $sql .= " values ('".$datum."', '".$mitarbeiter."', '".$typ."', '".$beginn_zeit."');";
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


# Traegt die Ende-Zeit bei einem Log Datensatz ein. Der Datensatz existiert oder wird 
# neu angelegt (im Fehlerfall). Die Felder 'mitarbeiter' und 'tag' m�ssen zu den aktuellen 
# Werten passen. Der 'typ' muss zu dem �bergebenen Typ passen. 
function ende_eintragen($typ, $anlegen = false) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  if($anlegen = false) {
    $sql = "update zeiterfassung.azlog set ende = '".$zeit."'";
    $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and typ = '".$typ."' and ende ="."'00:00:00'";
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
  else {
    $sql = "insert into zeiterfassung.azlog (tag, kuerzel, typ, ende)";
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
# in den Zustand 'abwesend' zur�ckgekehrt wird oder im Fehlerfall. 
# Kann mehrfach pro Tag aufgerufen werden.
function schreibe_arbeitszeiten() {
  global $dbh, $error, $mitarbeiter, $datum;

  # Ermittle alle Datens�tze, die noch nicht weggeschrieben wurden  
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

  # Gehe durch die Datens�tze und trage die Werte in $eintrag ein
  for($i = 0; $i < $count; $i++) {
    $row[$i] = mysql_fetch_assoc($res);

    if($row[$i]['beginn'] == "00:00:00") $beginn_null = true;
    else $beginn_null = false;
    if($row[$i]['ende'] == "00:00:00") $ende_null = true;
    else $ende_null = false;

    switch($row[$i]['typ']) {

      case 'arbeit':
        if(!beginn_null)
          $eintrag->beginn = $row[$i]['beginn'];
        else
          $eintrag->status = "fehler";
        if(!ende_null)
          $eintrag->ende = $row[$i]['ende'];
        else
          $eintrag->status = "fehler";
        break;

      case 'pause':
        if(!beginn_null && !ende_null)
          $eintrag->pause += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        if((beginn_null && !ende_null) || (!beginn_null && ende_null))
          $eintrag->status = "fehler";
        break;

      case 'buero':
        if(!beginn_null && !ende_null)
          $eintrag->buero += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        if((beginn_null && !ende_null) || (!beginn_null && ende_null))
          $eintrag->status = "fehler";
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

      # -----------------
      # Regul�re Aktionen
      # -----------------
      case 'arbeit_beginn':
        beginn_eintragen('arbeit'); 
        setze_zustand('arbeit');
        break;

      case 'buero_beginn':
        beginn_eintragen('arbeit');
        beginn_eintragen('buero'); 
        setze_zustand('buero');
        break;    

      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------
      case 'arbeit_ende':
        ende_eintragen('arbeit', true);
        schreibe_arbeitszeiten();
        # Zustand 'abwesend' bleibt unver�ndert
        break;

      case 'buero_ende':
        ende_eintragen('arbeit', true);
        ende_eintragen('buero', true); 
        schreibe_arbeitszeiten();
        # Zustand 'abwesend' bleibt unver�ndert
        break;    

      case 'pause_beginn':
        beginn_eintragen('arbeit', false); 
        beginn_eintragen('pause'); 
        setze_zustand('pause');
        break;    

      case 'pause_ende':
        # to be done
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
 
      # -----------------
      # Regul�re Aktionen
      # -----------------
      case 'arbeit_ende':
        if(gleicher_tag()) {
          ende_eintragen('arbeit');
          schreibe_arbeitszeiten();
        }    
        else {
          schreibe_arbeitszeiten();
          ende_eintragen('arbeit', true);
          schreibe_arbeitszeiten();
        }
        setze_zustand('abwesend');
        break;

      case 'buero_beginn':
        if(gleicher_tag()) {
          beginn_eintragen('buero'); 
        }
        else {
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit'); 
          beginn_eintragen('buero'); 
        }
        setze_zustand('buero');
        break;    

      case 'pause_beginn':
        if(gleicher_tag()) {
          beginn_eintragen('pause'); 
        }
        else {
        # to be done  
        }
        setze_zustand('pause');
        break;    

      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------
      case 'arbeit_beginn':
        schreibe_arbeitszeiten();
        beginn_eintragen('arbeit'); 
        # zustand 'arbeit' bleibt unver�ndert
        break;    

      case 'buero_ende':
        if(gleicher_tag()) {
          ende_eintragen('arbeit');
          ende_eintragen('buero', true); 
          schreibe_arbeitszeiten();
        }
        else {
          schreibe_arbeitszeiten();
          ende_eintragen('arbeit', true); 
          ende_eintragen('buero', true); 
          schreibe_arbeitszeiten();
        }
        setze_zustand('abwesend');
        break;    

      case 'pause_ende':
        if(gleicher_tag()) {
          ende_eintragen('pause', true); 
        }
        else {
        # to be done  
        }
        # zustand 'arbeit' bleibt unver�ndert
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
 
      # -----------------
      # Regul�re Aktionen
      # -----------------
      case 'buero_ende':
        if(gleicher_tag()) {
          ende_eintragen('arbeit');
          ende_eintragen('buero');
          schreibe_arbeitszeiten();
        }
        else {
          # to be done
        }
        setze_zustand('abwesend');
        break;    

      case 'arbeit_beginn':
        ende_eintragen('buero'); 
        setze_zustand('arbeit');
        break;    

      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------

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
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
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