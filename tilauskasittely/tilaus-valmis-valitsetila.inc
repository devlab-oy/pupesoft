<?php
// Tarvitsemme
// $laskurow jossa on laskun tiedot
// $kutsuja jossa on skripti josta kutsutaan

$kerattavia   = 0;
$ei_kerattavia   = 0;
$tilaus_valmis_message = "";

// Pamautetaan toimitusvahvistus asiakkaalle jos menn‰‰n haaraan jossa ei toimiteta mit‰‰n
$toimitusvahvitus_laheta = FALSE;

// etuk‰teen maksettu lasku, pit‰‰ huomioida myˆs kpl
if (($laskurow["tila"] == "L" or $laskurow["tila"] == "N") and $laskurow["mapvm"] != '0000-00-00' and $laskurow["mapvm"] != '') {
  $lis_plus_kpl = " + tilausrivi.kpl";
}
else {
  $lis_plus_kpl = "";
}

if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu $lis_plus_kpl";
}
else {
  $lisavarattu = "";
}

$rajattavat_tuotteet = "'{$yhtiorow['rahti_tuotenumero']}'";
$rajattavat_tuotteet = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rajattavat_tuotteet);

$query = "SELECT count(*) riveja,
          sum(if (tilausrivi.var = 'P' and tilausrivi.tilkpl $lis_plus_kpl > 0, 1, 0)) puutteet,
          sum(if (tilausrivi.var = 'J' and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet,
          sum(if (tilausrivi.var not in ('P','J','O','S') and tilausrivi.varattu $lis_plus_kpl < 0, 1, 0)) hyvitykset,
          sum(if (tilausrivi.var not in ('P','J','O','S') and (('$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa = '') or '$yhtiorow[kerataanko_saldottomat]' = 'S') and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) normaalit_kerataan,
          sum(if (tilausrivi.var not in ('P','J','O','S') and '$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa != '' and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) saldottomat,
          sum(if (tilausrivi.var not in ('P','J','O','S') and '{$yhtiorow['saldottomat_rahtikirjansyottoon']}' != '' and tuote.ei_saldoa != '' and tuote.tuotetyyppi = '' and tilausrivi.varattu {$lis_plus_kpl} > 0, 1, 0)) saldottomat_rahtikirjansyottoon,
          sum(if (tilausrivi.toimitettu != '' and tuote.ei_saldoa = '' and (tilausrivi.varattu $lis_plus_kpl > 0 and tilausrivi.var not in ('P','J','O','S')), 1, 0)) toimitetut
          FROM tilausrivi
          LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
          and tilausrivi.otunnus  = '$laskurow[tunnus]'
          and tilausrivi.tyyppi   = 'L'
          and tilausrivi.tuoteno NOT IN ({$rajattavat_tuotteet})";
$tresult = pupe_query($query);
$rivirow = mysql_fetch_array($tresult);

// Ker‰tt‰v‰t ja ei ker‰tt‰v‰ rivit
$kerattavia = $rivirow["normaalit_kerataan"];
$ei_kerattavia = $rivirow["saldottomat"] + $rivirow["hyvitykset"];

// Toimitettuja rivej‰ ei ker‰t‰ ikin‰
$ei_kerattavia += $rivirow["toimitetut"];
$kerattavia -= $rivirow["toimitetut"];

/*
puute_jt_kerataanko
''  Puuterivej‰ ja JT-rivej‰ EI ker‰t‰
'P' Puuterivit ker‰t‰‰n
'J' JT-rivit ker‰t‰‰n
'Q' Puuterivit ja JT-rivit ker‰t‰‰n

kerataanko_jos_vain_puute_jt
''  Tilausta EI ker‰t‰ jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰
'P' Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰
'J' Tilaus ker‰t‰‰n jos listalla on vain JT-rivej‰
'Q' Tilaus ker‰t‰‰n jos listalla on vain Puuterivej‰ JA/TAI JT-rivej‰
*/

// ei lasketa ker‰tt‰vi‰/eiker‰tt‰vi‰ m‰‰ri‰ jos ollaan ker‰‰.phpssa, koska silloin kaikki on varmasti ker‰tt‰vi‰ jos ne siell‰ n‰ky
if (($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtio haluaa ker‰t‰ Puuterivit
  $kerattavia += $rivirow["puutteet"];
}
else {
  $ei_kerattavia += $rivirow["puutteet"];
}

if (($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtiˆ haluaa ker‰t‰ JT-rivit
  $kerattavia += $rivirow["jteet"];
}
else {
  $ei_kerattavia += $rivirow["jteet"];
}

// jos yhtiˆ haluaa ker‰t‰ puuterivej‰ niin katotaan onko meill‰ pelk‰st‰‰n tilauksella jt/puuterivej‰ (HB tilaukset ker‰t‰‰n silti!)
if ($yhtiorow["puute_jt_kerataanko"] != "" and $kutsuja != 'keraa.php') {
  if ($rivirow["puutteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and (($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" and $laskurow["alatila"] != 'FF') or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "J")) {
    // Meill‰ on pelk‰st‰‰n puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
    $ei_kerattavia += $rivirow["puutteet"];
    $kerattavia     -= $rivirow["puutteet"];
  }
  elseif ($rivirow["jteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "P")) {
    // Meill‰ on pelk‰st‰‰n JT-rivej‰ ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
    $ei_kerattavia += $rivirow["jteet"];
    $kerattavia     -= $rivirow["jteet"];
  }
  elseif ($rivirow["jteet"] + $rivirow["puutteet"] == $kerattavia and $yhtiorow["puute_jt_kerataanko"] == "Q" and $yhtiorow["kerataanko_jos_vain_puute_jt"] == "") {
    // Meill‰ on pelk‰st‰‰n JT-rivej‰ + puutteita ker‰tt‰v‰n‰ ja niit‰ ei t‰ss‰ tapauksessa ker‰t‰
    $ei_kerattavia += $rivirow["jteet"] + $rivirow["puutteet"];
    $kerattavia     -= $rivirow["jteet"] + $rivirow["puutteet"];
  }
}

$tilaus_toimitetuksi = false;

//Siirtolistat ker‰t‰‰n ker‰yserien kautta ja siihen linkattu myyntitilaus halutaan laittaa toimitetuksi kun siirtolista on vastaanotettu kohdevarastossa
if ($yhtiorow['kerayserat'] == 'K' and $yhtiorow['siirtolistan_tulostustapa'] == 'U' and $laskurow['tila'] == 'N' and $laskurow['alatila'] == '') {
  $query = "SELECT *
            FROM tilausrivi AS t
            JOIN tilausrivin_lisatiedot AS tt
            ON ( tt.yhtio = t.yhtio
              AND tt.tilausrivilinkki = t.tunnus )
            JOIN tilausrivi AS t2
            ON ( t2.yhtio = tt.yhtio
              AND t2.tunnus           = tt.tilausrivitunnus
              AND t2.tyyppi           = 'G' )
            WHERE t.yhtio             = '{$kukarow['yhtio']}'
            AND t.otunnus             = {$laskurow['tunnus']}
            AND t.tyyppi              = 'L'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tilaus_toimitetuksi = true;
  }
}

/*
echo "puute_jt_kerataanko: $yhtiorow[puute_jt_kerataanko]<br>";
echo "kerataanko_jos_vain_puute_jt: $yhtiorow[kerataanko_jos_vain_puute_jt]<br>";
echo "R: $rivirow[riveja]<br>";
echo "P: $rivirow[puutteet]<br>";
echo "J: $rivirow[jteet]<br>";
echo "H: $rivirow[hyvitykset]<br>";
echo "N: $rivirow[normaalit_kerataan]<br>";
echo "S: $rivirow[saldottomat]<br>";
echo "T: $rivirow[toimitetut]<br>";
echo "KER: $kerattavia<br>";
echo "EIKER: $ei_kerattavia<br>";
// */

// Tutkitaan mit‰ tehd‰‰n tilaukselle
if ($rivirow["riveja"] == 0 or ($kerattavia == 0 and $ei_kerattavia == 0 and $kutsuja == 'keraa.php')) {
  // Tilauksella ei ole mit‰‰n laskutettavaa eik‰ ker‰tt‰v‰‰
  $query  = "UPDATE tilausrivi
             SET tyyppi='D'
             WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (1)")."<br>";

  $query  = "UPDATE lasku
             SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s mit‰tˆitiin koska tilauksella ei ollut yht‰‰n ker‰tt‰v‰‰/laskutettavaa rivi‰."), $laskurow["tunnus"])."</font><br><br>\n";
  $toimitusvahvitus_laheta = TRUE;
}
elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
  // Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
  $query  = "UPDATE lasku
             SET tila='N', alatila='U'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivej‰ ja osatoimitusta ei tehd‰, eli se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
}
elseif ($kerattavia == 0 or $tilaus_toimitetuksi) {
  // Rahtikulurivien rajaus
  $rajattavat_tuotteet = "'{$yhtiorow['rahti_tuotenumero']}'";
  $rajattavat_tuotteet = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rajattavat_tuotteet);
  // Tilauskella on pelk‰st‰‰n puuterivej‰ joita ei ker‰t‰
  // merkataan otsikko mit‰tˆidyksi, mutta j‰tet‰‰n PUUTE rivit analyysej‰ varten...
  if ($rivirow["riveja"] == $rivirow["puutteet"]) {
    // Jos tilauksella oli vain puuterivej‰ niin poistetaan rahtikulurivi jos sellanen on
    $query  = "DELETE FROM tilausrivi
               WHERE otunnus = '$laskurow[tunnus]'
               AND yhtio     = '$kukarow[yhtio]'
               AND tuoteno IN ({$rajattavat_tuotteet})";
    $result = pupe_query($query);

    $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit‰tˆi ohjelmassa tilaus-valmis-valitsetila.php (2)")."<br>";

    $query  = "UPDATE lasku
               SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s  oli pelk‰st‰‰n Puuterivej‰. Otsikko mit‰tˆitiin."), $laskurow["tunnus"])."</font><br><br>\n";
    $toimitusvahvitus_laheta = TRUE;
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"]) {

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno IN ({$rajattavat_tuotteet})";
      $result = pupe_query($query);
    }

    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus='$laskurow[tunnus]'
               AND yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"] + $rivirow["puutteet"]) {
    // Tilauskella on pelk‰st‰‰n jt-rivej‰ ja puutteita joita ei ker‰t‰

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt/puute-rivej‰ niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno IN ({$rajattavat_tuotteet})";
      $result = pupe_query($query);
    }

    // P‰ivitet‰‰n tilaus tilaan 'odottaa jt-rivej‰'
    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelk‰st‰‰n JT-rivej‰ ja Puuterivej‰ ja se j‰tettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($rivirow["riveja"] == $rivirow["saldottomat_rahtikirjansyottoon"]) {
    // merkataan rivit ker‰tyiksi, mutta ei toimitetuiksi ja tilaukselle alatila "ker‰tty"
    $query  = "UPDATE tilausrivi SET
               kerattyaika = now(),
               keratty = '{$kukarow['kuka']}'
               WHERE otunnus   = '{$laskurow['tunnus']}'
               and yhtio       = '{$kukarow['yhtio']}'
               and var         NOT IN ('P','J','O','S')
               and keratty     = ''
               and toimitettu  = ''
               and tyyppi     != 'D'";
    $result = pupe_query($query);

    $query  = "UPDATE lasku SET
               tila         = 'L',
               alatila      = 'C',
               lahetepvm    = now()
               WHERE tunnus = '{$laskurow['tunnus']}'
               AND yhtio    = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰. Tilaus siirretty rahtikirjansyˆttˆˆn."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($tilaus_toimitetuksi or $ei_kerattavia > 0) {
    if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
      // jos on laskuettavia rivej‰ ja jos kyseess‰ on k‰teist‰, itsetulostus tai vienti‰
      // merkataan rivit ker‰tyiksi, mutta ei toimitetuiksi ja laskulle alatila alatila C/B
      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty     = ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      // Jos on vienti‰ ja vain saldottomia niin menn‰‰n suoraan toimitetuksi
      if ($laskurow["vienti"] != '' and $rivirow["riveja"] == $rivirow["saldottomat"]) {

        // Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
        if ($laskurow['jaksotettu'] != 0) {
          $alat = "J";
        }
        else {
          $alat = "D";
        }

        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";

        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);
      }
      elseif ($laskurow["vienti"] != '') {
        $alat = "B";
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi. Viennin lis‰tiedot syˆtett‰v‰."), $laskurow["tunnus"])."</font><br><br>\n";

        $query  = "UPDATE tilausrivi
                   SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                   WHERE otunnus   = '$laskurow[tunnus]'
                   and yhtio       = '$kukarow[yhtio]'
                   and var         not in ('P','J','O','S')
                   and keratty    != ''
                   and toimitettu  = ''
                   and tyyppi     != 'D'";
        $result = pupe_query($query);
      }
      else {
        $alat = "C";
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin ker‰tyksi."), $laskurow["tunnus"])."</font><br><br>\n";
      }

      $query  = "UPDATE lasku SET
                 tila         = 'L',
                 alatila      = '$alat',
                 lahetepvm    = now()
                 WHERE tunnus = '$laskurow[tunnus]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);
    }
    else {
      // Jos on laskuettavia rivej‰ ja jos kyseess‰ ei ole k‰teinen, merkataan tilaukset ker‰tyksi ja toimitetuksi ja alatila 'D'
      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus  = '$laskurow[tunnus]'
                 and yhtio      = '$kukarow[yhtio]'
                 and var        not in ('P','J','O','S')
                 and keratty    = ''
                 and toimitettu = ''";
      $result = pupe_query($query);

      $query  = "UPDATE tilausrivi
                 SET toimitettuaika = now(), toimitettu = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty    != ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      // Jos meill‰ on maksupositioita laskulla, tulee se siirt‰‰ alatilaan J
      if ($laskurow['jaksotettu'] != 0) {
        $tilaus_valmis_alataila = 'J';
      }
      else {
        $tilaus_valmis_alataila = 'D';
      }

      $query  = "UPDATE lasku SET
                 tila         = 'L',
                 alatila      = '$tilaus_valmis_alataila',
                 lahetepvm    = now()
                 WHERE tunnus = '$laskurow[tunnus]'
                 AND yhtio    = '$kukarow[yhtio]'";
      $result = pupe_query($query);

      $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yht‰‰n ker‰tt‰v‰‰ rivi‰, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";
      $toimitusvahvitus_laheta = TRUE;
    }
  }
  else {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (1)</font><br><br>\n";
  }
}
elseif ($kerattavia > 0 and $kutsuja != 'keraa.php' and !$tilaus_toimitetuksi) {
  // T‰ss‰ meill‰ ON jotain ker‰tt‰v‰‰
  // laitetaan l‰hete tulostusjonoon
  $tila = 'N';
  $alatila = 'A';

  if ($yhtiorow['lahetteen_tulostustapa'] == 'I') {
    $tila = 'L';
    $alatila = 'D';
  }

  // Jos yhtiˆn parametreiss‰ on ker‰yser‰t A tarkistetaan asiakkaan takaa
  if ($yhtiorow['kerayserat'] == 'A') {
    // Haetaan kerayseratieto asiakkaan takaa
    $query = "SELECT kerayserat
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$laskurow['liitostunnus']}'";
    $asrow = mysql_fetch_assoc(pupe_query($query));
  }

  // Poistetaan vanhat ker‰yser‰t kun palautetaan tilaus takaisin tulostusjonoon,
  // jottei ker‰yseri‰ luotaisi joka kerta vaan lis‰‰ ja lis‰‰,
  // kun palautetaan tilaus takaisin tulostusjonoon
  if ($yhtiorow['kerayserat'] == 'K'
    or $yhtiorow['kerayserat'] == 'P'
    or ($yhtiorow['kerayserat'] == 'A' and $asrow['kerayserat'] == 'A')) {

    $query = "DELETE FROM kerayserat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND otunnus = '{$laskurow['tunnus']}'";
    pupe_query($query);
  }

  $query  = "UPDATE lasku SET
             tila        = '{$tila}',
             alatila     = '{$alatila}'
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$laskurow[tunnus]'
             and tila    in ('N', 'L')
             and alatila in ('', 'A', 'F', 'FF', 'U', 'G')";
  $result = pupe_query($query);

  ///* T‰h‰n menn‰‰n jos l‰hete tosiaan tulostetaan *///
  if (in_array($yhtiorow["lahetteen_tulostustapa"], array("A","B","X"))) {
    $tilausnumeroita = $laskurow["tunnus"];
    require "tilaus-valmis-tulostus.inc";

    // jos halutaan MY÷S l‰hete samantien
    if ($yhtiorow["lahetteen_tulostustapa"] == "B") {
      $params = array(
        'laskurow'          => $laskurow,
        'sellahetetyyppi'       => "",
        'extranet_tilausvahvistus'   => "",
        'naytetaanko_rivihinta'    => "",
        'tee'            => "",
        'toim'            => $toim,
        'komento'           => $komento,
        'lahetekpl'          => "",
        'kieli'           => ""
      );

      pupesoft_tulosta_lahete($params);
    }
  }
  ///* T‰h‰n menn‰‰n jos l‰hete vain siirret‰‰n tulostusjonoon *///
  elseif (mysql_affected_rows() > 0) {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s siirretty tulostusjonoon"), $laskurow["tunnus"])."!</font><br><br>\n";
  }
}
elseif ($kutsuja != 'keraa.php' and !$tilaus_toimitetuksi) {
  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (2)</font><br><br>\n";
}

if ($toimitusvahvitus_laheta) {
  $otunnukset = $laskurow["tunnus"];

  // L‰hetet‰‰n toimitusvahvistus
  pupesoft_toimitusvahvistus($otunnukset);
}

// haetaan toimitustavan tiedot
$query = "SELECT *,
          if(virallinen_selite != '', virallinen_selite, selite) as selite_text
          FROM toimitustapa
          WHERE yhtio = '$kukarow[yhtio]'
          AND selite  = '$laskurow[toimitustapa]'";
$toitares = pupe_query($query);
$toitarow = mysql_fetch_assoc($toitares);

// Jos Magento on k‰ytˆss‰, halutaan toimitusvahvistus, t‰m‰ on noutotilaus ja tullut Magentosta
$_magento_tv       = ($laskurow['laatija'] == 'Magento' and $toitarow['nouto'] != '' and !empty($laskurow["asiakkaan_tilausnumero"]));
$_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

if ($_magento_kaytossa and $_magento_tv and $kutsuja == 'keraa.php') {
  $magento_api_met = $toitarow['selite_text'];
  $magento_api_rak = '';
  $magento_api_ord = $laskurow["asiakkaan_tilausnumero"];
  $magento_api_noutokuittaus = "JOO";

  require "magento_toimita_tilaus.php";
}

if (!isset($silent) or $silent == "") {
  echo $tilaus_valmis_message;
}
