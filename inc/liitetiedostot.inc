<?php

$otsikko     = 'Liitetiedostot';
$otsikko_nappi   = "liite";

// HUOM HUOM: eth‰n muuta n‰it‰ ilman, ett‰ korjaat myˆs yllapito.php:ss‰ iframen lukitse_avaimeen muuttujat
$kentat = "tunnus, filetype liitedata, concat('<a href=\'view.php?id=', tunnus,'\' target=\'Attachment\'>','".t("N‰yt‰ liite")."','</a>') liite, selite, filename nimi, filesize koko, kieli, (SELECT selitetark from avainsana where avainsana.yhtio=liitetiedostot.yhtio and avainsana.laji like '%litety' and avainsana.selite=liitetiedostot.kayttotarkoitus LIMIT 1) Tyyppi, liitos liitos_hidden, liitostunnus liitostunnus_hidden";
$hakukentat = "tunnus, filetype, filetype, selite, filesize, kieli, kayttotarkoitus, liitos, liitostunnus";

$jarjestys = "liitos, liitostunnus, jarjestys, tunnus";

//  Oletuksena emme salli mit‰‰n!
$uusilukko   = "ON";
$poistolukko = "ON";

if ($lukitse_laji == "") {
  // Ei n‰ytet‰ mit‰‰n
  $rajauslisa = " and liitetiedostot.tunnus = -1 ";
}

if (($lukitse_laji == "tuote" or $lukitse_laji == "sarjanumeron_lisatiedot" or $lukitse_laji == 'asiakas' or $lukitse_laji == "avainsana" or $lukitse_laji == 'toimi' or $lukitse_laji == 'sarjanumeroseuranta') and $lukitse_avaimeen > 0) {
  $uusilukko = "";
  $poistolukko   = "";
}

if ($lukitse_laji == "lasku" and $lukitse_avaimeen > 0) {

  $query = "SELECT * from lasku where tunnus='$lukitse_avaimeen' and yhtio='$kukarow[yhtio]'";
  $lasres = pupe_query($query);
  $lasrow = mysql_fetch_array($lasres);

  $laskutyyppi = $lasrow["tila"];
  $alatila   = $lasrow["alatila"];

  //tehd‰‰n selv‰kielinen tila/alatila
  require "inc/laskutyyppi.inc";

  $otsikko_lisatiedot =  "
    <table>
    <tr><th>".t('Tila')."</th><td>".t("$laskutyyppi")." ".t("$alatila")."</td></tr>
    <tr><th>".t('Nimi')."</th><td>$lasrow[nimi]</td></tr>
    <tr><th>".t('Nimitark')."</th><td>$lasrow[nimitark]</td></tr>
    <tr><th>".t('Osoite')."</th><td>$lasrow[osoite]</td></tr>
    <tr><th>".t('Postitp')."</th><td>$lasrow[postitp]</td></tr>
    </table><br>";

  /*  Tarkastetaan k‰ytt‰j‰oikeuksia hieman eri tavalla eri laskuilla  */
  //  Ostoreskontran laskut
  if (in_array($lasrow['tila'], array('H', 'Y', 'M', 'P', 'Q', 'X'))) {
    $query = "SELECT * from oikeu where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]' and nimi LIKE '%ulask.php'";
    $res = pupe_query($query);

    if (mysql_num_rows($res) > 0) {
      $uusilukko = "";
    }
  }
  //  N‰m‰ ovat varmaankin sitten itse tilauksia?
  elseif (in_array($lasrow['tila'], array("L", "N", "R", "E", "T", "U", "0", "A", "O", "V", "C"))) {
    $uusilukko = "";
  }

  if ($kukarow["taso"] == "3" or !in_array($lasrow['tila'], array('Y', 'M', 'P', 'Q')) or ($lasrow['tila'] == "M" and $lasrow['h1time'] == '0000-00-00 00:00:00' and $lasrow['h2time'] == '0000-00-00 00:00:00' and $lasrow['h3time'] == '0000-00-00 00:00:00' and $lasrow['h4time'] == '0000-00-00 00:00:00' and $lasrow['h5time'] == '0000-00-00 00:00:00')) {
    $poistolukko = "";
  }

  if (laskun_hyvaksyjia() and ($del == 1 or ($tunnus != "" and $_FILES["liite_data"]["size"] > 0))) {
    if (in_array($lasrow['tila'], array('H', 'M')) and $kukarow["taso"] != 3) {

      $komm = "(" . $kukarow['nimi'] . "@" . date('Y-m-d') .") ".t("Lasku palautettiin hyv‰ksynt‰‰n koska laskun liitteit‰ muutettiin.")."<br>";

      // nollataan hyv‰ksyj‰t jos jokin n‰ist‰ tiloista
      $query = "UPDATE lasku
                SET h1time     = '',
                h2time        = '',
                h3time        = '',
                h4time        = '',
                h5time        = '',
                hyvaksyja_nyt = hyvak1,
                tila          = if(tila='M', 'H', tila),
                comments      = trim(concat('$komm', comments))
                WHERE tunnus  = $lasrow[tunnus]
                and yhtio     = '$kukarow[yhtio]'
                and tila      in ('H','M')";
      $ares = pupe_query($query);
    }
  }
}
