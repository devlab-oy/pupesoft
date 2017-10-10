<?php

if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php") !== FALSE) {
  if (@include "../inc/parametrit.inc");
  elseif (@include "parametrit.inc");
  else exit;

  if (isset($toim) and $toim == "ENNAKKO") {
    echo "<font class='head'>".t("Ennakkotilausrivit")."</font><hr>";
  }
  else {
    echo "<font class='head'>".t("JT rivit")."</font><hr>";
  }

  if (function_exists("js_popup")) {
    echo js_popup(-100);
  }
}

if (!isset($asiakasid)) $asiakasid = "";
if (!isset($asiakasmaa)) $asiakasmaa = "";
if (!isset($asiakasno)) $asiakasno = "";
if (!isset($automaaginen)) $automaaginen = "";
if (!isset($borderlask)) $borderlask = "";
if (!isset($ei_limiittia)) $ei_limiittia = "";
if (!isset($from_varastoon_inc)) $from_varastoon_inc = "";
if (!isset($ins)) $ins = "";
if (!isset($jarj)) $jarj = "";
if (!isset($kpl)) $kpl = "";
if (!isset($lapsires)) $lapsires = "";
if (!isset($loput)) $loput = "";
if (!isset($maa)) $maa = "";
if (!isset($pkrow)) $pkrow = array();
if (!isset($suoratoimit)) $suoratoimit = "";
if (!isset($jt_huomioi_pvm)) $jt_huomioi_pvm = "";
if (!isset($tee)) $tee = "";
if (!isset($tilaus)) $tilaus = "";
if (!isset($tilausnumero)) $tilausnumero = "";
if (!isset($tilaus_on_jo)) $tilaus_on_jo = "";
if (!isset($toim)) $toim = "";
if (!isset($toimi)) $toimi = "";
if (!isset($toimittaja)) $toimittaja = "";
if (!isset($toimittajaid)) $toimittajaid = "";
if (!isset($tuotemerkki)) $tuotemerkki = "";
if (!isset($tuotenumero)) $tuotenumero = "";
if (!isset($tuoteosasto)) $tuoteosasto = "";
if (!isset($saldolaskenta)) $saldolaskenta = "";
if (!isset($tuoteryhma)) $tuoteryhma = "";
if (!isset($vainvarastosta)) $vainvarastosta = "";
if (!isset($suoratoimitus_rivit)) $suoratoimitus_rivit  = array();
if (!isset($suoratoimitus_paikat)) $suoratoimitus_paikat = array();
if (!isset($varastosta)) $varastosta = "";
if (!isset($ytunnus)) $ytunnus = "";
if (!isset($myyja)) $myyja = "";
if (!isset($automaattinen_poiminta)) $automaattinen_poiminta = "";
if (!isset($mista_tullaan)) $mista_tullaan = "";
if (!isset($jt_tyyppi)) $jt_tyyppi = "";
if (!isset($alkuperainen_varasto)) $alkuperainen_varasto = "";

// ennakoissa ei setata jt_huomioi_pvm automaattisesti
// jälkkäreissä setataan jos ei tulla jtselauksen kautta
if ($yhtiorow["saldo_kasittely"] == 'U' and $toim != 'ENNAKKO' and strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php") === FALSE) {
  $jt_huomioi_pvm = "on";
}

$onkolaajattoimipaikat = ($yhtiorow['toimipaikkakasittely'] == "L" and $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio']) and mysql_num_rows($toimipaikat_res) > 0) ? TRUE : FALSE;

$DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

// JT-selaus päivitysoikeus, joko JT-selaus päivitysoikeus tai tullaan keikalta ja kaikki saa toimittaa JT-rivejä
$jtselaus_paivitys_oikeus = FALSE;

if (
  $oikeurow['paivitys'] == '1'
  or (
    (
      strpos($_SERVER['SCRIPT_NAME'], "keikka.php") !== FALSE
      or strpos($_SERVER['SCRIPT_NAME'], "verkkolasku-in.php") !== FALSE
      or strpos($_SERVER['SCRIPT_NAME'], "vastaanota.php") !== FALSE
    )
    and (
      in_array($yhtiorow["automaattinen_jt_toimitus"], array('J', 'A'))
      or in_array($yhtiorow["automaattinen_jt_toimitus_siirtolista"], array('J', 'S', 'K'))
    )
  )
) {
  $jtselaus_paivitys_oikeus = TRUE;
}

if (isset($_POST['korvataanko']) and $_POST['korvataanko'] == 'KORVAA') {
  $query = "UPDATE tilausrivi
            SET tuoteno = '$_POST[korvaava]'
            WHERE yhtio = '$kukarow[yhtio]'
            AND tuoteno = '$_POST[korvattava]'
            AND tunnus  = '$_POST[korvattava_tilriv]'";
  $res = pupe_query($query);
  $tee = 'JATKA';
  $_POST['korvataanko'] = '';
}

if ($tee != "JT_TILAUKSELLE" and $vainvarastosta != "") {
  $varastosta = array();
  $varastosta[$vainvarastosta] = $vainvarastosta;
}

//Extranet käyttäjille pakotetaan aina tiettyjä arvoja
if ($kukarow["extranet"] != "") {
  $query  = "SELECT *
             FROM asiakas
             WHERE yhtio = '$kukarow[yhtio]'
             and tunnus  = '$kukarow[oletus_asiakas]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $asiakas = mysql_fetch_assoc($result);

    $toimittaja    = "";
    $toimittajaid  = "";

    $asiakasno     = $asiakas["ytunnus"];
    $asiakasid    = $asiakas["tunnus"];

    if ($asiakas["toim_nimi"] == "") {
      $asiakasmaa = $asiakas["maa"];
    }
    else {
      $asiakasmaa = $asiakas["toim_maa"];
    }

    $jarj       = "tuoteno";
    $tuotenumero  = "";
    $tilaus      = "";
    $toimi      = "";
    $ei_limiittia  = "";
    $suoratoimit  = "";
    $tilaus_on_jo  = "KYLLA";

    if ($tee == "") {
      $tee = "JATKA";
    }
  }
  else {
    die("Asiakastietojasi ei löydy!");
  }
}

// Tilaus pitää tosiaan löytyä ja olla $kukarow[kesken]issä
if ($tilaus_on_jo != '') {
  $query  = "SELECT *
             FROM lasku
             WHERE yhtio = '$kukarow[yhtio]'
             AND tunnus  = '$kukarow[kesken]'
             AND tila    IN ('N', 'L', 'E', 'G')";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 1) {
    $tilaus_on_jo_row = mysql_fetch_assoc($result);
    $asiakasmaa = $tilaus_on_jo_row["toim_maa"];
  }
  else {
    die("Tilausta ei löydy!");
  }
}

// Haetaan tee_jt_tilaus-funktio
require "tee_jt_tilaus.inc";

//JT-rivit on poimittu
if ($jtselaus_paivitys_oikeus and ($tee == 'POIMI' or $tee == "JT_TILAUKSELLE")) {
  foreach ($jt_rivitunnus as $tunnukset) {

    $tunnusarray = explode(',', $tunnukset);

    if ((isset($kpl[$tunnukset]) and $kpl[$tunnukset] > 0) or (isset($loput[$tunnukset]) and $loput[$tunnukset] != '')) {

      // Tutkitaan hintoja ja alennuksia
      if ($tee == "JT_TILAUKSELLE" and $tila == "jttilaukseen" and $toim != "ENNAKKO" and $toim != 'SIIRTOLISTA') {
        $mista = 'jtrivit_tilaukselle.inc';
        require "laskealetuudestaan.inc";
      }

      // Toimitetaan jtrivit
      tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, "", $tilaus_on_jo, $varastosta, $jt_huomioi_pvm, $mista_tullaan);

      if ($kukarow['extranet'] != '' and $tee == "JT_TILAUKSELLE") {
        unset($jarj);
      }
    }
  }

  $tee = "JATKA";
}

if ($jtselaus_paivitys_oikeus and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'TOIMITA') {
  if ($toim == "ENNAKKO") {
    $query  = "SELECT *
               FROM lasku
               WHERE yhtio = '$kukarow[yhtio]'
               AND laatija = '$kukarow[kuka]'
               AND alatila = 'E'
               AND tila    = 'N'";
  }
  else {

    // "Osatoimitus kielletty"-tilauksella, mutta nyt kaikki JT-rivit on poimittu, joten laitetaan tilaus eteenpäin
    $query = "SELECT lasku.tunnus tilaus,
              count(tilausrivi.tunnus) tot_riveja,
              sum(if (tilausrivi.var != 'J',1,0)) toimitettavia_riveja
              FROM lasku use index (tila_index)
              JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and tilausrivi.tyyppi='L'
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              AND lasku.tila    = 'N'
              AND lasku.alatila = 'U'
              GROUP BY lasku.tunnus
              HAVING tot_riveja = toimitettavia_riveja";
    $stresult = pupe_query($query);

    $ostok = "";

    while ($osatoimrow = mysql_fetch_assoc($stresult)) {
      $ostok .= $osatoimrow["tilaus"].",";
    }

    $ostok = substr($ostok, 0, -1);

    if ($ostok != "") {
      $ostok = "  UNION
            (SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and lasku.tunnus in ($ostok))";
    }

    $query = "(SELECT *
               FROM lasku
               WHERE yhtio = '$kukarow[yhtio]'
               and tila    = 'N'
               and alatila = 'J'
               and laatija = '$kukarow[kuka]')
               UNION
               (SELECT *
               FROM lasku
               WHERE yhtio = '$kukarow[yhtio]'
               and tila    = 'G'
               and alatila = 'P'
               and laatija = '$kukarow[kuka]')
               $ostok";
  }

  $jtrest = pupe_query($query);

  while ($laskurow = mysql_fetch_assoc($jtrest)) {

    $query  = "UPDATE lasku
               SET alatila = ''
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$laskurow[tunnus]'";
    $apure  = pupe_query($query);

    $laskurow['alatila'] = '';

    if ($toim != "ENNAKKO") {
      //mennään aina tänne ja sit tuolla inkissä katotaan aiheuttaako toimenpiteitä.
      $mista = 'jtselaus';
      require "laskealetuudestaan.inc";
    }

    if ($laskurow["tila"] == "N" and $automaaginen == "") {
      //Pyydetään tilaus-valmista olla echomatta mitään
      $silent = "SILENT";
    }

    // tarvitaan $kukarow[yhtio], $kukarow[kesken], $laskurow ja $yhtiorow
    $kukarow["kesken"] = $laskurow["tunnus"];

    // Oletuksena ei laiteta käteistilauksia suoraan laskutuskeen vaikka ne muuten menisivät sinne.
    $kateisohitus = "X";

    // Onko yhtään kerättävää riviä, vai oliko kaikki esim. suoratoimituksia?
    $query = "SELECT tunnus
              FROM tilausrivi
              WHERE yhtio    = '$kukarow[yhtio]'
              AND otunnus    = '$laskurow[tunnus]'
              AND toimitettu = ''";
    $apure = pupe_query($query);

    // Kaikki rivit toimitettu:
    if (mysql_num_rows($apure) == 0) {

      // Magento etukätee maksetut suoratoimitustilaukset tulee asettaa kassalippaaseen
      // laskutus tapahtuu kuitenkin alempana tilaus.valmis.incissä
      if ($laskurow['ohjelma_moduli'] == 'MAGENTOJT') {

        // Katsotaan onko Magento käytössä, merkataan tilaus toimitetuksi
        $_magento_kaytossa = (!empty($magento_api_tt_url) and !empty($magento_api_tt_usr) and !empty($magento_api_tt_pas));

        if ($_magento_kaytossa) {

          $query = "SELECT virallinen_selite, selite
                    FROM toimitustapa
                    WHERE yhtio     = '$kukarow[yhtio]'
                    AND selite      = '$laskurow[toimitustapa]'";
          $toimitustapa_re = pupe_query($query);
          $toimitustapa_row = mysql_fetch_assoc($toimitustapa_re);

          $magento_api_met = $toimitustapa_row['virallinen_selite'] != '' ? $toimitustapa_row['virallinen_selite'] : $toimitustapa_row['selite'];
          $magento_api_rak = "";
          $magento_api_ord = $laskurow["asiakkaan_tilausnumero"];
          $magento_api_laskutunnus = $laskurow["tunnus"];

          require "{$pupe_root_polku}/magento_toimita_tilaus.php";
        }

        list($kukarow["kassamyyja"], $laskurow['kassalipas'], $laskurow['ohjelma_moduli']) = laskuta_magentojt($laskurow['tunnus'], false);
      }

      $kateisohitus = "";
      $laskurow['eilahetetta'] = 'o';
    }

    // Jos tilaus on osatoimituskiellossa ja on täpätty suoraan laskutukseen, pitää tällöinkin laittaa $kateisohitus tyhjäksi
    if ($laskurow['eilahetetta'] == 'o' and $laskurow['osatoimitus'] == 'o') $kateisohitus = "";

    if ($laskurow['tila']== 'G') {
      $vanhatoim = $toim;
      $toim = "SIIRTOLISTA";

      require "tilaus-valmis-siirtolista.inc";

      $aika=date("d.m.y @ G:i:s", time());
      echo "<font class='message'>".t("Siirtolista")." $laskurow[tunnus] ".t("valmis")."! ($aika)</font><br>";

      $toim = $vanhatoim;
      $vanhatoim = "";
    }
    else {
      $mista = "jtselaus";
      $tilausvalmiskutsuja = "JTSELAUS";

      require "tilaus-valmis.inc";

      $aika=date("d.m.y @ G:i:s", time());
      echo "<font class='message'>".t("Myyntitilaus")." $laskurow[tunnus], $laskurow[nimi] ".t("valmis")."! ($aika)</font><br>";
    }

    //  Katsotaan toimitettiinko jotain mistä meidän tulee laittaa viestiä
    if (sms_jt == "kaikki_toimitettu") {
      if ($yhtiorow["varaako_jt_saldoa"] != "") {
        $sms_lisavarattu = " + alkup_tilaus.varattu";
      }
      else {
        $sms_lisavarattu = "";
      }
      //  Haetaan kaikki alkuperäiset tilaukset joilla ei ole enää mitään jälkkärissä
      $query = "SELECT tilausrivin_lisatiedot.vanha_otunnus, sum(alkup_tilaus.jt $sms_lisavarattu) jt
                FROM tilausrivi
                JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                  AND tilausrivin_lisatiedot.tilausrivitunnus  = tilausrivi.tunnus)
                LEFT JOIN tilausrivi alkup_tilaus ON (alkup_tilaus.yhtio = tilausrivin_lisatiedot.yhtio
                  AND alkup_tilaus.otunnus                     = tilausrivin_lisatiedot.vanha_otunnus)
                WHERE tilausrivi.yhtio                         = '$kukarow[yhtio]'
                AND tilausrivi.otunnus                         = '$laskurow[tunnus]'
                and tilausrivi.var                            != 'J'
                GROUP BY tilausrivin_lisatiedot.vanha_otunnus
                HAVING jt = 0 or jt IS NULL";
      $result = pupe_query($query);

      while ($row = mysql_fetch_assoc($result)) {

        $smsviesti = "Tilauksenne $row[vanha_otunnus] on valmis noudettavaksi. Viestiin ei tarvitse vastata. - $yhtiorow[nimi]";
        $smsnumero = "";

        //  Jos mistään ei ole tullut numeroa koitetaan arvata se (tällähetkellä se ei tule mistään...)
        if ($smsnumero == "") {
          //  Haetaan sen orginaalilaskun tiedot, koska siellä ne on ainakin oikein
          $query = "SELECT lasku.liitostunnus, lasku.nimitark, maksuehto.kateinen, lasku.nimi
                    FROM lasku
                    LEFT JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and maksuehto.tunnus = lasku.maksuehto
                    WHERE lasku.yhtio = '$kukarow[yhtio]'
                    AND lasku.tunnus  = '$row[vanha_otunnus]'";
          $result = pupe_query($query);
          $vanhalaskurow = mysql_fetch_assoc($result);
          $animi = $vanhalaskurow["nimi"];

          //  Oletyksena asiakkaan on gsm-numero
          if ($smsnumero == "") {
            $query = "SELECT gsm
                      FROM asiakas
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND tunnus  = '$vanhalaskurow[liitostunnus]'";
            $result = pupe_query($query);
            $asrow = mysql_fetch_assoc($result);
            $n = on_puhelinnumero($asrow["gsm"]);
            if ($n != "") {
              $smsnumero = $n;
            }
          }

          //  Jos meillä on käteismyynti voidaan otsikon nimitarkenteessa sisällyttää puhelinnumero
          if ($smsnumero == "" and $vanhalaskurow["kateinen"] != "" and $vanhalaskurow["nimitark"] != "") {
            $n = on_puhelinnumero($vanhalaskurow["nimitark"]);
            if ($n != "" ) {
              $smsnumero = $n;
            }
          }

          //  Ja lähetetään itse SMS
          if ($smsnumero!="") {
            sendSMS($smsnumero, $smsviesti, $animi);
          }
        }
      }
    }
  }

  if ($from_varastoon_inc == "") $tee = '';
}

