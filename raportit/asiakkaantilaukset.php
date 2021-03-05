<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;

// DataTables päälle
$pupe_DataTables = "asiakkaantilaukset";

require "../inc/parametrit.inc";

if ($livesearch_tee == 'ASIAKKAANTILAUSNUMERO') {
  livesearch_asiakkaantilausnumero($toim);
  exit;
}

if ($livesearch_tee == 'TILAUSVIITE') {
  livesearch_asiakkaantilausnumero($toim, "viesti");
  exit;
}

if (substr($toim, 0, 8) == "KONSERNI") {
  $logistiikka_yhtio = '';
  $logistiikka_yhtiolisa = '';
  $lasku_yhtio_originaali = $kukarow['yhtio'];
  $konserni = "KONSERNIVARASTO";

  if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
    $logistiikka_yhtio = $konsernivarasto_yhtiot;
    $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

    if ($lasku_yhtio != '') {
      $kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
      $yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
    }
  }
  else {
    $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
  }

  $cleantoim = substr($toim, 8);
}
else {
  $logistiikka_yhtio = '';
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
  $lasku_yhtio_originaali = $kukarow['yhtio'];

  $cleantoim = $toim;
}

if (isset($vaihda)) {
  unset($asiakasid);
  unset($toimittajaid);
  unset($ytunnus);
}

