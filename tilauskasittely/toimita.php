<?php

require "../inc/parametrit.inc";

require_once 'rajapinnat/woo/woo-functions.php';

$logistiikka_yhtio = '';
$logistiikka_yhtiolisa = '';

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

echo "<font class='head'>".t("Toimita tilaus").":</font><hr>";

$query_ale_lisa = generoi_alekentta('M');

if ($tee == 'P' and $maksutapa == 'seka') {
  $query_maksuehto = "SELECT *
                      FROM maksuehto
                      WHERE yhtio='$kukarow[yhtio]'
                      and kateinen != ''
                      and kaytossa  = ''
                      and (sallitut_maat = '' or sallitut_maat like '%$maa%')";
  $maksuehtores = pupe_query($query_maksuehto);
  $maksuehtorow = mysql_fetch_assoc($maksuehtores);

  echo "<table><form name='laskuri' method='post'>";

  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='tee' value='P'>";
  echo "<input type='hidden' name='kassalipas' value='$kassalipas'>";
  echo "<input type='hidden' name='vaihdakateista' value='$vaihdakateista'>";
  echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";

  echo "  <script type='text/javascript' language='JavaScript'>
      <!--
        function update_summa(rivihinta) {

          kateinen = Number(document.getElementById('kateismaksu').value.replace(\",\",\".\"));
          pankki = Number(document.getElementById('pankkikortti').value.replace(\",\",\".\"));
          luotto = Number(document.getElementById('luottokortti').value.replace(\",\",\".\"));

          summa = rivihinta - (kateinen + pankki + luotto);

          summa = Math.round(summa*100)/100;

          if (summa == 0 && (document.getElementById('kateismaksu').value != '' || document.getElementById('pankkikortti').value != '' || document.getElementById('luottokortti').value != '')) {
            summa = 0.00;
            document.getElementById('hyvaksy_nappi').disabled = false;
          } else {
            document.getElementById('hyvaksy_nappi').disabled = true;
          }

          document.getElementById('loppusumma').innerHTML = '<b>' + summa.toFixed(2) + '</b>';
        }
      -->
      </script>";

  echo "<tr><th>".t("Laskun loppusumma")."</th><td align='right'>$rivihinta</td><td>$valkoodi</td></tr>";

  echo "<tr><td>".t("Käteisellä")."</td><td><input type='text' name='kateismaksu[kateinen]' id='kateismaksu' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
  echo "<tr><td>".t("Pankkikortilla")."</td><td><input type='text' name='kateismaksu[pankkikortti]' id='pankkikortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";
  echo "<tr><td>".t("Luottokortilla")."</td><td><input type='text' name='kateismaksu[luottokortti]' id='luottokortti' value='' size='7' autocomplete='off' onkeyup='update_summa(\"$rivihinta\");'></td><td>$valkoodi</td></tr>";

  echo "<tr><th>".t("Erotus")."</th><td name='loppusumma' id='loppusumma' align='right'><strong>0.00</strong></td><td>$valkoodi</td></tr>";
  echo "<tr><td class='back'><input type='submit' name='hyvaksy_nappi' id='hyvaksy_nappi' value='".t("Hyväksy")."' disabled></td></tr>";

  echo "</form><br><br>";

  $formi = "laskuri";
  $kentta = "kateismaksu";

  exit;
}

if ($tee == 'maksu') {
  if ($seka == '') {
    $tee == 'P';
  }
}

if ($tee=='P') {

  // jos kyseessä ei ole nouto tai noutajan nimi on annettu, voidaan merkata tilaus toimitetuksi..
  if (($nouto != 'yes') or ($noutaja != '')) {
    $query = "UPDATE tilausrivi
              SET toimitettu = '$kukarow[kuka]', toimitettuaika = now()
              WHERE otunnus   = '$otunnus'
              and var         not in ('P','J','O','S')
              and yhtio       = '$kukarow[yhtio]'
              and keratty    != ''
              and toimitettu  = ''
              and tyyppi      = 'L'";
    $result = pupe_query($query);

    if (isset($vaihdakateista) and $vaihdakateista == "KYLLA") {
      $katlisa = ", kassalipas = '$kassalipas', maksuehto = '$maksutapa'";
    }
    else {
      $katlisa = "";
    }

    $query = "UPDATE lasku
              set alatila = 'D',
              noutaja = '$noutaja'
              $katlisa
              WHERE tunnus='$otunnus' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    // Jos laskulla on maksupositioita, menee ne alatilaan J
    // eli odottamaan loppulaskutusta
    $query = "UPDATE lasku
              SET alatila = 'J'
              WHERE tunnus    = '$otunnus'
              AND jaksotettu != 0
              AND yhtio       = '$kukarow[yhtio]'";
    $ures  = pupe_query($query);

    // jos kyseessä on käteismyyntiä, tulostetaaan käteislasku
    $query  = "SELECT *
               from lasku, maksuehto
               where lasku.tunnus   = '$otunnus'
               and lasku.yhtio      = '$kukarow[yhtio]'
               and maksuehto.yhtio  = lasku.yhtio
               and maksuehto.tunnus = lasku.maksuehto";
    $result = pupe_query($query);
    $tilrow = mysql_fetch_assoc($result);

    // Etukäteen maksetut tilaukset pitää muuttaa takaisin "maksettu"-tilaan
    $query = "UPDATE lasku SET
              alatila      = 'X'
              WHERE yhtio  = '$kukarow[yhtio]'
              AND tunnus   = '$otunnus'
              AND mapvm   != '0000-00-00'
              AND chn      = '999'";
    $ures  = pupe_query($query);

    // Etukäteen maksettu Magentotilaus laskutetaan, jos ei ole jo laskuttunut
    if ($tilrow['ohjelma_moduli'] == 'MAGENTOJT') {
      laskuta_magentojt($otunnus);
    }
    elseif ($tilrow['kateinen']!='' and $tilrow["vienti"]=='') {

      // jos kyseessä on käteiskauppaa ja EI vientiä, laskutetaan ja tulostetaan tilaus..

      //tulostetaan käteislasku...
      $laskutettavat  = $otunnus;
      $tee       = "TARKISTA";
      $laskutakaikki   = "KYLLA";
      $silent       = "KYLLA";
      $tulosta_lasku_kpl = $laskukpl;

      if ($kukarow["kirjoitin"] != 0 and $valittu_tulostin == "") {
        $valittu_tulostin = $kukarow["kirjoitin"];
      }
      elseif ($valittu_tulostin == "") {
        $valittu_tulostin = "AUTOMAAGINEN_VALINTA";
      }

      require "verkkolasku.php";
    }

    // Merkaatan woo-commerce tilaukset toimitetuiksi kauppaan
    $woo_params = array(
      "pupesoft_tunnukset" => explode(",", $otunnus),
      "tracking_code" => "NOUDETTU / PICKED UP",
    );

    woo_commerce_toimita_tilaus($woo_params);

    //Tulostetaan uusi lähete jos käyttäjä valitsi drop-downista printterin
    //Paitsi jos tilauksen tila päivitettiin sellaiseksi, että lähetettä ei kuulu tulostaa
    $query = "SELECT *
              FROM lasku
              WHERE tunnus in ($otunnus)
              and yhtio    = '$kukarow[yhtio]'";
    $lasresult = pupe_query($query);

    while ($laskurow = mysql_fetch_assoc($lasresult)) {

      //tulostetaan faili ja valitaan sopivat printterit
      if ($laskurow["varasto"] == '') {
        $query = "SELECT *
                  from varastopaikat
                  where yhtio = '$kukarow[yhtio]' AND tyyppi != 'P'
                  order by alkuhyllyalue,alkuhyllynro
                  limit 1";
      }
      else {
        $query = "SELECT *
                  from varastopaikat
                  where yhtio = '$kukarow[yhtio]'
                  and tunnus  = '$laskurow[varasto]'
                  order by alkuhyllyalue,alkuhyllynro";
      }
      $prires = pupe_query($query);

      if (mysql_num_rows($prires) > 0) {

        $prirow = mysql_fetch_assoc($prires);

        // käteinen muuttuja viritetään tilaus-valmis.inc:issä jos maksuehto on käteinen
        // ja silloin pitää kaikki lähetteet tulostaa aina printteri5:lle (lasku printteri)
        if ($kateinen == 'X') {
          $apuprintteri = $prirow['printteri5']; // laskuprintteri
        }
        else {
          if ($valittu_tulostin == "oletukselle") {
            $apuprintteri = $prirow['printteri1']; // läheteprintteri
          }
          else {
            $apuprintteri = $valittu_tulostin;
          }
        }

        //haetaan lähetteen tulostuskomento
        $query   = "SELECT * FROM kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$apuprintteri'";
        $kirres  = pupe_query($query);
        $kirrow  = mysql_fetch_assoc($kirres);
        $komento = $kirrow['komento'];

        if ($valittu_oslapp_tulostin == "oletukselle") {
          $apuprintteri = $prirow['printteri3']; // osoitelappuprintteri
        }
        else {
          $apuprintteri = $valittu_oslapp_tulostin;
        }

        //haetaan osoitelapun tulostuskomento
        $query  = "SELECT * FROM kirjoittimet where yhtio = '$kukarow[yhtio]' and tunnus = '$apuprintteri'";
        $kirres = pupe_query($query);
        $kirrow = mysql_fetch_assoc($kirres);
        $oslapp = $kirrow['komento'];
      }

      if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {
        $params = array(
          'laskurow'          => $laskurow,
          'sellahetetyyppi'       => "",
          'extranet_tilausvahvistus'   => "",
          'naytetaanko_rivihinta'    => "",
          'tee'            => $tee,
          'toim'            => $toim,
          'komento'           => $komento,
          'lahetekpl'          => $lahetekpl,
          'kieli'           => ""
        );

        pupesoft_tulosta_lahete($params);
      }
    }

    echo t("Tilaus toimitettu")."!<br><br>";
    $id = 0;
  }
  else {
    $id = $otunnus;
    $virhe = "<font class='error'>".t("Noutajan nimi on syötettävä")."!</font><br><br>";
  }
}

if ($id == '') $id = 0;

// meillä ei ole valittua tilausta
if ($id == '0') {
  $formi  = "find";
  $kentta  = "etsi";
  $boob   = "";

  // tehdään etsi valinta
  echo "<form name='find' method='post'>".t("Etsi tilausta").": <input type='text' name='etsi'><input type='submit' class='hae_btn' value='".t("Etsi")."'></form><br><br>";

  $haku = '';
  if (is_string($etsi))  $haku="and lasku.nimi LIKE '%$etsi%'";
  if (is_numeric($etsi)) $haku="and lasku.tunnus='$etsi'";

  $query = "SELECT distinct otunnus
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != '')
            JOIN toimitustapa ON (toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa and toimitustapa.nouto != '')
            where lasku.$logistiikka_yhtiolisa
            and lasku.tila    = 'L'
            and lasku.alatila in ('C','B')
            and lasku.vienti  = ''
            ORDER BY lasku.toimaika";
  $tilre = pupe_query($query);

  while ($tilrow = mysql_fetch_assoc($tilre)) {
    // etsitään sopivia tilauksia
    $query = "SELECT lasku.yhtio, lasku.yhtio_nimi, lasku.tunnus 'tilaus',
              concat_ws(' ', lasku.nimi, lasku.nimitark) asiakas, maksuehto.teksti maksuehto, lasku.toimitustapa,
              date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, kuka.nimi laatija, lasku.toimaika
              FROM lasku
              LEFT JOIN maksuehto ON (maksuehto.yhtio = lasku.yhtio AND maksuehto.tunnus = lasku.maksuehto)
              LEFT JOIN kuka on (kuka.yhtio = lasku.yhtio and kuka.kuka = lasku.laatija)
              WHERE lasku.tunnus = '$tilrow[otunnus]'
              and lasku.tila     = 'L'
              $haku
              and lasku.$logistiikka_yhtiolisa
              and lasku.alatila  in ('C','B')
              ORDER by laadittu desc";
    $result = pupe_query($query);

    while ($row = mysql_fetch_assoc($result)) {
      // piirretään vaan kerran taulukko-otsikot
      if ($boob == '') {
        $boob = 'kala';

        echo "<table>";
        echo "<tr>";
        for ($i=0; $i<mysql_num_fields($result); $i++) {
          $fname = mysql_field_name($result, $i);

          if ($fname == 'yhtio_nimi') {
            if ($logistiikka_yhtio != '') {
              echo "<th align='left'>", t("Yhtiö"), "</th>";
            }
          }
          elseif ($fname == 'yhtio') {
            // skipataan tää
          }
          else {
            echo "<th align='left'>".t($fname)."</th>";
          }
        }

        echo "<th align='left'>".t("Muokkaa")."</th>";
        echo "</tr>";
      }

      echo "<tr class='aktiivi'>";

      for ($i=0; $i<mysql_num_fields($result); $i++) {
        $fname = mysql_field_name($result, $i);

        if ($fname == 'laadittu' or $fname == 'toimaika') {
          echo "<td>".tv1dateconv($row[$fname])."</td>";
        }
        elseif ($fname == 'yhtio_nimi') {
          if ($logistiikka_yhtio != '') {
            echo "<td>$row[yhtio_nimi]</td>";
          }
        }
        elseif ($fname == 'yhtio') {
          // skipataan tää
        }
        else {
          echo "<td>$row[$fname]</td>";
        }
      }

      echo "<td><a href='tilaus_myynti.php?toim=PIKATILAUS&tilausnumero={$row['tilaus']}&kaytiin_otsikolla=NOJOO!&lopetus={$palvelin2}tilauskasittely/toimita.php////id=0//etsi={$etsi}'>".t("Muokkaa")."</a></td>";

      echo "<td class='back'><form method='post'>
          <input type='hidden' name='id' value='$row[tilaus]'>
          <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
          <input type='submit' name='tila' value='".t("Toimita")."'></form></td>";

      echo "</tr>";
    }
  }

  if ($boob != '') {
    echo "</table>";
  }
  else {
    echo "<font class='message'>".t("Yhtään toimitettavaa tilausta ei löytynyt")."...</font>";
  }
}

if ($id > 0) {
  $query = "SELECT lasku.*,
            concat_ws(' ',lasku.nimi, nimitark) nimi,
            lasku.osoite,
            concat_ws(' ', lasku.postino, lasku.postitp) postitp,
            toim_osoite,
            concat_ws(' ', toim_postino, toim_postitp) toim_postitp,
            lasku.tunnus laskutunnus,
            lasku.liitostunnus,
            maksuehto.tunnus,
            maksuehto.teksti,
            maksuehto.kateinen
            FROM lasku
            JOIN maksuehto ON maksuehto.yhtio=lasku.yhtio and maksuehto.tunnus=lasku.maksuehto
            WHERE lasku.tunnus = '$id'
            and lasku.yhtio    = '$kukarow[yhtio]'
            and lasku.tila     = 'L'
            and lasku.alatila  in ('C','B')";
  $result = pupe_query($query);

  if (mysql_num_rows($result)==0) {
    die(t("Tilausta")." $id ".t("ei voida toimittaa, koska kaikkia tilauksen tietoja ei löydy!")."!");
  }

  $row = mysql_fetch_assoc($result);

  echo "<table>";
  echo "<tr><th>" . t("Tilaus") ."</th><td>$row[laskutunnus]</td></tr>";
  echo "<tr><th>" . t("Asiakas") ."</th><td>$row[nimi]<br>$row[toim_nimi]</td></tr>";
  echo "<tr><th>" . t("Ostajan osoite") ."</th><td>$row[osoite], $row[postitp]</td></tr>";
  echo "<tr><th>" . t("Toimitusosoite") ."</th><td>$row[toim_osoite], $row[toim_postitp]</td></tr>";
  echo "<tr><th>" . t("Maksuehto") ."</th><td>".t_tunnus_avainsanat($row, "teksti", "MAKSUEHTOKV")."</td></tr>";
  echo "<tr><th>" . t("Toimitustapa") ."</th><td>$row[toimitustapa]</td></tr>";
  echo "</table><br><br>";

  if ($row["valkoodi"] != '' and trim(strtoupper($row["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $row["vienti_kurssi"] != 0) {
    $hinta_riv = "(tilausrivi.hinta/$row[vienti_kurssi])";
  }
  else {
    $hinta_riv = "tilausrivi.hinta";
  }

  $query = "SELECT concat_ws(' ', tilausrivi.hyllyalue, tilausrivi.hyllynro, tilausrivi.hyllytaso, tilausrivi.hyllyvali) varastopaikka,
            concat_ws(' ', tilausrivi.tuoteno, tilausrivi.nimitys) tuoteno, tilausrivi.varattu,
            concat_ws('@', tilausrivi.keratty, tilausrivi.kerattyaika) keratty, tilausrivi.tunnus,
            tilausrivi.var,
            if (tilausrivi.alv<500, {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '', (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} * (tilausrivi.alv/100), 0) alv,
            {$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa} rivihinta,
            (tilausrivi.varattu+tilausrivi.kpl) kpl
            FROM tilausrivi
            JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
            WHERE tilausrivi.yhtio ='$kukarow[yhtio]'
            and tilausrivi.var     not in ('P','J','O','S')
            and tilausrivi.tyyppi  = 'L'
            and tilausrivi.otunnus = '$id'
            ORDER BY varastopaikka";
  $result = pupe_query($query);
  $riveja = mysql_num_rows($result);

  echo "  <table>
      <tr>
      <th>".t("Varastopaikka")."</th>
      <th>".t("Tuoteno")."</th>
      <th>".t("Määrä")."</th>
      <th>".t("Kerätty")."</th>
      </tr>";

  $summa = 0;
  $arvo  = 0;

  while ($rivi = mysql_fetch_assoc($result)) {

    $summa += hintapyoristys($rivi["rivihinta"]+$rivi["alv"]);
    $arvo  += hintapyoristys($rivi["rivihinta"]);

    echo "<tr><td>$rivi[varastopaikka]</td>
        <td>$rivi[tuoteno]</td>
        <td>$rivi[varattu]</td>
        <td>$rivi[keratty]</td>
        </tr>";
  }

  // EE keississä lasketaan veron määrää saman kaavan mukaan ku laskun tulostuksessa alvierittelyssä
  // ja sit lopuksi summataan $arvo+$alvinmaara jotta saadaan laskun verollinen loppusumma
  if (strtoupper($yhtiorow['maa']) == 'EE') {

    $alvinmaara = 0;

    //Haetaan kaikki alvikannat riveiltä
    $alvquery = "SELECT DISTINCT alv
                 FROM tilausrivi
                 WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                 and tilausrivi.var     not in ('P','J','O','S')
                 and tilausrivi.tyyppi  = 'L'
                 and tilausrivi.otunnus = '$id'
                 and tilausrivi.alv     < 500";
    $alvresult = pupe_query($alvquery);

    while ($alvrow = mysql_fetch_assoc($alvresult)) {

      $aquery = "SELECT
                 round(sum(round({$hinta_riv} / if ('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa},2) * (tilausrivi.alv / 100)),2) alvrivihinta
                 FROM tilausrivi
                 JOIN lasku ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
                 WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
                 and tilausrivi.var     not in ('P','J','O','S')
                 and tilausrivi.tyyppi  = 'L'
                 and tilausrivi.otunnus = '$id'
                 and tilausrivi.alv     = '$alvrow[alv]'";
      $aresult = pupe_query($aquery);
      $arow = mysql_fetch_assoc($aresult);

      $alvinmaara += $arow["alvrivihinta"];
    }

    $summa = $arvo+$alvinmaara;
  }

  echo "</table><br>";

  // Etsitään asiakas
  $query = "SELECT laskunsummapyoristys
            FROM asiakas
            WHERE tunnus = '$row[liitostunnus]'
            and yhtio    = '$kukarow[yhtio]'";
  $asres = pupe_query($query);
  $asrow = mysql_fetch_assoc($asres);

  //Käsin syötetty summa johon lasku pyöristetään
  if ($row["hinta"] <> 0 and abs($row["hinta"]-$summa) <= 0.5 and abs($summa) >= 0.5) {
    $summa = sprintf("%.2f", $row["hinta"]);
  }

  // Jos laskun loppusumma pyöristetään lähimpään tasalukuun
  if ($yhtiorow["laskunsummapyoristys"] == 'o' or $asrow["laskunsummapyoristys"] == 'o') {
    $summa = sprintf("%.2f", round($summa, 0));
  }

  $query = "SELECT * FROM toimitustapa WHERE yhtio='$kukarow[yhtio]' AND selite='$row[toimitustapa]'";
  $tores = pupe_query($query);
  $toita = mysql_fetch_assoc($tores);

  echo "<form name = 'rivit' method='post'>
      <input type='hidden' name='otunnus' value='$id'>
      <input type='hidden' name='lasku_yhtio' value='$row[yhtio]'>
      <input type='hidden' name='tee' value='P'>";

  echo "<table>";

  if ($toita['nouto'] != '' and $row['kateinen'] != '' and $row["chn"] != '999' and ($row["mapvm"] == "" or $row["mapvm"] == '0000-00-00')) {

    echo "<tr><th>".t("Verollinen Yhteensä")."</th><td>$summa $row[valkoodi]</td></tr>";

    echo "<tr><th>".t("Valitse kassalipas")."</th><td>";

    $query = "SELECT * FROM kassalipas WHERE yhtio='$kukarow[yhtio]'";
    $kassares = pupe_query($query);

    $sel = "";

    echo "<input type='hidden' name='noutaja' value=''>";
    echo "<input type='hidden' name='rivihinta' value='$summa'>";
    echo "<input type='hidden' name='valkoodi' value='$row[valkoodi]'>";
    echo "<input type='hidden' name='maa' value='$row[maa]'>";
    echo "<input type='hidden' name='vaihdakateista' value='KYLLA'>";
    echo "<select name='kassalipas'>";
    echo "<option value=''>".t("Ei kassalipasta")."</option>";

    while ($kassarow = mysql_fetch_assoc($kassares)) {
      if ($kukarow["kassamyyja"] == $kassarow["tunnus"]) {
        $sel = "selected";
      }
      elseif ($kassalipas == $kassarow["tunnus"]) {
        $sel = "selected";
      }

      echo "<option value='$kassarow[tunnus]' $sel>$kassarow[nimi]</option>";

      $sel = "";
    }
    echo "</select></td></tr>";

    $query_maksuehto = "SELECT *
                        FROM maksuehto
                        WHERE yhtio   = '$kukarow[yhtio]'
                        and kateinen != ''
                        and kaytossa  = ''
                        and (maksuehto.sallitut_maat = '' or maksuehto.sallitut_maat like '%$row[maa]%')
                        ORDER BY tunnus";
    $maksuehtores = pupe_query($query_maksuehto);

    if (mysql_num_rows($maksuehtores) > 1) {
      echo "<tr><th>".t("Maksutapa")."</th><td>";

      echo "<select name='maksutapa'>";

      while ($maksuehtorow = mysql_fetch_assoc($maksuehtores)) {
        $sel = "";
        if ($maksuehtorow["tunnus"] == $row["maksuehto"]) {
          $sel = "selected";
        }

        echo "<option value='$maksuehtorow[tunnus]' $sel>".t_tunnus_avainsanat($maksuehtorow, "teksti", "MAKSUEHTOKV")."</option>";
      }

      echo "<option value='seka'>".t("Seka")."</option>";
      echo "</select></td></tr>";

    }
    else {
      $maksuehtorow = mysql_fetch_assoc($maksuehtores);
      echo "<input type='hidden' name='maksutapa' value='$maksuehtorow[tunnus]'>";
    }
  }

  if ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00') {
    echo "<tr><th>".t("Maksutapa")."</th><td><font class='error'>".t("Tilaus on maksettu jo etukäteen luottokortilla").".</font></td></tr>";
  }

  if (($toita['nouto'] !='' and $row['kateinen'] == '' ) or ($row["chn"] == '999' and $row["mapvm"] != "" and $row["mapvm"] != '0000-00-00')) {

    // jos kyseessä on nouto jota *EI* makseta käteisellä, kysytään noutajan nimeä..
    echo "<tr><th>".t("Syötä noutajan nimi")."</th>";
    echo "<td><input size='60' type='text' name='noutaja'></td></tr>";
    echo "<input type='hidden' name='nouto' value='yes'>";
    echo "<input type='hidden' name='kassalipas' value=''>";

    //kursorinohjausta
    $formi  = "rivit";
    $kentta  = "noutaja";
  }

  echo "<tr><th>".t("Lähete")."</th><td>";

  $query = "SELECT *
            FROM kirjoittimet
            WHERE
            yhtio = '$kukarow[yhtio]'
            ORDER by kirjoitin";
  $kirre = pupe_query($query);

  echo "<select name='valittu_tulostin'>";

  echo "<option value=''>".t("Ei tulosteta")."</option>";
  echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

  $_apuprintteri = "";

  // Katsotaan onko avainsanoihin määritelty varaston toimipaikan läheteprintteriä
  if (!empty($row['yhtio_toimipaikka'])) {
    $avainsana_where = " and avainsana.selite       = '{$row['varasto']}'
                         and avainsana.selitetark   = '{$row['yhtio_toimipaikka']}'
                         and avainsana.selitetark_2 = 'printteri1'";

    $tp_tulostin = t_avainsana("VARTOIMTULOSTIN", '', $avainsana_where, '', '', "selitetark_3");

    if (!empty($tp_tulostin)) {
      $_apuprintteri = $tp_tulostin;
    }
  }

  while ($kirrow = mysql_fetch_assoc($kirre)) {
    $sel = (!empty($_apuprintteri) and $kirrow['tunnus'] == $_apuprintteri) ? "selected" : "";
    echo "<option value='$kirrow[tunnus]' {$sel}>$kirrow[kirjoitin]</option>";
  }

  echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></td>";
  echo "</tr>";

  if ($row['kateinen'] != '' and $row["vienti"] == '') {
    echo "<tr>";
    echo "<th>".t("Lasku")."</th>";
    echo "<td>";
    echo t("Kpl").": <input type='text' size='4' name='laskukpl' value='{$yhtiorow['oletus_laskukpl_toimitatilaus']}' />";
    echo "</td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "<br><br>";

  echo "$virhe";
  echo "<input type='submit' value='".t("Merkkaa toimitetuksi")."'></form>";
}

require "inc/footer.inc";
