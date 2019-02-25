<?php

if (isset($_REQUEST["tee"]) and $_REQUEST["tee"] == 'NAYTATILAUS') $nayta_pdf = 1; //Generoidaan .pdf-file

require "inc/parametrit.inc";

if (isset($_POST['ajax_toiminto']) and trim($_POST['ajax_toiminto']) != '') {
  require "inc/tilioinnin_toiminnot.inc";
}

if (!isset($ok)) $ok = 0;
if (!isset($tee)) $tee = "";
if (!isset($tee2)) $tee2 = "";
if (!isset($jaksota)) $jaksota = "";
if (!isset($lopetus)) $lopetus = "";
if (!isset($viivatut)) $viivatut = "";
if (!isset($tee_pdf)) $tee_pdf = "";
if (!isset($kpexport)) $kpexport = "";
if (!isset($liitetiedosto)) $liitetiedosto = 0;
if (!isset($livesearch_tee)) $livesearch_tee = "";
if (!isset($lisaselite)) $lisaselite = "";

$listaus = FALSE;

if ($tee_pdf == 'tulosta_karhu') {
  require 'myyntires/paperikarhu.php';
  exit;
}

if ($tee_pdf == 'tulosta_tratta') {
  require 'myyntires/paperitratta.php';
  exit;
}

if ($tee == 'tulosta_korkoerittely') {
  $apuqu = "SELECT *
            from lasku
            where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
  $res = pupe_query($apuqu);
  if (mysql_num_rows($res) == 1) {
    $trow = mysql_fetch_assoc($res);
    require 'myyntires/tulosta_korkoerittely.inc';
  }
  exit;
}

enable_ajax();

if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
  livesearch_tilihaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "ASIAKASHAKU") {
  livesearch_asiakashaku();
  exit;
}

if (isset($livesearch_tee) and $livesearch_tee == "TOIMITTAJAHAKU") {
  livesearch_toimittajahaku();
  exit;
}

// ekotetaan javascriptiä jotta saadaan pdf:ät uuteen ikkunaan
js_openFormInNewWindow();

js_popup();

echo "  <script language='javascript'>

      $(function() {

        $('.hae_lisaselite').on('click', function() {
          $('.lisaselite').val($('#lisaselite').val());
        });

      });

      $(document).ready(function() {
        $('#lisaselite').on('keyup change blur', function() {
          $('.selite').val($('#lisaselite').val());
        });
      });

    </script>";

echo "<font class='head'>".t("Tiliöintien muutos/selailu")."</font><hr>";

if (($tee == 'U' or $tee == 'P' or $tee == 'M' or $tee == 'J') and ($oikeurow['paivitys'] != 1)) {
  echo "<font class='error'>".t("Yritit päivittää vaikka sinulla ei ole siihen oikeuksia")."</font>";
  exit;
}

// Jaksotus
if ($tee == 'J') {
  require "inc/jaksota.inc";
}

// jos loppusumma on isompi kuin tietokannassa oleva tietuen koko (10 numeroa + 2 desimaalia), niin herjataan
if ($tee == 'U' and $summa != '' and abs($summa) > 0) {
  if (abs($summa) > 9999999999.99) {
    $tee = 'E';
    $tila = '';
    $virhe = t("VIRHE: liian iso summa")."!";
    $ok = 1;
  }
}

// Otsikon muutokseen
if ($tee == 'M') {
  require "inc/muutosite.inc";
}

// Seuraava "tosite"
if ($tee == 'G') {
  $query = "SELECT tapvm, tunnus
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  > '$tunnus'
            and tila    in ('H','Y','M','P','Q','X','U')
            ORDER by tunnus
            LIMIT 1";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $trow=mysql_fetch_assoc($result);
    $tunnus=$trow['tunnus'];
    $tee = 'E';
  }
  else {
    echo "<font class='error'>".t("Ei seuraavaa tositetta")."</font><br>";
    $tee = 'E';
  }
}

