<?php

require "../inc/parametrit.inc";

if (!isset($tee)) $tee = "";
if (!isset($tyojono)) $tyojono = "";

if ($indexvas == "1" and empty($viikkonakyma)) {
  $viikkonakyma = date("W");
  list($year, $kuu, $paiva) = explode("-", date("Y-m-d"));
}

// otetaan oletukseksi t‰m‰ kuukausi, vuosi ja p‰iv‰
if (empty($paiva)) $paiva = date("j");
if (empty($kuu))   $kuu   = date("n");
if (empty($year))  $year  = date("Y");

//lasketaan edellinen ja seuraava kuukausi/vuosi
$backmonth= date("n", mktime(0, 0, 0, $kuu-1, 1,  $year));
$backyear = date("Y", mktime(0, 0, 0, $kuu-1, 1,  $year));
$nextmonth= date("n", mktime(0, 0, 0, $kuu+1, 1,  $year));
$nextyear = date("Y", mktime(0, 0, 0, $kuu+1, 1,  $year));

$edelday  = date("j", mktime(0, 0, 0, $kuu, $paiva-1,  $year));
$edelmonth= date("n", mktime(0, 0, 0, $kuu, $paiva-1,  $year));
$edelyear = date("Y", mktime(0, 0, 0, $kuu, $paiva-1,  $year));
$seurday  = date("j", mktime(0, 0, 0, $kuu, $paiva+1,  $year));
$seurmonth= date("n", mktime(0, 0, 0, $kuu, $paiva+1,  $year));
$seuryear = date("Y", mktime(0, 0, 0, $kuu, $paiva+1,  $year));

if (!isset($MONTH_ARRAY)) $MONTH_ARRAY = array(1=> t('Tammikuu'), t('Helmikuu'), t('Maaliskuu'), t('Huhtikuu'), t('Toukokuu'), t('Kes‰kuu'), t('Hein‰kuu'), t('Elokuu'), t('Syyskuu'), t('Lokakuu'), t('Marraskuu'), t('Joulukuu'));
if (!isset($AIKA_ARRAY)) $AIKA_ARRAY = array("08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00");
if (!isset($DAY_ARRAY)) $DAY_ARRAY = array(1=> t('Maanantai'), t('Tiistai'), t('Keskiviikko'), t('Torstai'), t('Perjantai'), t('Lauantai'), t('Sunnuntai'));

function days_in_month($kuu, $year) {
  // calculate number of days in a month
  return $kuu == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($kuu - 1) % 7 % 2 ? 30 : 31);
}

function weekday_number($paiva, $kuu, $year) {
  // calculate weekday number
  $nro = date("w", mktime(0, 0, 0, $kuu, $paiva, $year));
  if ($nro==0) $nro=6;
  else $nro--;

  return $nro;
}

//jos muutparametrit os setattu niin otetaan siit‰ ulos ruksatut kalenterit
if (isset($muutparametrit)) {
  $valx = explode('!°!', $muutparametrit);
  $valitut = $valx[0];
}

//t‰ss‰ m‰‰ritell‰‰n kenen kaikkien kalenterit n‰ytet‰‰n
if (isset($kalen)) {
  foreach ($kalen as $tama) {
    $valitut .= "$tama,";
  }
  $valitut = substr($valitut, 0, -1); // Viimeinen pilkku poistetaan
}
else {
  if (!isset($valitut)) {
    $valitut = "$kukarow[kuka]"; // Jos ketaan ei ole valittu valitaan kayttaja itse
    $vertaa  = "'$kukarow[kuka]'";
  }
  else {
    $vertaa = "'$valitut'";
  }
}

$valitut = urldecode($valitut);

$ruksatut   = explode(",", $valitut);        //tata kaytetaan ihan lopussa
$ruksattuja = count($ruksatut);             //taman avulla pohditaan tarvitaanko tarkenteita
$vertaa     = "'".implode("','", $ruksatut)."'";  // tehd‰‰n mysql:n ymm‰rt‰m‰ muoto

if (trim($konserni) != '') {
  $query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
  $result = pupe_query($query);
  $konsernit = "";

  while ($row = mysql_fetch_assoc($result)) {
    $konsernit .= " '".$row["yhtio"]."' ,";
  }
  $konsernit = " and kalenteri.yhtio in (".substr($konsernit, 0, -1).") ";
  $kons = 1;
}
else {
  $konsernit = " and kalenteri.yhtio = '$kukarow[yhtio]' ";
  $kons = 0;
}

$asmemolinkki = tarkista_oikeus("asiakasmemo.php");

// Poistetaan liitetiedosto kalenterimerkinn‰st‰
// mik‰li GET-parametreist‰ lˆytyy poista_liite
if ( isset($poista_liite) and (int) $poista_liite > 0 ) {
  $poista_liite = (int) $poista_liite;

  $query = "DELETE FROM liitetiedostot
            WHERE tunnus = $poista_liite
            AND liitos   = 'kalenterimerkint‰'
            AND yhtio    = '$kukarow[yhtio]'";
  pupe_query($query);
}

echo "<font class='head'>".t("Kalenteri")."</font>";

if ($yhtiorow["kalenteri_aikavali"] == "") {
  $aikavali = 30;
}
else {
  $aikavali = (int)$yhtiorow["kalenteri_aikavali"];
}

