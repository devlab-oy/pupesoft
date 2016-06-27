<?php

require "../inc/parametrit.inc";

echo "<font class='head'>".t("Muuta factorointia")."</font><hr>";

if (isset($maksuehto) and isset($tunnus)) {

  // tutkaillaan maksuehtoa
  $query = "SELECT *
            from maksuehto
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$maksuehto'
            and factoring_id is not null";

  if ($laji == 'pois') {
    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio = '$kukarow[yhtio]'
              and tunnus  = '$maksuehto'
              and factoring_id is null";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Maksuehto katosi")."!</font><br><br>";
    unset($laskuno);
    unset($maksuehto);
  }
  else {
    $mehtorow = mysql_fetch_assoc($result);
  }

  // tutkaillaan laskua
  $query = "SELECT *
            from lasku
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'
            and mapvm   = '0000-00-00'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    echo "<font class='error'>".t("Lasku katosi")."!</font> ($tunnus)<br><br>";
    unset($laskuno);
    unset($tunnus);
  }
  else {
    $laskurow = mysql_fetch_assoc($result);
  }

  // haetaan asiakkaan tiedot (esim konserniyhtiö)
  $query = "SELECT konserniyhtio
            FROM asiakas
            WHERE yhtio = '$kukarow[yhtio]'
            and ytunnus = '$laskurow[ytunnus]'";
  $konsres = pupe_query($query);
  $konsrow = mysql_fetch_assoc($konsres);
}

if (isset($maksuehto) and isset($tunnus)) {

  // korjaillaan eräpäivät ja kassa-alet
  if ($mehtorow['abs_pvm'] === null) {
    $erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[rel_pvm] day)";
  }
  else {
    $erapvm = "'$mehtorow[abs_pvm]'";
  }

  if ($mehtorow['kassa_abspvm'] !== null or $mehtorow["kassa_relpvm"] > 0) {
    if ($mehtorow['kassa_abspvm'] === null) {
      $kassa_erapvm = "adddate('$laskurow[tapvm]', interval $mehtorow[kassa_relpvm] day)";
    }
    else {
      $kassa_erapvm = "'$mehtorow[kassa_abspvm]'";
    }
    $kassa_loppusumma = round($laskurow['summa']*$mehtorow['kassa_alepros']/100, 2);
  }
  else {
    $kassa_erapvm     = "''";
    $kassa_loppusumma = "";
  }

  // päivitetään lasku
  $query = "UPDATE lasku set
            maksuehto   = '$maksuehto',
            erpcm       = $erapvm,
            kapvm       = $kassa_erapvm,
            kasumma     = '$kassa_loppusumma'
            where yhtio = '$kukarow[yhtio]'
            and tunnus  = '$tunnus'";
  $result = pupe_query($query);

  if (mysql_affected_rows() > 0) {
    echo "<font class='message'>".t("Muutettin laskun")." $laskurow[laskunro] ".t("maksuehdoksi")." ".t_tunnus_avainsanat($mehtorow, "teksti", "MAKSUEHTOKV")."</font><br>";
  }
  else {
    echo "<font class='error'>".t("Laskua")." $laskurow[laskunro] ".t("ei pystytty muuttamaan")."!</font><br>";
  }

  if (isset($mehtorow["factoring_id"])) {
    $myysaatili  = $yhtiorow['factoringsaamiset'];
  }
  elseif ($konsrow["konserniyhtio"] != "") {
    $myysaatili  = $yhtiorow['konsernimyyntisaamiset'];
  }
  else {
    $myysaatili  = $yhtiorow['myyntisaamiset'];
  }

  // tehdään kirjanpitomuutokset
  if ($laji == 'pois') {
    $query = "UPDATE tiliointi
              SET tilino = '$myysaatili'
              WHERE yhtio = '$kukarow[yhtio]'
              and ltunnus = '$tunnus'
              and tilino  = '$yhtiorow[factoringsaamiset]'
              and tapvm   = '$laskurow[tapvm]'";
  }
  else {

    if ($konsrow["konserniyhtio"] != "") {
      $myysaatili2 = $yhtiorow['konsernimyyntisaamiset'];
    }
    else {
      $myysaatili2 = $yhtiorow['myyntisaamiset'];
    }

    $query = "UPDATE tiliointi
              set tilino = '$myysaatili'
              where yhtio = '$kukarow[yhtio]'
              and ltunnus = '$tunnus'
              and tilino  = '$myysaatili2'
              and tapvm   = '$laskurow[tapvm]'";
  }

  $result = pupe_query($query);

  if (mysql_affected_rows() > 0) {
    echo "<font class='message'>".t("Korjattiin kirjanpitoviennit")." (".mysql_affected_rows()." ".t("kpl").").</font><br>";
  }
  else {
    echo "<font class='error'>".t("Kirjanpitomuutoksia ei osattu tehdä! Korjaa kirjanpito käsin")."!</font><br>";
  }

  unset($laskuno);
}