if (!isset($kka))
  $kka = date("m", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva))
  $vva = date("Y", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa))
  $ppa = date("d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));

if (!isset($kkl))
  $kkl = date("m");
if (!isset($vvl))
  $vvl = date("Y");
if (!isset($ppl))
  $ppl = date("d");

$til = "";

if ($cleantoim == 'MYYNTI') {
  echo "<font class='head'>".t("Asiakkaan tilaukset").":</font><hr>";

  $til = " tila in ('L','U','N','R','E','D','G') ";
}
if ($cleantoim == 'VALMISTUSMYYNTI') {
  echo "<font class='head'>".t("Asiakkaan tilaukset ja valmistukset").":</font><hr>";

  $til = " tila in ('L','U','N','R','E','V','D') ";
}
if ($cleantoim == 'OSTO') {
  echo "<font class='head'>".t("Toimittajan tilaukset").":</font><hr>";

  $til = " (tila = 'O' or (tila = 'K' and vanhatunnus=0)) ";
}
if ($cleantoim == 'TARJOUS') {
  echo "<font class='head'>".t("Asiakkaan tarjoukset").":</font><hr>";

  $til = " tila in ('T','D') and tilaustyyppi='T' ";
}
if ($cleantoim == 'YLLAPITO') {
  echo "<font class='head'>".t("Asiakkaan ylläpitosopimukset").":</font><hr>";

  $til = " tila in ('L','0','D') and tilaustyyppi='0' ";
}
if ($cleantoim == 'REKLAMAATIO') {
  echo "<font class='head'>".t("Asiakkaan reklamaatiot").":</font><hr>";

  $til = " tila in ('L','N','C','D') and tilaustyyppi='R' ";
}
if ($cleantoim == 'TAKUU') {
  echo "<font class='head'>".t("Asiakkaan takuut").":</font><hr>";

  $til = " tila in ('L','N','C','D') and tilaustyyppi='U' ";
}

if ($til == "" or $cleantoim == "") {
  echo "<p><font class='error'>".t("Järjestelmävirhe, tämän modulin suorittaminen suoralla urlilla on kielletty")." !!!</font></p>";
  require "inc/footer.inc";
  exit;
}

//  Voidaan näyttää vain tilaus ilman hakuja yms. Haluamme kuitenkin tarkastaa oikeudet.
if ($tee == "NAYTA" and $til != "") {
  require "raportit/naytatilaus.inc";
  require "inc/footer.inc";
  die();
}

// scripti balloonien tekemiseen
js_popup();
enable_ajax();
// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
js_openFormInNewWindow();

if ($tee == "POISTA_TILAUS") {
  require "peru_laskutus.inc";

  poista_tilaus_ja_laskutus($tunnus);

  $tee = "TULOSTA";
}

if ($tee != 'NAYTATILAUS' and empty($vaihda) and $ytunnus == '' and $tilausviite == '' and $astilnro == '' and $otunnus == '' and $laskunro == '' and $sopimus == '' and $kukarow['kesken'] != 0 and $til != '') {

  $query = "SELECT ytunnus, liitostunnus
            FROM lasku
            WHERE $logistiikka_yhtiolisa
            and tunnus = '$kukarow[kesken]'
            and $til";
  $keskenresult = pupe_query($query);

  if (mysql_num_rows($keskenresult) == 1) {
    $keskenrow = mysql_fetch_array($keskenresult);

    $ytunnus = $keskenrow['ytunnus'];

    if ($cleantoim == 'OSTO') {
      $toimittajaid = $keskenrow['liitostunnus'];
    }
    else {
      $asiakasid     = $keskenrow['liitostunnus'];
    }
  }
}

if ($tee == 'NAYTATILAUS') {
  $query = "SELECT tila
            FROM lasku
            WHERE tunnus = $tunnus
            AND $logistiikka_yhtiolisa";
  $_tila = mysql_fetch_assoc(pupe_query($query));

  if ($_tila["tila"] != "U") {
    echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Lasku").":</font><hr>";
  }

  require "naytatilaus.inc";

  if ($cleantoim == "MYYNTI" or $cleantoim == "TARJOUS" or $cleantoim == 'REKLAMAATIO' or $cleantoim == 'VALMISTUSMYYNTI' or $cleantoim == "TAKUU") {
    $query = "SELECT *
              FROM rahtikirjat
              WHERE otsikkonro='$tunnus'
              and $logistiikka_yhtiolisa ";
    $rahtiresult = pupe_query($query);

    if (mysql_num_rows($rahtiresult)> 0) {
      echo "<b>".t("Rahtikirjatiedot").":</b><hr>";
      echo "<table><tr><th>".t("Kilot")."</th>
              <th>".t("Kollit")."</th>
              <th>".t("Kuutiot")."</th>
              <th>".t("Lavametri")."</th>
              <th>".t("Rahdinmaksaja")."</th>
              <th>".t("Pakkaus")."</th>
              <th>".t("Pakkauskuvaus")."</th>
              <th>".t("Rahtisopimus")."</th>
              <th>".t("Tulostettu")."</th>
              <th>".t("Tulostuspaikka")."</th>
              <th>".t("Tulostustapa")."</th></tr>";

      while ($rahtirow = mysql_fetch_array($rahtiresult)) {
        $query = "SELECT nimitys
                  FROM varastopaikat
                  WHERE tunnus='$rahtirow[tulostuspaikka]'
                  and $logistiikka_yhtiolisa
                  limit 1";
        $varnimresult = pupe_query($query);
        $varnimrow = mysql_fetch_array($varnimresult);

        echo "<tr><td>$rahtirow[kilot]</td>
              <td>$rahtirow[kollit]</td>
              <td>$rahtirow[kuutiot]</td>
              <td>$rahtirow[lavametri]</td>";

        if ($rahtirow['merahti']== 'K') {
          echo "  <td>".t("Lähettäjä")."</td>";
        }
        else {
          echo "  <td>".t("Vastaanottaja")."</td>";
        }

        echo "    <td>$rahtirow[pakkaus]</td>
              <td>$rahtirow[pakkauskuvaus]</td>
              <td>$rahtirow[rahtisopimus]</td>
              <td>$rahtirow[tulostettu]</td>
              <td>$varnimrow[nimitys]</td>
              <td>$rahtirow[tulostustapa]</td></tr>";
      }
      echo "</table>";
    }
  }
  echo "<hr>";
  $tee = "TULOSTA";
}

if ($ytunnus != '' and ($otunnus == '' and $laskunro == '' and $sopimus == '')) {
  if ($cleantoim == 'MYYNTI' or $cleantoim == "TARJOUS" or $cleantoim == 'REKLAMAATIO' or $cleantoim == 'VALMISTUSMYYNTI' or $cleantoim == "TAKUU") {
    require "inc/asiakashaku.inc";
  }

  if ($cleantoim == 'OSTO') {
    require "inc/kevyt_toimittajahaku.inc";
  }
}
elseif ($otunnus > 0) {
  $query = "(SELECT laskunro, ytunnus, liitostunnus, jaksotettu
             FROM lasku use index (PRIMARY)
             WHERE tunnus='$otunnus'
             and $logistiikka_yhtiolisa)
             UNION
             (SELECT laskunro, ytunnus, liitostunnus, jaksotettu
             FROM lasku use index (yhtio_vanhatunnus)
             WHERE vanhatunnus='$otunnus'
             and $logistiikka_yhtiolisa)
             UNION
             (SELECT laskunro, ytunnus, liitostunnus, jaksotettu
             FROM lasku use index (yhtio_tunnusnippu)
             WHERE tunnusnippu = '$otunnus'
             and $logistiikka_yhtiolisa)";
  $result = pupe_query($query);
  $row = mysql_fetch_array($result);

  if ($row["laskunro"] > 0) {
    $laskunro = $row["laskunro"];
  }
  if ($row["jaksotettu"] <> 0) {
    $sopimus = abs($row["jaksotettu"]);
  }
  $ytunnus   = $row["ytunnus"];

  if ($cleantoim == 'OSTO') {
    $toimittajaid   = $row["liitostunnus"];
  }
  else {
    $asiakasid     = $row["liitostunnus"];
  }
}
elseif ($sopimus > 0) {
  $query = "SELECT laskunro, ytunnus, liitostunnus
            FROM lasku
            WHERE tunnus = '$sopimus'
            and $logistiikka_yhtiolisa";
  $result = pupe_query($query);
  $row = mysql_fetch_array($result);

  $laskunro = $row["laskunro"];
  $ytunnus  = $row["ytunnus"];

  if ($cleantoim == 'OSTO') {
    $toimittajaid   = $row["liitostunnus"];
  }
  else {
    $asiakasid     = $row["liitostunnus"];
  }
}
elseif ($laskunro > 0) {
  if ($cleantoim == 'OSTO') {
    $query = "SELECT laskunro, ytunnus, liitostunnus
              FROM lasku
              WHERE laskunro  = '$laskunro'
              and tila        = 'K'
              and vanhatunnus = 0
              and $logistiikka_yhtiolisa";
    $result = pupe_query($query);
    $row = mysql_fetch_array($result);

    $laskunro     = $row["laskunro"];
    $ytunnus      = $row["ytunnus"];
    $toimittajaid   = $row["liitostunnus"];
  }
  else {
    $query = "SELECT laskunro, ytunnus, liitostunnus, jaksotettu
              FROM lasku
              WHERE laskunro = '$laskunro'
              and tila       = 'U'
              and alatila    = 'X'
              and $logistiikka_yhtiolisa";
    $result = pupe_query($query);
    $row = mysql_fetch_array($result);

    if ($row["jaksotettu"] <> 0) {
      $sopimus = abs($row["jaksotettu"]);
    }

    $laskunro     = $row["laskunro"];
    $ytunnus      = $row["ytunnus"];
    $asiakasid     = $row["liitostunnus"];
  }
}
//astilnro kentässä on laskun tunnus, jotta livesearch hakukentän ID olisi yksilöllinen
elseif ($astilnro != '' or $tilausviite != '') {
  $_haku = "";

  if ($astilnro != '') {
    $_haku = $astilnro;
  } else {
    $_haku = $tilausviite;
  }

  $query = "SELECT laskunro, ytunnus, liitostunnus, tunnus, asiakkaan_tilausnumero, nimi, jaksotettu
            FROM lasku
            WHERE tunnus = '$_haku'
            and $logistiikka_yhtiolisa";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  if ($row["laskunro"] > 0) {
    $laskunro = $row["laskunro"];
  }
  else {
    $otunnus = $row["tunnus"];
  }

  if ($row["jaksotettu"] <> 0) {
    $sopimus = abs($row["jaksotettu"]);
  }

  $ytunnus   = $row["ytunnus"];

  if ($cleantoim == 'OSTO') {
    $toimittajaid = $row["liitostunnus"];
  }
  else {
    $asiakasid = $row["liitostunnus"];
  }
}

