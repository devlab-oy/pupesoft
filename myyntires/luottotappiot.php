<?php

// DataTables päälle
$pupe_DataTables = "luottotappiot";

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Myyntisaamisten kirjaus luottotappioiksi")."</font><hr>";

if ($tila == 'K') {
  $tpk = (int) $tpk;
  $tpp = (int) $tpp;
  $tpv = (int) $tpv;

  if ($tpv < 1000) $tpv += 2000;

  if (!checkdate($tpk, $tpp, $tpv)) {
    echo "<font class='error'>".t("Virheellinen tapahtumapvm")."</font><br>";
    $tila = 'N';
  }
}

if ($tila == 'K' and is_array($luottotappio)) {

  $laskunrot = implode(",", $luottotappio);

  if ($laskunrot != "") {

    // Haetaan kaikki tiliöinnit paitsi varasto, varastonmuutos ja alv (tiliointi.aputunnus = 0)
    $query = "  SELECT lasku.*, tiliointi.ltunnus, tiliointi.tilino, tiliointi.summa, tiliointi.vero, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti
          FROM lasku
          JOIN tiliointi ON (tiliointi.yhtio = lasku.yhtio AND tiliointi.ltunnus = lasku.tunnus and tiliointi.korjattu = '' and tiliointi.aputunnus = 0 AND tiliointi.tilino NOT IN ('$yhtiorow[varasto]', '$yhtiorow[varastonmuutos]', '$yhtiorow[raaka_ainevarasto]', '$yhtiorow[raaka_ainevarastonmuutos]', '$yhtiorow[alv]'))
          JOIN tili ON (tiliointi.yhtio = tili.yhtio and tiliointi.tilino = tili.tilino)
          LEFT JOIN taso ON (tili.yhtio = taso.yhtio and tili.ulkoinen_taso = taso.taso and taso.tyyppi = 'U')          
          WHERE lasku.yhtio    = '$kukarow[yhtio]'
          AND lasku.mapvm      = '0000-00-00'
          AND lasku.tila      = 'U'
          AND lasku.alatila    = 'X'
          AND lasku.liitostunnus  = '$liitostunnus'
          AND lasku.laskunro in ($laskunrot)
          AND (taso.kayttotarkoitus is null or taso.kayttotarkoitus  in ('','M'))
          ORDER BY 1";
    $laskuresult = pupe_query($query);

    while ($lasku = mysql_fetch_assoc($laskuresult)) {

      if ($lasku['tilino'] != $yhtiorow['myyntisaamiset'] and $lasku['tilino'] != $yhtiorow['factoringsaamiset'] and $lasku['tilino'] != $yhtiorow['konsernimyyntisaamiset']) {
        // Hoidetaan alv
        $alv = round($lasku['summa'] * $lasku['vero'] / 100, 2);

        // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($yhtiorow["luottotappiot"], $lasku["kustp"], $lasku["kohde"], $lasku["projekti"]);

        $query = "  INSERT INTO tiliointi SET
              yhtio    = '$kukarow[yhtio]',
              ltunnus    = '$lasku[ltunnus]',
              tilino    = '$yhtiorow[luottotappiot]',
              kustp      = '{$kustp_ins}',
              kohde     = '{$kohde_ins}',
              projekti   = '{$projekti_ins}',
              tapvm     = '$tpv-$tpk-$tpp',
              summa    = $lasku[summa] * -1,
              vero    = '$lasku[vero]',
              selite    = '$lasku[selite]',
              lukko    = '',
              tosite    = '$lasku[tosite]',
              laatija    = '$kukarow[kuka]',
              laadittu  = now()";
        $result = pupe_query($query);
        $isa = mysql_insert_id ($link);

        // Tiliöidään alv
        if ($lasku['vero'] != 0) {

          // jos yhtiön toimipaikka löytyy, otetaan alvtilinumero tämän takaa jos se löytyy
          if ($lasku["yhtio_toimipaikka"] != '' and $yhtiorow["toim_alv"] != '') {
            $alvtilino = $yhtiorow["toim_alv"];
          }
          else {
            $alvtilino = $yhtiorow["alv"];
          }

          $query = "  INSERT INTO tiliointi SET
                yhtio    = '$kukarow[yhtio]',
                ltunnus    = '$lasku[ltunnus]',
                tilino    = '$alvtilino',
                kustp     = 0,
                kohde     = 0,
                projekti   = 0,
                tapvm    = '$tpv-$tpk-$tpp',
                summa    = $alv * -1,
                vero    = 0,
                selite    = '$lasku[selite]',
                lukko    = '1',
                tosite    = '$lasku[tosite]',
                laatija    = '$kukarow[kuka]',
                laadittu  = now(),
                aputunnus  = '$isa'";
          $result = pupe_query($query);
        }
      }
      else {

        // Tarkenteet kopsataan alkuperäiseltä tiliöinniltä, mutta jos alkuperäinen tiliöinti on ilman tarkenteita, niin mennään tilin defaulteilla
        list($kustp_ins, $kohde_ins, $projekti_ins) = kustannuspaikka_kohde_projekti($lasku["tilino"], $lasku["kustp"], $lasku["kohde"], $lasku["projekti"]);

        $query = "  INSERT INTO tiliointi SET
              yhtio     = '$kukarow[yhtio]',
              ltunnus    = '$lasku[ltunnus]',
              tilino    = '$lasku[tilino]',
              kustp      = '{$kustp_ins}',
              kohde     = '{$kohde_ins}',
              projekti   = '{$projekti_ins}',
              tapvm    = '$tpv-$tpk-$tpp',
              summa    = $lasku[summa] * -1,
              vero    = 0,
              selite    = '$lasku[selite]',
              lukko    = '',
              tosite    = '$lasku[tosite]',
              laatija    = '$kukarow[kuka]',
              laadittu  = now()";
        $result = pupe_query($query);
      }

      $query = "UPDATE lasku set mapvm = '$tpv-$tpk-$tpp' where yhtio ='$kukarow[yhtio]' and tunnus = '$lasku[ltunnus]'";
      $result = pupe_query($query);
    }

    echo "<font class='message'>".t("Laskut on tiliöity luottotappioksi")."!</font><br><br>";
    $tila = "";
  }
  else {
    echo "<font class='message'>".t("VIRHE: Et valinnut yhtään laskua")."!</font><br><br>";
    $tila = "N";
  }
}
elseif ($tila == 'K') {
  echo "<font class='message'>".t("VIRHE: Et valinnut yhtään laskua")."!</font><br><br>";
  $tila = "N";
}

