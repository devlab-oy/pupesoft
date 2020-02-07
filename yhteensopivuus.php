<?php

if (file_exists("inc/parametrit.inc")) {
  require "inc/parametrit.inc";
  require "inc/tecdoc.inc";

  if ($verkkokauppa == "") {
    require "verkkokauppa/ostoskori.inc";
    $post_myynti = "tilauskasittely/tilaus_myynti.php";
    if ($toim_kutsu == "") $toim_kutsu = "RIVISYOTTO";
  }
}
else {
  require "parametrit.inc";
  require "tecdoc.inc";

  if ($verkkokauppa == "") {
    require "ostoskori.inc";
    $post_myynti = "tilaus_myynti.php";
    $toim_kutsu = "EXTRANET";
  }
}

if (!isset($ostoskori)) $ostoskori = '';
if (!isset($toiminto)) $toiminto = '';
if (!isset($autoid)) $autoid = '';
if (!isset($rekkari)) $rekkari = '';
if (!isset($oldrekkari)) $oldrekkari = '';
if (!isset($rekkariid)) $rekkariid = '';
if (!isset($tmprekkari)) $tmprekkari = '';
if (!isset($formi)) $formi = '';
if (!isset($kentta)) $kentta = '';
if (!isset($toim)) $toim = '';
if (!isset($maa)) $maa = '';
if (!isset($tultiin)) $tultiin = '';
if (!isset($vanhamerkki)) $vanhamerkki = '';
if (!isset($merkki)) $merkki = '';
if (!isset($malli)) $malli = '';
if (!isset($rekrow)) $rekrow = array();
if (!isset($submit_button))  $submit_button = '';
if (!isset($order)) $order = '';
if (!isset($osasto)) $osasto = '';
if (!isset($tuoteryhma)) $tuoteryhma = '';
if (!isset($table)) $table = '';

$type = $table == 'CV' ? 'cv' : 'pc';
$reksuodatus = $type == 'cv' ? false : true;

// Liitetiedostot popup
if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
  liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
  liite_popup("JS");
}

if ($verkkokauppa != "") {
  $kukarow["kesken"] = 0;
  $kukarow["kieli"] = $maa;

  if ($maa == "SE") {
    $valkoodi = "SEK";
  }
  else {
    $valkoodi = "";
  }

  $kukarow["extranet"] = "x";
  unset($ostoskori);
}

$tyyppi     = $table == 'CV' ? "RA" : "HA";
$ajoneuvolaji   = "'4','5'";

$naytetaan_lisaanappi = "";

if ($kukarow["yhtio"] != "" and $kukarow["kuka"] != "") {
  $query = "SELECT yhtio
            FROM oikeu
            WHERE yhtio = '$kukarow[yhtio]'
            and kuka    = '$kukarow[kuka]'
            and nimi    like '%tilaus_myynti.php'";
  $ksres = pupe_query($query, $link);

  if (is_numeric($ostoskori) or mysql_num_rows($ksres) > 0) $naytetaan_lisaanappi = "Y";
}

echo "<font class='head'>".t("Varaosaselain")."</font><hr>";

if ($kukarow["kesken"] != 0 and $kukarow["kesken"] != '') {
  $apulaskq = "SELECT tila from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
  $apulaskures = pupe_query($apulaskq, $link);
  $apulaskurow = mysql_fetch_assoc($apulaskures);
}

// if ($kukarow["yhtio"] == "artr") {
//   echo "<br><a href='mailto:palaute@arwidson.fi?subject=".t("Palautetta")."'>".t("Lähetä Palautetta")."</a><br><br>";
// }

if (function_exists("js_popup")) {
  echo js_popup(-100);
}

$divit = "";

// halutaan lisätä rivi tilaukselle
if ($toiminto == "LISAARIVI" and ($kukarow["kuka"] != '')) {

  if (is_numeric($ostoskori)) {
    $kori = check_ostoskori($ostoskori, $kukarow["oletus_asiakas"]);
    $kukarow["kesken"] = $kori["tunnus"];

    // haetaan avoimen tilauksen otsikko
    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $laskures = pupe_query($query, $link);
  }

  if ($kukarow["kesken"] != 0) {
    // haetaan avoimen tilauksen otsikko
    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $laskures = pupe_query($query, $link);
  }
  else {
    // Luodaan uusi myyntitilausotsikko
    if ($kukarow["extranet"] == "") {
      require_once "tilauskasittely/luo_myyntitilausotsikko.inc";

      if ($toim_kutsu != "") {
        $lmyytoim = $toim_kutsu;
      }
      else {
        $lmyytoim = "RIVISYOTTO";
      }

      $tilausnumero = luo_myyntitilausotsikko($lmyytoim, 0);
      $kukarow["kesken"] = $tilausnumero;
      $kaytiin_otsikolla = "NOJOO!";
    }
    else {
      require_once "luo_myyntitilausotsikko.inc";
      $tilausnumero = luo_myyntitilausotsikko("EXTRANET", $kukarow["oletus_asiakas"]);
      $kukarow["kesken"] = $tilausnumero;
      $kaytiin_otsikolla = "NOJOO!";
    }

    // haetaan avoimen tilauksen otsikko
    $query    = "SELECT * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $laskures = pupe_query($query, $link);
  }

  if (mysql_num_rows($laskures) == 0) {
    echo "<font class='error'>".t("Sinulla ei ole avointa tilausta")."!</font><br>";
  }
  else {

    // tilauksen tiedot
    $laskurow = mysql_fetch_assoc($laskures);

    if (is_numeric($ostoskori)) {
      echo "<font class='message'>".t("Lisätään tuotteita ostoskoriin")." $ostoskori.</font><br>";
    }
    else {
      echo "<font class='message'>".t("Lisätään tuotteita tilaukselle")." $kukarow[kesken].</font><br>";
    }

    // käydään läpi formin kaikki rivit
    foreach ($tilkpl as $yht_i => $kpl) {

      if ((float) $kpl > 0) {

        // haetaan tuotteen tiedot
        $query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
        $tuoteres = pupe_query($query, $link);

        if (mysql_num_rows($tuoteres) == 0) {
          echo "<font class='error'>".t("Tuotetta")." $tiltuoteno[$yht_i] ".t("ei löydy")."!</font><br>";
        }
        else {

          // tuote löytyi ok, lisätään rivi
          $trow = mysql_fetch_assoc($tuoteres);

          $ytunnus         = $laskurow["ytunnus"];
          $kpl             = (float) $kpl;
          $tuoteno         = $trow["tuoteno"];
          $toimaika        = $laskurow["toimaika"];
          $kerayspvm       = $laskurow["kerayspvm"];
          $hinta          = "";
          $netto          = "";
          $var         = "";
          $alv         = "";
          $paikka         = "";
          $varasto        = $laskurow["varasto"];
          $rivitunnus     = "";
          $korvaavakielto   = "";
          $jtkielto      = $laskurow['jtkielto'];
          $varataan_saldoa = "";

          for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            ${'ale'.$alepostfix} = '';
          }

          if (isset($rekkari) and trim($rekkari) != '') {
            $kommentti = t("Rekisterinumero").": ".$rekkari;
          }

          // jos meillä on ostoskori muuttujassa numero, niin halutaan lisätä tuotteita siihen ostoskoriin
          if (is_numeric($ostoskori)) {

            lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
            $kukarow["kesken"] = "";
          }
          elseif (file_exists("tilauskasittely/lisaarivi.inc")) {
            require "tilauskasittely/lisaarivi.inc";
          }
          else {
            require "lisaarivi.inc";
          }

          echo "<font class='message'>".t("Lisättiin")." $kpl ".t("kpl tuotetta")." $tiltuoteno[$yht_i].</font><br>";

        } // tuote ok else

      } // end kpl > 0

    } // end foreach

  } // end tuotelöytyi else

  echo "<br>";
}

if (is_numeric($ostoskori)) {

  echo "<table><tr><td class='back'>";
  echo "  <form method='post' action='ostoskori.php'>
      <input type='hidden' name='tee' value='poistakori'>
      <input type='hidden' name='futur_toim' value='$toim'>
      <input type='hidden' name='table' value='{$table}'>
      <input type='hidden' name='tultiin' value='$tultiin'>
      <input type='hidden' name='ostoskori' value='$ostoskori'>
      <input type='hidden' name='rekkari' value='$rekkari'>
      <input type='hidden' name='oldrekkari' value='$oldrekkari'>
      <input type='hidden' name='rekkariid' value='$rekkariid'>
      <input type='hidden' name='pyytaja' value='yhteensopivuus'>
      <input type='submit' value='".t("Tyhjennä ostoskori")."'>
      </form>";
  echo "</td><td class='back'>";
  echo "  <form method='post' action='ostoskori.php'>
      <input type='hidden' name='tee' value=''>
      <input type='hidden' name='futur_toim' value='$toim'>
      <input type='hidden' name='table' value='{$table}'>
      <input type='hidden' name='tultiin' value='$tultiin'>
      <input type='hidden' name='ostoskori' value='$ostoskori'>
      <input type='hidden' name='rekkari' value='$rekkari'>
      <input type='hidden' name='oldrekkari' value='$oldrekkari'>
      <input type='hidden' name='rekkariid' value='$rekkariid'>
      <input type='hidden' name='pyytaja' value='yhteensopivuus'>
      <input type='submit' value='".t("Näytä ostoskori")."'>
      </form>";
  echo "</td></tr></table><br><br>";
}
elseif ($kukarow["kesken"] != 0 and ($apulaskurow["tila"] == "L" or $apulaskurow["tila"] == "N" or $apulaskurow["tila"] == "T" or $apulaskurow["tila"] == "A" or $apulaskurow["tila"] == "S")) {
  echo "  <form method='post' action='$post_myynti'>
      <input type='hidden' name='toim' value='$toim_kutsu'>
      <input type='hidden' name='table' value='{$table}'>
      <input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
      <input type='submit' value='".t("Takaisin tilaukselle")."'>
      </form><br><br>";
}