if ($ytunnus != '') {
  // Pikku scripti formin tyhjentämiseen
  echo "<script type='text/javascript' language='javascript'>
  function vaihdaClick() {
    document.etsiform.etsinappi.name='vaihda';
    document.etsiform.etsinappi.click();
  }
  </script>";

  echo "<form method='post' autocomplete='off' id='etsiform' name='etsiform'>
      <input type='hidden' name='ytunnus' value='$ytunnus'>
      <input type='hidden' name='asiakasid' value='$asiakasid'>
      <input type='hidden' name='toimittajaid' value='$toimittajaid'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='nimi' value='$nimi'>
      <input type='hidden' name='tee' value='TULOSTA'>";

  if ($asiakasid > 0) {
    $query  = "SELECT concat_ws(' ', nimi, nimitark) nimi
               FROM asiakas
               WHERE $logistiikka_yhtiolisa
               and tunnus='$asiakasid'";
    $result = pupe_query($query);
    $asiakasrow   = mysql_fetch_array($result);

    echo "<table><tr><th>".t("Valittu asiakas").":</th><td>$asiakasrow[nimi]</td></td><td class='back'><input type='button' onclick='vaihdaClick();' value = '".t("Vaihda asiakasta")."'></td></tr></table><br>";
  }
  elseif ($toimittajaid > 0) {
    $query  = "SELECT concat_ws(' ', nimi, nimitark) nimi
               FROM toimi
               WHERE $logistiikka_yhtiolisa
               and tunnus='$toimittajaid'";
    $result = pupe_query($query);
    $asiakasrow   = mysql_fetch_array($result);

    echo "<table><tr><th>".("Valittu toimittaja").":</th><td>$asiakasrow[nimi]</td><td class='back'><input type='button' onclick='vaihdaClick();' value = '".t("Vaihda toimittajaa")."'></td></td></tr></table><br>";
  }

  echo "<table>";

  $chk = "";

  if ($kaikki != "") {
    $chk = "CHECKED";
  }

  echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppa' value='$ppa' size='3'></td>
      <td><input type='text' name='kka' value='$kka' size='3'></td>
      <td><input type='text' name='vva' value='$vva' size='5'></td>
      </tr>";
  echo "<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
      <td><input type='text' name='ppl' value='$ppl' size='3'></td>
      <td><input type='text' name='kkl' value='$kkl' size='3'></td>
      <td><input type='text' name='vvl' value='$vvl' size='5'></td>";
  echo "</tr>";


  if ($cleantoim == 'OSTO') {
    $litunn = $toimittajaid;
  }
  else {
    $litunn = $asiakasid;
  }

  $summaselli = "";

  if (substr($toim, 0, 8) == "KONSERNI" and $yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
    $yhtioekolisa = "yhtio.nimi, ";
    $yhtioekojoin = "JOIN yhtio on lasku.yhtio=yhtio.yhtio ";

    if ($jarj != '') {
      $jarj = "ORDER BY $jarj";
    }
    else {
      $jarj = "ORDER BY 3 desc, 2 asc";
    }
  }
  else {
    $yhtioekolisa = "";
    $yhtioekojoin = "";

    if ($jarj != '') {
      $jarj = "ORDER BY $jarj";
    }
    else {
      $jarj = "ORDER BY 2 desc, 1 asc";
    }
  }

  if ($kukarow['hinnat'] == 0) {
    $summaselli = " lasku.summa, ";
  }

  $summaselli .= " lasku.viesti tilausviite, ";
  $summaselli .= " lasku.asiakkaan_tilausnumero astilno, ";

  if ($otunnus > 0 or $laskunro > 0 or $sopimus > 0) {

    if ($sopimus > 0) {
      $query = "(SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.tunnus='$sopimus')
                 UNION
                 (SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.vanhatunnus='$sopimus')
                 UNION
                 (SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.clearing='sopimus'
                 and lasku.swift='$sopimus')
                 UNION
                 (SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and (lasku.jaksotettu='{$sopimus}' or lasku.jaksotettu='-{$sopimus}'))";
    }
    elseif ($laskunro > 0) {
      $query = "SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                FROM lasku
                $yhtioekojoin
                WHERE lasku.$logistiikka_yhtiolisa
                and lasku.liitostunnus = '$litunn'
                and $til
                and lasku.laskunro='$laskunro'";
    }
    else {
      $query = "(SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.tunnus='$otunnus')
                 UNION
                 (SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.vanhatunnus='$otunnus')
                 UNION
                 (SELECT $yhtioekolisa lasku.tunnus tilaus, lasku.laskunro, concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, lasku.ytunnus, lasku.toimaika, lasku.laatija, $summaselli lasku.tila, lasku.alatila, lasku.hyvak1, lasku.hyvak2, lasku.h1time, lasku.h2time, lasku.luontiaika, lasku.yhtio
                 FROM lasku
                 $yhtioekojoin
                 WHERE lasku.$logistiikka_yhtiolisa
                 and lasku.liitostunnus = '$litunn'
                 and $til
                 and lasku.tunnusnippu='$otunnus')";
    }

    $query .=  "$jarj";
  }
  else {
    $query = "SELECT
                $yhtioekolisa
                lasku.tunnus tilaus,
                lasku.laskunro,
                concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas,
                lasku.ytunnus,
                lasku.toimaika,
                lasku.laatija,
                $summaselli
                lasku.tila,
                lasku.alatila,
                lasku.hyvak1,
                lasku.hyvak2,
                lasku.h1time,
                lasku.h2time,
                lasku.luontiaika,
                lasku.yhtio
              FROM lasku use index (yhtio_tila_luontiaika)
              $yhtioekojoin
              WHERE lasku.$logistiikka_yhtiolisa ";

    if (substr($ytunnus, 0, 1) == '£') {
      $query .= "  and lasku.nimi    = '$asiakasrow[nimi]'
            and lasku.nimitark  = '$asiakasrow[nimitark]'
            and lasku.osoite  = '$asiakasrow[osoite]'
            and lasku.postino  = '$asiakasrow[postino]'
            and lasku.postitp  = '$asiakasrow[postitp]' ";
    }
    else {
      $query .= "  and lasku.liitostunnus  = '$litunn'";
    }

    $query .= "  and $til
          and lasku.luontiaika >= '$vva-$kka-$ppa 00:00:00'
          and lasku.luontiaika <= '$vvl-$kkl-$ppl 23:59:59'
          $jarj";
  }

  if ($kaikki == "") {
    $query .= " LIMIT 51";
    $limittrikkeri = "A";
  }
  else {
    $query .= " LIMIT 500 ";
    $limittrikkeri = "X";
  }

  $result = pupe_query($query);

  echo "<tr>";

  if (mysql_num_rows($result) > 50) {
    echo "<th>".t("Näytä 500 uusinta tilausta")."</th>";
    echo "<td colspan='3' class=''>";
    echo "<input type='checkbox' name='kaikki' $chk></td>";
  }
  else {
    echo "<td colspan='4' class='back'>";
  }

  echo "<td class='back'>";
  echo "<input id='etsinappi' type='submit' value='".t("Hae")."'>";
  echo "</td></tr>";
  echo "</table>";
  echo "</form>";

  if (mysql_num_rows($result) > 50 and $limittrikkeri == "A") {
    echo "<p><font class='error'>".t("HUOM")."! ".t("Näytetään vain 50 uusinta tilausta")."</font></p>";
  }

  if (mysql_num_rows($result) > 0) {

    if ($kukarow["hinnat"] == 0) {
      if (substr($toim, 0, 8) == "KONSERNI" and $yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
        pupe_DataTables(array(array($pupe_DataTables, 12, 13)));
      }
      else {
        pupe_DataTables(array(array($pupe_DataTables, 11, 12)));
      }
    }
    else {
      if (substr($toim, 0, 8) == "KONSERNI" and $yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
        pupe_DataTables(array(array($pupe_DataTables, 11, 12)));
      }
      else {
        pupe_DataTables(array(array($pupe_DataTables, 10, 11)));
      }
    }

    echo "<br>";
    echo "<table class='display dataTable' id='$pupe_DataTables'>";
    echo "<thead>";
    echo "<tr>";

    for ($i=0; $i < mysql_num_fields($result)-8; $i++) {
      echo "<th>".t(mysql_field_name($result, $i))."</th>";
    }

    echo "<th>".t("Tyyppi")."</th>
        <th style='visibility:hidden;'></th>";
    echo "</tr>";

    echo "<tr>";

    for ($i=0; $i < mysql_num_fields($result)-8; $i++) {
      echo "<td><input type='text' class='search_field' name='search_".t(mysql_field_name($result, $i))."'></td>";
    }

    echo "<td><input type='text' class='search_field' name='search_tyyppi'></td>";
    echo "<td class='back'></td>";
    echo "<td class='back'></td>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    while ($row = mysql_fetch_array($result)) {

      echo "<tr>";

      // Laatikot laskujen ympärille
      if ($row["laskunro"] > 0 and $row["laskunro"] != $edlaskunro) {
        $query = "SELECT count(*)
                  FROM lasku
                  WHERE $logistiikka_yhtiolisa
                  and laskunro = '$row[laskunro]'
                  and tila     in ('U','L')";
        $pkres = pupe_query($query);
        $pkrow = mysql_fetch_array($pkres);

        $pknum     = $pkrow[0];
        $borderlask = $pkrow[0];
      }
      elseif ($row["laskunro"] == 0) {
        $borderlask    = 1;
        $pknum      = 1;
        $pkrow[0]     = 1;
      }

      $lask--;
      $classlisa = "";

      if ($borderlask == 1 and $pkrow[0] == 1 and $pknum == 1) {
        $classalku  = " style='border-left: 1px solid; border-top: 1px solid; border-bottom: 1px solid;' ";
        $classloppu = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
        $class     = " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

        $borderlask--;
      }
      elseif ($borderlask == $pkrow[0] and $pkrow[0] > 0) {
        $classalku  = " style='border-left: 1px solid; border-top: 1px solid;' ";
        $classloppu = " style='border-top: 1px solid; border-right: 1px solid;' ";
        $class     = " style='border-top: 1px solid;' ";

        $borderlask--;
      }
      elseif ($borderlask == 1) {
        $classalku  = " style='border-left: 1px solid; border-bottom: 1px solid;' ";
        $classloppu  = " style='border-right: 1px solid; border-bottom: 1px solid;' ";
        $class     = " style='border-bottom: 1px solid;' ";

        $borderlask--;
      }
      elseif ($borderlask > 0 and $borderlask < $pknum) {
        $classalku  = " style='border-left: 1px solid;' ";
        $classloppu  = " style='border-right: 1px solid;' ";
        $class     = " ";

        $borderlask--;
      }

      if ($tunnus == $row['tilaus']) {
        $classalku  .= " class='tumma' ";
        $classloppu  .= " class='tumma' ";
        $class     .= " class='tumma' ";
      }

      if ($row["tila"] == "U") {
        echo "<td valign='top' $classalku></td>";
      }
      else {
        if (trim($row["hyvak2"]) != "") {
          echo "<div id='div_kommentti$row[0]' class='popup' style='width: 500px;'>";
          echo t("Tilaus laadittu")." $row[laatija] @ ".tv1dateconv($row["luontiaika"], 'X')."<br>";
          echo t("Tilaus valmis")." $row[hyvak1] @ ".tv1dateconv($row["h1time"], 'X')."<br>";
          echo t("Tilaus hyväksytty")." $row[hyvak2] @ ".tv1dateconv($row["h2time"], 'X');
          echo "</div>";
          echo "<td valign='top' $classalku class='tooltip' id='kommentti$row[0]'>$row[0]</td>";
        }
        else {
          echo "<td valign='top' $classalku>$row[0]</td>";
        }
      }

      for ($i=1; $i<mysql_num_fields($result)-8; $i++) {
        if (mysql_field_name($result, $i) == 'toimaika') {
          echo "<td valign='top' $class>{$row[$i]}</td>";
        }
        elseif (mysql_field_name($result, $i) == 'laskunro' and $row['tila'] == "U" and tarkista_oikeus("muutosite.php")) {
          echo "<td valign='top' nowrap align='right' $class>";
          echo "<a href = '{$palvelin2}muutosite.php?tee=E&tunnus=$row[tilaus]&lopetus=$PHP_SELF////asiakasid=$asiakasid//ytunnus=$ytunnus//kka=$kka//vva=$vva//ppa=$ppa//kkl=$kkl//vvl=$vvl//ppl=$ppl//toim=$toim'>$row[$i]</a>";
          echo "</td>";
        }
        elseif (is_numeric(trim($row[$i])) and mysql_field_name($result, $i) != 'tilausviite' and mysql_field_name($result, $i) != 'astilno') {
          echo "<td valign='top' nowrap align='right' $class>$row[$i]</td>";
        }
        elseif (mysql_field_name($result, $i) == 'ytunnus') {
          echo "<td valign='top' {$class}>", tarkistahetu($row[$i]), "</td>";
        }
        else {
          echo "<td valign='top' $class>$row[$i]</td>";
        }
      }

      $laskutyyppi  = $row["tila"];
      $alatila    = $row["alatila"];

      //tehdään selväkielinen tila/alatila
      require "../inc/laskutyyppi.inc";

      if ($laskutyyppi == "Mitätöity") {
        $fn1 = "<font class='error'>";
        $fn2 = "</font>";
      }
      else {
        $fn1 = "";
        $fn2 = "";
      }

      if (($row["tila"] == "N" or $row["tila"] == "L" or $row['tila'] == 'C') and $cleantoim == "TAKUU") {
        $fn2 = " (".t("Takuu").") {$fn2}";
      }

      echo "<td valign='top' $classloppu>$fn1".t($laskutyyppi)." ".t($alatila)."$fn2</td>";

      echo "<td class='back' valign='top'>
          <form method='post'>
          <input type='hidden' name='tee'       value = 'NAYTATILAUS'>
          <input type='hidden' name='toim'       value = '$toim'>
          <input type='hidden' name='asiakasid'     value = '$asiakasid'>
          <input type='hidden' name='toimittajaid'   value = '$toimittajaid'>
          <input type='hidden' name='laskunro'     value = '$laskunro'>
          <input type='hidden' name='lasku_yhtio'     value = '$row[yhtio]'>
          <input type='hidden' name='tunnus'       value = '$row[tilaus]'>";

      //  Pysytään projektilla jos valitaan vain projekti
      if ($row["tila"] == "R" or $nippu > 0) {
        if ($nippu > 0) {
          echo "<input type='hidden' name='otunnus' value='$nippu'>";
          echo "<input type='hidden' name='nippu' value='$nippu'>";
        }
        else {
          echo "<input type='hidden' name='otunnus' value='$otunnus'>";
          echo "<input type='hidden' name='nippu' value='$otunnus'>";
        }
      }
      elseif ($sopimus > 0) {
        echo "<input type='hidden' name='sopimus' value='$sopimus'>";
      }
      else {
        echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
      }
      echo "  <input type='hidden' name='nimi' value='$nimi'>
          <input type='hidden' name='ppa' value='$ppa'>
          <input type='hidden' name='kka' value='$kka'>
          <input type='hidden' name='vva' value='$vva'>
          <input type='hidden' name='ppl' value='$ppl'>
          <input type='hidden' name='kkl' value='$kkl'>
          <input type='hidden' name='vvl' value='$vvl'>
          <input type='submit' value='".t("Näytä tilaus")."'>
          </form></td>";

      #$poista_tilaus_whiteliset = array("heidi", "tarja", "admin", "Rosa");

      // Poista nappi vain Heidille, sovittu Tarjan kanssa puhelimessa 174.7.2018/SA
      $poista_tilaus_whiteliset = array("heidi",  "admin");
      
      if ($row["tila"] != "U" and in_array($kukarow['kuka'], $poista_tilaus_whiteliset)) {
        echo "<td class='back'>
                <form>
                  <input type='hidden' name='tee' value='POISTA_TILAUS'>
                  <input type='hidden' name='toim' value='{$toim}'>
                  <input type='hidden' name='asiakasid' value='{$asiakasid}'>
                  <input type='hidden' name='ytunnus' value='{$ytunnus}'>
                  <input type='hidden' name='ppa' value='{$ppa}'>
                  <input type='hidden' name='kka' value='{$kka}'>
                  <input type='hidden' name='vva' value='{$vva}'>
                  <input type='hidden' name='ppl' value='{$ppl}'>
                  <input type='hidden' name='kkl' value='{$kkl}'>
                  <input type='hidden' name='vvl' value='{$vvl}'>
                  <input type='hidden' name='tunnus' value='{$row["tilaus"]}'>

                  <input type='submit'
                         value='" . t("Poista") . "'
                         style='background:#FF4200;'
                         onclick='return confirm(\"" . t("Oletko varma?") . "\");'>
                </form>
              </td>";
      }

      echo "<td class='back'>";

      if ($row['tila'] == "U" and tarkista_oikeus("tilauskasittely/tulostakopio.php", "LASKU")) {
        echo "<form id='tulostakopioform_{$row['tilaus']}' name='tulostakopioform_{$row['tilaus']}' action='../tilauskasittely/tulostakopio.php?toim=LASKU' method='post' autocomplete='off'>
            <input type='hidden' name='lopetus' value='{$lopetus}'>
            <input type='hidden' name='otunnus' value='{$row['tilaus']}'>
            <input type='hidden' name='toim' value='LASKU'>
            <input type='hidden' name='tee' value='NAYTATILAUS'>
            <input type='hidden' name='mista' value='tulostakopio'>
            <input type='submit' value='".t("Näytä pdf")."' onClick=\"js_openFormInNewWindow('tulostakopioform_{$row['tilaus']}', 'tulostakopio_{$row['tilaus']}'); return false;\"></form>";
      }

      echo "</td>";
      echo "</tr>";

      $edlaskunro = $row["laskunro"];
    }

    echo "</tbody>";
    echo "</table>";
  }
  else {
    echo t("Ei tilauksia")."...<br><br>";
  }
}

