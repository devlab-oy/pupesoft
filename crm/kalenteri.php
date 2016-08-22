<?php

require "../inc/parametrit.inc";

if (!isset($tee)) $tee = "";
if (!isset($tyojono)) $tyojono = "";

// otetaan oletukseksi t‰m‰ kuukausi, vuosi ja p‰iv‰
if ($paiva=='') $paiva = date("j");
if ($kuu=='')   $kuu   = date("n");
if ($year=='')  $year  = date("Y");

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

echo "<font class='head'>".t("Kalenteri")."</font><hr>";

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

    if ($lkello == "00:00") {
      $lkello = "23:59:59";
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
              kentta01     = '$viesti',";

    if ($toim == 'TYOMAARAYS_ASENTAJA' or $tyomaarays != '') {
      $query .= "kentta02  = '$tyomaarays',";
    }

    $query .= "  kentta03    = '$kilometrit',
          kentta04    = '$paivarahat',
          tapa     = '$tapa',
          tyyppi     = 'kalenteri'";
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
}

// tehd‰‰n lis‰ys ruutu ja laitetaan kaikki muuttujaan jotta voidaan echota sit oikeessa kohdassa
if ($tee == "SYOTA") {
  if ($tunnus != '') {
    $kukalisa = $toim == 'TYOMAARAYS_ASENTAJA' ? " AND kuka = '$kukarow[kuka]' " : '';

    $query = "SELECT *,
              if(asiakas='0','',asiakas) asiakas,
              if(liitostunnus=0,'',liitostunnus) liitostunnus,
              Year(pvmalku) lyear,
              Month(pvmalku) lkuu,
              Day(pvmalku) lpaiva,
              right(pvmalku,8) lkello,
              right(pvmloppu,8) lopkello
              FROM kalenteri
              WHERE tunnus = '$tunnus'
              $konsernit
              $kukalisa
              and tyyppi   = 'kalenteri'";
    $res  = pupe_query($query);
    $irow = mysql_fetch_assoc($res);

    $viesti   = $irow["kentta01"];
    $kilometrit  = $irow["kentta03"];
    $paivarahat = $irow["kentta04"];
    $tapa     = $irow["tapa"];
    $ytunnus   = $irow["asiakas"];
    $asiakasid   = $irow["liitostunnus"];
    $lkello   = $irow["lkello"];
    $lyear     = $irow["lyear"];
    $lkuu     = $irow["lkuu"];
    $lpaiva   = $irow["lpaiva"];
    $asyhtio   = $irow["yhtio"];
    $kenelle   = $irow["kuka"];

    if ($toim == 'TYOMAARAYS_ASENTAJA') {
      $tyomaarays = $irow["kentta02"];
    }

    if ($irow["lopkello"] == "23:59:59") $aikaloppu = "00:00:00";
    else $aikaloppu = $irow["lopkello"];

  }
  else {
    $lyear   = $year;
    $lkuu   = $kuu;
    $lpaiva = $paiva;
  }

  $lisayskello = $kello;

  $lisays =  "
    <td colspan='10'><form method='POST' enctype='multipart/form-data'>
    <input type='hidden' name='tee' value='LISAA'>
    <input type='hidden' name='lopetus' value='$lopetus'>
    <input type='hidden' name='valitut' value='".urlencode($valitut)."'>
    <input type='hidden' name='kenelle' value='".urlencode($kenelle)."'>
    <input type='hidden' name='asyhtio' value='$asyhtio'>
    <input type='hidden' name='kello' value='$kello'>
    <input type='hidden' name='year'  value='$year'>
    <input type='hidden' name='kuu'   value='$kuu'>
    <input type='hidden' name='paiva' value='$paiva'>
    <input type='hidden' name='tunnus'   value='$tunnus'>
    <input type='hidden' name='konserni' value='$konserni'>
    <input type='hidden' name='asiakasid' value='$asiakasid'>
    <input type='hidden' name='tyomaarays' value='$tyomaarays'>
    <input type='hidden' name='toim' value='$toim'>

  <table width='100%'>";

  $lisays .= "<tr><th colspan='2'>".t("Lis‰‰ tapahtuma").":</th></tr>";

  $lisays .= "<tr>
    <td nowrap>".t("Kesto").":</td>
    <td>$kello -
    <input type='text' size='3' name='lpaiva' value='$lpaiva'>
    <input type='text' size='3' name='lkuu'   value='$lkuu'>
    <input type='text' size='5' name='lyear'  value='$lyear'>
    <select name='lkello'>";

  if ($lkello == '') {
    $lophh=substr($kello, 0, 2);
    $lopmm=substr($kello, 3, 2)-30;
  }
  else {

    if ($lkello == "23:59:59") {
      $lkello = "24:00:00";
    }

    $lophh=substr($lkello, 0, 2);
    $lopmm=substr($lkello, 3, 2)-30;
  }

  list($whlopt, $whlopm) = explode(":", $AIKA_ARRAY[count($AIKA_ARRAY)-1]);
  $whileloppu = sprintf("%02d", $whlopt+1);

  if ($whileloppu >= 24) $whileloppu= sprintf("%02d", $whileloppu-24);

  $whileloppu = $whileloppu.":".$whlopm;
  $lopdate = "";

  while ($lopdate != $whileloppu) {
    $lophh   = date("H", mktime($lophh, $lopmm+30, 0));
    $lopmm   = date("i", mktime($lophh, $lopmm+30, 0));
    $lopdate = date("H:i", mktime($lophh, $lopmm+30, 0));

    $sel = '';
    if ($lopdate == substr($aikaloppu, 0, 5)) {
      $sel = "SELECTED";
    }

    $lisays .= "<option value='$lopdate' $sel>$lopdate</option>";
  }

  $lisays .= "</select></td>";

  $vresult = t_avainsana("KALETAPA");

  $lisays .= "<tr><td>".t("Tapa").":</td><td><select name='tapa'>";

  while ($vrow=mysql_fetch_assoc($vresult)) {
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

  $lisays .= "<tr><td><input type='submit' value='".t("Lis‰‰")."'></td></form>
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
}

//Paivan tapahtumat
//t‰st‰ alkaa main table
echo "<table class='pnopad'>";
echo "<tr>";

if ($toim != 'TYOMAARAYS_ASENTAJA') {

  echo "<td class='back ptop pnopad' nowrap>";

  //listataan paivan muistutukset
  $query = "SELECT kalenteri.tunnus tunnus, left(pvmalku,10) Muistutukset, asiakas.nimi Asiakas, yhteyshenkilo.nimi Yhteyshenkilo, kalenteri.kentta01 Kommentit, kalenteri.tapa Tapa, kuka.nimi Nimi, kalenteri.yhtio
            FROM kalenteri
            LEFT JOIN kuka ON kuka.yhtio=kalenteri.yhtio and kuka.kuka=kalenteri.kuka
            LEFT JOIN yhteyshenkilo ON kalenteri.henkilo=yhteyshenkilo.tunnus and yhteyshenkilo.yhtio=kalenteri.yhtio and yhteyshenkilo.tyyppi = 'A'
            LEFT JOIN asiakas ON asiakas.tunnus=kalenteri.liitostunnus and asiakas.yhtio=kalenteri.yhtio
            WHERE kalenteri.kuka in ($vertaa)
            and kalenteri.tyyppi='Muistutus'
            and kalenteri.kuittaus='K'
            $konsernit
            ORDER BY kalenteri.pvmalku desc";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "<table width='100%'>";
    echo "<tr>";
    echo "<th colspan='6'>".t("Muistutukset")."</th>";
    echo "</tr>";


    while ($prow = mysql_fetch_assoc($result)) {
      echo "  <form action='kuittaamattomat.php?tee=A&kaletunnus=$prow[tunnus]&kuka=$prow[kuka]' method='post'>
              <input type='hidden' name='lopetus' value='$lopetus'>
              <tr>";

      echo "<td nowrap>$prow[Muistutukset]</td>";
      echo "<td nowrap>$prow[Asiakas]</td>";
      echo "<td nowrap>$prow[Yhteyshenkilo]</td>";
      echo "<td nowrap>$prow[Kommentit]</td>";
      echo "<td nowrap>$prow[Tapa]</td>";

      $ko = "";

      if ($kons == 1) {
        $ko = "(".$prow["yhtio"]."), ";
      }

      echo "<td nowrap>$ko $prow[Nimi]</td>";

      echo "<td class='back'><input type='submit' value='".t("Kuittaa")."'></td>";
      echo "</tr></form>";
    }
    echo "</table>";
  }
  echo "</td>";
}
else {
  echo "<td class='back pnopad'>&nbsp;</td>";
}

