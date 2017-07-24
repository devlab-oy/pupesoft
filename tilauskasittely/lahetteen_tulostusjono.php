<?php

ob_start();

require "../inc/parametrit.inc";
require 'validation/Validation.php';
require 'valmistuslinjat.inc';

$onkologmaster = (LOGMASTER_RAJAPINTA and in_array($yhtiorow['ulkoinen_jarjestelma'], array('', 'K')));

if (isset($tee) and $tee == "TILAA_AJAX") {
  require_once "inc/tilaa_ajax.inc";
}

if ($toim == "VASTAANOTA_REKLAMAATIO" and !in_array($yhtiorow['reklamaation_kasittely'], array('U', 'X'))) {
  echo "<font class='error'>".t("HUOM: Ohjelma on käytössä vain kun käytetään laajaa reklamaatioprosessia")."!</font>";
  exit;
}

if (!isset($tuvarasto)) $tuvarasto = '';
if (!isset($tutyyppi)) $tutyyppi = '';

if (!isset($jarj)) $jarj = '';
if (!isset($etsi)) $etsi = '';
if (!isset($tumaa)) $tumaa = '';
if (!isset($tee2)) $tee2 = '';
if (!isset($show_ohjelma_moduli)) $show_ohjelma_moduli = false;


if ($show_ohjelma_moduli) {
  setcookie('show_ohjelma_moduli', true, strtotime('+1 year'));
}

$show_ohjelma_moduli = $show_ohjelma_moduli || isset($_COOKIE['show_ohjelma_moduli']) && $_COOKIE['show_ohjelma_moduli'] == true;

$valmistuslinjat = hae_valmistuslinjat();

if (!empty($valmistuslinjat)) {
  //Tänne vain jos valmistuslinjat on käytössä, eli käyttöliittymässä on dropdown sekä aikavalinta inputit
  $now_minus_1_month = date('Y-m-d', strtotime('now - 1 month'));
  $now_minus_1_month = explode('-', $now_minus_1_month);

  if (!isset($ppa)) $ppa = $now_minus_1_month[2];
  if (!isset($kka)) $kka = $now_minus_1_month[1];
  if (!isset($vva)) $vva = $now_minus_1_month[0];

  if (!isset($ppl)) $ppl = date('d');
  if (!isset($kkl)) $kkl = date('m');
  if (!isset($vvl)) $vvl = date('Y');
}

$logistiikka_yhtio     = '';
$logistiikka_yhtiolisa   = '';
$lasku_yhtio_originaali = $kukarow['yhtio'];

if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
  $logistiikka_yhtio = $konsernivarasto_yhtiot;
  $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

  if (isset($lasku_yhtio) and $lasku_yhtio != '') {
    $kukarow['yhtio'] = mysql_real_escape_string($lasku_yhtio);
    $yhtiorow = hae_yhtion_parametrit($lasku_yhtio);
  }
}
else {
  $logistiikka_yhtiolisa = "yhtio = '$kukarow[yhtio]'";
}

$DAY_ARRAY = array(1 => t("Ma"), t("Ti"), t("Ke"), t("To"), t("Pe"), t("La"), t("Su"));

js_popup();

if ($toim == 'SIIRTOLISTA') {
  echo "<font class='head'>".t("Tulosta siirtolista").":</font><hr>";
}
elseif ($toim == 'SIIRTOTYOMAARAYS') {
  echo "<font class='head'>".t("Tulosta sisäinen työmääräys").":</font><hr>";
}
elseif ($toim == 'VALMISTUS') {
  echo "<font class='head'>".t("Tulosta valmistuslista").":</font><hr>";
}
elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
  echo "<font class='head'>".t("Tulosta purkulista").":</font><hr>";
}
else {
  echo "<font class='head'>".t("Tulosta keräyslista").":</font><hr>";
}

if ($toim == 'KAIKKILISTAT') {
  $tila         = "N";
  $lalatila      = "A";
  $tila_lalatila_lisa = " OR (lasku.tila = 'G' AND lasku.alatila = 'J' and lasku.tilaustyyppi != 'M')
              OR (lasku.tila = 'S' AND lasku.alatila = 'J' and lasku.tilaustyyppi = 'S')
              OR (lasku.tila = 'G' AND lasku.alatila = 'J' and lasku.tilaustyyppi = 'M')
              OR (lasku.tila = 'V' AND lasku.alatila = 'J')
              OR (lasku.tila = 'C' AND lasku.alatila = 'B')";
}
elseif ($toim == 'SIIRTOLISTA') {
  $tila         = "G";
  $lalatila      = "J";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = " and tilaustyyppi!='M' ";
}
elseif ($toim == 'SIIRTOTYOMAARAYS') {
  $tila         = "S";
  $lalatila      = "J";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = " and tilaustyyppi='S' ";
}
elseif ($toim == 'MYYNTITILI') {
  $tila         = "G";
  $lalatila      = "J";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = " and tilaustyyppi='M' ";
}
elseif ($toim == 'VALMISTUS') {
  $tila         = "V";
  $lalatila      = "J";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = "";
}
elseif ($toim == 'VALMISTUSMYYNTI') {
  $tila         = "V";
  $lalatila      = "J";
  $tila_lalatila_lisa = " or (lasku.tila='N' and lasku.alatila='A')";
  $tilaustyyppi     = "";
}
elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
  $tila         = "C";
  $lalatila      = "B";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = "";
}
else {
  $tila         = "N";
  $lalatila      = "A";
  $tila_lalatila_lisa = "";
  $tilaustyyppi     = "";
}

