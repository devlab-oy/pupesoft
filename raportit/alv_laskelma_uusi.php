<?php

if (strpos($_SERVER['SCRIPT_NAME'], "viranomaisilmoitukset.php") === FALSE) {
  require "../inc/parametrit.inc";
}

/*
300 "Vero kotimaan myynnistä verokannoittain"
301 "24% vero"
302 "14% vero"
303 "10% vero"
305 "Vero tavaraostoista muista EU maista"
306 "Vero palveluostoista muista EU maista"
307 "Kohdekuukauden vähennettävä vero"
309 "0-verokannan alainen liikevaihto"
311 "Tavaran myynti muihin EU-maihin"
312 "Palveluiden myynti muihin EU-maihin"
313 "Tavaraostot muista EU-maista"
314 "Palveluostot muista EU-maista"
315 "Alarajahuojennukseen oikeuttava liikevaihto"
316 "Alarajahuojennukseen oikeuttava vero"
317 "Alarajahuojennuksen määrä"
318 "Vero rakentamispalveluiden ostoista"
319 "Rakentamispalvelun myynti"
320 "Rakentamispalvelun ostot"
*/

// suomen oletus ALV muuttui 1.7.2010
if (isset($vv) and isset($kk) and $vv == '2010' and $kk < 7) {
  $oletus_verokanta = 22;
}
// suomen oletus ALV muuttui 1.1.2013
elseif (isset($vv) and $vv < '2013') {
  $oletus_verokanta = 23;
}
else {
  $oletus_verokanta = 24;
}

// Sallittu erotus on luku kuinka paljon sallitaan ALV-ilmoitus erotus poikkeavan
if (!isset($alv_laskelman_sallittu_erotus)) {
  $alv_laskelman_sallittu_erotus = 1;
}

enable_ajax();

if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if (isset($tee) and $tee == 'kuittaa_alv_ilmoitus') {

  $query = "SELECT lasku.tunnus
            FROM lasku
            JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus)
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tapvm   = '{$loppukk}'
            AND lasku.tila    = 'X'
            AND lasku.nimi    = 'ALVTOSITEMAKSUUN$loppukk'";
  $tositelinkki_result = pupe_query($query);

  if (mysql_num_rows($tositelinkki_result) == 0) {

    $query = "SELECT *
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]'
              AND tilino  = '$maksettava_alv_tili'";
    $tilires = pupe_query($query);

    if (mysql_num_rows($tilires) != 1) {
      echo "<font class='error'>", t("VIRHE: Maksettava ALV-Tili virheellinen")."! ($maksettava_alv_tili)</font><br><br>";
    }

    $query = "SELECT *
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]'
              AND tilino  = '$erotus_tili'";
    $erotilires = pupe_query($query);

    if (mysql_num_rows($erotilires) != 1) {
      echo "<font class='error'>", t("VIRHE: Erotuksen Tili virheellinen")."! ($erotus_tili)</font><br><br>";
    }

    if (mysql_num_rows($tilires) == 1 and mysql_num_rows($erotilires) == 1) {

      $alvtili_yht = (float) $alvtili_yht;
      $alvmaks_yht = (float) $alvmaks_yht;
      $alvpyor_yht = (float) round($alvmaks_yht-$alvtili_yht, 2);

      $summa         = $alvtili_yht;
      $tili         = $yhtiorow['alv'];
      $kustp         = '';
      $selite       = t("ALV")." $kk $vv ".t("maksuun");
      $vero         = 0;
      $projekti       = '';
      $kohde         = '';
      $summa_valuutassa   = 0;
      $valkoodi       = $yhtiorow['valuutta'];

      list($tpv2, $tpk2, $tpp2) = explode("-", $tilikausi_loppu);

      $query = "INSERT into lasku set
                yhtio      = '{$kukarow['yhtio']}',
                tapvm      = '{$loppukk}',
                tila       = 'X',
                nimi       = 'ALVTOSITEMAKSUUN$loppukk',
                alv_tili   = '',
                comments   = '',
                laatija    = '{$kukarow['kuka']}',
                luontiaika = now()";
      $result = pupe_query($query);
      $tunnus = mysql_insert_id($GLOBALS["masterlink"]);

      require "inc/teetiliointi.inc";

      $summa         = $alvmaks_yht * -1;
      $tili         = $maksettava_alv_tili;
      $kustp         = '';
      $selite       = t("ALV")." $kk $vv ".t("maksuun");
      $vero         = 0;
      $projekti       = '';
      $kohde         = '';
      $summa_valuutassa   = 0;
      $valkoodi       = $yhtiorow['valuutta'];

      require "inc/teetiliointi.inc";

      if ($alvpyor_yht != 0) {

        $summa         = $alvpyor_yht;
        $tili         = $erotus_tili;
        $kustp         = '';
        $selite       = t("ALV")." $kk $vv ".t("maksuun");
        $vero         = 0;
        $projekti       = '';
        $kohde         = '';
        $summa_valuutassa   = 0;
        $valkoodi       = $yhtiorow['valuutta'];

        require "inc/teetiliointi.inc";
      }
    }

    $tili        = '';
    $kustp        = '';
    $kohde        = '';
    $projekti      = '';
    $summa        = '';
    $vero        = '';
    $selite       = '';
    $summa_valuutassa  = '';
    $valkoodi       = '';
  }
  else {
    echo "<font class='error'>", t("VIRHE: ALV-laskelma on jo täsmätty"), "!</font><br><br>";
  }
}