//oikean yl‰laidan pikkukalenteri..
echo "<td class='back ptop pnopad' rowspan='3' align='left'>";

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
echo "<tr><th>".date("W", mktime(0, 0, 0, $kuu, 1, $year))."</th>";

// kirjotetaan alkuun tyhji‰ soluja
for ($i=0; $i < weekday_number("1", $kuu, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

// kirjoitetaan p‰iv‰m‰‰r‰t taulukkoon..
for ($i=1; $i <= days_in_month($kuu, $year); $i++) {
  $font="";
  $class="";
  $style="";

  $paiv = sprintf("%02d", $i);
  $kuu2 = sprintf("%02d", $kuu);

  $query = "SELECT tunnus
            from kalenteri
            where
            ((left(pvmalku,10) = '$year-$kuu2-$paiv') or (left(pvmalku,10) < '$year-$kuu2-$paiv' and left(pvmloppu,10) >= '$year-$kuu2-$paiv'))
            and kuka   in ($vertaa)
            $konsernit
            and tyyppi = 'kalenteri'";
  $result = pupe_query($query);

  //v‰ritet‰‰n t‰m‰n p‰iv‰n pvm omalla v‰rill‰...
  if ((date("j")==$i) and (date("n")==$kuu) and (date("Y")==$year)) {
    $fn1 = "<font class='message'>";
    $fn2 = "</font>";
  }
  else {
    $fn1 = "";
    $fn2 = "";
  }

  //jos on valittu joku tietty p‰iv‰, tehd‰‰n solusta tumma
  if ($paiva == $i) {
    $style="border:1px solid #FF0000;";
  }

  if (mysql_num_rows($result) != 0) {
    $class = "tumma";
  }

  echo "<td align='center' style='$style' class='$class'><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$year&kuu=$kuu&paiva=$i&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>$fn1 $i $fn2</a></td>";

  // tehd‰‰n rivinvaihto jos kyseess‰ on sunnuntai ja seuraava p‰iv‰ on olemassa..
  if ((weekday_number($i, $kuu, $year)==6) and (days_in_month($kuu, $year)>$i)) {
    $weeknro=date("W", mktime(0, 0, 0, $kuu, $i+1, $year));
    echo "</tr><tr><th>$weeknro</th>";
  }
}

//kirjoitetaan loppuun tyhji‰ soluja
for ($i=0; $i<6 - weekday_number(days_in_month($kuu, $year), $kuu, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

echo "</tr>";

echo "<tr><td class='back' align='center' colspan='8'><a href='$PHP_SELF?valitut=".urlencode($valitut)."&kuu=$backmonth&year=$backyear&paiva=1&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>".t("Edellinen")."</a>  - <a href='$PHP_SELF?valitut=".urlencode($valitut)."&kuu=$nextmonth&year=$nextyear&paiva=1&konserni=$konserni&tyomaarays=$tyomaarays&toim=$toim&lopetus=$lopetus'>".t("Seuraava")."</a><br><br></td></tr>";
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

//listataan whole-day eventit
echo "<tr>";
echo "<td class='back ptop pnopad' nowrap>";

$query = "SELECT kalenteri.asiakas, kalenteri.liitostunnus, kentta01, tapa, kuka.nimi, kalenteri.tunnus, pvmalku, pvmloppu, kalenteri.yhtio
          FROM kalenteri, kuka
          WHERE kalenteri.kuka  in ($vertaa)
          and kalenteri.kuka    = kuka.kuka
          and kalenteri.yhtio   = kuka.yhtio
          and kalenteri.tyyppi= 'kalenteri'
          and pvmalku           >= '$year-$kuu-$paiva 00:00:00'
          and pvmalku           <= '$year-$kuu-$paiva 23:59:00'
          and kokopaiva        != ''
          $konsernit
          order by pvmalku";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  echo "<table width='100%'>";
  echo "<tr>";
  echo "<th colspan='4'>".t("Kokop‰iv‰n tapahtumat")."</th>";
  echo "</tr>";

  while ($prow = mysql_fetch_assoc($result)) {

    //haetaan asiakkaan tiedot
    $query = "SELECT *
              from asiakas
              where yhtio = '$prow[yhtio]'
              and tunnus  = '$prow[liitostunnus]'";
    $asres = pupe_query($query);
    $asiak = mysql_fetch_assoc($asres);

    echo "<tr>";
    echo "<td>$prow[tapa]</td>";

    if ($asmemolinkki and $kukarow["yhtio"] == $prow["yhtio"]) {
      echo "<td><a href='asiakasmemo.php?ytunnus=$prow[asiakas]&asiakasid=$prow[liitostunnus]'>$asiak[nimi]</a></td>";
    }
    else {
      echo "<td>$asiak[nimi]</td>";
    }

    echo "<td>$prow[kentta01]</td>";

    $ko = "";
    if ($kons == 1) {
      $ko = "(".$prow["yhtio"]."), ";
    }

    echo "<td>$ko $prow[nimi]</td>";
    echo "</tr>";
  }

  echo "</table><br>";
}


echo "</td></tr>";

echo "<tr>";
echo "<td class='back ptop' nowrap width='100%'>";

echo "<table width='100%'>
    <tr>
      <th nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$edelyear&kuu=$edelmonth&paiva=$edelday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'><< ".t("Edellinen")."</a></th>
      <th style='text-align:center' nowrap>$paiva. ".$MONTH_ARRAY[(int) $kuu]." $year</th>
      <th style='text-align:right' nowrap><a href='$PHP_SELF?valitut=".urlencode($valitut)."&year=$seuryear&kuu=$seurmonth&paiva=$seurday&konserni=$konserni&toim=$toim&tyomaarays=$tyomaarays&lopetus=$lopetus'>".t("Seuraava")." >></a></th>
    </tr>
    </table>";

// N‰ytet‰‰n p‰iv‰n kalenteritapahtumat //
echo  "<table width='100%' class='pnopad'><tr>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 22), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 23, FALSE), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 24, FALSE), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 25, FALSE), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 26, FALSE), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 27, FALSE), "</td>";
echo "<td class='back pnopad' style='width: 14%; min-width: 100px;'>",piirra_kalenteripaiva($year, $kuu, 28, FALSE), "</td>";
echo "</tr></table>";

//main tablen oikea yl‰laita
echo "</td>";
echo "</tr>";

//kalenterivalinta end
echo "</table>";

require "inc/footer.inc";