// ollaan painettu lis‰‰ nappia
if ($tee == 'LISAA') {

  $ok = '';

  if ($ytunnus != '') {
    if (!isset($muutparametrit)) {
      $muutparametrit  =   $valitut."!°!".$kenelle."!°!".$asyhtio."!°!".$kello."!°!".$year."!°!".$kuu."!°!".$paiva."!°!".$tunnus."!°!".$konserni."!°!".$lkello."!°!".$lyear."!°!".$lkuu."!°!".$lpaiva."!°!".$tapa."!°!".$lopetus."!°!".$viesti."!°!".$tyomaarays;
    }

    echo "<br><font class='message'>".t("Valitse asiakas").":</font><br><br>";

    $kutsuja = 'kalenteri.php';
    $ahlopetus   = $palvelin2."crm/kalenteri.php////tee=LISAA//ytunnus=$ytunnus//muutparametrit=$muutparametrit";

    require "inc/asiakashaku.inc";

    if ($ytunnus == '') {
      exit;
    }
  }

  if ($ytunnus != '') {
    $muut = explode('!°!', $muutparametrit);

    $valitut   = $muut[0];
    $kenelle   = $muut[1];
    $asyhtio  = $muut[2];
    $kello     = $muut[3];
    $year     = $muut[4];
    $kuu     = $muut[5];
    $paiva     = $muut[6];
    $tunnus   = $muut[7];
    $konserni   = $muut[8];
    $lkello   = $muut[9];
    $lyear     = $muut[10];
    $lkuu     = $muut[11];
    $lpaiva   = $muut[12];
    $tapa     = $muut[13];
    $lopetus  = $muut[14];
    $viesti   = $muut[15];
    $tyomaarays  = $muut[16];

    $ok = "OK";
  }
  elseif (isset($muutparametrit)) {
    $tee    = "SYOTA";
    $muut    = explode('!°!', $muutparametrit);

    $valitut   = $muut[0];
    $kenelle   = $muut[1];
    $asyhtio  = $muut[2];
    $kello     = $muut[3];
    $year     = $muut[4];
    $kuu     = $muut[5];
    $paiva     = $muut[6];
    $tunnus   = $muut[7];
    $konserni   = $muut[8];
    $lkello   = $muut[9];
    $lyear     = $muut[10];
    $lkuu     = $muut[11];
    $lpaiva   = $muut[12];
    $tapa     = $muut[13];
    $lopetus  = $muut[14];
    $viesti   = $muut[15];
    $tyomaarays  = $muut[16];
  }
  else {
    //syˆtet‰‰n siis ilman asiakasta
    $ok = "OK";
  }

  if ($ok == "OK") {
    $tunnus = (int) $tunnus;

    if ($tunnus > 0) {
      $query = "DELETE
                FROM kalenteri
                WHERE tunnus=$tunnus
                $konsernit";
      pupe_query($query);
    }

    if ($kenelle == "") {
      $kenelle = $kukarow["kuka"];
    }

    if ($asiakasyhtio!='') {
      $kyhtio = $asiakasyhtio;
    }
    elseif ($asyhtio != '') {
      $kyhtio = $asyhtio;
    }
    else {
      $kyhtio = $kukarow["yhtio"];
    }

    $kokopaiva = "";

    if ($lkello == "KOKOPAIVA") {
      $kello = "00:00:00";
      $lkello = "23:59:59";
      $kokopaiva = "x";
    }
    elseif ($lkello == "00:00") {
      $lkello = "23:59:59";
    }
    elseif ($kello == $lkello) {
      $lkello = $lkello.":01";
    }
    else {
      $lkello = $lkello.":00";
    }

    if ($kukarow["kieli"] != 'fi') {
      $query = "SELECT selite from avainsana
                where yhtio    = '$kukarow[yhtio]'
                and laji       = 'KALETAPA'
                and selitetark = '$tapa'
                and kieli      = '$kukarow[kieli]'";
      $tapa_res = pupe_query($query);
      $tapa_row = mysql_fetch_assoc($tapa_res);

      $tapa_res = t_avainsana("KALETAPA", "fi", "and avainsana.selite = '{$tapa_row['selite']}'");
      $tapa_row = mysql_fetch_assoc($tapa_res);

      if (!empty($tapa_row['selitetark'])) $tapa = $tapa_row['selitetark'];
    }

    $query = "INSERT INTO kalenteri
              SET
              yhtio        = '$kyhtio',
              laatija      = '$kukarow[kuka]',
              kuka         = '$kenelle',
              pvmalku      = '$year-$kuu-$paiva $kello:00',
              pvmloppu     = '$lyear-$lkuu-$lpaiva $lkello',
              asiakas      = '$ytunnus',
              liitostunnus = '$asiakasid',
              kokopaiva    = '$kokopaiva',
              kentta01     = '$viesti',
              kuittaus     = '$kuittaus',";

    if ($toim == 'TYOMAARAYS_ASENTAJA' or $tyomaarays != '') {
      $query .= "kentta02  = '$tyomaarays',";
    }

    $query .= "kentta03 = '$kilometrit',
               kentta04 = '$paivarahat',
               tapa     = '$tapa',
               tyyppi   = 'kalenteri'";
    pupe_query($query);

    $uusi_tunnus = mysql_insert_id($GLOBALS["masterlink"]);

    // P‰ivitet‰‰n liitetiedosto uudelle tunnukselle,
    // mik‰li aikaisempi kalenterimerkint‰ p‰ivittyi.
    // T‰m‰ siis sen takia, ett‰ kalenterimerkinn‰n p‰ivitys
    // tapahtuu poistamalla vanha merkint‰ ja lis‰‰m‰ll‰ uusi p‰ivitetty
    // versio merkinn‰st‰.
    if ($tunnus > 0 and $uusi_tunnus > 0) {
      $query = "UPDATE liitetiedostot
                SET liitostunnus = $uusi_tunnus
                WHERE liitostunnus = $tunnus
                AND liitos         = 'kalenterimerkint‰'
                AND yhtio          = '{$kukarow['yhtio']}'";
      pupe_query($query);
    }

    // Tallenna liitetiedosto formista
    if (is_uploaded_file($_FILES['liitetiedosto']['tmp_name']) === TRUE) {
      tallenna_liite('liitetiedosto', 'kalenterimerkint‰', $uusi_tunnus, '');
    }

    echo "
    <script>
       window.opener.location.reload();
       window.close();
    </script>";
  }
}

// ollaan painettu poista nappia
if ($tee == "POISTA" and (int) $tunnus > 0) {
  $tunnus = (int) $tunnus;

  $query = "DELETE FROM kalenteri
            WHERE tunnus = $tunnus
            $konsernit";
  pupe_query($query);

  // Poistetaan myˆs liitetiedostot t‰lle kalenterimerkinn‰lle
  $query = "DELETE FROM liitetiedostot
            WHERE liitostunnus = {$tunnus}
            AND liitos         = 'kalenterimerkint‰'
            AND yhtio          = '{$kukarow['yhtio']}'";
  pupe_query($query);

  echo "
  <script>
     window.opener.location.reload();
     window.close();
  </script>";
}

