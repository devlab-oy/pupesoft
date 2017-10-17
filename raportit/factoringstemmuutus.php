<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Factioringtäsmäytys")."</font><hr>";

if (!$vva) {
  $vva = date('Y');
  $vvl = date('Y');
}

echo "<table>";
echo "<form name='stemmuutus' method='post' autocomplete='off'>";

$query = "SELECT *
          FROM factoring
          WHERE yhtio = '$kukarow[yhtio]'";
$vresult = pupe_query($query);

echo "<tr><th>".t("Factoringsopimus")."</th><td><select name = 'sopimus'>";

while ($vrow = mysql_fetch_assoc($vresult)) {
  $sel="";
  if ($sopimus == $vrow["tunnus"]) {
    $sel = "selected";
  }
  echo "<option value = '$vrow[tunnus]' $sel>$vrow[factoringyhtio] - $vrow[nimitys]</option>";
}

echo "</select></td></tr>";

if (isset($ppa) and $ppa != '' and $ppl=='') {
  $ppl = $ppa;
  $kkl = $kka;
  $vvl = $vva;
}

echo "<tr><th>".t("Aloituspäivä")."</th><td><input type='text' name='ppa' value='$ppa' size='3'><td><input type='text' name='kka' value='$kka' size='3'></td><td><input type='text' name='vva' value='$vva' size='5'></td></tr>";
echo "<tr><th>".t("Lopetuspäivä")."</th><td><input type='text' name='ppl' value='$ppl' size='3'><td><input type='text' name='kkl' value='$kkl' size='3'></td><td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
echo "<tr><td class='back' colspan='4'><input type='submit' name='submit' value='Stemmuuta'></td></tr>";
echo "</form>";
echo "</table>";