// Tutkitaan onko käyttäjällä keskenolevia jt-rivejä
if ($jtselaus_paivitys_oikeus and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $from_varastoon_inc == "") {

  if ($toim == "ENNAKKO") {
    $query = "SELECT *
              FROM lasku
              WHERE yhtio = '$kukarow[yhtio]'
              and laatija = '$kukarow[kuka]'
              and alatila = 'E'
              and tila    = 'N'";
  }
  else {
    // "Osatoimitus kielletty"-tilauksella, mutta nyt kaikki JT-rivit on poimittu, joten laitetaan tilaus eteenpäin
    $query = "SELECT lasku.tunnus tilaus,
              count(tilausrivi.tunnus) tot_riveja,
              sum(if (tilausrivi.var != 'J',1,0)) toimitettavia_riveja
              FROM lasku use index (tila_index)
              JOIN tilausrivi ON lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and tilausrivi.tyyppi='L'
              WHERE lasku.yhtio = '$kukarow[yhtio]'
              AND lasku.tila    = 'N'
              AND lasku.alatila = 'U'
              GROUP BY lasku.tunnus
              HAVING tot_riveja = toimitettavia_riveja";
    $stresult = pupe_query($query);

    $ostok = "";

    while ($osatoimrow = mysql_fetch_assoc($stresult)) {
      $ostok .= $osatoimrow["tilaus"].",";
    }

    $ostok = substr($ostok, 0, -1);

    if ($ostok != "") {
      $ostok = "  UNION
            (SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and lasku.tunnus in ($ostok))";
    }

    $query = "(SELECT *
               FROM lasku
               WHERE yhtio = '$kukarow[yhtio]'
               and tila    = 'N'
               and alatila = 'J'
               and laatija = '$kukarow[kuka]')
               UNION
               (SELECT *
               FROM lasku
               WHERE yhtio = '$kukarow[yhtio]'
               and tila    = 'G'
               and alatila = 'P'
               and laatija = '$kukarow[kuka]')
               $ostok";
  }

  $stresult = pupe_query($query);

  if (mysql_num_rows($stresult) > 0) {
    echo "  <form name='valinta' method='post'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='maa' value='$maa'>
        <input type='hidden' name='myyja' value='{$myyja}' />
        <input type='hidden' name='tee' value='TOIMITA'>";

    if ($toim == "ENNAKKO") {
      echo "  <font class='error'>".t("HUOM: Sinulla on toimittamattomia ennakkorivejä")."</font><br>
          <table>
          <tr>
          <td>".t("Toimita poimitut ennakko-rivit").": </td>";
    }
    else {
      echo "  <font class='error'>".t("HUOM: Sinulla on toimittamattomia jt-rivejä")."</font><br>
          <table>
          <tr>
          <td>".t("Laske alennukset uudelleen")."</td>
          <td><input type='checkbox' name='laskeuusix'></td></tr><tr>
          <td>".t("Toimita poimitut JT-rivit")."</td>";
    }

    echo "  <td><input type='submit' value='".t("Toimita")."'></td>
        </tr>
        </table>
        </form><hr>";
  }
}

//muokataan tilausriviä
if ($jtselaus_paivitys_oikeus and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'MUOKKAARIVI') {
  $query = "SELECT *
            FROM tilausrivi
            WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo t("Tilausriviä ei löydy")."! $query";
    exit;
  }
  $trow = mysql_fetch_assoc($result);

  $query = "DELETE from tilausrivi
            WHERE tunnus = '$jt_rivitunnus' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  $query = "SELECT *
            FROM tuote
            WHERE yhtio='$kukarow[yhtio]' and tuoteno = '$trow[tuoteno]'";
  $result = pupe_query($query);
  $tuoterow = mysql_fetch_assoc($result);

  $query  = "SELECT *
             from lasku
             WHERE yhtio='$kukarow[yhtio]' and tunnus = '$trow[otunnus]'";
  $result = pupe_query($query);
  $laskurow = mysql_fetch_assoc($result);

  $tuoteno     = $trow["tuoteno"];

  if ($toim == "ENNAKKO") {
    $kpl     = $trow["varattu"];
  }
  else {
    if ($yhtiorow["varaako_jt_saldoa"] == "") {
      $kpl   = $trow["jt"];
    }
    else {
      $kpl   = $trow["jt"]+$trow["varattu"];
    }
  }

  // Tutkitaan onko tämä myyty ulkomaan alvilla
  list(, , , $tsek_alehinta_alv, ) = alehinta($laskurow, $tuoterow, $kpl, '', '', '');

  if ($tsek_alehinta_alv > 0) {
    $tuoterow["alv"] = $tsek_alehinta_alv;
  }

  if ($tuoterow["alv"] != $trow["alv"] and $yhtiorow["alv_kasittely"] == "" and $trow["alv"] < 500) {
    $hinta     = hintapyoristys($trow["hinta"] / (1+$trow['alv']/100) * (1+$tuoterow['alv']/100));
  }
  else {
    $hinta    = $trow["hinta"];
  }

  if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
    $hinta  = hintapyoristys(laskuval($hinta, $laskurow["vienti_kurssi"]));
  }

  for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    ${'ale'.$alepostfix} = $trow["ale{$alepostfix}"];
  }

  $toimaika     = $trow["toimaika"];
  $kerayspvm    = $trow["kerayspvm"];
  $alv       = $trow["alv"];
  $var       = $trow["var"];
  $netto      = $trow["netto"];
  $perheid    = $trow["perheid"];
  $kommentti     = $trow["kommentti"];
  $rivinotunnus  = $trow["otunnus"];

  echo t("Muuta riviä").":<br>";

  echo "<form method='post'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' name='tee' value='LISAARIVI'>";
  echo "<input type='hidden' name='jarj' value='$jarj'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
  echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
  echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
  echo "<input type='hidden' name='toimi' value='$toimi'>";
  echo "<input type='hidden' name='ei_limiittia' value='$ei_limiittia'>";
  echo "<input type='hidden' name='suoratoimit' value='$suoratoimit'>";
  echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
  echo "<input type='hidden' name='tilaus' value='$tilaus'>";
  echo "<input type='hidden' name='rivinotunnus' value='$rivinotunnus'>";
  echo "<input type='hidden' name='maa' value='$maa'>";
  echo "<input type='hidden' name='myyja' value='{$myyja}' />";

  if (is_array($varastosta)) {
    foreach ($varastosta as $vara) {
      $tilausnumero .= $vara."##";
    }
    $tilausnumero = substr($tilausnumero, 0, -2);
  }
  echo "<input type='hidden' name='tilausnumero' value='$tilausnumero'>";

  $aputoim = "RIVISYOTTO";

  require 'syotarivi.inc';
  exit;
}

//Lisätään muokaattu tilausrivi
if ($jtselaus_paivitys_oikeus and $kukarow["extranet"] == "" and $tilaus_on_jo == "" and $tee == 'LISAARIVI') {

  // lisää päivämäärän tarkistus.
  if (checkdate($kerayskka, $keraysppa, $keraysvva)) {
    $kerayspvm = $keraysvva."-".$kerayskka."-".$keraysppa;
  }
  else {
    $kerayspvm  = date("Y-m-d");
  }

  // lisää päivämäärän tarkistus.
  if (checkdate($toimkka, $toimppa, $toimvva)) {
    $toimaika  =  $toimvva."-".$toimkka."-".$toimppa;
  }
  else {
    $toimaika  = date("Y-m-d");
  }

  if ($kpl > 0) {
    $query = "SELECT *
              FROM tuote
              WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
    $tuoteresult = pupe_query($query);

    if (mysql_num_rows($tuoteresult) == 1) {

      $trow = mysql_fetch_assoc($tuoteresult);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio='$kukarow[yhtio]' and tunnus='$rivinotunnus'";
      $laskures = pupe_query($query);
      $laskurow = mysql_fetch_assoc($laskures);

      $varataan_saldoa       = "EI";
      $kukarow["kesken"]       = $rivinotunnus;

      require 'lisaarivi.inc';
    }
    else {
      $varaosavirhe = t("VIRHE: Tuotetta ei löydy")."!<br>";
    }
  }

  $varastot = explode('##', $tilausnumero);

  foreach ($varastot as $vara) {
    $varastosta[$vara] = $vara;
  }
  $tee = "JATKA";
}

if ($kukarow["extranet"] == "" and $tilaus_on_jo == "" and ($tee == "" or $tee == "JATKA")) {

  if (isset($muutparametrit)) {
    list($tuotenumero, $tilaus, $jarj, $toimi, $ei_limiittia, $suoratoimit, $automaaginen, $ytunnus, $asiakasno, $toimittaja, $tuoteosasto, $saldolaskenta, $tuoteryhma, $tuotemerkki, $maa, $myyja) = explode('#', $muutparametrit);

    $varastot = explode('##', $tilausnumero);

    foreach ($varastot as $vara) {
      $varastosta[$vara] = $vara;
    }
  }

  $muutparametrit = "$tuotenumero#$tilaus#$jarj#$toimi#$ei_limiittia#$suoratoimit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$tuoteosasto#$saldolaskenta#$tuoteryhma#$tuotemerkki#$maa#$myyja#";

  if (is_array($varastosta)) {
    foreach ($varastosta as $vara) {
      $tilausnumero .= $vara."##";
    }
    $tilausnumero = substr($tilausnumero, 0, -2);
  }

  if ($ytunnus != '' or is_array($varastosta)) {
    if ($ytunnus != '' and !isset($ylatila) and is_array($varastosta)) {
      require "inc/kevyt_toimittajahaku.inc";

      if ($ytunnus != '') {
        $toimittaja = $ytunnus;
        $tee = "JATKA";
      }
    }
    elseif ($ytunnus != '' and isset($ylatila)) {
      $tee = "JATKA";
    }
    elseif (is_array($varastosta)) {
      $tee = "JATKA";
    }
    else {
      $tee = "";
    }
  }
  $muutparametrit = "$tuotenumero#$tilaus#$jarj#$toimi#$ei_limiittia#$suoratoimit#$automaaginen#$ytunnus#$asiakasno#$toimittaja#$tuoteosasto#$saldolaskenta#$tuoteryhma#$tuotemerkki#$maa#$myyja#";

  if (is_array($varastosta)) {
    foreach ($varastosta as $vara) {
      $tilausnumero .= $vara."##";
    }
    $tilausnumero = substr($tilausnumero, 0, -2);
  }

  if ($asiakasno != '' and $tee == "JATKA") {
    $muutparametrit .= $ytunnus;

    if ($asiakasid == "") {
      $ytunnus = $asiakasno;
    }

    require "inc/asiakashaku.inc";

    if ($ytunnus != '') {
      $tee = "JATKA";
      $asiakasno = $ytunnus;
      $ytunnus = $toimittaja;
    }
    else {
      $asiakasno = $ytunnus;
      $ytunnus = $toimittaja;

      $tee = "";
    }
  }
}