if (isset($laskuno)) {

  // haetaan lasku. pitää factoroimaton
  $query = "SELECT lasku.*, lasku.tunnus ltunnus, maksuehto.tunnus, maksuehto.teksti
            from lasku
            JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
              and lasku.maksuehto = maksuehto.tunnus
              and maksuehto.factoring_id is null)
            where lasku.yhtio     = '$kukarow[yhtio]'
            and lasku.laskunro    = '$laskuno'
            and lasku.tila        = 'U'
            and lasku.alatila     = 'X'
            and lasku.mapvm       = '0000-00-00'";

  if ($laji == 'pois') {
    $query = "SELECT lasku.*, lasku.tunnus ltunnus, maksuehto.tunnus, maksuehto.teksti
              from lasku
              JOIN maksuehto ON (lasku.yhtio = maksuehto.yhtio
                and lasku.maksuehto = maksuehto.tunnus
                and maksuehto.factoring_id is not null)
              where lasku.yhtio     = '$kukarow[yhtio]'
              and lasku.laskunro    = '$laskuno'
              and lasku.tila        = 'U'
              and lasku.alatila     = 'X'
              and lasku.mapvm       = '0000-00-00'";
  }
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    if ($laji == 'pois')
      echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy factoroitua laskua")."!</font><br><br>";
    else
      echo "<font class='error'>".t("Laskunumerolla")." '$laskuno' ".t("ei löydy normaalia laskua")."!</font><br><br>";
    unset($laskuno);
  }
  else {
    $laskurow = mysql_fetch_assoc($result);

    echo "<form method='post' autocomplete='off'>";
    echo "<input name='tunnus' type='hidden' value='$laskurow[ltunnus]'>";
    echo "<input name='laji' type='hidden' value='$laji'>";
    echo "<table>
      <tr><th>".t("Laskutusosoite")."</th><th>".t("Toimitusosoite")."</th></tr>
      <tr><td>$laskurow[ytunnus]<br> $laskurow[nimi] $laskurow[nimitark]<br> $laskurow[osoite]<br> $laskurow[postino] $laskurow[postitp]</td><td>$laskurow[ytunnus]<br> $laskurow[toim_nimi] $laskurow[toim_nimitark]<br> $laskurow[toim_osoite]<br> $laskurow[toim_postino] $laskurow[toim_postitp]</td></tr>
      <tr><th>".t("Laskunumero")."</th><td>$laskurow[laskunro]</td></tr>
      <tr><th>".t("Laskun summa")."</th><td>$laskurow[summa]</td></tr>
      <tr><th>".t("Laskun summa (veroton)")."</th><td>$laskurow[arvo]</td></tr>
      <tr><th>".t("Maksuehto")."</th><td>".t_tunnus_avainsanat($laskurow, "teksti", "MAKSUEHTOKV")."</td></tr>
      <tr><th>".t("Tapahtumapäivä")."</th><td>$laskurow[tapvm]</td></tr>
      <tr><th>".t("Uusi maksuehto")."</th>
      <td>";

    // haetaan kaikki factoringmaksuehdot
    $query = "SELECT *
              FROM maksuehto
              WHERE yhtio = '$kukarow[yhtio]' and factoring_id is not null
              ORDER BY jarjestys, teksti";

    if ($laji == 'pois') {
      $query = "SELECT *
                FROM maksuehto
                WHERE yhtio = '$kukarow[yhtio]' and factoring_id is null
                ORDER BY jarjestys, teksti";
    }

    $vresult = pupe_query($query);

    echo "<select name='maksuehto'>";

    while ($vrow=mysql_fetch_assoc($vresult)) {
      echo "<option value='$vrow[tunnus]'>".t_tunnus_avainsanat($vrow, "teksti", "MAKSUEHTOKV")."</option>";
    }
    echo "</select>";

    echo "</td></tr></table><br>";

    echo "<input name='subnappi' type='submit' value='".t("Muuta maksuehto")."'>";
    echo "</form>";
  }
}


if (!isset($laskuno)) {
  echo "<form name='eikat' method='post' autocomplete='off'>";
  echo "<table><tr>";
  echo "<td><input type='radio' name='laji' value='paalle' checked> ".t("Lisää factoring")."</td>";
  echo "<td><input type='radio' name='laji' value='pois'> ".t("Poista factoring")."</td></tr>";
  echo "<tr><th>".t("Syötä laskunumero")."</th>";
  echo "<td><input type='text' name='laskuno'></td>";
  echo "<td class='back'><input name='subnappi' type='submit' value='".t("Hae lasku")."'></td>";
  echo "</tr></table>";
  echo "</form>";
}

// kursorinohjausta
$formi = "eikat";
$kentta = "laskuno";

require "inc/footer.inc";