// tehd‰‰n lis‰ys ruutu ja laitetaan kaikki muuttujaan jotta voidaan echota sit oikeessa kohdassa
if ($tee == "SYOTA") {
  if (!empty($tunnus)) {
    $kukalisa = $toim == 'TYOMAARAYS_ASENTAJA' ? " AND kuka = '$kukarow[kuka]' " : '';

    $query = "SELECT *,
              if(asiakas='0','',asiakas) asiakas,
              if(liitostunnus=0,'',liitostunnus) liitostunnus,
              substring(pvmalku, 12, 5) kello,
              substring(pvmloppu, 12, 5) lkello
              FROM kalenteri
              WHERE tunnus = '$tunnus'
              $konsernit
              $kukalisa
              and tyyppi in ('muistutus','kalenteri')";
    $res  = pupe_query($query);
    $irow = mysql_fetch_assoc($res);

    $viesti     = $irow["kentta01"];
    $kuittaus   = $irow["kuittaus"];
    $kilometrit = $irow["kentta03"];
    $paivarahat = $irow["kentta04"];
    $tapa       = $irow["tapa"];
    $ytunnus    = $irow["asiakas"];
    $asiakasid  = $irow["liitostunnus"];
    $kokopaiva  = $irow["kokopaiva"];
    $kello      = $irow["kello"];
    $lkello     = $irow["lkello"];

    list($year, $kuu, $paiva) = explode("-", substr($irow["pvmalku"], 0, 10));
    list($lyear, $lkuu, $lpaiva) = explode("-", substr($irow["pvmloppu"], 0, 10));

    $asyhtio    = $irow["yhtio"];
    $kenelle    = $irow["kuka"];

    if ($toim == 'TYOMAARAYS_ASENTAJA') {
      $tyomaarays = $irow["kentta02"];
    }

    if ($irow["lopkello"] == "23:59:59") $aikaloppu = "00:00:00";
    else $aikaloppu = $irow["lopkello"];
  }
  else {
    $lyear  = $year;
    $lkuu   = $kuu;
    $lpaiva = $paiva;
  }

  $lisays =  "
    <script type='text/javascript'>
      function paivita_loppukello () {
        alku = $('#alkukello option:selected').val();
        $('#loppukello').val(alku).change();
      }
    </script>

    <form method='POST' enctype='multipart/form-data'>
    <input type='hidden' name='tee' value='LISAA'>
    <input type='hidden' name='lopetus' value='$lopetus'>
    <input type='hidden' name='valitut' value='".urlencode($valitut)."'>
    <input type='hidden' name='kenelle' value='".urlencode($kenelle)."'>
    <input type='hidden' name='asyhtio' value='$asyhtio'>
    <input type='hidden' name='tunnus'   value='$tunnus'>
    <input type='hidden' name='konserni' value='$konserni'>
    <input type='hidden' name='asiakasid' value='$asiakasid'>
    <input type='hidden' name='tyomaarays' value='$tyomaarays'>
    <input type='hidden' name='toim' value='$toim'>

  <table width='100%'>";

  $lisays .= "<tr><th colspan='2'>".t("Lis‰‰ tapahtuma").":</th></tr>";

  $lisays .= "<tr>
    <td nowrap>".t("Alku").":</td>
    <td><input type='text' size='3' name='paiva' value='$paiva'>
    <input type='text' size='3' name='kuu'   value='$kuu'>
    <input type='text' size='5' name='year'  value='$year'>";

  $paivitaloppu = "";
  if (empty($tunnus)) {
    $paivitaloppu = "onchange='paivita_loppukello();'";
  }

  $lisays .= " - <select name='kello' id='alkukello' $paivitaloppu>";

  if ($kello == ""){
    $kello = "08:00";
  }

  $loophh = "{$AIKA_ARRAY[0]}";
  $loopmm = "{$aikavali}";

  list($whlopt, $whlopm) = explode(":", $AIKA_ARRAY[count($AIKA_ARRAY)-1]);
  $whileloppu = sprintf("%02d", $whlopt+1);

  if ($whileloppu >= 24) $whileloppu = sprintf("%02d", $whileloppu-24);

  $whileloppu = $whileloppu.":".$whlopm;
  $loopdate = "";

  while ($loopdate != $whileloppu) {
    $loopdate = date("H:i", mktime($loophh, $loopmm+$aikavali, 0));
    $loophh   = date("H",   mktime($loophh, $loopmm+$aikavali, 0));
    $loopmm   = date("i",   mktime($loophh, $loopmm+$aikavali, 0));

    $sel = '';
    if ($loopdate == substr($kello, 0, 5)) {
      $sel = "SELECTED";
    }

    $lisays .= "<option value='$loopdate' $sel>$loopdate</option>";
  }

  $lisays .= "</select> ";
  $lisays .= "</td></tr>";

  $lisays .= "<tr><td nowrap>".t("Loppu").":</td>";
  $lisays .= "<td><input type='text' size='3' name='lpaiva' value='$lpaiva'>
              <input type='text' size='3' name='lkuu'   value='$lkuu'>
              <input type='text' size='5' name='lyear'  value='$lyear'>";

  $lisays .= " - <select name='lkello' id='loppukello'>";

  $loophh = "{$AIKA_ARRAY[0]}";
  $loopmm = "{$aikavali}";

  if (empty($lkello)) {
    $lkello = date("H:i", strtotime('+{$aikavali} minutes', strtotime($kello)));
  }

  $loopdate = "";

  while ($loopdate != $whileloppu) {
    $loophh   = date("H", mktime($loophh, $loopmm+$aikavali, 0));
    $loopmm   = date("i", mktime($loophh, $loopmm+$aikavali, 0));
    $loopdate = date("H:i", mktime($loophh, $loopmm+$aikavali, 0));

    $sel = '';
    if ($loopdate == substr($lkello, 0, 5)) {
      $sel = "SELECTED";
    }

    $lisays .= "<option value='$loopdate' $sel>$loopdate</option>";
  }

  $sel = (!empty($kokopaiva)) ? "SELECTED" : "";
  $lisays .= "<option value='KOKOPAIVA' $sel>".t("Kokop‰iv‰")."</option>";
  $lisays .= "</select></td></tr>";

  $vresult = t_avainsana("KALETAPA");
  $lisays .= "<tr><td>".t("Tapa").":</td><td><select name='tapa'>";

  if (!empty($tapa)) {
    $lisays .= "<option value = '$tapa'>$tapa</option>";
  }

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if ($tapa == $vrow["selitetark"]) {
      $sel = "selected";
    }
    $lisays .= "<option value = '$vrow[selitetark]' $sel>$vrow[selitetark]</option>";
  }
  $lisays .= "</select></td></tr>";

  if ($yhtiorow["monikayttajakalenteri"] == "" or $kukarow["asema"] == "MP") {

    $lisays .= "<tr><td>".t("Kalenteri")."</td><td><select name='kenelle'>";
    $lisays .= "<option value='$kukarow[kuka]'>".t("Oma")."</option>";

    $query = "SELECT distinct kuka.nimi, kuka.kuka
              FROM kuka, oikeu
              WHERE kuka.yhtio = '$kukarow[yhtio]'
              and oikeu.yhtio  = kuka.yhtio
              and oikeu.kuka   = kuka.kuka
              and oikeu.nimi   = 'crm/kalenteri.php'
              and kuka.tunnus  <> '$kukarow[tunnus]'
              ORDER BY kuka.nimi";
    $result = pupe_query($query);

    while ($row = mysql_fetch_assoc($result)) {
      $sel = $kenelle == $row['kuka'] ? ' selected' : '';
      $lisays .= "<option value='$row[kuka]'$sel>$row[nimi]</option>";
    }

    $lisays .= "</select></td></tr>";
  }

  $lisays .= "<tr><td>".t("Asiakas").":</td><td><input type text name='ytunnus' value='$ytunnus'>";
  $lisays .= "</td></tr>";

  if ($toim == 'TYOMAARAYS_ASENTAJA' or $tyomaarays != '') {
    $lisays .= "<tr><td>".t("Tyˆm‰‰r‰ys").":</td><td><input type text name='tyomaarays' value='$tyomaarays'>";
    $lisays .= "</td></tr>";
  }

  $lisays .= "<tr><td class='ptop'>".t("Kommentti").":</td>";
  $lisays .= "<td>
          <textarea name='viesti' cols='50' rows='5'>$viesti</textarea><br>
          </td>
          </tr>";

  $chk = "";

  if (!empty($kuittaus)) {
    $chk = "CHECKED";
  }

  $lisays .= "<tr><td class='ptop'>".t("Tehty").":</td>";
  $lisays .= "<td>
          <input type='checkbox' name='kuittaus' value='K' $chk>
          </td>
          </tr>";

  /*
  $lisays .= "<tr><td class='ptop'>".t("Kilometrit").":</td>";
  $lisays .= "<td>
          <input name='kilometrit' value='$kilometrit'><br>
          </td>
          </tr>";

  $lisays .= "<tr><td class='ptop'>".t("P‰iv‰rahat").":</td>";
  $lisays .= "<td>
          <input name='paivarahat' value='$paivarahat'><br>
          </td>
          </tr>";
  */

  $query = "SELECT *
            from liitetiedostot
            where yhtio      = '$kukarow[yhtio]'
            and liitos       = 'kalenterimerkint‰'
            and liitostunnus = '$tunnus'";
  $liiteres = pupe_query($query);

  if (mysql_num_rows($liiteres) > 0) {
    $lisays .= "<tr><td class='ptop'>".t("Liitetiedostot").":</td>";
    $lisays .= "<td><table><tbody>";

    while ($liiterow = mysql_fetch_assoc($liiteres)) {
      $lisays .= '<tr>';
      $lisays .= "<td><a href=\"#\" onclick=\"window.open('".$palvelin2."view.php?id=$liiterow[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=800,height=600'); return false;\">$liiterow[filename]</a></td>";
      $lisays .= '<td><a onclick="if (window.confirm(\'Haluatko poistaa liitteen '. $liiterow['filename'] .'\')===false ) return false;" href="'. $_SERVER['REQUEST_URI'] .'&poista_liite='. $liiterow['tunnus'] .'">Poista</a></td>';
      $lisays .= '</tr>';
    }

    $lisays .= "</tbody></table></td></tr>";
  }

  $lisays .= "<tr><td class='ptop'>".t("Lis‰‰ uusi Liitetiedosto").":</td>";
  $lisays .= "<td>
          <input name='liitetiedosto' type='file'><br>
          </td>
          </tr>";

  $lisays .= "<tr><td><input type='submit' value='".t("Tallenna")."'></td></form>
        <form method='POST'>
        <input type='hidden' name='tee' value='POISTA'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='valitut' value='".urlencode($valitut)."'>
        <input type='hidden' name='year' value='$year'>
        <input type='hidden' name='kuu' value='$kuu'>
        <input type='hidden' name='paiva' value='$paiva'>
        <input type='hidden' name='tunnus' value='$tunnus'>
        <input type='hidden' name='konserni' value='$konserni'>
        <input type='hidden' name='tyomaarays' value='$tyomaarays'>
        <input type='hidden' name='toim' value='$toim'>
        <td><input type='submit' value='".t("Poista")."'></td></form></tr>
        </table></td>";

  echo "$lisays";
  exit;
}