// Tositeselailu
if (isset($tee_Y) or isset($tee_Z) or isset($tee_X) or isset($tee_XKAIKKI) or isset($tee_W) or isset($tee_T) or isset($tee_S) or isset($tee_Å) or isset($tee_Ä) or isset($tee_automaattikirjauksia_muutettu) or isset($tee_kasintehtyja_alvkirjauksia) or isset($tee_alvkirjauksia_ilmanalvtasoa) or isset($tee_automaattikirjauksia_alv_muutettu) or isset($tee_KP)) {

  $tav = !isset($tav) ? 0 : (int) $tav;
  $tak = !isset($tak) ? 0 : (int) $tak;
  $tap = !isset($tap) ? 0 : (int) $tap;

  if  (isset($tee_Z) or isset($tee_X) or isset($tee_XKAIKKI) or isset($tee_W) or isset($tee_T) or isset($tee_S) or isset($tee_Å) or isset($tee_Ä) or isset($tee_automaattikirjauksia_muutettu) or isset($tee_kasintehtyja_alvkirjauksia) or isset($tee_alvkirjauksia_ilmanalvtasoa) or isset($tee_automaattikirjauksia_alv_muutettu) or isset($tee_KP)) {

    $ppa = (int) $ppa;
    $kka = (int) $kka;
    $vva = (int) $vva;
    $ppl = (int) $ppl;
    $kkl = (int) $kkl;
    $vvl = (int) $vvl;

    $query  = "SELECT TO_DAYS('{$vvl}-{$kkl}-{$ppl}') - TO_DAYS('{$vva}-{$kka}-{$ppa}') ero";
    $result = pupe_query($query);
    $row    = mysql_fetch_assoc($result);

    if ($row["ero"] > 365) {
      echo "<font class='error'>", t("Jotta homma ei menisi liian hitaaksi, niin vuosi on pisin mahdollinen laskentaväli!"), "</font><br>";

      unset($tee_alvkirjauksia_ilmanalvtasoa);
      unset($tee_automaattikirjauksia_alv_muutettu);
      unset($tee_automaattikirjauksia_muutettu);
      unset($tee_kasintehtyja_alvkirjauksia);
      unset($tee_KP);
      unset($tee_S);
      unset($tee_T);
      unset($tee_W);
      unset($tee_X);
      unset($tee_XKAIKKI);
      unset($tee_Y);
      unset($tee_Z);
      unset($tee_Ä);
      unset($tee_Å);
    }

    // Etsitään virheet vain kuluvalta tilikaudelta!
    if (isset($tee_Z)) {
      $query = "SELECT ltunnus, tapvm, round(sum(summa),2) summa, 'n/a', 'n/a', 'n/a', selite
                FROM tiliointi use index (yhtio_tapvm_tilino)
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.korjattu = ''
                AND tiliointi.tapvm    >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm    <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY ltunnus, tapvm
                HAVING summa <> 0";
    }
    if (isset($tee_X)) {
      // etsii kaikki tositteet joilta puuttuu kustannuspaikka, EI myynti ja tasetilit
      // Onko where oikein!
      $query = "SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
                FROM tiliointi use index (yhtio_tilino_tapvm), tili use index (tili_index)
                WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
                AND tili.yhtio          = '$kukarow[yhtio]'
                AND tili.sisainen_taso  like '3%'
                AND tiliointi.tilino    = tili.tilino
                AND tiliointi.korjattu  = ''
                AND tiliointi.tapvm     >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm     <= '{$vvl}-{$kkl}-{$ppl}'
                AND tiliointi.kustp     = 0
                AND tiliointi.tilino   != '$yhtiorow[myynti]'
                AND tiliointi.tilino   != '$yhtiorow[myynti_ei_eu]'
                AND tiliointi.tilino   != '$yhtiorow[myynti_eu]'
                AND tiliointi.tilino   != '$yhtiorow[varastonmuutos]'
                AND tiliointi.tilino   != '$yhtiorow[pyoristys]'";
    }
    if (isset($tee_XKAIKKI)) {
      // etsii kaikki tositteet joilta puuttuu kustannuspaikka, myös myynti ja tasetilit
      $query = "SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, 'n/a', 'n/a', 'n/a', tiliointi.selite
                FROM tiliointi use index (yhtio_tilino_tapvm)
                JOIN tili use index (tili_index) ON (tili.yhtio = tiliointi.yhtio AND tili.tilino = tiliointi.tilino)
                WHERE tiliointi.yhtio  = '{$kukarow["yhtio"]}'
                AND tiliointi.korjattu = ''
                AND tiliointi.tilino   not in ('{$yhtiorow["alv"]}', '{$yhtiorow["pyoristys"]}')
                AND tiliointi.tapvm    >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm    <= '{$vvl}-{$kkl}-{$ppl}'
                AND tiliointi.kustp    = 0
                ORDER BY tiliointi.ltunnus";
    }
    if (isset($tee_W)) {
      $query = "(SELECT lasku.tunnus ltunnus, lasku.laskunro, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm,
                 count(tiliointi.tunnus) saamistilejä,
                 round(sum(tiliointi.summa),2) heitto
                 FROM lasku
                 LEFT JOIN tiliointi ON lasku.yhtio = tiliointi.yhtio
                           and lasku.tunnus        = tiliointi.ltunnus
                           and lasku.tapvm         = tiliointi.tapvm
                           and abs(lasku.summa*lasku.vienti_kurssi + tiliointi.summa) <= 0.02
                           and tiliointi.korjattu  = ''
                           and tiliointi.tilino    in ('$yhtiorow[ostovelat]','$yhtiorow[konserniostovelat]')
                 WHERE lasku.yhtio                 = '$kukarow[yhtio]'
                 and lasku.tila                    in ('H','Y','M','P','Q')
                 AND lasku.tapvm                   >= '{$vva}-{$kka}-{$ppa}'
                 AND lasku.tapvm                   <= '{$vvl}-{$kkl}-{$ppl}'
                 GROUP BY 1,2,3,4,5,6
                 HAVING saamistilejä != 1)

                 UNION

                 (SELECT lasku.tunnus ltunnus, lasku.laskunro, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm,
                 count(tiliointi.tunnus) saamistilejä,
                 round(sum(tiliointi.summa),2) heitto
                 FROM lasku
                 LEFT JOIN tiliointi ON lasku.yhtio = tiliointi.yhtio
                           and lasku.tunnus        = tiliointi.ltunnus
                           and tiliointi.korjattu  = ''
                           and tiliointi.tilino    in ('$yhtiorow[ostovelat]','$yhtiorow[konserniostovelat]')
                 WHERE lasku.yhtio                 = '$kukarow[yhtio]'
                 and lasku.tila                    in ('H','Y','M','P','Q')
                 and lasku.mapvm                  != '0000-00-00'
                 AND lasku.tapvm                   >= '{$vva}-{$kka}-{$ppa}'
                 AND lasku.tapvm                   <= '{$vvl}-{$kkl}-{$ppl}'
                 GROUP BY 1,2,3,4,5,6
                 HAVING heitto <> 0)";
    }

    if (isset($tee_S)) {
      $query = "SELECT lasku.tunnus ltunnus, lasku.laskunro, lasku.nimi, lasku.summa, lasku.valkoodi, lasku.tapvm,
                if(sum(ifnull(t1.summa, 0))=0,0,1)+if(sum(ifnull(t2.summa, 0))=0,0,1)+if(sum(ifnull(t3.summa, 0))=0,0,1) korjattu,
                count(distinct t1.tilino)+count(distinct t2.tilino)+count(distinct t3.tilino) saamistilejä
                FROM lasku
                LEFT JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myyntisaamiset]'
                LEFT JOIN tiliointi t2 ON lasku.yhtio=t2.yhtio and lasku.tunnus=t2.ltunnus and t2.korjattu = '' and t2.tilino='$yhtiorow[factoringsaamiset]'
                LEFT JOIN tiliointi t3 ON lasku.yhtio=t3.yhtio and lasku.tunnus=t3.ltunnus and t3.korjattu = '' and t3.tilino='$yhtiorow[konsernimyyntisaamiset]'
                WHERE lasku.yhtio  = '$kukarow[yhtio]'
                and lasku.tila     = 'U'
                and lasku.alatila  = 'X'
                and lasku.mapvm   != '0000-00-00'
                AND lasku.tapvm    >= '{$vva}-{$kka}-{$ppa}'
                AND lasku.tapvm    <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY 1,2,3,4,5,6
                HAVING saamistilejä > 1 and korjattu > 0";
    }

    if (isset($tee_Å)) {
      $query = "(SELECT distinct lasku.tunnus ltunnus, lasku.laskunro, lasku.nimi, lasku.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr1.alv>=500, 'MV', tr1.alv) alv1, if(tr2.alv>=500, 'MV', tr2.alv) alv2, l2.laskunro, l2.nimi
                 FROM lasku
                 JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[osto_marginaali]'
                 JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl<0
                 JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.ostorivitunnus
                 JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.myyntirivitunnus=tr2.tunnus
                 JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
                 WHERE lasku.yhtio = '$kukarow[yhtio]'
                 and lasku.tila    = 'U'
                 and lasku.alatila = 'X'
                 AND lasku.tapvm   >= '{$vva}-{$kka}-{$ppa}'
                 AND lasku.tapvm   <= '{$vvl}-{$kkl}-{$ppl}'
                 HAVING (alv1 != 'MV' or alv2 != 'MV')
                 ORDER by lasku.laskunro)

                 UNION DISTINCT

                 (SELECT distinct l2.tunnus ltunnus, l2.laskunro, l2.nimi, l2.tapvm, tr1.tuoteno, s1.sarjanumero, if(tr2.alv>=500, 'MV', tr2.alv) alv2, if(tr1.alv>=500, 'MV', tr1.alv) alv1, lasku.laskunro, lasku.nimi
                 FROM lasku
                 JOIN tiliointi t1 ON lasku.yhtio=t1.yhtio and lasku.tunnus=t1.ltunnus and t1.korjattu = '' and t1.tilino='$yhtiorow[myynti_marginaali]'
                 JOIN tilausrivi tr1 ON lasku.yhtio=tr1.yhtio and lasku.tunnus=tr1.uusiotunnus and tr1.alv>=500 and tr1.kpl>0
                 JOIN sarjanumeroseuranta s1 ON tr1.yhtio=s1.yhtio and tr1.tunnus=s1.myyntirivitunnus
                 JOIN tilausrivi tr2 ON s1.yhtio=tr2.yhtio and s1.ostorivitunnus=tr2.tunnus
                 JOIN lasku l2 ON tr2.yhtio=l2.yhtio and tr2.uusiotunnus=l2.tunnus
                 WHERE lasku.yhtio = '$kukarow[yhtio]'
                 and lasku.tila    = 'U'
                 and lasku.alatila = 'X'
                 AND lasku.tapvm   >= '{$vva}-{$kka}-{$ppa}'
                 AND lasku.tapvm   <= '{$vvl}-{$kkl}-{$ppl}'
                 HAVING (alv1 != 'MV' or alv2 != 'MV')
                 ORDER by l2.laskunro)";
    }

    if (isset($tee_Ä)) {
      $query = "SELECT lasku.tunnus ltunnus, lasku.ytunnus, lasku.nimi, lasku.tapvm, lasku.summa, ifnull(sum(t1.summa),0) + ifnull(sum(t2.summa),0) + ifnull(sum(t3.summa),0) ero
                FROM lasku
                LEFT JOIN tiliointi t1 ON (lasku.yhtio = t1.yhtio and lasku.tunnus = t1.ltunnus and t1.korjattu = '' and t1.tilino = '$yhtiorow[myyntisaamiset]')
                LEFT JOIN tiliointi t2 ON (lasku.yhtio = t2.yhtio and lasku.tunnus = t2.ltunnus and t2.korjattu = '' and t2.tilino = '$yhtiorow[factoringsaamiset]')
                LEFT JOIN tiliointi t3 ON (lasku.yhtio = t3.yhtio and lasku.tunnus = t3.ltunnus and t3.korjattu = '' and t3.tilino = '$yhtiorow[konsernimyyntisaamiset]')
                WHERE lasku.yhtio  = '$kukarow[yhtio]'
                and lasku.tila     = 'U'
                and lasku.alatila  = 'X'
                and lasku.mapvm   != '0000-00-00'
                AND lasku.tapvm    >= '{$vva}-{$kka}-{$ppa}'
                AND lasku.tapvm    <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY ltunnus
                HAVING round(sum(t1.summa),2) != 0 or round(sum(t2.summa),2) != 0 or round(sum(t3.summa),2) != 0
                ORDER by lasku.laskunro";
    }

    if (isset($tee_automaattikirjauksia_muutettu)) {
      $query = "SELECT lasku.tunnus ltunnus, lasku.tapvm, lasku.summa, lasku.ytunnus, lasku.nimi, 'n/a'
                FROM tiliointi use index (yhtio_tapvm_tilino)
                JOIN lasku use index (PRIMARY) ON (lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q','U'))
                WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
                AND tiliointi.korjattu != ''
                AND tiliointi.tapvm     >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm     <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY lasku.tunnus
                ORDER BY lasku.tapvm, lasku.ytunnus";
      $viivatut = "on";
    }

    if (isset($tee_automaattikirjauksia_alv_muutettu)) {

      // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity
      $query = "SELECT ifnull(group_concat(DISTINCT concat(\"'\", toim_alv, \"'\")), '') tilino
                FROM yhtion_toimipaikat
                WHERE yhtio     = '$kukarow[yhtio]'
                and maa        != ''
                and vat_numero != ''
                and toim_alv   != ''";
      $alhire = pupe_query($query);
      $vrow = mysql_fetch_assoc($alhire);

      $tilino_alv = "'$yhtiorow[alv]'";

      if ($vrow["tilino"] != "") {
        $tilino_alv .= ",$vrow[tilino]";
      }

      $query = "SELECT lasku.tunnus ltunnus, lasku.tapvm, lasku.summa, lasku.ytunnus, lasku.nimi, 'n/a'
                FROM tiliointi use index (yhtio_tapvm_tilino)
                JOIN lasku use index (PRIMARY) ON (lasku.yhtio = '$kukarow[yhtio]' and lasku.tunnus = tiliointi.ltunnus and lasku.tila in ('H','Y','M','P','Q','U'))
                WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
                AND tiliointi.korjattu != ''
                AND tiliointi.tapvm     >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm     <= '{$vvl}-{$kkl}-{$ppl}'
                AND (tiliointi.vero != 0 or tiliointi.tilino in ($tilino_alv))
                GROUP BY lasku.tunnus
                ORDER BY lasku.tapvm, lasku.ytunnus";
      $viivatut = "on";
    }

    if (isset($tee_kasintehtyja_alvkirjauksia)) {
      // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity
      $query = "SELECT ifnull(group_concat(DISTINCT concat(\"'\", toim_alv, \"'\")), '') tilino
                FROM yhtion_toimipaikat
                WHERE yhtio     = '$kukarow[yhtio]'
                and maa        != ''
                and vat_numero != ''
                and toim_alv   != ''";
      $alhire = pupe_query($query);
      $vrow = mysql_fetch_assoc($alhire);

      $tilino_alv = "'$yhtiorow[alv]'";

      if ($vrow["tilino"] != "") {
        $tilino_alv .= ",$vrow[tilino]";
      }

      $query = "SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
                FROM tiliointi use index (yhtio_tapvm_tilino)
                WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
                AND tiliointi.korjattu  = ''
                and tiliointi.aputunnus = 0
                AND tiliointi.tilino    in ($tilino_alv)
                AND tiliointi.tapvm     >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm     <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY ltunnus, tapvm
                ORDER BY tapvm, ltunnus";
    }

    if (isset($tee_alvkirjauksia_ilmanalvtasoa)) {
      // tutkitaan onko tiliöintejä tileillä ilman alv-tasoa
      $query = "SELECT ltunnus, tapvm, summa, 'n/a', 'n/a', 'n/a', selite
                FROM tiliointi use index (yhtio_tapvm_tilino)
                JOIN tili on (tili.yhtio = tiliointi.yhtio and tili.tilino=tiliointi.tilino and tili.alv_taso = '')
                WHERE tiliointi.yhtio   = '$kukarow[yhtio]'
                AND tiliointi.korjattu  = ''
                AND tiliointi.vero     != 0
                AND tiliointi.tapvm     >= '{$vva}-{$kka}-{$ppa}'
                AND tiliointi.tapvm     <= '{$vvl}-{$kkl}-{$ppl}'
                GROUP BY ltunnus, tapvm
                ORDER BY tapvm, ltunnus";
    }

    if (isset($tee_KP)) {
      // tutkitaan saldoja kirjanpidon ja pankkisaldon välillä
      $query = "SELECT
                tiliotedata.aineisto ltunnus,
                tiliotedata.alku,
                tiliotedata.tilino,
                tiliotedata.tieto tiliote,
                group_concat(t1.tiliointitunnus) kirjanpito,
                '' ero,
                '' muutetut_tositteet
                FROM tiliotedata
                LEFT JOIN tiliotedata as t1 on (tiliotedata.yhtio=t1.yhtio and tiliotedata.tyyppi = t1.tyyppi and tiliotedata.aineisto = t1.aineisto and t1.tiliointitunnus > 0)
                WHERE tiliotedata.yhtio = '$kukarow[yhtio]'
                AND tiliotedata.alku    >= '{$vva}-{$kka}-{$ppa}'
                AND tiliotedata.alku    <= '{$vvl}-{$kkl}-{$ppl}'
                AND tiliotedata.tyyppi  = 1
                AND left(tiliotedata.tieto,3) = 'T50'
                AND SUBSTRING(tiliotedata.tieto, 7, 1) = 1
                GROUP BY 1,2,3,4";
    }
  }
  elseif ((int) $tav > 0 or strlen($selite) > 0 or strlen($tilino) > 0 or strlen($summa) > 0 or strlen($laatija) > 0 or ($kpexport == 1 and strlen($tositenro) > 0)) {

    $plisa = "";
    $lisa  = "";
    $summa = str_replace( ",", ".", $summa);

    if ($tav > 0 and $tav < 1000) {
      $tav += 2000;
    }

    if ($tav != 0 and $tak != 0 and $tap != 0) {
      $plisa = " and tapvm = '$tav-$tak-$tap' ";
    }
    elseif ($tav != 0 and $tak != 0) {
      $plisa = " and tapvm >= '$tav-$tak-01' and tapvm < '".date("Y-m-d", mktime(0, 0, 0, $tak+1, 1, $tav))."' ";
    }
    elseif ($tav != 0) {
      $plisa = " and tapvm >= '$tav-01-01' and tapvm < '".date("Y-m-d", mktime(0, 0, 0, 1, 1, $tav+1))."' ";
    }
    else {
      $plisa = " and tapvm >= '$yhtiorow[tilikausi_alku]' and tapvm <= '$yhtiorow[tilikausi_loppu]' ";
    }

    if (strlen($selite) > 0) {
      $lisa = " and selite";
      if ($ohita == 'on') {
        $lisa .= " not ";
      }
      $lisa .= " like '%" . $selite . "%'";
    }

    if (strlen($tilino) > 0) {
      $lisa .= " and tiliointi.tilino = '" . $tilino . "'";
    }

    if (strlen($summa) > 0) {
      $summa = abs($summa); // tehdään siitä positiivinen numero
      $lisa .= " and abs(tiliointi.summa) = $summa";
    }

    if (strlen($laatija) > 0) {
      $lisa .= " and tiliointi.laatija = '" . $laatija . "'";
    }

    if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
      if (strlen($tositenro) > 0) {
        list($tositenro1, $tositenro2) = explode("-", $tositenro);
        $tositenro1 = (int) $tositenro1;
        $tositenro2 = (int) $tositenro2;
        $tositenro = sprintf('%02d', $tositenro1) . sprintf('%06d', $tositenro2);
        $lisa .= " and tiliointi.tosite = '$tositenro' ";
      }
    }

    if ($viivatut != 'on') {
      $vlisa = "and tiliointi.korjattu=''";
      $slisa = "";
    }
    else {
      $vlisa = "and tiliointi.korjattu!=''";
      $slisa = ", concat_ws('@', korjattu, korjausaika) korjaus";
    }

    $query = "SELECT tiliointi.ltunnus, tiliointi.tapvm, tiliointi.summa, tili.tilino,
              tili.nimi, vero, selite $slisa
              FROM tiliointi use index (yhtio_tapvm_tilino), tili
              WHERE tiliointi.yhtio = '$kukarow[yhtio]'
              and tili.yhtio        = tiliointi.yhtio
              and tili.tilino       = tiliointi.tilino
              $plisa
              $vlisa
              $lisa
              ORDER BY tiliointi.ltunnus desc, tiliointi.tunnus";
  }
  else {
    // Ei haettu mitään, eli ei löydetä mitään
    $query = "SELECT tunnus
              FROM tiliointi
              WHERE tunnus = -1 ";
  }


  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Haulla ei löytynyt yhtään tositetta")."</font>";
  }
  else {

    // Tehdään lopetusmuuttuja kaikkiin urleihin
    $lopetus = "{$palvelin2}muutosite.php////tee=$tee//tap=$tap//tak=$tak//tav=$tav//summa=$summa//tilino=$tilino//selite=$selite//laatija=$laatija";

    $miinusta = 0;

    if (isset($tee_KP)) {
      $miinusta = 0;
    }

    echo "<table><tr>";

    for ($i = 1; $i < mysql_num_fields($result)+$miinusta; $i++) {
      echo "<th>".t(mysql_field_name($result, $i))."</th>";
    }

    echo "</tr>";
    echo "<tr class='aktiivi'>";

    $ero = 0;
    $edtunnus = 0;

    while ($trow = mysql_fetch_assoc($result)) {
      //Laitetaan linkki tuonne pvm:ään, näin voimme avata tositteita tab:eihin
      if ($edtunnus > 0 and ($tee != 'KP' or $ero != 0)) {
        // Tosite vaihtui
        if ($trow["ltunnus"] != $edtunnus) {
          echo "</tr><tr><th style='padding:3px;' colspan='".(mysql_num_fields($result)+$miinusta)."'></th></tr><tr class='aktiivi'>";
        }
        else {
          echo "</tr><tr class='aktiivi'>";
        }
      }

      $edtunnus = $trow["ltunnus"];

      for ($i=1; $i < mysql_num_fields($result)+$miinusta; $i++) {

        $kennimi = mysql_field_name($result, $i);

        if ($i == 1) {
          if (mysql_field_name($result, $i) == 'tapvm' or (mysql_field_name($result, $i) == 'alku' and isset($tee_KP))) {
            $orgpvm     = $trow[$kennimi];
            $trow[$kennimi] = tv1dateconv($trow[$kennimi]);

            // Ei tiedetä vielä näytetäänkö
            if (isset($tee_KP)) {
              ob_start();
            }
          }

          if ($tee !='KP') {
            echo "<td><a href = '$PHP_SELF?tee=E&tunnus=$edtunnus&viivatut=$viivatut&lopetus=$lopetus'>$trow[$kennimi]</td>"; // orkkis
          }
          elseif (($toikrow = tarkista_oikeus("tilioteselailu.php", "%", "", "OK")) !== FALSE) {
            echo "<td><a href = '{$palvelin2}tilioteselailu.php?toim={$toikrow['alanimi']}&tilino=$trow[tilino]&pvm=$orgpvm&tyyppi=1&tee=T&lopetus=$lopetus'>$trow[$kennimi]</td>";
          }
          else {
            echo "<td>$trow[$kennimi]</td>";
          }
        }
        elseif (is_numeric($trow[$kennimi]) and (mysql_field_type($result, $i) == 'real' or mysql_field_type($result, $i) == 'int')) {
          if ($kennimi == "vero") $trow[$kennimi] = $trow[$kennimi] * 1;

          echo "<td align='right'>$trow[$kennimi]</td>";
        }
        elseif (mysql_field_name($result, $i) == "tapvm") {
          echo "<td>".tv1dateconv($trow[$kennimi])."</td>";
        }
        elseif (mysql_field_name($result, $i) == "ytunnus") {
          echo "<td>".tarkistahetu($trow["ytunnus"])."</td>";
        }
        elseif (mysql_field_name($result, $i) == "tiliote" and isset($tee_KP)) {
          $pano = sprintf("%.2f", substr($trow[$kennimi], 21, 19) / 100);
          $otto = sprintf("%.2f", substr($trow[$kennimi], 48, 19) / 100);

          $saldo = sprintf("%.2f", $pano + $otto);

          if ($saldo > 0) {
            $psaldo = "<font class='ok'>".$saldo."</font>";
          }
          else {
            $psaldo = "<font class='error'>".$saldo."</font>";
          }

          echo "<td align='right'>".t("Panot")." <font class='ok'>".$pano."</font><br>";
          echo t("Otot")." <font class='error'>".$otto."</font><br>";
          echo t("Yhteensä")." ".$psaldo."</td>";
        }
        elseif (mysql_field_name($result, $i) == "kirjanpito" and isset($tee_KP)) {

          $huom   = "";
          $linkki = "";

          $orgpvm_pankki = substr(str_replace("-", "", $orgpvm), 2);

          //viiteaineistom summa, sen tiliöinnit ovat suoraan myyntilaskuilla, joten niitä ei oikein tähän saa haettua
          $subq = "SELECT sum(substring(tieto, 78, 10)/100) AS aineistosumma
                   FROM tiliotedata
                   WHERE yhtio = '$kukarow[yhtio]'
                   and tyyppi  = '3'
                   and tilino  = '$trow[tilino]'
                   and substring(tieto, 16, 6) = '$orgpvm_pankki'
                   and left(tieto, 1) = '3'";
          $subr = pupe_query($subq);
          $asumma = mysql_fetch_assoc($subr);

          if ($trow[$kennimi] != "") {
            // Haetaan tositteet
            $subq = "SELECT group_concat(distinct ltunnus) ltunnus
                     FROM tiliointi
                     WHERE yhtio = '$kukarow[yhtio]'
                     AND tunnus  IN ($trow[$kennimi])";
            $subr = pupe_query($subq);
            $ltunnukset = mysql_fetch_assoc($subr);

            $subq = "SELECT oletus_rahatili
                     FROM yriti
                     WHERE yhtio = '$kukarow[yhtio]'
                     and tilino  = '$trow[tilino]'";
            $subr = pupe_query($subq);
            $oratil = mysql_fetch_assoc($subr);

            $subquery = "SELECT sum(summa) as tiliointisumma
                         FROM tiliointi
                         WHERE yhtio   = '$kukarow[yhtio]'
                         and ltunnus   in ($ltunnukset[ltunnus])
                         and korjattu  = ''
                         and tilino   != '$oratil[oletus_rahatili]'
                         and tapvm     = '$orgpvm'";
            $subres = pupe_query($subquery);
            $tsumma = mysql_fetch_assoc($subres);

            $kpito = sprintf("%.2f", $asumma["aineistosumma"]-$tsumma["tiliointisumma"]);

            echo "<td align='right'><br><br>$kpito<br></td>";
          }
          else {
            $kpito = sprintf("%.2f", $asumma["aineistosumma"]);
            echo "<td><br><br>".t("Ei tiliöintejä")."</td>";
          }
        }
        elseif (mysql_field_name($result, $i) == "ero" and isset($tee_KP)) {
          $ero = sprintf("%.2f", $saldo-$kpito);

          echo "<td align='right'><br><br>$ero<br></td>";
        }
        elseif (mysql_field_name($result, $i) == "muutetut_tositteet" and isset($tee_KP)) {
          if ($ero != 0) {

            if ($ltunnukset["ltunnus"] != "") {
              $subquery = "SELECT distinct ltunnus, tapvm
                           FROM tiliointi
                           WHERE yhtio   = '$kukarow[yhtio]'
                           and ltunnus   in ($ltunnukset[ltunnus])
                           and korjattu != ''
                           and tilino   != '$oratil[oletus_rahatili]'
                           and tapvm     = '$orgpvm'";
              $subres = pupe_query($subquery);
            }

            echo "<td>";

            while ($tpoistetut = mysql_fetch_assoc($subres)) {
              echo "<a href = '$PHP_SELF?tee=E&tunnus=$tpoistetut[ltunnus]&viivatut=on&lopetus=$lopetus'>".tv1dateconv($tpoistetut["tapvm"])."<br>";
            }

            echo "</td>";

            $kala = ob_get_contents();
          }

          ob_end_clean();

          if ($ero != 0) {
            echo $kala;
          }
        }
        else {
          echo "<td>$trow[$kennimi]</td>";
        }
      }
    }

    echo "</tr>";
    echo "</table><br><br>";

    $listaus = TRUE;
  }

  $tee = "";
}

