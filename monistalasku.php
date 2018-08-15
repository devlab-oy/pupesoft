<?php

if (!isset($tee)) $tee = '';
if (!isset($toim)) $toim = '';
if (!isset($ytunnus)) $ytunnus = '';
if (!isset($laskunro)) $laskunro = 0;
if (!isset($otunnus)) $otunnus = 0;
if (!isset($vain_monista)) $vain_monista = '';
if (!isset($tunnukset)) $tunnukset = '';
if (!isset($tunnus)) $tunnus = '';

if ($vain_monista == "") {
  require 'inc/parametrit.inc';

  if ($tee == 'NAYTATILAUS') {
    echo "<font class='head'>".t("Tilaus")." {$tunnus}:</font><hr>";
    require "raportit/naytatilaus.inc";
    echo "<br><br><br>";
    $tee = "ETSILASKU";
  }

  if ($toim == 'SOPIMUS') {
    echo "<font class='head'>".t("Monista sopimus")."</font><hr>";
  }
  elseif ($toim == 'TARJOUS') {
    echo "<font class='head'>".t("Monista tarjous")."</font><hr>";
  }
  elseif ($toim == 'TYOMAARAYS') {
    echo "<font class='head'>".t("Monista tyˆm‰‰r‰ys")."</font><hr>";
  }
  elseif ($toim == 'TILAUS') {
    echo "<font class='head'>".t("Monista tilaus")."</font><hr>";
  }
  elseif ($toim == 'ENNAKKOTILAUS') {
    echo "<font class='head'>".t("Monista ennakkotilaus")."</font><hr>";
  }
  elseif ($toim == 'OSTOTILAUS') {
    echo "<font class='head'>".t("Monista ostotilaus")."</font><hr>";
  }
  else {
    echo "<font class='head'>".t("Monista lasku")."</font><hr>";
  }
}

if ($tee == 'MONISTA' and count($monistettavat) == 0) {
  echo "<font class='error'>", t("Et valinnut yht‰‰n laskua monistettavaksi/hyvitett‰v‰ksi"), "</font><br>";
  $tee = "";
}

$kommenttikentta = $yhtiorow["laskun_monistus_kommenttikentta"];

if ($tee == 'MONISTA' and strlen($kommentti) < 20 and $kommenttikentta == "P") {
  echo "<font class='error'>",
  t("Sinun on annettava kommentti laskun monistuksesta. Kommentin v‰himm‰ispituus on 20 merkki‰."),
  "</font><br>";

  $tee = "";
}
// Monistetaan laskua
if ($toim == '' and $tee == 'MONISTA' and count($monistettavat) > 0) {

  foreach ($monistettavat as $lasku_x => $kumpi_x) {

    // T‰m‰ on hyvitett‰v‰ lasku
    $query = "SELECT tunnus, clearing, vanhatunnus, liitostunnus, laskunro
              FROM lasku
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$lasku_x}'
              AND tila    = 'U'
              AND alatila = 'X'";
    $chk_res = pupe_query($query);
    $chk_row = mysql_fetch_assoc($chk_res);

    // Onko asiakkalla panttitili
    $query = "SELECT panttitili
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$chk_row['liitostunnus']}'";
    $asiakas_panttitili_chk_res = pupe_query($query);
    $asiakas_panttitili_chk_row = mysql_fetch_assoc($asiakas_panttitili_chk_res);

    if ($kumpi_x == 'HYVITA') {
      // jos tilauksella on panttituotteita/sarjanumeroita pit‰‰ est‰‰, ett‰ hyvityst‰ ei saa en‰‰ hyvitt‰‰ (clearing=hyvitys)
      if ($chk_row['clearing'] == 'HYVITYS') {
        $query = "SELECT tilausrivi.otunnus, tuote.panttitili, tuote.sarjanumeroseuranta, tuote.tuoteno, tilausrivi.varattu
                  FROM tilausrivi
                  JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                  WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                  and tilausrivi.tyyppi      = 'L'
                  AND tilausrivi.uusiotunnus = '{$chk_row['tunnus']}'";
        $chk_til_res = pupe_query($query);

        while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {
          if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $chk_til_row['panttitili'] != '') {
            echo "<font class='error'>", t("Et voi hyvitt‰‰ hyvityslaskua, jossa on panttitilillisi‰ tuotteita"), "! ({$lasku_x})</font><br>";
            $tee = "";
            break 2;
          }
          elseif ($chk_til_row["sarjanumeroseuranta"] != "") {
            echo "<font class='error'>", t("Et voi hyvitt‰‰ hyvityslaskua, jossa on sarjanumerollisisa tuotteita"), "! ({$lasku_x})</font><br>";
            $tee = "";
            break 2;
          }
        }
      }
      else {
        // jos tilauksella on panttituotteita/sarjanumeroita pit‰‰ tarkistaa, ett‰ ei anneta hyvitt‰‰ laskua joka on jo hyvitetty (vanhatunnus lˆytyy)
        $query = "SELECT tunnus
                  FROM lasku
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND vanhatunnus = '{$lasku_x}'
                  AND clearing    = 'HYVITYS'
                  AND tila        IN ('N', 'L')";
        $clearing_chk_res = pupe_query($query);

        // Lasku on jo hyvitetty
        if (mysql_num_rows($clearing_chk_res) > 0) {

          while ($clearing_chk_row = mysql_fetch_assoc($clearing_chk_res)) {
            $query = "SELECT tuote.panttitili, tuote.sarjanumeroseuranta
                      FROM tilausrivi
                      JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                      WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                      and tilausrivi.tyyppi  = 'L'
                      AND tilausrivi.otunnus = '{$clearing_chk_row['tunnus']}'";
            $chk_til_res = pupe_query($query);

            while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {
              if ($asiakas_panttitili_chk_row['panttitili'] == "K" and $chk_til_row['panttitili'] != '') {
                echo "<font class='error'>", t("Et voi hyvitt‰‰ tilausta, jossa on panttitilillisi‰ tuotteita ja joka on jo hyvitetty"), "! ({$lasku_x})</font><br>";
                $tee = "";
                break 3;
              }
              elseif ($chk_til_row["sarjanumeroseuranta"] != "") {
                echo "<font class='error'>", t("Et voi hyvitt‰‰ tilausta, jossa on sarjanumerollisia tuotteita ja joka on jo hyvitetty"), "! ({$lasku_x})</font><br>";
                $tee = "";
                break 3;
              }
            }
          }
        }

        if ($asiakas_panttitili_chk_row['panttitili'] == "K") {
          // Hyvitett‰v‰n laskun pantilliset rivit
          $query = "SELECT tilausrivi.otunnus, tilausrivi.tuoteno, sum(tilausrivi.kpl) kpl
                    FROM tilausrivi
                    JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno and tuote.panttitili != '')
                    WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                    and tilausrivi.tyyppi      = 'L'
                    AND tilausrivi.uusiotunnus = '{$chk_row['tunnus']}'
                    AND tilausrivi.kpl         > 0
                    GROUP BY 1, 2";
          $chk_til_res = pupe_query($query);

          if (mysql_num_rows($chk_til_res) > 0) {
            while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {

              // jos tilauksella on panttituotteita ja ollaan tekem‰ss‰ hyvityst‰, pit‰‰ katsoa, ett‰ alkuper‰isen veloituslaskun panttitili rivej‰ ei ole viel‰ k‰ytetty
              $query = "SELECT sum(kpl) kpl
                        FROM panttitili
                        WHERE yhtio               = '{$kukarow['yhtio']}'
                            AND asiakas           = '{$chk_row['liitostunnus']}'
                            AND tuoteno           = '{$chk_til_row['tuoteno']}'
                            AND myyntitilausnro   = '{$chk_til_row['otunnus']}'
                            AND status            = ''
                            AND kaytettypvm       = '0000-00-00'
                            AND kaytettytilausnro = 0";
              $pantti_chk_res = pupe_query($query);
              $pantti_chk_row = mysql_fetch_assoc($pantti_chk_res);

              if ($chk_til_row['kpl'] != $pantti_chk_row['kpl']) {
                echo "<font class='error'>", t("Hyvitett‰v‰n laskun pantit on jo k‰ytetty"), "! ({$lasku_x})</font><br>";
                $tee = "";
                break 2;
              }
            }
          }
        }
      }
    }
    elseif ($asiakas_panttitili_chk_row['panttitili'] == "K" and $kumpi_x == 'MONISTA') {

      $query = "SELECT tuote.panttitili
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno and tuote.panttitili != '')
                WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                and tilausrivi.tyyppi      = 'L'
                AND tilausrivi.uusiotunnus = '{$lasku_x}'";
      $chk_til_res = pupe_query($query);

      while ($chk_til_row = mysql_fetch_assoc($chk_til_res)) {

        $query = "SELECT tunnus
                  FROM lasku
                  WHERE yhtio     = '{$kukarow['yhtio']}'
                  AND vanhatunnus = '{$lasku_x}'
                  AND clearing    = 'HYVITYS'
                  AND tila        = 'U'
                  AND alatila     = 'X'";
        $clearing_chk_res = pupe_query($query);

        if (mysql_num_rows($clearing_chk_res) == 0) {
          echo "<font class='error'>", t("Et voi monistaa tilausta, jossa on panttitilillisi‰ tuotteita ja se on hyvitetty, mutta hyvityst‰ ei ole laskutettu"), "! ({$lasku_x})</font><br>";
          $tee = "";
          break 2;
        }
      }
    }
  }
}

if ($toim == 'TYOMAARAYS') {
  // Halutaanko saldot koko konsernista?
  $query = "SELECT *
            FROM yhtio
            WHERE konserni  = '{$yhtiorow['konserni']}'
            AND konserni   != ''";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $yhtiot = array();

    while ($row = mysql_fetch_assoc($result)) {
      $yhtiot[] = $row["yhtio"];
    }
  }
  else {
    $yhtiot = array();
    $yhtiot[] = $kukarow["yhtio"];
  }
}
else {
  $yhtiot = array();
  $yhtiot[] = $kukarow["yhtio"];
}

if ($tee == '') {

  if ($toim == 'OSTOTILAUS') {
    if ($ytunnus != '') {
      require "inc/kevyt_toimittajahaku.inc";
    }
  }
  else {
    if ($ytunnus != '') {
      require "inc/asiakashaku.inc";
    }
  }

  if ($ytunnus != '') {
    $tee = "ETSILASKU";
  }
  else {
    $tee = "";
  }

  if ($laskunro > 0) {
    $tee = "ETSILASKU";
  }

  if ($otunnus > 0) {
    $tee = 'ETSILASKU';
  }
}

if ($tee == "mikrotila" or $tee == "file") {
  require 'tilauskasittely/mikrotilaus_monistalasku.inc';
}

