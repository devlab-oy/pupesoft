<?php

require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

if (!$fd = fopen($filename, "r")) die("Filen '$filename' avaus epäonnistui!");

$edytunnus = "luuppiinmeno_ekalla";

/* FILEFORMAATTI:
  ytunnus TAI asiakasnro JOS $tyyppi == multi_asiakasnro:
  toim_ovttunnus
  tuoteno
  kpl
  alv
  toimaika
  hinta
  ale
  netto
  var
  kommentti
  viesti aka tilausviite
  toimitustapa
*/

$_asiak_kummmalla = "YTUNNUS";

// Luetaanko asiakasnumerolla vai ytunnuksella/toim_ovttunnuksella
if (!empty($tyyppi) and $tyyppi == "multi_asiakasnro") {
  $_asiak_kummmalla = "ASIAKASNRO";
}

while ($rivi = fgets($fd)) {

  $poista = array("\"", "'", "\\");
  $rivi = str_replace($poista, "", $rivi);
  $rivi = explode("\t", trim($rivi));

  $ytunnus = trim($rivi[0]);
  $toim_ovttunnus = trim($rivi[1]);
  $tuoteno = trim($rivi[2]);

  $tarkytunnus = $ytunnus.$toim_ovttunnus;

  if ($ytunnus != "" and $tuoteno != "") {

    // asiakas vaihtuu, uusi otsikko
    if ($tarkytunnus != $edytunnus) {

      if (isset($tilaus_valmiiksi) and $edytunnus != "luuppiinmeno_ekalla" and !isset($virherivi)) {
        require "tilaus-valmis.inc";
      }

      //uusi rivi, joten voidaan rauhassa unsettaa virherivimuuttuja
      unset($virherivi);

      $query = "UPDATE kuka
                SET kesken = 0
                WHERE session = '$session'";
      $result = pupe_query($query);

      $kukarow['kesken'] = 0;
      $tilausnumero = 0;

      if ($_asiak_kummmalla == "ASIAKASNRO") {
        $asiakashaku_lis = " and asiakasnro = '$ytunnus' ";
      }
      else {
        $asiakashaku_lis = " and ytunnus = '$ytunnus' ";

        if ($toim_ovttunnus != '') {
          $asiakashaku_lis .= " and toim_ovttunnus = '$toim_ovttunnus' ";
        }
      }

      $query = "SELECT tunnus
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                {$asiakashaku_lis}
                and laji    not in ('P','R')";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        echo t("VIRHE: Tiedoilla ei löydy yhtään asiakasta").": ytunnus = '$ytunnus' $toim_lis!<br>";

        // Skipataan tämä rivi ja setataan virherivi muuttuja ettei turhaa yritettäs kutsua tilaus_valmis.inciä (or crashcaboom!).....
        $virherivi = TRUE;
        continue;
      }
      elseif (mysql_num_rows($result) > 1) {
        echo t("VIRHE: Tiedoilla löytyy useita asiakkaita").": ytunnus = '$ytunnus' $toim_lis!<br>";

        // Skipataan tämä rivi ja setataan virherivi muuttuja ettei turhaa yritettäs kutsua tilaus_valmis.inciä (or crashcaboom!).....
        $virherivi = TRUE;
        continue;
      }

      $arow = mysql_fetch_assoc($result);
      $_toimitustapa_echo = "";

      if (!empty($rivi[12])) {
        $toimitustapa = trim($rivi[12]);

        // Tarkistetaan onko käyttäjän syöttämä toimitustapa olemassa
        $apuqu = "SELECT *
                  from toimitustapa
                  where yhtio = '{$kukarow['yhtio']}'
                  and selite  = '{$toimitustapa}'";
        $meapu = pupe_query($apuqu);

        if (mysql_num_rows($meapu) == 0) {
          $toimitustapa = "";
          $_toimitustapa_echo = t('HUOM').': '.t('toimitustapaa %s ei löytynyt, käytetään asiakkaan oletusta', '', $rivi[12]);
          $_toimitustapa_echo = "<font class='error'>{$_toimitustapa_echo}</font>";
        }
      }
      else {
        $toimitustapa = "";
      }

      $id = luo_myyntitilausotsikko(
        "RIVISYOTTO",
        $arow["tunnus"],
        "",
        "",
        trim($rivi[11]),
        "",
        "",
        "",
        "",
        $tilaus_ketjutus,
        "",
        $toimitustapa
      );

      echo "Tehtiin tilaus $id asiakkaalle $ytunnus {$_toimitustapa_echo}<br>";

      // Jos ei haluta tilausvahvistusta
      if (!empty($tilaus_novahvistus)) {
        $query = "UPDATE lasku
                  SET tilausvahvistus = ''
                  WHERE tunnus = '$id'
                  AND yhtio    = '$kukarow[yhtio]'";
        pupe_query($query);
      }

      // haetaan tilauksen tiedot
      $query = "SELECT *
                FROM lasku
                WHERE tunnus = '$id'
                AND yhtio    = '$kukarow[yhtio]'";
      $result   = pupe_query($query);
      $laskurow = mysql_fetch_assoc($result);
    }

    $query = "SELECT *
              FROM tuote
              WHERE tuoteno = '$tuoteno'
              and yhtio     = '$kukarow[yhtio]'";
    $rarresult = pupe_query($query);

    if (mysql_num_rows($rarresult) == 1) {
      $trow = mysql_fetch_assoc($rarresult);

      $ytunnus         = $laskurow["ytunnus"];
      $kpl             = (float) str_replace(",", ".", $rivi[3]);
      $tuoteno         = $trow["tuoteno"];
      $toimaika        = $rivi[5] != "" ? $rivi[5] : $laskurow["toimaika"];
      $kerayspvm       = $laskurow["kerayspvm"];
      $hinta           = str_replace(",", ".", $rivi[6]);
      $netto           = strtoupper(trim($rivi[8]));
      $alv             = str_replace(",", ".", $rivi[4]);
      $var             = strtoupper(trim($rivi[9]));
      $varasto         = "";
      $rivitunnus      = "";
      $korvaavakielto  = "";
      $jtkielto        = $laskurow['jtkielto'];
      $varataan_saldoa = "";
      $myy_sarjatunnus = "";
      $kommentti       = trim($rivi[10]);
      $paikka          = "";

      for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        if ($alepostfix == 1) ${'ale'.$alepostfix} = str_replace(",", ".", $rivi[7]);
        else ${'ale'.$alepostfix} = '';
      }

      if ($hinta == 0) {
        $hinta = "";
      }

      //lisätään rivi
      require "lisaarivi.inc";
    }

    $edytunnus = $tarkytunnus;

  }

}

if (isset($tilaus_valmiiksi) and $tilaus_valmiiksi != "") {
  require "tilaus-valmis.inc";
}

fclose($fd);