// jos tulee autoid niin halutaan suoraan joku tietty auto
if ($autoid != '') {

  $params = array(
    'tyyppi' => $type,
    'autoid' => $autoid,
    'reksuodatus' => $reksuodatus
  );

  $result2 = td_getversion($params);

  if (mysql_num_rows($result2) > 0) {
    $rivi = mysql_fetch_assoc($result2);

    // täytetään dropdownit oikein
    $merkki        = $rivi["manuid"];
    $malli         = $rivi["modelno"];
    $tunnus        = $autoid;
  }
}

if ($rekkari != '') {
  $rekkari = strtoupper(trim($rekkari));

  if (strpos($rekkari, '-') === false) {
    $rekkari1 = $rekkari;

    // Ahvenanmaan rekisterinumerot alkaa aina Å:lla ja niissä ei ole väliviivaa, ei tehdä niille näitä käsittelyjä
    if (strlen($rekkari) == 6 and substr($rekkari, 0, 1) != 'Å') {
      $rekkari2   = substr($rekkari, 0, 3)."-".substr($rekkari, 3, 3);
    }
    elseif ((strlen($rekkari) == 5 or strlen($rekkari) == 4) and substr($rekkari, 0, 1) != 'Å') {
      $rekkari2   = substr($rekkari, 0, 2)."-".substr($rekkari, 2, 3);
    }

    if ($rekkari2 != '') {
      $rekkari = $rekkari2;
    }
  }
  else {
    $rekkari1   = str_replace("-", "", $rekkari);
    $rekkari2   = $rekkari;
  }
}

if ($rekkari != '') {

  if ($maa != "") {
    $maalisa = " and maa='$maa' ";
  }
  else {
    $maalisa = "";
  }

  if ((int) $rekkariid > 0) {
    $reklisa = " and tunnus ='$rekkariid' ";
  }
  else {
    $reklisa = "";
  }

  if ($rekkari2 != "") {
    $rekkari2lisa = ", '$rekkari2' ";
  }
  else {
    $rekkari2lisa = "";
  }

  $query = "SELECT *
            from rekisteritiedot
            where yhtio      = '$kukarow[yhtio]'
            and ajoneuvolaji in ($ajoneuvolaji)
            $maalisa
            $reklisa
            and rekno        in ('$rekkari1' $rekkari2lisa)";
  $rekresult = pupe_query($query);

  if (mysql_num_rows($rekresult) > 0) {
    while ($rekrow = mysql_fetch_assoc($rekresult)) {

      if ($oldrekkari != $rekkari and $rekrow["maa"] == "FI") {
        $query = "INSERT into ake_log SET
                  yhtio   = '$kukarow[yhtio]',
                    kuka  = '$kukarow[kuka]',
                    rekno = '$rekrow[rekno]',
                    aika  = now()";
        $result = pupe_query($query);

        $oldrekkari     = $rekkari;
        $merkki       = '';
        $oldmerkki       = '';
        $malli         = '';
        $oldmalli       = '';
        $mallitarkenne     = '';
        $oldmallitarkenne   = '';
      }

      $params = array(
        'tyyppi' => $type,
        'rekno' => $rekrow['rekno'],
      );

      $result2 = td_regsearch($params);
      $rekrow2 = mysql_fetch_assoc($result2);

      if ($rekrow['ajoneuvolaji'] == '4') {
        // Pakut
        $rekrow['ajoneuvolaji'] = 'Pakettiauto';
      }
      elseif ($rekrow['ajoneuvolaji'] == '5') {
        // Autot
        $rekrow['ajoneuvolaji'] = 'Henkilöauto';
      }
      elseif ($rekrow['ajoneuvolaji'] == '8') {
        // Kelkat
        $rekrow['ajoneuvolaji'] = 'Moottorikelkka';
      }
      elseif ($rekrow['ajoneuvolaji'] == '9') {
        // Prätkät
        $rekrow['ajoneuvolaji'] = 'Moottoripyörä';
      }
      elseif (strtoupper($rekrow['ajoneuvolaji']) == 'A') {
        // Kolmipyöräset ja jotkut mönkkärit
        $rekrow['ajoneuvolaji'] = 'Mönkijä';
      }
      elseif (strtoupper($rekrow['ajoneuvolaji']) == 'B') {
        // Mopot
        $rekrow['ajoneuvolaji'] = 'Mopo';
      }
      elseif (strtoupper($rekrow['ajoneuvolaji']) == 'C') {
        // Jotku mönkkärit
        $rekrow['ajoneuvolaji'] = 'Mönkijä';
      }
      elseif (strtoupper($rekrow['ajoneuvolaji']) == 'D') {
        // Jotku mönkkärit
        $rekrow['ajoneuvolaji'] = 'Mönkijä';
      }
      elseif (strtoupper($rekrow['ajoneuvolaji']) == 'E') {
        // Jotku mönkkärit
        $rekrow['ajoneuvolaji'] = 'Mönkijä';
      }

      if ($rekrow['rinnakkaistuonti'] == '1') {
        $rekrow['rinnakkaistuonti'] = 'Käytetty';
      }
      elseif ($rekrow['rinnakkaistuonti'] == '2') {
        $rekrow['rinnakkaistuonti'] = 'Uusi';
      }

      if ($rekrow['k_voima'] == '1') {
        $rekrow['k_voima'] = 'Bensiini';
      }
      elseif ($rekrow['k_voima'] == '3') {
        $rekrow['k_voima'] = 'Diesel';
      }
      elseif ($rekrow['k_voima'] == '38') {
        $rekrow['k_voima'] = 'Bensiini/Kaasu';
      }
      if ($rekrow['vahapaastoisyys'] == '1') {
        $rekrow['vahapaastoisyys'] = 'Vähäpäästöinen';
      }

      if ($verkkokauppa != "") {
        echo "<table>";
        echo "  <tr>
              <th>".t("Rekisterinumero")."</th>
              <td nowrap>".strtoupper($rekkari)."</td></tr></table>";
      }
      else {
        $tmprekkari = array();

        $tmprekkari[] = "<table>";
        $tmprekkari[] = "  <tr>
                  <th>".t("Rekisterinumero")."</th>
                  <td nowrap>".strtoupper($rekkari)."</td>
                  <th>".t("Ajoneuvolaji")."</th>
                  <td nowrap>".t("$rekrow[ajoneuvolaji]")."</td>
                  <th>".t("Kokonaismassa")."</th>
                  <td nowrap>".(int)$rekrow['kok_massa']." KG</td>
                  </tr>";

        $tmprekkari[] = "  <tr>
                  <th>".t("Merkki")."</th>
                  <td nowrap>$rekrow[merkki]</td>
                  <th>".t("Valmistenumero")."</th>
                  <td nowrap>$rekrow[valmistenumero]</td>
                  <th>".t("Omamassa")."</th>
                  <td nowrap>".(int)$rekrow['oma_massa']." KG</td>
                  </tr>";

        $tmprekkari[] = "  <tr>
                  <th>".t("Malli")."</th>
                  <td nowrap>$rekrow[malli]</td>
                  <th>".t("Tyyppikoodi")."</th>
                  <td nowrap>$rekrow[tyyppikoodi]</td>
                  <th>".t("Pituus")."</th>
                  <td nowrap>".(int) $rekrow["pituus"]." CM</td>
                  </tr>";

        $tmprekkari[] = "  <tr>
                  <th>".t("Käyttövoima")."</th>
                  <td nowrap>".t("$rekrow[k_voima]")."</td>
                  <th>".t("Käyttöönottopvm")."</th>
                  <td nowrap>".(int) substr($rekrow["kayttoonotto"], 6, 2).".".(int) substr($rekrow["kayttoonotto"], 4, 2).".". substr($rekrow["kayttoonotto"], 0, 4)."</td>
                  <th>".t("Vähäpäästöisyys")."</th>
                  <td nowrap>".t("$rekrow[vahapaastoisyys]")."</td>
                  </tr>";

        $tmprekkari[] = "  <tr>
                  <th>".t("Moottorintilavuus")."</th>
                  <td nowrap>$rekrow[moottorin_til]</td>
                  <th>".t("Rinnakkaistuonti")."</th>
                  <td nowrap>".t("$rekrow[rinnakkaistuonti]")."</td>
                  <th>".t("Renkaat")."</th>";

        if (strlen($rekrow['renkaat']) > 20) {

          $tmprekkari[] = "<div id='div_renkaat' class='popup'>$rekrow[renkaat]</div>";

          $tmprekkari[] = "<td><a class='tooltip' id='renkaat'>".t("Useita")."...</a></td>";
        }
        else {
          $tmprekkari[] = "<td nowrap>".substr($rekrow["renkaat"], 0, 20)."</td>";
        }

        $tmprekkari[] = "  <tr>
                  <th>".t("Teho")."</th>
                  <td nowrap>".(int) $rekrow['teho']." KW (".round((int)$rekrow['teho']*1.36, 0)."HV)</td>
                  <th>".t("Vetävät akselit")."</th>
                  <td nowrap>".(int) $rekrow["vetavat_akselit"]."</td>
                  <td colspan='2'>".t("Lähde: Ajoneuvoliikennerekisteri").".</td>
                  </tr></table>";

        if (mysql_num_rows($rekresult) > 1) {
          $tmprekkari[] = "<form name='' method='post'>";
          $tmprekkari[] = "<input type='hidden' name='toim' value='$toim'>";
          $tmprekkari[] = "<input type='hidden' name='table' value='{$table}'>";
          $tmprekkari[] = "<input type='hidden' name='maa' value='$maa'>";
          $tmprekkari[] = "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
          $tmprekkari[] = "<input type='hidden' name='ostoskori' value='$ostoskori'>";
          $tmprekkari[] = "<input type='hidden' name='rekkari' value='$rekkari'>";
          $tmprekkari[] = "<input type='hidden' name='rekkariid' value='$rekrow[tunnus]'>";
          $tmprekkari[] = "<input type='submit' value='".t("Valitse")."'></td></tr>";
          $tmprekkari[] = "</form><br>";
        }
      }
    }

    echo "<br>";

    if (mysql_num_rows($rekresult) == 1) {
      mysql_data_seek($rekresult, 0);
      $rekrow = mysql_fetch_assoc($rekresult);
    }
  }
  else {
    echo "<font class='error'>".t("Rekisterinumeroa")." $rekkari ".t("ei löytynyt rekisteristä").".</font><br><br>";
  }
  $tee = '';
}

