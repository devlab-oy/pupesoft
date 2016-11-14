<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Asiakkaan Y-tunnuksen vaihto")."</font><hr>";
echo t("P‰ivitet‰‰n: asiakkaat, asiakasalennukset, asiakashinnat, asiakaskommentit, avoimet tilaukset, korvaavien tuotteiden kiellot ja rahtisopimukset");
echo "<br>";
echo "<br>";

$tee = (isset($tee)) ? $tee : "";

$error = 0;

$vanytunnus = (isset($vanytunnus)) ? trim($vanytunnus) : "";
$uusytunnus = (isset($uusytunnus)) ? trim($uusytunnus) : "";
$uusnimi = (isset($uusnimi)) ? trim($uusnimi) : "";

if ($tee == "vaihda" and empty($vanytunnus)) {
  echo t("VIRHE: Vanha ytunnus puuttuu")."!<br><br>";
  $error = 1;
  $tee = "";
}

if ($tee == "vaihda" and empty($uusytunnus)) {
  echo t("VIRHE: Uusi ytunnus puuttuu")."!<br><br>";
  $error = 1;
  $tee = "";
}

if ($error == 0 and $tee == "vaihda") {

  $nimilisa = "";

  if (!empty($uusnimi)) {
    $nimilisa = ", nimi = '$uusnimi' ";
  }

  $query = "UPDATE asiakas
            SET ytunnus = '$uusytunnus'
            {$nimilisa}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  $query = "UPDATE asiakasalennus
            SET ytunnus = '$uusytunnus'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  $query = "UPDATE asiakashinta
            SET ytunnus = '$uusytunnus'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  $query = "UPDATE asiakaskommentti
            SET ytunnus = '$uusytunnus'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  $query = "UPDATE korvaavat_kiellot
            SET ytunnus = '$uusytunnus'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  $query = "UPDATE lasku
            SET ytunnus = '$uusytunnus'
            {$nimilisa}
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'
            and (
                (tila IN ('L','N','R','V','E','C') AND alatila != 'X')
                OR
                (tila = 'T' AND alatila in ('','A'))
                OR
                (tila IN ('A','0'))
              )";
  pupe_query($query);

  $query = "UPDATE rahtisopimukset
            SET ytunnus = '$uusytunnus'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND ytunnus = '$vanytunnus'";
  pupe_query($query);

  echo t("Valmis")."!<br><br>";
  $tee = "";
}

if ($tee == "") {

  echo "<form method='post'>

      <table>
      <tr>
        <th>".t("Vanha y-tunnus").":</th>
        <td><input type='text' name='vanytunnus' size='25'></td>
      </tr>

      <tr>
        <th>".t("Uusi y-tunnus").":</th>
        <td><input type='text' name='uusytunnus' size='25'></td>
      </tr>
      <tr>
        <th>".t("Uusi nimi").":</th>
        <td><input type='text' name='uusnimi' size='25'></td>
      </tr>
      </table>

      <br>
      <input type='hidden' name='tee' value='vaihda'>
      <input type='submit' value='".t("Vaihda asiakaan ytunnus")."'>
      </form>";
}

require "inc/footer.inc";
