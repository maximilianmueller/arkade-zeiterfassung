<?php
require "helpers.php";

# Globale Variablen auslesen bzw. initialisieren
$mitarbeiter = $_POST['mitarbeiter'];
$aktion = $_POST['aktion'];
$zustand = $_POST['zustand'];
$error = false;

# Datum und Uhrzeit ermitteln
$datum = date("Y:m:d");
$zeit = date("H:i:s");
$zeit_minuten = date("H:i");

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

  function AZ_Eintrag($ma) {
    $this->mitarbeiter = $ma;
    $this->tag = "";
    $this->beginn = "";
    $this->ende = "";
    $this->pause = 0;
    $this->buero = 0;
    $this->status = "ok";
  }

  function eintragen() {
    global $dbh;
  
    $insert = "tag, kuerzel";
    $values = "'".$this->tag."', '".$this->mitarbeiter."'";
    if($this->beginn != "") {
      $insert .= ", beginn";
      $values .= ", '".$this->beginn."'";
    }
    if($this->ende != "") {
      $insert .= ", ende";
      $values .= ", '".$this->ende."'";
    }
    if($this->pause != 0) {
      $insert .= ", pause";
      $values .= ", '".gmdate("H:i:s", $this->pause)."'";
    }
    if($this->buero != 0) {
      $insert .= ", buero";
      $values .= ", '".gmdate("H:i:s", $this->buero)."'";
    }
    if($this->status != "ok") {
      $insert .= ", status";
      $values .= ", '".$this->status."'";
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

# Ermittle alle Datensätze vom Typ 'arbeit' in azlog, die heute angelegt wurden und für die 
# noch kein Ende eingetragen wurde  
function gleicher_tag() {
  global $dbh, $mitarbeiter, $datum;

  $sql = "select * from zeiterfassung.azlog";
  $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and typ = 'arbeit'";
  $sql .= " and ende = '00:00:00';";
  $res = mysql_query($sql, $dbh);
  return mysql_num_rows ($res) > 0;  
}
  

# Erzeugt einen neuen Datensatz in der Tabelle azlog und trägt die 'beginn' Zeit ein 
# oder setzt diese auf "00:00:00". Der Typ wird übergeben. Die Werte 'mitarbeiter' und 
# 'tag' und 'beginn' werden von den globalen Werten übernommen.
function beginn_eintragen($typ, $mit_zeit = true) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  if($mit_zeit) $beginn_zeit = $zeit;
  else $beginn_zeit = "00:00:00";

  $sql = "insert into zeiterfassung.azlog (tag, kuerzel, typ, beginn)";
  $sql .= " values ('".$datum."', '".$mitarbeiter."', '".$typ."', '".$beginn_zeit."');";
  $res = mysql_query($sql, $dbh);
  if($res) {
    return true;
  }
  else {
    $error = true;
    return false;
  }
}


# Traegt die Ende-Zeit bei einem Log Datensatz ein. Der Datensatz existiert oder wird 
# neu angelegt (im Fehlerfall). Die Felder 'mitarbeiter' und 'tag' müssen zu den aktuellen 
# Werten passen. Der 'typ' muss zu dem übergebenen Typ passen. 
function ende_eintragen($typ, $anlegen = false) {
  global $dbh, $error, $mitarbeiter, $datum, $zeit;

  if(!$anlegen) {
    # Wenn der Typ gleich 'pause' ist, können mehrere offene Pausenintervalle vorliegen.
    # In diesem Fall das letzte aussuchen. Kann theoretisch auch für Typ 'buero' vorkommen.
    $sql = "select MAX(log_id) from zeiterfassung.azlog";
    $sql .= " where kuerzel = '".$mitarbeiter."' and tag = '".$datum."' and typ = '".$typ."'";
    $sql .= " and ende = '00:00:00' and dump_flag = 0;";
    $res = mysql_query($sql, $dbh);
    if(!$res) {
      $error = true;
      return false;
    }

    $row = mysql_fetch_assoc($res);
    $log_id = $row['MAX(log_id)'];

    $sql = "update zeiterfassung.azlog set ende = '".$zeit."'";
    $sql .= " where log_id = ".$log_id.";";
    $res = mysql_query($sql, $dbh);
    if($res) {
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
    return true;
  }
  else {
    $error = true;
    return false;
  }
}



# Erzeugt einen Eintrag in der Tabelle 'azdump'. Wird aufgerufen, wenn
# in den Zustand 'abwesend' zurückgekehrt wird oder im Fehlerfall. 
# Kann mehrfach pro Tag aufgerufen werden.
function schreibe_arbeitszeiten() {
  global $dbh, $error, $mitarbeiter;

  # Ermittle alle Datensätze, die noch nicht weggeschrieben wurden  
  $sql = "select * from zeiterfassung.azlog";
  $sql .= " where kuerzel = '".$mitarbeiter."' and dump_flag = 0";
  $res = mysql_query($sql, $dbh);
  if($res) {
    $count = mysql_num_rows($res);
  }
  else {
    $error = true;
    return false;
  }
  
  # Erzeuge eine Instanz der Klasse AZ_Eintrag
  $eintrag = new AZ_Eintrag($mitarbeiter);

  # Gehe durch die Datensätze und trage die Werte in $eintrag ein
  for($i = 0; $i < $count; $i++) {
    $row[$i] = mysql_fetch_assoc($res);

    $row_tag = $row[$i]['tag'];
    if ($eintrag->tag == "")
      $eintrag->tag = $row_tag;
    else if ($eintrag->tag != $row_tag)
      {
      $error = true;
      return false;
      }
    
    if($row[$i]['beginn'] == "00:00:00") $beginn_null = true;
    else $beginn_null = false;
    if($row[$i]['ende'] == "00:00:00") $ende_null = true;
    else $ende_null = false;

    switch($row[$i]['typ']) {

      case 'arbeit':
        if(!$beginn_null)
          $eintrag->beginn = $row[$i]['beginn'];
        else
          $eintrag->status = "fehler";
        if(!$ende_null)
          $eintrag->ende = $row[$i]['ende'];
        else
          $eintrag->status = "fehler";
        break;

      case 'pause':
        if(!$beginn_null && !$ende_null)
          $eintrag->pause += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        if(($beginn_null && !$ende_null) || (!$beginn_null && $ende_null))
          $eintrag->status = "fehler";
        break;

      case 'buero':
        if(!$beginn_null && !$ende_null)
          $eintrag->buero += strtotime($row[$i]['ende']) - strtotime($row[$i]['beginn']);
        if(($beginn_null && !$ende_null) || (!$beginn_null && $ende_null))
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
  $sql .= " where kuerzel = '".$mitarbeiter."' and dump_flag = 0";
  $res = mysql_query($sql, $dbh);
  if($res) {
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
      # Reguläre Aktionen
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
        # Zustand 'abwesend' bleibt unverändert
        break;

      case 'buero_ende':
        ende_eintragen('arbeit', true);
        ende_eintragen('buero', true); 
        schreibe_arbeitszeiten();
        # Zustand 'abwesend' bleibt unverändert
        break;    

      case 'pause_beginn':
        beginn_eintragen('arbeit', false); 
        beginn_eintragen('pause'); 
        setze_zustand('pause');
        break;    

      case 'pause_ende':
        beginn_eintragen('arbeit', false); 
        ende_eintragen('pause', true); 
        setze_zustand('arbeit');
        break;    

      default:
        $error = true;
        break;
    }

    break;


  # -----------
  # arbeit
  # -----------
  case 'arbeit':

    switch($aktion) {
 
      # -----------------
      # Reguläre Aktionen
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
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit', false); 
          beginn_eintragen('pause'); 
        }
        setze_zustand('pause');
        break;    

      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------
      case 'arbeit_beginn':
        schreibe_arbeitszeiten();
        beginn_eintragen('arbeit'); 
        # zustand 'arbeit' bleibt unverändert
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
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit'); 
          ende_eintragen('pause', true); 
        }
        # zustand 'arbeit' bleibt unverändert
        break;    

      default:
        $error = true;
        break;
    }

    break;


  # -----------
  # buero
  # -----------
  case 'buero':

    switch($aktion) {
 
      # -----------------
      # Reguläre Aktionen
      # -----------------
      case 'buero_ende':
      case 'arbeit_ende':
        if(gleicher_tag()) {
          ende_eintragen('arbeit');
          ende_eintragen('buero');
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

      case 'arbeit_beginn':
        ende_eintragen('buero'); 
        setze_zustand('arbeit');
        break;    

      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------
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
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit', false); 
          beginn_eintragen('pause'); 
        }
        setze_zustand('pause');
        break;

      case 'pause_ende':
        if(gleicher_tag()) {
          ende_eintragen('pause', true); 
        }
        else {
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit'); 
          ende_eintragen('pause', true); 
        }
        setze_zustand('arbeit');
        break;    

      default:
        $error = true;
        break;
    }

    break;

  # -----------
  # pause
  # -----------
  case 'pause':

    switch($aktion) {
 
      # -----------------
      # Reguläre Aktionen
      # -----------------
      case 'pause_ende':
        ende_eintragen('pause');
        setze_zustand('arbeit');
        break;    

      
      # -------------------
      # buero_beginn aus pause heraus kann reguläre oder korrekturmodus-aktion sein
      # -------------------
      case 'buero_beginn':
        if(gleicher_tag()) {
          ende_eintragen('pause');
          beginn_eintragen('buero'); 
        }
        else {
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit'); 
          beginn_eintragen('buero'); 
        }
        setze_zustand('buero');
        break;


      # --------------------------
      # Aktionen im Korrekturmodus
      # --------------------------
      case 'arbeit_beginn':
        schreibe_arbeitszeiten();
        beginn_eintragen('arbeit');
        setze_zustand('arbeit');
        break;

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

      case 'pause_beginn':
        if(gleicher_tag()) {
          beginn_eintragen('pause'); 
        }
        else {
          schreibe_arbeitszeiten();
          beginn_eintragen('arbeit', false); 
          beginn_eintragen('pause'); 
        }
        setze_zustand('pause');
        break;    

      default:
        $error = true;
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
</head>

<body onLoad="<?php if (!$error) { ?>handleCountdown ()<?php } ?>">
  
  <?php if ($error) { ?>
    <div class="errorMsg">Fehler bei der Zeiteintragung!</div>
  <?php } else { ?>
    <div class="successMsg"><?php echo $actionNames["$aktion"]; ?> <?php echo $zeit_minuten; ?> für <?php echo $mitarbeiter; ?> eingetragen.</div>
    <br><br>
    Automatische Weiterleitung auf den Startbildschirm in <span id="countdown">&nbsp;</span> Sekunden.
  <?php } ?>
  
  <br><button onClick="clickStart()">Zum Startbildschirm</button>
  
</body>

<script type="text/javascript">

var redirSeconds = 10;
var start = new Date ().getTime ();

function handleCountdown ()
  {
  var now = new Date ().getTime ();
  var passedSeconds = Math.floor ((now - start) / 1000);
  var leftSeconds = redirSeconds - passedSeconds;
  document.getElementById ("countdown").innerHTML = leftSeconds;
  if (leftSeconds <= 0)
    clickStart ();
  else
    setTimeout ("handleCountdown ()", 1000);
  }

function clickStart ()
  {
  document.location.href = ".";
  }
  
</script>

</html>