if ((int) $asiakasid == 0 and (int) $toimittajaid == 0) {
  // Näytetään muuten vaan sopivia tilauksia

  echo "<form name = 'asiaktilaus' action = 'asiakkaantilaukset.php' method = 'post'>
    <input type='hidden' name='lopetus' value='$lopetus'>
    <input type='hidden' name='toim' value='$toim'>";

  echo "<br><table>";

  if ($cleantoim == "OSTO") {
    echo "<tr><th>".t("Toimittajan nimi")."</th><td><input type='text' size='10' name='ytunnus'></td></tr>";
  }
  else {
    echo "<tr><th>".t("Asiakas")."</th><td><input type='text' size='10' name='ytunnus'> ", asiakashakuohje(), "</td></tr>";
  }
  if ($cleantoim == "YLLAPITO") {
    echo "<tr><th>".t("Sopimusnumero")."</th><td><input type='text' size='10' name='sopimus'></td></tr>";
  }
  else {
    echo "<tr><th>".t("Tilausnumero")."</th><td><input type='text' size='10' name='otunnus'></td></tr>";
  }
  echo "<tr><th>".t("Laskunumero")."</th><td><input type='text' size='10' name='laskunro'></td></tr>";

  if ($cleantoim == "MYYNTI") {
    echo "<tr><th>".t("Asiakkaan tilausnumero")."</th><td>";
    echo livesearch_kentta("asiaktilaus", "ASIAKKAANTILAUSNUMERO", "astilnro", 170, $astilnro);
    echo "</td>";

    echo "<tr><th>".t("Tilausviite")."</th><td>";
    echo livesearch_kentta("asiaktilaus", "TILAUSVIITE", "tilausviite", 170, $tilausviite);
    echo "</td>";
  }

  echo "</table>";

  echo "<br><input type='submit' class='hae_btn' value='".t("Etsi")."'>";
  echo "</form>";
}
else {
  echo "<br>";
  echo "<form action = 'asiakkaantilaukset.php' method = 'post'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='lopetus' value='$lopetus'>";
  echo "<br><input name='vaihda' type='submit' value='".t("Tee uusi haku")."'>";
  echo "</form>";
}

require "inc/footer.inc";