if ($tila == 'N') {

  $query = "  SELECT *, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl
        FROM lasku USE INDEX (yhtio_tila_mapvm)
        WHERE mapvm      = '0000-00-00'
        AND tila      = 'U'
        AND alatila      = 'X'
        AND yhtio      = '$kukarow[yhtio]'
        AND liitostunnus  = '$liitostunnus'
        GROUP BY liitostunnus
        ORDER BY ytunnus";
  $result = pupe_query($query);
  $asiakas = mysql_fetch_assoc ($result);

  echo "<table>";

  echo "<tr>";
  echo "<th>".t("ytunnus")."</th>";
  echo "<th>".t("asiakas")."</th>";
  echo "<th>".t("summa")."</th>";
  echo "<th>".t("kpl")."</th>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>$asiakas[ytunnus]</td>";
  echo "<td>$asiakas[asiakas]</td>";
  echo "<td>$asiakas[summa]</td>";
  echo "<td>$asiakas[kpl]</td>";
  echo "</tr>";

  echo "</table>";

  echo "<br><font class='message'>".t("Erittely:")."</font><br>";

  echo "<form method = 'post' name='pvm'>";
  echo "<input type='hidden' name='tila' value='K'>";
  echo "<input type='hidden' name='eraantyneet' value='$eraantyneet'>";
  echo "<input type='hidden' name='liitostunnus' value='$liitostunnus'>";
  echo "<table><tr>";

  $query = "  SELECT laskunro, tapvm, erpcm, summa-saldo_maksettu summa
        FROM lasku
        WHERE mapvm    = '0000-00-00'
        AND tila    = 'U'
        AND alatila    = 'X'
        AND yhtio    = '$kukarow[yhtio]'
        AND liitostunnus = '$liitostunnus'
        ORDER BY 1";
  $result = pupe_query($query);

  echo "<tr>";
  echo "<th>".t("Laskunro")."</th>";
  echo "<th>".t("Tapvm")."</th>";
  echo "<th>".t("Eräpvm")."</th>";
  echo "<th>".t("Summa")."</th>";
  echo "<th>".t("Luottotappio")."</th>";
  echo "</tr>";

  while ($lasku = mysql_fetch_assoc ($result)) {
    echo "<tr>";
    echo "<td>$lasku[laskunro]</td>";
    echo "<td>".tv1dateconv($lasku["tapvm"])."</td>";
    echo "<td>".tv1dateconv($lasku["erpcm"])."</td>";
    echo "<td align='right'>$lasku[summa]</td>";

    $ltchk = "";

    if ($eraantyneet != "" and (int) str_replace("-", "", $lasku['erpcm']) < (int) date("Ymd")) {
      $ltchk = "CHECKED";
    }
    elseif ($eraantyneet == "")  {
      $ltchk = "CHECKED";
    }


    echo "<td align='center'><input type='checkbox' name='luottotappio[]' value='$lasku[laskunro]' $ltchk></td>";
    echo "</tr>";
  }

  echo "</table><br>";

  if (!isset($tpk)) $tpk = date("m");
  if (!isset($tpv)) $tpv = date("Y");
  if (!isset($tpp)) $tpp = date("d");

  echo "<table>";
  echo "<tr>";
  echo "<th colspan='2'>".t("Kirjaa luottotappioksi")."</th>";
  echo "</tr><tr>";
  echo "<td>".t("Päivämäärä")." ".t("pp-kk-vvvv")."</td>";
  echo "<td>
      <input type='text' name='tpp' maxlength='2' size='2' value='$tpp'>
      <input type='text' name='tpk' maxlength='2' size='2' value='$tpk'>
      <input type='text' name='tpv' maxlength='4' size='5' value='$tpv'></td>";
  echo "<td class='back'><input type='submit' value='".t("Luottotappio")."'></td>";
  echo "</tr>";
  echo "</table>";

  echo "</form>";

  $formi  ='pvm';
  $kentta  ='tpp';
}

