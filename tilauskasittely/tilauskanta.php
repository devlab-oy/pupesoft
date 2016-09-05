<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// DataTables päälle
$pupe_DataTables = "tilauskanta";

require '../inc/parametrit.inc';

echo "<font class='head'>".t("Tilauskanta")."</font><hr>";

if ($tee == 'NAYTATILAUS') {

  echo "<font class='head'>".t("Tilaus")." $tunnus:</font><hr>";

  require "raportit/naytatilaus.inc";

  $tee = 'aja';
}

if ($tee == 'aja') {

  $alkupvm = $vva."-".$kka."-".$ppa;
  $loppupvm = $vvl."-".$kkl."-".$ppl;

  $tuotelisa = '';

  if ((trim($tuotealku) != '' and trim($tuoteloppu) != '') or $osasto != '' or $try != '') {
    $tuotelisa = "  JOIN tilausrivi on lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
            and tilausrivi.tyyppi in ('L','V','W') and tilausrivi.var != 'P' and tilausrivi.varattu <> 0 and tilausrivi.laskutettuaika = '0000-00-00'  ";

    if ($osasto != '' or $try != '') {
      $tuotelisa .= " JOIN tuote on lasku.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno ";
    }

    if (trim($tuotealku) != '' and trim($tuoteloppu) != '') {
      $tuotelisa .= " and tilausrivi.tuoteno >= '$tuotealku' and tilausrivi.tuoteno <= '$tuoteloppu' ";
    }

    if ($osasto != '') {
      $tuotelisa .= " and tuote.osasto = '$osasto' ";
    }

    if ($try != '') {
      $tuotelisa .= " and tuote.try = '$try' ";
    }
  }

  $query = "(SELECT lasku.toimaika as 'Toimitusaika',
             concat(concat(lasku.nimi,'<br>'),if(lasku.nimitark!='',concat(lasku.nimitark,'<br>'),''),if(lasku.toim_nimi!='',if(lasku.toim_nimi!=lasku.nimi,concat(lasku.toim_nimi,'<br>'),''),''),if(lasku.toim_nimitark!='',if(lasku.toim_nimitark!=lasku.nimitark,concat(lasku.toim_nimitark,'<br>'),''),'')) as 'Nimi/Toim. nimi',
             lasku.tunnus as 'Tilausnro', lasku.tila, lasku.alatila, lasku.tilaustyyppi, lasku.viesti as viesti,
             if (kuka.kuka IS NOT NULL, kuka.nimi, '') AS myyja
             from lasku use index (tila_index)
             $tuotelisa
             LEFT JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
             where lasku.yhtio  = '$kukarow[yhtio]'
             and lasku.tila     in ('L','N','V')
             and lasku.alatila  not in ('X','V')
             and lasku.toimaika >= '$alkupvm' and lasku.toimaika <= '$loppupvm')

             UNION

             (SELECT lasku.toimaika as 'Toimitusaika',
             concat(concat(lasku.nimi,'<br>'),if(lasku.nimitark!='',concat(lasku.nimitark,'<br>'),''),if(lasku.toim_nimi!='',if(lasku.toim_nimi!=lasku.nimi,concat(lasku.toim_nimi,'<br>'),''),''),if(lasku.toim_nimitark!='',if(lasku.toim_nimitark!=lasku.nimitark,concat(lasku.toim_nimitark,'<br>'),''),'')) as 'Nimi/Toim. nimi',
             lasku.tunnus as 'Tilausnro', lasku.tila, lasku.alatila, lasku.tilaustyyppi, lasku.viesti as viesti,
             if (kuka.kuka IS NOT NULL, kuka.nimi, '') AS myyja
             from lasku use index (yhtio_tila_tapvm)
             $tuotelisa
             LEFT JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
             where lasku.yhtio  = '$kukarow[yhtio]'
             and lasku.tila     = 'N'
             and tapvm          = '0000-00-00'
             and lasku.toimaika >= '$alkupvm' and lasku.toimaika <= '$loppupvm')

             UNION

             (SELECT lasku.toimaika as 'Toimitusaika',
             concat(concat(lasku.nimi,'<br>'),if(lasku.nimitark!='',concat(lasku.nimitark,'<br>'),''),if(lasku.toim_nimi!='',if(lasku.toim_nimi!=lasku.nimi,concat(lasku.toim_nimi,'<br>'),''),''),if(lasku.toim_nimitark!='',if(lasku.toim_nimitark!=lasku.nimitark,concat(lasku.toim_nimitark,'<br>'),''),'')) as 'Nimi/Toim. nimi',
             lasku.tunnus as 'Tilausnro', lasku.tila, lasku.alatila, lasku.tilaustyyppi, lasku.viesti as viesti,
             if (kuka.kuka IS NOT NULL, kuka.nimi, '') AS myyja
             from lasku use index (yhtio_tila_tapvm)
             $tuotelisa
             LEFT JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
             where lasku.yhtio  = '$kukarow[yhtio]'
             and lasku.tila     = 'E'
             and tapvm          = '0000-00-00'
             and lasku.toimaika >= '$alkupvm' and lasku.toimaika <= '$loppupvm')

             ORDER BY 1, 3 ";
  $result = pupe_query($query);

  pupe_DataTables(array(array($pupe_DataTables, 8, 8, false, false)));

  echo "<table class='display dataTable' id='$pupe_DataTables'><thead><tr>";

  for ($i = 0; $i < mysql_num_fields($result)-4; $i++) {
    echo "<th align='left'>".t(mysql_field_name($result, $i))."</th>";
  }

  echo "<th align='left'>".t("Tyyppi")."</th>";
  echo "<th align='left'>".t("Viesti")."</th>";
  echo "<th align='left'>".t("Summa")."</th>";
  echo "<th align='left'>".t("Myyjä")."</th>";
  echo "</tr></thead>";

  $summat = 0;
  $arvot  = 0;

  echo "<tbody>";

  while ($prow = mysql_fetch_array($result)) {

    $ero="td";
    if ($tunnus==$prow['Tilausnro']) $ero="th";

    echo "<tr class='aktiivi'>";

    for ($i=0; $i < mysql_num_fields($result)-4; $i++) {
      if (mysql_field_name($result, $i) == 'Nimi/Toim. nimi' and substr($prow[$i], -4) == '<br>') {
        echo "<$ero valign='top'>".substr($prow[$i], 0, -4)."</$ero>";
      }
      elseif (mysql_field_name($result, $i) == 'Tilausnro') {
        echo "<$ero valign='top'><a href = '$PHP_SELF?tee=NAYTATILAUS&tunnus=$prow[$i]&ppa=$ppa&kka=$kka&vva=$vva&ppl=$ppl&kkl=$kkl&vvl=$vvl&tuotealku=$tuotealku&tuoteloppu=$tuoteloppu&osasto=$osasto&try=$try'>$prow[$i]</a></$ero>";
      }
      else {
        echo "<$ero valign='top'>".str_replace(".", ",", $prow[$i])."</$ero>";
      }
    }

    $laskutyyppi = $prow["tila"];
    $alatila     = $prow["alatila"];

    //tehdään selväkielinen tila/alatila
    require "inc/laskutyyppi.inc";

    $tarkenne = " ";

    if ($prow["tila"] == "V" and $prow["tilaustyyppi"] == "V") {
      $tarkenne = " (".t("Asiakkaalle").") ";
    }
    elseif ($prow["tila"] == "V" and  $prow["tilaustyyppi"] == "W") {
      $tarkenne = " (".t("Varastoon").") ";
    }

    echo "<$ero valign='top'>".t("$laskutyyppi")."$tarkenne".t("$alatila")."</$ero>";

    echo "<$ero valign='top'>{$prow['viesti']}</$ero>";

    if ($prow["tilaustyyppi"] != "W") {

      $query_ale_lisa = generoi_alekentta('M');

      // haetaan kaikkien avoimien tilausten arvo
      $sumquery = "SELECT
                   round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) arvo,
                   round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}),2) summa,
                   count(distinct lasku.tunnus) kpl
                   FROM lasku
                   JOIN tilausrivi use index (yhtio_otunnus) on (tilausrivi.yhtio=lasku.yhtio and tilausrivi.otunnus=lasku.tunnus and tilausrivi.tyyppi IN ('L','W'))
                   WHERE lasku.yhtio = '$kukarow[yhtio]'
                   and lasku.tunnus  = '$prow[Tilausnro]'";
      $sumresult = pupe_query($sumquery);
      $sumrow = mysql_fetch_array($sumresult);

      $sumrow["arvo"]  = (float) $sumrow["arvo"];
      $sumrow["summa"] = (float) $sumrow["summa"];

      echo "<$ero align='right' valign='top'>$sumrow[arvo]<br>$sumrow[summa]</$ero>";

      $summat += $sumrow["summa"];
      $arvot  += $sumrow["arvo"];
    }
    else {
      echo "<$ero align='right' valign='top'>0<br>0</$ero>";
    }

    echo "<{$ero} valign='top'>{$prow['myyja']}</{$ero}>";

    echo "</tr>";

  }

  echo "</tbody>";

  echo "<tfoot>";
  echo "<tr><td class='back' colspan='5'><th>".t("Veroton").":</th><td class='tumma' align='right' id='arvo_yhteensa'>".sprintf('%.2f', $arvot)."</th></tr>";
  echo "<tr><td class='back' colspan='5'><th>".t("Verollinen").":</th><td class='tumma' align='right' id='summa_yhteensa'>".sprintf('%.2f', $summat)."</th></tr>";
  echo "</tfoot>";

  echo "</table><br><br><br><br>";
}