if ($tee == "JATKA") {

  $tuotelisa      = "";
  $laskulisa      = "";
  $toimittajalisa = "";
  $tilausrivilisa = "";

  // Näytetään vain ne rivit joiden kerayspvm sanoo, että nyt mennään
  if ($jt_huomioi_pvm != "") {
    $tilausrivilisa .= " and tilausrivi.kerayspvm <= now() ";

    // Jos JT-rivit varaavat saldoa, niin tulevaisuudessa toimitettavat rivit varaavat nykyhetken saldoa vaikka "JTSPEC" tarkoittaa, että JT-rivien varauksia ei huomioida
    $jtspec = "JTSPEC2";
  }
  else {
    $jtspec = "JTSPEC";
  }

  if ($toimittaja != '') {
    $toimittajalisa .= " JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = tilausrivi.yhtio and tuotteen_toimittajat.tuoteno = tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '$toimittajaid') ";
  }

  if ($tilaus_on_jo == "KYLLA" and $asiakasid != '') {
    $laskulisa .= " and lasku.liitostunnus = '$asiakasid' ";
  }
  elseif ($tilaus_on_jo == "" and $asiakasid != '') {
    $laskulisa .= " and lasku.liitostunnus = '$asiakasid' ";
  }
  elseif ($tilaus_on_jo == "" and $asiakasno != '') {
    $laskulisa .= " and lasku.ytunnus = '$asiakasno' ";
  }

  if (isset($tuotenumero) and $tuotenumero != '') {
    $tuotteet = explode("\n", $tuotenumero);
    $tuoterajaus = "";
    foreach ($tuotteet as $tuotenumero) {
      if (pupesoft_cleanstring($tuotenumero) != '') {
        $tuoterajaus .= "'".pupesoft_cleanstring($tuotenumero)."',";
      }
    }

    if ($tuoterajaus != "") {
      $tilausrivilisa .= "and tilausrivi.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
    }
  }

  if ($tilaus != '') {
    $tilausrivilisa .= " and tilausrivi.otunnus = '$tilaus' ";
  }

  if (count($suoratoimitus_rivit) > 0) {
    $tilausrivilisa .= " and tilausrivi.tunnus in (".implode(",", $suoratoimitus_rivit).") ";
  }

  if ($tilaus_on_jo == "KYLLA" and $toim == 'SIIRTOLISTA' and $tilaus_on_jo_row['clearing'] != '') {
    $laskulisa .= " and lasku.clearing = '$tilaus_on_jo_row[clearing]' ";
  }

  if ($tuoteryhma != '') {
    $tuotelisa .= " and tuote.try = '$tuoteryhma' ";
  }

  if ($tuoteosasto != '') {
    $tuotelisa .= " and tuote.osasto = '$tuoteosasto' ";
  }

  if (!empty($ei_tehdastoimitus_tuotteita)) {
    $tuotelisa .= " and tuote.status != 'T' ";
  }

  if ($tuotemerkki != '') {
    $tuotelisa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
  }

  if ($maa != '') {
    $laskulisa .= " and lasku.maa = '$maa' ";
  }

  if ($myyja != '') {
    $laskulisa .= " and lasku.myyja = '{$myyja}' ";
  }

  if ($yhtiorow['jt_toimitus_varastorajaus'] == 'K' and count($suoratoimitus_rivit) == 0) {
    if (count($varastosta) > 0 and trim(implode(", ", $varastosta)) != '') {
      $laskulisa .= " and lasku.varasto in (0, ".implode(", ", $varastosta).") ";
    }
    else {
      // Jos ei saada mitään järkevää inputtia, niin riipaistaan tyhjää sitten
      $laskulisa .= " and lasku.varasto = 0 ";
    }
  }

  if ($automaaginen != '' or $ei_limiittia != '') {
    $limit = "";
  }
  else {
    $limit = " LIMIT 1000 ";
  }

  $saatanat_chk = array();

  switch ($jarj) {
  case "ytunnus":
    $order = " ORDER BY lasku.ytunnus, tuote.tuoteno";
    break;
  case "tuoteno":
    $order = " ORDER BY tuote.tuoteno, lasku.ytunnus";
    break;
  case "luontiaika":
    $order = " ORDER BY lasku.luontiaika, tuote.tuoteno, lasku.ytunnus";
    break;
  case "toimaika":
    $order = " ORDER BY lasku.toimaika, tuote.tuoteno, lasku.ytunnus";
    break;
  default:
    $order = " ORDER BY lasku.tunnus";
    break;
  }

  if ($yhtiorow["varaako_jt_saldoa"] != "") {
    $lisavarattu = " + tilausrivi.varattu";
  }
  else {
    $lisavarattu = "";
  }

  if (!empty($yhtiorow["saldo_kasittely"])) {
    $saldoaikalisa = date("Y-m-d");
  }
  else {
    $saldoaikalisa = "";
  }

  // Jos ollaan automaattisesti toimittamassa JT-rivejä ja halutaan vaan toimittaa vain osatoimituskiellossa olevia tilauksia
  if ($yhtiorow["automaattinen_jt_toimitus"] == "U" and $from_varastoon_inc == "varastoon.inc") {
    $laskulisa .= " and lasku.osatoimitus != '' ";
  }

  $summarajauslisa = '';
  $summarajausfail = '';
  $query_ale_lisa = generoi_alekentta('M');

  if (in_array($jarj, array("ytunnus", "tuoteno", "luontiaika", "toimaika"))) {
    if (isset($summarajaus) and $summarajaus != '') {
      $summarajaus = (float) $summarajaus;

      // jos on annettuna summarajaus, niin katsotaan ylittääkö asiakkaan kaikkien tilausrivien yhteishinta tämän rajan
      // näytetään vaan kaikkien summarajan ylittäneiden asiakkaiden rivit (summarajauslisa)
      if ($toim == "ENNAKKO") {
        $query = "SELECT lasku.liitostunnus, sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) hintarajaus
                  FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
                  JOIN lasku use index (PRIMARY) ON (lasku.yhtio=tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus and ((lasku.tila = 'E' and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'X')) $laskulisa)
                  JOIN tuote use index (tuoteno_index) ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno $tuotelisa)
                  $toimittajalisa
                  WHERE tilausrivi.yhtio         = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi          = 'E'
                  and tilausrivi.var            != 'O'
                  and tilausrivi.laskutettuaika  = '0000-00-00'
                  and tilausrivi.varattu         > 0
                  and ((tilausrivi.tunnus = tilausrivi.perheid and tilausrivi.perheid2 = 0) or (tilausrivi.tunnus = tilausrivi.perheid2) or (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0))
                  $tilausrivilisa
                  GROUP BY lasku.liitostunnus
                  HAVING hintarajaus > '$summarajaus'
                  $limit";
      }
      else {
        $query = "SELECT lasku.liitostunnus, sum(tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa}) hintarajaus
                  FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                  JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus and ((lasku.tila = 'N' and lasku.alatila != '') or lasku.tila != 'N') $laskulisa)
                  JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $tuotelisa)
                  $toimittajalisa
                  WHERE tilausrivi.yhtio     = '$kukarow[yhtio]'
                  and tilausrivi.tyyppi      in ('L','G')
                  and tilausrivi.var         = 'J'
                  and tilausrivi.keratty     = ''
                  and tilausrivi.uusiotunnus = 0
                  and tilausrivi.kpl         = 0
                  and tilausrivi.jt $lisavarattu  > 0
                  and ((tilausrivi.tunnus=tilausrivi.perheid and tilausrivi.perheid2=0) or (tilausrivi.tunnus=tilausrivi.perheid2) or (tilausrivi.perheid=0 and tilausrivi.perheid2=0))
                  $tilausrivilisa
                  GROUP BY lasku.liitostunnus
                  HAVING hintarajaus > '$summarajaus'
                  $limit";
      }
      $summarajausres = pupe_query($query);

      // loopataan asiakkaan tunnukset talteen
      if (mysql_num_rows($summarajausres) > 0) {
        $summarajauslisa = " and lasku.liitostunnus in (";

        while ($summarajausrow = mysql_fetch_assoc($summarajausres)) {
          $summarajauslisa .= "$summarajausrow[liitostunnus],";
        }

        $summarajauslisa = substr($summarajauslisa, 0, -1);
        $summarajauslisa .= ")";
      }
      else {
        echo "<font class='error'>", t("Hintarajauksella ei löytynyt yhtään tilausta"), "!</font><br/><br/>";
        $tee         = '';
        $tilaus_on_jo     = '';
        $from_varastoon_inc = '';
        $summarajausfail   = 'fail';
      }
    }
  }

  if (in_array($jarj, array("ytunnus", "tuoteno", "luontiaika", "toimaika")) and $summarajausfail == '') {

    $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

    if ($jt_tyyppi == 'j_manual') {
      $j_manual_lisa = " and tilausrivin_lisatiedot.jt_manual != '' ";
    }
    elseif ($jt_tyyppi == 'j') {
      $j_manual_lisa = " and tilausrivin_lisatiedot.jt_manual = '' ";
    }
    else {
      $j_manual_lisa = '';
    }

    if ($jt_tyyppi == 'j_muiden_mukana') {
      $j_muiden_mukana_lisa = " and tilausrivi.kerayspvm > CURRENT_DATE ";
    }
    elseif ($jt_tyyppi == 'j') {
      $j_muiden_mukana_lisa = ' and tilausrivi.kerayspvm <= CURRENT_DATE ';
    }
    else {
      $j_muiden_mukana_lisa = '';
    }

    // haetaan vain tuoteperheiden isät tai sellaset tuotteet jotka eivät kuulu tuoteperheisiin
    if ($toim == "ENNAKKO") {
      $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tilaajanrivinro,
                lasku.ytunnus,
                tilausrivi.varattu jt,
                lasku.nimi,
                lasku.nimitark,
                lasku.toim_nimi,
                lasku.viesti,
                tilausrivi.tilkpl,
                tilausrivi.hinta,
                {$ale_query_select_lisa}
                lasku.tunnus ltunnus,
                tilausrivi.tunnus tunnus,
                tuote.ei_saldoa,
                tilausrivi.perheid,
                tilausrivi.perheid2,
                tilausrivi.otunnus,
                lasku.clearing,
                lasku.varasto,
                tuote.yksikko,
                tilausrivi.toimaika ttoimaika,
                lasku.toimaika ltoimaika,
                lasku.toimvko,
                lasku.osatoimitus,
                lasku.valkoodi,
                lasku.vienti_kurssi,
                lasku.liitostunnus,
                tilausrivi.hinta * (tilausrivi.varattu + tilausrivi.jt) * {$query_ale_lisa} jt_rivihinta,
                tilausrivi.jaksotettu,
                tuote.status,
                lasku.jtkielto,
                '' as jt_manual
                FROM tilausrivi use index (yhtio_tyyppi_laskutettuaika)
                JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio
                  and lasku.tunnus             = tilausrivi.otunnus
                  and ((lasku.tila = 'E' and lasku.alatila = 'A') or (lasku.tila = 'L' and lasku.alatila = 'X'))
                  $laskulisa
                  $summarajauslisa)
                JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                  and tuote.tuoteno            = tilausrivi.tuoteno
                  $tuotelisa)
                $toimittajalisa
                WHERE tilausrivi.yhtio         = '$kukarow[yhtio]'
                and tilausrivi.tyyppi          = 'E'
                and tilausrivi.var            != 'O'
                and tilausrivi.laskutettuaika  = '0000-00-00'
                and tilausrivi.varattu         > 0
                and ((tilausrivi.tunnus = tilausrivi.perheid and tilausrivi.perheid2 = 0)
                  or (tilausrivi.tunnus = tilausrivi.perheid2)
                  or (tilausrivi.perheid = 0 and tilausrivi.perheid2 = 0))
                $tilausrivilisa
                $order
                $limit";
    }
    elseif ($tilaus_on_jo != '' and $automaattinen_poiminta != '') {
      // Pitää tarkistaa, että automaattisessa rivien poiminnassa olemassa olevalle tilaukselle
      // ei ole asetettu poikkeavaa toimitusosoitetta,
      $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tilaajanrivinro,
                lasku.ytunnus,
                tilausrivi.jt $lisavarattu jt,
                lasku.nimi,
                lasku.nimitark,
                lasku.toim_nimi,
                lasku.viesti,
                tilausrivi.tilkpl,
                tilausrivi.hinta,
                {$ale_query_select_lisa}
                lasku.tunnus ltunnus,
                tilausrivi.tunnus tunnus,
                tuote.ei_saldoa,
                tilausrivi.perheid,
                tilausrivi.perheid2,
                tilausrivi.otunnus,
                lasku.clearing,
                lasku.varasto,
                tuote.yksikko,
                tilausrivi.toimaika ttoimaika,
                lasku.toimaika ltoimaika,
                lasku.toimvko,
                lasku.osatoimitus,
                lasku.valkoodi,
                lasku.vienti_kurssi,
                lasku.liitostunnus,
                tilausrivin_lisatiedot.tilausrivilinkki,
                tilausrivi.hinta
                  * (tilausrivi.varattu + tilausrivi.jt)
                  * {$query_ale_lisa} jt_rivihinta,
                tilausrivi.kerayspvm,
                tilausrivi.jaksotettu,
                tuote.status,
                lasku.jtkielto,
                tilausrivin_lisatiedot.jt_manual
                FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                  AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus {$j_manual_lisa})
                JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio
                  AND lasku.tunnus                            = tilausrivi.otunnus
                  AND (lasku.tila != 'N' OR lasku.alatila != '') $laskulisa $summarajauslisa
                  AND lasku.toim_osoite                       = '{$tilaus_on_jo_row['toim_osoite']}'
                  AND lasku.toim_postino                      = '{$tilaus_on_jo_row['toim_postino']}'
                  AND lasku.toim_postitp                      = '{$tilaus_on_jo_row['toim_postitp']}'
                  AND lasku.toim_maa                          = '{$tilaus_on_jo_row['toim_maa']}')
                JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno                           = tilausrivi.tuoteno $tuotelisa)
                $toimittajalisa
                WHERE tilausrivi.yhtio                        = '$kukarow[yhtio]'
                AND tilausrivi.tyyppi                         IN ('L','G')
                AND tilausrivi.var                            = 'J'
                AND tilausrivi.keratty                        IN ('', 'saldoton')
                AND tilausrivi.uusiotunnus                    = 0
                AND tilausrivi.kpl                            = 0
                AND tilausrivi.jt + tilausrivi.varattu  > 0
                AND ((tilausrivi.tunnus = tilausrivi.perheid AND tilausrivi.perheid2 = 0)
                  OR (tilausrivi.tunnus = tilausrivi.perheid2)
                  OR (tilausrivi.perheid = 0 AND tilausrivi.perheid2 = 0))
                {$j_muiden_mukana_lisa}
                $tilausrivilisa
                $order
                $limit";
    }
    else {
      // Manuaalinen jt-rivien poiminta
      // Eriävä toimitusosoite hyväksytään mutta siitä ilmoitetaan
      $query = "SELECT tilausrivi.tuoteno,
                tilausrivi.nimitys,
                tilausrivi.tilaajanrivinro,
                lasku.ytunnus,
                tilausrivi.jt $lisavarattu jt,
                lasku.nimi,
                lasku.nimitark,
                lasku.toim_nimi,
                lasku.viesti,
                tilausrivi.tilkpl,
                tilausrivi.hinta,
                {$ale_query_select_lisa}
                lasku.tunnus ltunnus,
                tilausrivi.tunnus tunnus,
                tuote.ei_saldoa,
                tilausrivi.perheid,
                tilausrivi.perheid2,
                tilausrivi.otunnus,
                lasku.clearing,
                lasku.varasto,
                tuote.yksikko,
                tilausrivi.toimaika ttoimaika,
                lasku.toimaika ltoimaika,
                lasku.toimvko,
                lasku.osatoimitus,
                lasku.valkoodi,
                lasku.vienti_kurssi,
                lasku.liitostunnus,
                tilausrivin_lisatiedot.tilausrivilinkki,
                tilausrivin_lisatiedot.korvamerkinta,
                tilausrivi.hinta
                  * (tilausrivi.varattu + tilausrivi.jt)
                  * {$query_ale_lisa} jt_rivihinta,
                tilausrivi.kerayspvm,
                lasku.toim_nimitark,
                lasku.toim_osoite,
                lasku.toim_postino,
                lasku.toim_postitp,
                lasku.toim_maa,
                tilausrivi.jaksotettu,
                tuote.status,
                lasku.jtkielto,
                tilausrivin_lisatiedot.jt_manual
                FROM tilausrivi use index (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                  AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus {$j_manual_lisa})
                JOIN lasku use index (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio
                  AND lasku.tunnus                            = tilausrivi.otunnus
                  AND (lasku.tila != 'N' OR lasku.alatila != '') $laskulisa $summarajauslisa)
                JOIN tuote use index (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                  AND tuote.tuoteno                           = tilausrivi.tuoteno $tuotelisa)
                $toimittajalisa
                WHERE tilausrivi.yhtio                        = '$kukarow[yhtio]'
                AND tilausrivi.tyyppi                         IN ('L','G')
                AND tilausrivi.var                            = 'J'
                AND tilausrivi.keratty                        IN ('', 'saldoton')
                AND tilausrivi.uusiotunnus                    = 0
                AND tilausrivi.kpl                            = 0
                AND tilausrivi.jt  + tilausrivi.varattu  > 0
                AND ((tilausrivi.tunnus = tilausrivi.perheid AND tilausrivi.perheid2 = 0)
                  OR (tilausrivi.tunnus = tilausrivi.perheid2)
                  OR (tilausrivi.perheid = 0 AND tilausrivi.perheid2 = 0))
                {$j_muiden_mukana_lisa}
                $tilausrivilisa
                $order
                $limit";
    }
    $isaresult = pupe_query($query);
    $jtseluas_rivienmaara = mysql_num_rows($isaresult);

    if (mysql_num_rows($isaresult) > 0) {

      $jt_rivilaskuri  = 1;
      $jt_hintalaskuri = 0;
      $jt_toim_hintalaskuri = 0;
      $saapumisajat    = array();

      while ($jtrow = mysql_fetch_assoc($isaresult)) {

        //tutkitaan onko tämä suoratoimitusrivi
        $onko_suoratoimi = "";

        // Summataan hintaa
        $jt_hintalaskuri += $jtrow["jt_rivihinta"];

        if ($jtrow["tilausrivilinkki"] > 0) {
          $query = "SELECT tunnus
                    FROM tilausrivi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and tyyppi  = 'O'
                    and tunnus  = '$jtrow[tilausrivilinkki]'";
          $sjtres = pupe_query($query);

          if (mysql_num_rows($sjtres) > 0) {
            $onko_suoratoimi = "ON";
          }
        }

        if ($yhtiorow['tee_siirtolista_myyntitilaukselta'] == 'K' and $onko_suoratoimi == '') {
          $query = "SELECT t.tunnus AS siirtolistarivi_tunnus
                    FROM tilausrivin_lisatiedot AS tl
                    JOIN tilausrivi AS t
                    ON ( t.yhtio = tl.yhtio
                      AND t.tunnus          = tl.tilausrivitunnus
                      AND t.tyyppi          = 'G')
                    WHERE tl.yhtio          = '{$kukarow['yhtio']}'
                    AND tl.tilausrivilinkki = {$jtrow['tunnus']}";
          $siirtolista_result = pupe_query($query);

          if (mysql_num_rows($siirtolista_result) > 0) {
            $onko_suoratoimi = "ON";
          }
        }

        // Ei näytetä suoratoimitusrivejä, ellei $suoratoimit ole ruksattu, sillon näytetään pelkästään suoratoimitukset
        // Jos $suoratoimitus_rivit muuttuja on setattu niin huomioidaan sekä normit, että suoratoimit
        if (($onko_suoratoimi == "" and $suoratoimit == "") or ($onko_suoratoimi == "ON" and $suoratoimit != "") or count($suoratoimitus_rivit) > 0) {

          $kokonaismyytavissa = 0;

          if ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {
            if ($jtrow["perheid"] == 0) {
              $pklisa = " and perheid2 = '$jtrow[perheid2]'";
            }
            else {
              $pklisa = " and (perheid = '$jtrow[perheid]' or perheid2 = '$jtrow[perheid]')";
            }

            unset($perherow);

            $query = "SELECT vanhatunnus
                      FROM lasku use index (PRIMARY)
                      WHERE yhtio = '$kukarow[yhtio]'
                      and tunnus  = '$jtrow[ltunnus]'";
            $vtunres = pupe_query($query);
            $vtunrow = mysql_fetch_assoc($vtunres);

            if ($vtunrow["vanhatunnus"] > 0) {
              $query = "SELECT GROUP_CONCAT(distinct tunnus SEPARATOR ',') tunnukset
                        FROM lasku use index (yhtio_vanhatunnus)
                        WHERE yhtio     = '$kukarow[yhtio]'
                        and vanhatunnus = '$vtunrow[vanhatunnus]'";
              $perheresult = pupe_query($query);
              $perherow = mysql_fetch_assoc($perheresult);
            }

            if ($perherow["tunnukset"] != "") {
              $otunlisa = " and tilausrivi.otunnus in ($perherow[tunnukset]) ";
            }
            else {
              $otunlisa = " and tilausrivi.otunnus = '$jtrow[ltunnus]' ";
            }
          }

          // Jos tuote on tuoteperheen isä
          unset($lapsires);

          if ($toim == "ENNAKKO" and ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0)) {
            $query = "SELECT tilausrivi.tuoteno,
                      tilausrivi.nimitys,
                      tilausrivi.varattu jt,
                      tilausrivi.tilkpl,
                      tilausrivi.hinta,
                      {$ale_query_select_lisa}
                      tilausrivi.tunnus tunnus,
                      tuote.ei_saldoa,
                      tilausrivi.perheid,
                      tilausrivi.perheid2,
                      tilausrivi.otunnus,
                      tilausrivi.kerayspvm,
                      tuote.yksikko,
                      lasku.valkoodi,
                      lasku.vienti_kurssi,
                      lasku.tunnus ltunnus,
                      lasku.nimi,
                      lasku.ytunnus,
                      lasku.toim_nimi,
                      lasku.viesti
                      FROM tilausrivi use index (yhtio_otunnus)
                      JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
                      JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                      WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                      and tilausrivi.var     != 'O'
                      $otunlisa
                      $pklisa
                      and tilausrivi.varattu  > 0
                      and tilausrivi.tunnus  != '$jtrow[tunnus]'
                      ORDER BY tilausrivi.tunnus";
            $lapsires = pupe_query($query);
          }
          elseif ($jtrow["perheid"] > 0 or $jtrow["perheid2"] > 0) {
            $query = "SELECT tilausrivi.tuoteno,
                      tilausrivi.nimitys,
                      tilausrivi.jt $lisavarattu jt,
                      tilausrivi.tilkpl,
                      tilausrivi.hinta,
                      {$ale_query_select_lisa}
                      tilausrivi.tunnus tunnus,
                      tuote.ei_saldoa,
                      tilausrivi.perheid,
                      tilausrivi.perheid2,
                      tilausrivi.otunnus,
                      tilausrivi.kerayspvm,
                      tuote.yksikko,
                      lasku.valkoodi,
                      lasku.vienti_kurssi,
                      lasku.tunnus ltunnus,
                      lasku.nimi,
                      lasku.ytunnus,
                      lasku.toim_nimi,
                      lasku.viesti
                      FROM tilausrivi use index (yhtio_otunnus)
                      JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus=tilausrivi.otunnus
                      JOIN tuote use index (tuoteno_index) ON tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno
                      WHERE tilausrivi.yhtio  = '$kukarow[yhtio]'
                      $otunlisa
                      $pklisa
                      and tilausrivi.jt $lisavarattu > 0
                      and tilausrivi.tunnus  != '$jtrow[tunnus]'
                      ORDER BY tilausrivi.tunnus";
            $lapsires = pupe_query($query);
          }

          unset($perherow);
          $perheok = 0;

          //Käsiteltävät rivitunnukset (isä ja mahdolliset lapset)
          $tunnukset = $jtrow["tunnus"].",";

          // Jos meillä on lasku.jtkielto = 'Z' "Asiakkaan jälkitoimituksia ei osatoimiteta", tarkistetaanko saako rivin toimittaa. Ei tarkisteta jos ollaan väkisinhyväksymässä riviä.
          if ($jtrow['jtkielto'] == 'Z' and $loput[$tunnukset] != 'VAKISIN') {
            $voiko_toimittaa = hae_toimitettavat_tilausrivit($jtrow['otunnus'], $varastosta, $jtspec, $toim);
          }
          else {
            $voiko_toimittaa = true;
          }

          //jtrivin jaksotuksen tarkistus tehdään vain kun ollaan tulossa myyntitilaukselta, koska jaksotetut tilausrivit eivät saa mennä väärien myyntitilaus otsikoiden alle.
          //jtrivit pitää kuitenkin pystyä toimittamaan jtselaus ohjelmasta käsin.
          if ($mista_tullaan == 'MYYNTITILAUKSELTA' and !empty($jtrow['jaksotettu'])) {
            //jos käsittelyssä oleva jt-rivi on jaksotettu, voidaan se liittää vain sellaiseen myyntitilaukseen ($myyntitilaus_jaksotettu), jossa jaksotus on sama kuin jtrivin jaksotus
            if ($myyntitilaus_jaksotettu == $jtrow['jaksotettu'] and $voiko_toimittaa) {
              $voiko_toimittaa = true;
            }
            else {
              $voiko_toimittaa = false;
            }
          }

          if (isset($lapsires) and mysql_num_rows($lapsires) > 0) {
            while ($perherow = mysql_fetch_assoc($lapsires)) {
              $lapsitoimittamatta = $perherow["jt"];

              if ($perherow["ei_saldoa"] == "") {

                $jt_saldopvm = "";

                if (!empty($yhtiorow["saldo_kasittely"])) {
                  $jt_saldopvm = date("Y-m-d");
                }

                if ($yhtiorow["saldo_kasittely"] == 'U' and $toim == 'ENNAKKO') {
                  $jt_saldopvm = $jtrow['ttoimaika'];
                }

                foreach ($varastosta as $vara) {
                  list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], $jtspec, $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

                  if ($saldolaskenta == "hyllysaldo") {
                    $lapsitoimittamatta -= $hyllyssa;
                  }
                  else {
                    $lapsitoimittamatta -= $myytavissa;
                  }

                }
              }
              else {
                $lapsitoimittamatta  = 0;
              }

              $tunnukset .= $perherow["tunnus"].",";


              if ($lapsitoimittamatta > 0) {
                //tämän lapsen saldo ei riitä
                $perheok++;
              }
            }
          }

          $tunnukset = substr($tunnukset, 0, -1);

          if ($jtrow["ei_saldoa"] == "") {

            $jt_saldopvm = "";

            if (!empty($yhtiorow["saldo_kasittely"])) {
              $jt_saldopvm = date("Y-m-d");
            }

            if ($yhtiorow["saldo_kasittely"] == 'U' and $toim == 'ENNAKKO') {
              $jt_saldopvm = $jtrow['ttoimaika'];
            }

            foreach ($varastosta as $vara) {
              list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($jtrow["tuoteno"], $jtspec, $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

              if ($saldolaskenta == "hyllysaldo") {
                $kokonaismyytavissa += $hyllyssa;
              }
              else {
                $kokonaismyytavissa += $myytavissa;
              }
            }

            // Tarkistetaan, että yhtiö parametrit on (Jt_automatiikka = "kohdistetaan.." ja Automaattinen_jt_toimitus = "Jälkitoimitusrivit kohdistetaan ja toimitetaan automaattisesti varastoonviennin yhteydessä keräyspäivän mukaisesti")
            if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {

              // Loopata kaikki jt-rivit läpi tuotteen perusteella jat katsoa, onko joku tilannut tuotetta aikaisemmin ja vähentää saldoa jos on
              $jt_muiden_mukana_query = "SELECT tilausrivi.tunnus,
                                         tilausrivi.tuoteno,
                                         tilausrivi.nimitys,
                                         tilausrivi.tilkpl,
                                         tilausrivi.kerayspvm,
                                         tilausrivi.jt + tilausrivi.varattu jt,
                                         lasku.ytunnus,
                                         lasku.nimi,
                                         lasku.toim_nimi
                                         FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
                                         JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                                           AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                                         JOIN lasku USE INDEX (primary) ON (lasku.yhtio = tilausrivi.yhtio
                                           AND lasku.tunnus                            = tilausrivi.otunnus
                                           AND (lasku.tila != 'N' OR lasku.alatila != '') $laskulisa)
                                         JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio
                                           AND tuote.tuoteno                           = tilausrivi.tuoteno)
                                         WHERE tilausrivi.yhtio                        = '{$kukarow['yhtio']}'
                                         AND tilausrivi.tyyppi                         IN ('L', 'G')
                                         AND tilausrivi.tuoteno                        = '{$jtrow['tuoteno']}'
                                         AND tilausrivi.var                            = 'J'
                                         AND tilausrivi.keratty                        = ''
                                         AND tilausrivi.uusiotunnus                    = 0
                                         AND tilausrivi.kpl                            = 0
                                         AND tilausrivi.jt $lisavarattu > 0
                                         AND ((tilausrivi.tunnus = tilausrivi.perheid AND tilausrivi.perheid2 = 0)
                                           OR (tilausrivi.tunnus = tilausrivi.perheid2)
                                           OR (tilausrivi.perheid = 0 AND tilausrivi.perheid2 = 0))
                                         ORDER BY lasku.luontiaika";
              $jt_muiden_mukana_result = pupe_query($jt_muiden_mukana_query);

              while ($jt_muiden_mukana_row = mysql_fetch_assoc($jt_muiden_mukana_result)) {

                // Jos ennen tätä käsittelyssä olevaa riviä (jtrow) löytyy tilauksia, joissa on jt rivejä nämä varaavat saldoa
                if ($jt_muiden_mukana_row['tunnus'] != $jtrow['tunnus']) {
                  $kokonaismyytavissa -= $jt_muiden_mukana_row['jt'];
                }
                else {
                  // Tuli jtrow vuoro. $kokonaismyytavissa pitää sisällään nyt todellisen myytävissä olevan saldon. breakataan
                  // Mutta jos jtrow on jt-muiden mukana, niin sitä ei voida toimittaa eli $kokonaismyytavissa = 0;
                  // Mutta jos ollaan tulossa esim pikatilauksesta niin myytavissa oleva saldo pitää pystyä lisäämään uudelle tilaukselle eli iffiin ei mennä
                  if ($jtrow['kerayspvm'] > date('Y-m-d') and empty($tilaus_on_jo)) {
                    $kokonaismyytavissa = 0;
                  }
                  break;
                }
              }
            }
          }

          // Saldoa on tai halutaan nähdä kaikki rivit
          if ($kokonaismyytavissa > 0 or $toimi == '') {

            // Lasktetaan toimitettavat yhteensä
            if ($kokonaismyytavissa > 0) {
              $jt_toim_hintalaskuri += $jtrow["jt_rivihinta"];
            }

            //Tulostetaan otsikot
            if ($automaaginen == '' and $jt_rivilaskuri == 1) {

              echo "<table>";
              echo "<tr>";
              echo "<th>#</th>";
              echo "<th valign='top'>".t("Tuoteno");
              echo "<br>".t("Nimitys");
              echo "</th>";

              if ($tilaus_on_jo == "") {
                echo "<th valign='top'>".t("Ytunnus")."<br>".t("Nimi")."<br>".t("Toim_Nimi")."</th>";
              }

              echo "<th valign='top'>".t("Tilausnro")."<br>".t("Viesti.")."</th>";
              echo "<th valign='top'>".t("JT")."<br>".t("Hinta")."<br>";

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                echo t("Ale"), "{$alepostfix}<br />";
              }

              echo "</th>";

              echo "<th valign='top'>".t("Status")."<br>".t("Toimaika")."<br/>".t("Saapumispäivä")."</th>";

              if ($kukarow['extranet'] == '' and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') echo "<th valign='top'>", t("Tyyppi"), "</th>";

              if ($jtselaus_paivitys_oikeus) {
                if ($kukarow["extranet"] == "") {
                  echo "<th valign='top'>".t("Toimita")."<br>".t("kaikki")."</th>";
                  echo "<th valign='top'>".t("Toimita")."<br>".t("määrä")."</th>";
                  echo "<th valign='top'>".t("Poista")."<br>".t("loput")."</th>";
                  echo "<th valign='top'>".t("Jätä")."<br>".t("loput")."</th>";
                  echo "<th valign='top'>".t("Mitätöi")."<br>".t("rivi")."</th>";
                  echo "<th valign='top'>".t("Hyväksy")."<br>".t("väkisin")."</th>";
                }
                else {
                  echo "<th valign='top'>".t("Toimita")."</th>";
                  if ($kukarow["extranet"] != "" and $yhtiorow["jt_rivien_kasittely"] != 'E') {
                    echo "<th valign='top'>".t("Mitätöi")."</th>";
                  }
                  echo "<th valign='top'>".t("Älä tee mitään")."</th>";
                }
              }

              echo "</tr>";

              if ($jtselaus_paivitys_oikeus) {

                echo "  <script type='text/javascript' language='JavaScript'>
                    <!--
                      function update_params(KORVATTAVA, KORVAAVA, TILRIVTUNNUS) {
                        document.getElementById('korvattava_tilriv').value = TILRIVTUNNUS;
                        document.getElementById('korvattava').value = KORVATTAVA;
                        document.getElementById('korvaava').value = KORVAAVA;
                        document.getElementById('korvataanko').value = 'KORVAA';
                      }
                    -->
                    </script>";


                echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
                  <!--

                  function toggleAll(toggleBox) {

                    var currForm = toggleBox.form;
                    var isChecked = toggleBox.checked;
                    var nimi = toggleBox.name;

                    for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
                      if (currForm.elements[elementIdx].type == 'radio' && currForm.elements[elementIdx].name.substring(0,5) == 'loput' && currForm.elements[elementIdx].value == nimi) {
                        currForm.elements[elementIdx].checked = isChecked;
                      }
                    }
                  }

                  (function () {
                    window.onload = function() {
                      var buttons = document.getElementsByName('poistaValintaNappi');

                      for (var i = 0;i < buttons.length;i++) {
                        buttons[i].addEventListener('click', poistaValinnat, false);
                      }

                      function poistaValinnat(e) {
                        e.preventDefault();

                        var row = e.target.parentNode.parentNode;
                        var inputs = row.getElementsByTagName('input');

                        for (var i = 0;i < inputs.length;i++) {
                          var input = inputs[i];

                          if (input.type === 'radio') {
                            input.checked = false;
                          }
                        }
                      }
                    };
                  })();

                  //-->
                  </script>";


                echo "<form method='post'>";
                echo "<input type='hidden' name='maa' value='$maa'>";
                echo "<input type='hidden' name='myyja' value='{$myyja}' />";

                // Nämä ovat niitä hiddeneitä mitä ylläoleva js muokkaa (korvaa-nappi).
                echo "<input type='hidden' name='korvattava_tilriv' id='korvattava_tilriv' value=''>";
                echo "<input type='hidden' name='korvattava' id='korvattava' value=''>";
                echo "<input type='hidden' name='korvaava' id='korvaava' value=''>";
                echo "<input type='hidden' name='korvataanko' id='korvataanko' value=''>";

                if ($tilaus_on_jo == "") {
                  echo "<input type='hidden' name='tee' value='POIMI'>";
                  echo "<input type='hidden' name='toim' value='$toim'>";
                  echo "<input type='hidden' name='jarj' value='$jarj'>";
                  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
                  echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
                  echo "<input type='hidden' name='asiakasno' value='$asiakasno'>";
                  echo "<input type='hidden' name='toimittaja' value='$toimittaja'>";
                  echo "<input type='hidden' name='toimi' value='$toimi'>";
                  echo "<input type='hidden' name='ei_limiittia' value='$ei_limiittia'>";
                  echo "<input type='hidden' name='suoratoimit' value='$suoratoimit'>";
                  echo "<input type='hidden' name='tuotenumero' value='$tuotenumero'>";
                  echo "<input type='hidden' name='tilaus' value='$tilaus'>";
                  echo "<input type='hidden' name='jt_huomioi_pvm' value='$jt_huomioi_pvm'>";

                  if (is_array($varastosta)) {
                    foreach ($varastosta as $vara) {
                      echo "<input type='hidden' name='varastosta[$vara]' value='$vara'>";
                    }
                  }

                  //Tehdään apumuuttuja jotta muokkaa_rivi linkki toimisi kunnolla
                  $tilausnumero = "";

                  if (is_array($varastosta)) {
                    foreach ($varastosta as $vara) {
                      $tilausnumero .= $vara."##";
                    }
                    $tilausnumero = substr($tilausnumero, 0, -2);
                  }
                }
                else {
                  if ($kukarow["extranet"] == "") {

                    $args = array(
                      'asiakasid' => $asiakasid,
                      'maa' => $asiakasmaa,
                    );

                    $yhtiotoimipaikka = tilauksen_toimipaikka($args);

                    $params = array(
                      'asiakas_tunnus' => $asiakasid,
                      'toimipaikka_tunnus' => $yhtiotoimipaikka,
                      'toimitus_maa' => $asiakasmaa,
                      'varastotyyppi' => 'kaikki_varastot',
                    );

                    $varastot = sallitut_varastot($params);

                    $query = "SELECT *
                              FROM varastopaikat
                              WHERE yhtio = '$kukarow[yhtio]'
                              AND tunnus  in (".implode(",", $varastot).")
                              ORDER BY tyyppi, nimitys";
                    $vtresult = pupe_query($query);

                    if (mysql_num_rows($vtresult) > 1) {
                      echo "<b>".t("Näytä saatavuus vain varastosta").": </b> <select name='vainvarastosta' onchange='submit();'>";
                      echo "<option value=''>".t("Kaikki varastot")."</option>";

                      while ($vrow = mysql_fetch_assoc($vtresult)) {
                        if ($vrow["tyyppi"] != 'E' or (isset($kukarow["varasto"]) and (int) $kukarow["varasto"] > 0 and in_array($vrow["tunnus"], explode(",", $kukarow['varasto'])))) {

                          $sel = "";
                          if ($vainvarastosta == $vrow["tunnus"]) {
                            $sel = 'SELECTED';
                          }

                          echo "<option value='$vrow[tunnus]' $sel>$vrow[nimitys]</option>";
                        }
                      }

                      echo "</select>";

                    }
                    echo "<input type='hidden' name='jt_kayttoliittyma' value='kylla'>";
                  }

                  echo "  <input type='hidden' name='toim' value='$toim'>
                      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
                      <input type='hidden' name='tee'  value='JT_TILAUKSELLE'>
                      <input type='hidden' name='tila' value='jttilaukseen'>";
                }
              }
            }

            if ($automaaginen == '') {
              // Tuoteperheiden lapsille ei näytetä rivinumeroa
              if ($jtrow["perheid"] == $jtrow["tunnus"] or ($jtrow["perheid2"] == $jtrow["tunnus"] and $jtrow["perheid"] == 0)) {
                $query = "SELECT count(*) kpl
                          from tilausrivi
                          where yhtio = '$kukarow[yhtio]'
                          $otunlisa
                          $pklisa";
                $pkres = pupe_query($query);
                $pkrow = mysql_fetch_assoc($pkres);

                $pknum     = $pkrow['kpl'];
                $borderlask = $pkrow['kpl'];

                echo "<tr class='aktiivi'><td valign='top' rowspan='$pknum' style='border-top: 1px solid; border-left: 1px solid; border-bottom: 1px solid;' >$jt_rivilaskuri</td>";
              }
              elseif ($jtrow["perheid"] == 0 and $jtrow["perheid2"] == 0) {
                $pkrow     = array('kpl' => 0);
                $pknum     = 0;
                $borderlask = 0;

                echo "<tr class='aktiivi'><td valign='top'>$jt_rivilaskuri</td>";
              }

              $classlisa   = "";
              $class     = "";

              if ($borderlask == 1 and $pkrow['kpl'] == 1 and $pknum == 1) {
                $classlisa = " style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
                $class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

                $borderlask--;
              }
              elseif ($borderlask == $pkrow['kpl'] and $pkrow['kpl'] > 0) {
                $classlisa = " style='border-top: 1px solid; border-right: 1px solid;' ";
                $class    .= " style='border-top: 1px solid;' ";
                $borderlask--;
              }
              elseif ($borderlask == 1) {
                if ($pknum > 1) {
                  $classlisa =" style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
                  $class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
                }
                else {
                  $classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
                  $class    .= " style='border-bottom: 1px solid;' ";
                }

                $borderlask--;
              }
              elseif ($borderlask > 0 and $borderlask < $pknum) {
                $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
                $class    .= " style='font-style:italic;' ";
                $borderlask--;
              }

              if ($kukarow["extranet"] == "") {
                echo "<td valign='top' $class>$ins <a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($jtrow["tuoteno"])."'>$jtrow[tuoteno]</a>";
              }
              else {
                echo "<td valign='top' $class>$ins $jtrow[tuoteno]";
              }

              echo "<br>$jtrow[nimitys]";
              echo "</td>";

              if ($tilaus_on_jo == "") {
                echo "<td valign='top' $class>$jtrow[ytunnus]<br>";

                $_nimitark = "";
                if (!empty($jtrow["nimitark"])) {
                  $_nimitark = "<br>".$jtrow["nimitark"];
                }

                if ($kukarow["extranet"] == "") {
                  echo "<a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=NAYTATILAUS&tunnus=$jtrow[ltunnus]'>$jtrow[nimi]$_nimitark</a><br>";
                }
                else {
                  echo "$jtrow[nimi]$_nimitark<br>";
                }

                $_toim_nimitark = "";
                if (!empty($jtrow["toim_nimitark"])) {
                  $_toim_nimitark = "<br>".$jtrow["toim_nimitark"];
                }

                echo "$jtrow[toim_nimi]$_toim_nimitark";

                if (!isset($saatanat_chk[$jtrow['ytunnus']])) {

                  // Parametrejä saatanat.php:lle
                  $sytunnus       = $jtrow['ytunnus'];
                  $sliitostunnus   = $jtrow['liitostunnus'];
                  $eiliittymaa    = "ON";
                  $luottorajavirhe = "";
                  $jvvirhe      = "";
                  $ylivito      = 0;
                  $trattavirhe    = "";
                  $laji        = "MA";
                  $grouppaus       = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";

                  ob_start();
                  require "raportit/saatanat.php";
                  ob_end_clean();

                  $saatanat_chk[$sytunnus] = array($luottorajavirhe, $jvvirhe, $ylivito, $trattavirhe);
                }
                else {
                  list($luottorajavirhe, $jvvirhe, $ylivito, $trattavirhe) = $saatanat_chk[$jtrow['ytunnus']];
                }

                if ($luottorajavirhe != '' or $jvvirhe != '' or $ylivito > 0 or $trattavirhe != '') {
                  echo "<br/>";
                }

                if ($luottorajavirhe != '') {
                  echo "<br/>";
                  echo "<font class='message'>", t("Luottoraja ylittynyt"), "</font>";
                }

                if ($jvvirhe != '') {
                  echo "<br/>";
                  echo "<font class='message'>", t("Tämä on jälkivaatimusasiakas"), "</font>";
                }

                if ($ylivito > 0) {
                  echo "<br/>";
                  echo "<font class='message'>".t("Yli %s pv sitten erääntyneitä laskuja", $kukarow['kieli'], $yhtiorow['erapaivan_ylityksen_raja'])."</font>";
                }

                if ($trattavirhe != '') {
                  echo "<br/>";
                  echo "<font class='message'>".t("Asiakkaalla on maksamattomia trattoja")."<br></font>";
                }

                echo "</td>";
              }

              echo "<td valign='top' $class>$jtrow[otunnus]<br>$jtrow[viesti]</td>";

              if ($jtselaus_paivitys_oikeus and $kukarow["extranet"] == "") {
                echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$jtrow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&ei_limiittia=$ei_limiittia&suoratoimit=$suoratoimit&tuotenumero=$tuotenumero&tilaus=$tilaus&jarj=$jarj&tilausnumero=$tilausnumero'>".($jtrow["jt"]*1)."</a><br>";
              }
              else {
                echo "<td valign='top' align='right' $class>".($jtrow["jt"]*1)."<br>";
              }

              if ($jtrow["valkoodi"] != '' and trim(strtoupper($jtrow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
                $hinta  = hintapyoristys(laskuval($jtrow["hinta"], $jtrow["vienti_kurssi"]))." ".$jtrow["valkoodi"];
              }
              else {
                $hinta  = hintapyoristys($jtrow["hinta"])." ".$jtrow["valkoodi"];
              }

              echo "$hinta<br>";

              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                if ($jtrow["ale{$alepostfix}"] > 0) {
                  if ($alepostfix > 1) echo "+";
                  echo ($jtrow["ale{$alepostfix}"]*1), "%<br>";
                }
              }

              if (!empty($jtrow['korvamerkinta'])) {

                if ($jtrow['korvamerkinta'] == '.') {
                  $luokka = '';
                }
                else {
                  $luokka = 'tooltip';
                }

                echo "<img src='{$palvelin2}pics/lullacons/info.png' class='{$luokka}' id='{$jtrow['tunnus']}_info'>";
                echo "<div id='div_{$jtrow['tunnus']}_info' class='popup'>";
                echo $jtrow['korvamerkinta'];
                echo "</div>";
              }

              echo "</td>";
            }

            if ($jtselaus_paivitys_oikeus) {
              if ($toim == "ENNAKKO") {
                $query = "SELECT sum(varattu) jt, count(*) kpl
                          FROM tilausrivi use index (yhtio_tyyppi_tuoteno_varattu)
                          WHERE yhtio  = '$kukarow[yhtio]'
                          and tyyppi   = 'E'
                          and var     != 'O'
                          and tuoteno  = '$jtrow[tuoteno]'
                          and varattu  > 0";
              }
              else {
                $query = "SELECT sum(jt $lisavarattu) jt, count(*) kpl
                          FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
                          WHERE yhtio        = '$kukarow[yhtio]'
                          and tyyppi         in ('L','G')
                          and tuoteno        = '$jtrow[tuoteno]'
                          and jt $lisavarattu > 0
                          and kpl            = 0
                          and laskutettuaika = 0
                          and var            = 'J'";
              }

              $juresult = pupe_query($query);
              $jurow    = mysql_fetch_assoc($juresult);

              if (strtotime($jtrow['ttoimaika']) == strtotime($jtrow['ltoimaika'])) {
                unset($toimvko);
                unset($toimpva);

                if ($jtrow['toimvko'] > 0 and $jtrow['toimvko'] != 7) {
                  $toimvko = date("W", strtotime($jtrow['ltoimaika']));
                  $toimpva = date("N", strtotime($jtrow['ltoimaika']));
                }
                elseif ($jtrow['toimvko'] > 0 and $jtrow['toimvko'] == 7) {
                  $toimvko = date("W", strtotime($jtrow['ltoimaika']));
                }

                $toimaika = $jtrow['ltoimaika'];
              }
              else {
                unset($toimvko);
                unset($toimpva);
                $toimaika = $jtrow['ttoimaika'];
              }

              $kaikki_check = '';
              $poista_check = '';
              $jata_check = '';
              $mita_check = '';

              if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'KAIKKI') {
                $kaikki_check = 'checked';
              }

              if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'POISTA') {
                $poista_check = 'checked';
              }

              if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'JATA') {
                $jata_check = 'checked';
              }

              if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'MITA') {
                $mita_check = 'checked';
              }

              if (isset($loput[$tunnukset]) and $loput[$tunnukset] == 'VAKISIN') {
                $mita_check = 'checked';
              }

              if (!isset($kpl[$tunnukset])) $kpl[$tunnukset] = "";

              if ($automaaginen == '' and !isset($saapumisajat[$jtrow['tuoteno']])) {

                $tulossalisat = hae_tuotteen_saapumisaika($jtrow['tuoteno'], $jtrow['status'], $kokonaismyytavissa);
                $saapumisajat[$jtrow['tuoteno']] = "";

                foreach ($tulossalisat as $tulossalisa) {
                  list($o, $v) = explode("!¡!", $tulossalisa);
                  $saapumisajat[$jtrow['tuoteno']] .= "<br>$o ".strip_tags($v);
                }
              }

              // Riittää kaikille
              if (($jtrow['jt_manual'] == '' or ($jtrow['jt_manual'] != '' and $mista_tullaan != 'MYYNTITILAUKSELTA' and $automaaginen == 'tosi_automaaginen' and $from_varastoon_inc == "")) and (($kokonaismyytavissa >= $jurow["jt"] or $jtrow["ei_saldoa"] != "") and $perheok == 0 and $voiko_toimittaa !== false) or $automaaginen == 'vakisin') {

                // Jos haluttiin toimittaa tämä rivi automaagisesti
                if (($jtrow['jt_manual'] == '' or ($jtrow['jt_manual'] != '' and $mista_tullaan != 'MYYNTITILAUKSELTA' and $automaaginen == 'tosi_automaaginen' and $from_varastoon_inc == "")) and ($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and ($automaaginen == 'automaaginen' or $automaaginen == 'tosi_automaaginen' or $automaaginen == 'vakisin')) {

                  if ($from_varastoon_inc == "editilaus_in.inc") {
                    $edi_ulos .= "\n".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lisättiin tilaukseen")."!";
                  }
                  else {
                    echo "<font class='message'>".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lisättiin tilaukseen").". (".t("Tuotetta riitti kaikille JT-riveille").")</font><br>";
                  }

                  if ($automaaginen == 'vakisin') {
                    $loput[$tunnukset]   = "VAKISIN";
                  }
                  else {
                    $loput[$tunnukset]   = "KAIKKI";
                  }

                  $kpl[$tunnukset]   = 0;
                  $tunnusarray     = explode(',', $tunnukset);

                  // Toimitetaan jtrivit
                  tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimitus_paikat, $tilaus_on_jo, $varastosta, $jt_huomioi_pvm, $mista_tullaan);

                  $jt_rivilaskuri++;
                }
                else {
                  echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

                  if ($kukarow["extranet"] == "") {

                    echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:green;'>".t("Riittää kaikille")."!</font><br>";

                    if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
                      echo t("Viikko")." $toimvko";
                    }
                    elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
                      echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
                    }
                    else {
                      echo tv1dateconv($toimaika);
                      echo $saapumisajat[$jtrow['tuoteno']];
                    }

                    echo "</td>";

                    if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                      echo "<td valign='top' {$class}>";

                      if ($jtrow['jt_manual'] != '') {
                        echo t("JT manuaalinen");
                      }
                      elseif (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $jtrow['kerayspvm'] > date('Y-m-d')) {
                        echo t("JT muiden mukana");
                      }
                      else {
                        echo t("JT");

                        if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                          echo " ".t("heti");
                        }
                      }

                      echo "</td>";
                    }

                    echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI' $kaikki_check></td>";

                    if ($jtrow["osatoimitus"] == "") {
                      echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='{$kpl[$tunnukset]}'></td>";
                      echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
                      echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
                    }
                    else {
                      echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
                    }

                    echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
                    echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
                    echo "<td class='back'><button name='poistaValintaNappi'>" . t("Poista valinta") . "</button></td>";
                  }
                  elseif ($kukarow["extranet"] != "") {
                    echo "<td valign='top' $class><font style='color:green;'>".t("Voidaan toimittaa")."!</font></td>";

                    if ((int) $kukarow["kesken"] > 0) {
                      echo "  <td valign='top' align='center' $class>".t("Toimita")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI'></td>";
                    }
                    else {
                      echo "<td valign='top' $class>".t("Avaa uusi tilaus jotta voit toimittaa rivin").".</td>";
                    }

                    if ($yhtiorow["jt_rivien_kasittely"] != 'E') {
                      echo "<td valign='top' align='center' $class>".t("Mitätöi")."<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
                    }
                    echo "<td valign='top' align='center' $classlisa>".t("Älä tee mitään")."<input type='radio' name='loput[$tunnukset]' value=''></td>";

                  }

                  $jt_rivilaskuri++;
                }
              }
              // Riittää tälle riville mutta ei kaikille
              elseif (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and $kokonaismyytavissa >= $jtrow["jt"] and $perheok == 0 and $voiko_toimittaa !== false and ($jtrow['jt_manual'] == '' or ($jtrow['jt_manual'] != '' and $mista_tullaan != 'MYYNTITILAUKSELTA' and $from_varastoon_inc == ""))) {

                // Jos haluttiin toimittaa tämä rivi automaagisesti
                if (($kukarow["extranet"] == "" or ($kukarow['extranet'] != '' and $automaattinen_poiminta != '')) and $automaaginen == 'tosi_automaaginen' and ($jtrow['jt_manual'] == '' or ($jtrow['jt_manual'] != '' and $mista_tullaan != 'MYYNTITILAUKSELTA' and $from_varastoon_inc == ""))) {

                  if ($from_varastoon_inc == "editilaus_in.inc") {
                    $edi_ulos .= "\n".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lisättiin tilaukseen")."!";
                  }
                  else {
                    echo "<font class='message'>".t("JT-rivi")." --> ".t("Tuoteno").": $jtrow[tuoteno] ".t("lisättiin tilaukseen").". (".t("Tuotetta ei riittänyt kaikille JT-riveille").")</font><br>";
                  }

                  $loput[$tunnukset]   = "KAIKKI";

                  $kpl[$tunnukset]   = 0;
                  $tunnusarray     = explode(',', $tunnukset);

                  // Toimitetaan jtrivit
                  tee_jt_tilaus($tunnukset, $tunnusarray, $kpl, $loput, $suoratoimitus_paikat, $tilaus_on_jo, $varastosta, $jt_huomioi_pvm, $mista_tullaan);

                  $jt_rivilaskuri++;
                }
                elseif ($automaaginen == "") {
                  echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:yellowgreen;'>".t("Ei riitä kaikille")."!</font><br>";

                  if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
                    echo t("Viikko")." $toimvko";
                  }
                  elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
                    echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
                  }
                  else {
                    echo tv1dateconv($toimaika);
                    echo $saapumisajat[$jtrow['tuoteno']];
                  }
                  echo "</td>";

                  if ($kukarow['extranet'] == '' and !empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {

                    echo "<td valign='top' {$class}>";

                    if ($jtrow['jt_manual'] != '') {
                      echo t("JT manuaalinen");
                    }
                    elseif (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $jtrow['kerayspvm'] > date('Y-m-d')) {
                      echo t("JT muiden mukana");
                    }
                    else {
                      echo t("JT");

                      if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                        echo " ".t("heti");
                      }
                    }

                    echo "</td>";
                  }

                  echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";
                  echo "<td valign='top' align='center' $class>".t("K")."<input type='radio' name='loput[$tunnukset]' value='KAIKKI' $kaikki_check></td>";

                  if ($jtrow["osatoimitus"] == "") {
                    echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='$kpl[$tunnukset]'></td>";
                    echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
                    echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
                  }
                  else {
                    echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
                  }

                  echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
                  echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
                  echo "<td class='back'><button name='poistaValintaNappi'>" . t("Poista valinta") . "</button></td>";

                  $jt_rivilaskuri++;
                }
              }
              // Ei riitä koko riville
              elseif ($kukarow["extranet"] == "" and $kokonaismyytavissa > 0 and $perheok==0 and $voiko_toimittaa !== false) {
                if ($automaaginen == '') {
                  echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:orange;'>".t("Ei riitä koko riville")."!</font><br>";

                  if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
                    echo t("Viikko")." $toimvko";
                  }
                  elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
                    echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
                  }
                  else {
                    echo tv1dateconv($toimaika);
                    echo $saapumisajat[$jtrow['tuoteno']];
                  }
                  echo "</td>";

                  if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {

                    echo "<td valign='top' {$class}>";

                    if ($jtrow['jt_manual'] != '') {
                      echo t("JT manuaalinen");
                    }
                    elseif (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $jtrow['kerayspvm'] > date('Y-m-d')) {
                      echo t("JT muiden mukana");
                    }
                    else {
                      echo t("JT");

                      if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                        echo " ".t("heti");
                      }
                    }

                    echo "</td>";
                  }

                  echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";
                  echo "<td valign='top' align='center' $class>&nbsp;</td>";

                  if ($jtrow["osatoimitus"] == "" and $jtrow["jtkielto"] != "Z") {
                    echo "<td valign='top' align='center' $class><input type='text' name='kpl[$tunnukset]' size='4' value='$kpl[$tunnukset]'></td>";
                    echo "<td valign='top' align='center' $class>".t("P")."<input type='radio' name='loput[$tunnukset]' value='POISTA' $poista_check></td>";
                    echo "<td valign='top' align='center' $class>".t("J")."<input type='radio' name='loput[$tunnukset]' value='JATA' $jata_check></td>";
                  }
                  else {
                    echo "<td valign='top' align='center' colspan='3' $class>".t("Tilausta ei osatoimiteta")."</td>";
                  }

                  echo "<td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
                  echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
                  echo "<td class='back'><button name='poistaValintaNappi'>" . t("Poista valinta") . "</button></td>";

                  $jt_rivilaskuri++;
                }
              }
              // Riviä ei voida toimittaa
              else {
                if ($automaaginen == '') {
                  echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$jtrow[yksikko]'", "", "", "selite")."<br><font style='color:red;'>".t("Riviä ei voida toimittaa")."!</font><br>";

                  if (isset($toimvko) and $toimvko > 0 and !isset($toimpva)) {
                    echo t("Viikko")." $toimvko";
                  }
                  elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
                    echo t("Viikko")." $toimvko (".$DAY_ARRAY[$toimpva].")";
                  }
                  else {
                    echo tv1dateconv($toimaika);
                    echo $saapumisajat[$jtrow['tuoteno']];
                  }
                  echo "</td>";
                  echo "<input type='hidden' name='jt_rivitunnus[]' value='$tunnukset'>";

                  if ($kukarow["extranet"] == "") {

                    if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {

                      echo "<td valign='top' {$class}>";

                      if ($jtrow['jt_manual'] != '') {
                        echo t("JT manuaalinen");
                      }
                      elseif (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $jtrow['kerayspvm'] > date('Y-m-d')) {
                        echo t("JT muiden mukana");
                      }
                      else {
                        echo t("JT");

                        if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                          echo " ".t("heti");
                        }
                      }

                      echo "</td>";
                    }

                    echo "  <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $classlisa>".t("M")."<input type='radio' name='loput[$tunnukset]' value='MITA' $mita_check></td>";
                    echo "<td valign='top' align='center' $classlisa>".t("H")."<input type='radio' name='loput[$tunnukset]' value='VAKISIN' $mita_check></td>";
                    echo "<td class='back'><button name='poistaValintaNappi'>" . t("Poista valinta") . "</button></td>";

                  }
                  else {
                    echo "<td valign='top' align='center' $class>&nbsp;</td>";
                    if ($kukarow["extranet"] != "" and $yhtiorow["jt_rivien_kasittely"] != 'E') {
                      echo "<td valign='top' align='center' $class>".t("Mitätöi")." $yhtiorow[jt_rivien_kasittely]<input type='radio' name='loput[$tunnukset]' value='MITA'></td>";
                    }
                    echo "<td valign='top' align='center' $classlisa>".t("Älä tee mitään")."<input type='radio' name='loput[$tunnukset]' value=''></td>";
                  }

                  $jt_rivilaskuri++;
                }
              }
            }
            else {
              echo "<td valign='top' align='center' $classlisa>&nbsp;</td>";
              $jt_rivilaskuri++;
            }

            if ($automaaginen == '' and $tilaus_on_jo != "") {
              if ($tilaus_on_jo_row['toim_osoite'] != $jtrow['toim_osoite'] or $tilaus_on_jo_row['toim_postino'] != $jtrow['toim_postino'] or $tilaus_on_jo_row['toim_postitp'] != $jtrow['toim_postitp']) {
                $toimitus_osoite = $jtrow['toim_osoite'] . ' ' . $jtrow['toim_postino'] . ' ' . $jtrow['toim_postino'];
                echo "<td class='back'><font class='error'>".t('Huom! Jälkitoimitusrivillä on eri toimitusosoite.') . ' ' . $toimitus_osoite . "</font></td>";
              }
              echo "</tr>";
            }

            if (isset($lapsires) and mysql_num_rows($lapsires) > 0 and $automaaginen == '') {

              mysql_data_seek($lapsires, 0);

              while ($perherow = mysql_fetch_assoc($lapsires)) {

                $classlisa   = "";
                $class     = "";

                if (isset($pkrow['kpl']) and $borderlask == 1 and $pkrow['kpl'] == 1 and $pknum == 1) {
                  $classlisa = $class." style='border-top: 1px solid; border-bottom: 1px solid; border-right: 1px solid;' ";
                  $class    .= " style=' border-top: 1px solid; border-bottom: 1px solid;' ";

                  $borderlask--;
                }
                elseif (isset($pkrow['kpl']) and $borderlask == $pkrow['kpl'] and $pkrow['kpl'] > 0) {
                  $classlisa = $class." style='border-top: 1px solid; border-right: 1px solid;' ";
                  $class    .= " style='border-top: 1px solid;' ";
                  $borderlask--;
                }
                elseif ($borderlask == 1) {
                  if ($pknum > 1) {
                    $classlisa = $class." style='font-style:italic; border-bottom: 1px solid; border-right: 1px solid;' ";
                    $class    .= " style='font-style:italic; border-bottom: 1px solid;' ";
                  }
                  else {
                    $classlisa = $class." style='border-bottom: 1px solid; border-right: 1px solid;' ";
                    $class    .= " style='border-bottom: 1px solid;' ";
                  }

                  $borderlask--;
                }
                elseif ($borderlask > 0 and $borderlask < $pknum) {
                  $classlisa = $class." style='font-style:italic; border-right: 1px solid;' ";
                  $class    .= " style='font-style:italic;' ";
                  $borderlask--;
                }

                echo "<tr>";

                $kokonaismyytavissa = 0;

                $jt_saldopvm = "";

                if (!empty($yhtiorow["saldo_kasittely"])) {
                  $jt_saldopvm = date("Y-m-d");
                }

                if ($yhtiorow["saldo_kasittely"] == 'U' and $toim == 'ENNAKKO') {
                  $jt_saldopvm = $jtrow['ttoimaika'];
                }

                foreach ($varastosta as $vara) {
                  list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($perherow["tuoteno"], $jtspec, $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

                  if ($saldolaskenta == "hyllysaldo") {
                    $kokonaismyytavissa += $hyllyssa;
                  }
                  else {
                    $kokonaismyytavissa += $myytavissa;
                  }
                }

                if ($kukarow["extranet"] == "") {
                  echo "<td valign='top' $class><a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($perherow["tuoteno"])."'>$perherow[tuoteno]</a>";
                }
                else {
                  echo "<td valign='top' $class>$perherow[tuoteno]";
                }

                echo "<br>$perherow[nimitys]</td>";
                echo "</td>";

                if ($tilaus_on_jo == "") {
                  echo "<td valign='top' $class>$perherow[ytunnus]<br>";

                  $_nimitark = "";
                  if (!empty($perherow["nimitark"])) {
                    $_nimitark = "<br>".$perherow["nimitark"];
                  }

                  if ($kukarow["extranet"] == "") {
                    echo "<a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=NAYTATILAUS&tunnus=$perherow[ltunnus]'>$perherow[nimi]$_nimitark</a><br>";
                  }
                  else {
                    echo "$perherow[nimi]$_nimitark<br>";
                  }

                  $_toim_nimitark = "";
                  if (!empty($perherow["toim_nimitark"])) {
                    $_toim_nimitark = "<br>".$perherow["toim_nimitark"];
                  }

                  echo "$perherow[toim_nimi]$_toim_nimitark</td>";
                }

                echo "<td valign='top' $class>$perherow[otunnus]<br>$perherow[viesti]</td>";

                if ($kukarow["extranet"] == "") {
                  echo "<td valign='top' $class><a href='$PHP_SELF?toim=$toim&tee=MUOKKAARIVI&jt_rivitunnus=$perherow[tunnus]&toimittajaid=$toimittajaid&asiakasid=$asiakasid&asiakasno=$asiakasno&toimittaja=$toimittaja&toimi=$toimi&ei_limiittia=$ei_limiittia&suoratoimit=$suoratoimit&tuotenumero=$tuotenumero&tilaus=$tilaus&jarj=$jarj&tilausnumero=$tilausnumero'>$perherow[jt]</a><br>";
                }
                else {
                  echo "<td valign='top' align='right' $class>$perherow[jt]<br>";
                }


                if ($perherow["valkoodi"] != '' and trim(strtoupper($perherow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
                  $hinta  = hintapyoristys(laskuval($perherow["hinta"], $perherow["vienti_kurssi"]))." ".$perherow["valkoodi"];
                }
                else {
                  $hinta  = hintapyoristys($perherow["hinta"])." ".$perherow["valkoodi"];
                }

                echo $hinta."<br>";

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  if ($perherow["ale{$alepostfix}"] > 0) {
                    if ($alepostfix > 1) echo "+";
                    echo $perherow["ale{$alepostfix}"], "%<br />";
                  }
                }

                echo "</td>";

                if ($jtselaus_paivitys_oikeus) {
                  echo "<td valign='top' $class>$kokonaismyytavissa ".t_avainsana("Y", "", "and avainsana.selite='$perherow[yksikko]'", "", "", "selite")."<br></font>";

                  if (!isset($toimpva) and isset($toimvko) and $toimvko > 0) {
                    echo t("Viikko")." $toimvko";
                  }
                  elseif (isset($toimvko) and $toimvko > 0 and isset($toimpva)) {
                    echo t("Viikko")." $toimvko";

                    if (isset($toimpva)) {
                      echo " ($DAY_ARRAY[$toimpva])";
                    }
                  }
                  else {
                    echo tv1dateconv($toimaika);
                  }
                  echo "</td>";

                  if ($kukarow["extranet"] == "") {

                    if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                      echo "<td valign='top' {$class}>";

                      if ($jtrow['jt_manual'] != '') {
                        echo t("JT manuaalinen");
                      }
                      elseif (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A' and $jtrow['kerayspvm'] > date('Y-m-d')) {
                        echo t("JT muiden mukana");
                      }
                      else {
                        echo t("JT");

                        if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
                          echo " ".t("heti");
                        }
                      }

                      echo "</td>";
                    }

                    echo "  <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $classlisa>&nbsp;</td>
                        <td valign='top' align='center' $classlisa>&nbsp;</td>";
                  }
                  else {
                    echo "  <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $class>&nbsp;</td>
                        <td valign='top' align='center' $classlisa>&nbsp;</td>";
                  }
                }
                else {
                  echo "<td valign='top' align='center' $classlisa>&nbsp;</td>";
                }
                echo "</tr>";
              }

              unset($lapsires);
            }

            if ($kukarow['extranet'] == '' and (!isset($php_cli) or !$php_cli) and $automaaginen == '') {
              // Korvaavat tuotteet //
              $query  = "SELECT * from korvaavat where tuoteno='$jtrow[tuoteno]' and yhtio='$kukarow[yhtio]'";
              $korvaresult = pupe_query($query);

              if (mysql_num_rows($korvaresult) > 0) {
                // tuote löytyi, joten haetaan sen id...
                $korvarow = mysql_fetch_assoc($korvaresult);

                $query = "SELECT * from korvaavat where id='$korvarow[id]' and tuoteno<>'$jtrow[tuoteno]' and yhtio='$kukarow[yhtio]' order by jarjestys, tuoteno";
                $korva2result = pupe_query($query);

                if (mysql_num_rows($korva2result) > 0) {
                  while ($krow2row = mysql_fetch_assoc($korva2result)) {

                    $vapaana = 0;

                    $jt_saldopvm = "";

                    if (!empty($yhtiorow["saldo_kasittely"])) {
                      $jt_saldopvm = date("Y-m-d");
                    }

                    if ($yhtiorow["saldo_kasittely"] == 'U' and $toim == 'ENNAKKO') {
                      $jt_saldopvm = $jtrow['ttoimaika'];
                    }

                    foreach ($varastosta as $vara) {
                      list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($krow2row["tuoteno"], $jtspec, $vara, "", "", "", "", "", $asiakasmaa, $jt_saldopvm);

                      if ($saldolaskenta == "hyllysaldo") {
                        $vapaana += $hyllyssa;
                      }
                      else {
                        $vapaana += $myytavissa;
                      }

                    }

                    if ($vapaana >= $jurow["jt"]) {
                      echo "<tr class='aktiivi'>";
                      echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
                      echo "<td align='left' style='vertical-align:top'>";
                      echo "$krow2row[tuoteno] ($vapaana) <font style='color:green;'>".t("Riittää kaikille")."!$varalisa</font><br>";
                      echo "</td><td colspan='9' align='left'><input type='button' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
                    }
                    elseif ($vapaana >= $jtrow["jt"]) {
                      echo "<tr class='aktiivi'>";
                      echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
                      echo "<td align='left' style='vertical-align:top'>";
                      echo "$krow2row[tuoteno] ($vapaana) <font style='color:yellowgreen;'>".t("Ei riitä kaikille")."!$varalisa</font><br>";
                      echo "</td><td colspan='9' align='left'><input type='submit' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
                    }
                    elseif ($vapaana > 0) {
                      echo "<tr class='aktiivi'>";
                      echo "<td><font style='color:red;'>".t("Korvaava")."</font></td>";
                      echo "<td align='left' style='vertical-align:top'>";
                      echo "$krow2row[tuoteno] ($vapaana) <font style='color:orange;'>".t("Ei riitä koko riville")."!$varalisa</font><br>";
                      echo "</td><td colspan='9' align='left'><input type='submit' value='".t("Korvaa tuote")." $jtrow[tuoteno]' onClick='javascript:update_params(\"$jtrow[tuoteno]\", \"$krow2row[tuoteno]\", \"$jtrow[tunnus]\");javascript:submit();'></td></tr>";
                    }
                  }
                }
              }
            }
          }
        }
      }

      if ($automaaginen == '' and $jt_rivilaskuri > 1) {

        if ($jtselaus_paivitys_oikeus) {

          if ($kukarow["extranet"] == "" and $automaaginen == '') {

            echo "<tr class='aktiivi'>";

            if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {
              $colspan = 4;
            }
            else {
              $colspan = 3;
            }

            if ($tilaus_on_jo == "") {
              $colspan++;
            }

            $colspan++;

            if ($jtselaus_paivitys_oikeus) {
              $colspan++;
            }

            echo "<td colspan='$colspan'>".t("Ruksaa kaikki")."</td>";
            echo "<td align='center'><input type='checkbox' name='KAIKKI' onclick='toggleAll(this);'></td>";
            echo "<td></td>";
            echo "<td align='center'><input type='checkbox' name='POISTA' onclick='toggleAll(this);'></td>";
            echo "<td align='center'><input type='checkbox' name='JATA' onclick='toggleAll(this);'></td>";
            echo "<td align='center'><input type='checkbox' name='MITA' onclick='toggleAll(this);'></td>";
            echo "<td align='center'><input type='checkbox' name='VAKISIN' onclick='toggleAll(this);'></td></tr>";
          }

          echo "<tr><td colspan='8' class='back'></td><td colspan='3' class='back' align='right'><input type='submit' value='".t("Poimi")."'></td></tr>";
          echo "</form>";
        }

        echo "</table>";

        echo "<table><th>".t("Toimitettavat jälkitoimitusrivit yhteensä")."</th><td>".sprintf("%.02f", $jt_toim_hintalaskuri)." {$yhtiorow["valkoodi"]}</td></tr>";

        echo "<th>".t("Kaikki jälkitoimitusrivit yhteensä")."</th><td>".sprintf("%.02f", $jt_hintalaskuri)." {$yhtiorow["valkoodi"]}</td></tr></table>";

        if ($jtseluas_rivienmaara >= 1000 and $ei_limiittia == "") {
          echo "<font class='error'>".t("Haun tulos liian suuri! Näytetään ensimmäiset 1000 riviä!")."</font><br>";
        }
      }
      elseif ($jt_rivilaskuri == 1) {
        if ($from_varastoon_inc == "editilaus_in.inc") {
          $edi_ulos .= "\n".t("Yhtään JT-riviä ei löytynyt")."!";
        }
        elseif ($from_varastoon_inc == "varastoon.inc") {
          // ei mitään
        }
        else {
          echo t("Yhtään JT-riviä ei löytynyt")."!<br>";
        }
      }
    }
    else {
      if ($from_varastoon_inc == "editilaus_in.inc") {
        $edi_ulos .= "\n".t("Yhtään JT-riviä ei löytynyt")."!";
      }
      else {
        echo t("Yhtään JT-riviä ei löytynyt")."!<br>";
      }
    }
    $tee = '';
  }
}