// Olemassaolevaa tiliöintiä muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
if ($tee == 'P') {

  $query = "SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm, tosite, summa_valuutassa, valkoodi, liitos, liitostunnus, tapahtumatunnus
            FROM tiliointi
            WHERE tunnus = '$ptunnus'
            AND yhtio    = '$kukarow[yhtio]'
            AND tapvm    >= '$yhtiorow[tilikausi_alku]'
            AND tapvm    <= '$yhtiorow[tilikausi_loppu]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo t("Tiliöintiä ei löydy tai se on lukitulla tilikaudella! Systeemivirhe!");
    exit;
  }

  $tiliointirow = mysql_fetch_assoc($result);

  $tili        = $tiliointirow['tilino'];
  $kustp        = $tiliointirow['kustp'];
  $kohde        = $tiliointirow['kohde'];
  $projekti      = $tiliointirow['projekti'];
  $summa        = $tiliointirow['summa'];
  $summa_valuutassa  = $tiliointirow['summa_valuutassa'];
  $valkoodi      = $tiliointirow["valkoodi"];
  $vero        = $tiliointirow['vero'];
  $selite        = $tiliointirow['selite'];
  $tiliointipvm    = $tiliointirow['tapvm'];
  $tositenro      = $tiliointirow['tosite'];
  $tapahtumatunnus  = $tiliointirow['tapahtumatunnus'];
  $ok          = 1;
  $alv_tili      = $yhtiorow["alv"];
  $liitos       = $tiliointirow['liitos'];
  $liitostunnus    = $tiliointirow['liitostunnus'];

  // Katotaan voisiko meillä olla tässä joku toinen ALV tili
  // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity ja oteteaan niiden alv tilit
  $query = "  SELECT alv_tili FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$tunnus' AND alv_tili != ''";
  $alv_tili_res = pupe_query($query);

  if (mysql_num_rows($alv_tili_res) == 1) {
    $alv_tili_row = mysql_fetch_assoc($alv_tili_res);
    $alv_tili = $alv_tili_row['alv_tili'];
  }

  $summa_kuuluva = etsi_kuuluvat_tilioinnit($ptunnus);

  $summa += $summa_kuuluva;

  $query = "UPDATE tiliointi SET
            korjattu     = '$kukarow[kuka]',
            korjausaika  = now()
            WHERE tunnus = '$ptunnus'
            AND yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  $tee = 'E'; // Näytetään miltä tosite nyt näyttää
}