if (isset($tee) and $tee == 'VSRALVKK_UUSI_erittele') {

  $alvv       = $vv;
  $alvk       = $kk;
  $alvp       = 0;
  $tiliointilisa    = '';

  if (isset($etsivirheita) and (int) $etsivirheita > 0) {
    $alkupvm     = date("Y-m-d", mktime(0, 0, 0, $alvk, $etsivirheita, $alvv));
    $loppupvm    = date("Y-m-d", mktime(0, 0, 0, $alvk, $etsivirheita, $alvv));
    $virhelisa   = "&alvp=".sprintf("%02d", $etsivirheita);
  }
  else {
    $alkupvm     = date("Y-m-d", mktime(0, 0, 0, $alvk,   1, $alvv));
    $loppupvm    = date("Y-m-d", mktime(0, 0, 0, $alvk+1, 0, $alvv));
    $virhelisa   = "";
  }

  $kerroin     = '';
  $maalisa      = '';
  $_309lisa      = '';
  $vainveroton    = '';
  $tuotetyyppilisa = '';
  $kolmilkantakauppa = '';

  if ($ryhma == 'fi307') {
    // Kohdekuukauden vähennettävä vero
    $maalisa = " and if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) in ('FI', '') ";
  }

  if ($ryhma == 'fi309') {
    // 0-verokannan alainen liikevaihto
    $query = "SELECT group_concat(DISTINCT concat('\'',koodi,'\'')) maat FROM maat WHERE eu = ''";
    $result = pupe_query($query);
    $maarow = mysql_fetch_assoc($result);

    // Kaikki ei-EU-maat plus FI ja tyhjä
    $maalisa = " and if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) in ('','FI', $maarow[maat]) ";

    $_309lisa    = " or alv_taso like '%fi300%' ";
    $vainveroton = " and tiliointi.vero = 0 ";
  }

  if ($ryhma == 'fi312') {
    // Palveluiden myynti muihin EU-maihin
    $tuotetyyppilisa = " AND tuote.tuotetyyppi = 'K' ";
  }
  elseif ($ryhma == 'fi311') {
    // Tavaran myynti muihin EU-maihin
    $tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
  }

  // Tasot kuntoon
  if ($ryhma == 'fi301' or $ryhma == 'fi302' or $ryhma == 'fi303') {
    // Vero Kotimaan Myynnistä Verokannoittain
    $taso = 'fi300';
    $kerroin = ' * -1 ';
  }
  elseif ($ryhma == 'fi312' or $ryhma == 'fi311') {
    // Tavaran/Palveluiden myynti muihin EU-maihin
    $taso = 'fi311';
    $kerroin = ' * -1 ';
    $kolmikantakauppa = "AND lasku.kolmikantakauppa = ''";
  }
  elseif ($ryhma == 'fi313') {
    // Tavaraostot muista EU-maista
    $taso = 'fi305';
  }
  elseif ($ryhma == 'fi318') {
    // Rakannuspalveluiden ostot
    $taso = 'fi320';
  }
  elseif ($ryhma == 'fi314') {
    // Palveluostot muista EU-maista
    $taso = 'fi306';
  }
  elseif ($ryhma == 'fi309') {
    // Muu/0-verokannan alainen liikevaihto
    $taso = "fi309%' or alv_taso like '%fi300";
    $kerroin = ' * -1 ';
  }
  else {
    $taso = $ryhma;
  }

  $query = "SELECT ifnull(group_concat(if(alv_taso like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit300,
            ifnull(group_concat(if(alv_taso not like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
            FROM tili
            WHERE yhtio = '$kukarow[yhtio]'
            and (alv_taso like '%$taso%' $_309lisa)";
  $tilires = pupe_query($query);
  $tilirow = mysql_fetch_assoc($tilires);

  if ($tilirow['tilit300'] != '' or $tilirow['tilitMUU'] != '') {

    echo "<table>";
    echo "<tr>";

    switch ($ryhma) {
    case 'fi301' :
      $tiliointilisa .= " and tiliointi.vero in (22, 23, 24) ";
      break;
    case 'fi302' :
      $tiliointilisa .= " and tiliointi.vero in (12, 13, 14) ";
      break;
    case 'fi303' :
      $tiliointilisa .= " and tiliointi.vero in (8, 9, 10) ";
      break;
    }

    if ($ryhma == 'fi307') $tiliointilisa .= " and tiliointi.vero > 0 ";

    $tilinolisa = "";
    if ($tilirow["tilit300"] != "") $tilinolisa .= "(tiliointi.tilino in ($tilirow[tilit300]) $vainveroton)";
    if ($tilirow["tilit300"] != "" and $tilirow["tilitMUU"] != "") $tilinolisa .= " or ";
    if ($tilirow["tilitMUU"] != "") $tilinolisa .= " tiliointi.tilino in ($tilirow[tilitMUU])";

    echo "<br><font class='head'>".t("Arvonlisäveroerittely kaudelta")." $alvv-$alvk " . t("taso") . " $ryhma</font><hr>";

    $query = "SELECT if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) maa,
              if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
              tiliointi.vero,
              tiliointi.tilino,
              tili.nimi,
              group_concat(lasku.tunnus) ltunnus,
              sum(round(tiliointi.summa * (1 + tiliointi.vero / 100), 2)) $kerroin bruttosumma,
              sum(round(tiliointi.summa * if (('$ryhma' = 'fi305' or '$ryhma' = 'fi306' or '$ryhma' = 'fi318'), ($oletus_verokanta / 100), tiliointi.vero / 100), 2)) $kerroin verot,
              sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + vero / 100), 2)) $kerroin bruttosumma_valuutassa,
              sum(round(tiliointi.summa / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * vero / 100, 2)) $kerroin verot_valuutassa,
              count(*) kpl
              FROM tiliointi
              JOIN lasku ON (lasku.yhtio = tiliointi.yhtio AND lasku.tunnus = tiliointi.ltunnus {$kolmikantakauppa})
              LEFT JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              AND tiliointi.korjattu = ''
              AND tiliointi.tapvm    >= '$alkupvm'
              AND tiliointi.tapvm    <= '$loppupvm'
              $maalisa
              $tiliointilisa
              AND ($tilinolisa)
              GROUP BY 1, 2, 3, 4, 5
              ORDER BY maa, valuutta, vero, tilino, nimi";
    $result = pupe_query($query);

    echo "<table><tr>";
    echo "<th valign='top'>" . t("Maa") . "</th>";
    echo "<th valign='top'>" . t("Val") . "</th>";
    echo "<th valign='top'>" . t("Vero") . "</th>";
    echo "<th valign='top'>" . t("Tili") . "</th>";
    echo "<th valign='top'>" . t("Nimi") . "</th>";
    echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
    echo "<th valign='top'>" . t("Verot") . "</th>";
    echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
    echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
    echo "<th valign='top'>" . t("Kpl") . "</th>";
    echo "</tr>";

    $verosum  = 0.0;
    $kplsum   = 0;
    $verotot  = 0.0;
    $kpltot   = 0;
    $kantasum = 0.0;
    $kantatot = 0.0;

    while ($trow = mysql_fetch_assoc($result)) {

      // Vaihtuiko verokanta?
      if (isset($edvero) and ($edvero != $trow["vero"] or $edmaa != $trow["maa"])) {
        echo "<tr>
            <td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
            <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
            <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
            <td colspan='2' class='spec'></td>
            <td align = 'right' class='spec'>$kplsum</td></tr>";

        $verosum   = 0.0;
        $kplsum   = 0;
        $kantasum  = 0.0;
      }

      echo "<tr>";
      echo "<td valign='top'>$trow[maa]</td>";
      echo "<td valign='top'>$trow[valuutta]</td>";
      echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
      echo "<td valign='top'><a href='{$palvelin2}raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk$virhelisa&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]//etsivirheita=$etsivirheita'>$trow[tilino]</a></td>";
      echo "<td valign='top'>$trow[nimi]</td>";
      echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma']), "</td>";
      echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot']), "</td>";

      if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
        echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma_valuutassa']), "</td>";
        echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot_valuutassa']), "</td>";
      }
      else {
        echo "<td valign='top' align='right'></td>";
        echo "<td valign='top' align='right'></td>";
      }

      echo "<td valign='top' align='right' nowrap>$trow[kpl]</td>";
      echo "</tr>";

      $verosum  += $trow['verot'];
      $kplsum   += $trow['kpl'];
      $verotot  += $trow['verot'];
      $kpltot   += $trow['kpl'];
      $kantasum += $trow['bruttosumma'];
      $kantatot += $trow['bruttosumma'];
      $edvero    = $trow["vero"];
      $edmaa      = $trow["maa"];
    }

    // Tälle kuukaudelle tiliöidyt kassa-alennukset
    if ($ryhma == "fi305" or $ryhma == "fi306" or $ryhma == 'fi311' or $ryhma == 'fi312' or $ryhma == "fi313" or $ryhma == "fi314") {

      // Tälle kuukaudelle tiliöidyt kassa-alennukset
      if ($ryhma == "fi305" or $ryhma == "fi306") {
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", $ryhma, $oletus_verokanta);
      }
      elseif ($ryhma == "fi313" or $ryhma == "fi314") {
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", $ryhma, 0);
      }
      else {
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, $maalisa, $tiliointilisa, $ryhma, 0);
      }

      if (is_resource($ttres)) {

        mysql_data_seek($ttres, 0);

        while ($trow = mysql_fetch_assoc($ttres)) {

          echo "<tr>
              <td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
              <td colspan='2' class='spec'></td>
              <td align = 'right' class='spec'>$kplsum</td></tr>";

          $verosum   = 0.0;
          $kplsum   = 0;
          $kantasum  = 0.0;

          echo "<tr>";
          echo "<td valign='top'>$trow[maa]</td>";
          echo "<td valign='top'>$trow[valuutta]</td>";
          echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
          echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk$virhelisa&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]//etsivirheita=$etsivirheita'>$trow[tilino]</a></td>";
          echo "<td valign='top'>$trow[nimi]</td>";
          echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma']), "</td>";
          echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot']), "</td>";

          if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
            echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma_valuutassa']), "</td>";
            echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot_valuutassa']), "</td>";
          }
          else {
            echo "<td valign='top' align='right'></td>";
            echo "<td valign='top' align='right'></td>";
          }

          echo "<td valign='top' align='right' nowrap>$trow[kpl]</td>";
          echo "</tr>";

          $verosum  += $trow['verot'];
          $kplsum   += $trow['kpl'];
          $verotot  += $trow['verot'];
          $kpltot   += $trow['kpl'];
          $kantasum += $trow['bruttosumma'];
          $kantatot += $trow['bruttosumma'];
        }
      }
    }

    echo "<tr><td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
        <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
        <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
        <td colspan = '2' class='spec'></td>
        <td align = 'right' class='spec'>$kplsum</td></tr>";

    if ($ryhma == 'fi307') {
      $query = "SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
                FROM tili
                WHERE yhtio  = '$kukarow[yhtio]'
                and alv_taso in ('fi305', 'fi306')";
      $tilires = pupe_query($query);
      $tilirow = mysql_fetch_assoc($tilires);

      $vero = 0.0;

      if ($tilirow['tilit'] != '') {
        $query = "SELECT sum(round(summa * ($oletus_verokanta / 100), 2)) veronmaara
                  FROM tiliointi
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND korjattu = ''
                  AND tilino   in ($tilirow[tilit])
                  AND tapvm    >= '$alkupvm'
                  AND tapvm    <= '$loppupvm'";
        $verores = pupe_query($query);

        while ($verorow = mysql_fetch_assoc($verores)) {
          $vero += $verorow['veronmaara'];
        }

        // Kassa-alennukset
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi305", $oletus_verokanta);

        if (is_resource($ttres)) {
          while ($trow = mysql_fetch_assoc($ttres)) {
            $vero += $trow['verot'];
          }
        }

        // Kassa-alennukset
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi306", $oletus_verokanta);

        if (is_resource($ttres)) {
          while ($trow = mysql_fetch_assoc($ttres)) {
            $vero += $trow['verot'];
          }
        }

        echo "<tr><td colspan='5' align='right' class='spec'>".t("Vero tavaraostoista muista EU-maista").":</td>
            <td class='spec'></td>
            <td align = 'right' class='spec'>".sprintf('%.2f', $vero)."</td>
            <td colspan='2' class='spec'></td>
            <td class='spec'></td></tr>";
        $verotot+=$vero;
      }

      $query = "SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
                FROM tili
                WHERE yhtio  = '$kukarow[yhtio]'
                and alv_taso in ('fi320')";
      $tilires = pupe_query($query);
      $tilirow = mysql_fetch_assoc($tilires);

      $vero = 0.0;

      if ($tilirow['tilit'] != '') {
        $query = "SELECT sum(round(summa * ($oletus_verokanta / 100), 2)) veronmaara
                  FROM tiliointi
                  WHERE yhtio  = '$kukarow[yhtio]'
                  AND korjattu = ''
                  AND tilino   in ($tilirow[tilit])
                  AND tapvm    >= '$alkupvm'
                  AND tapvm    <= '$loppupvm'";
        $verores = pupe_query($query);

        while ($verorow = mysql_fetch_assoc($verores)) {
          $vero += $verorow['veronmaara'];
        }

        // Kassa-alennukset
        list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($alkupvm, $loppupvm, "", "", "fi320", $oletus_verokanta);

        if (is_resource($ttres)) {
          while ($trow = mysql_fetch_assoc($ttres)) {
            $vero += $trow['verot'];
          }
        }

        echo "<tr><td colspan='5' align='right' class='spec'>".t("Vero rakentamispalveluiden ostoista").":</td>
            <td class='spec'></td>
            <td align = 'right' class='spec'>".sprintf('%.2f', $vero)."</td>
            <td colspan='2' class='spec'></td>
            <td class='spec'></td></tr>";

        $verotot+=$vero;
      }
    }

    echo "<tr><td colspan='5' align='right' class='spec'>".t("Verokannat yhteensä").":</td>
        <td align = 'right' class='spec'>".sprintf('%.2f', $kantatot)."</td>
        <td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td>
        <td colspan = '2' class='spec'></td>
        <td align = 'right' class='spec'>$kpltot</td></tr>";
    echo "</table><br>";

    if ($ryhma == 'fi311' or $ryhma == 'fi312') {

      $query = "SELECT if(lasku.toim_maa = '', '$yhtiorow[maa]', lasku.toim_maa) maa,
                if(lasku.valkoodi = '', '$yhtiorow[valkoodi]', lasku.valkoodi) valuutta,
                tilausrivi.alv vero,
                group_concat(DISTINCT lasku.tunnus) ltunnus,
                sum(round(rivihinta * (1 + tilausrivi.alv / 100), 2)) bruttosumma,
                sum(round(rivihinta * (tilausrivi.alv / 100), 2)) verot,
                sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * (1 + tilausrivi.alv / 100), 2)) bruttosumma_valuutassa,
                sum(round(rivihinta / if(lasku.vienti_kurssi = 0, 1, lasku.vienti_kurssi) * tilausrivi.alv / 100, 2)) verot_valuutassa
                FROM lasku USE INDEX (yhtio_tila_tapvm)
                JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
                JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
                WHERE lasku.yhtio          = '$kukarow[yhtio]'
                and lasku.tila             = 'U'
                and lasku.tapvm            >= '$alkupvm'
                and lasku.tapvm            <= '$loppupvm'
                and lasku.vienti           = 'E'
                and lasku.kolmikantakauppa = ''
                GROUP BY 1, 2, 3
                ORDER BY maa, valuutta, vero";
      $result = pupe_query($query);

      if ($ryhma == 'fi311') {
        echo "<font class='head'>", t("Josta tavaramyyntiä"), ":</font><hr>";
      }
      else {
        echo "<font class='head'>", t("Josta palvelumyyntiä"), ":</font><hr>";
      }

      echo "<table><tr>";
      echo "<th valign='top'>" . t("Maa") . "</th>";
      echo "<th valign='top'>" . t("Val") . "</th>";
      echo "<th valign='top'>" . t("Vero") . "</th>";
      echo "<th valign='top'>" . t("Tili") . "</th>";
      echo "<th valign='top'>" . t("Nimi") . "</th>";
      echo "<th valign='top'>" . t("Verollinen summa") . "</th>";
      echo "<th valign='top'>" . t("Verot") . "</th>";
      echo "<th valign='top'>" . t("Verollinen summa valuutassa") . "</th>";
      echo "<th valign='top'>" . t("Verot valuutassa") . "</th>";
      echo "</tr>";

      $verosum  = 0.0;
      $kplsum   = 0;
      $verotot  = 0.0;
      $kpltot   = 0;
      $kantasum = 0.0;
      $kantatot = 0.0;
      unset($edvero);
      unset($edmaa);

      while ($trow = mysql_fetch_assoc($result)) {

        if ($trow['bruttosumma'] == 0) continue;

        if (isset($edvero) and ($edvero != $trow["vero"] or (isset($edmaa) and $edmaa != $trow["maa"]))) { // Vaihtuiko verokanta?
          echo "<tr>
              <td colspan = '5' align = 'right' class='spec'>".t("Yhteensä").":</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
              <td colspan='2' class='spec'></td>
              </tr>";

          $verosum   = 0.0;
          $kplsum   = 0;
          $kantasum  = 0.0;
        }

        $query = "SELECT group_concat(distinct tili.tilino) tilino, group_concat(distinct tili.nimi) nimi
                  FROM tiliointi
                  JOIN tili ON (tili.yhtio = tiliointi.yhtio AND tiliointi.tilino = tili.tilino)
                  WHERE tiliointi.yhtio = '$kukarow[yhtio]'
                  AND tiliointi.ltunnus in ($trow[ltunnus])
                  AND tiliointi.tilino  in ($tilirow[tilitMUU])";
        $tili_res = pupe_query($query);
        $tili_row = mysql_fetch_assoc($tili_res);

        $trow['tilino'] = $tili_row['tilino'];
        $trow['nimi'] = $tili_row['nimi'];

        echo "<tr>";
        echo "<td valign='top'>$trow[maa]</td>";
        echo "<td valign='top'>$trow[valuutta]</td>";
        echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
        echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk$virhelisa&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]//etsivirheita=$etsivirheita'>$trow[tilino]</a></td>";
        echo "<td valign='top'>$trow[nimi]</td>";
        echo "<td valign='top' align='right' nowrap>$trow[bruttosumma]</td>";
        echo "<td valign='top' align='right' nowrap>$trow[verot]</td>";

        if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
          echo "<td valign='top' align='right' nowrap>$trow[bruttosumma_valuutassa]</td>";
          echo "<td valign='top' align='right' nowrap>$trow[verot_valuutassa]</td>";
        }
        else {
          echo "<td valign='top' align='right'></td>";
          echo "<td valign='top' align='right'></td>";
        }

        echo "</tr>";

        $verosum  += $trow['verot'];
        $kplsum   += $trow['kpl'];
        $verotot  += $trow['verot'];
        $kpltot   += $trow['kpl'];
        $kantasum += $trow['bruttosumma'];
        $kantatot += $trow['bruttosumma'];
        $edvero    = $trow["vero"];
        $edmaa      = $trow["maa"];
      }

      if (is_resource($ttres)) {

        mysql_data_seek($ttres, 0);

        while ($trow = mysql_fetch_assoc($ttres)) {

          $trow['bruttosumma']       = round($kakerroinlisa * $trow['bruttosumma'], 2);
          $trow['verot']           = round($kakerroinlisa * $trow['verot'], 2);
          $trow['bruttosumma_valuutassa'] = round($kakerroinlisa * $trow['bruttosumma_valuutassa'], 2);
          $trow['verot_valuutassa']     = round($kakerroinlisa * $trow['verot_valuutassa'], 2);

          echo "<tr>
              <td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
              <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
              <td colspan='2' class='spec'></td></tr>";

          $verosum   = 0.0;
          $kplsum   = 0;
          $kantasum  = 0.0;

          echo "<tr>";
          echo "<td valign='top'>$trow[maa]</td>";
          echo "<td valign='top'>$trow[valuutta]</td>";
          echo "<td valign='top' align='right'>". (float) $trow["vero"]."%</td>";
          echo "<td valign='top'><a href='".$palvelin2."raportit.php?toim=paakirja&tee=P&alvv=$vv&alvk=$kk$virhelisa&tili=$trow[tilino]&alv=$trow[vero]&maarajaus=$trow[maa]&lopetus=$PHP_SELF////tee=VSRALVKK_UUSI_erittele//ryhma=$ryhma//vv=$vv//kk=$kk//maarajaus=$trow[maa]//etsivirheita=$etsivirheita'>$trow[tilino]</a></td>";
          echo "<td valign='top'>$trow[nimi]</td>";
          echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma']), "</td>";
          echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot']), "</td>";

          if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
            echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['bruttosumma_valuutassa']), "</td>";
            echo "<td valign='top' align='right' nowrap>", sprintf('%.2f', $trow['verot_valuutassa']), "</td>";
          }
          else {
            echo "<td valign='top' align='right'></td>";
            echo "<td valign='top' align='right'></td>";
          }

          echo "</tr>";

          $verosum  += $trow['verot'];
          $verotot  += $trow['verot'];
          $kantasum += $trow['bruttosumma'];
          $kantatot += $trow['bruttosumma'];
        }
      }

      echo "<tr><td colspan='5' align='right' class='spec'>".t("Yhteensä").":</td>
          <td align = 'right' class='spec'>".sprintf('%.2f', $kantasum)."</td>
          <td align = 'right' class='spec'>".sprintf('%.2f', $verosum)."</td>
          <td colspan='2' class='spec'></td></tr>";

      echo "<tr><td colspan='5' align='right' class='spec'>".t("Verokannat yhteensä").":</td>
          <td align = 'right' class='spec'>".sprintf('%.2f', $kantatot)."</td>
          <td align = 'right' class='spec'>".sprintf('%.2f', $verotot)."</td>
          <td colspan='2' class='spec'></td></tr>";
      echo "</table><br/>";

    }
  }
}