//Paivan tapahtumat
//t‰st‰ alkaa main table
echo "<table class='pnopad'>";
echo "<tr>";

echo "<td class='back pnopad ptop' width='100%'>";

echo "<table width='100%'><tr>";

if (!empty($viikkonakyma)) {
  list($edelyear, $edelmonth, $edelday) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva-7, $year)));
  list($seuryear, $seurmonth, $seurday) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+7, $year)));

  $edelviikko = date("W", mktime(0, 0, 0, $kuu, $paiva-7, $year));
  $seurviikko = date("W", mktime(0, 0, 0, $kuu, $paiva+7, $year));

  echo "<td nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&viikkonakyma=$edelviikko&year=$edelyear&kuu=$edelmonth&paiva=$edelday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'><< ".t("Edellinen")."</a></td>
        <td style='text-align:center' nowrap>Viikko: ".date("W", mktime(0, 0, 0, $kuu, $paiva, $year))."</td>
        <td style='text-align:right' nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&viikkonakyma=$seurviikko&year=$seuryear&kuu=$seurmonth&paiva=$seurday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'>".t("Seuraava")." >></a></td>";
}
else {
  echo "<td nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$edelyear&kuu=$edelmonth&paiva=$edelday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'><< ".t("Edellinen")."</a></td>
        <td style='text-align:center' nowrap>$paiva. ".$MONTH_ARRAY[(int) $kuu]." $year</td>
        <td style='text-align:right' nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$seuryear&kuu=$seurmonth&paiva=$seurday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'>".t("Seuraava")." >></a></td>";
}

echo "</tr>";
echo "</table>";
echo "</td>";

//oikean yl‰laidan pikkukalenteri..
echo "<td class='back ptop pnopad' style='padding-right:5px;' rowspan='2'>";

echo "<table width='100%'>
    <tr><td class='back' align='center' colspan='8'>
    <form action = '?valitut=".urlencode($valitut)."&year=$year&paiva=1&konserni=$konserni' method='post'>
    <input type='hidden' name='lopetus' value='$lopetus'>
    <input type='hidden' name='toim' value='$toim'>
    <input type='hidden' name='tyomaarays' value='$tyomaarays'>
    <select name='kuu' onchange='submit();'>";

$i=1;
foreach ($MONTH_ARRAY as $val) {
  if ($i == $kuu) {
    $sel = "selected";
  }
  else {
    $sel = "";
  }
  echo "<option value='$i' $sel>$val $year</option>";
  $i++;
}

echo "</select></form></td></tr>";

echo "<tr><th>".t("Vk.")."</th><th>".t("Ma")."</th><th>".t("Ti")."</th><th>".t("Ke")."</th><th>".t("To")."</th><th>".t("Pe")."</th><th>".t("La")."</th><th>".t("Su")."</th></tr>";

$ekaviikko = date("W", mktime(0, 0, 0, $kuu, 1, $year));

$viikkostyle = "";