// Lisätään tiliöintirivi
if ($tee == 'U') {
  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Laskua ei enää löydy! Systeemivirhe!");
    exit;
  }
  else {
    $laskurow = mysql_fetch_assoc($result);
  }

  $summa       = str_replace( ",", ".", $summa);
  $selausnimi   = 'tili'; // Minka niminen mahdollinen popup on?
  $tositetila   = $laskurow["tila"];
  $tositeliit   = $laskurow["liitostunnus"];
  $kustp_tark    = $kustp;
  $kohde_tark    = $kohde;
  $projekti_tark  = $projekti;

  require "inc/tarkistatiliointi.inc";

  $tiliulos = $ulos;
  $ulos = '';

  // Katotaan voisiko meillä olla tässä joku toinen ALV tili
  // tutkitaan ollaanko jossain toimipaikassa alv-rekisteröity ja oteteaan niiden alv tilit
  if ($laskurow['alv_tili'] != '') {
    $alv_tili = $laskurow['alv_tili'];
  }
  else {
    $alv_tili = $yhtiorow["alv"];
  }

  // Tarvitaan kenties tositenro
  if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {

    if ($tositenro != 0) {
      $query = "SELECT tosite FROM tiliointi
                WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' and tosite='$tositenro'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) == 0) {
        echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
        $tositenro = 0;
      }
    }
    else {
      //Tällä ei vielä ole tositenroa. Yritetään jotain
      switch ($laskurow['tila']) {
      case "X" : // Tämä on muistiotosite, sillä voi olla vain yksi tositenro
        $query = "SELECT distinct tosite
                  FROM tiliointi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and ltunnus = '$tunnus'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) != 1) {
          echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
          $tositenro=0;
        }
        else {
          $tositerow=mysql_fetch_assoc($result);
          $tositenro = $tositerow['tosite'];
        }
        break;

      case 'U' : //Tämä on myyntilasku
        $query = "SELECT tosite
                  FROM tiliointi
                  WHERE yhtio = '$kukarow[yhtio]'
                  and ltunnus = '$tunnus'";
        $result = pupe_query($query);
        if (mysql_num_rows($result) != 0) {

          // Tälle saamme tositenron myyntisaamisista
          if ($laskurow['tapvm'] == $tiliointipvm) {
            $query = "SELECT tosite
                      FROM tiliointi
                      WHERE yhtio = '$kukarow[yhtio]'
                      and ltunnus = '$tunnus'
                      and tapvm   = '$tiliointipvm'
                      and tilino  in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]', '$yhtiorow[factoringsaamiset]')
                      and summa   = $laskurow[summa]";
            $result = pupe_query($query);

            if (mysql_num_rows($result) == 0) {
              echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
              $tositenro=0;
            }
            else {
              $tositerow = mysql_fetch_assoc($result);
              $tositenro = $tositerow['tosite'];
            }
          }
          else {

            // Tälle saamme tositenron jostain samanlaisesta viennistä
            if ($laskurow['tapvm'] != $tiliointipvm) {
              $query = "SELECT tosite
                        FROM tiliointi
                        WHERE yhtio  = '$kukarow[yhtio]'
                        and ltunnus  = '$tunnus'
                        and tapvm    = '$tiliointipvm'
                        and tilino   not in ('$yhtiorow[myyntisaamiset]', '$yhtiorow[konsernimyyntisaamiset]', '$yhtiorow[factoringsaamiset]')
                        and summa   != $laskurow[summa]";
              $result = pupe_query($query);

              if (mysql_num_rows($result) == 0) {
                echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
                $tositenro=0;
              }
              else {
                $tositerow = mysql_fetch_assoc($result);
                $tositenro = $tositerow['tosite'];
              }
            }
          }
        }
        else {
          echo t("Tositenumeron tarkistus ei onnistu, koska tositteen kaikki tiliöinnit puuttuvat")."<br>";
          $tositenro=0;
        }
      default: //Tämän pitäisi olla nyt ostolasku

        // Tälle saamme tositenron ostoveloista
        if ($laskurow['tapvm'] == $tiliointipvm) {
          $query = "SELECT tosite
                    FROM tiliointi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and ltunnus = '$tunnus'
                    and tapvm   = '$tiliointipvm'
                    and tilino  in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
                    and summa   = round($laskurow[summa] * $laskurow[vienti_kurssi],2) * -1";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
            $tositenro = 0;
          }
          else {
            $tositerow = mysql_fetch_assoc($result);
            $tositenro = $tositerow['tosite'];
          }
        }

        // Tälle saamme tositenron ostoveloista
        if ($laskurow['mapvm'] == $tiliointipvm) {
          $query = "SELECT tosite
                    FROM tiliointi
                    WHERE yhtio = '$kukarow[yhtio]'
                    and ltunnus = '$tunnus'
                    and tapvm   = '$tiliointipvm'
                    and tilino  in ('$yhtiorow[ostovelat]', '$yhtiorow[konserniostovelat]')
                    and summa   = round($laskurow[summa] * $laskurow[vienti_kurssi],2)";
          $result = pupe_query($query);

          if (mysql_num_rows($result) == 0) {
            echo t("Tositenron tarkastus ei onnistu! Oletetaan nolla");
            $tositenro=0;
          }
          else {
            $tositerow=mysql_fetch_assoc($result);
            $tositenro = $tositerow['tosite'];
          }
        }
      }
    }
    echo "<font class='message'>".t("Tiliöintirivi liitettiin tositteeseen")." $tositenro</font><br>";
  }

  $tee = 'E';

  if ($ok != 1) {
    require "inc/teetiliointi.inc";

    $liitos = '';
    $liitostunnus = 0;
  }
}