function laskeveroja($taso, $tulos) {
  global $yhtiorow, $kukarow, $startmonth, $endmonth, $oletus_verokanta;

  if ($tulos == $oletus_verokanta or $tulos == 'veronmaara' or $tulos == 'summa') {

    $maalisa      = '';
    $_309lisa      = '';
    $vainveroton    = '';
    $tuotetyyppilisa = '';
    $cleantaso      = $taso;

    if ($taso == 'fi307') {
      $maalisa = "JOIN lasku ON lasku.yhtio = tiliointi.yhtio and lasku.tunnus = tiliointi.ltunnus and if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) in ('FI', '')";
    }

    if ($taso == 'fi309') {
      $query = "SELECT group_concat(DISTINCT concat('\'',koodi,'\'')) maat FROM maat WHERE eu = ''";
      $result = pupe_query($query);
      $maarow = mysql_fetch_assoc($result);

      // Kaikki ei-EU-maat plus FI ja tyhjä
      $maalisa = "JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and if(lasku.toim_maa!='', lasku.toim_maa, if(lasku.maa != '', lasku.maa, '$yhtiorow[maa]')) in ('','FI', $maarow[maat])";

      $_309lisa    = " or alv_taso like '%fi300%' ";
      $vainveroton = " and tiliointi.vero = 0 ";
    }

    if ($taso == 'fi312') {
      $tuotetyyppilisa = " AND tuote.tuotetyyppi = 'K' ";
      $taso        = 'fi311';
      $cleantaso      = 'fi312';
      $kolmikantakauppa = "AND lasku.kolmikantakauppa = ''";
    }
    elseif ($taso == 'fi311') {
      $tuotetyyppilisa = " AND tuote.tuotetyyppi != 'K' ";
      $taso        = 'fi311';
      $cleantaso      = 'fi311';
      $kolmikantakauppa = "AND lasku.kolmikantakauppa = ''";
    }
    else {
      $kolmikantakauppa = "";
    }

    if ($taso == 'fi313') {
      $taso        = 'fi305';
      $cleantaso      = 'fi313';
    }
    elseif ($taso == 'fi314') {
      $taso        = 'fi306';
      $cleantaso      = 'fi314';
    }
    elseif ($taso == 'fi318') {
      $taso        = 'fi320';
      $cleantaso      = 'fi318';
    }

    $query = "SELECT ifnull(group_concat(if(alv_taso like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilit300,
              ifnull(group_concat(if(alv_taso not like '%fi300%', concat(\"'\",tilino,\"'\"), NULL)), '') tilitMUU
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]'
              and (alv_taso like '%$taso%' $_309lisa)";
    $tilires = pupe_query($query);
    $tilirow = mysql_fetch_assoc($tilires);

    $vero = 0.0;

    if ($tilirow['tilit300'] != '' or $tilirow['tilitMUU'] != '') {


      $tilinolisa = "";
      if ($tilirow["tilit300"] != "") $tilinolisa .= "(tiliointi.tilino in ($tilirow[tilit300]) $vainveroton)";
      if ($tilirow["tilit300"] != "" and $tilirow["tilitMUU"] != "") $tilinolisa .= " or ";
      if ($tilirow["tilitMUU"] != "") $tilinolisa .= " tiliointi.tilino in ($tilirow[tilitMUU])";

      if ($tuotetyyppilisa != '') {
        $query = "SELECT lasku.tunnus, lasku.arvo laskuarvo, round(sum(tilausrivi.rivihinta),2) summa
                  FROM lasku USE INDEX (yhtio_tila_tapvm)
                  JOIN tilausrivi USE INDEX (uusiotunnus_index) ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.uusiotunnus = lasku.tunnus)
                  JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno and tuote.tuoteno != '$yhtiorow[ennakkomaksu_tuotenumero]' $tuotetyyppilisa)
                  WHERE lasku.yhtio       = '$kukarow[yhtio]'
                  and lasku.tila          = 'U'
                  and lasku.tapvm         >= '$startmonth'
                  and lasku.tapvm         <= '$endmonth'
                  and lasku.vienti        = 'E'
                  and lasku.tilaustyyppi != '9'
                  {$kolmikantakauppa}
                  GROUP BY 1,2";
      }
      else {
        $query = "SELECT sum(round(tiliointi.summa * if('$tulos'='$oletus_verokanta', $oletus_verokanta, vero) / 100, 2)) veronmaara,
                  sum(tiliointi.summa) summa,
                   count(*) kpl
                  FROM tiliointi
                  $maalisa
                  WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                  AND tiliointi.korjattu = ''
                  AND ($tilinolisa)
                  AND tiliointi.tapvm    >= '$startmonth'
                  AND tiliointi.tapvm    <= '$endmonth'";
      }

      $verores = pupe_query($query);

      while ($verorow = mysql_fetch_assoc($verores)) {
        if ($tulos == $oletus_verokanta) $tulos = 'veronmaara';
        $vero += $verorow[$tulos];
      }
    }

    if ($cleantaso == "fi305" or $cleantaso == "fi306" or $cleantaso == "fi320") {
      // Vähennetään kassa-alennuksien laskennaliset verot Tavara/Palveluaostot muista EU-maista
      list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($startmonth, $endmonth, "", "", $cleantaso, $oletus_verokanta);

      if (is_resource($ttres)) {
        while ($trow = mysql_fetch_assoc($ttres)) {
          $vero += $trow['verot'];
        }
      }
    }

    if ($cleantaso == "fi313" or $cleantaso == "fi314") {
      // Vähennetään kassa-alennukset Tavara/Palveluaostot muista EU-maista
      list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($startmonth, $endmonth, "", "", $cleantaso, 0);

      if (is_resource($ttres)) {
        while ($trow = mysql_fetch_assoc($ttres)) {
          $vero += $trow['bruttosumma'];
        }
      }
    }

    if ($cleantaso == 'fi312' or $cleantaso == 'fi311') {
      // Vähennetään kassa-alennukset Tavaran/palveluiden myynnistä muihin EU-maihin
      list($kakerroinlisa, $ttres) = alvilmo_kassa_ale_erittely($startmonth, $endmonth, $maalisa, $vainveroton, $cleantaso, 0);

      if (is_resource($ttres)) {
        while ($trow = mysql_fetch_assoc($ttres)) {
          $vero += round($kakerroinlisa*$trow['bruttosumma'], 2);
        }
      }
    }
  }
  else {
    $vero = 0;
  }
  return sprintf('%.2f', $vero);
}

function alvlaskelma($kk, $vv) {
  global $yhtiorow, $kukarow, $startmonth, $endmonth, $etsivirheita, $oletus_verokanta, $maksettava_alv_tili, $palvelin2, $erotus_tili, $alv_laskelman_sallittu_erotus;

  echo "<font class='head'>".t("ALV-laskelma")."</font><hr>";

  if (isset($kk) and $kk != '') {
    if (isset($etsivirheita) and (int) $etsivirheita > 0) {

      echo "<br><a href='{$palvelin2}raportit/alv_laskelma_uusi.php?kk=$kk&vv=$vv&etsivirheita=".($etsivirheita-1)."'>".t("Edellinen päivä")."</a> ";
      echo t("ALV-laskelma")." ".t("päivältä")." $etsivirheita.$kk.$vv ";
      echo "<a href='{$palvelin2}raportit/alv_laskelma_uusi.php?kk=$kk&vv=$vv&etsivirheita=".($etsivirheita+1)."'>".t("Seuraava päivä")."</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      echo "<a href='{$palvelin2}raportit/tilioinnit_lajeittain.php?tee=raportti&laji=myynti&pp=$etsivirheita&kk=$kk&vv=$vv&lpp=$etsivirheita&lkk=$kk&lvv=$vv&lopetus={$palvelin2}raportit/alv_laskelma_uusi.php////tee=VSRALVKK_UUSI//vv=$vv//kk=$kk//etsivirheita=$etsivirheita'>".t("Näytä tiliöinnit lajeittain")."</a><br><br>";

      $startmonth  = date("Y-m-d", mktime(0, 0, 0, $kk, $etsivirheita, $vv));
      $endmonth   = date("Y-m-d", mktime(0, 0, 0, $kk, $etsivirheita, $vv));
    }
    else {
      $startmonth  = date("Y-m-d", mktime(0, 0, 0, $kk, 1, $vv));
      $endmonth   = date("Y-m-d", mktime(0, 0, 0, $kk+1, 0, $vv));
    }

    // 301-303 sääntö fi300
    $query = "SELECT group_concat(concat(\"'\",tilino,\"'\")) tilit
              FROM tili
              WHERE yhtio = '$kukarow[yhtio]' and alv_taso like '%fi300%'";
    $tilires = pupe_query($query);

    $fi3xx = array();

    $fi301 = 0.0;
    $fi302 = 0.0;
    $fi303 = 0.0;

    $tilirow = mysql_fetch_assoc($tilires);

    if ($tilirow['tilit'] != '') {
      $query = "SELECT vero, sum(round(tiliointi.summa * vero / 100 * -1, 2)) veronmaara, count(*) kpl
                FROM tiliointi
                JOIN lasku on (lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and lasku.tilaustyyppi != '9')
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.korjattu = ''
                AND tiliointi.tilino   in ($tilirow[tilit])
                AND tiliointi.tapvm    >= '$startmonth'
                AND tiliointi.tapvm    <= '$endmonth'
                AND tiliointi.vero     > 0
                GROUP BY vero
                ORDER BY vero DESC";
      $verores = pupe_query($query);

      while ($verorow = mysql_fetch_assoc($verores)) {

        switch ($verorow['vero']) {
        case 24 :
        case 23 :
        case 22 :
          $fi301 += $verorow['veronmaara'];
          break;
        case 14 :
        case 13 :
        case 12 :
          $fi302 += $verorow['veronmaara'];
          break;
        case 8 :
        case 9 :
        case 10 :
          $fi303 += $verorow['veronmaara'];
          break;
        default:
          $fi3xx[$verorow['vero']] += $verorow['veronmaara'];
          break;
        }
      }
    }

    // 305 "Vero tavaraostoista muista EU maista"
    $fi305 = laskeveroja('fi305', $oletus_verokanta);

    // 306 "Vero palveluostoista muista EU maista"
    $fi306 = laskeveroja('fi306', $oletus_verokanta);

    // 318 "Vero rakentamispalveluiden ostoista"
    $fi318 = laskeveroja('fi318', $oletus_verokanta);

    // 307 sääntö fi307
    $fi307 = laskeveroja('fi307', 'veronmaara') + $fi305 + $fi306 + $fi318;

    // 308 laskennallinen
    $fi308 = $fi301 + $fi302 + $fi303 + $fi305 + $fi306 + $fi318 - $fi307;

    // 309 sääntö fi309
    $fi309 = laskeveroja('fi309', 'summa') * -1;

    // 311 sääntö fi311
    $fi311 = laskeveroja('fi311', 'summa');

    // 312 sääntö fi312
    $fi312 = laskeveroja('fi312', 'summa');

    // 313 sääntö fi313
    $fi313 = laskeveroja('fi313', 'summa');

    // 314 sääntö fi314
    $fi314 = laskeveroja('fi314', 'summa');


    // 319 "Rakentamispalvelun myynnit"
    $fi319 = laskeveroja('fi319', 'summa') * -1;

    // 320 "Rakentamispalvelun ostot"
    $fi320 = laskeveroja('fi320', 'summa');

    if (strtoupper($yhtiorow["maa"]) == 'FI') {
      $uytunnus = tulosta_ytunnus($yhtiorow["ytunnus"]);
    }
    else {
      $uytunnus = $yhtiorow["ytunnus"];
    }

    echo "<br><table>";
    echo "<tr><th>", t("Ilmoittava yritys"), "</th><th>$uytunnus</th></tr>";
    echo "<tr><th>", t("Ilmoitettava kausi"), "</th><th>".substr($startmonth, 0, 4)."/".substr($startmonth, 5, 2)."</th></tr>";

    echo "<tr><th colspan='2'>", t("Vero kotimaan myynnistä verokannoittain"), "</th></tr>";

    if ($oletus_verokanta == 22) {
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi301&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>301</a> ", t("22% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi301)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi302&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>302</a> ", t("12% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi302)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi303&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>303</a> ", t("8% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi303)."</td></tr>";
    }
    elseif ($oletus_verokanta == 23) {
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi301&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>301</a> ", t("23% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi301)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi302&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>302</a> ", t("13% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi302)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi303&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>303</a> ", t("9% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi303)."</td></tr>";
    }
    else {
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi301&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>301</a> ", t("24% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi301)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi302&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>302</a> ", t("14% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi302)."</td></tr>";
      echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi303&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>303</a> ", t("10% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fi303)."</td></tr>";
    }

    foreach ($fi3xx as $fikey => $fival) {
      echo "<tr><td>xxx ".($fikey * 1).t("% :n vero"), "</td><td align='right'>".sprintf('%.2f', $fival)."</td></tr>";
    }

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi305&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>305</a> ", t("Vero tavaraostoista muista EU-maista"), "</td><td align='right'>".sprintf('%.2f', $fi305)."</td></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi306&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>306</a> ", t("Vero palveluostoista muista EU-maista"), "</td><td align='right'>".sprintf('%.2f', $fi306)."</td></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi318&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>318</a> ", t("Vero rakentamispalveluiden ostoista"), "</td><td align='right'>".sprintf('%.2f', $fi318)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi307&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>307</a> ", t("Kohdekuukauden vähennettävä vero"), "</td><td align='right'>".sprintf('%.2f', $fi307)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td>308 ", t("Maksettava vero"), " / ", t("Palautukseen oikeuttava vero"), " (-)</td><td align='right'>".sprintf('%.2f', $fi308)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi309&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>309</a> ", t("0-verokannan alainen liikevaihto"), "</td><td align='right'>".sprintf('%.2f', $fi309)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi311&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>311</a> ", t("Tavaran myynti muihin EU-maihin"), "</td><td align='right'>".sprintf('%.2f', $fi311)."</td></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi312&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>312</a> ", t("Palveluiden myynti muihin EU-maihin"), "</td><td align='right'>".sprintf('%.2f', $fi312)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi313&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>313</a> ", t("Tavaraostot muista EU-maista"), "</td><td align='right'>".sprintf('%.2f', $fi313)."</td></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi314&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>314</a> ", t("Palveluostot muista EU-maista"), "</td><td align='right'>".sprintf('%.2f', $fi314)."</td></tr>";

    echo "<tr><th colspan='2'></th></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi319&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>319</a> ", t("Rakentamispalvelun myynti"), "</td><td align='right'>".sprintf('%.2f', $fi319)."</td></tr>";
    echo "<tr class='aktiivi'><td><a href = '?tee=VSRALVKK_UUSI_erittele&ryhma=fi320&vv=$vv&kk=$kk&etsivirheita=$etsivirheita'>320</a> ", t("Rakentamispalvelun ostot"), "</td><td align='right'>".sprintf('%.2f', $fi320)."</td></tr>";

    echo "</table><br>";

    //HUOM: AND tiliointi.selite not like 'Avaavat saldot%'. Pitäisi mieluummin ratkaista niin, että "Avaavat saldot"-tositteen alatila ois esim "A"

    $query = "SELECT sum(tiliointi.summa) vero
              FROM tiliointi
              WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
              AND tiliointi.korjattu = ''
              AND tiliointi.selite   not like 'Avaavat saldot%'
              AND tiliointi.tilino   = '$yhtiorow[alv]'
              AND tiliointi.tapvm    >= '$startmonth'
              AND tiliointi.tapvm    <= '$endmonth'";
    $verores = pupe_query($query);
    $verorow = mysql_fetch_assoc($verores);

    // ei näytetä yhteensä-laatikkoa turhaan
    if ($verorow["vero"] != 0 or (($verorow['vero'] - $fi308) * -1) != $fi308 or $fi308 == 0) {
      echo "<table>";
      echo "<tr class='aktiivi'><th>", t("Tili"), " $yhtiorow[alv] ", t("yhteensä"), "</th><td align='right'>".sprintf('%.2f', $verorow['vero'] * -1)."</td></tr>";
      echo "<tr class='aktiivi'><th>", t("Maksettava alv"), "</th><td align='right'>".sprintf('%.2f', $fi308)."</td></tr>";
      echo "<tr class='aktiivi'><th>", t("Erotus"), "</th><td align='right'>".sprintf('%.2f', (-1 * $verorow['vero']) - $fi308)."</td></tr>";
      echo "</table><br>";
    }

    if (tarkista_oikeus("muutosite.php") and (!isset($etsivirheita) or $etsivirheita == 0)) {

      $query = "SELECT lasku.tunnus
                FROM lasku
                JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus)
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.tapvm   = '{$endmonth}'
                AND lasku.tila    = 'X'
                AND lasku.nimi    = 'ALVTOSITEMAKSUUN$endmonth'";
      $tositelinkki_result = pupe_query($query);

      if (mysql_num_rows($tositelinkki_result) > 0) {
        $tositelinkki_row = mysql_fetch_assoc($tositelinkki_result);
        echo "<a href='../muutosite.php?tee=E&tunnus={$tositelinkki_row['tunnus']}&lopetus={$palvelin2}raportit/alv_laskelma_uusi.php////kk=$kk//vv=$vv'>", t("Katso tositetta"), "</a><br /><br />";
      }
      elseif (abs($verorow['vero']) != 0 and abs(round((-1 * $verorow['vero']) - $fi308, 2)) <= $alv_laskelman_sallittu_erotus and (int) date("Ym") > (int) $vv.$kk) {
        echo "<form method='post' name='alv_ilmoituksen_kuittaus'>";
        echo "<table>";
        echo "<input type='hidden' name='alkukk' value='{$startmonth}' />";
        echo "<input type='hidden' name='loppukk' value='{$endmonth}' />";
        echo "<input type='hidden' name='vv' value='$vv' />";
        echo "<input type='hidden' name='kk' value='$kk' />";
        echo "<input type='hidden' name='tee' value='kuittaa_alv_ilmoitus' />";

        echo "<input type='hidden' name='alvmaks_yht' value='".round($fi308, 2)."' />";
        echo "<input type='hidden' name='alvtili_yht' value='".round($verorow['vero']*-1, 2)."' />";

        echo "<tr><th>", t("Anna maksettava ALV-tili"), "</th><td>";

        echo livesearch_kentta("alv_ilmoituksen_kuittaus", "TILIHAKU", "maksettava_alv_tili", 200, $maksettava_alv_tili, 'EISUBMIT');
        echo "</td></tr>";

        if (!isset($erotus_tili) or $erotus_tili == "") $erotus_tili = $yhtiorow["pyoristys"];

        echo "<tr><th>", t("Anna erotuksen tili"), "</th><td>";
        echo livesearch_kentta("erotuksen_kuittaus", "TILIHAKU", "erotus_tili", 200, $erotus_tili, 'EISUBMIT');

        echo "</td><td class='back'><input type='submit' value='", t("Kuittaa ALV-ilmoitus"), "' /></td></tr>";
        echo "</table></form><br />";
      }
      elseif (abs($verorow['vero']) != 0 and abs(round((-1 * $verorow['vero']) - $fi308, 2)) != 0 and (int) date("Ym") > (int) $vv.$kk) {
        echo "<font class='error'>", t("Tilin"), " {$yhtiorow['alv']} ", t("ja maksettavan arvonlisäveron luvut eivät täsmää"), "!</font><br /><br />";
      }
    }

    if (strpos($_SERVER['SCRIPT_NAME'], "viranomaisilmoitukset.php") !== FALSE) {
      $ilmoituskausi = str_replace("0", "", substr($startmonth, 5, 2));
      $ilmoitusvuosi = substr($startmonth, 0, 4);
      $file  = "000:VSRALVKK\n";
      $file .= "100:\n";
      $file .= "051:\n";
      $file .= "105:\n";
      $file .= "107:\n";
      $file .= "010:$uytunnus\n";
      $file .= "050:K\n";
      $file .= "052:$ilmoituskausi\n";
      $file .= "053:$ilmoitusvuosi\n";
      $file .= "301:".round($fi301*100, 0)."\n";
      $file .= "302:".round($fi302*100, 0)."\n";
      $file .= "303:".round($fi303*100, 0)."\n";
      $file .= "305:".round($fi305*100, 0)."\n";
      $file .= "306:".round($fi306*100, 0)."\n";
      $file .= "318:".round($fi318*100, 0)."\n";
      $file .= "307:".round($fi307*100, 0)."\n";
      $file .= "308:".round($fi308*100, 0)."\n";
      $file .= "309:".round($fi309*100, 0)."\n";
      $file .= "311:".round($fi311*100, 0)."\n";
      $file .= "312:".round($fi312*100, 0)."\n";
      $file .= "313:".round($fi313*100, 0)."\n";
      $file .= "314:".round($fi314*100, 0)."\n";
      $file .= "319:".round($fi319*100, 0)."\n";
      $file .= "320:".round($fi320*100, 0)."\n";
      $file .= "999:1\n";

      $filenimi = "VSRALVKK-$kukarow[yhtio]-".date("dmy-His").".txt";
      file_put_contents("dataout/".$filenimi, $file);

      echo "  <form method='post' class='multisubmit'>
            <input type='hidden' name='tee' value='lataa_tiedosto'>
            <input type='hidden' name='lataa_tiedosto' value='1'>
            <input type='hidden' name='kaunisnimi' value='".t("arvonlisaveroilmoitus")."-$ilmoituskausi.txt'>
            <input type='hidden' name='filenimi' value='$filenimi'>
            <input type='submit' name='tallenna' value='".t("Tallenna tiedosto")."'>
          </form><br><br>";
    }
  }

  // tehdään käyttöliittymä, näytetään aina
  echo "<form method='post' action='{$palvelin2}raportit/alv_laskelma_uusi.php'><input type='hidden' name='tee' value ='VSRALVKK_UUSI'>";
  echo "<table>";

  if (!isset($vv)) $vv = date("Y");
  if (!isset($kk)) $kk = date("m");

  echo "<tr>";
  echo "<th>".t("Valitse kausi")."</th>";
  echo "<td>";

  $sel = array();
  $sel[$vv] = "SELECTED";

  $vv_select = date("Y") < 2010 ? 2010 : date("Y");

  echo "<select name='vv'>";
  for ($i = $vv_select; $i >= $vv_select-4; $i--) {
    if ($i < 2010) continue;
    echo "<option value='$i' $sel[$i]>$i</option>";
  }
  echo "</select>";

  $sel = array(1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => '', 10 => '', 11 => '', 12 => '');
  $sel[$kk] = "SELECTED";

  echo "<select name='kk'>
      <option $sel[01] value = '01'>01</option>
      <option $sel[02] value = '02'>02</option>
      <option $sel[03] value = '03'>03</option>
      <option $sel[04] value = '04'>04</option>
      <option $sel[05] value = '05'>05</option>
      <option $sel[06] value = '06'>06</option>
      <option $sel[07] value = '07'>07</option>
      <option $sel[08] value = '08'>08</option>
      <option $sel[09] value = '09'>09</option>
      <option $sel[10] value = '10'>10</option>
      <option $sel[11] value = '11'>11</option>
      <option $sel[12] value = '12'>12</option>
      </select>";
  echo "</td>";
  echo "<td class='back' style='text-align:bottom;'><input type = 'submit' value = '".t("Näytä")."'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<th>".t("Aja laskelma per päivä")."</th>";
  echo "<td><input type = 'checkbox' name='etsivirheita' value = '1'></td></tr>";
  echo "</table>";
  echo "</form><br>";
}

if (!isset($kk)) $kk = "";
if (!isset($vv)) $vv = "";

alvlaskelma($kk, $vv);

require "inc/footer.inc";