if (!empty($viikkonakyma) and $viikkonakyma == $ekaviikko) {
  $viikkostyle="border:1px solid #FF0000;";
}

echo "<tr><th style='$viikkostyle'><a href='{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&year=$year&kuu=$kuu&paiva=1&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&viikkonakyma=$ekaviikko&lopetus=$lopetus'>$ekaviikko</a></th>";

// kirjotetaan alkuun tyhji‰ soluja
for ($i=0; $i < weekday_number("1", $kuu, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

$tyolista = array();

// kirjoitetaan p‰iv‰m‰‰r‰t taulukkoon..
for ($i=1; $i <= days_in_month($kuu, $year); $i++) {
  $font="";
  $class="";
  $style="";

  $paiv = sprintf("%02d", $i);
  $kuu2 = sprintf("%02d", $kuu);

  $query = "SELECT *, left(pvmalku, 10) alkudate
            from kalenteri
            where tyyppi in ('kalenteri', 'muistutus')
            and pvmalku <= '$year-$kuu2-$paiv 23:59:59'
            and pvmloppu >= '$year-$kuu2-$paiv 00:00:00'
            and kuka in ($vertaa)
            $konsernit";
  $result = pupe_query($query);

  //v‰ritet‰‰n t‰m‰n p‰iv‰n pvm omalla v‰rill‰...
  if (date("j") == $i and date("n") == $kuu and date("Y") == $year) {
    $fn1 = "<font class='ok'>";
    $fn2 = "</font>";
  }
  else {
    $fn1 = "";
    $fn2 = "";
  }

  //jos on valittu joku tietty p‰iv‰, tehd‰‰n solusta tumma
  if (empty($viikkonakyma) and $paiva == $i) {
    $style="border:1px solid #FF0000;";
  }

  if (mysql_num_rows($result) != 0) {
    $class = "tumma";

    while ($tyolistarow = mysql_fetch_assoc($result)) {
      if ($tyolistarow['kuka'] == $kukarow["kuka"]) {
        $tyolista[$tyolistarow["alkudate"]][] = $tyolistarow;
      }
    }
  }

  echo "<td align='center' style='$style' class='$class'><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$year&kuu=$kuu&paiva=$i&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>$fn1 $i $fn2</a></td>";

  // tehd‰‰n rivinvaihto jos kyseess‰ on sunnuntai ja seuraava p‰iv‰ on olemassa..
  if ((weekday_number($i, $kuu, $year)==6) and (days_in_month($kuu, $year)>$i)) {
    $weeknro=date("W", mktime(0, 0, 0, $kuu, $i+1, $year));

    $viikkostyle = "";

    if (!empty($viikkonakyma) and $viikkonakyma == $weeknro) {
      $viikkostyle="border:1px solid #FF0000;";
    }

    echo "</tr><tr><th style='$viikkostyle'><a href='{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&year=$year&kuu=$kuu&paiva=".($i+1)."&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&viikkonakyma=$weeknro&lopetus=$lopetus'>$weeknro</a></th>";
  }
}

//kirjoitetaan loppuun tyhji‰ soluja
for ($i=0; $i<6 - weekday_number(days_in_month($kuu, $year), $kuu, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

echo "</tr>";

echo "<tr><td class='back' align='center' colspan='8'><a href='$PHP_SELF?valitut=".urlencode($valitut)."&kuu=$backmonth&year=$backyear&paiva=1&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>".t("Edellinen")."</a>  - <a href='$PHP_SELF?valitut=".urlencode($valitut)."&kuu=$nextmonth&year=$nextyear&paiva=1&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>".t("Seuraava")."</a><br><br></td></tr>";
echo "</table>";

// Tyˆlista
echo "<table width='100%'>";

if (count($tyolista[date("Y-m-d")]) > 0) {
  echo "<tr><th>".t("T‰n‰‰n")."</th></tr>";

  foreach($tyolista[date("Y-m-d")] as $tyot) {
    echo "<tr><td>$tyot[kentta01]</td></tr>";
  }

  echo "<tr><td class='back'><br></td></tr>";
}

if (count($tyolista[date("Y-m-d", strtotime("TOMORROW"))]) > 0) {
  echo "<tr><th>".t("Huomenna")."</th></tr>";

  foreach($tyolista[date("Y-m-d", strtotime("TOMORROW"))] as $tyot) {
    echo "<tr><td>$tyot[kentta01]</td></tr>";
  }
}

echo "</table>";

if ($yhtiorow["monikayttajakalenteri"] == "" or $kukarow["asema"] == "MP") {

  $ckhk = "";
  if (trim($konserni) != '') {
    $ckhk = "CHECKED";
  }

  //konsernivalinta
  if ($yhtiorow["konserni"] != "") {
    echo "<form action = '?valitut=".urlencode($valitut)."&year=$year&kuu=$kuu&paiva=$paiva' method='post'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='tyomaarays' value='$tyomaarays'>";
    echo "<input type='checkbox' name='konserni' $ckhk onclick='submit();'> ".t("Kaikkien konserniyritysten merkinn‰t");
    echo "</form>";
  }

  //kalenterivalinnat
  echo "<br><br>";

  if (in_array("$kukarow[kuka]", $ruksatut)) { // Oletko valinnut itsesi
    $checked = 'checked';
  }

  echo "<form action = '?year=$year&kuu=$kuu&paiva=$paiva&konserni=$konserni' method='post'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='viikkonakyma' value='$viikkonakyma'>
      <input type='hidden' name='tyojono' value='$tyojono'>
      <input type='hidden' name='toim' value='$toim'>";

  echo t("N‰yt‰ kalenterit").":
      <div style='width:250px;height:275px;overflow:auto;'>
      <table width='100%'>
      <tr><td width='1%'><input type='checkbox' name='kalen[]' value = '$kukarow[kuka]' $checked onclick='submit()'></td><td>".t("Oma")."</td></tr>";

  $query = "SELECT distinct kuka.nimi, kuka.kuka
            FROM kuka, oikeu
            WHERE kuka.yhtio = '$kukarow[yhtio]'
            and oikeu.yhtio  = kuka.yhtio
            and oikeu.kuka   = kuka.kuka
            and oikeu.nimi   = 'crm/kalenteri.php'
            and kuka.tunnus  <> '$kukarow[tunnus]'
            ORDER BY kuka.nimi";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $checked = '';
    if (in_array("$row[kuka]", $ruksatut)) {
      $checked = 'checked';
    }
    echo "<tr><td nowrap><input type='checkbox' name='kalen[]' value='$row[kuka]' $checked onclick='submit()'></td><td>$row[nimi]</td></tr>";
  }

  echo "</table></div></form>";
}

echo "</td>";
echo "</tr>";

// N‰ytet‰‰n p‰iv‰n kalenteritapahtumat //
echo "<tr><td class='back ptop pnopad'>";
echo "<table width='100%' class='pnopad'>";

if (!empty($viikkonakyma)) {

  // Korjataan tiedot jos kuukausi/vuosi vaihtuu keskell‰ viikkoa
  $viikonekapaiva = weekday_number($paiva, $kuu, $year);

  if ($viikonekapaiva > 0) {
    list($year, $kuu, $paiva) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva-$viikonekapaiva, $year)));
  }

  echo "<tr>";

  for ($vpaiva = 0; $vpaiva < 7; $vpaiva++) {
    $vppaiva = $paiva+$vpaiva;
    $vpdate = date("j.n", mktime(0, 0, 0, $kuu, $vppaiva, $year));
    $url = js_openUrlNewWindow("{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&kenelle=".urlencode($kenelle)."&tee=SYOTA&year=$year&kuu=$kuu&paiva=$vppaiva&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays", substr($DAY_ARRAY[$vpaiva+1], 0, 2)." $vpdate", NULL, 550, 550);

    echo "<td style='text-align: center;' colspan='$max'>$url</td>";
  }

  echo "</tr>";

  piirra_kokopaivantapahtumat($kuu, $paiva, $year);

  echo "<tr>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, TRUE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";

  list($vy, $vm, $vd) = explode("-", date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva++, $year)));
  echo "<td class='back ptop pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($vy, $vm, $vd, FALSE, $aikavali), "</td>";
  echo "</tr>";
}
else {
  piirra_kokopaivantapahtumat($kuu, $paiva, $year);
  piirra_kalenteripaiva($year, $kuu, $paiva, TRUE, $aikavali);
}