if ($tilaus_on_jo == "" and $from_varastoon_inc == "" and $tee == '') {

  echo "<br><font class='message'>".t("Valinnat")."</font><br><br>";

  if (!empty($kukarow['varasto'])) {
    $_kukarow_varasto = mysql_real_escape_string($kukarow['varasto']);
    $_varastolisa = "AND tunnus in ({$_kukarow_varasto})";
  }
  elseif ($onkolaajattoimipaikat) {
    if ($kukarow['toimipaikka'] != 0) {
      $_toimipaikat = array($kukarow['toimipaikka'], 0);
    }
    else {
      $_toimipaikat = array(0);
    }

    foreach ($_toimipaikat as $_toimipaikka) {

      $query  = "SELECT GROUP_CONCAT(tunnus) AS tunnukset
                 FROM varastopaikat
                 WHERE yhtio      = '{$kukarow['yhtio']}'
                 AND tyyppi      != 'P'
                 AND toimipaikka  = '{$_toimipaikka}'";
      $vares = pupe_query($query);
      $varow = mysql_fetch_assoc($vares);

      // Jos meillä on toimipaikka setattuna ja ei löydetty tämän toimipaikan varastoja
      // Fallback: etsitään varastoja joita ei ole liitetty toimipaikkaan
      if (count($_toimipaikat) > 1 and $_toimipaikka != 0 and empty($varow['tunnukset'])) {
        continue;
      }

      if (!empty($varow['tunnukset'])) {
        $_varastolisa = "AND tunnus IN ({$varow['tunnukset']})";
        break;
      }

      $_varastolisa = "";
    }
  }
  else {
    $_varastolisa = "";
  }

  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio  = '{$kukarow['yhtio']}'
            AND tyyppi  != 'P'
            {$_varastolisa}
            ORDER BY tyyppi, nimitys";
  $vtresult = pupe_query($query);

  echo "  <form name='valinta' method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <table>";

  echo "<tr><td class='back'><font class='message'>".t("Toimita varastosta:")."</font></td></tr>";

  while ($vrow = mysql_fetch_assoc($vtresult)) {

    $sel = "";
    if (isset($varastosta[$vrow["tunnus"]]) and $varastosta[$vrow["tunnus"]] == $vrow["tunnus"]) {
      $sel = 'CHECKED';
    }

    $huomio = "";

    if ($vrow['tyyppi'] == 'E') {
      $huomio = "<td class='back'><font class='error'>".t("HUOM: Erikoisvarasto!")."</font></td>";
    }

    echo "<tr><th>$vrow[nimitys]</th><td><input type='checkbox' name='varastosta[$vrow[tunnus]]' value='$vrow[tunnus]' $sel></td>$huomio</tr>";
  }

  echo "</table>";

  echo "<table>";
  echo "<tr><td class='back'><br></td></tr><tr><td class='back'><font class='message'>".t("Valinnat:")." </font></td></tr>";

  $sel=array();
  $sel[$jarj] = "selected";

  echo "<tr>
      <th>".t("Järjestys")."</th>
      <td>
        <select name='jarj'>
        <option value='tuoteno' {$sel["tuoteno"]}>".t("Tuotteittain")."</option>
        <option value='ytunnus' {$sel["ytunnus"]}>".t("Asiakkaittain")."</option>
        <option value='luontiaika' {$sel["luontiaika"]}>".t("Tilausajankohdan mukaan")."</option>
        <option value='toimaika' {$sel["toimaika"]}>".t("Toimitusajankohdan mukaan")."</option>
        </select>
      </td>
    </tr>";

  $sel = '';
  if ($automaaginen != '') $sel = 'CHECKED';

  echo "  <tr>
      <th>".t("Toimita selkeät rivit automaagisesti")."</th>
      <td><input type='checkbox' name='automaaginen' value='tosi_automaaginen' $sel onClick = 'return verify()'></td>
    </tr>";

  $selvar=array();
  $selvar[$saldolaskenta] = "SELECTED";

  echo "  <tr>
      <th>".t("Saldovalinnassa käytetään")."</th>
      <td><select name='saldolaskenta'>
        <option value='myytavissasaldo' {$selvar["myytavissasaldo"]}>".t("Myytävissä olevaa määrää")."</option>
        <option value='hyllysaldo' {$selvar["hyllysaldo"]}>".t("Hyllyssä olevaa määrää")."</option>
        </select>
      </td></tr>";

  echo "</table>";


  echo "<table>";

  echo "<tr><td class='back'><br></td></tr><tr><td class='back'><font class='message'>".t("Rajaukset:")."</font></td></tr>";

  echo "<tr>
      <th>".t("Toimittaja")."</th>
      <td>
      <input type='text' size='20' name='ytunnus' value='$toimittaja'>
      </td>
    </tr>";

  echo "<tr>
      <th>".t("Asiakas")."</th>
      <td>
      <input type='text' size='20' name='asiakasno' value='$asiakasno'>
      </td>
      </td>
    </tr>";

  echo "<tr>";
  echo "<th>".t("Tuoteosasto")."</th>";

  // tehdään avainsana query
  $result = t_avainsana("OSASTO");

  echo "<td><select name='tuoteosasto'>";
  echo "<option value=''>".t("Tuoteosasto")."</option>";

  while ($row = mysql_fetch_assoc($result)) {
    if ($tuoteosasto == $row["selite"]) $sel = "selected";
    else $sel = "";
    echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
  }

  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Tuoteryhmä")."</th>";

  // tehdään avainsana query
  $result = t_avainsana("TRY");

  echo "<td><select name='tuoteryhma'>";
  echo "<option value=''>".t("Tuoteryhmä")."</option>";

  while ($row = mysql_fetch_assoc($result)) {
    if ($tuoteryhma == $row["selite"]) $sel = "selected";
    else $sel = "";
    echo "<option value='$row[selite]' $sel>$row[selite] $row[selitetark]</option>";
  }

  echo "</select></td>";
  echo "</tr>\n";

  echo "<tr>";
  echo "<th>".t("Tuotemerkki")."</th>";

  $query = "SELECT distinct tuotemerkki
            FROM tuote use index (yhtio_tuotemerkki)
            WHERE yhtio='$kukarow[yhtio]'
            and tuotemerkki != ''
            ORDER BY tuotemerkki";
  $result = pupe_query($query);

  echo "<td><select name='tuotemerkki'>";
  echo "<option value=''>".t("Tuotemerkki")."</option>";

  while ($row = mysql_fetch_assoc($result)) {
    if ($tuotemerkki == $row["tuotemerkki"]) $sel = "selected";
    else $sel = "";
    echo "<option value='$row[tuotemerkki]' $sel>$row[tuotemerkki]</option>";
  }

  echo "</select></td>";
  echo "</tr>\n";

  echo "<tr>";
  echo "<th>".t("Maa")."</th>";

  $query = "SELECT distinct koodi, nimi
            FROM maat
            WHERE nimi != ''
            ORDER BY koodi";
  $result = pupe_query($query);

  echo "<td><select name='maa'>";
  echo "<option value=''>".t("Maa")."</option>";

  while ($row = mysql_fetch_assoc($result)) {
    if ($maa == $row["koodi"]) $sel = "selected";
    else $sel = "";
    echo "<option value = '".strtoupper($row['koodi'])."' $sel>".t($row['nimi'])."</option>";
  }

  echo "</select></td>";
  echo "</tr>\n";

  echo "<tr><th valign='top'>", t("Tuotelista"), "<br>(", t("Rajaa näillä tuotteilla"), ")</th>
    <td colspan=''><textarea name='tuotenumero' rows='5' cols='35'>{$tuotenumero}</textarea></td></tr>";

  echo "<tr><th>", t("Myyjä"), "</th>";

  $query = "SELECT tunnus, nimi, myyja
            FROM kuka
            WHERE yhtio   = '{$kukarow['yhtio']}'
            AND extranet  = ''
            AND myyja    != 0
            ORDER BY nimi";
  $result = pupe_query($query);

  echo "<td><select name='myyja'>";
  echo "<option value=''>", t("Myyjä"), "</option>";

  while ($row = mysql_fetch_assoc($result)) {
    $sel = $row['myyja'] == $myyja ? " selected" : "";

    $row['nimi'] = ($row['myyja'] > 0) ? $row['nimi']." - ".$row['myyja'] : $row['nimi'];

    echo "<option value='{$row['tunnus']}'{$sel}>{$row['nimi']}</option>";
  }

  echo "</select></td></tr>\n";

  if (!empty($yhtiorow['jt_automatiikka']) and $yhtiorow['automaattinen_jt_toimitus'] == 'A') {

    $sel = array($jt_tyyppi => 'selected') + array('j' => '', 'j_muiden_mukana' => '', 'j_manual' => '');

    echo "<tr>";
    echo "<th>", t("JT tyyppi"), "</th>";
    echo "<td>";
    echo "<select name='jt_tyyppi'>";
    echo "<option value=''>", t("Näytä kaikki"), "</option>";
    echo "<option value='j' {$sel['j']}>", t("JT heti"), "</option>";
    echo "<option value='j_muiden_mukana' {$sel['j_muiden_mukana']}>", t("JT muiden mukana"), "</option>";

    if ($yhtiorow['jt_manual'] == 'K') {
      echo "<option value='j_manual' {$sel['j_manual']}>", t("JT manuaalinen"), "</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }

  echo "<tr>
      <th>".t("Tilaus")."</th>
      <td>
      <input type='text' name='tilaus' value='$tilaus' size='10'>
      </td>
      </td>
    </tr>";

  echo "<tr>
      <th>", t("Näytä vain jt-rivit jos asiakkaan jälkitoimitusten yhteissumma on yli")."</th>
      <td>
      <input type='text' name='summarajaus' value='$summarajaus' size='10'>
      </td>
      </td>
    </tr>";

  $sel = $sel2 = '';
  if ($toimi != '') $sel = 'CHECKED';
  if ($ei_limiittia != '') $sel2 = 'CHECKED';

  echo "<tr>
      <th>".t("Näytä vain toimitettavat rivit")."</th>
      <td><input type='checkbox' name='toimi' $sel></td>
    </tr>";

  echo "<tr>
      <th>".t("Näytä kaikki rivit")." (".t("Oletus").": max 1000 ".t("riviä").")</th>
      <td><input type='checkbox' name='ei_limiittia' $sel2></td>
    </tr>";

  if ($toim == "ENNAKKO") {
    echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
        function verify(){
          msg = '".t("Haluatko todella toimittaa kaikki selkeät ennakkorivit?")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
        </SCRIPT>";
  }
  else {
    echo "  <SCRIPT LANGUAGE=JAVASCRIPT>
        function verify(){
          msg = '".t("Haluatko todella toimittaa kaikki selkeät JT-Rivit?")."?';

          if (confirm(msg)) {
            return true;
          }
          else {
            skippaa_tama_submitti = true;
            return false;
          }
        }
        </SCRIPT>";
  }

  $sel = '';
  if ($suoratoimit != '') $sel = 'CHECKED';

  echo "  <tr>
      <th>".t("Näytä vain suoratoimitusrivit")."</th>
      <td><input type='checkbox' name='suoratoimit' $sel></td>
      </tr>";

  if ($jt_huomioi_pvm != '' or ($yhtiorow["saldo_kasittely"] == 'U' and $toim != 'ENNAKKO')) $sel = 'CHECKED';

  echo "  <tr>
      <th>".t("Huomioi päivämäärät jälkitilauksissa")."</th>
      <td><input type='checkbox' name='jt_huomioi_pvm' $sel></td>
      </tr>";

  echo "</table>

    <br><input type='submit' value='".t("Näytä")."'>
    </form>";
}

if (strpos($_SERVER['SCRIPT_NAME'], "jtselaus.php") !== FALSE) {
  if (@include "inc/footer.inc");
  elseif (@include "footer.inc");
  else exit;
}