if ($tee2 == 'NAYTATILAUS') {

  if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
    echo "<font class='head'>", t("Yhtiön"), " $yhtiorow[nimi] ", t("tilaus"), " $tunnus:</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";
  }

  require "raportit/naytatilaus.inc";
  echo "<br><br><br>";
  $tee2 = $vanha_tee2;

  if ($logistiikka_yhtio != '' and $konsernivarasto_yhtiot != '') {
    $logistiikka_yhtio = $konsernivarasto_yhtiot;
  }

  enable_ajax();

  echo "<script type=\"text/javascript\" charset=\"utf-8\">

    $('.tilaa').live('click', function(){

      var submitid = $(this).attr(\"id\");
      var osat    = submitid.split(\"_\");

      var tuoteno   = $(\"#tuoteno_\"+osat[1]).val();
      var toimittaja   = $(\"#toimittaja_\"+osat[1]).val();
      var maara     = $(\"#maara_\"+osat[1]).val();

      $.post('{$_SERVER['SCRIPT_NAME']}',
        {   tee: 'TILAA_AJAX',
          tuoteno: tuoteno,
          toimittaja: toimittaja,
          maara: maara,
          no_head: 'yes',
          ohje: 'off' },
        function(return_value) {
          var message = jQuery.parseJSON(return_value);

          if (message == \"ok\") {
            $(\"#\"+submitid).val('".t("Tilattu")."').attr('disabled',true);
            $(\"#maara_\"+osat[1]).attr('disabled',true);
          }
        }
      );
    });

    </script>";

}

if ($tee2 == 'TULOSTA') {

  unset($tilausnumerorypas);
  $tulostetaanko_kaikki = "";

  if (isset($tulostukseen) and ($toim == 'VALMISTUS' or $toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS' or $toim == 'MYYNTITILI')) {
    $lask   = 0;

    foreach ($tulostukseen as $tun) {
      $tilausnumerorypas[] = $tun;
      $lask++;
    }

    //ja niiden lukumäärä
    $laskuja = $lask;
  }
  elseif (isset($tulostukseen)) {
    $laskut  = "";
    $lask   = 0;

    foreach ($tulostukseen as $tun) {
      $laskut .= "$tun,";
      $lask++;
    }

    //tulostettavat tilausket
    $tilausnumerorypas[] = substr($laskut, 0, -1);
    //ja niiden lukumäärä
    $laskuja = $lask;
  }
  elseif (isset($tulostukseen_kaikki)) {
    $tilausnumerorypas = unserialize(urldecode($tulostukseen_kaikki));
    $tulostukseen_kaikki = "KYLLA";

    // Tsekataan komento valitulle tulostimelle
    $query = "SELECT komento
              from kirjoittimet
              where tunnus = '$valittu_tulostin'
              AND yhtio    = '$kukarow[yhtio]'";
    $pres = pupe_query($query);
    $prow = mysql_fetch_assoc($pres);

    // Kun tulostetaan kaikki kerralla ja otetaan sähköpostiin,
    // niin laitetaan kaikki yhteen dokkariin
    if ($prow["komento"] == "email" and $yhtiorow["lahetteen_tulostustapa"] != "X") {
      require_once "pdflib/phppdflib.class.php";

      $pdf_kaikki_tul = new pdffile;
      $pdf_kaikki_tul->set_default('margin-top', 0);
      $pdf_kaikki_tul->set_default('margin-bottom', 0);
      $pdf_kaikki_tul->set_default('margin-left', 0);
      $pdf_kaikki_tul->set_default('margin-right', 0);
    }
  }

  if (is_array($tilausnumerorypas)) {
    foreach ($tilausnumerorypas as $tilausrypas_key => $tilausrypas_value) {

      if ($tulostukseen_kaikki == 'KYLLA') {
        $kukarow['yhtio'] = $tilausrypas_value;
        $tilausnumeroita = $tilausrypas_key;
      }
      else {
        $tilausnumeroita = $tilausrypas_value;
      }

      $yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

      // katsotaan, ettei tilaus ole kenelläkään auki ruudulla
      $query = "SELECT *
                FROM kuka
                WHERE kesken in ($tilausnumeroita)
                and yhtio='$kukarow[yhtio]'";
      $keskenresult = pupe_query($query);

      //jos kaikki on ok...
      if (mysql_num_rows($keskenresult)==0) {

        $query    = "SELECT *
                     from lasku
                     where tunnus in ($tilausnumeroita)
                     and ((tila = '$tila' and alatila = '$lalatila') $tila_lalatila_lisa)
                     and yhtio    = '$kukarow[yhtio]'
                     ORDER BY clearing DESC
                     LIMIT 1";
        $result   = pupe_query($query);

        if (mysql_num_rows($result) > 0) {

          $laskurow = mysql_fetch_array($result);

          // jos tulostetaan kaikki ruudun keräyslistat, käytetään ainoastaa EIPAKKAAMOA oletusta
          if ($tulostukseen_kaikki == "KYLLA") {
            $query = "SELECT ei_pakkaamoa
                      FROM toimitustapa
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND selite  = '$laskurow[toimitustapa]'";
            $ei_pakkaamoa_res = pupe_query($query);
            $ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

            if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
              $ei_pakkaamoa = "X";
            }
            else {
              $ei_pakkaamoa = "";
            }
          }

          if ($laskurow["tila"] == 'G' or $laskurow["tila"] == 'S') {
            $tilausnumero  = $laskurow["tunnus"];
            $tee      = "valmis";
            $tulostetaan  = "OK";
            $toim_bck    = $toim;
            if ($toim == "KAIKKILISTAT") {
              if ($laskurow["tilaustyyppi"] == "M") $toim = "MYYNTITILI";
              else $toim = "SIIRTOLISTA";
            }

            require "tilaus-valmis-siirtolista.inc";

            $toim      = $toim_bck;
          }
          elseif ($laskurow["tila"] == 'V') {
            $tilausnumero  = $laskurow["tunnus"];
            $tulostetaan  = "OK";
            $toim_bck    = $toim;
            $toim       = "VALMISTAVARASTOON";

            require "tilaus-valmis-siirtolista.inc";

            $toim       = $toim_bck;
          }
          elseif ($laskurow["tila"] == 'C' and $laskurow["alatila"] == 'B') {
            $tee      = "VALMIS";
            $tulostetaan  = "OK";
            $toim_bck    = $toim;
            $takas       = 1;
            $tyyppi     = "REKLAMAATIO";
            if ($toim == "KAIKKILISTAT") $toim = "VASTAANOTA_REKLAMAATIO";

            require "tilaus-valmis-tulostus.inc";

            $toim = $toim_bck;
          }
          else {
            $toim_bck    = $toim;
            if ($toim == "KAIKKILISTAT") $toim = "";

            require "tilaus-valmis-tulostus.inc";

            $toim = $toim_bck;
          }
        }
        else {
          echo "<font class='error'>".t("Keräyslista on jo tulostettu")."! ($tilausnumeroita)</font><br>";
        }
      }
      else {
        $keskenrow = mysql_fetch_array($keskenresult);
        echo t("Tilaus on kesken käyttäjällä").", $keskenrow[nimi], ".t("ota yhteyttä häneen ja käske hänen laittaa vähän vauhtia tähän touhuun")."!<br>";
        $tee2 = "";
      }
    }

    if (!empty($pdf_kaikki_tul)) {
      // Tulostetaan sivu
      $params_kerayslista["komento"] = $komento;

      print_pdf_kerayslista($params_kerayslista);
    }
  }
  else {
    echo "<font class='error'>".t("Et valinnut mitään tulostettavaa")."!</font><br>";
  }
  $tee2 = "";
}

// valiitaan keräysklöntin tilaukset jotka tulostetaan
if ($tee2 == 'VALITSE') {

  //Haetaan sopivat tilaukset
  $query = "SELECT lasku.tunnus, lasku.ytunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto,
            if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos) prioriteetti,
            if (min(lasku.clearing)='','N',if (min(lasku.clearing)='JT-TILAUS','J',if (min(lasku.clearing)='ENNAKKOTILAUS','E',''))) t_tyyppi,
            left(min(lasku.kerayspvm),10) kerayspvm,
            left(min(lasku.toimaika),10) toimaika,
            min(keraysvko) keraysvko,
            min(toimvko) toimvko,
            varastopaikat.nimitys varastonimi,
            varastopaikat.tunnus varastotunnus,
            lasku.tunnus otunnus,
            lasku.viesti,
            GROUP_CONCAT(DISTINCT if(lasku.comments!='',lasku.comments, NULL) SEPARATOR '\n') comments,
            GROUP_CONCAT(DISTINCT if(lasku.sisviesti2!='',lasku.sisviesti2, NULL) SEPARATOR '\n') sisviesti2,
            GROUP_CONCAT(DISTINCT if(tilausrivi.kommentti!='',tilausrivi.kommentti, NULL) SEPARATOR '\n') kommentti,
            count(*) riveja,
            lasku.yhtio yhtio,
            lasku.yhtio_nimi yhtio_nimi
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi != 'D')
            LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
            WHERE lasku.yhtio = '$kukarow[yhtio]'
            and lasku.tunnus  in ($tilaukset)
            $tilaustyyppi
            GROUP BY lasku.tunnus
            ORDER BY prioriteetti, kerayspvm";
  $tilre = pupe_query($query);

  if (mysql_num_rows($tilre)==0) {
    $tee2     = "";
    $tuytunnus   = "";
    $tuvarasto  = "";
    $tumaa    = "";
  }
  else {
    // katsotaan, ettei tilaus ole kenelläkään auki ruudulla
    $query = "SELECT *
              FROM kuka
              WHERE kesken in ($tilaukset)
              and yhtio='$kukarow[yhtio]'";
    $keskenresult = pupe_query($query);

    //jos kaikki on ok...
    if (mysql_num_rows($keskenresult) == 0) {
      echo "<form method='post'>";
      echo "<input type='hidden' name='toim'       value='$toim'>";
      echo "<input type='hidden' name='jarj'       value='$jarj'>";
      echo "<input type='hidden' name='tuvarasto'   value='$tuvarasto'>";
      echo "<input type='hidden' name='tumaa'     value='$tumaa'>";
      echo "<input type='hidden' name='tutyyppi'     value='$tutyyppi'>";
      echo "<input type='hidden' name='tutoimtapa'  value='$tutoimtapa'>";
      echo "<input type='hidden' name='karajaus'     value='$karajaus'>";
      echo "<input type='hidden' name='tee2'       value='TULOSTA'>";

      echo "<table>";
      echo "<tr>";
      if ($logistiikka_yhtio != '') {
        echo "<th>", t("Yhtiö"), "</th>";
      }
      echo "<th>".t("Pri")."<br>".t("Varastoon")."</th>";
      echo "<th>".t("Tilaus")."</th>";
      echo "<th>".t("Asiakas")."</th>";
      echo "<th>".t("Nimi")."</th>";
      echo "<th>".t("Viite")."</th>";
      echo "<th>".t("Keräyspvm")."</th>";
      echo "<th>".t("Toimaika")."</th>";
      echo "<th>".t("Riv")."</th>";
      echo "<th>".t("Tulosta")."</th>";
      echo "<th>".t("Näytä")."</th>";
      echo "</tr>";

      $keskenlask = 0;

      while ($tilrow = mysql_fetch_array($tilre)) {

        $keskenlask ++;
        //otetaan tämä muutuja talteen
        $tul_varastoon = $tilrow["varasto"];

        echo "<tr class='aktiivi'>";

        $ero="td";
        if ($tunnus==$tilrow['otunnus']) $ero="th";

        if ($logistiikka_yhtio != '') {
          echo "<$ero valign='top'>$tilrow[yhtio_nimi]</$ero>";
        }

        echo "<$ero valign='top' align='right'>$tilrow[t_tyyppi] $tilrow[prioriteetti] ";

        if (trim($tilrow["sisviesti2"]) != "") {
          echo "<div id='div_{$tilrow["div_id"]}_1' class='popup' style='width:500px;'>";
          echo t("Keräyslistan lisätiedot").":<br>".str_replace("\n", "<br>", $tilrow["sisviesti2"]);
          echo "</div><img class='tooltip' id='{$tilrow["div_id"]}_1' src='$palvelin2/pics/lullacons/alert.png'>";
        }

        echo "<br>$tilrow[varastonimi] ";

        if (trim($tilrow["comments"]) != "" or trim($tilrow["kommentti"]) != "") {
          echo "<div id='div_{$tilrow["div_id"]}_2' class='popup' style='width:500px;'>";
          echo t("Lähetteen lisätiedot").":<br>";
          if (trim($tilrow["comments"]) != "") echo str_replace("\n", "<br>", $tilrow["comments"])."<br>";
          if (trim($tilrow["kommentti"]) != "") echo str_replace("\n", "<br>", $tilrow["kommentti"])."<br>";
          echo "</div><img class='tooltip' id='{$tilrow["div_id"]}_2' src='$palvelin2/pics/lullacons/info.png'>";
        }

        echo "</$ero>";

        echo "<$ero valign='top'>$tilrow[tunnus]</$ero>";
        echo "<$ero valign='top'>$tilrow[ytunnus]</$ero>";

        $nimitarklisa = "";

        if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
          echo "<$ero valign='top'>$tilrow[nimi]</$ero>";
        }
        else {
          if ($tilrow['toim_nimitark'] != '') {
            $nimitarklisa = ", $tilrow[toim_nimitark]";
          }
          echo "<$ero valign='top'>$tilrow[toim_nimi]$nimitarklisa</$ero>";
        }

        echo "<$ero valign='top'>$tilrow[viesti]</$ero>";

        if ($tilrow['keraysvko'] != '') {
          echo "<$ero valign='top' nowrap>".t("Vko")." ".date("W", strtotime($tilrow["kerayspvm"]));

          if ($tilrow['keraysvko'] != '7') {
            echo "/".$DAY_ARRAY[$tilrow["keraysvko"]];
          }

          echo "</$ero>";
        }
        else {
          echo "<$ero valign='top'>".tv1dateconv($tilrow["kerayspvm"])."</$ero>";
        }

        if ($tilrow["toimvko"] != '') {
          echo "<$ero valign='top' nowrap>".t("Vko")." ".date("W", strtotime($tilrow["toimaika"]));

          if ($tilrow['toimvko'] != '7') {
            echo "/".$DAY_ARRAY[$tilrow["toimvko"]];
          }

          echo "</$ero>";
        }
        else {
          echo "<$ero valign='top'>".tv1dateconv($tilrow["toimaika"])."</$ero>";
        }

        echo "<$ero valign='top'>$tilrow[riveja]</$ero>";
        echo "<$ero valign='top'><input type='checkbox' name='tulostukseen[]' value='$tilrow[otunnus]' CHECKED></$ero>";

        echo "<$ero valign='top'><a href='$PHP_SELF?lasku_yhtio=$kukarow[yhtio]&toim=$toim&tilaukset=$tilaukset&vanha_tee2=VALITSE&tee2=NAYTATILAUS&tunnus=$tilrow[otunnus]'>".t("Näytä")."</a></$ero>";

        echo "</tr>";
      }
    }
    else {
      $keskenrow = mysql_fetch_array($keskenresult);
      echo t("Tilaus on kesken käyttäjällä").", $keskenrow[nimi], ".t("ota yhteyttä häneen ja käske hänen laittaa vähän vauhtia tähän touhuun")."!<br>";
      $tee2 = '';
    }

    if ($tee2 != '') {
      echo "</table><br>";
      echo "<table>";

      if ($yhtiorow["pakkaamolokerot"] != "") {

        echo "<tr>";
        echo "<th>", t("Ei lokeroa"), "</th>";

        $ei_pakkaamoa_sel = '';

        if ($toim == 'SIIRTOLISTA' and $tila == "G") {
          $ei_pakkaamoa_sel = "checked";
        }

        if ($tila == 'N') {

          if (mysql_num_rows($tilre) > 0) {
            mysql_data_seek($tilre, 0);
            $tilrow = mysql_fetch_array($tilre);
          }

          $query = "SELECT ei_pakkaamoa
                    FROM toimitustapa
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND selite  = '$tilrow[toimitustapa]'";
          $ei_pakkaamoa_res = pupe_query($query);
          $ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

          if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
            $ei_pakkaamoa_sel = "checked";
          }
        }

        echo "<td valign='top'><input type='checkbox' name='ei_pakkaamoa' id='ei_pakkaamoa' value='EI' $ei_pakkaamoa_sel>";
        echo "<input type='hidden' name='ei_pakkaamoa_selected' id='ei_pakkaamoa_selected' value='$ei_pakkaamoa_sel'>";
        echo "</td>";
        echo "</tr>";
      }

      //haetaan keräyslistan oletustulostin
      $query = "SELECT *
                FROM varastopaikat
                WHERE yhtio = '$kukarow[yhtio]'
                AND tunnus  = '$tul_varastoon'";
      $prires = pupe_query($query);
      $prirow = mysql_fetch_array($prires);
      $kirjoitin = $toim == 'VASTAANOTA_REKLAMAATIO' ? $prirow['printteri9'] : $prirow['printteri0'];

      $varasto = $tul_varastoon;
      $tilaus  = $tilaukset;

      require "varaston_tulostusalue.inc";

      echo "<tr>";
      echo "<th>", t("Tulostin"), "</th>";
      echo "<td>";

      $query = "SELECT *
                FROM kirjoittimet
                WHERE yhtio  = '$kukarow[yhtio]'
                AND komento != 'EDI'
                ORDER by kirjoitin";
      $kirre = pupe_query($query);

      echo "<select name='valittu_tulostin'>";

      while ($kirrow = mysql_fetch_array($kirre)) {
        $sel = '';

        //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
        if (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
          $sel = "SELECTED";
        }

        echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
      }

      $sel = '';

      //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
      if (($kirjoitin == "-88" and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kukarow['kirjoitin'] == "-88")) {
        $sel = "SELECTED";
      }

      echo "<option value='-88' $sel>".t("PDF Ruudulle")."</option>";
      echo "</select></td></tr>";
      echo "</table><br><br>";
      echo "<input type='hidden' name='lasku_yhtio' value='$kukarow[yhtio]'>";

      if ($onkologmaster and in_array($prirow['ulkoinen_jarjestelma'], array('L','P'))) {
        echo t("Ulkoisen varaston tilaus");
      }
      else {
        echo "<input type='submit' name='tila' value='".t("Tulosta")."'>";
      }

      echo "</form>";

      echo "<br>";
      echo "<form action = 'lahetteen_tulostusjono.php' method = 'post'>
            <input type='hidden' name='toim' value='$toim'>
            <input type='hidden' name='lopetus' value='$lopetus'>";
      echo "<br><input type='submit' value='".t("Takaisin tilauksen valintaan")."'>";
      echo "</form>";
    }
  }
}

//valitaan keräysklöntti
if ($tee2 == '') {

  if ($lasku_yhtio_originaali != '' and $kukarow['yhtio'] != $lasku_yhtio_originaali) {
    $logistiikka_yhtio = $konsernivarasto_yhtiot;
    $logistiikka_yhtiolisa = "yhtio in ($logistiikka_yhtio)";

    $yhtiorow = hae_yhtion_parametrit($lasku_yhtio_originaali);
    $kukarow['yhtio'] = $lasku_yhtio_originaali;
  }

  /*
    Oletuksia
  */
  if (isset($indexvas) and $indexvas == 1 and $tuvarasto == '') {

    $karajaus = 1;

    if ($yhtiorow["keraysaikarajaus"] != "") {
      $karajaus = $yhtiorow["keraysaikarajaus"];
    }

    // jos käyttäjällä on oletusvarasto, valitaan se
    if ($kukarow['oletus_varasto'] != 0) {
      $tuvarasto = $kukarow['oletus_varasto'];
    }
    //  Varastorajaus jos käyttäjällä on joku varasto valittuna
    elseif ($kukarow['varasto'] != '' and $kukarow['varasto'] != 0) {
      // jos käyttäjällä on monta varastoa valittuna, valitaan ensimmäinen
      $tuvarasto   = strpos($kukarow['varasto'], ',') !== false ? array_shift(explode(",", $kukarow['varasto'])) : $kukarow['varasto'];
    }
    else {
      $tuvarasto   = "KAIKKI";
    }

    $tutoimtapa = "KAIKKI";
    $tutyyppi   = "KAIKKI";
  }

  $haku = '';

  if (is_numeric($karajaus) and $karajaus != 0) {
    $valid = true;
    $haku .= " and lasku.kerayspvm<=date_add(now(), INTERVAL $karajaus day)";
  }
  elseif ($vva != '' and $kka != '' and $ppa != '' and $vvl != '' and $kkl != '' and $ppl != '') {
    $valid = true;
    $alku_paiva = "{$vva}-{$kka}-{$ppa}";
    $loppu_paiva = "{$vvl}-{$kkl}-{$ppl}";
    $valid = FormValidator::validateContent($alku_paiva, 'paiva');

    if ($valid) {
      $valid = FormValidator::validateContent($loppu_paiva, 'paiva');

      if ($valid and strtotime($alku_paiva) > strtotime($loppu_paiva)) {
        echo "<font class='error'>".t('Alkupäivä on myöhemmin kuin loppupäivä')."</font>";
        $valid = false;
      }
    }

    if ($valid) {
      $haku .= " AND lasku.kerayspvm >= '{$alku_paiva}' AND lasku.kerayspvm <= '{$loppu_paiva}'";
    }
    else {
      echo "<font class='error'>".t('Päivämäärät ei ole valideja')."</font>";
    }
  }
  else {
    //karajus == 'KK' eli kaikki
    $valid = true;
  }

  if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
    if (strpos($tuvarasto, "##")) {
      $temp_tuvarasto = explode("##", $tuvarasto);
      $haku .= " and lasku.varasto='$temp_tuvarasto[0]' and lasku.tulostusalue = '$temp_tuvarasto[1]'";
    }
    else {
      $haku .= " and lasku.varasto in ($tuvarasto) ";
    }
  }

  if ($tumaa != '') {
    $query = "SELECT group_concat(tunnus) tunnukset
              FROM varastopaikat
              WHERE maa != ''
              and $logistiikka_yhtiolisa
              and maa    = '$tumaa'";
    $maare = pupe_query($query);
    $maarow = mysql_fetch_array($maare);
    $haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
  }

  if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
    $haku .= " and lasku.toimitustapa='$tutoimtapa' ";
  }

  if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
    if ($tutyyppi == "NORMAA") {
      $haku .= " and lasku.clearing='' ";
    }
    elseif ($tutyyppi == "ENNAKK") {
      $haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
    }
    elseif ($tutyyppi == "JTTILA") {
      $haku .= " and lasku.clearing='JT-TILAUS' ";
    }
    elseif ($tutyyppi == "VALMISTUS") {
      $haku .= " and lasku.sisviesti2='Tehty valmistuksen kautta' ";
    }
  }

  if (!is_numeric($etsi) and $etsi != '') {
    $haku = "AND (lasku.nimi LIKE '%{$etsi}%'
                  OR lasku.toim_nimi LIKE '%{$etsi}%'
                  OR lasku.nimitark LIKE '%{$etsi}%'
                  OR lasku.toim_nimitark LIKE '%{$etsi}%')";
  }

  if (is_numeric($etsi) and $etsi != '') {
    $haku = "and lasku.tunnus='$etsi'";
  }

  $formi  = "find";
  $kentta  = "etsi";

  echo "<table>";
  echo "<form name='find' method='POST' action='lahetteen_tulostusjono.php'>";
  echo "<input type='hidden' name='toim' value='$toim'>";
  echo "<input type='hidden' id='jarj' name='jarj' value='$jarj'>";

  echo "<tr><th>".t("Valitse varasto:")."</th><td><select name='tuvarasto' onchange='submit()'>";

  $query = "SELECT lasku.yhtio_nimi,
            varastopaikat.tunnus,
            varastopaikat.nimitys,
            lasku.tulostusalue,
            count(*) kpl
            FROM varastopaikat
            JOIN lasku ON (varastopaikat.yhtio = lasku.yhtio
              AND ((lasku.tila = '$tila'
                AND lasku.alatila       = '$lalatila') $tila_lalatila_lisa)
              $tilaustyyppi
              AND lasku.varasto         = varastopaikat.tunnus)
            WHERE varastopaikat.$logistiikka_yhtiolisa
              AND varastopaikat.tyyppi != 'P'
            GROUP BY
            lasku.yhtio_nimi,
            varastopaikat.tunnus,
            varastopaikat.nimitys,
            lasku.tulostusalue
            ORDER BY varastopaikat.tyyppi,
            varastopaikat.nimitys,
            lasku.tulostusalue,
            varastopaikat.yhtio";
  $result = pupe_query($query);

  echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";

  $sel = array();
  while ($row = mysql_fetch_array($result)) {
    $sel[$row["tunnus"]] = ($row["tunnus"] == $tuvarasto) ? "selected" : "";
    if ($row['tulostusalue'] != '') {
      echo "<option value='$row[tunnus]##$row[tulostusalue]' ".$sel[$row['tunnus']."##".$row['tulostusalue']].">$row[nimitys] $row[tulostusalue] ($row[kpl])";
    }
    else {
      echo "<option value='$row[tunnus]' ".$sel[$row['tunnus']].">$row[nimitys] ($row[kpl])";
    }

    if ($logistiikka_yhtio != '') {
      echo " ($row[yhtio_nimi])";
    }

    echo "</option>";
  }
  echo "</select>";

  $query = "SELECT varastopaikat.maa, count(*) kpl
            FROM varastopaikat
            JOIN lasku ON (varastopaikat.yhtio = lasku.yhtio and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi and lasku.varasto = varastopaikat.tunnus)
            WHERE varastopaikat.maa != '' and varastopaikat.$logistiikka_yhtiolisa AND varastopaikat.tyyppi != 'P'
            GROUP BY varastopaikat.maa
            ORDER BY varastopaikat.maa";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 1) {
    echo "<select name='tumaa' onchange='submit()'>";
    echo "<option value=''>".t("Kaikki")."</option>";

    $sel=array();
    $sel[$tumaa] = "selected";
    while ($row = mysql_fetch_array($result)) {
      echo "<option value='$row[maa]' ".$sel[$row['maa']].">$row[maa] ($row[kpl])</option>";
    }
    echo "</select>";
  }

  echo "</td>";

  echo "<th>".t("Valitse tilaustyyppi:")."</th><td><select name='tutyyppi' onchange='submit()'>";

  $query = "SELECT IF(sisviesti2 = 'Tehty valmistuksen kautta' and clearing = 'JT-TILAUS', 'VALMISTUS', clearing) AS clearing, count(*) kpl
            FROM lasku
            WHERE {$logistiikka_yhtiolisa}
            and ((tila = '{$tila}' and alatila = '{$lalatila}') {$tila_lalatila_lisa}) {$tilaustyyppi}
            GROUP BY clearing
            ORDER by clearing";
  $result = pupe_query($query);

  echo "<option value='KAIKKI'>", t("Näytä kaikki"), "</option>";

  if (mysql_num_rows($result) > 0) {

    $sel = array_fill_keys(array($tutyyppi), 'selected') + array('NORMAA' => '', 'ENNAKK' => '', 'JTTILA' => '', 'VALMISTUS' => '');

    while ($row = mysql_fetch_assoc($result)) {

      if ($row["clearing"] == "") {
        echo "<option value='NORMAA' {$sel['NORMAA']}>", t("Näytä normaalitilaukset"), " ({$row['kpl']})</option>";
      }
      elseif ($row["clearing"] == "ENNAKKOTILAUS") {
        echo "<option value='ENNAKK' {$sel['ENNAKK']}>", t("Näytä ennakkotilaukset"), " ({$row['kpl']})</option>";
      }
      elseif ($row["clearing"] == "JT-TILAUS") {
        echo "<option value='JTTILA' {$sel['JTTILA']}>", t("Näytä jt-tilaukset"), " ({$row['kpl']})</option>";
      }
      elseif ($row['clearing'] == 'VALMISTUS') {
        echo "<option value='VALMISTUS' {$sel['VALMISTUS']}>", t("Näytä jt-tilaukset valmistuksesta"), " ({$row['kpl']})</option>";
      }
    }
  }

  echo "</select></td></tr>";

  echo "<tr>";
  echo "<th>".t("Valitse toimitustapa:")."</th>";
  echo "<td>";
  echo "<select name='tutoimtapa' onchange='submit()'>";

  $query = "SELECT toimitustapa.selite, count(*) kpl, MIN(toimitustapa.tunnus) tunnus
            FROM toimitustapa
            JOIN lasku ON (toimitustapa.yhtio = lasku.yhtio and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa) $tilaustyyppi and lasku.toimitustapa = toimitustapa.selite)
            WHERE toimitustapa.$logistiikka_yhtiolisa
            GROUP BY toimitustapa.selite
            ORDER BY toimitustapa.selite";
  $result = pupe_query($query);

  echo "<option value='KAIKKI'>".t("Näytä kaikki")."</option>";
  $sel=array();
  while ($row = mysql_fetch_array($result)) {
    $sel[$row["selite"]] = ($row["selite"] == $tutoimtapa) ? "selected" : "";
    echo "<option value='$row[selite]' ".$sel[$row["selite"]].">".t_tunnus_avainsanat($row, "selite", "TOIMTAPAKV")." ($row[kpl])</option>";
  }

  if (!isset($tuoteno)) {$tuoteno = '';}

  echo "</select>";
  echo "</td>";
  echo "<th>".t('Tuotenumero')."</th>";
  echo "<td>";
  echo "<input type='text' name='tuoteno' value='{$tuoteno}' />";
  echo "</td>";
  echo "</tr>";

  if (!empty($valmistuslinjat)) {
    echo "<tr>";
    echo "<th>".t('Valmistuslinja')."</th>";
    echo "<td>";

    echo "<select name='valmistuslinja'>";
    echo "<option value='' >".t('Ei valintaa')."</option>";
    foreach ($valmistuslinjat as $_valmistuslinja) {
      $sel = "";
      if ($_valmistuslinja['selite'] == $valmistuslinja) {
        $sel = "SELECTED";
      }
      echo "<option value='{$_valmistuslinja['selite']}' {$sel}>{$_valmistuslinja['selitetark']}</option>";
    }
    echo "</select>";

    echo "</td>";

    echo "<th></th>";
    echo "<td>";
    echo "</td>";
    echo "</tr>";
  }


  $sel=array('1' => '', '3' => '', '5' => '', '7' => '', '14' => '', 'KK' => '');
  $sel[$karajaus] = "selected";

  echo "<tr><th>".t("Keräysaikarajaus:")."</th>";
  echo "<td>";
  echo "<select name='karajaus' onchange='submit()'>";

  if (!empty($valmistuslinjat)) {
    echo "<option value='0'   $sel[0]>".t("Ei valintaa")."</option>";
  }

  echo "<option value='1'    $sel[1]>".t("Huominen")."</option>
      <option value='3'  $sel[3]>".t("Seuraavat 3 päivää")."</option>
      <option value='5'  $sel[5]>".t("Seuraavat 5 päivää")."</option>
      <option value='7'  $sel[7]>".t("Seuraava viikko")."</option>
      <option value='14' $sel[14]>".t("Seuraavat 2 viikkoa")."</option>
      <option value='KK' $sel[KK]>".t("Näytä kaikki")."</option>
      </select>";

  if (!empty($valmistuslinjat)) {
    echo "<br/>";
    echo "<br/>";
    echo t('TAI');
    echo "<br/>";
    echo "<br/>";
    echo t("Syötä alkupäivämäärä")." (pp-kk-vvvv)";
    echo "<br/>";
    echo "  <input type='text' name='ppa' value='{$ppa}' size='3'>
        <input type='text' name='kka' value='{$kka}' size='3'>
        <input type='text' name='vva' value='{$vva}' size='5'>";
    echo "<br/>";
    echo t("Syötä loppupäivämäärä")." (pp-kk-vvvv)";
    echo "<br/>";
    echo "  <input type='text' name='ppl' value='{$ppl}' size='3'>
        <input type='text' name='kkl' value='{$kkl}' size='3'>
        <input type='text' name='vvl' value='{$vvl}' size='5'>";
  }

  echo "</td>";
  echo "<th>".t("Etsi tilausta").":</th><td><input type='text' name='etsi'>";
  echo "<input type='submit' class='hae_btn' value='".t("Etsi")."'></td></tr>";

  echo "</table>";

  if ($jarj != "") {
    $jarjx = " ORDER BY t_tyyppi desc, $jarj ";
  }
  else {
    $jarjx = " ORDER BY t_tyyppi desc, prioriteetti, kerayspvm, h1time ";
  }

  if ($toim == 'SIIRTOLISTA') {
    $selectlisa = " if (lasku.chn = 'GEN', '2', '1') t_tyyppi2, ";
  }
  else {
    $selectlisa = " if (lasku.clearing = 'ENNAKKOTILAUS', '2', '1') t_tyyppi2, ";
  }

  // Vain keräyslistat saa groupata
  $grouppi = '';
  if (($yhtiorow["lahetteen_tulostustapa"] == "K" or $yhtiorow["lahetteen_tulostustapa"] == "L") and $yhtiorow["kerayslistojen_yhdistaminen"] == "Y") {
    //jos halutaan eritellä tulostusalueen mukaan , lasku.tulostusalue
    $grouppi = "GROUP BY lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus, lasku.toim_ovttunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.toimitustapa, lasku.varasto, jvgrouppi, vientigrouppi, varastonimi, varastotunnus, keraysviikko, lasku.mapvm, t_tyyppi2";
  }
  elseif (($yhtiorow["lahetteen_tulostustapa"] == "K" or $yhtiorow["lahetteen_tulostustapa"] == "L") and $yhtiorow["kerayslistojen_yhdistaminen"] == "T") {
    $grouppi = "GROUP BY lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus";
  }
  else {
    $grouppi = "GROUP BY lasku.tunnus";
  }

  if ($yhtiorow["pakkaamolokerot"] != "") {
    $grouppi .= ", lasku.varasto, lasku.tulostusalue";
  }

  if ($toim == "VASTAANOTA_REKLAMAATIO" and $yhtiorow['reklamaation_kasittely'] == 'X') {
    $grouppi = "GROUP BY lasku.varasto, lasku.yhtio_toimipaikka";
  }

  $tilausrivi_tuoteno_join = '';
  if (isset($tuoteno) and $tuoteno != '') {
    $tilausrivi_tuoteno_join = "  AND tilausrivi.tuoteno = '{$tuoteno}'";
  }

  $valmistuslinja_where = '';
  if (isset($valmistuslinja) and $valmistuslinja != '') {
    $valmistuslinja_where = "  AND lasku.kohde = '{$valmistuslinja}'";
  }

  $siirtolista_where = '';

  if ($toim == "SIIRTOLISTA") {
    $siirtolista_where = " AND lasku.toimitustavan_lahto = 0 ";
  }

  $query = "SELECT lasku.yhtio, lasku.yhtio_nimi, lasku.ytunnus, lasku.toim_ovttunnus, lasku.toim_nimi, lasku.toim_nimitark, lasku.nimi, lasku.nimitark, lasku.toim_osoite, lasku.toim_postino, lasku.toim_postitp, lasku.toim_maa, lasku.varasto,
            lasku.yhtio_toimipaikka,
            lasku.ohjelma_moduli,
            if (tila = 'V', lasku.viesti, lasku.toimitustapa) toimitustapa,
            if (maksuehto.jv!='', lasku.tunnus, '') jvgrouppi,
            if (lasku.vienti!='', lasku.tunnus, '') vientigrouppi,
            varastopaikat.nimitys varastonimi,
            varastopaikat.tunnus varastotunnus,
            week(lasku.kerayspvm, 3) keraysviikko,
            min(if (lasku.hyvaksynnanmuutos = '', 'X', lasku.hyvaksynnanmuutos)) prioriteetti,
            max(if (lasku.clearing = '', 'N', if (lasku.clearing = 'JT-TILAUS', 'J', if (lasku.clearing = 'ENNAKKOTILAUS', 'E', '')))) t_tyyppi,
            $selectlisa
            min(lasku.luontiaika) laadittu,
            min(lasku.h1time) h1time,
            min(lasku.kerayspvm) kerayspvm,
            min(lasku.toimaika) toimaika,
            min(lasku.keraysvko) keraysvko,
            min(lasku.toimvko) toimvko,
            GROUP_CONCAT(distinct lasku.tunnus SEPARATOR ',') otunnus,
            GROUP_CONCAT(distinct lasku.tunnus SEPARATOR '_') div_id,
            count(distinct otunnus) tilauksia,
            count(*) riveja,
            GROUP_CONCAT(DISTINCT if(lasku.comments!='',lasku.comments, NULL) SEPARATOR '\n') comments,
            GROUP_CONCAT(DISTINCT if(lasku.sisviesti2!='',lasku.sisviesti2, NULL) SEPARATOR '\n') sisviesti2,
            GROUP_CONCAT(DISTINCT if(tilausrivi.kommentti!='',tilausrivi.kommentti, NULL) SEPARATOR '\n') kommentti,
            lasku.mapvm,
            round(sum(tuotemassa * (tilausrivi.kpl + tilausrivi.varattu))) AS tilauksen_paino
            FROM lasku
            JOIN tilausrivi
            ON ( tilausrivi.yhtio = lasku.yhtio
              AND tilausrivi.otunnus  = lasku.tunnus
              AND tilausrivi.tyyppi  != 'D'
              {$tilausrivi_tuoteno_join})
            LEFT JOIN varastopaikat ON varastopaikat.yhtio=lasku.yhtio and varastopaikat.tunnus=lasku.varasto
            LEFT JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and lasku.maksuehto=maksuehto.tunnus
            LEFT JOIN tuote ON tuote.yhtio = lasku.yhtio AND tuote.tuoteno = tilausrivi.tuoteno
            WHERE
            lasku.$logistiikka_yhtiolisa
            and ((lasku.tila = '$tila' and lasku.alatila = '$lalatila') $tila_lalatila_lisa)
            $valmistuslinja_where
            $siirtolista_where
            $haku
            $tilaustyyppi
            $grouppi
            $jarjx";

  if ($valid) {
    $tilre = pupe_query($query);
  }

  if (mysql_num_rows($tilre)==0 or !$valid) {
    echo "<br><br><font class='message'>".t("Tulostusjonossa ei ole yhtään tilausta")."...</font>";
  }
  else {
    echo "<br>";
    echo "<table>";
    echo "<tr>";
    if ($logistiikka_yhtio != '') {
      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio'; document.forms['find'].submit();\">".t("Yhtiö")."</a></th>";
    }

    if ($toim == "VASTAANOTA_REKLAMAATIO") {
      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='yhtio_toimipaikka'; document.forms['find'].submit();\">".t("Toimipaikka")."</a></th>";
    }

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='prioriteetti'; document.forms['find'].submit();\">".t("Pri")."</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='varastonimi'; document.forms['find'].submit();\">".t("Varastoon")."</a></th>";

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tilauksia'; document.forms['find'].submit();\">".t("Tilaus")."</a></th>";

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='lasku.ytunnus'; document.forms['find'].submit();\">".t("Asiakas")."</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='lasku.nimi'; document.forms['find'].submit();\">".t("Nimi")."</a></th>";

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='lasku.luontiaika'; document.forms['find'].submit();\">".t("Laadittu")."</a><br>
            <a href='#' onclick=\"getElementById('jarj').value='lasku.h1time'; document.forms['find'].submit();\">".t("Valmis")."</a></th>";

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='kerayspvm'; document.forms['find'].submit();\">".t("Keräysaika")."</a><br>
          <a href='#' onclick=\"getElementById('jarj').value='toimaika'; document.forms['find'].submit();\">".t("Toimitusaika")."</a></th>";

    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='toimitustapa'; document.forms['find'].submit();\">".t("Toimitustapa")."</a></th>";
    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Riv")."</a></th>";
    echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='tilauksen_paino'; document.forms['find'].submit();\">".t("Paino")."</a></th>";

    if ($show_ohjelma_moduli) {
      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='ohjelma_moduli'; document.forms['find'].submit();\">".t("Lähde")."</a></th>";
    }

    if ($yhtiorow["pakkaamolokerot"] != "" or $logistiikka_yhtio != '') {
      echo "<th valign='top'><a href='#' onclick=\"getElementById('jarj').value='riveja'; document.forms['find'].submit();\">".t("Ei lokeroa")."</a></th>";
    }

    echo "<th valign='top'>".t("Tulostin")."</th>";
    echo "<th valign='top'>".t("Tulosta")."</th>";
    echo "<th valign='top'>".t("Näytä")."</th>";
    echo "</tr></form>";

    $tulostakaikki_tun = array();
    $edennakko = "";
    $riveja_yht = 0;

    while ($tilrow = mysql_fetch_array($tilre)) {
      if ($logistiikka_yhtio != '') {
        $kukarow['yhtio'] = $tilrow['yhtio'];
        $yhtiorow = hae_yhtion_parametrit($kukarow["yhtio"]);
      }

      if ($edennakko != "" and $edennakko != $tilrow["t_tyyppi"] and $tilrow["t_tyyppi"] == "E") {
        echo "<tr><td colspan='11' class='back'><br></td></tr>";
      }

      $edennakko = $tilrow["t_tyyppi"];

      $ero="td";
      if (isset($tunnus) and $tunnus==$tilrow['otunnus']) $ero="th";

      echo "<tr class='aktiivi'>";

      if ($logistiikka_yhtio != '') {
        echo "<$ero valign='top'>$tilrow[yhtio_nimi]</$ero>";
      }

      if ($toim == "VASTAANOTA_REKLAMAATIO") {
        if (!empty($tilrow['yhtio_toimipaikka'])) {
          $_tp_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $tilrow['yhtio_toimipaikka']);
          $_tp_row = mysql_fetch_assoc($_tp_res);

          echo "<{$ero}>{$_tp_row['nimi']}</{$ero}>";
        }
        else {
          echo "<{$ero}></{$ero}>";
        }
      }

      echo "<$ero valign='top' align='right'>$tilrow[t_tyyppi] $tilrow[prioriteetti] ";

      if (trim($tilrow["sisviesti2"]) != "") {
        echo "<div id='div_{$tilrow["div_id"]}_1' class='popup' style='width:500px;'>";
        echo t("Keräyslistan lisätiedot").":<br>".str_replace("\n", "<br>", $tilrow["sisviesti2"]);
        echo "</div><img class='tooltip' id='{$tilrow["div_id"]}_1' src='$palvelin2/pics/lullacons/alert.png'>";
      }

      echo "<br>$tilrow[varastonimi] ";

      if (trim($tilrow["comments"]) != "" or trim($tilrow["kommentti"]) != "") {
        echo "<div id='div_{$tilrow["div_id"]}_2' class='popup' style='width:500px;'>";
        echo t("Lähetteen lisätiedot").":<br>";
        if (trim($tilrow["comments"]) != "") echo str_replace("\n", "<br>", $tilrow["comments"])."<br>";
        if (trim($tilrow["kommentti"]) != "") echo str_replace("\n", "<br>", $tilrow["kommentti"])."<br>";
        echo "</div><img class='tooltip' id='{$tilrow["div_id"]}_2' src='$palvelin2/pics/lullacons/info.png'>";
      }

      echo "</$ero>";

      echo "<$ero valign='top'>".str_replace(',', '<br>', $tilrow["otunnus"])."</$ero>";

      if ($toim == "VASTAANOTA_REKLAMAATIO" and $tilrow['tilauksia'] > 1) {
        echo "<{$ero} valign='top'>", t("Useita"), "</{$ero}>";
      }
      else {
        echo "<$ero valign='top'>$tilrow[ytunnus]";

        if ($toim == 'SIIRTOLISTA' or $toim == 'SIIRTOTYOMAARAYS') {
          $_nimitark = "";
          if (!empty($tilrow["nimitark"])) {
            $_nimitark = "<br>".$tilrow["nimitark"];
          }

          echo "<br>$tilrow[nimi]$_nimitark</$ero>";
        }
        else {
          $_toim_nimitark = "";
          if (!empty($tilrow["toim_nimitark"])) {
            $_toim_nimitark = "<br>".$tilrow["toim_nimitark"];
          }

          echo "<br>$tilrow[toim_nimi]$_toim_nimitark</$ero>";
        }
      }

      $laadittu_e   = tv1dateconv($tilrow["laadittu"], "P", "LYHYT");
      $h1time_e    = tv1dateconv($tilrow["h1time"], "P", "LYHYT");
      $h1time_e    = str_replace(substr($laadittu_e, 0, strpos($laadittu_e, " ")), "", $h1time_e);

      echo "<$ero valign='top' nowrap align='right'>$laadittu_e<br>$h1time_e</$ero>";

      if ($tilrow['keraysvko'] != '') {
        echo "<$ero valign='top' nowrap align='right'>".t("Vko")." ".date("W", strtotime($tilrow["kerayspvm"]));

        if ($tilrow['keraysvko'] != '7') {
          echo "/".$DAY_ARRAY[$tilrow["keraysvko"]];
        }
      }
      else {
        echo "<$ero valign='top' align='right'>".tv1dateconv($tilrow["kerayspvm"], "", "LYHYT");
      }

      if ($tilrow["toimvko"] != '') {
        echo "<br>".t("Vko")." ".date("W", strtotime($tilrow["toimaika"]));

        if ($tilrow['toimvko'] != '7') {
          echo "/".$DAY_ARRAY[$tilrow["toimvko"]];
        }

        echo "</$ero>";
      }
      else {
        echo "<br>".tv1dateconv($tilrow["toimaika"], "", "LYHYT")."</$ero>";
      }

      echo "<$ero valign='top'>$tilrow[toimitustapa]</$ero>";
      echo "<$ero valign='top'>$tilrow[riveja]</$ero>";
      echo "<$ero valign='top' align='right'>$tilrow[tilauksen_paino] kg</$ero>";

      if ($show_ohjelma_moduli) {
        echo "<{$ero} valign='top'>" . humanize_ohjelma_moduli($tilrow['ohjelma_moduli']) . "</{$ero}>";
      }

      //haetaan keräyslistan oletustulostin
      $query = "SELECT *
                from varastopaikat
                where yhtio = '$kukarow[yhtio]'
                and tunnus  = '$tilrow[varasto]'";
      $prires = pupe_query($query);
      $prirow = mysql_fetch_array($prires);

      $onkologmaster_varasto = ($onkologmaster and in_array($prirow['ulkoinen_jarjestelma'], array('L','P')));

      if ($tilrow["tilauksia"] > 1) {
        echo "<$ero valign='top'></$ero>";

        if ($yhtiorow["pakkaamolokerot"] != "" or $logistiikka_yhtio != '') {
          echo "<$ero valign='top'></$ero>";
        }

        if (!isset($vva)) $vva = '';
        if (!isset($kka)) $kka = '';
        if (!isset($ppa)) $ppa = '';
        if (!isset($vvl)) $vvl = '';
        if (!isset($kkl)) $kkl = '';
        if (!isset($ppl)) $ppl = '';

        echo "<form method='post'>";
        echo "<input type='hidden' name='toim'       value='$toim'>";
        echo "<input type='hidden' name='jarj'       value='$jarj'>";
        echo "<input type='hidden' name='tuvarasto'   value='$tuvarasto'>";
        echo "<input type='hidden' name='tumaa'     value='$tumaa'>";
        echo "<input type='hidden' name='tutyyppi'     value='$tutyyppi'>";
        echo "<input type='hidden' name='tutoimtapa'   value='$tutoimtapa'>";
        echo "<input type='hidden' name='karajaus'     value='$karajaus'>";
        echo "<input type='hidden' name='vva'      value='$vva'>";
        echo "<input type='hidden' name='kka'      value='$kka'>";
        echo "<input type='hidden' name='ppa'      value='$ppa'>";
        echo "<input type='hidden' name='vvl'      value='$vvl'>";
        echo "<input type='hidden' name='kkl'      value='$kkl'>";
        echo "<input type='hidden' name='ppl'      value='$ppl'>";
        echo "<input type='hidden' name='lasku_yhtio'   value='$tilrow[yhtio]'>";
        echo "<input type='hidden' name='tee2'       value='VALITSE'>";
        echo "<input type='hidden' name='tilaukset'    value='$tilrow[otunnus]'>";
        echo "<$ero valign='top'><input type='submit' name='tila'   value='".t("Valitse")."'></form></$ero>";
        echo "<$ero valign='top'></$ero>";
        echo "</tr>";
      }
      else {
        $kirjoitin = $toim == 'VASTAANOTA_REKLAMAATIO' ? $prirow['printteri9'] : $prirow['printteri0'];

        $varasto = $tilrow["varasto"];
        $tilaus  = $tilrow["otunnus"];

        require "varaston_tulostusalue.inc";

        echo "<form method='post'>";

        if ($yhtiorow["pakkaamolokerot"] != "") {

          $ei_pakkaamoa_sel = '';

          if ($toim == 'SIIRTOLISTA' and $tila == "G") {
            $ei_pakkaamoa_sel = "checked";
          }

          if ($tila == 'N') {
            $query = "SELECT ei_pakkaamoa
                      FROM toimitustapa
                      WHERE yhtio = '$kukarow[yhtio]'
                      AND selite  = '$tilrow[toimitustapa]'";
            $ei_pakkaamoa_res = pupe_query($query);
            $ei_pakkaamoa_row = mysql_fetch_assoc($ei_pakkaamoa_res);

            if ($ei_pakkaamoa_row['ei_pakkaamoa'] == '1' or $tilrow["t_tyyppi"] == "E") {
              $ei_pakkaamoa_sel = "checked";
            }
          }

          echo "<$ero valign='top'><input type='checkbox' name='ei_pakkaamoa' id='ei_pakkaamoa' value='$tilaus' $ei_pakkaamoa_sel>";
          echo "<input type='hidden' name='ei_pakkaamoa_selected' id='ei_pakkaamoa_selected' value='$ei_pakkaamoa_sel'>";
          echo "</$ero>";
        }
        elseif ($logistiikka_yhtio != '') {
          echo "<$ero></$ero>";
        }

        $query = "SELECT *
                  FROM kirjoittimet
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND komento != 'EDI'
                  ORDER BY kirjoitin";
        $kirre = pupe_query($query);

        echo "<$ero valign='top'><select name='valittu_tulostin'>";

        while ($kirrow = mysql_fetch_array($kirre)) {
          $sel = '';

          // tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
          if (($kirrow['tunnus'] == $kirjoitin and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kirrow['tunnus'] == $kukarow['kirjoitin'])) {
            $sel = "SELECTED";
          }

          echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
        }

        $sel = '';

        //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
        if (($kirjoitin == "-88" and ($kukarow['kirjoitin'] == 0 or $lasku_yhtio_originaali != $kukarow["yhtio"])) or ($kukarow['kirjoitin'] == "-88")) {
          $sel = "SELECTED";
        }

        echo "<option value='-88' $sel>".t("PDF Ruudulle")."</option>";
        echo "</select></$ero>";

        echo "<input type='hidden' name='toim'       value='$toim'>";
        echo "<input type='hidden' name='jarj'       value='$jarj'>";
        echo "<input type='hidden' name='tuvarasto'    value='$tuvarasto'>";
        echo "<input type='hidden' name='tumaa'     value='$tumaa'>";
        echo "<input type='hidden' name='tutyyppi'    value='$tutyyppi'>";
        echo "<input type='hidden' name='tutoimtapa'  value='$tutoimtapa'>";
        echo "<input type='hidden' name='karajaus'    value='$karajaus'>";
        echo "<input type='hidden' name='vva'      value='$vva'>";
        echo "<input type='hidden' name='kka'      value='$kka'>";
        echo "<input type='hidden' name='ppa'      value='$ppa'>";
        echo "<input type='hidden' name='vvl'      value='$vvl'>";
        echo "<input type='hidden' name='kkl'      value='$kkl'>";
        echo "<input type='hidden' name='ppl'      value='$ppl'>";
        echo "<input type='hidden' name='tee2'       value='TULOSTA'>";
        echo "<input type='hidden' name='tulostukseen[]' value='$tilrow[otunnus]'>";
        echo "<input type='hidden' name='lasku_yhtio'   value='$tilrow[yhtio]'>";
        echo "<$ero valign='top'>";

        if ($onkologmaster_varasto) {
          echo t("Ulkoisen varaston tilaus");

          $keskenres = tilaus_aktiivinen_kayttajalla($tilrow['otunnus']);

          if (mysql_num_rows($keskenres) != 0) {
            $keskenrow = mysql_fetch_assoc($keskenres);

            echo "<br>";
            echo "<font class='error'>";
            echo t("Tilaus on kesken käyttäjällä %s (%s)", "", $keskenrow['nimi'], $keskenrow['kuka']);
            echo "</font>";
          }
        }
        else {
          echo "<input type='submit' value='".t("Tulosta")."'>";
        }

        echo "</$ero></form>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='toim'       value='$toim'>";
        echo "<input type='hidden' name='jarj'       value='$jarj'>";
        echo "<input type='hidden' name='tuvarasto'   value='$tuvarasto'>";
        echo "<input type='hidden' name='tumaa'     value='$tumaa'>";
        echo "<input type='hidden' name='tutyyppi'     value='$tutyyppi'>";
        echo "<input type='hidden' name='tutoimtapa'  value='$tutoimtapa'>";
        echo "<input type='hidden' name='karajaus'     value='$karajaus'>";
        echo "<input type='hidden' name='vva'      value='$vva'>";
        echo "<input type='hidden' name='kka'      value='$kka'>";
        echo "<input type='hidden' name='ppa'      value='$ppa'>";
        echo "<input type='hidden' name='vvl'      value='$vvl'>";
        echo "<input type='hidden' name='kkl'      value='$kkl'>";
        echo "<input type='hidden' name='ppl'      value='$ppl'>";
        echo "<input type='hidden' name='etsi'       value='$etsi'>";
        echo "<input type='hidden' name='tee2'       value='NAYTATILAUS'>";
        echo "<input type='hidden' name='vanha_tee2'   value=''>";
        echo "<input type='hidden' name='lasku_yhtio'   value='$tilrow[yhtio]'>";
        echo "<input type='hidden' name='tunnus'     value='$tilrow[otunnus]'>";
        echo "<$ero valign='top'><input type='submit' value='".t("Näytä")."'></form></$ero>";

        echo "</tr>";
      }

      // Kerätään tunnukset tulosta kaikki-toimintoa varten
      if (!$onkologmaster_varasto) {
        $tulostakaikki_tun[$tilrow['otunnus']] = $tilrow["yhtio"];
      }

      $riveja_yht += $tilrow["riveja"];
    }

    $spanni = $logistiikka_yhtio != '' ? 7 : 6;

    echo "<tr class='aktiivi'>";
    echo "<th colspan='$spanni'>";

    echo t("Rivejä yhteensä")."</th>";
    echo "<th>".$riveja_yht."</th>";

    if ($show_ohjelma_moduli) {
      $spanni = 5;
    }
    else {
      $spanni = 4;
    }

    if ($toim == "VASTAANOTA_REKLAMAATIO") {
      $spanni++;
    }

    echo "<th colspan='$spanni'></th>";
    echo "</tr>";

    echo "</table>";
    echo "<br>";

    if ($oikeurow['paivitys'] == 1) {
      echo "<table>";
      echo "<form method='post'>";

      if ($toim == 'SIIRTOLISTA') {
        echo "<tr><th colspan='2'>".t("Tulosta kaikki siirtolistat")."</th></tr>";
      }
      elseif ($toim == 'SIIRTOTYOMAARAYS') {
        echo "<tr><th colspan='2'>".t("Tulosta kaikki työmääräykset")."</th></tr>";
      }
      elseif ($toim == 'VALMISTUS') {
        echo "<tr><th colspan='2'>".t("Tulosta kaikki valmistuslistat")."</th></tr>";
      }
      elseif ($toim == 'VASTAANOTA_REKLAMAATIO') {
        echo "<tr><th colspan='2'>".t("Tulosta kaikki purkulistat")."</th></tr>";
      }
      else {
        echo "<tr><th colspan='2'>".t("Tulosta kaikki keräyslistat")."</th></tr>";
      }

      if ($yhtiorow['konsernivarasto'] != '' and $konsernivarasto_yhtiot != '') {
        $logistiikka_yhtio = $konsernivarasto_yhtiot;
      }

      $query = "SELECT komento, min(kirjoitin) kirjoitin, min(tunnus) tunnus
                FROM kirjoittimet
                WHERE $logistiikka_yhtiolisa
                AND komento != 'EDI'
                GROUP BY komento
                ORDER BY jarjestys, kirjoitin";
      $kirre = pupe_query($query);

      echo "<tr><td><select name='valittu_tulostin'>";

      while ($kirrow = mysql_fetch_array($kirre)) {
        $sel = '';

        //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
        if ($kirrow['tunnus'] == $kukarow['kirjoitin']) {
          $sel = "SELECTED";
        }

        // Varaston oletus keräyslistatulostin (tai tarkalleen listan vikan keräyslistan varaston oletustulostin, mutta yleensä listaaan aina per varasto)
        if (isset($kirjoitin) and $kirjoitin != "" and $kukarow['kirjoitin'] == 0 and $kirrow['tunnus'] == $kirjoitin) {
          $sel = "SELECTED";
        }

        echo "<option value='$kirrow[tunnus]' $sel>$kirrow[kirjoitin]</option>";
      }

      $sel = '';

      //tässä vaiheessa käyttäjän oletustulostin ylikirjaa optimaalisen varastotulostimen
      if ($kukarow['kirjoitin'] == "-88") {
        $sel = "SELECTED";
      }

      echo "<option value='-88' $sel>".t("PDF Ruudulle")."</option>";
      echo "</select></td>";

      $tulostakaikki_tun = urlencode(serialize($tulostakaikki_tun));

      echo "<input type='hidden' name='toim'       value='$toim'>";
      echo "<input type='hidden' name='jarj'       value='$jarj'>";
      echo "<input type='hidden' name='tuvarasto'   value='$tuvarasto'>";
      echo "<input type='hidden' name='tumaa'     value='$tumaa'>";
      echo "<input type='hidden' name='tutyyppi'     value='$tutyyppi'>";
      echo "<input type='hidden' name='tutoimtapa'  value='$tutoimtapa'>";
      echo "<input type='hidden' name='karajaus'     value='$karajaus'>";
      echo "<input type='hidden' name='tee2'       value='TULOSTA'>";
      echo "<input type='hidden' name='tulostukseen_kaikki' value='$tulostakaikki_tun'>";
      echo "<td><input type='submit' value='".t("Tulosta kaikki")."'></td></tr></form>";

      echo "</table>";
    }
  }
}

require "inc/footer.inc";