// Tositeen näyttö muokkausta varten
if ($tee == 'E' or $tee == 'F') {

  // Näytetään laskun tai tositteen tiedot....
  $query = "SELECT lasku.*,
            ifnull(la.nimi, lasku.laatija) laatija_nimi,
            ifnull(h1.nimi, lasku.hyvak1) hyvak1_nimi,
            ifnull(h2.nimi, lasku.hyvak2) hyvak2_nimi,
            ifnull(h3.nimi, lasku.hyvak3) hyvak3_nimi,
            ifnull(h4.nimi, lasku.hyvak4) hyvak4_nimi,
            ifnull(h5.nimi, lasku.hyvak5) hyvak5_nimi,
            ifnull(ma.nimi, lasku.maksaja) maksaja_nimi,
            yriti.nimi maksajanpankkitili,
            yriti.tilino maksajanpankkitilinro
            FROM lasku
            LEFT JOIN yriti ON (lasku.yhtio = yriti.yhtio and maksu_tili = yriti.tunnus)
            LEFT JOIN kuka la ON (lasku.yhtio = la.yhtio and lasku.laatija = la.kuka)
            LEFT JOIN kuka h1 ON (lasku.yhtio = h1.yhtio and lasku.hyvak1 = h1.kuka)
            LEFT JOIN kuka h2 ON (lasku.yhtio = h2.yhtio and lasku.hyvak2 = h2.kuka)
            LEFT JOIN kuka h3 ON (lasku.yhtio = h3.yhtio and lasku.hyvak3 = h3.kuka)
            LEFT JOIN kuka h4 ON (lasku.yhtio = h4.yhtio and lasku.hyvak4 = h4.kuka)
            LEFT JOIN kuka h5 ON (lasku.yhtio = h5.yhtio and lasku.hyvak5 = h5.kuka)
            LEFT JOIN kuka ma ON (lasku.yhtio = ma.yhtio and lasku.maksaja = ma.kuka)
            WHERE lasku.tunnus = '$tunnus'
            and lasku.yhtio    = '$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) != 1) {
    echo t("Laskua ei löydy!");
    exit;
  }

  $trow = mysql_fetch_assoc($result);

  // jos pitää näyttää keikan tietoja
  if ($tee2 == "1") {
    // katotaan keikkajuttuja
    if ($trow["tila"] != "U" and $trow["tila"] != "L") {
      $query = "SELECT *
                from lasku
                where yhtio     = '$kukarow[yhtio]'
                and tila        = 'K'
                and vanhatunnus = '$tunnus'";
      $keikres = pupe_query($query);
      $keekrow = mysql_fetch_assoc($keikres);

      $query = "SELECT *
                FROM lasku
                WHERE yhtio     = '$kukarow[yhtio]'
                and tila        = 'K'
                and vanhatunnus = ''
                and laskunro    = '$keekrow[laskunro]'";
      $keikres = pupe_query($query);
      $keikrow = mysql_fetch_assoc($keikres);

      $query = "SELECT *
                from lasku
                where yhtio      = '$kukarow[yhtio]'
                and tila         = 'K'
                and laskunro     = '$keekrow[laskunro]'
                and vanhatunnus != '0'
                and vanhatunnus != '$tunnus'";
      $muutkeikres = pupe_query($query);
    }
  }

  $kurssi = $trow["maksu_kurssi"];
  if ((float) $kurssi == 0) $kurssi = $trow["vienti_kurssi"];

  // Myyntilaskut
  if ($trow['tila'] == 'U' or $trow['tila'] == 'L') {
    echo "<form name='ylaotsikko'>";
    // Tämä on koko yläotsikon table
    echo "<table>";
    // Aloitetaan vasen sarake
    echo "<tr><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    echo "<table>";
    echo "<tr><th>".t("Ytunnus")."</th><td>".tarkistahetu($trow["ytunnus"])."</td></tr>";
    echo "<tr><th>".t("Nimi")."</th><td>$trow[nimi]</td></tr>";
    if ($trow["nimitark"] != "") echo "<tr><th>".t("Nimitark")."</th><td>$trow[nimitark]</td></tr>";
    echo "<tr><th>".t("Osoite")."</th><td>$trow[osoite]</td></tr>";
    if ($trow["osoitetark"] != "") echo "<tr><th>".t("Osoitetark")."</th><td>$trow[osoitetark]</td></tr>";
    echo "<tr><th>".t("Postino")."</th><td>$trow[postino], $trow[postitp], $trow[maa]</td></tr>";

    if (($trow["toim_nimi"] != $trow["nimi"] or $trow["toim_nimitark"] != $trow["nimitark"] or $trow["toim_osoite"] != $trow["osoite"]) and ($trow["toim_nimi"] != "" or $trow["toim_nimitark"] != "" or $trow["toim_osoite"] != "")) {
      echo "<tr><td><br></td></tr>";
      echo "<tr><th>".t("Toim_nimi")."</th><td>$trow[toim_nimi]</td></tr>";
      if ($trow["toim_nimitark"] != "") echo "<tr><th>".t("Toim_nimitark")."</th><td>$trow[toim_nimitark]</td></tr>";
      echo "<tr><th>".t("Toim_osoite")."</th><td>$trow[toim_osoite]</td></tr>";
      echo "<tr><th>".t("Toim_postino")."</th><td>$trow[toim_postino], $trow[toim_postitp], $trow[toim_maa]</td></tr>";
    }

    echo "</table>";

    // Lopetettaan vasen sarake, aloitetaan keskisarake
    echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    echo "<table>";
    echo "<tr><th>".t("Tapvm")."</th><td>".tv1dateconv($trow["tapvm"])."</td></tr>";
    echo "<tr><th>".t("Eräpvm")."</th><td>".tv1dateconv($trow["erpcm"])."</td></tr>";
    echo "<tr><th>".t("Mapvm")."</th><td>".tv1dateconv($trow["mapvm"])."</td></tr>";
    echo "<tr><th nowrap>".t("Summa")." $trow[valkoodi]</th><td align='right'>$trow[summa_valuutassa]</td></tr>";

    if ($trow["valkoodi"] == $yhtiorow["valkoodi"]) {
      if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $trow[valkoodi]</th><td align='right'>$trow[kasumma]</td></tr>";
    }
    else {
      if ($trow["kasumma_valuutassa"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $trow[valkoodi]</th><td align='right'>$trow[kasumma_valuutassa]</td></tr>";
    }

    if ($trow["valkoodi"] != $yhtiorow["valkoodi"]) {
      echo "<tr><th nowrap>".t("Summa")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["summa"])."</td></tr>";
      if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["kasumma"])."</td></tr>";
    }
    echo "</table>";
    echo "</form>";
    // Lopetataan keskisarake, aloitetaan oikea sarake
    echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    echo "<table>";

    echo "<tr><th>".t("Laatija")."</th><td nowrap>".tv1dateconv($trow["luontiaika"], "PITKÄ")." &raquo; $trow[laatija_nimi]</td></tr>";

    // Onko laskua karhuttu?
    $karhu_query = "SELECT liitostunnus
                    FROM lasku
                    WHERE yhtio = '$kukarow[yhtio]'
                    AND tunnus  = '$trow[tunnus]'";
    $karhu_result = pupe_query($karhu_query);
    $karhu_liitos = mysql_fetch_assoc($karhu_result);

    // haetaan kaikki karhukerrat
    $karhu_query = "SELECT ifnull(group_concat(ktunnus), '') ktunnus
                    FROM karhu_lasku
                    WHERE karhu_lasku.ltunnus = '$trow[tunnus]'
                    ORDER BY ktunnus";
    $karhu_result = pupe_query($karhu_query);
    $karhu_row = mysql_fetch_assoc($karhu_result);

    if ($karhu_row["ktunnus"] != "") {
      // haetaan kaikki karhukerrat jotka kuuluu tähän laskuun
      $karhu_query = "SELECT pvm, tyyppi, karhukierros.tunnus ktunnus, group_concat(lasku.tunnus) laskutunnukset
                      FROM karhukierros
                      JOIN karhu_lasku ON (karhu_lasku.ktunnus = karhukierros.tunnus)
                      JOIN lasku USE INDEX (primary) ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = karhukierros.yhtio and lasku.tila = 'U' and lasku.liitostunnus = '$karhu_liitos[liitostunnus]')
                      WHERE karhukierros.yhtio = '$kukarow[yhtio]'
                      and karhukierros.tunnus  in ($karhu_row[ktunnus])
                      GROUP BY pvm, tyyppi, karhukierros.tunnus";
      $karhu_result = pupe_query($karhu_query);

      if (mysql_num_rows($karhu_result) > 0) {
        echo "<tr><th>", t('Maksukehotus / Tratta'), ":</th><td>";

        $laskuri = 0;

        while ($karhu_row = mysql_fetch_assoc($karhu_result)) {

          $laskuri++;

          if ($karhu_row["tyyppi"] == 'T') {
            echo "<form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post' action='".$palvelin2."muutosite.php'>
                <input type='hidden' name='karhutunnus' value='$karhu_row[ktunnus]'>
                <input type='hidden' name='lasku_tunnus[]' value='$karhu_row[laskutunnukset]'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='hidden' name='tee_pdf' value='tulosta_tratta'>
                <input type='submit' value='".t("Tratta")." - ".tv1dateconv($karhu_row["pvm"])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
                </form>";
          }
          else {
            echo "<form id='tulostakopioform_$laskuri' name='tulostakopioform_$laskuri' method='post' action='".$palvelin2."muutosite.php'>
                <input type='hidden' name='karhutunnus' value='$karhu_row[ktunnus]'>
                <input type='hidden' name='lasku_tunnus[]' value='$karhu_row[laskutunnukset]'>
                <input type='hidden' name='tee' value='NAYTATILAUS'>
                <input type='hidden' name='tee_pdf' value='tulosta_karhu'>
                <input type='submit' value='".t("Maksukehotus")." - ".tv1dateconv($karhu_row["pvm"])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$laskuri', ''); return false;\">
                </form>";
          }
          echo "<br>";
        }
        echo "</td></tr>";
      }
    }

    echo "<tr><th>".t("Maksutieto")."</th><td>".wordwrap($trow["viite"]." ".$trow["viesti"]." ".$trow["sisviesti1"], 45, "<br>")."</td></tr>";

    // katsotaan onko tästä laskusta tehty korkolasku
    $korko_query = "SELECT olmapvm, liitostunnus
                    FROM lasku
                    WHERE yhtio='$kukarow[yhtio]'
                    AND tunnus='$trow[tunnus]'
                    AND olmapvm > '0000-00-00'";
    $korko_result = pupe_query($korko_query);

    if (mysql_num_rows($korko_result) > 0) {

      $korkolaskurow = mysql_fetch_assoc($korko_result);

      // etsitään korkolasku
      $korko2_query = "SELECT lasku2.tunnus
                       FROM lasku
                       JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.tyyppi = 'L' AND tilausrivi.tuoteno = 'Korko' AND tilausrivi.otunnus = lasku.tunnus)
                       JOIN lasku AS lasku2 ON (lasku2.yhtio = lasku.yhtio AND lasku2.laskunro = lasku.laskunro AND lasku2.tila = 'U')
                       WHERE lasku.yhtio      = '$kukarow[yhtio]'
                       AND lasku.olmapvm      = '$korkolaskurow[olmapvm]'
                       AND lasku.tapvm        = '$korkolaskurow[olmapvm]'
                       AND lasku.liitostunnus = '$korkolaskurow[liitostunnus]'
                       AND lasku.tila         = 'L'";
      $korko2_result = pupe_query($korko2_query);

      if (mysql_num_rows($korko2_result) > 0) {

        echo "<tr><th>", t('Korkolaskut'), ":</th><td>";

        while ($korkolaskurow2 = mysql_fetch_assoc($korko2_result)) {
          echo "<form id='tulostakopioform_$korkolaskurow2[tunnus]' name='tulostakopioform_$korkolaskurow2[tunnus]' method='post' action='".$palvelin2."tilauskasittely/tulostakopio.php'>
              <input type='hidden' name='otunnus' value='$korkolaskurow2[tunnus]'>
              <input type='hidden' name='toim' value='LASKU'>
              <input type='hidden' name='tee' value='NAYTATILAUS'>
              <input type='submit' value='".tv1dateconv($korkolaskurow['olmapvm'])."' onClick=\"js_openFormInNewWindow('tulostakopioform_$korkolaskurow2[tunnus]', ''); return false;\">
              </form>";
        }

        echo "</td></tr>";
      }
    }

    if ($trow["comments"] != '' or ($trow['saldo_maksettu'] != 0 and $trow['mapvm'] == "0000-00-00")) {
      echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]";

      if ($trow['saldo_maksettu'] != 0 and $trow['mapvm'] == "0000-00-00") {
        if ($trow["comments"] != '') echo "<br>";
        echo t("Laskusta avoinna");
        echo " ";
        echo $trow['summa'] - $trow['saldo_maksettu'];

        if ($trow['valkoodi'] != $yhtiorow['valkoodi']) {
          echo " (";
          echo $trow['summa_valuutassa'] - $trow['saldo_maksettu_valuutassa'];
          echo $trow['valkoodi'];
          echo ")";
        }
      }
      echo "</td></tr>";
    }

    // tehdään laskulinkki
    echo "<tr><th>".t("Laskun kuva")."</th><td>".ebid($tunnus)."</td></tr>";
    echo "</table>";

    // Lopetaaan oikea sarake
    echo "</td></tr>";

    $query = "SELECT fakta
              FROM asiakas
              WHERE yhtio = '$kukarow[yhtio]' and tunnus='$trow[liitostunnus]'";
    $faktares = pupe_query($query);
    $faktarow = mysql_fetch_assoc($faktares);

    if (trim($faktarow["fakta"]) != "") {
      echo "<tr><th colspan='3'>".t("Fakta")."</th></tr>";
      echo "<tr><td colspan='3'>".wordwrap($faktarow["fakta"], 120, "<br>")."</td></tr>";
    }

    $_kirjanpidollinen_varastosiirto = ($yhtiorow['kirjanpidollinen_varastosiirto_myyntitilaukselta'] == 'K');

    if ($_kirjanpidollinen_varastosiirto and $trow['tila'] == "U") {

      $query = "SELECT varastosiirto_tunnus
                FROM lasku
                WHERE yhtio              = '{$kukarow['yhtio']}'
                AND tila                 = 'L'
                AND alatila              = 'X'
                AND laskunro             = '{$trow['laskunro']}'
                AND varastosiirto_tunnus > 0";
      $varastosiirto_result = pupe_query($query);

      if (mysql_num_rows($varastosiirto_result) > 0) {
        echo "<tr>";
        echo "<th>".t('Varastosiirtotosite')."</th>";
        echo "<td colspan='5'>";

        if ($lopetus != '') $lopetus .= "/SPLIT/";

        while ($varastosiirto = mysql_fetch_assoc($varastosiirto_result)) {
          echo "<a href='{$palvelin2}muutosite.php?tee=E&tunnus={$varastosiirto['varastosiirto_tunnus']}&lopetus={$lopetus}{$palvelin2}muutosite.php////tee=E//tunnus={$trow['tunnus']}'>{$varastosiirto['varastosiirto_tunnus']}</a> ";
        }

        echo "</td>";
        echo "</tr>";
      }
    }

    echo "<tr><th colspan='3'>".t("Selite tiliöinneille")."</th></tr><tr><td colspan='3'><input type='text' id='lisaselite' value='{$lisaselite}' maxlength='150' size='60'></td></tr>";

    // Lopetaaan koko table
    echo "</table>";

  }
  // Tositteet
  elseif ($trow["tila"] == 'X') {

    if ($trow["liitostunnus"] > 0) {
      echo "<form name='ylaotsikko>";
      // Tämä on koko yläotsikon table
      echo "<table>";
      // Aloitetaan vasen sarake
      echo "<tr><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

      echo "<table>";
      echo "<tr><th>".t("Ytunnus")."</th><td>".tarkistahetu($trow["ytunnus"])."</td></tr>";
      echo "<tr><th>".t("Nimi")."</th><td>$trow[nimi]</td></tr>";
      if ($trow["nimitark"] != "") echo "<tr><th>".t("Nimitark")."</th><td>$trow[nimitark]</td></tr>";
      echo "<tr><th>".t("Osoite")."</th><td>$trow[osoite]</td></tr>";
      if ($trow["osoitetark"] != "") echo "<tr><th>".t("Osoitetark")."</th><td>$trow[osoitetark]</td></tr>";
      echo "<tr><th>".t("Postino")."</th><td>$trow[postino], $trow[postitp], $trow[maa]</td></tr>";
      echo "</table>";

      echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";
    }

    echo "<table>";
    echo "<tr><th>".t("Laatija")."</th><td nowrap>$trow[laatija_nimi] @ ".tv1dateconv($trow["luontiaika"], "PITKÄ")."</td></tr>";
    echo "<tr><th>".t("Tapvm")."</th><td>".tv1dateconv($trow["tapvm"])."</td></tr>";
    echo "<tr><th>".t("Kommentti")."</th><td>$trow[comments]</td></tr>";
    echo "<tr><th>".t("Liitetiedostot")."</th><td>".ebid($tunnus) ."</td></tr>";
    echo "</table>";

    if ($trow["liitostunnus"] > 0) {
      echo "</td></tr></table>";
    }
    echo "</form>";
  }
  // Jotain muuta kuin Myytilasku tai Tosite
  else {
    echo "<form name='ylaotsikko'>";
    // Tämä on koko yläotsikon table
    echo "<table>";
    // Aloitetaan vasen sarake
    echo "<tr><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    echo "<table>";
    echo "<tr><th>".t("Ytunnus")."</th><td>".tarkistahetu($trow["ytunnus"])."</td></tr>";
    echo "<tr><th>".t("Nimi")."</th><td>$trow[nimi]</td></tr>";
    if ($trow["nimitark"] != "") echo "<tr><th>".t("Nimitark")."</th><td>$trow[nimitark]</td></tr>";
    echo "<tr><th>".t("Osoite")."</th><td>$trow[osoite]</td></tr>";
    if ($trow["osoitetark"] != "") echo "<tr><th>".t("Osoitetark")."</th><td>$trow[osoitetark]</td></tr>";
    echo "<tr><th>".t("Postino")."</th><td>$trow[postino], $trow[postitp], $trow[maa]</td></tr>";
    echo "<tr><th>".t("Tilinumero")."</th><td>$trow[ultilno]</td></tr>";

    //Ulkomaan ostolaskuille
    if (strtoupper($trow["maa"]) != strtoupper($yhtiorow["maa"])) {
      if ($trow["pankki_haltija"] != "") echo "<tr><th>".t("Pankkihaltija")."</th><td>$trow[pankki_haltija]</td></tr>";
      if ($trow["pankki1"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki1]</td></tr>";
      if ($trow["pankki2"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki2]</td></tr>";
      if ($trow["pankki3"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki3]</td></tr>";
      if ($trow["pankki4"] != "") echo "<tr><th>".t("Pankkitieto")."</th><td>$trow[pankki4]</td></tr>";
      if ($trow["swift"] != "") echo "<tr><th>".t("Swift")."</th><td>$trow[swift]</td></tr>";
    }

    echo "<tr><th>".t("Maksutieto")."</th><td>".wordwrap($trow["viite"]." ".$trow["viesti"]." ".$trow["sisviesti1"], 40, "<br>")."</td></tr>";
    echo "</table>";
    echo "</form>";
    // Lopetaan vasen sarake, aloitetaan keskisarake
    echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    if ($tee2 != 1) {

      echo "<table>";
      if ($yhtiorow['ostolaskujen_paivays'] == "1" and $trow["lapvm"] != '0000-00-00') {
        echo "<tr><th>".t("Lapvm")."</th><td align='right'>".tv1dateconv($trow["lapvm"])."</td></tr>";
      }
      echo "<tr><th>".t("Tapvm")."</th><td align='right'>".tv1dateconv($trow["tapvm"])."</td></tr>";
      echo "<tr><th>".t("Eräpvm")."</th><td align='right'>".tv1dateconv($trow["erpcm"])."</td></tr>";
      echo "<tr><th>".t("Olmapvm")."</th><td align='right'>".tv1dateconv($trow["olmapvm"])."</td></tr>";
      echo "<tr><th>".t("Mapvm")."</th><td align='right'>".tv1dateconv($trow["mapvm"])."</td></tr>";
      echo "<tr><th nowrap>".t("Summa")." $trow[valkoodi]</th><td align='right'><strong>$trow[summa]</strong></td></tr>";
      if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $trow[valkoodi]</th><td align='right'>$trow[kasumma]</td></tr>";
      if ($trow["valkoodi"] != $yhtiorow["valkoodi"]) {
        echo "<tr><th nowrap>".t("Summa")." $yhtiorow[valkoodi]</th><td align='right'><strong>".sprintf("%.02f", $trow["summa"] * $kurssi)."</strong></td></tr>";
        if ($trow["kasumma"] != 0) echo "<tr><th nowrap>".t("Kassa-ale")." $yhtiorow[valkoodi]</th><td align='right'>".sprintf("%.02f", $trow["kasumma"] * $kurssi)."</td></tr>";
      }

      if ($trow["vanhatunnus"] != 0) {
        $query = "SELECT *
                  from lasku
                  where yhtio     = '$kukarow[yhtio]'
                  and tila        in ('H','Y','M','P','Q')
                  and vanhatunnus = '$trow[vanhatunnus]'";
        $jaetutres = pupe_query($query);

        echo "<tr><td colspan='2'><br><font class='message'>".sprintf(t("Lasku on jaettu %s osaan!"), mysql_num_rows($jaetutres))."</font><br></td></tr>";

        echo "<tr><th>".t("Alkuperäinen summa")."</th><td align='right'><strong>$trow[arvo]</strong></td></tr>";
        $osa = 1;
        while ($jaetutrow = mysql_fetch_assoc($jaetutres)) {
          echo "<tr><th>".t("Osa")." $osa</th><td align='right'><a href='muutosite.php?tee=E&tunnus=$jaetutrow[tunnus]'>$jaetutrow[summa]</a></td></tr>";
          $osa++;
        }
      }

      if ($trow['laskunro'] != 0) echo "<tr><th nowrap>", t("Laskunro"), "</th><td align='right'>$trow[laskunro]</td></tr>";

      echo "</table>";
    }
    else { //Laajennetut
      echo "<table>";
      echo "<tr><th>".t("Saapuminen")."</th><td>$keikrow[laskunro]</td></tr>";
      echo "<tr><th>".t("Lähetysmaa")."</th><td>$keikrow[maa_lahetys]</td></tr>";
      echo "<tr><th>".t("Kuljetusmuoto")."</th><td>$keikrow[kuljetusmuoto]</td></tr>";
      echo "<tr><th>".t("KT")."</th><td>$keikrow[kauppatapahtuman_luonne]</td></tr>";
      if ($keikrow["rahti"] != 0) echo "<tr><th>".t("Rahti")."</th><td>$keikrow[rahti]</td></tr>";
      if ($keikrow["rahti_etu"] != 0) echo "<tr><th>".t("Eturahti")."</th><td>$keikrow[rahti_etu]</td></tr>";
      if ($keikrow["pyoristys_erot"] != 0) echo "<tr><th>".t("Pyöristyserot")."</th><td>$keikrow[pyoristys_erot]</td></tr>";
      if ($keikrow["rahti_huolinta"] != 0) echo "<tr><th>".t("Huolinta")."</th><td>$keikrow[rahti_huolinta]</td></tr>";
      if ($keikrow["erikoisale_saapuminen"] != 0) echo "<tr><th>".t("Alennus")."</th><td>$keikrow[erikoisale_saapuminen]</td></tr>";
      if ($keikrow["bruttopaino"] != 0) echo "<tr><th>".t("Paino")."</th><td>$keikrow[bruttopaino]</td></tr>";
      echo "<tr><th>".t("Toimaika")."</th><td>".tv1dateconv($keikrow["toimaika"])."</td></tr>";
      echo "<tr><th>".t("Kommentit")."</th><td>$keikrow[comments]</td></tr>";
      echo "<tr><th>".t("Saapumisen muut laskut")."</td><td>";
      while ($muutkeikrow = mysql_fetch_assoc($muutkeikres)) {
        echo "<a href='muutosite.php?tee=E&tunnus=$muutkeikrow[vanhatunnus]'>$muutkeikrow[nimi] ($muutkeikrow[summa])</a><br>";
      }
      echo "</td></tr>";
      echo "</table>";
    }

    // Lopetetaan keskisarake, aloitetaan oikea sarake
    echo "</td><td style='padding: 0px; margin: 0px; vertical-align:top;'>";

    echo "<table>";

    $laskutyyppi = $trow["tila"];
    $alatila = $trow["alatila"];
    require "inc/laskutyyppi.inc";

    echo "<tr><th>".t("Tila")."</th><td nowrap>$laskutyyppi $alatila</td></tr>";
    echo "<tr><th>".t("Laatija")."</th><td nowrap>".tv1dateconv($trow["luontiaika"], "PITKÄ")." &raquo; $trow[laatija_nimi]</td></tr>";
    echo "<tr><th>".t("Hyväksyjä1")."</th><td nowrap>".tv1dateconv($trow["h1time"], "PITKÄ")." &raquo; $trow[hyvak1_nimi]</td></tr>";
    if ($trow["hyvak2"] != "") echo "<tr><th>".t("Hyväksyjä2")."</th><td nowrap>".tv1dateconv($trow["h2time"], "PITKÄ")." &raquo; $trow[hyvak2_nimi]</td></tr>";
    if ($trow["hyvak3"] != "") echo "<tr><th>".t("Hyväksyjä3")."</th><td nowrap>".tv1dateconv($trow["h3time"], "PITKÄ")." &raquo; $trow[hyvak3_nimi]</td></tr>";
    if ($trow["hyvak4"] != "") echo "<tr><th>".t("Hyväksyjä4")."</th><td nowrap>".tv1dateconv($trow["h4time"], "PITKÄ")." &raquo; $trow[hyvak4_nimi]</td></tr>";
    if ($trow["hyvak5"] != "") echo "<tr><th>".t("Hyväksyjä5")."</th><td nowrap>".tv1dateconv($trow["h5time"], "PITKÄ")." &raquo; $trow[hyvak5_nimi]</td></tr>";
    echo "<tr><th>".t("Poimittu")."</th><td nowrap>".tv1dateconv($trow["maksuaika"], "PITKÄ", "")." &raquo; $trow[maksaja_nimi]</td></tr>";
    echo "<tr><th>".t("Maksuaineisto")."</th><td nowrap>";
    if ($trow["popvm"] != '0000-00-00 00:00:00') {
      $queryoik = "SELECT tunnus from oikeu where nimi like '%selaa_maksuaineisto.php' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
      $res = pupe_query($queryoik);

      if (mysql_num_rows($res) > 0) {
        list($apu, $dymmu) = explode(" ", $trow["popvm"]);
        list($apu_vv, $apu_kk, $apu_pp) = explode("-", $apu);
        echo "<a href='{$palvelin2}selaa_maksuaineisto.php?alkuvv=$apu_vv&alkukk=$apu_kk&alkupp=$apu_pp&loppuvv=$apu_vv&loppukk=$apu_kk&loppupp=$apu_pp&kuka_poimi=$trow[maksaja_nimi]'>";
        echo tv1dateconv($trow["popvm"], "PITKÄ", "");
        echo "</a>";
      }
      else {
        echo tv1dateconv($trow["popvm"], "PITKÄ", "");
      }
      echo " &raquo;  $trow[maksaja_nimi]";
    }
    echo "</td></tr>";
    if ($trow['maksajanpankkitili'] != '') echo "<tr><th>".t("Oma pankkitili")."</th><td>$trow[maksajanpankkitili] ($trow[maksajanpankkitilinro])</td></tr>";

    // tehdään laskulinkit
    echo "<tr><th nowrap>".t("Liitteet")."</th><td>".ebid($tunnus)."</td></tr>";
    echo "</table>";

    // Lopetetaan oikea sarake
    echo "</td></tr>";

    $query = "SELECT fakta
              FROM toimi
              WHERE yhtio = '$kukarow[yhtio]' and tunnus='$trow[liitostunnus]'";
    $faktares = pupe_query($query);
    $faktarow = mysql_fetch_assoc($faktares);

    if (trim($faktarow["fakta"]) != "" or $trow["comments"] != "") {
      $vali = " ";
      if ($faktarow["fakta"] != "" and $trow["comments"] != "") {
        $vali = "<br>";
      }
      echo "<tr><th colspan='3'>".t("Fakta")." / ".t("Kommentit")."</th></tr>";
      echo "<tr><td colspan='3'>".wordwrap($faktarow["fakta"].$vali.$trow["comments"], 120, "<br>")."</td></tr>";
    }

    echo "<tr><th colspan='3'>".t("Selite tiliöinneille")."</th></tr><tr><td colspan='3'><input type='text' id='lisaselite' value='{$lisaselite}' maxlength='150' size='60'></td></tr>";

    // Lopetetaan koko otsikko
    echo "</table>";
  }

  // Jaksotus käyttöliittymä
  if ($ok != 1 and $jaksota == 'on') {
    $tee = 'U';
    require "inc/jaksota.inc";
    if ($tee != "E") exit;
  }

  // Näytetään nappi vain jos siihen on oikeus
  if ($oikeurow['paivitys'] == 1) {
    echo "<form method='post'>";
    echo "<input type = 'hidden' name = 'lopetus' value = '{$lopetus}'>";
    echo "<input type = 'hidden' name = 'tee' value='M'>";
    echo "<input type = 'hidden' name = 'tila' value=''>";
    echo "<input type = 'hidden' name = 'tunnus' value='{$tunnus}'>";
    echo "<input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />";
    echo "<input type = 'submit' class='hae_lisaselite' value = '", t("Muuta tietoja"), "'>";
    echo "</form>";
  }

  $queryoik = "SELECT tunnus from oikeu where nimi like '%yllapito.php' and alanimi='liitetiedostot' and kuka='$kukarow[kuka]' and yhtio='$yhtiorow[yhtio]'";
  $res = pupe_query($queryoik);

  if (mysql_num_rows($res) > 0) {
    echo "<form method='POST' action='".$palvelin2."yllapito.php?toim=liitetiedostot&from=muutosite&ohje=off&haku[7]=@lasku&haku[8]=@$tunnus&lukitse_avaimeen=$tunnus&lukitse_laji=lasku'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus/SPLIT/{$palvelin2}muutosite.php////tee=E//tunnus=$tunnus'>
        <input type = 'submit' value='" . t('Muokkaa liitteitä')."'>
        </form>";
    $liitetiedosto = 1;
  }

  // Näytetään nappi vain jos tieoja on
  if ($trow['vienti'] != '' and $trow['vienti'] != 'A' and $trow['vienti'] != 'D' and $trow['vienti'] != 'G') {
    if ($tee2 != 1) {
      echo "<form method='post'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'tee' value='$tee'>
        <input type = 'hidden' name = 'tee2' value='1'>
        <input type = 'hidden' name = 'tunnus' value='$tunnus'>
        <input type = 'submit' value = '".t("Lisätiedot")."'></form>";
    }
    else {
      echo "<form method='post'>
        <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
        <input type = 'hidden' name = 'tee' value='$tee'>
        <input type = 'hidden' name = 'tunnus' value='$tunnus'>
        <input type = 'submit' value = '".t("Normaalitiedot")."'></form>";
    }
  }

  if ($trow['tila'] == 'U') {
    echo "<form id='tulostakopioform_$tunnus' name='tulostakopioform_$tunnus' method='post' action='".$palvelin2."tilauskasittely/tulostakopio.php' autocomplete='off'>
        <input type='hidden' name='otunnus' value='$tunnus'>
        <input type='hidden' name='toim' value='LASKU'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='submit' value='".t("Näytä laskun PDF")."' onClick=\"js_openFormInNewWindow('tulostakopioform_$tunnus', ''); return false;\"></form>";

    $query  = "SELECT *
               FROM maksuehto
               WHERE yhtio = '$kukarow[yhtio]'
               and tunnus  = '$trow[maksuehto]'";
    $masres = pupe_query($query);
    $masrow = mysql_fetch_assoc($masres);


    if (($yhtiorow["verkkolasku_lah"] == "iPost" or $yhtiorow["verkkolasku_lah"] == "finvoice" or $yhtiorow["verkkolasku_lah"] == "apix" or $yhtiorow["verkkolasku_lah"] == "maventa") and ($trow["vienti"] == "" or ($trow["vienti"] == "E" and ($trow["chn"] == "020" or $trow["chn"] == "030"))) and $masrow["itsetulostus"] == "" and $trow["sisainen"] == "" and $masrow["kateinen"] == "" and $trow["chn"] != '666' and $trow["chn"] != '667' and abs($trow["summa"]) != 0) {
      echo "<form id='finvoice_$tunnus' name='finvoice_$tunnus' method='post' action='".$palvelin2."tilauskasittely/uudelleenluo_laskuaineisto.php' autocomplete='off'>
          <input type='hidden' name='laskunumerot' value='$trow[laskunro]'>
          <input type='hidden' name='tee' value='NAYTATILAUS'>
          <input type='submit' value='".t("Näytä Finvoice")."' onClick=\"js_openFormInNewWindow('finvoice_$tunnus', ''); return false;\"></form>";
    }

    if ($trow['viesti'] == 'Korkolasku') {
      echo "<form method='post'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type='hidden' name='tunnus' value='$trow[tunnus]'>
      <input type='hidden' name='nayta_pdf' value='1'>
      <input type='hidden' name='tee' value='tulosta_korkoerittely'>
      <input type='submit' value='" . t('Tulosta korkoerittely')."'></form>";
    }
  }

  if ($trow["tila"] == "U" or $trow["tila"] == "L") {
    // Tehdään nappula, jolla voidaan vaihtaa näkymäksi tilausrivit/tiliöintirivit
    if ($tee == 'F') {
      $ftee = 'E';
      $fnappula = t('Näytä tiliöinnit');
    }
    else {
      $ftee = 'F';
      $fnappula = t('Näytä tilausrivit');
    }

    echo "<form method='post'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'tee' value='$ftee'>
      <input type = 'hidden' name = 'tunnus' value='$tunnus'>
      <input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />
      <input type = 'submit' class='hae_lisaselite' value = '$fnappula'>
      </form>
      <form method='post'>
      <input type = 'hidden' name = 'lopetus' value = '$lopetus'>
      <input type = 'hidden' name = 'tee' value='G'>
      <input type = 'hidden' name = 'tunnus' value='$tunnus'>
      <input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />
        <input type = 'submit' class='hae_lisaselite' value = '".t("Seuraava")."'>
      </form>";
  }

  // tehdään tiliöintisääntönappula, mikäli laskussa on liitettynä finveoice tai pupevoice lasku
  if ($liitetiedosto == 1) {
    $query = "SELECT filename, kayttotarkoitus, tunnus
              FROM liitetiedostot
              WHERE yhtio         = '$kukarow[yhtio]'
              AND liitos          = 'lasku'
              AND liitostunnus    = '$trow[tunnus]'
              AND kayttotarkoitus in ('FINVOICE','INVOIC.D.96A')";
    $tulokset = pupe_query($query);

    if (mysql_num_rows($tulokset) >0) {
      $ktarkoitus = mysql_fetch_assoc($tulokset);

      echo "<form method='POST' action='".$palvelin2."tiliointisaannot.php'>";
      echo "<input type = 'hidden' name = 'tila' value = 'XML'>";
      echo "<input type = 'hidden' name = 'tunnus' value = '$trow[liitostunnus]'>";
      echo "<input type = 'hidden' name = 'liitetiedosto' value = '$ktarkoitus[tunnus]'>";
      echo "<input type = 'hidden' name = 'kayttotyyppi' value = '$ktarkoitus[kayttotarkoitus]'>";
      echo "<input type='hidden' name='lopetus' value='$lopetus/SPLIT/${palvelin2}muutosite.php////tee=E//tunnus=$trow[tunnus]'>";
      echo "<input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />";
      echo "<input type = 'submit' class='hae_lisaselite' value='" . t('Tee tiliöintisääntö laskusta')."'>";
      echo "</form>";
    }
  }

  if ($tee == 'F') {
    // Laskun tilausrivit
    echo "<br><br>";
    require "inc/tilausrivit.inc";
  }
  else {
    echo "<br>";

    // Tositteen tiliöintirivit...
    require "inc/tiliointirivit.inc";

    echo "<br><br>";
  }
}

if ($tee == "") {

  if (!isset($formi) and $listaus === FALSE) {
    $formi = 'valikko';
    $kentta = 'tap';
  }

  echo "<form name = 'valikko' action='muutosite.php' method='post'>";
  echo "<input type='hidden' name='tee_Y' value='Y'>";
  echo "<table>";
  echo "<tr><th colspan='3'>".t("Etsi tositetta")."</th></tr>";
  echo "<tr>
      <td>".t("Pvm")."</td>
      <td>
      <input type='text' name='tap' maxlength='2' size=2>
      <input type='text' name='tak' maxlength='2' size=2>
      <input type='text' name='tav' maxlength='4' size=4></td>
      <td></td>
      </tr>
      <tr>
      <td>".t("Summa")."</td>
      <td><input type='text' name='summa' size=10></td>
      <td></td>
      </tr>
      <tr>
      <td>".t("Tili")."</td>
      <td><input type='text' name='tilino' size=10></td>
      <td></td>
      </tr>
      <tr>
      <td>".t("Osa selitteestä")."</td>
      <td><input type='text' name='selite' maxlength='15' size=10></td>
      <td><input type='checkbox' name='ohita' maxlength='15' size=10>".t("Ohita nämä")."</td>
      </tr>
      <tr>
      <td>".t("Laatija")."</td>
      <td><input type='text' name='laatija' size=10></td>
      <td></td>
      </tr>";

  //$kpexport tulee salanasat.php:stä
  if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
    echo "
      <tr>
      <td>".t("tositenumero")."</td>
      <td><input type='text' name='tositenro' size=10></td>
      <td></td>
      </tr>";
  }

  echo "<tr>
        <td>".t("Näytä yliviivatut rivit")."</td>
        <td><input type = 'checkbox' name = 'viivatut'></td>
      <input type = 'hidden' name = 'lisaselite' class='lisaselite' value='' />
        <td><input type = 'submit' class='hae_lisaselite hae_btn' value = '".t("Etsi")."'></td></tr>";

  echo "</table>";
  echo "</form>";



  echo "<form action = 'muutosite.php'>";
  echo "<br><br><table>";

  $tkalku = explode("-", $yhtiorow["tilikausi_alku"]);
  $tklopu = explode("-", $yhtiorow["tilikausi_loppu"]);

  if (!isset($kka)) $kka = $tkalku[1];
  if (!isset($vva)) $vva = $tkalku[0];
  if (!isset($ppa)) $ppa = $tkalku[2];
  if (!isset($kkl)) $kkl = $tklopu[1];
  if (!isset($vvl)) $vvl = $tklopu[0];
  if (!isset($ppl)) $ppl = $tklopu[2];

  // Ei sallita loppupäivää tulevaisuudessa
  if (date("Ymd", mktime(0, 0, 0, $kkl, $ppl, $vvl)) > date("Ymd")) {
    $kkl = date("m");
    $vvl = date("Y");
    $ppl = date("d");
  }

  $query  = "SELECT TO_DAYS('{$vvl}-{$kkl}-{$ppl}') - TO_DAYS('{$vva}-{$kka}-{$ppa}') ero";
  $result = pupe_query($query);
  $row    = mysql_fetch_assoc($result);

  // Sallitaan vain vuosi takaperin
  if ($row["ero"] > 365) {
    $kka = date("m", mktime(0, 0, 0, $kkl, $ppl-365, $vvl));
    $vva = date("Y", mktime(0, 0, 0, $kkl, $ppl-365, $vvl));
    $ppa = date("d", mktime(0, 0, 0, $kkl, $ppl-365, $vvl));
  }

  echo "  <tr><th colspan='2'>".t("Etsi tapahtumia ajalta").":
    <input type='text' name='ppa' value='{$ppa}' size='3'>
    <input type='text' name='kka' value='{$kka}' size='3'>
    <input type='text' name='vva' value='{$vva}' size='5'>
    -
    <input type='text' name='ppl' value='{$ppl}' size='3'>
    <input type='text' name='kkl' value='{$kkl}' size='3'>
    <input type='text' name='vvl' value='{$vvl}' size='5'>
    </th></tr>";

  echo "  <tr class='aktiivi'>
      <td>".t("näytä tositteet, jotka eivät täsmää")."</td>
        <td><input type = 'submit' name = 'tee_Z' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joilla on manuaalisia alv kirjauksia")."</td>
        <td><input type = 'submit' name = 'tee_kasintehtyja_alvkirjauksia' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joilla on alv kirjauksia tileille, jotka ei ole alv-ilmoituksessa")."</td>
        <td><input type = 'submit' name = 'tee_alvkirjauksia_ilmanalvtasoa' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joilta puuttuu kustannuspaikka")." (".t("ei huomioida myynti- ja varastonmuutostilejä").")</td>
        <td><input type = 'submit' name = 'tee_X' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joilta puuttuu kustannuspaikka")." (".t("huomioidaan kaikki tilit").")</td>
        <td><input type = 'submit' name = 'tee_XKAIKKI' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joiden ostovelat ei täsmää")."</td>
        <td><input type = 'submit' name = 'tee_W'  value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joiden myyntisaamiset ei täsmää")."</td>
        <td><input type = 'submit' name = 'tee_S' value = '".t("Näytä")."'></td>
      </tr>
      <tr class='aktiivi'>
        <td>".t("näytä maksetut laskut, joilla on myyntisaamisia")."</td>
        <td><input type = 'submit' name = 'tee_Ä' value = '".t("Näytä")."'></td>
      </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joiden automaattikirjauksia on muutettu")."</td>
        <td><input type = 'submit' name = 'tee_automaattikirjauksia_muutettu' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joiden automaattisia alv-kirjauksia on muutettu")."</td>
        <td><input type = 'submit' name = 'tee_automaattikirjauksia_alv_muutettu' value = '".t("Näytä")."'></td>
        </tr>
      <tr class='aktiivi'>
        <td>".t("näytä tositteet, joiden marginaaliverotiliöinnit ovat väärin")."</td>
        <td><input type = 'submit' name = 'tee_Å' value = '".t("Näytä")."'></td>
      </tr>
      <tr class='aktiivi'>
        <td>näytä saldo pankin tiliotteen ja kirjanpidon välillä</td>
        <td><input type = 'submit' name = 'tee_KP' value = '".t("Näytä")."'></td>
      </tr>
      </table>
      </form>";
}

require "inc/footer.inc";