echo "<table><form name='uliuli' method='post'>";
echo "<input type='hidden' name='tee' value='aja'>";
echo "<input type='hidden' name='tunnus' value=''>";

if (!isset($kka))
  $kka = date("m");
if (!isset($vva))
  $vva = date("Y");
if (!isset($ppa))
  $ppa = date("d");

if (!isset($kkl))
  $kkl = date("m", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
if (!isset($vvl))
  $vvl = date("Y", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
if (!isset($ppl))
  $ppl = date("d", mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));


echo "<tr><th>".t("Syötä toimitusajan alku (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppa' value='$ppa' size='3'></td>
    <td><input type='text' name='kka' value='$kka' size='3'></td>
    <td><input type='text' name='vva' value='$vva' size='5'></td>
    <td class='back'></td>
    </tr><tr><th>".t("Syötä toimitusajan loppu (pp-kk-vvvv)")."</th>
    <td><input type='text' name='ppl' value='$ppl' size='3'></td>
    <td><input type='text' name='kkl' value='$kkl' size='3'></td>
    <td><input type='text' name='vvl' value='$vvl' size='5'></td>
    <td class='back'></td></tr>";

echo "<tr><th>".t("Syötä tuotenumeroväli (Ei pakollinen)").":</th>
    <td colspan='3'><input type='text' name='tuotealku' value='$tuotealku' size='15'> - <input type='text' name='tuoteloppu' value='$tuoteloppu' size='15'></td></tr>";

// tehdään avainsana query
$sresult = t_avainsana("OSASTO");

echo "<tr><th>".t("Osasto (Ei pakollinen)")."</th><td colspan='3'>";

echo "<select name='osasto'>";
echo "<option value=''>".t("Näytä kaikki")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
  $sel = '';
  if ($osasto == $srow["selite"]) {
    $sel = "selected";
  }
  echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select>";
echo "</td></tr>";

// tehdään avainsana query
$sresult = t_avainsana("TRY");

echo "<tr><th>".t("Tuoteryhmä (Ei pakollinen)")."</th><td colspan='3'>";

echo "<select name='try'>";
echo "<option value=''>".t("Näytä kaikki")."</option>";

while ($srow = mysql_fetch_array($sresult)) {
  $sel = '';
  if ($try == $srow["selite"]) {
    $sel = "selected";
  }
  echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
}
echo "</select>";

echo "</td></tr>";


echo "</table>";

echo "<br><input type='submit' value='".t("Aja")."'>";
echo "</form>";


require "../inc/footer.inc";