if ($tila == "") {

  pupe_DataTables(array(array($pupe_DataTables, 5, 6)));

  $lisa = "";
  $erachk = "";

  if ($eraantyneet != "") {
    $lisa = " and erpcm < curdate() ";
    $erachk = "SELECTED";
  }

  $query = "  SELECT *, concat_ws(' ', nimi, nimitark, '<br>', osoite, '<br>', postino, postitp) asiakas, sum(summa-saldo_maksettu) summa, count(*) kpl, group_concat(distinct laskunro SEPARATOR '<br>') laskut
        FROM lasku USE INDEX (yhtio_tila_mapvm)
        WHERE mapvm    = '0000-00-00'
        AND tila    = 'U'
        AND alatila    = 'X'
        AND yhtio    = '$kukarow[yhtio]'
        AND liitostunnus != 0
        $lisa
        GROUP BY liitostunnus
        ORDER BY ytunnus";
  $result = pupe_query($query);

  echo "<form method = 'post'>";
  echo "<table>";
  echo "<tr><th>".t("Rajaus")."</th>";

  echo "<td><select name='eraantyneet'>
      <option value=''>".t("Näytä kaikki laskut")."</option>
      <option value='E' $erachk>".t("Näytä vain erääntyneet laskut")."</option>
      </select></td>
      <td class='back'><input type='submit' value='".t("Aja")."'></td>";
  echo "</table>";
  echo "</form><br>";

  echo "<table class='display dataTable' id='$pupe_DataTables'>";

  echo "<thead>
      <tr>
      <th>".t("ytunnus")."</th>
      <th>".t("asiakas")."</th>
      <th>".t("summa")."</th>
      <th>".t("kpl")."</th>
      <th>".t("laskut")."</th>
      <th class='back'></th>
      </tr>
      <tr>
      <td><input type='text' class='search_field' name='search_ytunnus'></td>
      <td><input type='text' class='search_field' name='search_asiakas'></td>
      <td><input type='text' class='search_field' name='search_summa'></td>
      <td><input type='text' class='search_field' name='search_kpl'></td>
      <td><input type='text' class='search_field' name='search_laskut'></td>
      <td class='back'></td>
      </tr>
    </thead>";

  echo "<tbody>";

  while ($asiakas = mysql_fetch_assoc ($result)) {

    echo "<tr class='aktiivi'>";
    echo "<td>$asiakas[ytunnus]</td>";
    echo "<td>$asiakas[asiakas]</td>";
    echo "<td align='right'>$asiakas[summa]</td>";
    echo "<td align='right'>$asiakas[kpl]</td>";
    echo "<td align='right'>$asiakas[laskut]</td>";

    echo "<td class='back'>
        <form method = 'post'>
           <input type='hidden' name='tila' value='N'>
        <input type='hidden' name='eraantyneet' value='$eraantyneet'>
           <input type='hidden' name='liitostunnus' value='$asiakas[liitostunnus]'>
           <input type='submit' value='".t("Luottotappio")."'>
        </form>
        </td>";
    echo "</tr>";
  }

  echo "</tbody>";
  echo "</table>";
}

require ("inc/footer.inc");
