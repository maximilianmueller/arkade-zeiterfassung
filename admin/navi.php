
<a href="index.php">Start</a> |
<a href="admin_ma.php">Mitarbeiter</a> |
<a href="admin_dump.php">Zeiterfassung Ausgabe</a> |
<a href="admin_log.php">Zeiterfassung Log</a> |
<a href="admin_uks.php">Urlaub Krank Schule</a> |
<a href="admin_export.php">Datenexport nach Calc</a> |
<a href="javascript:doLogout()">Ausloggen</a>

<script type="text/javascript">

function doLogout ()
  {
  alert ("Sie werden jetzt ausgeloggt. Bitte anschlieﬂend das Fenster schlieﬂen.")
  if (document.execCommand && document.queryCommandSupported && document.queryCommandSupported("ClearAuthenticationCache"))
    {
    // clear HTTP Authentication (e.g. in IE)
    document.execCommand("ClearAuthenticationCache");
    window.location.href = "//" + window.location.host + window.location.pathname;
    }
  else
    {
    // use invalid username
    window.location.href = "//logout@" + window.location.host + window.location.pathname;
    }
  }

</script>
