<?php

// Tarvitsemme
// $laskurow jossa on laskun tiedot
// $kutsuja jossa on skripti josta kutsutaan

$kerattavia   = 0;
$ei_kerattavia   = 0;
$tilaus_valmis_message = "";

// Pamautetaan toimitusvahvistus asiakkaalle jos mennään haaraan jossa ei toimiteta mitään
$toimitusvahvitus_laheta = FALSE;

// etukäteen maksettu lasku, pitää huomioida myös kpl
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

$query = "SELECT count(*) riveja,
          sum(if (tilausrivi.var = 'P' and tilausrivi.tilkpl $lis_plus_kpl > 0, 1, 0)) puutteet,
          sum(if (tilausrivi.var in ('J','S') and tilausrivi.jt $lisavarattu > 0, 1, 0)) jteet,
          sum(if (tilausrivi.var not in ('P','J','O','S') and tilausrivi.varattu $lis_plus_kpl < 0, 1, 0)) hyvitykset,
          sum(if (tilausrivi.var not in ('P','J','O','S') and (('$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa = '') or '$yhtiorow[kerataanko_saldottomat]' = 'S') and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) normaalit_kerataan,
          sum(if (tilausrivi.var not in ('P','J','O','S') and '$yhtiorow[kerataanko_saldottomat]' = '' and tuote.ei_saldoa != '' and tilausrivi.varattu $lis_plus_kpl > 0, 1, 0)) saldottomat,
          sum(if (tilausrivi.toimitettu != '' and tuote.ei_saldoa = '' and (tilausrivi.varattu $lis_plus_kpl > 0 and tilausrivi.var not in ('P','J','O','S')), 1, 0)) toimitetut
          FROM tilausrivi
          LEFT JOIN tuote ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
          and tilausrivi.otunnus  = '$laskurow[tunnus]'
          and tilausrivi.tyyppi   = 'L'
          and tilausrivi.tuoteno != '$yhtiorow[rahti_tuotenumero]'";
$tresult = pupe_query($query);
$rivirow = mysql_fetch_array($tresult);

// Kerättävät ja ei kerättävä rivit
$kerattavia = $rivirow["normaalit_kerataan"];
$ei_kerattavia = $rivirow["saldottomat"] + $rivirow["hyvitykset"];

// Toimitettuja rivejä ei kerätä ikinä
$ei_kerattavia += $rivirow["toimitetut"];
$kerattavia -= $rivirow["toimitetut"];

/*
puute_jt_kerataanko
''  Puuterivejä ja JT-rivejä EI kerätä
'P' Puuterivit kerätään
'J' JT-rivit kerätään
'Q' Puuterivit ja JT-rivit kerätään

kerataanko_jos_vain_puute_jt
''  Tilausta EI kerätä jos listalla on vain Puuterivejä JA/TAI JT-rivejä
'P' Tilaus kerätään jos listalla on vain Puuterivejä
'J' Tilaus kerätään jos listalla on vain JT-rivejä
'Q' Tilaus kerätään jos listalla on vain Puuterivejä JA/TAI JT-rivejä
*/

// ei lasketa kerättäviä/eikerättäviä määriä jos ollaan kerää.phpssa, koska silloin kaikki on varmasti kerättäviä jos ne siellä näky
if (($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtio haluaa kerätä Puuterivit
  $kerattavia += $rivirow["puutteet"];
}
else {
  $ei_kerattavia += $rivirow["puutteet"];
}

if (($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and $kutsuja != 'keraa.php') {
  // Jos yhtiö haluaa kerätä JT-rivit
  $kerattavia += $rivirow["jteet"];
}
else {
  $ei_kerattavia += $rivirow["jteet"];
}

// jos yhtiö haluaa kerätä puuterivejä niin katotaan onko meillä pelkästään tilauksella jt/puuterivejä
if ($yhtiorow["puute_jt_kerataanko"] != "" and $kutsuja != 'keraa.php') {
  if ($rivirow["puutteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "P" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "J")) {
    // Meillä on pelkästään puutteita kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["puutteet"];
    $kerattavia     -= $rivirow["puutteet"];
  }
  elseif ($rivirow["jteet"] == $kerattavia and ($yhtiorow["puute_jt_kerataanko"] == "J" or $yhtiorow["puute_jt_kerataanko"] == "Q") and ($yhtiorow["kerataanko_jos_vain_puute_jt"] == "" or $yhtiorow["kerataanko_jos_vain_puute_jt"] == "P")) {
    // Meillä on pelkästään JT-rivejä kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["jteet"];
    $kerattavia     -= $rivirow["jteet"];
  }
  elseif ($rivirow["jteet"] + $rivirow["puutteet"] == $kerattavia and $yhtiorow["puute_jt_kerataanko"] == "Q" and $yhtiorow["kerataanko_jos_vain_puute_jt"] == "") {
    // Meillä on pelkästään JT-rivejä + puutteita kerättävänä ja niitä ei tässä tapauksessa kerätä
    $ei_kerattavia += $rivirow["jteet"] + $rivirow["puutteet"];
    $kerattavia     -= $rivirow["jteet"] + $rivirow["puutteet"];
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

// Tutkitaan mitä tehdään tilaukselle
if ($rivirow["riveja"] == 0 or ($kerattavia == 0 and $ei_kerattavia == 0 and $kutsuja == 'keraa.php')) {
  // Tilauksella ei ole mitään laskutettavaa eikä kerättävää
  $query  = "UPDATE tilausrivi
             SET tyyppi='D'
             WHERE otunnus = '$laskurow[tunnus]' and yhtio = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus-valmis-valitsetila.php (1)")."<br>";

  $query  = "UPDATE lasku
             SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s mitätöitiin koska tilauksella ei ollut yhtään kerättävää/laskutettavaa riviä."), $laskurow["tunnus"])."</font><br><br>\n";
  $toimitusvahvitus_laheta = TRUE;
}
elseif ($rivirow["jteet"] > 0 and $laskurow["osatoimitus"] != '') {
  // Jos tilauksella oli yksikin jt-rivi ja osatoimitus on kielletty
  $query  = "UPDATE lasku
             SET tila='N', alatila='U'
             where tunnus='$laskurow[tunnus]'
             and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli JT-rivejä ja osatoimitusta ei tehdä, eli se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
}
elseif ($kerattavia == 0) {
  // Tilauskella on pelkästään puuterivejä joita ei kerätä
  // merkataan otsikko mitätöidyksi, mutta jätetään PUUTE rivit analyysejä varten...
  if ($rivirow["riveja"] == $rivirow["puutteet"]) {
    // Jos tilauksella oli vain puuterivejä niin poistetaan rahtikulurivi jos sellanen on
    $query  = "DELETE FROM tilausrivi
               WHERE otunnus = '$laskurow[tunnus]'
               AND yhtio     = '$kukarow[yhtio]'
               AND tuoteno   = '$yhtiorow[rahti_tuotenumero]'";
    $result = pupe_query($query);

    $komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mitätöi ohjelmassa tilaus-valmis-valitsetila.php (2)")."<br>";

    $query  = "UPDATE lasku
               SET tila='D', alatila='$laskurow[tila]', comments = '$komm'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s  oli pelkästään Puuterivejä. Otsikko mitätöitiin."), $laskurow["tunnus"])."</font><br><br>\n";
    $toimitusvahvitus_laheta = TRUE;
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"]) {

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt-rivejä niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno   = '$yhtiorow[rahti_tuotenumero]'";
      $result = pupe_query($query);
    }

    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus='$laskurow[tunnus]'
               AND yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelkästään JT-rivejä ja se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($rivirow["riveja"] == $rivirow["jteet"] + $rivirow["puutteet"]) {
    // Tilauskella on pelkästään jt-rivejä ja puutteita joita ei kerätä

    if ($yhtiorow["rahti_hinnoittelu"] != 'K') {
      // Jos tilauksella oli vain jt/puute-rivejä niin poistetaan rahtikulurivi jos sellanen on
      $query  = "DELETE FROM tilausrivi
                 WHERE otunnus = '$laskurow[tunnus]'
                 AND yhtio     = '$kukarow[yhtio]'
                 AND tuoteno   = '$yhtiorow[rahti_tuotenumero]'";
      $result = pupe_query($query);
    }

    // Päivitetään tilaus tilaan 'odottaa jt-rivejä'
    $query  = "UPDATE lasku
               SET tila='N', alatila='T'
               WHERE tunnus = '$laskurow[tunnus]'
               AND yhtio    = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s oli pelkästään JT-rivejä ja Puuterivejä ja se jätettiin odottamaan JT-tuotteita."), $laskurow["tunnus"])."</font><br><br>\n";
  }
  elseif ($ei_kerattavia > 0) {
    if ($kateinen == 'X' or $itsetulostus == "X" or $laskurow["vienti"] != '') {
      // jos on laskuettavia rivejä ja jos kyseessä on käteistä, itsetulostus tai vientiä
      // merkataan rivit kerätyiksi, mutta ei toimitetuiksi ja laskulle alatila alatila C/B
      $query  = "UPDATE tilausrivi
                 SET kerattyaika = now(), keratty = '$kukarow[kuka]'
                 WHERE otunnus   = '$laskurow[tunnus]'
                 and yhtio       = '$kukarow[yhtio]'
                 and var         not in ('P','J','O','S')
                 and keratty     = ''
                 and toimitettu  = ''
                 and tyyppi     != 'D'";
      $result = pupe_query($query);

      // Jos on vientiä ja vain saldottomia niin mennään suoraan toimitetuksi
      if ($laskurow["vienti"] != '' and $rivirow["riveja"] == $rivirow["saldottomat"]) {

        // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
        if ($laskurow['jaksotettu'] != 0) {
          $alat = "J";
        }
        else {
          $alat = "D";
        }

        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";

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
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi. Viennin lisätiedot syötettävä."), $laskurow["tunnus"])."</font><br><br>\n";

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
        $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin kerätyksi."), $laskurow["tunnus"])."</font><br><br>\n";
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
      // Jos on laskuettavia rivejä ja jos kyseessä ei ole käteinen, merkataan tilaukset kerätyksi ja toimitetuksi ja alatila 'D'
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

      // Jos meillä on maksupositioita laskulla, tulee se siirtää alatilaan J
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

      $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksella %s ei ollut yhtään kerättävää riviä, se merkattiin toimitetuksi."), $laskurow["tunnus"])."</font><br><br>\n";
      $toimitusvahvitus_laheta = TRUE;
    }
  }
  else {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (1)</font><br><br>\n";
  }
}
elseif ($kerattavia > 0 and $kutsuja != 'keraa.php') {
  // Tässä meillä ON jotain kerättävää
  // laitetaan lähete tulostusjonoon
  $tila = 'N';
  $alatila = 'A';

  if ($yhtiorow['lahetteen_tulostustapa'] == 'I') {
    $tila = 'L';
    $alatila = 'D';
  }

  $query  = "UPDATE lasku SET
             tila        = '{$tila}',
             alatila     = '{$alatila}'
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$laskurow[tunnus]'
             and tila    in ('N','L')
             and alatila in ('','A','F','U', 'G')";
  $result = pupe_query($query);

  ///* Tähän mennään jos lähete tosiaan tulostetaan *///
  if ($yhtiorow["lahetteen_tulostustapa"] == "A" or $yhtiorow["lahetteen_tulostustapa"] == "B") {
    $tilausnumeroita = $laskurow["tunnus"];
    require("tilaus-valmis-tulostus.inc");

    // jos halutaan MYÖS lähete samantien
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
  ///* Tähän mennään jos lähete vain siirretään tulostusjonoon *///
  elseif (mysql_affected_rows() > 0) {
    $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilaus %s siirretty tulostusjonoon"), $laskurow["tunnus"])."!</font><br><br>\n";
  }
}
elseif ($kutsuja != 'keraa.php') {
  $tilaus_valmis_message .= "<font class='message'>".sprintf(t("VIRHE: Tilausta %s ei voitu merkata valmiiksi. Aktivoi tilaus ja korjaa virheet"), $laskurow["tunnus"])."! (2)</font><br><br>\n";
}

if ($toimitusvahvitus_laheta) {
  $query = "SELECT toimitusvahvistus
            FROM asiakas
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$laskurow[liitostunnus]'";
  $tresult = pupe_query($query);
  $asirow = mysql_fetch_array($tresult);

  if ($asirow['toimitusvahvistus'] != '') {

    // haetaan toimitustavan tiedot
    $query    = "SELECT * from toimitustapa where yhtio = '$kukarow[yhtio]' and selite = '$laskurow[toimitustapa]'";
    $toitares = pupe_query($query);
    $toitarow = mysql_fetch_assoc($toitares);

    $otunnukset    = $laskurow["tunnus"];
    $tunnukset    = 0;
    $rahtikirjanro  = 0;

    if ($asirow["toimitusvahvistus"] == "toimitusvahvistus_desadv_una.inc") {
      $desadv_version = "una";
      $asirow["toimitusvahvistus"] = "toimitusvahvistus_desadv.inc";
    }
    elseif ($asirow["toimitusvahvistus"] == "toimitusvahvistus_desadv_fi0089.inc") {
      $desadv_version = "fi0089";
      $asirow["toimitusvahvistus"] = "toimitusvahvistus_desadv.inc";
    }
    else {
      $desadv_version = "";
    }

    @include("tilauskasittely/$asirow[toimitusvahvistus]");

    if ($toimitusvahvistus_desadv_lahetys === true) {
      $tilaus_valmis_message .= "<font class='message'>".sprintf(t("Tilauksesta %s lähetettiin toimitusvahvistus."), $laskurow["tunnus"])."</font><br><br>\n";
    }
  }
}

if (!isset($silent) or $silent == "") {
  echo $tilaus_valmis_message;
}