echo "</table>";
echo "</td></tr>";

//kalenterivalinta end
echo "</table>";

// Tarvittavat funktiot
function piirra_kalenteripaiva($year, $kuu, $paiva, $aikasarake = TRUE, $aikavali) {
  global $MONTH_ARRAY, $AIKA_ARRAY, $DAY_ARRAY, $konsernit, $vertaa, $valitut, $kenelle, $konserni, $toim, $tyomaarays, $palvelin2, $lopetus,
        $lisays, $lyear, $lkuu, $lpaiva, $kukarow, $yhtiorow, $kons, $viikkonakyma, $maxkokopaivamaara, $asmemolinkki;

  $date  = '';
  $max   = 1;
  $paiva = sprintf("%02d", $paiva);
  $kuu   = sprintf("%02d", $kuu);

  list($whlopt, $whlopm) = explode(":", $AIKA_ARRAY[count($AIKA_ARRAY)-1]);

  $whlopm+=$aikavali;

  if ($aikavali == 15) {
    $whlopm = $whlopm + 30;
  }

  if ($whlopm >= 60) {
    $whlopt++;
    $whlopm = 0;
  }

  if ($whlopt >= 24) {
    $whileloppu = sprintf("%02d", $whlopt-24).":".$whlopm;
  }
  else {
    $whileloppu = sprintf("%02d", $whlopt).":".$whlopm;
  }

  if ($whlopt >= 23) {
    $vikaloppu = "23:59";
  }
  else {
    $vikaloppu = sprintf("%02d", $whlopt+1).":00";
  }

  $query = "SELECT selitetark, selitetark_2
            FROM avainsana
            WHERE laji = 'KALETAPA'
            ".str_ireplace("kalenteri.", "", $konsernit)."
            ORDER BY selite+0, laji, jarjestys, selite";
  $varires = pupe_query($query);

  $kalevarit = array();

  while ($varirow = mysql_fetch_assoc($varires)) {
    $kalevarit[$varirow["selitetark"]] = $varirow["selitetark_2"];
  }

  $tyyppi = $toim == 'TYOMAARAYS_ASENTAJA' ? "'kalenteri'" : "'kalenteri', 'asennuskalenteri', 'muistutus'";

  // kalenterin taulukko alkaa t‰st‰
  echo "<table class='pnopad' style='table-layout:fixed;' width='100%'>";

  $query = "SELECT kalenteri.asiakas, kalenteri.liitostunnus, kentta01, tapa, kuka.nimi, kalenteri.kuka, kalenteri.tunnus,
            if((pvmalku  < '$year-$kuu-$paiva ".$AIKA_ARRAY[0].":00' and pvmalku > '$year-$kuu-$paiva 00:00:00') or (pvmalku  < '$year-$kuu-$paiva 00:00:00' and pvmloppu > '$year-$kuu-$paiva 00:00:00'), '$year-$kuu-$paiva ".$AIKA_ARRAY[0].":00', pvmalku) pvmalku,
            if((pvmloppu > '$year-$kuu-$paiva $vikaloppu:00' and pvmloppu <= '$year-$kuu-$paiva 23:59:59') or (pvmloppu = '$year-$kuu-$paiva 00:00:00') or (pvmalku  < '$year-$kuu-$paiva 00:00:00' and pvmloppu > '$year-$kuu-$paiva 23:59:59'),  '$year-$kuu-$paiva $vikaloppu:00', pvmloppu) pvmloppu,
            TIME_TO_SEC(if((pvmloppu > '$year-$kuu-$paiva $vikaloppu:00') or (pvmloppu = '$year-$kuu-$paiva 00:00:00'),'$vikaloppu:59', right(pvmloppu,8))) - TIME_TO_SEC(if(right(pvmalku,8) < '".$AIKA_ARRAY[0].":00' or pvmalku < '$year-$kuu-$paiva 00:00:00','".$AIKA_ARRAY[0].":00', right(pvmalku,8))) kesto,
            kalenteri.yhtio yhtio,
            kalenteri.kuka kuka,
            kalenteri.laatija laatija,
            kalenteri.kuittaus,
            kalenteri.tyyppi,
            kentta03,
            kentta04,
            kentta05,
            kentta06
            FROM kalenteri
            LEFT JOIN kuka ON kalenteri.kuka = kuka.kuka and kalenteri.yhtio = kuka.yhtio
            WHERE kalenteri.kuka in ($vertaa)
            and kalenteri.tyyppi in ($tyyppi)
            and kokopaiva = ''
            $konsernit
            and pvmalku <= '$year-$kuu-$paiva 23:59:00'
            and pvmloppu > '$year-$kuu-$paiva 00:00:00'
            order by kesto desc, pvmalku";
  $result = pupe_query($query);

  $paivantapahtumat = array();

  while ($row = mysql_fetch_assoc($result)) {
    $row['paallekkaiset'] = 0;
    $paivantapahtumat[$row["pvmalku"]][] = $row;
  }

  $kello_nyt = '';
  list($whalkt, $whalkm) = explode(":", $AIKA_ARRAY[0]);
  $hh = $whalkt-1;
  $mm = $whalkm;
  $paallekkaiset = array();
  $vasenpad = array();
  $aikalask = 0;

  if ($aikavali == 15) {
    $mm = $mm + 30;
  }

  while ($kello_nyt != $whileloppu) {
    $hh        = date("H", mktime($hh, $mm+$aikavali, 0));
    $mm        = date("i", mktime($hh, $mm+$aikavali, 0));
    $kello_nyt = date("H:i", mktime($hh, $mm+$aikavali, 0));

    foreach ($paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"] as $ind => $row) {
      $kesto = round(($row['kesto']/60)/$aikavali);

      for ($i = $aikalask; $i < $aikalask+$kesto; $i++) {
        $paallekkaiset[$i]++;

        if ($i > $aikalask) {
          $vasenpad[$i]++;
        }
      }
    }

    $aikalask++;
  }

  $kello_nyt = '';
  list($whalkt, $whalkm) = explode(":", $AIKA_ARRAY[0]);
  $hh = $whalkt-1;
  $mm = $whalkm;
  $aikalask = 0;

  if ($aikavali == 15) {
    $mm = $mm + 30;
  }

  while ($kello_nyt != $whileloppu) {
    $hh        = date("H", mktime($hh, $mm+$aikavali, 0));
    $mm        = date("i", mktime($hh, $mm+$aikavali, 0));
    $kello_nyt = date("H:i", mktime($hh, $mm+$aikavali, 0));

    foreach ($paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"] as $ind => $row) {
      $kesto = round(($row['kesto']/60)/$aikavali); //kuinka monta solua t‰m‰ itemi kest‰‰

      // Suurin p‰‰llekk‰ism‰‰r‰ t‰n tapahtuman aikana?
      for ($i = $aikalask; $i < $aikalask+$kesto; $i++) {
        if ($paallekkaiset[$i] > $paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"][$ind]["paallekkaiset"]) {
          $paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"][$ind]["paallekkaiset"] = $paallekkaiset[$i];
        }
      }
    }

    $aikalask++;
  }

  $max = max($paallekkaiset);

  $kello_nyt = '';
  list($whalkt, $whalkm) = explode(":", $AIKA_ARRAY[0]);
  $hh = $whalkt-1;
  $mm = $whalkm;
  $aikalask = 0;

  if ($aikavali == 15) {
    $mm = $mm + 30;
  }

  while ($kello_nyt != $whileloppu) {
    $hh        = date("H", mktime($hh, $mm+$aikavali, 0));
    $mm        = date("i", mktime($hh, $mm+$aikavali, 0));
    $kello_nyt = date("H:i", mktime($hh, $mm+$aikavali, 0));

    // lasketaan montako p‰‰llekk‰ist‰ on t‰h‰n kellonaikaan
    $nyt    = $paallekkaiset[$aikalask];
    $tyhjaa = $max - $nyt;
    $tanaan = count($paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"]);

    echo "<tr>";

    if ($aikasarake) {
      echo "<td class='ptop kalepad' style='width: 35px; height: 32px;'>$kello_nyt</td>";
    }

    echo "<td class='ptop kalepad' style='position:relative; display:block; height: 32px;'>";

    $nyklask = 0;

    foreach($paivantapahtumat["$year-$kuu-$paiva $kello_nyt:00"] as $row) {

      $kesto = round(($row['kesto']/60)/$aikavali); //kuinka monta solua t‰m‰ itemi kest‰‰

      //haetaan asiakkaan tiedot
      if ($row["liitostunnus"] > 0 and $row['tyyppi'] == 'kalenteri') {
        $query = "SELECT *
                  from asiakas
                  where tunnus = '$row[liitostunnus]' ".str_ireplace("kalenteri.", "", $konsernit);
        $asres = pupe_query($query);
        $asiak = mysql_fetch_assoc($asres);
      }

      if ($kons == 1) {
        $ko = "(".$row["yhtio"]."), ";
      }

      if ($row['nimi'] != '') {
        list($enim, $snim) = explode(" ", $row['nimi']);
        $kukanimi  = $ko.$enim." ".substr($snim, 0, 1).".";
      }
      elseif ($row['kuka'] != '') {
        $kukanimi  = $ko.$row['kuka']." ";
      }
      else {
        $kukanimi = '';
      }

      // Vanhoja kalenteritapahtumia ei saa en‰‰ muuttaa
      list($rvv, $rkk, $rpp) = explode("-", substr($row["pvmloppu"], 0, 10));

      $kaleloppu  = (int) date('Ymd', mktime(0, 0, 0, $rkk, $rpp, $rvv));
      $aikanyt   = (int) date('Ymd', mktime(0, 0, 0, date('m'), date('d'), date('Y')));

      $varilisa = "";

      if ($row['tyyppi'] == 'kalenteri' and !empty($kalevarit[$row["tapa"]])) {
        $varilisa = "background-color: {$kalevarit[$row["tapa"]]};";
      }

      if ($kukarow["kuka"] == $row["kuka"]) {
        $reunavari = "#FF0000";

        if ($row["kuittaus"] != "") {
          $reunavari = "#00FF00";
        }
      }
      else {
        $reunavari = "#AAAAAA";
      }

      $korkeus = 34 * $kesto;

      if ($kesto > 1) {
        $korkeus += $kesto - 2;
      }
      else {
        $korkeus -= 1;
      }

      $divwidth = floor(100 / $row['paallekkaiset']) - 0.5;
      $left = floor(100 / $max) * ($nyklask + $vasenpad[$aikalask]);
      $nyklask++;

      if (($yhtiorow["kayttoliittyma"] == "U" and $kukarow["kayttoliittyma"] == "") or $kukarow["kayttoliittyma"] == "U") {
        $dbg = "#EEEEEE";
      }
      else {
        $dbg = "#555555";
      }

      echo "<div style='background-color: $dbg; border:1px solid $reunavari; -webkit-border-radius: 3px; border-radius: 3px;position: absolute; float: right; top: 0; left: {$left}%; height: {$korkeus}px; width:{$divwidth}%; display: block; overflow: hidden;'>
            <div style='padding: 3px;'>";

      // Vanhoja kalenteritapahtumia ei saa en‰‰ muuttaa ja Hyv‰ksyttyj‰ lomia ei saa ikin‰ muokata
      if (($kukarow["kuka"] == $row["kuka"] or $kukarow["kuka"] == $row["laatija"])) {
        $url = js_openUrlNewWindow("{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&kenelle=".urlencode($kenelle)."&tee=SYOTA&kello=$kello_nyt&year=$year&kuu=$kuu&paiva=$paiva&tunnus=$row[tunnus]&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus", "$row[tapa]");

        echo "$url";
      }
      elseif ($row['tyyppi'] == 'asennuskalenteri') {
        echo t("Asennustyˆ"), " $row[liitostunnus] ";
      }
      else {
        echo "$kukanimi $row[tapa] ";
      }

      if (in_array($row['tyyppi'], array('kalenteri','Muistutus')) and $row["liitostunnus"] != 0) {
        if ($asmemolinkki and $kukarow["yhtio"] == $row["yhtio"]) {
          echo " - <a href='asiakasmemo.php?ytunnus=$row[asiakas]&asiakasid=$row[liitostunnus]&lopetus=$lopetus'>$asiak[nimi]</a>";
        }
        else {
          echo " - $asiak[nimi]";
        }
      }

      echo " $row[kentta01]";

      $query = "SELECT *
                from liitetiedostot
                where yhtio      = '$kukarow[yhtio]'
                and liitos       = 'kalenterimerkint‰'
                and liitostunnus = '$row[tunnus]'";
      $liiteres = pupe_query($query);

      if (mysql_num_rows($liiteres) > 0) {
        echo "<br />";

        while ($liiterow = mysql_fetch_assoc($liiteres)) {
          echo "<a href=\"#\" onclick=\"window.open('".$palvelin2."view.php?id=$liiterow[tunnus]', '_blank' ,'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,left=200,top=100,width=800,height=600'); return false;\">$liiterow[filename]</a>&nbsp;";
        }
      }

      echo "</div></div>";
    }

    $aikalask++;

    $url = js_openUrlNewWindow("{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&kenelle=".urlencode($kenelle)."&tee=SYOTA&kello=$kello_nyt&year=$year&kuu=$kuu&paiva=$paiva&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus", "<div style='height:100%;width:100%'>&nbsp;</div>", "style='text-decoration: none;'", 550, 550);

    echo "$url</td></tr>";
  }

  //kalenterin table loppuu
  echo "</table>";
}

function piirra_kokopaivantapahtumat($kuu, $paiva, $year) {
  global $MONTH_ARRAY, $AIKA_ARRAY, $DAY_ARRAY, $konsernit, $vertaa, $valitut, $kenelle, $konserni, $toim, $tyomaarays, $palvelin2, $lopetus,
        $lisays, $lyear, $lkuu, $lpaiva, $kukarow, $yhtiorow, $kons, $viikkonakyma, $maxkokopaivamaara;

  if (!empty($viikkonakyma)) {
    $ekapaiva = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva, $year));
    $vikapaiva = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+7, $year));
    $paivat = 7;
  }
  else {
    $ekapaiva = $vikapaiva = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva, $year));
    $paivat = 1;
  }

  $query = "SELECT kalenteri.*,
            datediff(left(pvmloppu,10), if(left(pvmalku, 10) < '$ekapaiva', '$ekapaiva', pvmalku)) kesto,
            if (pvmalku < '$ekapaiva', '$ekapaiva', left(pvmalku, 10)) korjattualku,
            kuka.nimi
            FROM kalenteri
            JOIN kuka ON (kalenteri.kuka = kuka.kuka and kalenteri.yhtio = kuka.yhtio)
            WHERE kalenteri.kuka  in ($vertaa)
            and kalenteri.pvmloppu >= '$ekapaiva 00:00:00'
            and kalenteri.pvmalku <= '$vikapaiva 23:59:00'
            and ((kalenteri.tyyppi = 'kalenteri' and kalenteri.kokopaiva != ''))
            $konsernit
            order by korjattualku, kesto desc, tunnus";
  $result = pupe_query($query);

  $kokopaivat = array();

  while ($prow = mysql_fetch_assoc($result)) {
    $kokopaivat[$prow['korjattualku']][] = $prow;
  }

  // loopataan viikko l‰pi
  for ($vpaiva = 0; $vpaiva < $paivat; $vpaiva++) {
    $vpdate = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+$vpaiva, $year));
    $looppaiva = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+$vpaiva, $year));

    foreach ($kokopaivat[$looppaiva] as $indeksi => $kpe) {
      if ($kpe["kesto"]+$vpaiva > $paivat) {
        $kpe["kesto"] = $paivat - $vpaiva;
      }
      if ($kpe["kesto"] == 0) {
        $kpe["kesto"] = 1;
      }

      echo "<tr>";

      // tyhji‰ alkuun
      for ($tap = 0; $tap < $vpaiva; $tap++) {
        echo "<td class='back'></td>";
      }

      if ($kukarow["kuka"] == $kpe["kuka"]) {
        $reunavari = "#FF0000";

        if ($kpe["kuittaus"] != "") {
          $reunavari = "#00FF00";
        }
      }
      else {
        $reunavari = "#9FDCFF";
      }

      echo "<td colspan='$kpe[kesto]' style='border:1px solid $reunavari; -webkit-border-radius: 3px; border-radius: 3px;'>";

      $url = js_openUrlNewWindow("{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&kenelle=".urlencode($kenelle)."&tee=SYOTA&kello=$kello_nyt&year=$year&kuu=$kuu&paiva=$paiva&tunnus=$kpe[tunnus]&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus", "$kpe[kentta01]");

      echo "$url</td>";

      // tyhji‰ loppuun
      for ($tap = $kpe["kesto"]+$vpaiva; $tap < $paivat; $tap++) {
        $looppaiva2 = date("Y-m-d", mktime(0, 0, 0, $kuu, $paiva+$tap, $year));

        if (count($kokopaivat[$looppaiva2]) > 0) {
          $kala = array_shift($kokopaivat[$looppaiva2]);
          $kesto = ($tap+$kala["kesto"]) > $paivat ? $paivat - $tap : $kala["kesto"];

          if ($kukarow["kuka"] == $kala["kuka"]) {
            $reunavari = "#FF0000";

            if ($kala["kuittaus"] != "") {
              $reunavari = "#00FF00";
            }
          }
          else {
            $reunavari = "#9FDCFF";
          }

          echo "<td colspan='$kesto' style='border:1px solid $reunavari; -webkit-border-radius: 3px; border-radius: 3px;'>";

          $url = js_openUrlNewWindow("{$palvelin2}crm/kalenteri.php?valitut=".urlencode($valitut)."&kenelle=".urlencode($kenelle)."&tee=SYOTA&kello=$kello_nyt&year=$year&kuu=$kuu&paiva=$paiva&tunnus=$kala[tunnus]&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus", "$kala[kentta01]");

          echo "$url</td>";
          $tap += $kesto-1;
        }
        else {
          echo "<td class='back'></td>";
        }
      }

      echo "</tr>";
    }
  }
}

require "inc/footer.inc";
