<?php

if (!isset($tee)) $tee = "";

if (strpos($_SERVER['SCRIPT_NAME'], "synkronoi_mysqlaliakset.php") !== FALSE) {
  require "inc/parametrit.inc";

  echo "<font class='head'>".t("Mysqlaliaksien synkronointi")."</font><hr>";
}

if ($tee == "TEE" or strpos($_SERVER['SCRIPT_NAME'], "iltasiivo.php") !== FALSE) {

  $ch  = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://api.devlab.fi/referenssialiakset.sql");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  $aliakset = curl_exec($ch);

  // K‰‰nnet‰‰n aliakset UTF-8 muotoon, jos Pupe on UTF-8:ssa
  if (PUPE_UNICODE) {
    // T‰ss‰ on "//NO_MB_OVERLOAD"-kommentti
    // jotta UTF8-konversio ei osu t‰h‰n riviin
    $aliakset = utf8_encode($aliakset); //NO_MB_OVERLOAD
  }

  $aliakset = explode("\n", trim($aliakset));

  $taulut = array();
  $rivit  = array();

  // Eka rivi roskikseen
  array_shift($aliakset);

  foreach ($aliakset as $rivi) {
    list($perhe, $kieli, $selite, $selitetark, $selitetark_2, $selitetark_3, $jarjestys, $nakyvyys) = explode("\t", trim($rivi));

    list($taulu, $sarake) = explode(".", $selite);

    $taulut[] = $taulu."###".$selitetark_2;
    $rivit[]  = $rivi;
  }

  $taulut = array_unique($taulut);

  if ($tee == "TEE") echo "<br><table>";

  foreach ($rivit as $rivi) {
    list($perhe, $kieli, $selite, $selitetark, $selitetark_2, $selitetark_3, $selitetark_4, $jarjestys, $nakyvyys) = explode("\t", trim($rivi));

    $sanakirjaquery = "SELECT *
                       FROM avainsana
                       WHERE yhtio      = '$kukarow[yhtio]'
                       and laji         = 'MYSQLALIAS'
                       and selite       = '$selite'
                       and selitetark_2 = '$selitetark_2'";
    $sanakirjaresult = pupe_query($sanakirjaquery);

    if (mysql_num_rows($sanakirjaresult) > 0) {
      $sanakirjaquery  = "UPDATE avainsana SET
                          selitetark       = '$selitetark'
                          WHERE yhtio      = '$kukarow[yhtio]'
                          AND laji         = 'MYSQLALIAS'
                          AND selite       = '$selite'
                          AND selitetark_2 = '$selitetark_2'";
      pupe_query($sanakirjaquery);

      if ($tee == "TEE") echo "<tr><th>".t("P‰ivitet‰‰n mysqlalias")."</th><td>$selite</td><td>$selitetark</td><td>$selitetark_2</td></tr>";
    }
    else {
      $sanakirjaquery  = "INSERT INTO avainsana SET
                          yhtio        = '$kukarow[yhtio]',
                          laji         = 'MYSQLALIAS',
                          perhe        = '$perhe',
                          kieli        = '$kieli',
                          nakyvyys     = '$nakyvyys',
                          selite       = '$selite',
                          selitetark   = '$selitetark',
                          selitetark_2 = '$selitetark_2',
                          selitetark_3 = '$selitetark_3',
                          selitetark_4 = '$selitetark_4',
                          jarjestys    = '$jarjestys',
                          laatija      = '$kukarow[kuka]',
                          luontiaika   = now()";
      pupe_query($sanakirjaquery);

      if ($tee == "TEE") echo "<tr><th>".t("Lis‰t‰‰n mysqlalias")."</th><td>$selite</td><td>$selitetark</td><td>$selitetark_2</td></tr>";
    }
  }

  if ($tee == "TEE") {
    echo "</table><br>";
    echo "<br>".t("Mysqlaliakset synkronoitu onnistuneesti")."!<br>";
  }
  else {
    $iltasiivo .= is_log("Mysqlaliakset synkronoitu onnistuneesti.");
  }
}

if (strpos($_SERVER['SCRIPT_NAME'], "synkronoi_mysqlaliakset.php") !== FALSE) {
  echo "  <br><br>
      <form method='post'>
      <input type='hidden' name='tee' value='TEE'>
      <input type='submit' value='".t("Hae uusimmat mysqlaliakset")."'>
      </form>";

  require "inc/footer.inc";
}