if ($tee == "ETSILASKU") {
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

  // Jos meill‰ on tunnus tiedossa, haetaan toimittajan/asiakkaan tiedot
  if ($toimittajaid != '') {
    require "inc/kevyt_toimittajahaku.inc";
  }
  elseif ($asiakasid != '') {
    require "inc/asiakashaku.inc";
  }

  // Ei n‰ytet‰ p‰iv‰m‰‰r‰rajauksia, jos etsit‰‰n tilausnumerolla/laskunumerolla
  if ($laskunro == 0 and $otunnus == 0) {
    echo "<form method='post' autocomplete='off'>
        <input type='hidden' name='toim' value='{$toim}'>
        <input type='hidden' name='asiakasid' value='{$asiakasid}'>
        <input type='hidden' name='toimittajaid' value='{$toimittajaid}'>
        <input type='hidden' name='tunnukset' value='{$tunnukset}'>
        <input type='hidden' name='tee' value='ETSILASKU'>";

    echo "<table>";

    echo "<tr><th colspan='4'>";
    if ($toimittajaid != '') {
      echo $toimittajarow["nimi"], " ", $toimittajarow["nimitark"];
    }
    elseif ($asiakasid != '') {
      echo $asiakasrow["nimi"], " ", $asiakasrow["nimitark"];
    }
    echo "</th></tr>";

    echo "<tr><th>".t("Syˆt‰ alkup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppa' value='{$ppa}' size='3'></td>
        <td><input type='text' name='kka' value='{$kka}' size='3'></td>
        <td><input type='text' name='vva' value='{$vva}' size='5'></td>
        </tr><tr><th>".t("Syˆt‰ loppup‰iv‰m‰‰r‰ (pp-kk-vvvv)")."</th>
        <td><input type='text' name='ppl' value='{$ppl}' size='3'></td>
        <td><input type='text' name='kkl' value='{$kkl}' size='3'></td>
        <td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
    echo "<td class='back'><input type='submit' class='hae_btn' value='".t("Hae")."'></td></tr></form></table><br>";
  }

  $limit = "LIMIT 100";

  if ($tunnukset != '') {
    $where   = " tila = 'U' and lasku.tunnus in ({$tunnukset}) ";
    $use   = " ";
    $limit = "";
  }
  elseif ($laskunro > 0) {
    $where   = " tila = 'U' and laskunro = '{$laskunro}' ";
    $use   = " use index (lasno_index) ";
  }
  elseif ($otunnus > 0) {
    //katotaan lˆytyykˆ lasku ja sen kaikki tilaukset
    $query = "SELECT laskunro
              FROM lasku
              WHERE tunnus = '{$otunnus}'
              and yhtio    = '{$kukarow['yhtio']}'";
    $laresult = pupe_query($query);
    $larow = mysql_fetch_assoc($laresult);

    if ($toim == 'SOPIMUS') {
      $where   = " tila = '0' and tunnus = '{$otunnus}' ";
      $use   = " ";

    }
    elseif ($toim == 'TARJOUS') {
      $where   = " tila in ('T','L','N') and tunnus = '{$otunnus}' ";
      $use   = " ";
    }
    elseif ($toim == 'TYOMAARAYS') {
      $where   = " tila in ('N','L','A') and tunnus = '{$otunnus}' ";
      $use   = " ";
    }
    elseif ($toim == 'TILAUS') {
      $where   = " tila in ('N','L') and tunnus = '{$otunnus}' ";
      $use   = " ";
    }
    elseif ($toim == 'ENNAKKOTILAUS') {
      $where   = " tila = 'E' and tunnus = '{$otunnus}' ";
      $use   = " ";
    }
    elseif ($toim == 'OSTOTILAUS') {
      $where   = " tila = 'O' and tunnus = '{$otunnus}' ";
      $use   = " ";
    }
    else {
      if ($larow["laskunro"] > 0) {
        $where   = " tila = 'U' and laskunro = '{$larow['laskunro']}' ";
        $use   = " use index (lasno_index) ";
      }
      else {
        $where   = " tila = 'U' and tunnus = '{$otunnus}' ";
        $use   = " ";
      }
    }
  }
  else {
    if ($toim == 'SOPIMUS') {
      $where = "  tila = '0'
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    elseif ($toim == 'TARJOUS') {
      $where = "  tila in ('T','L','N')
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    elseif ($toim == 'TYOMAARAYS') {
      $where   = " tila in ('N','L','A')
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    elseif ($toim == 'TILAUS') {
      $where   = " tila in ('N','L')
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    elseif ($toim == 'ENNAKKOTILAUS') {
      $where   = " tila = 'E'
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    elseif ($toim == 'OSTOTILAUS') {
      $where   = " tila = 'O'
            and lasku.liitostunnus = '{$toimittajaid}'
            and lasku.luontiaika >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.luontiaika <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " ";
    }
    else {
      $where = "  tila = 'U'
            and lasku.liitostunnus = '{$asiakasid}'
            and lasku.tapvm >='{$vva}-{$kka}-{$ppa} 00:00:00'
            and lasku.tapvm <='{$vvl}-{$kkl}-{$ppl} 23:59:59' ";
      $use   = " use index (yhtio_tila_liitostunnus_tapvm) ";
    }
  }

  echo "  <script type='text/javascript'>

      $(function() {

        $('.nayta_rivit').on('click', function() {
          var id = $(this).attr('id');
          var rows = $('#row_'+id);

          if (rows.is(':visible')) {
            rows.hide();
            $(this).val('", t("N‰yt‰ rivit"), "');
          }
          else {
            rows.show();
            $(this).val('", t("Piilota rivit"), "');
          }
        });

        $('.check_all').on('click', function() {
          var id = $(this).val();

          if ($(this).is(':checked')) {
            $('.'+id).attr('checked', true);
          }
          else {
            $('.'+id).attr('checked', false);
          }
        });

        $('.toiminnot_chk').on('click', function() {
          if ($(this).val() == 'REKLAMA' && '{$yhtiorow['reklamaation_hinnoittelu']}' == 'K') {
            $('.'+$(this).attr('id')).attr('checked', true).show();
          }
          else {
            $('.'+$(this).attr('id')).attr('checked', false).hide();
          }
        });
      });

      </script>";

  // Etsit‰‰n muutettavaa tilausta
  $query = "SELECT yhtio, tunnus 'tilaus', laskunro, concat_ws(' ', nimi, nimitark) asiakas,
            ytunnus, summa, tapvm, luontiaika, laatija, tila, alatila
            FROM lasku {$use}
            WHERE {$where}
            AND yhtio in ('".implode("','", $yhtiot)."')
            ORDER BY tapvm, lasku.tunnus DESC
            $limit";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    echo "  <form method='post' autocomplete='off'>
    <input type='hidden' name='kklkm' value='1'>
    <input type='hidden' name='toim' value='{$toim}'>
    <input type='hidden' name='tee' value='MONISTA'>
    <input type='hidden' name='laskunro' value='{$laskunro}'>
    <input type='hidden' name='otunnus' value='{$otunnus}'>
    <input type='hidden' name='ytunnus' value='{$ytunnus}'>
    <input type='hidden' name='asiakasid' value='{$asiakasid}'>
    <input type='hidden' name='toimittajaid' value='{$toimittajaid}'>
    <input type='hidden' name='kka' value='{$kka}'>
    <input type='hidden' name='vva' value='{$vva}'>
    <input type='hidden' name='ppa' value='{$ppa}'>
    <input type='hidden' name='kkl' value='{$kkl}'>
    <input type='hidden' name='vvl' value='{$vvl}'>
    <input type='hidden' name='ppl' value='{$ppl}'>";

    echo "<table>";
    echo "<tr>";

    if ($toim != '') {
      echo "<th>".t("Tilaus")."</th>";
    }

    echo "<th>".t("Laskunro")."</th>";
    echo "<th>".t("Asiakas")."</th>";
    echo "<th>".t("Ytunnus")."</th>";
    echo "<th>".t("Summa")."</th>";
    echo "<th>".t("Tapvm")."</th>";
    echo "<th>".t("Luontiaika")."</th>";
    echo "<th>".t("Laatija")."</th>";
    echo "<th>".t("Tyyppi")."</th>";
    echo "<th>".t("Toiminto")."</th>";

    if ($toim == '') {
      echo "<th>".t("Toiminnot")."</th>";
    }

    echo "<th>".t("N‰yt‰")."</th></tr>";

    while ($row = mysql_fetch_assoc($result)) {
      echo "<tr>";
      $ero = "td";

      if ($tunnus == $row['tilaus']) $ero = "th";

      echo "<tr class='aktiivi'>";

      if ($toim != '') {
        echo "<{$ero}>{$row['tilaus']}</{$ero}>";
      }
      echo "<{$ero}>";
      echo "{$row['laskunro']}";

      if ($toim == '') {
        echo "<br />";
        echo "<input class='nayta_rivit' type='button' id='{$row['tilaus']}' value='", t("N‰yt‰ rivit"), "' />";
      }

      echo "</{$ero}>";

      echo "<{$ero}>{$row['asiakas']}</{$ero}>";
      echo "<{$ero}>", tarkistahetu($row['ytunnus']), "</{$ero}>";
      echo "<{$ero}>{$row['summa']}</{$ero}>";
      echo "<{$ero}>".tv1dateconv($row["tapvm"])."</{$ero}>";
      echo "<{$ero}>".tv1dateconv($row["luontiaika"])."</{$ero}>";
      echo "<{$ero}>{$row['laatija']}</{$ero}>";

      $laskutyyppi = $row["tila"];
      $alatila   = $row["alatila"];

      //tehd‰‰n selv‰kielinen tila/alatila
      require "inc/laskutyyppi.inc";

      echo "<{$ero} valign='top'>".t($laskutyyppi)." ".t($alatila)."</{$ero}>";
      echo "<{$ero} valign='top'>";

      $selmo = $selhy = $selre = "";
      if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'MONISTA') $selmo = "CHECKED";
      if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'HYVITA')  $selhy = "CHECKED";
      if (isset($monistettavat[$row["tilaus"]]) and $monistettavat[$row["tilaus"]] == 'REKLAMA') $selre = "CHECKED";

      if ($toim == '') {
        echo "<input type='radio' id='rekla_{$row['tilaus']}' name='monistettavat[{$row['tilaus']}]' value='MONISTA' {$selmo} class='toiminnot_chk'>".t("Monista")."<br>";
        echo "<input type='radio' id='rekla_{$row['tilaus']}' name='monistettavat[{$row['tilaus']}]' value='HYVITA' {$selhy} class='toiminnot_chk'>".t("Hyvit‰")."<br>";
        echo "<input type='radio' id='rekla_{$row['tilaus']}' name='monistettavat[{$row['tilaus']}]' value='REKLAMA' {$selre} class='toiminnot_chk'>".t("Reklamaatio")."<br>";
      }
      else {
        echo "<input type='checkbox' name='monistettavat[{$row['tilaus']}]' value='MONISTA' {$selmo}>".t("Monista")."<br>";
      }

      if ($toim == '') {
        echo "<{$ero} valign='top' nowrap>";

        // Katotaan ettei yksik‰‰n tuote ole sarjanumeroseurannassa, silloin ei voida turvallisesti laittaa suoraan laskutukseen
        $query = "SELECT tuote.sarjanumeroseuranta
                  FROM tilausrivi
                  JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.sarjanumeroseuranta != '')
                  WHERE tilausrivi.yhtio     = '{$row['yhtio']}'
                  AND tilausrivi.uusiotunnus = '{$row['tilaus']}'";
        $res = pupe_query($query);

        if (mysql_num_rows($res) == 0) {
          $sel = "";
          if (isset($suoraanlasku[$row["tilaus"]]) and $suoraanlasku[$row["tilaus"]] != '') {
            $sel = "CHECKED";
          }
          echo "<input type='checkbox' name='suoraanlasku[{$row['tilaus']}]' value='on' {$sel}> ".t("Suoraan laskutukseen")."<br>";
        }

        $sel = "";
        if (isset($sailytaprojekti[$row["tilaus"]]) and $sailytaprojekti[$row["tilaus"]] != '') {
          $sel = "CHECKED";
        }

        echo "<input type='checkbox' name='sailytaprojekti[{$row['tilaus']}]' value='on' {$sel}> ".t("S‰ilyt‰ projektitiedot")."<br>";

        if ($toim == '') {
          $sel = "";
          if (isset($sailytatyomaarays[$row["tilaus"]]) and $sailytatyomaarays[$row["tilaus"]] != '') {
            $sel = "CHECKED";
          }

          echo "<input type='checkbox' name='sailytatyomaarays[{$row['tilaus']}]' value='on' {$sel}> ".t("S‰ilyt‰ tyˆm‰‰r‰ystiedot")."<br>";
        }

        if ($toim == '' and $yhtiorow['rahti_hinnoittelu'] == '') {
          $sel = "";
          if (isset($korjaarahdit[$row["tilaus"]]) and $korjaarahdit[$row["tilaus"]] != '') {
            $sel = "CHECKED";
          }

          echo "<input type='checkbox' name='korjaarahdit[{$row['tilaus']}]' value='on' {$sel}> ".t("Laske rahtiveloitus uudestaan")."<br>";
        }

        echo "<input type='checkbox'
                     name='sailyta_rivikommentit[{$row['tilaus']}]'
                     value='on'>" . t('S‰ilyt‰ rivikommentit') . "<br>";

        echo "<input type='checkbox'
                     name='verkkotunnus_laskulta[{$row['tilaus']}]'
                     value='on'>" . t('S‰ilyt‰ laskun verkkolaskutustunnus') . "<br>";

        if ($toim == '') {

          $display_none = "style='display:none;'";

          if (isset($kaytetaanhyvityshintoja[$row["tilaus"]]) and $kaytetaanhyvityshintoja[$row["tilaus"]] != '') {
            $sel = "CHECKED";
            $display_none = "";
          }

          echo "<input class='rekla_{$row['tilaus']}' {$display_none} type='checkbox' name='kaytetaanhyvityshintoja[{$row['tilaus']}]' value='on' {$sel}> <span {$display_none} class='rekla_{$row['tilaus']}'>", t("K‰ytet‰‰n reklamaatiolla hyvityshintoja"), "</span>";
        }

        echo "</{$ero}>";
      }

      echo "<{$ero} valign='top'><a href='?tunnus={$row['tilaus']}&tunnukset={$tunnukset}&asiakasid={$asiakasid}&otunnus={$otunnus}&laskunro={$laskunro}&ppa={$ppa}&kka={$kka}&vva={$vva}&ppl={$ppl}&kkl={$kkl}&vvl={$vvl}&tee=NAYTATILAUS&toim={$toim}'>".t("N‰yt‰")."</a></{$ero}>";

      echo "</tr>";

      if ($toim == '') {

        $query = "SELECT liitostunnus, GROUP_CONCAT(tunnus) AS otsikot
                  FROM lasku
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND laskunro = '{$row['laskunro']}'
                  AND tila     = 'U'
                  AND alatila  = 'X'
                  GROUP BY 1";
        $otsikot_res = pupe_query($query);
        $otsikot_row = mysql_fetch_assoc($otsikot_res);

        if ($otsikot_row['otsikot'] != '') {

          $query_ale_lisa = generoi_alekentta('M');

          // "N‰yt‰ rivit"-toiminto
          $query = "SELECT tilausrivin_lisatiedot.*, tilausrivi.*,
                    if (tilausrivi.tyyppi='V', 0, tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) rivihinta,
                    if (tilausrivi.tyyppi='V', 0, tilausrivi.hinta / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.kpl+tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) arvo,
                    if (tilausrivi.tyyppi='V', 0, if (tilausrivi.kpl!=0, tilausrivi.rivihinta * if (tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1), tilausrivi.hinta * if ('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa})) summa
                    FROM tilausrivi
                    LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio=tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus=tilausrivi.tunnus)
                    LEFT JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio=tilausrivi.yhtio and tuotteen_toimittajat.tuoteno=tilausrivi.tuoteno and tuotteen_toimittajat.liitostunnus = '{$otsikot_row['liitostunnus']}')
                    WHERE tilausrivi.yhtio     = '{$kukarow['yhtio']}'
                    AND tilausrivi.uusiotunnus IN ({$otsikot_row['otsikot']})
                    AND tilausrivi.tyyppi      = 'L'";
          $nayta_rivit_res = pupe_query($query);

          if (mysql_num_rows($nayta_rivit_res) > 0) {

            echo "<tr class='back' id='row_{$row['tilaus']}' style='display: none;'>";

            echo "<td colspan='11'>";

            echo "<table style='width: 100%;'>";
            echo "<tr>";
            echo "<th>#</th>";
            echo "<th>", t("Nimitys"), "</th>";
            echo "<th>", t("Tuotenumero"), "</th>";
            echo "<th>", t("Til. M‰‰r‰"), "</th>";
            echo "<th>", t("M‰‰r‰"), "</th>";
            echo "<th>", t("Tila"), "</th>";
            echo "<th>", t("Netto"), "</th>";

            if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>", t("Svh"), "</th>";

            if ($kukarow['hinnat'] == 0) {
              for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                echo "<th style='text-align:right;'>", t("Ale"), "{$alepostfix}%</th>";
              }
            }

            if ($kukarow['hinnat'] == 0) echo "<th style='text-align:right;'>", t("Hinta"), "</th>";
            if ($kukarow['hinnat'] >= 0) echo "<th style='text-align:right;'>", t("Rivihinta"), "</th>";

            if ($kukarow['extranet'] == '' and ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y"))) {
              echo "<th style='text-align:right;'>".t("Kate")."</th>";
            }

            echo "<th style='text-align:right;'>".t("Alv%")."</th>";

            if (!isset($valitse_rivit)) $chk = 'checked';
            echo "<th><input class='check_all' type='checkbox' value='{$row['tilaus']}' {$chk} /></th>";

            echo "</tr>";

            $rivilaskuri = mysql_num_rows($nayta_rivit_res);

            if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
              $rivino = 0;
            }
            else {
              $rivino = $rivilaskuri+1;
            }

            $query = "SELECT *
                      FROM lasku
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  IN ({$otsikot_row['otsikot']})
                      LIMIT 1";
            $laskures = pupe_query($query);
            $laskurow = mysql_fetch_assoc($laskures);

            while ($nayta_rivit_row = mysql_fetch_assoc($nayta_rivit_res)) {

              if ($yhtiorow["tilauksen_jarjestys_suunta"] == "ASC") {
                $rivino++;
              }
              else {
                $rivino--;
              }

              echo "<tr>";
              echo "<td>{$rivino}</td>";
              echo "<td>{$nayta_rivit_row['nimitys']}</td>";
              echo "<td>{$nayta_rivit_row['tuoteno']}</td>";
              echo "<td>", ($nayta_rivit_row["tilkpl"]*1), "</td>";

              if ($nayta_rivit_row["kpl"] != 0) {
                $kpl_ruudulle = $nayta_rivit_row['kpl'] * 1;
              }
              elseif ($nayta_rivit_row["var"] == 'J') {
                $kpl_ruudulle = $nayta_rivit_row['jt'] * 1;
              }
              elseif ($nayta_rivit_row["var"] == 'S' or $nayta_rivit_row["var"] == 'T' or $nayta_rivit_row["var"] == 'U') {
                $kpl_ruudulle = $nayta_rivit_row['jt'] * 1;
              }
              elseif ($nayta_rivit_row["var"] == 'P') {
                $kpl_ruudulle = $nayta_rivit_row['tilkpl'] * 1;
              }
              else {
                $kpl_ruudulle = $nayta_rivit_row['varattu'] * 1;
              }

              echo "<td align='right' valign='top' nowrap>{$kpl_ruudulle}</td>";

              $var_temp = var_kaannos($nayta_rivit_row['var']);
              echo "<td>{$var_temp}</td>";
              echo "<td>{$nayta_rivit_row['netto']}</td>";

              $query = "SELECT *
                        FROM tuote
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tuoteno = '{$nayta_rivit_row['tuoteno']}'";
              $tres = pupe_query($query);
              $trow = mysql_fetch_assoc($tres);

              $kpl         = $nayta_rivit_row["varattu"]+$nayta_rivit_row["jt"]+$nayta_rivit_row['kpl'];
              $myyntihinta  = hintapyoristys(tuotteen_myyntihinta($laskurow, $trow, 1));
              $bruttorivi    = $nayta_rivit_row["hinta"] * $kpl;

              if ($kukarow['hinnat'] == 1) {
                echo "<td align='right' valign='top' nowrap>$myyntihinta</td>";
              }
              elseif ($kukarow['hinnat'] == 0) {

                if ($myyntihinta != $nayta_rivit_row["hinta"]) $myyntihinta = hintapyoristys($myyntihinta);
                else $myyntihinta = hintapyoristys($myyntihinta);

                echo "<td align='right' valign='top' nowrap>$myyntihinta</td>";

                for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  echo "<td align='right' valign='top' nowrap>".($nayta_rivit_row["ale{$alepostfix}"] * 1)."</td>";
                }

                echo "<td align='right' valign='top' nowrap>".hintapyoristys($nayta_rivit_row["hinta"]);

                if ($trow["myyntihinta_maara"] > 1) {
                  echo "<br />".hintapyoristys($nayta_rivit_row["hinta"]*$trow["myyntihinta_maara"])." / $trow[myyntihinta_maara]";
                }

                echo "</td>";
              }

              if ($kukarow['hinnat'] == 1) {
                echo "<td align='right' valign='top' nowrap>".hintapyoristys($bruttorivi)."</td>";
              }
              elseif ($kukarow['hinnat'] == 0) {

                if ($yhtiorow["alv_kasittely"] == "") {
                  //verolliset hinnat
                  echo "<td align='right' valign='top' nowrap>".hintapyoristys($nayta_rivit_row["summa"])."</td>";
                }
                else {
                  echo "<td align='right' valign='top' nowrap>".hintapyoristys($nayta_rivit_row["arvo"])."</td>";
                }
              }

              if ($kukarow["naytetaan_katteet_tilauksella"] == "Y" or ($kukarow["naytetaan_katteet_tilauksella"] == "" and $yhtiorow["naytetaan_katteet_tilauksella"] == "Y")) {
                // T‰n rivin kate
                $kate = 0;

                if ($laskurow["tapvm"] != '0000-00-00') {

                  if ($nayta_rivit_row["kpl"] == 0) {
                    $kate = "";
                  }
                  elseif ($nayta_rivit_row["rivihinta"] != 0) {
                    if ($nayta_rivit_row["kate"] < 0) {
                      $kate = sprintf('%.2f', -1 * abs(100 * $nayta_rivit_row["kate"] / $nayta_rivit_row["rivihinta"]))."%";
                    }
                    else {
                      $kate = sprintf('%.2f', abs(100 * $nayta_rivit_row["kate"] / $nayta_rivit_row["rivihinta"]))."%";
                    }
                  }
                  elseif ($nayta_rivit_row["kate"] != 0) {
                    $kate = "-100.00%";
                  }

                  if ($nayta_rivit_row["tyyppi"] != "V" and $nayta_rivit_row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += $nayta_rivit_row["kate"];
                }
                elseif ($kukarow['extranet'] == '' and ($nayta_rivit_row["sarjanumeroseuranta"] == "S")) {
                  if ($kpl > 0) {
                    //Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on myynti‰
                    $ostohinta = sarjanumeron_ostohinta("myyntirivitunnus", $nayta_rivit_row["tunnus"]);

                    // Kate = Hinta - Ostohinta
                    if ($nayta_rivit_row["rivihinta"] != 0) {
                      $kate = sprintf('%.2f', 100*($nayta_rivit_row["rivihinta"] - ($ostohinta * $kpl))/$nayta_rivit_row["rivihinta"])."%";
                    }

                    if ($nayta_rivit_row["tyyppi"] != "V" and $nayta_rivit_row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($nayta_rivit_row["rivihinta"] - ($ostohinta * $kpl));
                  }
                  elseif ($kpl < 0 and $nayta_rivit_row["osto_vai_hyvitys"] == "") {
                    //Jos tuotteella yll‰pidet‰‰n in-out varastonarvo ja kyseess‰ on HYVITYSTƒ

                    //T‰h‰n hyvitysriviin liitetyt sarjanumerot
                    $query = "SELECT sarjanumero, kaytetty
                              FROM sarjanumeroseuranta
                              WHERE yhtio        = '$kukarow[yhtio]'
                              and ostorivitunnus = '$nayta_rivit_row[tunnus]'";
                    $sarjares = pupe_query($query);

                    $ostohinta = 0;

                    while ($sarjarow = mysql_fetch_assoc($sarjares)) {

                      // Haetaan hyvitett‰vien myyntirivien kautta alkuper‰iset ostorivit
                      $query  = "SELECT tilausrivi.rivihinta/tilausrivi.kpl ostohinta
                                 FROM sarjanumeroseuranta
                                 JOIN tilausrivi use index (PRIMARY) ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.ostorivitunnus
                                 WHERE sarjanumeroseuranta.yhtio          = '$kukarow[yhtio]'
                                 and sarjanumeroseuranta.tuoteno          = '$nayta_rivit_row[tuoteno]'
                                 and sarjanumeroseuranta.sarjanumero      = '$sarjarow[sarjanumero]'
                                 and sarjanumeroseuranta.kaytetty         = '$sarjarow[kaytetty]'
                                 and sarjanumeroseuranta.myyntirivitunnus > 0
                                 and sarjanumeroseuranta.ostorivitunnus   > 0
                                 ORDER BY sarjanumeroseuranta.tunnus
                                 LIMIT 1";
                      $sarjares1 = pupe_query($query);
                      $sarjarow1 = mysql_fetch_assoc($sarjares1);

                      $ostohinta += $sarjarow1["ostohinta"];
                    }

                    // Kate = Hinta - Alkuper‰inen ostohinta
                    if ($nayta_rivit_row["rivihinta"] != 0) {
                      $kate = sprintf('%.2f', 100 * ($nayta_rivit_row["rivihinta"]*-1 - $ostohinta)/$nayta_rivit_row["rivihinta"])."%";
                    }
                    else {
                      $kate = "100.00%";
                    }

                    if ($nayta_rivit_row["tyyppi"] != "V" and $nayta_rivit_row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($nayta_rivit_row["rivihinta"]*-1 - $ostohinta);
                  }
                  else {
                    $kate = "N/A";
                  }
                }
                elseif ($kukarow['extranet'] == '') {

                  if ($nayta_rivit_row["tyyppi"] == "V") {
                    $kate = "";
                  }
                  elseif ($nayta_rivit_row["rivihinta"] != 0) {
                    $kate = sprintf('%.2f', 100*($nayta_rivit_row["rivihinta"] - (kehahin($nayta_rivit_row["tuoteno"])*($nayta_rivit_row["varattu"]+$nayta_rivit_row["jt"]+$nayta_rivit_row['kpl'])))/$row["rivihinta"])."%";
                  }
                  elseif (kehahin($nayta_rivit_row["tuoteno"]) != 0) {
                    $kate = "-100.00%";
                  }

                  if ($nayta_rivit_row["tyyppi"] != "V" and $nayta_rivit_row["tuoteno"] != $yhtiorow["ennakkomaksu_tuotenumero"]) $kate_yht += ($nayta_rivit_row["rivihinta"] - (kehahin($nayta_rivit_row["tuoteno"])*($nayta_rivit_row["varattu"]+$nayta_rivit_row["jt"]+$nayta_rivit_row['kpl'])));
                }

                echo "<td align='right' valign='top' nowrap>{$kate}</td>";
              }

              if ($nayta_rivit_row["alv"] >= 600) {
                echo "<td align='right' valign='top' nowrap>", t("K.V."), "</td>";
              }
              elseif ($nayta_rivit_row["alv"] >= 500) {
                echo "<td align='right' valign='top' nowrap>", t("M.V."), "</td>";
              }
              else {
                echo "<td align='right' valign='top' nowrap>", ($nayta_rivit_row["alv"] * 1), "</td>";
              }

              $chk = (!isset($valitse_rivit) or in_array($nayta_rivit_row['tunnus'], $valitse_rivit)) ? "checked" : "";

              echo "<td><input class='{$row['tilaus']}' type='checkbox' name='valitse_rivit[{$row['tilaus']}][]' value='{$nayta_rivit_row['tunnus']}' {$chk} /></td>";

              echo "</tr>";
            }

            echo "<tr><td class='back' colspan='16'>&nbsp;</td></tr>";

            echo "</table>";
            echo "</td>";
            echo "</tr>";
          }
        }
      }
    }

    echo "</table><br>";

    if ($kommenttikentta) {
      $required = $kommenttikentta == "P" ? "required" : "";

      $label_text = t("Monistuskommentti");

      echo "<label>{$label_text}
              <br>
              <textarea rows='3'
                        cols='40'
                        name='kommentti'
                        minlength='20'
                        placeholder='" . t("Kommentti monistuksesta") . "'
                        {$required}>{$kommentti}</textarea>
            </label>
            <br>";
    }

    echo "<input type='submit' value='".t("Monista")."'></form>";

    echo "<br>";
    echo "<form action = 'monistalasku.php' method = 'post'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>";
    echo "<br><input type='submit' value='".t("Tee uusi haku")."'>";
    echo "</form>";
  }
  else {
    echo "<font class='error'>", t("Haulla ei lˆytynyt yht‰‰n tilausta").".<br>";

    // N‰ytet‰‰n haku uudestaan, jos yritettiin etsi‰ numerolla ja ei lˆydetty
    if ($laskunro != 0 or $otunnus != 0) {
      $tee = "";
    }
  }
}

if ($tee == 'MONISTA') {

  // $tunnus joka on array joss on monistettavat laskut
  // $kklkm kopioiden m‰‰r‰
  // Jos hyvit‰ on 'on', niin silloin $kklkm t‰ytyy aina olla 1
  // $suoraanlasku array sanoo ett‰ tilausta ei ker‰t‰ vaan se menee suoraan laskutusjonoon

  // Otetaan uudet tunnukset talteen
  $tulos_ulos = array();

  if (count($monistettavat) == 0) {
    echo "<font class='error'>Et valinnut yht‰‰n laskua monistettavaksi/hyvitett‰v‰ksi</font><br>";
    $tee = "";
  }

  foreach ($monistettavat as $lasku => $kumpi) {

    $slask      = "";
    $sprojekti  = "";
    $koptyom    = "";
    $korjrahdit = "";

    if (isset($suoraanlasku[$lasku]) and $suoraanlasku[$lasku] != '')           $slask      = "on";
    if (isset($sailytaprojekti[$lasku]) and $sailytaprojekti[$lasku] != '')     $sprojekti  = "on";
    if (isset($sailytatyomaarays[$lasku]) and $sailytatyomaarays[$lasku] != '') $koptyom    = "on";
    if (isset($korjaarahdit[$lasku]) and $korjaarahdit[$lasku] != '')           $korjrahdit = "on";
    if (isset($kaytetaanhyvityshintoja[$lasku]) and $kaytetaanhyvityshintoja[$lasku] != '')  $kaythyvit = "on";

    if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
      $kklkm = 1;
      echo t("Hyvitet‰‰n")." ";
    }
    else {
      echo t("Kopioidaan")." ";
    }

    if ($toim == 'SOPIMUS') {
      echo "{$kklkm} ".t("sopimus(ta)").".<br><br>";
    }
    elseif ($toim == 'TARJOUS') {
      echo "{$kklkm} ".t("tarjous(ta)").".<br><br>";
    }
    elseif ($toim == 'TYOMAARAYS') {
      echo "{$kklkm} ".t("tyˆm‰‰r‰ys(t‰)").".<br><br>";
    }
    elseif ($toim == 'TILAUS' or $toim == 'ENNAKKOTILAUS') {
      echo "{$kklkm} ".t("tilaus(ta)").".<br><br>";
    }
    elseif ($toim == 'OSTOTILAUS') {
      echo "{$kklkm} ".t("ostotilaus(ta)").".<br><br>";
    }
    else {
      echo "{$kklkm} ".t("lasku(a)").".<br><br>";
    }

    for ($monta = 1; $monta <= $kklkm; $monta++) {

      $query = "SELECT *
                FROM lasku
                WHERE tunnus = '{$lasku}'
                AND yhtio    IN ('".implode("','", $yhtiot)."')";
      $monistares = pupe_query($query);
      $monistarow = mysql_fetch_assoc($monistares);

      $squery = "SELECT *
                 FROM asiakas
                 WHERE yhtio = '{$kukarow['yhtio']}'
                 AND tunnus  = '{$monistarow['liitostunnus']}'";
      $asiakres = pupe_query($squery);
      $asiakrow = mysql_fetch_assoc($asiakres);

      $fields = "yhtio";
      $values = "'$kukarow[yhtio]'";

      // Ei monisteta tunnusta
      for ($i = 1; $i < mysql_num_fields($monistares) - 1; $i++) {
        $fieldname = mysql_field_name($monistares, $i);
        $fields .= ", ".$fieldname;
        switch ($fieldname) {

        case 'ytunnus':
        case 'liitostunnus':
        case 'nimi':
        case 'nimitark':
        case 'osoite':
        case 'postino':
        case 'postitp':
        case 'toim_nimi':
        case 'toim_nimitark':
        case 'toim_osoite':
        case 'toim_postino':
        case 'toim_postitp':
        case 'yhtio_nimi':
        case 'yhtio_osoite':
        case 'yhtio_postino':
        case 'yhtio_postitp':
        case 'yhtio_maa':
        case 'yhtio_ovttunnus':
        case 'yhtio_kotipaikka':
        case 'yhtio_toimipaikka':
        case 'myyja':
        case 'kassalipas':
        case 'ovttunnus':
        case 'toim_ovttunnus':
        case 'maa':
        case 'toim_maa':
          if ($kukarow["yhtio"] != $monistarow["yhtio"]) {
            $values .= ", ''";
          }
          else {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          break;
        case 'verkkotunnus':
          if (empty($verkkotunnus_laskulta[$lasku]) and !empty($asiakrow["verkkotunnus"])) {
            // halutaan k‰ytt‰‰ asiakkaan nykyist‰ verkkolaskutunnusta
            $values .= ", '{$asiakrow['verkkotunnus']}'";
          }
          else {
            // halutaan k‰ytt‰‰ verkkolaskutunnusta vanhalta laskulta
            $values .= ", '{$monistarow['verkkotunnus']}'";
          }
          break;
        case 'maksuehto':

          $query = "SELECT tunnus, jv
                    FROM maksuehto
                    WHERE yhtio  = '{$kukarow['yhtio']}'
                    AND kaytossa = ''
                    AND (sallitut_maat = '' OR sallitut_maat LIKE '%{$monistarow['maa']}%')
                    AND tunnus   = '{$monistarow[$fieldname]}'";
          $abures = pupe_query($query);

          $maksuehto_ok = TRUE;

          if (mysql_num_rows($abures) == 1) {
            $aburow = mysql_fetch_assoc($abures);

            if ($kumpi == 'HYVITA' and $aburow["jv"] != "") {
              // Ei laiteta j‰lkivaatimusta hyvityslaskulle
              $maksuehto_ok = FALSE;
            }
          }
          else {
            // Maksuehtoa ei en‰‰ lˆydy
            $maksuehto_ok = FALSE;
          }

          if ($maksuehto_ok) {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          else {
            // Otetaan firman eka maksuehto
            $query = "SELECT tunnus
                      FROM maksuehto
                      WHERE yhtio     = '{$kukarow['yhtio']}'
                      AND kaytossa    = ''
                      AND (sallitut_maat = '' OR sallitut_maat LIKE '%{$monistarow['maa']}%')
                      AND kateinen    = ''
                      AND jv          = ''
                      AND jaksotettu  = ''
                      AND erapvmkasin = ''
                      ORDER BY jarjestys, teksti, tunnus
                      LIMIT 1";
            $abures = pupe_query($query);
            $aburow = mysql_fetch_assoc($abures);

            $values .= ", '{$aburow['tunnus']}'";
          }
          break;
        case 'directdebitsiirtonumero':
          $values .= ", 0";

          break;
        case 'toimaika':
          if (($kumpi == 'HYVITA' or $kumpi == 'REKLAMA' or $yhtiorow["tilausrivien_toimitettuaika"] == 'X') and $toim != 'OSTOTILAUS') {
            $values .= ", '{$monistarow[$fieldname]}'";
          }
          else {
            $values .= ", now()";
          }
          break;
        case 'kerayspvm':
        case 'luontiaika':
          $values .= ", now()";
          break;
        case 'alatila':
          if ($toim == 'SOPIMUS') {
            $values .= ", 'V'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'tila':
          if ($kumpi == 'REKLAMA') {
            $values .= ", 'C'";
          }
          elseif ($toim == 'SOPIMUS') {
            $values .= ", '0'";
          }
          elseif ($toim == 'TARJOUS') {
            $values .= ", 'T'";
          }
          elseif ($toim == 'TYOMAARAYS' or $koptyom == 'on') {
            $values .= ", 'A'";
          }
          elseif ($toim == 'OSTOTILAUS') {
            $values .= ", 'O'";
          }
          elseif ($toim == 'ENNAKKOTILAUS') {
            $values .= ", 'E'";
          }
          else {
            $values .= ", 'N'";
          }
          break;
        case 'tilaustyyppi':
          if ($kumpi == 'REKLAMA') {
            $values .= ", 'R'";
            break;
          }
          elseif ($toim == 'TYOMAARAYS' or $koptyom == 'on') {
            $values .= ", 'A'";
            break;
          }
          elseif ($toim == 'TARJOUS') {
            $values .= ", 'T'";
            break;
          }
          elseif ($toim == 'ENNAKKOTILAUS') {
            $values .= ", 'E'";
            break;
          }
          // vientitiedot
        case 'maa_maara':
        case 'maa_lahetys':
        case 'kuljetusmuoto':
        case 'kauppatapahtuman_luonne':
        case 'sisamaan_kuljetus':
        case 'sisamaan_kuljetusmuoto':
        case 'sisamaan_kuljetus_kansallisuus':
        case 'kontti':
        case 'aktiivinen_kuljetus':
        case 'aktiivinen_kuljetus_kansallisuus':
        case 'poistumistoimipaikka':
        case 'poistumistoimipaikka_koodi':
        case 'bruttopaino':
        case 'lisattava_era':
        case 'vahennettava_era':
        case 'ultilno':
          if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'chn':
          if ($monistarow[$fieldname] == '999' and $monistarow['mapvm'] != '0000-00-00') {
            $values .= ", '".$asiakrow[$fieldname]."'";
          }
          else {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          break;
        case 'tunnus':
        case 'tapvm':
        case 'kapvm':
        case 'erpcm':
        case 'suoraveloitus':
        case 'olmapvm':
        case 'summa':
        case 'summa_valuutassa':
        case 'kasumma':
        case 'kasumma_valuutassa':
        case 'hinta':
        case 'kate':
        case 'arvo':
        case 'arvo_valuutassa':
        case 'saldo_maksettu':
        case 'saldo_maksettu_valuutassa':
        case 'pyoristys':
        case 'pyoristys_valuutassa':
        case 'maksaja':
        case 'lahetepvm':
        case 'h1time':
        case 'lahetepvm':
        case 'laskuttaja':
        case 'laskutettu':
        case 'viite':
        case 'laskunro':
        case 'mapvm':
        case 'tilausvahvistus':
        case 'viikorkoeur':
        case 'tullausnumero':
        case 'kerayslista':
        case 'viikorkoeur':
        case 'noutaja':
        case 'jaksotettu':
        case 'factoringsiirtonumero':
        case 'laskutuspvm':
        case 'maksuaika':
          $values .= ", ''";
          break;
        case 'kate_korjattu':
        case 'lahetetty_ulkoiseen_varastoon':
          $values .= ", NULL";
          break;
        case 'toimitustavan_lahto':
        case 'toimitustavan_lahto_siirto':
          $values .= ", 0";
          break;
        case 'clearing':
          if ($kumpi == 'HYVITA') {
            $values .= ", 'HYVITYS'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'vanhatunnus':
          if ($kumpi == 'HYVITA') {
            $values .= ", '{$lasku}'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'laatija':
          $values .= ", '{$kukarow['kuka']}'";
          break;
        case 'tunnusnippu':
          if ($sprojekti == "on") {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'eilahetetta':
          if ($slask == 'on') {
            echo t("Tilaus laitetaan suoraan laskutusjonoon")."<br>";
            $values .= ", 'o'";
          }
          else {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          break;
        case 'alv':
          //Korjataanko laskun alvit
          if ($alvik == "on") {
            // katsotaan miten vienti ja ALV k‰sitell‰‰n
            $alv_velvollisuus = "";
            $uusi_alv = 0;

            // jos meill‰ on lasku menossa ulkomaille
            if (isset($asiakrow["maa"]) and $asiakrow["maa"] != "" and $asiakrow["maa"] != $yhtiorow["maa"]) {
              // tutkitaan ollaanko siell‰ alv-rekisterˆity
              $alhqur = "SELECT *
                         FROM yhtion_toimipaikat
                         WHERE yhtio     = '$kukarow[yhtio]'
                         AND maa         = '$asiakrow[maa]'
                         AND vat_numero != ''";
              $alhire = pupe_query($alhqur);

              // ollaan alv-rekisterˆity, aina kotimaa myynti ja alvillista
              if (mysql_num_rows($alhire) == 1) {
                $alhiro  = mysql_fetch_assoc($alhire);

                // haetaan maan oletusalvi
                $query = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='ALVULK' and selitetark='o' and selitetark_2='$asiakrow[maa]'";
                $alhire = pupe_query($query);

                // jos ei lˆydy niin menn‰‰n erroriin
                if (mysql_num_rows($alhire) == 0) {
                  echo "<font class='error'>".t("VIRHE: Oletus ALV-kantaa ei lˆydy asiakkaan maahan")." $asiakrow[maa]!</font><br>";
                }
                else {
                  $apuro  = mysql_fetch_assoc($alhire);
                  // n‰m‰ t‰ss‰ keisiss‰ aina n‰in
                  $uusi_alv        = $apuro["selite"];
                  $vienti       = "";
                  $alv_velvollisuus = $alhiro["vat_numero"];
                }
              }
            }

            //yhtiˆn oletusalvi!
            $wquery = "SELECT selite from avainsana where yhtio='$kukarow[yhtio]' and laji='alv' and selitetark!=''";
            $wtres  = pupe_query($wquery);
            $wtrow  = mysql_fetch_assoc($wtres);

            if ($alv_velvollisuus != "") {
              $uusi_alv = $uusi_alv;
            }
            elseif ($asiakrow["vienti"] == '') {

              if ($asiakrow['alv'] == 0) {
                $uusi_alv = 0;
              }

              if ($asiakrow['alv'] == $wtrow["selite"]) {
                $uusi_alv = $wtrow['selite'];
              }
            }
            else {
              $uusi_alv = 0;
            }

            $values .= ", '{$uusi_alv}'";

            echo t("Korjataan laskun ALVia").":  {$monistarow['alv']} --> {$uusi_alv}<br>";
          }
          else {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          break;
        case 'ketjutus':
          if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
            echo t("Hyvityst‰/ALV-korjausta ei ketjuteta")."<br>";
            $values .= ", 'x'";
          }
          else {
            $values .= ", '".$monistarow[$fieldname]."'";
          }
          break;
        case 'viesti':
          if ($kumpi == 'HYVITA') {
            $values .= ", '".t("Hyvitys laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
          }
          elseif ($kumpi == 'REKLAMA') {
            $values .= ", '".t("Reklamaatio laskuun", $asiakrow['kieli']).": ".$monistarow["laskunro"].".'";
          }
          else {
            $values .= ", ''";
          }
          break;
        case 'vienti_kurssi';
          // hyvityksiss‰ pidet‰‰n kurssi samana, tai jos korjataan rahtikuluja
          if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA' or ($toim == '' and $kumpi == 'MONISTA' and $korjrahdit == 'on')) {
            if ($monistarow[$fieldname] == 0) {
              // Vanhoilla u-laskuilla ei ole vienti kurssia....
              $vienti_kurssi = @round($monistarow["arvo"] / $monistarow["arvo_valuutassa"], 9);

              $values .= ", '{$vienti_kurssi}'";
            }
            else {
              $values .= ", '".$monistarow[$fieldname]."'";
            }
          }
          else {
            $vquery = "SELECT kurssi
                       FROM valuu
                       WHERE yhtio = '{$kukarow['yhtio']}'
                       and nimi    = '{$monistarow['valkoodi']}'";
            $vresult = pupe_query($vquery);
            $valrow = mysql_fetch_assoc($vresult);
            $values .= ", '{$valrow['kurssi']}'";
          }
          break;
        default:
          $values .= ", '".$monistarow[$fieldname]."'";
        }
      }

      $kysely  = "INSERT into lasku ({$fields}) VALUES ({$values})";
      $insres  = pupe_query($kysely);
      $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$utunnus}'";
      $laskures = pupe_query($query);
      $laskurow = mysql_fetch_assoc($laskures);

      if ($kommenttikentta and !empty($kommentti)) {
        $tallennettava_kommentti =
          trim($laskurow["sisviesti3"] .
          "\nMonistuskommentti:\n" .
          $kommentti);

        $kommentti_query = "UPDATE lasku SET
                            sisviesti3  = '{$tallennettava_kommentti}'
                            WHERE yhtio = '{$kukarow['yhtio']}'
                            AND tunnus  = '{$utunnus}'";

        pupe_query($kommentti_query);
      }

      $tulos_ulos[] = $utunnus;

      if ($toim == 'SOPIMUS') {
        echo t("Uusi sopimusnumero on")." <a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=YLLAPITO&tilausnumero=$utunnus'>{$utunnus}</a><br><br>";
      }
      elseif ($toim == 'TARJOUS') {
        echo t("Uusi tarjousnumero on")." <a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=TARJOUS&tilausnumero=$utunnus'>{$utunnus}</a><br><br>";
      }
      else {
        echo t("Uusi tilausnumero on")." <a href='{$palvelin2}tilauskasittely/tilaus_myynti.php?toim=PIKATILAUS&tilausnumero=$utunnus'>{$utunnus}</a><br><br>";
      }

      //  P‰ivitet‰‰n myˆs tunnusnippu jotta t‰t‰ voidaan versioida..
      if ($toim == "TARJOUS" and $yhtiorow["tarjouksen_voi_versioida"] != "") {
        $kysely = "UPDATE lasku SET
                   tunnusnippu = tunnus
                   WHERE yhtio = '{$kukarow['yhtio']}' and tunnus = '{$utunnus}'";
        $updres = pupe_query($kysely);
      }

      if ($toim == "TARJOUS" and $monistarow["jaksotettu"] > 0) {

        // Oliko meill‰ maksusopparia?
        $query = "SELECT *
                  FROM maksupositio
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otunnus = '{$monistarow['jaksotettu']}'";
        $sompmonres = pupe_query($query);

        if (mysql_num_rows($sompmonres) > 0) {

          while ($sopmonrow = mysql_fetch_assoc($sompmonres)) {

            $fields = "yhtio";
            $values = "'{$kukarow['yhtio']}'";

            // Ei monisteta tunnusta
            for ($i = 1; $i < mysql_num_fields($sompmonres) - 1; $i++) {
              $fieldname = mysql_field_name($sompmonres, $i);
              $fields .= ", ".$fieldname;

              switch ($fieldname) {
              case 'otunnus':
                $values .= ", '{$utunnus}'";
                break;
              default:
                $values .= ", '".$monistalisrow[$fieldname]."'";
              }
            }

            $kysely  = "INSERT INTO maksupositio ({$fields}) VALUES ({$values})";
            $insres3 = pupe_query($kysely);
          }

          //  P‰ivitet‰‰n jaksotettu myˆs laskulle
          $kysely = "UPDATE lasku SET
                     jaksotettu  = '{$utunnus}'
                     WHERE yhtio = '{$kukarow['yhtio']}'
                     AND tunnus  = '{$utunnus}'";
          $updres = pupe_query($kysely);
        }
      }

      //Kopioidaan otsikon lisatiedot
      $query = "SELECT *
                FROM laskun_lisatiedot
                WHERE otunnus = '{$lasku}'
                AND yhtio     = '{$monistarow['yhtio']}'";
      $monistalisres = pupe_query($query);

      if (mysql_num_rows($monistalisres) > 0) {
        $monistalisrow = mysql_fetch_assoc($monistalisres);

        $fields = "yhtio";
        $values = "'{$kukarow['yhtio']}'";

        // Ei monisteta tunnusta
        for ($i = 1; $i < mysql_num_fields($monistalisres) - 1; $i++) {
          $fieldname = mysql_field_name($monistalisres, $i);
          $fields .= ", ".$fieldname;

          switch ($fieldname) {
          case 'otunnus':
            $values .= ", '{$utunnus}'";
            break;
          default:
            $values .= ", '".$monistalisrow[$fieldname]."'";
          }
        }

        $kysely = "INSERT INTO laskun_lisatiedot ({$fields}) VALUES ({$values})";
        $insres2 = pupe_query($kysely);
      }

      if ($toim == 'TYOMAARAYS' or $koptyom == 'on' or $kumpi == 'REKLAMA') {

        if ($koptyom == 'on') {
          $query = "SELECT DISTINCT otunnus AS tyomaarays
                    FROM tilausrivi
                    WHERE uusiotunnus = '{$lasku}'
                    AND kpl           <> 0
                    AND tyyppi        = 'L'
                    AND yhtio         = '{$monistarow['yhtio']}'
                    ORDER BY tunnus
                    LIMIT 1";
          $monistalisres = pupe_query($query);
          $monistalisrow = mysql_fetch_assoc($monistalisres);

          $tyomaarays = $monistalisrow["tyomaarays"];
        }
        else {
          $tyomaarays = $lasku;
        }

        //Kopioidaan otsikon tyˆm‰‰r‰ystiedot
        $query = "SELECT *
                  FROM tyomaarays
                  WHERE otunnus = '{$tyomaarays}'
                  AND yhtio     = '{$monistarow['yhtio']}'";
        $monistalisres = pupe_query($query);
        $monistalisrow = mysql_fetch_assoc($monistalisres);

        $fields = "yhtio";
        $values = "'{$kukarow['yhtio']}'";

        for ($i = 1; $i < mysql_num_fields($monistalisres); $i++) {
          $fieldname = mysql_field_name($monistalisres, $i);
          $fields .= ", ".$fieldname;

          switch ($fieldname) {
          case 'otunnus':
            $values .= ", '{$utunnus}'";
            break;
          case 'valmnro':
            if ($yhtiorow['laiterekisteri_kaytossa'] != '') {
              $values .= ", ''";
            }
            else {
              $values .= ", '".$monistalisrow[$fieldname]."'";
            }
            break;
          default:
            $values .= ", '".$monistalisrow[$fieldname]."'";
          }
        }

        $kysely = "INSERT INTO tyomaarays ({$fields}) VALUES ({$values})";
        $insres2 = pupe_query($kysely);
      }

      if ($toim == 'SOPIMUS' or $toim == 'TARJOUS' or $toim == 'TYOMAARAYS' or $toim == 'TILAUS' or $toim == 'OSTOTILAUS' or $toim == 'ENNAKKOTILAUS') {
        $query = "SELECT *
                  FROM tilausrivi
                  WHERE otunnus = '{$lasku}'
                  AND yhtio     = '{$monistarow['yhtio']}'
                  ORDER BY otunnus, tunnus";
      }
      else {

        $tunnuslisa = "";

        if ($toim == '' and in_array($kumpi, array('MONISTA', 'HYVITA', 'REKLAMA')) and isset($valitse_rivit) and isset($valitse_rivit[$lasku]) and count($valitse_rivit[$lasku]) > 0) {
          $tunnuslisa   = "AND tunnus IN (".implode(",", $valitse_rivit[$lasku]).")";
        }

        $query = "SELECT *
                  FROM tilausrivi
                  WHERE uusiotunnus = '{$lasku}'
                  AND kpl           <> 0
                  AND tyyppi        = 'L'
                  AND yhtio         = '{$monistarow['yhtio']}'
                  {$tunnuslisa}
                  ORDER BY otunnus, tunnus";
      }
      $rivires = pupe_query($query);

      $_rivit = array();

      while ($rivirow = mysql_fetch_assoc($rivires)) {

        $palautus = array();

        if ($toim == '' and $kumpi == 'REKLAMA' and isset($kaytetaanhyvityshintoja[$lasku]) and $kaytetaanhyvityshintoja[$lasku] != '') {
          $_kpl = $rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"];
          $palautus = hae_hyvityshinta($laskurow["liitostunnus"], $rivirow['tuoteno'], $_kpl);

          $_orig_kommentti = $rivirow['kommentti'];

          if (count($palautus) > 0) {
            $rivirow['hinta'] = $palautus[0]["hinta"];
            $rivirow['kommentti'] = trim($rivirow['kommentti']) != '' ? "{$rivirow['kommentti']} {$palautus[0]['kommentti']}" : $palautus[0]['kommentti'];
            $rivirow['varattu'] = $palautus[0]["kpl"];
            $rivirow['kpl'] = 0;
            $rivirow['ale1'] = $palautus[0]['ale'];
            for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              $rivirow['ale'.$alepostfix] = 0;
            }

            array_push($_rivit, $rivirow);

            if (count($palautus) > 1) {
              // eka pois
              array_shift($palautus);

              foreach ($palautus as $_palautusrow) {
                $_arr = array(
                  'hinta' => $_palautusrow['hinta'],
                  'kommentti' => trim($_orig_kommentti) != '' ? "{$_orig_kommentti} {$_palautusrow['kommentti']}" : $_palautusrow['kommentti'],
                  'varattu' => $_palautusrow['kpl'],
                  'kpl' => 0,
                  'ale1' => $_palautusrow['ale'],
                );

                for ($alepostfix = 2; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                  $_arr['ale'.$alepostfix] = 0;
                }

                $rivirow = $_arr + $rivirow;
                array_push($_rivit, $rivirow);
              }
            }
          }
        }
        else {
          array_push($_rivit, $rivirow);
        }
      }

      foreach ($_rivit as $rivirow) {
        $uusikpl = 0;

        $pquery = "SELECT tunnus
                   FROM tuotepaikat
                   WHERE yhtio   = '{$monistarow['yhtio']}'
                   AND tuoteno   = '{$rivirow['tuoteno']}'
                   AND hyllyalue = '{$rivirow['hyllyalue']}'
                   AND hyllynro  = '{$rivirow['hyllynro']}'
                   AND hyllyvali = '{$rivirow['hyllyvali']}'
                   AND hyllytaso = '{$rivirow['hyllytaso']}'";
        $presult = pupe_query($pquery);

        if (mysql_num_rows($presult) == 0) {
          // Onko alkuper‰isess‰ vcarastossa toinen/uusi paikka?
          $pquery = "SELECT *
                     FROM tuotepaikat
                     WHERE yhtio = '{$monistarow['yhtio']}'
                     AND tuoteno = '{$rivirow['tuoteno']}'
                     AND varasto = '{$rivirow['varasto']}'
                     LIMIT 1";
          $presult = pupe_query($pquery);

          if (mysql_num_rows($presult) == 1) {
            $uusipaikka = mysql_fetch_assoc($presult);
          }
          else {
            // Jos paikkaa ei ole olemassa perustetaan sellainen varaston oletustietojen perusteella
            $query = "SELECT alkuhyllyalue, alkuhyllynro
                      FROM varastopaikat
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$rivirow['varasto']}'";
            $oletus_tuotepaikka_res = pupe_query($query);
            $oletus_tuotepaikka_row = mysql_fetch_assoc($oletus_tuotepaikka_res);

            $uusipaikka = lisaa_tuotepaikka($rivirow['tuoteno'], $oletus_tuotepaikka_row['alkuhyllyalue'], $oletus_tuotepaikka_row['alkuhyllynro'], 0, 0, "", "", 0, 0, 0);
          }

          $rivirow["hyllyalue"] = $uusipaikka["hyllyalue"];
          $rivirow["hyllynro"]  = $uusipaikka["hyllynro"];
          $rivirow["hyllyvali"] = $uusipaikka["hyllyvali"];
          $rivirow["hyllytaso"] = $uusipaikka["hyllytaso"];
        }

        $rfields = "yhtio";
        $rvalues = "'{$kukarow['yhtio']}'";

        for ($i = 1; $i < mysql_num_fields($rivires) - 1; $i++) {
          $fieldname = mysql_field_name($rivires, $i);
          $rfields .= ", ".$fieldname;
          switch ($fieldname) {

          case 'toimaika':
            if (($yhtiorow["tilausrivien_toimitettuaika"] == 'X' and $toim != 'OSTOTILAUS') or $toim == 'SOPIMUS') {
              $rvalues .= ", '".$rivirow[$fieldname]."'";
            }
            else {
              $rvalues .= ", now()";
            }
            break;
          case 'kerayspvm':
          case 'laadittu':
            if ($toim == 'SOPIMUS') {
              $rvalues .= ", '".$rivirow[$fieldname]."'";
            }
            else {
              $rvalues .= ", now()";
            }
            break;
          case 'tunnus':
          case 'laskutettu':
          case 'laskutettuaika':
          case 'toimitettu':
          case 'toimitettuaika':
          case 'keratty':
          case 'kerattyaika':
          case 'kpl':
          case 'rivihinta':
          case 'rivihinta_valuutassa':
          case 'kate':
          case 'uusiotunnus':
          case 'jaksotettu':
            $rvalues .= ", ''";
            break;
          case 'kate_korjattu':
            $rvalues .= ", NULL";
            break;
          case 'kommentti':
            if (($toim == 'SOPIMUS' or
                $toim == 'TARJOUS' or
                $toim == 'TYOMAARAYS' or
                $toim == 'TILAUS' or
                $toim == 'OSTOTILAUS' or
                $toim == 'ENNAKKOTILAUS') or
              ($toim == '' and
                $kumpi == 'REKLAMA' and
                isset($kaytetaanhyvityshintoja[$lasku]) and
                $kaytetaanhyvityshintoja[$lasku] != '' and
                count($palautus) > 0) or
              $sailyta_rivikommentit[$lasku] == "on") {
              $rvalues .= ", '{$rivirow['kommentti']}'";
            }
            else {
              $rvalues .= ", ''";
            }
            break;
          case 'otunnus':
            $rvalues .= ", '{$utunnus}'";
            break;
          case 'laatija':
            $rvalues .= ", '{$kukarow['kuka']}'";
            break;
          case 'varattu':
            if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
              $uusikpl = ($rivirow["kpl"] + $rivirow["varattu"]) * -1;
              $rvalues .= ", '{$uusikpl}'";

            }
            else {
              $uusikpl = ($rivirow["kpl"] + $rivirow["varattu"]);
              $rvalues .= ", '{$uusikpl}'";
            }
            break;
          case 'jt':
            if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
              $rvalues .= ", '".($rivirow["jt"] * -1)."'";
            }
            else {
              $rvalues .= ", '".($rivirow["jt"])."'";
            }
            break;
          case 'tilkpl':
            if ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') {
              $rvalues .= ", '".(($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"]) * -1)."'";
            }
            else {
              $rvalues .= ", '".($rivirow["kpl"] + $rivirow["jt"] + $rivirow["varattu"])."'";
            }
            break;
          case 'alv':
            //Korjataanko tilausrivin alvit
            if ($alvik == "on") {
              $rvalues .= ", '{$uusi_alv}'";
              $rivirow['orig_alv'] = $rivirow[$fieldname];
            }
            else {
              $rvalues .= ", '".$rivirow[$fieldname]."'";
            }
            break;
          case 'tyyppi':
            // Tarjouskase
            if ($toim == 'TARJOUS') {
              $rvalues .= ", 'T'";
            }
            else {
              $rvalues .= ", '".$rivirow[$fieldname]."'";
            }
            break;
          case 'suuntalava':
            if ($toim == 'OSTOTILAUS') {
              //ei kopsata suuntalavan tietoa aka must be 0!
              $rvalues .= ", 0";
            }
            else {
              $rvalues .= ", '".$rivirow[$fieldname]."'";
            }
            break;
          default:
            $rvalues .= ", '".$rivirow[$fieldname]."'";
          }
        }

        $kysely = "INSERT INTO tilausrivi ({$rfields}) VALUES ({$rvalues})";
        $insres = pupe_query($kysely);
        $insid = mysql_insert_id($GLOBALS["masterlink"]);

        //Kopioidaan tilausrivin lisatiedot
        $query = "SELECT *
                  FROM tilausrivin_lisatiedot
                  WHERE tilausrivitunnus = '{$rivirow['tunnus']}'
                  and yhtio              = '{$monistarow['yhtio']}'";
        $monistares2 = pupe_query($query);

        if (mysql_num_rows($monistares2) > 0) {
          $monistarow2 = mysql_fetch_assoc($monistares2);

          $kysely = "INSERT INTO tilausrivin_lisatiedot
                     SET yhtio       = '{$kukarow['yhtio']}',
                     laatija          = '{$kukarow['kuka']}',
                     luontiaika       = now(),
                     tilausrivitunnus = {$insid},";

          for ($i = 0; $i < mysql_num_fields($monistares2) - 1; $i++) {
            $fieldname = mysql_field_name($monistares2, $i);

            switch ($fieldname) {
            case 'yhtio':
            case 'laatija':
            case 'luontiaika':
            case 'tilausrivitunnus':
            case 'tiliointirivitunnus':
            case 'tilausrivilinkki':
            case 'toimittajan_tunnus':
            case 'tunnus':
            case 'muutospvm':
            case 'muuttaja':
              break;
            case 'osto_vai_hyvitys':
              if ($monistarow2[$fieldname] == "O" and ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA')) {
                $kysely .= $fieldname."='H',";
              }
              elseif ($monistarow2[$fieldname] == "H" and ($kumpi == 'HYVITA' or $kumpi == 'REKLAMA')) {
                $kysely .= $fieldname."='O',";
              }
              else {
                $kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
              }
              break;
            default:
              $kysely .= $fieldname."='".$monistarow2[$fieldname]."',";
            }
          }

          $kysely  = substr($kysely, 0, -1);
          $insres2 = pupe_query($kysely);
        }

        // Kopsataan sarjanumerot kuntoon jos tilauksella oli sellaisia
        if (($kumpi == 'HYVITA' or $kumpi == 'REKLAMA') and $kukarow["yhtio"] == $monistarow["yhtio"]) {
          if ($rivirow["kpl"] > 0) {
            $tunken = "myyntirivitunnus";
            $tunken2 = "ostorivitunnus";
          }
          else {
            $tunken = "ostorivitunnus";
            $tunken2 = "myyntirivitunnus";
          }

          $query = "SELECT *
                    FROM sarjanumeroseuranta
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND tuoteno = '{$rivirow['tuoteno']}'
                    AND {$tunken} = '{$rivirow['tunnus']}'
                    AND {$tunken2} = 0";
          $sarjares = pupe_query($query);

          while ($sarjarow = mysql_fetch_assoc($sarjares)) {
            if ($uusikpl > 0) {
              $uusi_tunken = "myyntirivitunnus";
            }
            else {
              $uusi_tunken = "ostorivitunnus";
            }

            $query = "SELECT sarjanumeroseuranta
                      FROM tuote
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tuoteno = '{$rivirow['tuoteno']}'";
            $sarjatuoteres = pupe_query($query);
            $sarjatuoterow = mysql_fetch_assoc($sarjatuoteres);

            if ($sarjatuoterow["sarjanumeroseuranta"] == "E" or $sarjatuoterow["sarjanumeroseuranta"] == "F" or $sarjatuoterow["sarjanumeroseuranta"] == "G") {
              $query = "INSERT INTO sarjanumeroseuranta
                        SET yhtio      = '{$kukarow['yhtio']}',
                        tuoteno       = '{$rivirow['tuoteno']}',
                        sarjanumero   = '{$sarjarow['sarjanumero']}',
                        lisatieto     = '{$sarjarow['lisatieto']}',
                        kaytetty      = '{$sarjarow['kaytetty']}',
                        {$uusi_tunken} = '{$insid}',
                        takuu_alku    = '{$sarjarow['takuu_alku']}',
                        takuu_loppu   = '{$sarjarow['takuu_loppu']}',
                        parasta_ennen = '{$sarjarow['parasta_ennen']}',
                        era_kpl       = '{$sarjarow['era_kpl']}',
                        hyllyalue     = '{$rivirow['hyllyalue']}',
                        hyllynro      = '{$rivirow['hyllynro']}',
                        hyllytaso     = '{$rivirow['hyllytaso']}',
                        hyllyvali     = '{$rivirow['hyllyvali']}',
                        laatija       = '{$kukarow['kuka']}',
                        luontiaika    = now()";
              $sres = pupe_query($query);
            }
            else {
              //Tutkitaan lˆytyykˆ t‰llanen vapaa sarjanumero jo?
              $query = "SELECT tunnus
                        FROM sarjanumeroseuranta
                        WHERE yhtio     = '{$kukarow['yhtio']}'
                        AND tuoteno     = '{$rivirow['tuoteno']}'
                        AND sarjanumero = '{$sarjarow['sarjanumero']}'
                        AND {$uusi_tunken}  = 0
                        LIMIT 1";
              $sarjares1 = pupe_query($query);

              if (mysql_num_rows($sarjares1) == 1) {
                $sarjarow1 = mysql_fetch_assoc($sarjares1);

                $query = "UPDATE sarjanumeroseuranta
                          SET {$uusi_tunken} = '{$insid}',
                          hyllyalue    = '{$rivirow['hyllyalue']}',
                          hyllynro     = '{$rivirow['hyllynro']}',
                          hyllytaso    = '{$rivirow['hyllytaso']}',
                          hyllyvali    = '{$rivirow['hyllyvali']}'
                          WHERE tunnus = '{$sarjarow1['tunnus']}'
                          AND yhtio    = '{$kukarow['yhtio']}'";
                $sres = pupe_query($query);
              }
              else {
                $query = "INSERT INTO sarjanumeroseuranta
                          SET yhtio      = '{$kukarow['yhtio']}',
                          tuoteno       = '{$rivirow['tuoteno']}',
                          sarjanumero   = '{$sarjarow['sarjanumero']}',
                          lisatieto     = '{$sarjarow['lisatieto']}',
                          kaytetty      = '{$sarjarow['kaytetty']}',
                          {$uusi_tunken} = '{$insid}',
                          takuu_alku    = '{$sarjarow['takuu_alku']}',
                          takuu_loppu   = '{$sarjarow['takuu_loppu']}',
                          parasta_ennen = '{$sarjarow['parasta_ennen']}',
                          era_kpl       = '{$sarjarow['era_kpl']}',
                          hyllyalue     = '{$rivirow['hyllyalue']}',
                          hyllynro      = '{$rivirow['hyllynro']}',
                          hyllytaso     = '{$rivirow['hyllytaso']}',
                          hyllyvali     = '{$rivirow['hyllyvali']}',
                          laatija       = '{$kukarow['kuka']}',
                          luontiaika    = now()";
                $sres = pupe_query($query);
              }
            }
          }
        }

        //tehd‰‰n alvikorjaus jos k‰ytt‰j‰ on pyyt‰nyt sit‰
        if ($alvik == "on" and $rivirow["hinta"] != 0) {

          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '{$monistarow['yhtio']}'
                    AND tuoteno = '{$rivirow['tuoteno']}'";
          $tres = pupe_query($query);
          $trow = mysql_fetch_assoc($tres);

          // Ohitetaan valuuttaproblematiikka
          $laskurow["vienti_kurssi"] = 1;

          $vanhahinta = $rivirow["hinta"];

          if ($yhtiorow["alv_kasittely"] == "") {

            if ($alv_velvollisuus != "") {
              $korj_alv = $uusi_alv;
            }
            else {
              $korj_alv = $trow["alv"];
            }

            $uusihinta = $rivirow['hinta'] / (1+$rivirow['orig_alv']/100) * (1+$korj_alv/100);

            if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"]))) {
              $uusihinta = round($uusihinta, 6);
            }
            else {
              $uusihinta = round($uusihinta, $yhtiorow['hintapyoristys']);
            }
          }
          else {
            $uusihinta = $rivirow['hinta'];
          }

          list($lis_hinta, $lis_netto, $lis_ale_kaikki, $alehinta_alv, $alehinta_val) = alehinta($laskurow, $trow, 1, '', $uusihinta, '');
          list($lis_hinta, $alehinta_alv) = alv($laskurow, $trow, $lis_hinta, '', $alehinta_alv);

          if ($vanhahinta != $lis_hinta) {
            echo t("Korjataan hinta").": $trow[tuoteno], {$vanhahinta} --> {$lis_hinta},  $rivirow[alv] --> $alehinta_alv<br>";

            $query = "UPDATE tilausrivi
                      SET hinta = '{$lis_hinta}',
                      alv         = '{$alehinta_alv}'
                      where yhtio = '{$kukarow['yhtio']}'
                      and otunnus = '{$utunnus}'
                      and tunnus  = '{$insid}'";
            $tres = pupe_query($query);
          }
        }
      }

      //Korjataan perheid:t uusilla riveill‰
      $query = "SELECT perheid, min(tunnus) uusiperheid
                FROM tilausrivi
                WHERE yhtio  = '{$kukarow['yhtio']}'
                AND otunnus  = '{$utunnus}'
                AND perheid != 0
                GROUP BY perheid";
      $copresult = pupe_query($query);

      while ($coprivirow = mysql_fetch_assoc($copresult)) {
        $query = "UPDATE tilausrivi
                  SET perheid = '{$coprivirow['uusiperheid']}'
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND otunnus = '{$utunnus}'
                  AND perheid = '{$coprivirow['perheid']}'";
        $cores = pupe_query($query);
      }

      //Korjataan perheid2:t uusilla riveill‰
      $query = "SELECT perheid2, min(tunnus) uusiperheid2
                FROM tilausrivi
                WHERE yhtio   = '{$kukarow['yhtio']}'
                AND otunnus   = '{$utunnus}'
                AND perheid2 != 0
                GROUP BY perheid2";
      $copresult = pupe_query($query);

      while ($coprivirow = mysql_fetch_assoc($copresult)) {
        $query = "UPDATE tilausrivi
                  SET perheid2 = '{$coprivirow['uusiperheid2']}'
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND otunnus  = '{$utunnus}'
                  AND perheid2 = '{$coprivirow['perheid2']}'";
        $cores = pupe_query($query);
      }

      // Korjataanko rahdit?
      if ($toim == '' and $kumpi == 'MONISTA' and $korjrahdit == 'on' and $monistarow['laskunro'] > 0 and $yhtiorow['rahti_hinnoittelu'] == '') {

        // Poistetaan virheelliset rahdit
        $rahtituotelisa = "'$yhtiorow[rahti_tuotenumero]'";
        $rahtituotelisa = lisaa_vaihtoehtoinen_rahti_merkkijonoon($rahtituotelisa);

        $query  = " UPDATE tilausrivi set tyyppi='D' where yhtio = '$kukarow[yhtio]' and otunnus='$utunnus' AND tuoteno IN ({$rahtituotelisa})";
        $addtil = pupe_query($query);

        $query   = "SELECT date_format(rahtikirjat.tulostettu, '%Y-%m-%d') tulostettu, group_concat(distinct lasku.tunnus) tunnukset
                    FROM lasku, rahtikirjat, maksuehto
                    WHERE lasku.yhtio     = '$kukarow[yhtio]'
                    and lasku.rahtivapaa  = ''
                    and lasku.kohdistettu = 'K'
                    and lasku.yhtio       = rahtikirjat.yhtio
                    and lasku.tunnus      = rahtikirjat.otsikkonro
                    and lasku.yhtio       = maksuehto.yhtio
                    and lasku.maksuehto   = maksuehto.tunnus
                    AND lasku.tila        = 'L'
                    AND lasku.alatila     = 'X'
                    AND lasku.laskunro    = '{$monistarow['laskunro']}'
                    GROUP BY date_format(rahtikirjat.tulostettu, '%Y-%m-%d'), lasku.ytunnus, lasku.toimitustapa, maksuehto.jv";
        $raresult  = pupe_query($query);

        while ($rahtirow = mysql_fetch_assoc($raresult)) {
          //haetaan ekan otsikon tiedot
          $query = "SELECT lasku.*, maksuehto.jv
                    FROM lasku, maksuehto
                    WHERE lasku.yhtio ='$kukarow[yhtio]'
                    AND lasku.tunnus    in ($rahtirow[tunnukset])
                    AND lasku.yhtio     = maksuehto.yhtio
                    AND lasku.maksuehto = maksuehto.tunnus
                    ORDER BY lasku.tunnus
                    LIMIT 1";
          $otsre = pupe_query($query);
          $laskurow = mysql_fetch_assoc($otsre);

          // haetaan rahdin hinta
          list($rah_hinta, $rah_ale, $rah_alv, $rah_netto) = hae_rahtimaksu($rahtirow['tunnukset']);

          $query = "SELECT *
                    FROM tuote
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tuoteno = '$yhtiorow[rahti_tuotenumero]'";
          $rhire = pupe_query($query);

          if ($rah_hinta > 0 and $virhe == 0 and mysql_num_rows($rhire) == 1) {

            $trow      = mysql_fetch_assoc($rhire);
            $otunnus   = $laskurow['tunnus'];
            $nimitys   = tv1dateconv($rahtirow['tulostettu'])." $laskurow[toimitustapa]";

            $ale_lisa_insert_query_1 = $ale_lisa_insert_query_2 = '';

            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
              if (isset($rah_ale["ale{$alepostfix}"]) and $rah_ale["ale{$alepostfix}"] > 0) {
                $ale_lisa_insert_query_1 .= " ale{$alepostfix},";
                $ale_lisa_insert_query_2 .= " '".$rah_ale["ale{$alepostfix}"]."',";
              }
            }

            $query  = "INSERT INTO tilausrivi (laatija, laadittu, hinta, {$ale_lisa_insert_query_1} netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti)
                       values ('automaatti', now(), '$rah_hinta', {$ale_lisa_insert_query_2} '$rah_netto', '1', '1', '$utunnus', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', 'L', '$rah_alv', '')";
            $addtil = pupe_query($query);
          }
        }
      }

      if ($slask == "on") {
        $query = "SELECT *
                  FROM lasku
                  WHERE yhtio = '{$kukarow['yhtio']}'
                  AND tunnus  = '{$utunnus}'";
        $result = pupe_query($query);
        $laskurow = mysql_fetch_assoc($result);

        $kukarow["kesken"] = $laskurow["tunnus"];

        require "tilauskasittely/tilaus-valmis.inc";
      }
    }
  }

  $tee = ''; //menn‰‰n alkuun
}

if ($tee == '' and $vain_monista == "") {
  //syˆtet‰‰n tilausnumero
  echo "<br><table>";
  echo "<form method = 'post'>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<tr>";

  if ($toim == 'OSTOTILAUS') {
    echo "<th>".t("Toimittajan nimi")."</th>";
  }
  else {
    echo "<th>".t("Asiakkaan nimi")."</th>";
  }

  echo "<td><input type='text' size='10' name='ytunnus'></td></tr>";

  echo "<tr><th>".t("Tilausnumero")."</th><td><input type='text' size='10' name='otunnus'></td></tr>";

  if ($toim == '') {
    echo "<tr><th>".t("Laskunumero")."</th><td><input type='text' size='10' name='laskunro'></td></tr>";
  }

  echo "</table>";

  echo "<br><input type='submit' value='".t("Jatka")."'>";
  echo "</form>";

  if ($toim == '') {
    echo "<br>";
    echo "<form method = 'post'>";
    echo "<input type='hidden' name='toim' value='{$toim}'>";
    echo "<input type='hidden' name='tee' value='mikrotila'>";
    echo "<br><input type='submit' value='".t("Lue monistettavat laskut tiedostosta")."'>";
    echo "</form>";
  }

  if (isset($mistatultiin) and $mistatultiin == "maksutapahtumaselaus") {
    $osoite = "{$palvelin2}tilauskasittely/tilaus_myynti.php?" .
      "toim=PIKATILAUS&tilausnumero={$utunnus}&lopetus={$lopetus}";

    echo "<script>";
    echo "$(function() {
            window.location.replace('{$osoite}');
          })";
    echo "</script>";
  }

  require 'inc/footer.inc';
}