if (isset($submit)) {

  $query = "SELECT group_concat(tunnus) joukko
            FROM maksuehto
            WHERE yhtio='$kukarow[yhtio]'
            AND factoring_id='$sopimus'";
  $maksuehtores = pupe_query($query);
  $maksuehtorow = mysql_fetch_assoc($maksuehtores);

  echo "<table>";

  $query = "SELECT SUM(if(summa > 0, summa, 0)) possumma, SUM(if(summa < 0, summa, 0)) negsumma
            FROM lasku
            WHERE yhtio='$kukarow[yhtio]'
            AND tila='U'
            AND alatila='X'
            AND tapvm      >= '$vva-$kka-$ppa'
            AND tapvm      <= '$vvl-$kkl-$ppl'
             AND maksuehto in ($maksuehtorow[joukko])";
  $laskures = pupe_query($query);
  $laskurow = mysql_fetch_assoc($laskures);

  echo "<tr><th>", t("Lähteneet veloituslaskut"), "</th><td>$laskurow[possumma]</td></tr>";
  $lahteneet=$laskurow['possumma'];
  echo "<tr><th>", t("Lähteneet hyvityslaskut"), "</th><td>$laskurow[negsumma]</td></tr>";
  $lahteneet+=$laskurow['negsumma'];
  echo "<tr><th>", t("Lähteneet yhteensä"), "</th><td>$lahteneet</td></tr>";

  $query = "SELECT tiliointi.tilino, SUM(tiliointi.summa) summa, sum(if(lasku.tapvm=tiliointi.tapvm,tiliointi.summa,0)) summa2
            FROM tiliointi, lasku
            WHERE tiliointi.yhtio='$kukarow[yhtio]'
            AND tiliointi.tapvm  >= '$vva-$kka-$ppa'
            AND tiliointi.tapvm  <= '$vvl-$kkl-$ppl'
            AND tiliointi.tilino in ('$yhtiorow[factoringsaamiset]', '$yhtiorow[myynninkassaale]', '$yhtiorow[luottotappiot]', $yhtiorow[alv])
            AND korjattu         = ''
            AND lasku.tila='U'
            AND lasku.alatila='X'
            AND lasku.tunnus     = tiliointi.ltunnus
            AND lasku.yhtio=tiliointi.yhtio
            AND lasku.maksuehto  in ($maksuehtorow[joukko])
            GROUP BY 1";
  $laskures = pupe_query($query);
  $suoritukset = -$lahteneet;

  while ($laskurow = mysql_fetch_assoc($laskures)) {

    if ($laskurow['tilino'] == $yhtiorow['factoringsaamiset']) {
      $kplahteneet += $laskurow['summa2'];
      $suoritukset += $laskurow['summa'];
    }

    if ($laskurow['tilino'] == $yhtiorow['factoringsaamiset'])
      $factoringsaamiset = $laskurow['summa'];
    elseif ($laskurow['tilino'] == $yhtiorow['myynninkassaale'])
      $kateisalennukset = $laskurow['summa'];
    elseif ($laskurow['tilino'] == $yhtiorow['alv'])
      $suoritustenalv = round($laskurow['summa'] - $laskurow['summa2'], 2);
    else
      $luottotappiot = $laskurow['summa'];
  }

  echo "<tr><th>", t("Suoritukset"), "</th><td>", $factoringsaamiset-$lahteneet-$kateisalennukset-$suoritustenalv, "</td></tr>";
  echo "<tr><th>", t("Käteisalennukset"), "</th><td>$kateisalennukset</td></tr>";
  echo "<tr><th>", t("Alv"), "</th><td>$suoritustenalv</td></tr>";
  echo "<tr><th>", t("Luottotappiot"), "</th><td>$luottotappiot</td></tr>";
  echo "<tr><th>", t("Kohdistetut yhteensä"), "</th><td>$suoritukset</td></tr>";


  $query = "SELECT *
            FROM factoring
            WHERE yhtio = '$kukarow[yhtio]'
            AND tunnus  = '$sopimus'";
  $res = pupe_query($query);

  if (mysql_num_rows($res) == 1) {
    $factoringrow = mysql_fetch_assoc($res);

    $query = "SELECT *
              FROM yriti
              WHERE yhtio = '$kukarow[yhtio]'
              AND tilino  = '$factoringrow[pankki_tili]'";
    $res = pupe_query($query);

    $yritirow = mysql_fetch_assoc($res);

    if (mysql_num_rows($res) == 1) {
      $query = "SELECT SUM(tiliointi.summa) summa
                FROM tiliointi,lasku
                WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
                AND tiliointi.tapvm    >= '$vva-$kka-$ppa'
                AND tiliointi.tapvm    <= '$vvl-$kkl-$ppl'
                AND tiliointi.tilino   = $yritirow[oletus_rahatili]
                AND tiliointi.korjattu > '$vvl-$kkl-$ppl'
                AND lasku.tila         = 'X'
                AND lasku.alatila      = ''
                AND lasku.tunnus       = tiliointi.ltunnus
                AND lasku.yhtio        = tiliointi.yhtio";
      $laskures = pupe_query($query);
      $laskurow = mysql_fetch_assoc($laskures);

      $laskurow['summa'] = $laskurow['summa'] * -1;

      echo "<tr><th>", t("Kohdistamattomat suoritukset rahatilillä"), "</th><td>$laskurow[summa]</td></tr>";

      $suoritukset += $laskurow['summa'];
    }
    else echo "<font class='error'>".t('Factoringpankkitili ei löydy')."</font>$factoringrow[pankki_tili]<br>";
  }
  else echo "<font class='error'>".t('Factoringsopimus ei löydy')."</font>$sopimus<br>";

  echo "<tr><th>", t("Suoritukset yhteensä ennen korjauksia"), "</th><td>$suoritukset</td></tr>";

  $erotus = round($kplahteneet - $lahteneet, 2);

  echo "<tr><th>", t("Korjaus ongelmista"), "</th><td>$erotus</td></tr>";

  $suoritukset -= $erotus;

  echo "<tr><th>", t("Suoritukset yhteensä"), "</th><td>$suoritukset</td></tr>";

  if ($kplahteneet!=$lahteneet) {
    $query = "SELECT tiliointi.summa summa, lasku.summa summa2, lasku.nimi, lasku.laskunro
              FROM tiliointi, lasku
              WHERE tiliointi.yhtio  ='$kukarow[yhtio]'
              AND tiliointi.tapvm    >= '$vva-$kka-$ppa'
              AND tiliointi.tapvm    <= '$vvl-$kkl-$ppl'
              AND tiliointi.tilino   = '$yhtiorow[factoringsaamiset]'
              AND tiliointi.korjattu = ''
              AND lasku.tila         = 'U'
              AND lasku.alatila      = 'X'
              AND lasku.tunnus       = tiliointi.ltunnus
              AND lasku.yhtio        = tiliointi.yhtio
              AND lasku.tapvm        = tiliointi.tapvm
              AND lasku.summa - tiliointi.summa != 0";
    $laskures = pupe_query($query);

    while ($laskurow = mysql_fetch_assoc($laskures)) {
      echo "<tr><th>", t("Laskussa ongelma"), "</th><td>$laskurow[laskunro] $laskurow[summa] $laskurow[summa2] $laskurow[nimi]</td></tr>";
    }
  }

  echo "</table>";
}

require "inc/footer.inc";