if (is_array($tmprekkari)) {
  for ($i = 0; $i < count($tmprekkari); $i++) {
    if (strpos($tmprekkari[$i], "<div") === true) {
      $divit .= $tmprekkari[$i];
    }
    else {
      echo $tmprekkari[$i];
    }
  }
  echo "<br>";
}

if (!is_array($tmprekkari) and strlen($tmprekkari) > 0) {
  $tmprekkari = unserialize(urldecode($tmprekkari));
  for ($i = 0; $i < count($tmprekkari); $i++) {
    if (isset($tmprekkari[$i]) and strpos($tmprekkari[$i], "<div") === true) {
      $divit .= $tmprekkari[$i];
    }
    else {
      if (isset($tmprekkari[$i])) echo $tmprekkari[$i];
    }
  }
  echo "<br>";
}

if ($formi == '') {
  $formi  = "rekisterinro";
}
if ($kentta == '') {
  $kentta = "rekkari";
}

echo "<table>";

echo "<form  name='rekisterinro' method='post'>";
echo "<input type='hidden' name='toim' value='{$toim}'>";
echo "<input type='hidden' name='table' value='{$table}'>";
echo "<input type='hidden' name='maa' value='{$maa}'>";
echo "<input type='hidden' name='toim_kutsu' value='{$toim_kutsu}'>";
echo "<input type='hidden' name='ostoskori' value='{$ostoskori}'>";
echo "<input type='hidden' name='tultiin' value='{$tultiin}'>";
echo "<tr><th>", t("Syötä rekisterinumero"), "</th><td><input type='text' name='rekkari' value='{$rekkari}'><input type='submit' value='", t("Hae tiedot"), "'></td></tr>";
echo "</form>";
echo "<tr><th>", t("tai valitse"), "</th><td>";

echo "<form  name='selain' method='post'>";
echo "<input type='hidden' name='toim' value='{$toim}'>";
echo "<input type='hidden' name='table' value='{$table}'>";
echo "<input type='hidden' name='maa' value='{$maa}'>";
echo "<input type='hidden' name='toim_kutsu' value='{$toim_kutsu}'>";
echo "<input type='hidden' name='ostoskori' value='{$ostoskori}'>";
echo "<input type='hidden' name='tultiin' value='{$tultiin}'>";
echo "<input type='hidden' name='tmprekkari' value='".urlencode(serialize($tmprekkari))."'>";

if (trim($vanhamerkki) != '' and $vanhamerkki != $merkki) {
  $malli = '';
}

$params = array(
  'tyyppi' => $type,
  'reksuodatus' => $reksuodatus
);

$res = td_getbrands($params);

echo "<select name='merkki' onchange='submit()'>";
echo "<option value=''>", t("Valitse merkki"), "</option>";

while ($rivi = mysql_fetch_assoc($res)) {

  $selected = '';

  if ($merkki == '' and isset($rekrow2['manuid']) and $rekrow2['manuid'] != '' and strtoupper($rivi['manuid']) == strtoupper($rekrow2['manuid'])) {
    $merkki = $rekrow2['manuid'];
  }

  if (strtoupper($merkki) == strtoupper($rivi["manuid"])) $selected = 'SELECTED';

  echo "<option value='{$rivi['manuid']}' {$selected}>{$rivi['name']}</option>";
}

echo "</select>";

echo "<select name='malli' onchange='submit()'>";
echo "<option value=''>", t("Valitse malli"), "</option>";

if ($merkki != '') {

  $params = array(
    'tyyppi' => $type,
    'merkkino' => $merkki,
    'reksuodatus' => $reksuodatus
  );

  $result = td_getmodels($params);

  while ($malli_row = mysql_fetch_assoc($result)) {

    $sel = $malli == $malli_row['modelno'] ? ' SELECTED' : '';

    if ($malli == '' and $rekrow2['modelno'] != '' and strtoupper($malli_row['modelno']) == strtoupper($rekrow2['modelno'])) {
      $malli = $rekrow2['modelno'];
      $sel = ' SELECTED';
    }

    echo "<option value='{$malli_row['modelno']}'{$sel}>{$malli_row['modelname']} ";
    echo td_niceyear($malli_row['vma'], $malli_row['vml']);
    echo "</option>";
  }
}

echo "</select>";

echo "<input type='hidden' name='dropista' value='joo'>";
echo "<input type='hidden' name='vanhamerkki' value='{$merkki}' />";

echo "</td></tr></table>";

echo "</form>\n";

