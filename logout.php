<?php

$no_head = "yes";

require "inc/parametrit.inc";

$query = "UPDATE kuka SET
          session       = '',
          kesken        = 0
          WHERE session = '$session'";
$result = pupe_query($query);

$bool = setcookie("pupesoft_session", "", time()-43200, "/");

echo "<html>
  <head>
      <title>$yhtiorow[nimi]</title>
    <meta http-equiv=\"Cache-Control\" Content=\"no-cache\">
    <meta http-equiv=\"Pragma\" Content=\"no-cache\">
    <meta http-equiv=\"Expires\" Content=\"-1\">
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
  </head>
  <body>";

if (isset($toim) and $toim == 'change') {
  echo "<form name='change' target='_top' action='$palvelin2' method='post'>";
  echo "<input type='hidden' name='user' value='$kukarow[kuka]'>";
  echo "<input type='hidden' name='salamd5' value='$kukarow[salasana]'>";
  echo "</form>";

  echo "<script>change.submit();</script>";
}
else {
  if (isset($location) and $location != "") {
    echo "<script>setTimeout(\"parent.location.href='$location'\",0);</script>";
  }
  else {
    echo "<script>setTimeout(\"parent.location.href='$palvelin2'\",0);</script>";
  }
}

echo "</body>\n</html>";