if ($malli != '' or (isset($rekrow['rekno']) and $rekrow['rekno'] != '' and isset($rekrow2['manuid']) and $rekrow2['manuid'] != '')) {

  $lisa = array();

  if (trim($rekkari) != '') {
    $query3 = "SELECT autoid
               FROM yhteensopivuus_rekisteri
               WHERE yhtio      = '{$kukarow['yhtio']}'
               and rekno        = '{$rekkari}'
               and ajoneuvolaji in ($ajoneuvolaji)
               and autoid       > 0";
    $result3 = pupe_query($query3, $link);

    while ($rekrivi3 = mysql_fetch_assoc($result3)) {
      $lisa[] = $rekrivi3['autoid'];
    }
  }

  $params = array(
    'tyyppi' => $type,
    'mallino' => $malli,
    'merkkino' => $merkki,
    'autoid' => $lisa,
    'reksuodatus' => $reksuodatus
  );

  $res = td_getversion($params);

  $lisa = '';

  echo "<br><font class='head'>", t("Mallit"), ":</font><hr>";

  echo "<table>";
  echo "<tr>";

  if ($kukarow['extranet'] == '') {
    echo "<th>autoid</th>";
  }

  echo "<th>", t("Merkki ja malli"), "</th>";
  echo "<th>", t("tilavuus"), "</th>";
  echo "<th>", t("cc"), "</th>";
  echo "<th>", t("vm alku"), "</th>";
  echo "<th>", t("vm loppu"), "</th>";
  echo "<th>", t("kw"), "</th>";
  echo "<th>", t("hp"), "</th>";

  if ($type == 'pc') {
    echo "<th>", t("sylinterit"), "</th>";
    echo "<th>", t("venttiilit"), "</th>";
    echo "<th>", t("voimansiirto"), "</th>";
    echo "<th>", t("polttoaine"), "</th>";
    echo "<th>", t("moottorit"), "</th>";
  }
  else {
    echo "<th>", t("Korityyppi"), "</th>";
    echo "<th>", t("Moottorityyppi"), "</th>";
    echo "<th>", t("Tonnit"), "</th>";
    echo "<th>", t("Akselit"), "</th>";
  }

  echo "<th>", t("Moottorikoodit"), "</th>";
  echo "<th>", t("Rek. määrä"), "</th>";
  echo "<td class='back'></td>";

  echo "</tr>";

  if (mysql_num_rows($res) == 1) {
    $vaanyks = 'joo';
  }
  else {
    $vaanyks = '';
  }

  if (!isset($tunnus)) $tunnus = '';

  while ($rivi = mysql_fetch_assoc($res)) {
    if ($vaanyks == 'joo') {
      $tunnus = $rivi["autoid"];
    }

    if ($tunnus == "" or $tunnus == $rivi["autoid"]) {

      echo "<tr class='aktiivi'>";

      if ($kukarow['extranet'] == '') {
        echo "<td valing='top'>{$rivi['autoid']}</td>";
      }

      echo "<td valing='top'>{$rivi['manu']} {$rivi['model']} {$rivi['version']}</td>";
      echo "<td valing='top'>{$rivi['capltr']}</td>";
      echo "<td valing='top'>{$rivi['cc']}</td>";
      echo "<td valing='top'>", td_cleanyear($rivi['vma']), "</td>";
      echo "<td valing='top'>", td_cleanyear($rivi['vml']), "</td>";

      if ($type == 'pc') {
        echo "<td valing='top'>{$rivi['kw']}</td>";
        echo "<td valing='top'>{$rivi['hp']}</td>";
      }
      else {
        if ($rivi['kwl'] == 0) {
          echo "<td valing='top'>{$rivi['kwa']}</td>";
        }
        else {
          echo "<td valing='top'>{$rivi['kwa']} - {$rivi['kwl']}</td>";
        }

        if ($rivi['hpl'] == 0) {
          echo "<td valing='top'>{$rivi['hpa']}</td>";
        }
        else {
          echo "<td valing='top'>{$rivi['hpa']} - {$rivi['hpl']}</td>";
        }
      }

      if ($type == 'pc') {
        echo "<td valing='top'>{$rivi['cyl']}</td>";
        echo "<td valing='top'>{$rivi['valves']}</td>";
        echo "<td valing='top'>{$rivi['drivetype']}</td>";
        echo "<td valing='top'>{$rivi['fueltype']}</td>";
        echo "<td valing='top'>{$rivi['enginetype']}</td>";
      }
      else {
        echo "<td valing='top'>{$rivi['bodytype']}</td>";
        echo "<td valing='top'>{$rivi['enginetype']}</td>";
        echo "<td valing='top'>{$rivi['tons']}</td>";
        echo "<td valing='top'>{$rivi['axles']}</td>";
      }

      echo "<td valing='top'>{$rivi['mcodes']}</td>";
      echo "<td valing='top'>{$rivi['rekmaara']}</td>";

      if ($tunnus == "") {
        echo "<form  name='selain' method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name='table' value='{$table}'>";
        echo "<input type='hidden' name='maa' value='{$maa}'>";
        echo "<input type='hidden' name='toim_kutsu' value='{$toim_kutsu}'>";
        echo "<input type='hidden' name='ostoskori' value='{$ostoskori}'>";
        echo "<input type='hidden' name='rekkari' value='{$rekkari}'>";
        echo "<input type='hidden' name='oldrekkari' value='{$oldrekkari}'>";
        echo "<input type='hidden' name='rekkariid' value='{$rekkariid}'>";
        echo "<input type='hidden' name='merkki' value='{$merkki}'>";
        echo "<input type='hidden' name='malli' value='{$malli}'>";
        echo "<input type='hidden' name='tunnus' value='{$rivi['autoid']}'>";
        echo "<input type='hidden' name='tultiin' value='{$tultiin}'>";
        echo "<input type='hidden' name='tmprekkari' value='".urlencode(serialize($tmprekkari))."'>";
        echo "<td class='back' valign='top'>";
        echo "<input type='submit' value='".t("Valitse")."'>";
        echo "</td></tr></form>";
      }
      else {
        echo "<form  name='selain' method='post'>";
        echo "<input type='hidden' name='toim' value='{$toim}'>";
        echo "<input type='hidden' name='table' value='{$table}'>";
        echo "<input type='hidden' name='maa' value='{$maa}'>";
        echo "<input type='hidden' name='toim_kutsu' value='{$toim_kutsu}'>";
        echo "<input type='hidden' name='ostoskori' value='{$ostoskori}'>";
        echo "<input type='hidden' name='rekkari' value='{$rekkari}'>";
        echo "<input type='hidden' name='oldrekkari' value='{$oldrekkari}'>";
        echo "<input type='hidden' name='rekkariid' value='{$rekkariid}'>";
        echo "<input type='hidden' name='merkki' value='$merkki'>";
        echo "<input type='hidden' name='malli' value='{$malli}'>";
        echo "<input type='hidden' name='autoid' value=''>";
        echo "<input type='hidden' name='tultiin' value='{$tultiin}'>";
        echo "<input type='hidden' name='tmprekkari' value='".urlencode(serialize($tmprekkari))."'>";
        echo "<td class='back' valign='top'>* ".t("Valittu")." * <input type='submit' value='".t("Palaa valintaan")."'></td></form>";

        if (isset($autodata_config)) {

          $query = "SELECT distinct autodataid from yhteensopivuus_autodata where autoid=$tunnus and yhtio='$kukarow[yhtio]'";
          $resa = pupe_query($query, $link);

          $rivi = mysql_fetch_assoc($resa);

          echo "<td class='back' valign='top'>";
          echo "<form action='autodata_sg.php' method='post'>";
          echo "<input type='hidden' name='atunnus' value='$tunnus'>";
          echo "<input type='hidden' name='table' value='{$table}'>";
          echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
          echo "<input type='hidden' name='mid' value='$rivi[autodataid]'>";
          echo "<input type='submit' value='".t("Määräaikaishuollot")."'>";
          echo "</form>";
          echo "</td>";

          echo "<td class='back' valign='top'>";
          echo "<form action='autodata_rt.php' method='post'>";
          echo "<input type='hidden' name='atunnus' value='$tunnus'>";
          echo "<input type='hidden' name='table' value='{$table}'>";
          echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
          echo "<input type='hidden' name='mid' value='$rivi[autodataid]'>";
          echo "<input type='submit' value='".t("Huoltoajat")."'>";
          echo "</form>";
          echo "</td>";

        }

        echo "</tr>";
      }
    }
  }

  echo "</table>\n";

  if ($tunnus != "") {

    // vientikieltokäsittely:
    // +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
    // -maa tarkoittaa että ainoastaan tähän maahan saa myydä
    // eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
    $kieltolisa = "";
    unset($vierow);

    if ($kukarow["kesken"] > 0) {
      $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                 FROM lasku
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$kukarow[kesken]'";
      $vieres = pupe_query($query, $link);
      $vierow = mysql_fetch_assoc($vieres);
    }
    elseif ($verkkokauppa != "") {
      $vierow = array();

      if ($maa != "") {
        $vierow["maa"] = $maa;
      }
      else {
        $vierow["maa"] = $yhtiorow["maa"];
      }
    }
    elseif ($kukarow["extranet"] != "") {
      $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                 FROM asiakas
                 WHERE yhtio = '$kukarow[yhtio]'
                 and tunnus  = '$kukarow[oletus_asiakas]'";
      $vieres = pupe_query($query, $link);
      $vierow = mysql_fetch_assoc($vieres);
    }

    if (isset($vierow) and $vierow["maa"] != "") {
      $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
    }

    if ($kukarow['extranet'] == '') {
      $queryoik = "SELECT tunnus from oikeu where nimi = 'yhteensopivuus_yllapito_malli.php' and alanimi='' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
      $oikeu_res = pupe_query($queryoik, $link);

      echo "<br/>";
      if (mysql_num_rows($res) > 0) {
        echo "<form action='yhteensopivuus_yllapito_malli.php' method='post'>";
        echo "<input type='hidden' name='tunnus' value='$tunnus'>";
        echo "<input type='hidden' name='table' value='{$table}'>";
        echo "<input type='hidden' name='merkki' value='$merkki'>";
        echo "<input type='hidden' name='malli' value='$malli'>";
        echo "<input type='hidden' name='rekkari' value='$rekkari'>";
        echo "<input type='hidden' name='valinta[]' value='$tunnus'>";
        echo "<input type='hidden' name='lopetus' value='".$palvelin2."yhteensopivuus.php////rekkari=$rekkari//tunnus=$tunnus//toim=$toim//maa=$maa//toim_kutsu=$toim_kutsu//ostoskori=$ostoskori//oldrekkari=$oldrekkari//rekkariid=$rekkariid//merkki=$merkki//malli=$malli//tultiin=$tultiin'>";
        echo "<input type='submit' value='", t("Ylläpito malleittain"), "'>";
        echo "</form>";
      }

      $queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='yhteensopivuus_tuote' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
      $oikeu_res = pupe_query($queryoik, $link);

      if (mysql_num_rows($res) > 0) {
        echo "<form action='yllapito.php?toim=yhteensopivuus_tuote' method='post'>";
        echo "<input type='hidden' name='haku[2]' value='$tunnus'>";
        echo "<input type='hidden' name='table' value='{$table}'>";
        echo "<input type='hidden' name='lopetus' value='".$palvelin2."yhteensopivuus.php////rekkari=$rekkari//tunnus=$tunnus//toim=$toim//maa=$maa//toim_kutsu=$toim_kutsu//ostoskori=$ostoskori//oldrekkari=$oldrekkari//rekkariid=$rekkariid//merkki=$merkki//malli=$malli//tultiin=$tultiin'>";
        echo "&nbsp;<input type='submit' value='", t("Selaimen tuotteet"), "'>";
        echo "</form>";
      }
      echo "<br/>";
    }

    $query = "SELECT yhteensopivuus_tuote.tuoteno
              FROM yhteensopivuus_tuote
              JOIN tuote ON (yhteensopivuus_tuote.yhtio = tuote.yhtio and yhteensopivuus_tuote.tuoteno = tuote.tuoteno $kieltolisa)
              where yhteensopivuus_tuote.yhtio = '{$kukarow['yhtio']}'
              and yhteensopivuus_tuote.atunnus = '{$tunnus}'
              and yhteensopivuus_tuote.tyyppi  = '{$tyyppi}'
              and yhteensopivuus_tuote.status  IN ('', 'L')";
    $res = pupe_query($query, $link);

    $monivalinta_tuotteet = "";

    while ($rivi = mysql_fetch_assoc($res)) {
      $monivalinta_tuotteet .= "'$rivi[tuoteno]',";
    }

    $monivalinta_tuotteet = substr($monivalinta_tuotteet, 0, -1);

    if (mysql_num_rows($res) != 0) {

      mysql_data_seek($res, 0);

      echo "<br/>";

      echo "<div id='tuotehaku'>";

      echo "<br><form name='selain' method='post'>";
      echo "<input type='hidden' name='toim' value='$toim'>";
      echo "<input type='hidden' name='table' value='{$table}'>";
      echo "<input type='hidden' name='maa' value='$maa'>";
      echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
      echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
      echo "<input type='hidden' name='rekkari' value='$rekkari'>";
      echo "<input type='hidden' name='oldrekkari' value='$oldrekkari'>";
      echo "<input type='hidden' name='rekkariid' value='$rekkariid'>";
      echo "<input type='hidden' name='merkki' value='$merkki'>";
      echo "<input type='hidden' name='malli' value='$malli'>";
      echo "<input type='hidden' name='tunnus' value='$tunnus'>";
      echo "<input type='hidden' name='tultiin' value='$tultiin'>";
      echo "<input type='hidden' name='tmprekkari' value='".urlencode(serialize($tmprekkari))."'>";

      echo "<font class='head'>".t("Tuotteet").":</font><hr>";

      $ulisa = "";

      if (isset($sort) and $sort != '') {
        $sort = trim(mysql_real_escape_string($sort));
      }

      if (!isset($sort)) {
        $sort = 'asc';
      }

      if ($sort == 'asc') {
        $sort = 'desc';
        $edsort = 'asc';
      }
      else {
        $sort = 'asc';
        $edsort = 'desc';
      }

      if ($kukarow["extranet"] != "") {
        $extra_poislisa = " and tuote.hinnastoon != 'E' ";
        $avainlisa = "";
      }
      else {
        $extra_poislisa = "";
        $avainlisa = "";
      }

      // vientikieltokäsittely:
      // +maa tarkoittaa että myynti on kielletty tähän maahan ja sallittu kaikkiin muihin
      // -maa tarkoittaa että ainoastaan tähän maahan saa myydä
      // eli näytetään vaan tuotteet jossa vienti kentässä on tyhjää tai -maa.. ja se ei saa olla +maa
      $kieltolisa = "";
      unset($vierow);

      if ($kukarow["kesken"] > 0) {
        $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                   FROM lasku
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$kukarow[kesken]'";
        $vieres = pupe_query($query, $link);
        $vierow = mysql_fetch_assoc($vieres);
      }
      elseif ($verkkokauppa != "") {
        $vierow = array();

        if ($maa != "") {
          $vierow["maa"] = $maa;
        }
        else {
          $vierow["maa"] = $yhtiorow["maa"];
        }
      }
      elseif ($kukarow["extranet"] != "") {
        $query  = "SELECT if (toim_maa != '', toim_maa, maa) maa
                   FROM asiakas
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tunnus  = '$kukarow[oletus_asiakas]'";
        $vieres = pupe_query($query, $link);
        $vierow = mysql_fetch_assoc($vieres);
      }

      if (isset($vierow) and $vierow["maa"] != "") {
        $kieltolisa = " and (tuote.vienti = '' or tuote.vienti like '%-$vierow[maa]%' or tuote.vienti like '%+%') and tuote.vienti not like '%+$vierow[maa]%' ";
      }

      if ($tunnus != '') $autoid = $tunnus;

      $monivalintalaatikot = array("DYNAAMINEN_TUOTE", "TUOTEMERKKI", "<br>PARAMETRI_TUUMAKOKO", "PARAMETRI_LEVEYS", "PARAMETRI_PULTTIJAKO", "PARAMETRI_OFFSET", "PARAMETRI_KESKIREIKA");
      $monivalintalaatikot_normaali = array();

      if (file_exists("tilauskasittely/monivalintalaatikot.inc")) {
        require "tilauskasittely/monivalintalaatikot.inc";
      }
      else {
        require "monivalintalaatikot.inc";
      }

      echo "<br/><input type='Submit' name='submit_button' id='submit_button' value = '".t("Etsi")."'></form>";
      echo "</div><br/>";

      if (trim($lisa_dynaaminen["tuote"]) != '') {

        $ulisa .= "&submit_button=yes";

        echo "<div id='tuotelistaus'>";
        echo "<table>";
        echo "<tr>";
        echo "<td class='back'></td>";
        echo "<th>", t("kuva"), "</th>";

        echo "<th><a href='?indexvas=1&order=1&toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=1&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("tuoteno")."</a>";
        if ($order == 1) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th><a href='?order=2toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=2&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("merkki")."</a>";
        if ($order == 2) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th><a href='?order=3&toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=3&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("tuoteryhmä")."</a>";
        if (!isset($order)) {
          echo " <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'>";
        }
        elseif ($order == 3) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th><a href='?order=4&toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=4&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("nimitys")."</a>";
        if ($order == 4) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th><a href='?order=5&toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=5&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("lisätiedot")."</a>";
        if ($order == 5) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th><a href='?order=6&toim=$toim&tunnus=$tunnus&merkki=$merkki&malli=$malli&maa=$maa&autoid=$tunnus&rekkari=$rekkari&rekkariid=$rekkariid&oldrekkari=$oldrekkari&order=6&ostoskori=$ostoskori&tultiin=$tultiin&sort=$sort".$ulisa."'>".t("myyntihinta")."</a>";
        if ($order == 6) {
          if ($sort == 'asc') {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-green.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-red.png'>";
          }
          else {
            echo " <img src='".$palvelin2."pics/lullacons/arrow-single-up-red.png'> <img src='".$palvelin2."pics/lullacons/arrow-single-down-green.png'>";
          }
        }
        echo "</th>";

        echo "<th>".t("saldo")."</th>";

        if ($naytetaan_lisaanappi != "") {
          if (is_numeric($ostoskori)) {
            echo "<th>".t("lisää ostoskoriin")."</th>";
          }
          else {
            echo "<th>".t("lisää tilaukseen")."</th>";
          }
        }

        echo "</tr>";

        unset($order_saldo);

        // tehään order by switchillä niin on tietoturvallisin
        switch ($order) {
        case 1:
          $order = "tuote.tuoteno";
          break;
        case 2:
          $order = "tuote.tuotemerkki";
          break;
        case 3:
          $order = "tuote.try";
          break;
        case 4:
          $order = "tuote.nimitys";
          break;
        case 5:
          $order = "tuote.tuoteno";
          break;
        case 6:
          $order = "tuote.myyntihinta";
          break;
        case 7:
          $order_saldo = 'saldo';
        default:
          $order = "tuote.nimitys";
          break;
        }

        if (!function_exists("yhteensopivuus_vastaavat_korvaavat")) {
          function yhteensopivuus_vastaavat_korvaavat($tvk_taulu, $tvk_korvaavat, $tvk_tuoteno) {
            global $kukarow, $kieltolisa, $link, $tunnus, $tyyppi;

            if ($tvk_taulu != "vastaavat") $kyselylisa = " and {$tvk_taulu}.tuoteno != '$tvk_tuoteno' ";
            else $kyselylisa = "";

            $query = "SELECT
                      '' tuoteperhe,
                      {$tvk_taulu}.id {$tvk_taulu},
                      tuote.tuoteno,
                      tuote.nimitys,
                      tuote.osasto,
                      tuote.try,
                      tuote.myyntihinta,
                      tuote.nettohinta,
                      tuote.aleryhma,
                      tuote.status,
                      tuote.ei_saldoa,
                      tuote.yksikko,
                      tuote.tunnus,
                        (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno ORDER BY tuotteen_toimittajat.tunnus separator '<br>')
                        FROM tuotteen_toimittajat use index (yhtio_tuoteno)
                        WHERE tuote.yhtio = tuotteen_toimittajat.yhtio
                        AND tuote.tuoteno = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                      tuote.sarjanumeroseuranta,
                      if (tuote.tuoteno = '$tvk_tuoteno', 'ISÄTUOTE', '') isatuote,
                      tuote.tuotemerkki,
                      yhteensopivuus_tuote.tunnus ysttun
                      FROM {$tvk_taulu}
                      JOIN tuote ON tuote.yhtio={$tvk_taulu}.yhtio and tuote.tuoteno={$tvk_taulu}.tuoteno
                      LEFT JOIN yhteensopivuus_tuote on (yhteensopivuus_tuote.yhtio=tuote.yhtio and yhteensopivuus_tuote.tyyppi='$tyyppi' and yhteensopivuus_tuote.tuoteno=tuote.tuoteno and yhteensopivuus_tuote.atunnus='$tunnus' and yhteensopivuus_tuote.status IN ('', 'L'))
                      WHERE {$tvk_taulu}.yhtio = '$kukarow[yhtio]'
                      and {$tvk_taulu}.id = '$tvk_korvaavat'
                      $kyselylisa
                      $kieltolisa
                      ORDER BY {$tvk_taulu}.jarjestys, {$tvk_taulu}.tuoteno";
            $kores = pupe_query($query, $link);

            return $kores;
          }


        }

        if (!function_exists("yhteensopivuus_tuoteperhe")) {
          function yhteensopivuus_tuoteperhe($esiisatuoteno, $tuoteno, $isat_array, $kaikki_array, $rows, $tyyppi = 'P') {
            global $kukarow, $kieltolisa, $link;

            if (!in_array($tuoteno, $isat_array)) {
              $isat_array[] = $tuoteno;

              $query = "SELECT
                        '$esiisatuoteno' tuoteperhe,
                        tuote.tuoteno korvaavat,
                        tuote.tuoteno vastaavat,
                        tuote.tuoteno,
                        tuote.nimitys,
                        tuote.osasto,
                        tuote.try,
                        tuote.myyntihinta,
                        tuote.nettohinta,
                        tuote.aleryhma,
                        tuote.status,
                        tuote.ei_saldoa,
                        tuote.yksikko,
                        tuote.tunnus,
                         (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>')
                         FROM tuotteen_toimittajat use index (yhtio_tuoteno)
                         WHERE tuote.yhtio        = tuotteen_toimittajat.yhtio
                         AND tuote.tuoteno        = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                        tuote.sarjanumeroseuranta,
                        tuoteperhe.tyyppi,
                        'ISÄTUOTE' isatuote,
                        tuote.tuotemerkki,
                        '' ysttun
                        FROM tuoteperhe
                        JOIN tuote ON tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno
                        WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                        and tuoteperhe.isatuoteno = '$tuoteno'
                        AND tuoteperhe.tyyppi     = '$tyyppi'
                        $kieltolisa
                        ORDER BY tuoteperhe.tuoteno";
              $kores = pupe_query($query, $link);

              while ($krow = mysql_fetch_assoc($kores)) {

                unset($krow["pjarjestys"]);

                $rows[$krow["tuoteperhe"].$krow["tuoteno"]] = $krow;
                $kaikki_array[]  = $krow["tuoteno"];
              }
            }

            return array($isat_array, $kaikki_array, $rows);
          }


        }

        $query = "SELECT
                  ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'P' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') tuoteperhe,
                  ifnull((SELECT isatuoteno FROM tuoteperhe use index (yhtio_tyyppi_isatuoteno) where tuoteperhe.yhtio=tuote.yhtio and tuoteperhe.tyyppi = 'V' and tuoteperhe.isatuoteno=tuote.tuoteno LIMIT 1), '') osaluettelo,
                  ifnull((SELECT id FROM korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) korvaavat,
                  ifnull((SELECT id FROM vastaavat use index (yhtio_tuoteno) where vastaavat.yhtio=tuote.yhtio and vastaavat.tuoteno=tuote.tuoteno LIMIT 1), tuote.tuoteno) vastaavat,
                  tuote.tuoteno,
                  tuote.nimitys,
                  tuote.osasto,
                  tuote.try,
                  tuote.myyntihinta,
                  tuote.nettohinta,
                  tuote.aleryhma,
                  tuote.status,
                  tuote.ei_saldoa,
                  tuote.yksikko,
                  tuote.tunnus,
                    (SELECT group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>')
                    FROM tuotteen_toimittajat use index (yhtio_tuoteno)
                    WHERE tuote.yhtio   = tuotteen_toimittajat.yhtio
                    AND tuote.tuoteno   = tuotteen_toimittajat.tuoteno) toim_tuoteno,
                  tuote.sarjanumeroseuranta,
                  'ISÄTUOTE' isatuote,
                  tuote.tuotemerkki,
                  yhteensopivuus_tuote.tunnus as ysttun
                  FROM tuote
                  $lisa_parametri
                  LEFT JOIN yhteensopivuus_tuote on (yhteensopivuus_tuote.yhtio=tuote.yhtio and yhteensopivuus_tuote.tyyppi='$tyyppi' and yhteensopivuus_tuote.tuoteno=tuote.tuoteno and yhteensopivuus_tuote.atunnus='$tunnus' and yhteensopivuus_tuote.status IN ('', 'L'))
                  WHERE tuote.yhtio     = '$kukarow[yhtio]'
                  and tuote.tuoteno     in ($monivalinta_tuotteet)
                  AND tuote.tuotetyyppi NOT IN ('A', 'B')
                  $lisa
                  ORDER BY $order $sort";
        $result = pupe_query($query, $link);

        $rows = array();

        // Rakennetaan array ja laitetaan vastaavat ja korvaavat mukaan
        while ($mrow = mysql_fetch_assoc($result)) {

          if ($mrow["vastaavat"] != $mrow["tuoteno"]) {

            $kores = yhteensopivuus_vastaavat_korvaavat("vastaavat", $mrow["vastaavat"], $mrow["tuoteno"]);

            if (mysql_num_rows($kores) > 0) {

              $vastaavamaara = mysql_num_rows($kores);

              while ($krow = mysql_fetch_assoc($kores)) {
                if (isset($vastaavamaara)) {
                  $krow["vastaavamaara"] = $vastaavamaara;
                  $ekavastaava = $mrow["korvaavat"].$krow["tuoteno"];
                  unset($vastaavamaara);
                }
                else {
                  $krow["ekavastaava"] = $ekavastaava;
                  $krow["mikavastaava"] = $mrow["tuoteno"];
                }

                if (!isset($rows[$mrow["korvaavat"].$krow["tuoteno"]])) $rows[$mrow["korvaavat"].$krow["tuoteno"]] = $krow;
              }
            }
            else {
              $rows[$mrow["tuoteno"]] = $mrow;
            }
          }

          if ($mrow["korvaavat"] != $mrow["tuoteno"]) {
            $kores = yhteensopivuus_vastaavat_korvaavat("korvaavat", $mrow["korvaavat"], $mrow["tuoteno"]);

            if (mysql_num_rows($kores) > 0) {

              // Korvaavan isätuotetta ei listata uudestaan jos se on jo listattu vastaavaketjussa
              if (!isset($rows[$mrow["korvaavat"].$mrow["tuoteno"]])) $rows[$mrow["korvaavat"].$mrow["tuoteno"]] = $mrow;

              while ($krow = mysql_fetch_assoc($kores)) {

                $krow["mikakorva"] = $mrow["tuoteno"];

                if (!isset($rows[$mrow["korvaavat"].$krow["tuoteno"]])) $rows[$mrow["korvaavat"].$krow["tuoteno"]] = $krow;
              }
            }
            else {
              $rows[$mrow["tuoteno"]] = $mrow;
            }
          }

          if ($mrow["korvaavat"] == $mrow["tuoteno"] and $mrow["vastaavat"] == $mrow["tuoteno"]) {
            $rows[$mrow["tuoteno"]] = $mrow;

            if ($mrow["osaluettelo"] == $mrow["tuoteno"]) {
              $riikoko     = 1;
              $isat_array   = array();
              $kaikki_array   = array($mrow["tuoteno"]);
              $isa      = 0;

              // Tästä poistettu rekursiivisuus ominaisuus
              list($isat_array, $kaikki_array, $rows) = yhteensopivuus_tuoteperhe($mrow["tuoteno"], $kaikki_array[$isa], $isat_array, $kaikki_array, $rows, 'V');
            }

          }
        }

        $yht_i = 0; // tää on meiän indeksi

        echo "<form action='?order=$order&sort=$edsort$ulisa' name='lisaa' method='post'>";
        echo "<input type='hidden' name='toim' value='$toim'>";
        echo "<input type='hidden' name='table' value='{$table}'>";
        echo "<input type='hidden' name='maa' value='$maa'>";
        echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
        echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
        echo "<input type='hidden' name='rekkari' value='$rekkari'>";
        echo "<input type='hidden' name='oldrekkari' value='$oldrekkari'>";
        echo "<input type='hidden' name='rekkariid' value='$rekkariid'>";
        echo "<input type='hidden' name='merkki' value='$merkki'>";
        echo "<input type='hidden' name='malli' value='$malli'>";
        echo "<input type='hidden' name='tunnus' value='$tunnus'>";
        echo "<input type='hidden' name='toiminto' value = 'LISAARIVI'>";
        echo "<input type='hidden' name='autoid' value = '$tunnus'>";
        echo "<input type='hidden' name='osasto' value = '$osasto'>";
        echo "<input type='hidden' name='tuoteryhma' value = '$tuoteryhma'>";
        echo "<input type='hidden' name='order' value = '$order'>";
        echo "<input type='hidden' name='tultiin' value='$tultiin'>";
        echo "<input type='hidden' name='tmprekkari' value='".urlencode(serialize($tmprekkari))."'>";

        // Loopataan läpi ja dropataan pois ne rivit mitä ei tulla näyttämään
        foreach ($rows as $indexki => &$row) {

          // katotaan paljonko on myytävissä
          $saldo = 0;

          if (trim($row["tuoteperhe"]) == trim($row["tuoteno"])) {
            // Tuoteperheen isä
            $saldot = tuoteperhe_myytavissa($row["tuoteno"], "KAIKKI", "", 0, "", "", "", "", "", $maa);

            foreach ($saldot as $varasto => $myytavissa) {
              $saldo += $myytavissa;
            }

            if ($row['myyntihinta'] == 0) {
              $query = "SELECT tuote.myyntihinta, tuoteperhe.kerroin, tuoteperhe.hintakerroin
                        FROM tuoteperhe
                        JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno $kieltolisa)
                        WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                        and tuoteperhe.isatuoteno = '$row[tuoteperhe]'
                        ORDER BY tuoteperhe.tuoteno";
              $lapsires = pupe_query($query, $link);

              while ($lapsirow = mysql_fetch_assoc($lapsires)) {
                $row['myyntihinta'] += ($lapsirow['myyntihinta'] * $lapsirow['hintakerroin'] * $lapsirow['kerroin']);
              }
            }

          }
          elseif ($row['ei_saldoa'] != '') {
            $saldo = 1;
          }
          else {
            list($saldooooo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, "", "", "", "", "", $maa);
            $saldo = $myytavissa;
          }

          // Näytetään rivi vain jos kysessä on isätuote tai saldo on > 0, poistuvat tuotteet näytetään vain jos saldo on > 0 oli se isätuote tai ei
          if ($saldo <= 0 and (strtoupper($row["status"]) == "P" or $row["isatuote"] != "ISÄTUOTE")) {
            unset($rows[$indexki]);

            $rows[$row["ekavastaava"]]["vastaavamaara"]--;
          }
          else {
            if (isset($row["mikavastaava"]) and $rows[$row["ekavastaava"]]["vastaavamaara"] <= 1) {
              unset($row["mikavastaava"]);
            }

            $row['saldo'] = $saldo;
          }
        }

        foreach ($rows as $indexki =>  &$row) {
          $class = "";

          if (isset($row["mikakorva"])) {
            $class = 'spec';
            $row["nimitys"] .= "<br> * ".t("Korvaa tuotteen").": $row[mikakorva]";
          }

          $tuotteen_lisatiedot = tuotteen_lisatiedot($row["tuoteno"]);

          if (count($tuotteen_lisatiedot) > 0) {
            $row["nimitys"] .= "<ul>";
            foreach ($tuotteen_lisatiedot as $tuotteen_lisatiedot_arvo) {
              $row["nimitys"] .= "<li>$tuotteen_lisatiedot_arvo[kentta] &raquo; $tuotteen_lisatiedot_arvo[selite]</li>";
            }
            $row["nimitys"] .= "</ul>";
          }

          echo "<tr class='aktiivi'>";

          if (isset($row["vastaavamaara"]) and $row["vastaavamaara"] > 1) {
            echo "<td style='border-top: 1px solid #555555; border-left: 1px solid #555555; border-bottom: 1px solid #555555; border-right: 1px solid #555555;' rowspan='{$row["vastaavamaara"]}' align='center'>V<br>a<br>s<br>t<br>a<br>a<br>v<br>a<br>t</td>";
          }
          elseif (!isset($row["mikavastaava"])) {
            echo "<td class='back'></td>";
          }

          // Onko liitetiedostoja
          $liitteet = liite_popup("TH", $row["tunnus"]);

          // jos ei löydetä kuvaa isätuotteelta, niin katsotaan ne lapsilta
          if (trim($liitteet) == '' and trim($row["tuoteperhe"]) == trim($row["tuoteno"])) {
            $query = "SELECT tuote.tunnus
                      FROM tuoteperhe
                      JOIN tuote ON (tuote.yhtio = tuoteperhe.yhtio and tuote.tuoteno = tuoteperhe.tuoteno )
                      WHERE tuoteperhe.yhtio    = '$kukarow[yhtio]'
                      and tuoteperhe.isatuoteno = '$row[tuoteno]'
                      and tuoteperhe.tyyppi     = 'P'";
            $lapsires = pupe_query($query, $link);

            while ($lapsirow = mysql_fetch_assoc($lapsires)) {
              // Onko lapsien liitetiedostoja
              $liitteet = liite_popup("TH", $lapsirow["tunnus"]);

              if ($liitteet != '') break;
            }
          }

          if ($liitteet != "") {
            echo "<td class='$class' style='vertical-align: top;'>$liitteet</td>";
          }
          else {
            echo "<td class='$class'>&nbsp;</td>";
          }

          if ($row['ei_saldoa'] != '' and trim($row["tuoteperhe"]) != trim($row["tuoteno"])) {
            $apusaldo = "<td class='green' valign='top' align='right'>".t("On")."</td>";
          }
          else {
            if ($row['status'] == 'A' and $row['saldo'] == 0 and trim($row['tuoteperhe']) == '') {
              $tulossa_query = "SELECT DATE_ADD(MIN(tilausrivi.toimaika), INTERVAL (tuotteen_toimittajat.toimitusaika+tuotteen_toimittajat.tilausvali) DAY) paivamaara, sum(varattu) tilattu
                                FROM tilausrivi
                                JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio AND tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno)
                                WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                                AND tilausrivi.tuoteno = '$row[tuoteno]'
                                AND tilausrivi.varattu > 0
                                AND tilausrivi.tyyppi='O'";
              $tulossa_result = pupe_query($tulossa_query, $link);
              $tulossa_row = mysql_fetch_assoc($tulossa_result);

              $apusaldo = "<td class='$class' valign='top'>";
              if (mysql_num_rows($tulossa_result) > 0 and $tulossa_row["paivamaara"] != '' and $tulossa_row['tilattu'] > 0) {
                $apusaldo .= "<table style='width:100%;'>";
                $apusaldo .= "<tr><td class='$class' valign='top' align='left' nowrap>".t("TULOSSA")."</td><td class='$class' valign='top' align='right' nowrap>$tulossa_row[tilattu] ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";
                $apusaldo .= "<tr><td class='$class' nowrap>".t("Arvioitu saap.aika")."</td><td class='$class' nowrap align='right'>".tv1dateconv($tulossa_row['paivamaara'])."</td></tr>";
                $apusaldo .= "</table>";
              }
              $apusaldo .= "</td>";
            }
            elseif ($row['status'] == 'T' and $row['saldo'] == 0) {
              $query = "SELECT toimitusaika
                        FROM tuotteen_toimittajat
                        WHERE yhtio      = '$kukarow[yhtio]'
                        and tuoteno      = '$row[tuoteno]'
                        and toimitusaika > 0
                        LIMIT 1";
              $tulossa_result = pupe_query($query, $link);
              $tulossa_row = mysql_fetch_assoc($tulossa_result);

              $apusaldo = "<td class='$class' valign='top'>";
              if ($tulossa_row["toimitusaika"] > 0) {
                $apusaldo .= "<table style='width:100%;'>";
                $apusaldo .= "<tr><td class='$class' align='left' nowrap>".t("TILAUSTUOTE")."</td><td class='$class' nowrap align='right'>&nbsp;</td></tr>";
                $apusaldo .= "<tr><td class='$class' nowrap>".t("Arvioitu toim.aika")."</td><td class='$class' nowrap align='right'>".t("%s pvä", $kieli, $tulossa_row["toimitusaika"])."</td></tr>";
                $apusaldo .= "</table>";
              }
              $apusaldo .= "</td>";

            }
            else {

              // tehtaan saldot
              if ($row['saldo'] < $yhtiorow['tehdas_saldo_alaraja']) {

                $tehdas_saldo_tuoteperhelisa = " and tuotteen_toimittajat.tuoteno = '$row[tuoteno]' ";

                if (trim($row['tuoteperhe']) != '' and trim($row["tuoteperhe"]) == trim($row["tuoteno"])) {
                  $query = "SELECT group_concat(distinct concat(\"'\",tuoteno,\"'\") separator ',') lapsituotteet
                            FROM tuoteperhe
                            WHERE yhtio    = '$kukarow[yhtio]'
                            AND isatuoteno = '$row[tuoteno]'";
                  $lapsi_chk_res = pupe_query($query, $link);
                  $lapsi_chk_row = mysql_fetch_assoc($lapsi_chk_res);

                  if (trim($lapsi_chk_row['lapsituotteet']) != '') {
                    $tehdas_saldo_tuoteperhelisa = " and tuotteen_toimittajat.tuoteno in ($lapsi_chk_row[lapsituotteet]) ";
                  }
                }

                $query = "SELECT tuotteen_toimittajat.tehdas_saldo, tuotteen_toimittajat.tehdas_saldo_toimaika, toimi.oletus_toimaika
                          FROM tuotteen_toimittajat
                          JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                          WHERE tuotteen_toimittajat.yhtio      = '$kukarow[yhtio]'
                          $tehdas_saldo_tuoteperhelisa
                          and tuotteen_toimittajat.tehdas_saldo > 0";
                $tehdassaldo_res = pupe_query($query, $link);

                if (mysql_num_rows($tehdassaldo_res) > 0) {
                  $apusaldo = '';
                  $apusaldo .= "<td class='$class' valign='top'>";

                  $tehdassaldo_row = mysql_fetch_assoc($tehdassaldo_res);

                  $tehdas_saldo_toimaika = $tehdassaldo_row['oletus_toimaika'] > 0 ? $tehdassaldo_row['oletus_toimaika'] : $tehdassaldo_row['tehdas_saldo_toimaika'];

                  $apusaldo .= "<table style='width:100%;'>";
                  $apusaldo .= "<tr><td class='$class' valign='top' align='left'>".t("Tehtaalla")."</td><td class='$class' nowrap align='right'>$tehdassaldo_row[tehdas_saldo] ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td></tr>";
                  $apusaldo .= "<tr><td class='$class'>".t("Toimaika")."</td><td class='$class' nowrap align='right'>$tehdas_saldo_toimaika ".t("pvä")."</td></tr>";
                  $apusaldo .= "</table>";

                  $apusaldo .= "</td>";
                }
                else {
                  $apusaldo = "<td class='$class' valign='top' align='right'>$row[saldo] ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td>";
                }
              }
              else {
                $apusaldo = "<td class='$class' valign='top' align='right'>$row[saldo] ".t_avainsana("Y", "", " and avainsana.selite='$row[yksikko]'", "", "", "selite")."</td>";
              }
            }
          }

          $query = "SELECT *
                    from tuotteen_orginaalit
                    where yhtio = '$kukarow[yhtio]'
                    and tuoteno = '$row[tuoteno]'";
          $origresult = pupe_query($query, $link);

          if (mysql_num_rows($origresult) > 0) {

            $i = 0;

            $divit .= "<div id='div_$row[tuoteno]' class='popup'>";
            $divit .= "<table><tr><td valign='top'><table>";
            $divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";

            while ($origrow = mysql_fetch_assoc($origresult)) {
              ++$i;
              if ($i == 20) {
                $divit .= "</table></td><td valign='top'><table>";
                $divit .= "<tr><td class='back' valign='top' align='center'>".t("Alkuperäisnumero")."</td><td class='back' valign='top' align='center'>".t("Hinta")."</td><td class='back' valign='top' align='center'>".t("Merkki")."</td></tr>";
                $i = 1;
              }
              $divit .= "<tr><td class='back' valign='top'>$origrow[orig_tuoteno]</td><td class='back' valign='top' align='right'>$origrow[orig_hinta]</td><td class='back' valign='top'>$origrow[merkki]</td></tr>";
            }

            $divit .= "</table></td></tr>";

            $divit .= "</table>";
            $divit .= "</div>";

            if ($kukarow["extranet"] == "") {
              echo "<td class='$class'><a class='tooltip' id='$row[tuoteno]' href='tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
            }
            else {
              echo "<td class='$class'><a class='tooltip' id='$row[tuoteno]'>$row[tuoteno]</a></td>";
            }
          }
          else {
            if ($kukarow["extranet"] == "") {
              echo "<td class='$class' valign='top'><a href='tuote.php?tee=Z&tuoteno=".urlencode($row["tuoteno"])."'>$row[tuoteno]</a></td>";
            }
            else {
              echo "<td class='$class' valign='top'>$row[tuoteno]</td>";
            }
          }

          echo "<td class='$class' valign='top'>".t_avainsana("TUOTEMERKKI", "", " and avainsana.selite='$row[tuotemerkki]'", "", "", "selite")."</td>";

          if (isset($row['puun_tunnus']) and trim($row['puun_tunnus']) != '') {
            $query = "SELECT koodi, nimi
                      FROM dynaaminen_puu
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND laji    = 'tuote'
                      AND tunnus  = '{$row['puun_tunnus']}'";
            $trynimi_res = pupe_query($query);
            $trynimi_row = mysql_fetch_assoc($trynimi_res);
            $row['try'] = ($trynimi_row['koodi'] != '' and $trynimi_row['koodi'] != 0) ? $trynimi_row['koodi'].' - '.$trynimi_row['nimi'] : $trynimi_row['nimi'];
          }

          echo "<td class='$class' valign='top'>$row[try]</td>";
          echo "<td class='$class' valign='top'>".t_tuotteen_avainsanat($row, 'nimitys')."</td>";


          echo "<td class='$class' valign='top' style='padding:0px;'>";

          $ytlsql = "SELECT otsikko, arvo, nakyvyys
                     FROM yhteensopivuus_tuote_lisatiedot
                     WHERE yhtio                     = '{$kukarow['yhtio']}'
                     and yhteensopivuus_tuote_tunnus = '{$row['ysttun']}'
                     and status                      IN ('', 'L')
                     and nakyvyys                    IN ('', 'K')
                     ORDER BY jarjestys, otsikko";
          $extra_res = pupe_query($ytlsql);

          if (mysql_num_rows($extra_res) != 0) {
            echo "<table>";
            while ($extrarivit = mysql_fetch_assoc($extra_res)) {
              if ($extrarivit['nakyvyys'] == 'K') {
                echo "<tr><td colspan='2'>{$extrarivit['arvo']}</td></tr>";
              }
              else {
                echo "<tr><td>{$extrarivit['otsikko']}:</td><td>{$extrarivit['arvo']}</td></tr>";
              }
            }
            echo "</table>";
          }

          echo "</td>";

          if ($kukarow["oletus_asiakas"] != 0) {

            $query = "SELECT *
                      FROM asiakas
                      WHERE tunnus='$kukarow[oletus_asiakas]' and yhtio='$kukarow[yhtio]'";
            $asres = pupe_query($query, $link);
            $asrow = mysql_fetch_assoc($asres);

            $query = "SELECT kurssi
                      FROM valuu
                      WHERE nimi='$asrow[valkoodi]' and yhtio = '$kukarow[yhtio]'";
            $asres = pupe_query($query, $link);
            $kurssi = mysql_fetch_assoc($asres);

            $spec_laskurow = array();
            $spec_laskurow["valkoodi"]    = $asrow["valkoodi"];
            $spec_laskurow["maa"]      = $asrow["maa"];
            $spec_laskurow["vienti_kurssi"]  = $kurssi["kurssi"];

            $spec_trow['tuoteno']      = $row["tuoteno"];
            $spec_trow['myyntihinta']    = $row["myyntihinta"];
            $spec_trow['nettohinta']    = 0;

            echo "<td class='$class' valign='top' align='right'>".hintapyoristys(tuotteen_myyntihinta($spec_laskurow, $spec_trow, 1))." $asrow[valkoodi]</td>";
          }
          elseif ($verkkokauppa != "" and $valkoodi != "" and $maa != "") {
            $query = "SELECT kurssi
                      FROM valuu
                      WHERE nimi='$valkoodi' and yhtio = '$kukarow[yhtio]'";
            $asres = pupe_query($query, $link);
            $kurssi = mysql_fetch_assoc($asres);

            $spec_laskurow = array();
            $spec_laskurow["valkoodi"]    = $valkoodi;
            $spec_laskurow["maa"]      = $maa;
            $spec_laskurow["vienti_kurssi"]  = $kurssi["kurssi"];

            $spec_trow['tuoteno']      = $row["tuoteno"];
            $spec_trow['myyntihinta']    = $row["myyntihinta"];
            $spec_trow['nettohinta']    = 0;

            echo "<td class='$class' valign='top' align='right'>".hintapyoristys(tuotteen_myyntihinta($spec_laskurow, $spec_trow, 1))." $valkoodi</td>";

          }
          else {
            echo "<td class='$class' valign='top' align='right'>".hintapyoristys($row["myyntihinta"])." $yhtiorow[valkoodi]</td>";
          }

          echo "$apusaldo";

          if ($naytetaan_lisaanappi != "") {
            if (($row["tuoteperhe"] == "" or $row["tuoteperhe"] == $row["tuoteno"] or $row["tyyppi"] == "V") and $row["osaluettelo"] == "") {
              echo "<td valign='top' class='$class'>";
              echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
              echo "<input type='text' size='4' name='tilkpl[$yht_i]'>";
              echo "<input type='submit' value = '".t("Lisää")."'>";
              echo "</td>";
              $yht_i++;
            }
            else {
              echo "<td valign='top' class='$class'>";
              echo "</td>";
            }
          }

          echo "</tr>";

        } // end while tuote

        echo "</form>";
        echo "</table>";
        echo "</div>";

      }
    }
    else {
      echo "<br><font class='message'>".t("Valitsemaasi malliin ei löydy yhtään soveltuvia tuotteita")."!</font>";
    }
  }
}

echo "<br><font class='info'>".t("Emme vastaa varaosaselaimen mahdollisesti sisältämien virheellisyyksien johdosta aiheutuvista vahingoista").".</font>";
echo "<br><font class='info'>".t("Varmista osien sopivuus ennen asennusta").".</font>";
echo "<br><font class='info'>".t("Takuu ei korvaa virheellisestä asennuksesta johtuvia vahinkoja").".</font>";

echo $divit;

if (file_exists("inc/footer.inc")) {
  require "inc/footer.inc";
}
else {
  require "footer.inc";
}
