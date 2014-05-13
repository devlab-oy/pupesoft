<?php

  require("inc/parametrit.inc");


  echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox) {

      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi = toggleBox.name;

      for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,5) == nimi) {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    //-->
    </script>";

  echo "<font class='head'>".t("Alennusten yll‰pito")."</font><hr>";

  echo "<form name=asiakas method=post>";
  echo "<table>
      <tr>
        <th>".t("Asiakas")."</th>
        <td><input type='text' name='ytunnus'></td>
        <td><input type='submit' value='".t("Hae")."'></td>
      </tr>
  </table><br>";
  echo "</form>";

  if ($ytunnus != '' and $asiakasid == 0) {
    require ("inc/asiakashaku.inc");
    if ($asiakasid == 0) exit;
  }

  if ($asiakasid != "") {

    # Oletustyyppi
    if($tyyppi == "") $tyyppi = "ytunnus";

    # Haetaan asiakas
    $asiakas_query = "  SELECT *
              FROM asiakas
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus = '$asiakasid'";
    $asiakas_result = pupe_query($asiakas_query);

    if (mysql_num_rows($asiakas_result) != 1) {
      echo "<font class='error'>".t("Asiakasta ei lˆytynyt").".</font>";
      exit;
    }
    $asiakas = mysql_fetch_assoc($asiakas_result);

    # Asiakkaan tiedot
    echo "<table>
        <tr>
          <th>".t("Ytunnus")."</th>
          <th>".t("Nimi")."</th>
          <th>".t("Osoite")."</th>
          <th>".t("Postino")."</th>
          <th>".t("Postitp")."</th>
        </tr>";
    echo "<tr>
        <td>{$asiakas["ytunnus"]}<br>{$asiakas["ovttunnus"]}</td>
        <td>{$asiakas["nimi"]}<br>{$asiakas["toim_nimi"]}</td>
        <td>{$asiakas["osoite"]}<br>{$asiakas["toim_osoite"]}</td>
        <td>{$asiakas["postino"]}<br>{$asiakas["toim_postino"]}</td>
        <td>{$asiakas["postitp"]}<br>{$asiakas["toim_postitp"]}</td>
      </tr>
      </table>";

    echo "<br>";
    echo "<table><tr><th>".t("Valitse alennusten tallennustapa")."</th><td>";

    echo "<form method='post' name='asiakasalennus'>";
    echo "<select name='tyyppi'onchange='submit()'>";

    $sel = ($tyyppi == "tunnus") ? "selected" : "";
    echo "<option value='ytunnus'>".t("Asiakasalennukset tallennetaan asiakkaan ytunnuksen mukaan")."</option>";
    echo "<option value='tunnus' $sel>".t("Asiakasalennukset tallennetaan vain valitulle asiakkalle")."</option>";

    echo "</select>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "</form>";

    echo "</td></tr></table>";

    if ($tee == "") $tee = "alennustaulukko";
  }

  if ($oikeurow["paivitys"] != "1" and ($tee == 'uusi' or $tee == 'lisaa')) {
    echo "<br><font class='error'>".t("Sinulla ei ole p‰ivitysoikeutta")."!</font><br/>";
    $tee = "alennustaulukko";
  }

  # Uusi lomakkeen kenttien tarkistus
  if ($tee == 'uusi') {
    list($yyyy, $mm, $dd) = explode('-', $uusi_alkupvm);
    if ($uusi_alkupvm!= "" and $uusi_alkupvm != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
      echo "<br><font class='error'>".t("Virheellinen alkupvm")."! $uusi_alkupvm</font><br/>";
      $tee = "alennustaulukko";
    }
    list($yyyy, $mm, $dd) = explode('-', $uusi_loppupvm);
    if ($uusi_loppupvm!= "" and $uusi_loppupvm != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
      echo "<br><font class='error'>".t("Virheellinen loppupvm")."! $uusi_loppupvm</font><br/>";
      $tee = "alennustaulukko";
    }
    if(empty($uusi_alkuryhma) or empty($uusi_loppuryhma)) {
      echo "<br><font class='error'>".t("Alkuryhm‰ tai loppuryhm‰")." puuttuu</font><br/>";
      $tee = "alennustaulukko";
    }
    if (!is_numeric($uusi_alennus) and $uusi_alennus != "") {
      echo "<br><font class='error'>".t("Alennuksen t‰ytyy olla numero").".</font><br/>";
      $tee = "alennustaulukko";
    }
    if ($uusi_alennus < 0 or $uusi_alennus > 100) {
      echo "<br><font class='error'>".t("Alennuksen t‰ytyy olla v‰lill‰ 0-100").".</font><br/>";
      $tee = "alennustaulukko";
    }
    if (!is_numeric($uusi_minkpl) and $uusi_minkpl != "") {
      echo "<br><font class='error'>".t("Minkpl t‰ytyy olla numero").".</font><br/>";
      $tee = "alennustaulukko";
    }
  }

  # P‰ivit‰ kenttien tarkistus
  if ($tee == 'lisaa') {
    foreach($alkuryhma as $i => $ryhma) {
      list($yyyy, $mm, $dd) = explode('-', $alkupvm[$i]);
      if ($alkupvm[$i] != "" and $alkupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
        echo "<font class='error'>".t("Virheellinen alkupvm")."! $alkupvm[$i]</font><br/>";
        $tee = "alennustaulukko";
      }
      list($yyyy, $mm, $dd) = explode('-', $loppupvm[$i]);
      if ($loppupvm[$i] != "" and $loppupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
        echo "<font class='error'>".t("Virheellinen loppupvm")."! $loppupvm[$i]</font><br/>";
        $tee = "alennustaulukko";
      }
      if (empty($alkuryhma[$i]) or empty($loppuryhma[$i])) {
        echo "<font class='error'>".t("Alkuryhm‰ tai loppuryhm‰ puuttuu")."</font><br/>";
        $tee = "alennustaulukko";
      }
      if (!is_numeric($alennus[$i]) and $alennus[$i] != "") {
        echo "<font class='error'>".t("Alennuksen t‰ytyy olla numero").".</font><br/>";
        $tee = "alennustaulukko";
      }
      if ($alennus[$i] < 0 or $alennus[$i] > 100) {
        echo "<font class='error'>".t("Alennuksen t‰ytyy olla v‰lill‰ 0-100").".</font><br/>";
        $tee = "alennustaulukko";
      }
      if (!is_numeric($minkpl[$i]) and $minkpl[$i] != "") {
        echo "<font class='error'>".t("Minkpl t‰ytyy olla numero").".</font><br/>";
        $tee = "alennustaulukko";
      }
    }
  }

  # P‰ivitet‰‰n ryhm‰t
  if ($tee == 'lisaa' or $tee == 'uusi') {

    if ($tee == 'uusi') {
      # Kysely toimii arraylla, joten uusi_muuttujat muutetaan
      $alkuryhma[] = $uusi_alkuryhma;
      $loppuryhma[] = $uusi_loppuryhma;
      $alennus[] = $uusi_alennus;
      $alennuslaji[] = $uusi_alennuslaji;
      $minkpl[] = $uusi_minkpl;
      $monikerta[] = $uusi_monikerta;
      $alkupvm[] = $uusi_alkupvm;
      $loppupvm[] = $uusi_loppupvm;
    }

    # Query lis‰ ytunnuksen tai asiakasid:n mukaan
    $query_lisa = ($tyyppi == 'ytunnus') ? "ytunnus = '$ytunnus'" : "asiakas = '$asiakasid'";

    # Loopataan l‰pi ryhm‰v‰lit (rivit)
    for ($i = 0; $i < count($alkuryhma); $i++) {

      # Haetaan ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
      $ryhmat = hae_ryhmat($alkuryhma[$i], $loppuryhma[$i]);

      # Jos ryhmi‰ ei lˆydy
      if (count($ryhmat) == 0) {
        echo "<br><font class='error'>".t("Yht‰‰n ryhm‰‰ ei lˆytynyt v‰lilt‰")." $alkuryhma[$i] - $loppuryhma[$i]!</font><br>";
        $tee = "alennustaulukko";
        break;
      }

      # Jos poistettava, loopataan ryhm‰v‰li ja poistetaan.
      if ($poista[$i] != "") {
        foreach ($ryhmat as $poistettava_ryhma) {
          if ($alkupvm[$i] == "") {
            $alkupvm[$i] = "0000-00-00";
          }
          if ($loppupvm[$i] == "") {
            $loppupvm[$i] = "0000-00-00";
          }
          $poista_query = "   DELETE FROM asiakasalennus
                    WHERE yhtio    = '{$kukarow['yhtio']}'
                    AND $query_lisa
                    AND ryhma    = '$poistettava_ryhma'
                    AND alennus     = '{$alennus[$i]}'
                    AND alennuslaji = '{$alennuslaji[$i]}'
                    AND minkpl    = '{$minkpl[$i]}'
                    AND monikerta  = '{$monikerta[$i]}'
                    AND alkupvm    = '{$alkupvm[$i]}'
                    AND loppupvm  = '{$loppupvm[$i]}'";
          $poista_result = pupe_query($poista_query);
          $poista[$i] = NULL;
        }
      }
      # Jos ei poistettava niin lis‰t‰‰n tai p‰ivitet‰‰n uudet alennukset
      else {

        # Ryhm‰v‰lin tunnukset
        $tunnus = explode(",", $tunnukset[$i]);
        $j = 0;

        foreach ($ryhmat as $ryhma) {
          # P‰ivitet‰‰n jos tunnus on setattu
          if (!empty($tunnus[$j])) {
            $query = "  UPDATE asiakasalennus SET
                  yhtio    = '{$kukarow['yhtio']}',
                  ryhma    = '$ryhma',
                  $query_lisa,
                  alennus    = '{$alennus[$i]}',
                  alennuslaji = '{$alennuslaji[$i]}',
                  minkpl    = '{$minkpl[$i]}',
                  monikerta   = '{$monikerta[$i]}',
                  alkupvm    = '{$alkupvm[$i]}',
                  loppupvm  = '{$loppupvm[$i]}',
                  muutospvm   = now(),
                  muuttaja  = '{$kukarow['kuka']}'
                  WHERE tunnus = '{$tunnus[$j]}'";
            $j++;
            $update_query = pupe_query($query);
          }
          else {
            $query = "  INSERT INTO asiakasalennus SET
                  yhtio    = '{$kukarow['yhtio']}',
                  ryhma    = '$ryhma',
                  $query_lisa,
                  alennus    = '{$alennus[$i]}',
                  alennuslaji  = '{$alennuslaji[$i]}',
                  minkpl    = '{$minkpl[$i]}',
                  monikerta  = '{$monikerta[$i]}',
                  alkupvm    = '{$alkupvm[$i]}',
                  loppupvm  = '{$loppupvm[$i]}',
                  laatija    = '{$kukarow['kuka']}',
                  luontiaika  = now(),
                  muutospvm  = now(),
                  muuttaja  = '{$kukarow['kuka']}'";
            $lisaa_result = pupe_query($query);
          }
        }
      }
    }

    // Nollataan POST muuttujat, jotta formit tulostuu oikein
    $alkuryhma = NULL;
    $loppuryhma = NULL;
    $alennus = NULL;
    $alennuslaji = NULL;
    $minkpl = NULL;
    $monikerta = NULL;
    $alkupvm = NULL;
    $loppupvm = NULL;
    $uusi_alkuryhma = NULL;
    $uusi_loppuryhma = NULL;
    $uusi_alennus = NULL;
    $uusi_alennuslaji = NULL;
    $uusi_minkpl = NULL;
    $uusi_monikerta = NULL;
    $uusi_alkupvm = NULL;
    $uusi_loppupvm = NULL;
    $tunnukset = NULL;

    $tee = "alennustaulukko";
  }

  if ($tee == "alennustaulukko") {
    echo "<br/><font class='head'>".t("Alennustaulukko")."</font><hr>";

    echo "<form name='paivita_alennukset' method='post'>";
    echo "<table>
      <tr>
        <th>".t("Alkuryhm‰")."</th>
        <th>".t("Loppuryhm‰")."</th>
        <th>".t("Alennus")."</th>
        <th>".t("Alennuslaji")."</th>
        <th>".t("Minkpl")."</th>
        <th>".t("Monikerta")."</th>
        <th>".t("Alkupvm")." (".t("VVVV-KK-PP").")</th>
        <th>".t("Loppupvm")." (".t("VVVV-KK-PP").")</th>
        <th>".t("Poista")."</th>
      </tr>";

    $query_lisa = ($tyyppi == 'ytunnus') ? "AND asiakasalennus.ytunnus = '$ytunnus'" : "AND asiakasalennus.asiakas = '$asiakasid'";

    $query = "  SELECT asiakasalennus.asiakas,
          perusalennus.ryhma,
          asiakasalennus.alennuslaji,
          asiakasalennus.minkpl,
          asiakasalennus.monikerta,
          asiakasalennus.alkupvm,
          asiakasalennus.loppupvm,
          asiakasalennus.tunnus,
          ifnull(asiakasalennus.alennus, perusalennus.alennus) alennus
          FROM perusalennus
          LEFT JOIN asiakasalennus ON (perusalennus.ryhma = asiakasalennus.ryhma
            AND perusalennus.yhtio = asiakasalennus.yhtio
            $query_lisa)
          WHERE perusalennus.yhtio = '{$kukarow["yhtio"]}'
          ORDER BY perusalennus.ryhma";
    $result = pupe_query($query);

    $edellinen_rivi = "";
    $ensimmainen_ryhma = "";

    $i = 0;

    do {
      $row = mysql_fetch_array($result);

      if ($ensimmainen_ryhma == "") {
        $ensimmainen_ryhma = $row['ryhma'];
      }

      // Alennustaulukon rivit
      if ($edellinen_rivi != "" and ($edellinen_rivi['alennus'] != $row['alennus']
        or $edellinen_rivi['alennuslaji'] != $row['alennuslaji']
        or $edellinen_rivi['minkpl'] != $row['minkpl']
        or $edellinen_rivi['monikerta'] != $row['monikerta']
        or $edellinen_rivi['alkupvm'] != $row['alkupvm']
        or $edellinen_rivi['loppupvm'] != $row['loppupvm'])) {

        // Kenttien tarkistus
        if ($alennus[$i] != "") {
          $edellinen_rivi['alennus'] = ($edellinen_rivi['alennus'] == $alennus[$i]) ? $edellinen_rivi['alennus'] : $alennus[$i];
        }

        if ($alennuslaji[$i] != "") {
          $edellinen_rivi['alennuslaji'] = ($edellinen_rivi['alennuslaji'] == $alennuslaji[$i]) ? $edellinen_rivi['alennuslaji'] : $alennuslaji[$i];
        }

        if ($minkpl[$i] != "" or $edellinen_rivi['minkpl'] == 0) {
          $edellinen_rivi['minkpl'] = ($edellinen_rivi['minkpl'] == $minkpl[$i]) ? $edellinen_rivi['minkpl'] : $minkpl[$i];
          $edellinen_rivi['minkpl'] = ($edellinen_rivi['minkpl'] == '0') ? "" : $edellinen_rivi['minkpl'];
        }

        if ($monikerta[$i] != "") {
          $edellinen_rivi['monikerta'] = ($edellinen_rivi['monikerta'] == $monikerta[$i]) ? $edellinen_rivi['monikerta'] : $monikerta[$i];
        }

        if ($alkupvm[$i] != "" or $loppupvm[$i] != "") {
          $edellinen_rivi['alkupvm'] = ($edellinen_rivi['alkupvm'] == $alkupvm[$i]) ? $edellinen_rivi['alkupvm'] : $alkupvm[$i];
          $edellinen_rivi['loppupvm'] = ($edellinen_rivi['loppupvm'] == $loppupvm[$i]) ? $edellinen_rivi['loppupvm'] : $loppupvm[$i];
        }

        # Jos p‰iv‰m‰‰r‰ on 0000-00-00
        $edellinen_rivi['alkupvm'] = ($edellinen_rivi['alkupvm'] == '0000-00-00') ? "" : $edellinen_rivi['alkupvm'];
        $edellinen_rivi['loppupvm'] = ($edellinen_rivi['loppupvm'] == '0000-00-00') ? "" : $edellinen_rivi['loppupvm'];

        // Rivien tulostus
        echo "<tr>";
        echo "<td><input type='hidden' name='alkuryhma[$i]' value=$ensimmainen_ryhma>".$ensimmainen_ryhma."</td>";
        echo "<td><input type='hidden' name='loppuryhma[$i]' value=$edellinen_rivi[ryhma]>".$edellinen_rivi['ryhma']."</td>";
        echo "<td><input type='text' name='alennus[$i]' value=".$edellinen_rivi['alennus']."></td>";

        // Alennuslaji dropdown
        $sel = array_fill_keys(array($edellinen_rivi['alennuslaji']), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
        $ulos = "<td><select name='alennuslaji[$i]'>";
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
          $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
        }
        echo $ulos .= "</select></td>\n";

        echo "<td><input type='text' name='minkpl[$i]' value=".$edellinen_rivi['minkpl']."></td>";
        echo "<td><select name='monikerta[$i]' value=".$edellinen_rivi['monikerta'].">";

        // Monikerta dropdown
        if(empty($edellinen_rivi['monikerta'])) {
          echo "<option value='' selected>".t("Ei")."</option>";
          echo "<option value='K'>".t("Kyll‰")."</option>";
        }
        else {
          echo "<option value='' >".t("Ei")."</option>";
          echo "<option value='K' selected>".t("Kyll‰")."</option>";
        }
        echo "</td>";

        echo "<td><input type='text' name='alkupvm[$i]' value=".$edellinen_rivi['alkupvm']."></td>";
        echo "<td><input type='text' name='loppupvm[$i]' value=".$edellinen_rivi['loppupvm']."></td>";

        $checked = (isset($poista[$i])) ? "checked" : "";
        echo "<td><input type='checkbox' name='poista[$i]' $checked></td>";
        echo "<input type='hidden' name='tunnukset[$i]' value=".implode(",", $tunnukset).">";
        echo "</tr>";

        $ensimmainen_ryhma = $row['ryhma'];
        $i++;
        $tunnukset = NULL;
      }
      $tunnukset[] = $row['tunnus'];
      $edellinen_rivi = $row;
    } while ($row);

    echo "<tr>";
    echo "<td colspan='7' class='back'><input type='submit' value='".t("P‰ivit‰")."'></td>";
    echo "<td colspan='2' class='back' style='text-align:right;'>".t("Ruksaa kaikki")." <input type='checkbox' name='poist' onclick='toggleAll(this);'></td>";
    echo "</tr>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
    echo "<input type='hidden' name='tee' value='lisaa'>";
    echo "</table>";
    echo "</form>";

    # Uuden ryhm‰n luominen
    echo "<br/><font class='head'>".t("Uusi")."</font><hr>";
    echo "<form name='uusi_alennus' method='post'>";
    echo "<table><tr>
        <th>".t("Alkuryhm‰")."</th>
        <th>".t("Loppuryhm‰")."</th>
        <th>".t("Alennus")."</th>
        <th>".t("Alennuslaji")."</th>
        <th>".t("Minkpl")."</th>
        <th>".t("Monikerta")."</th>
        <th>".t("Alkupvm")." (".t("VVVV-KK-PP").")</th>
        <th>".t("Loppupvm")." (".t("VVVV-KK-PP").")</th>
      </tr>
      <tr>
      <td><input type='text' name='uusi_alkuryhma' value=$uusi_alkuryhma></td>
      <td><input type='text' name='uusi_loppuryhma' value=$uusi_loppuryhma></td>
      <td><input type='text' name='uusi_alennus' value=$uusi_alennus></td>";

    $sel = array_fill_keys(array($uusi_alennuslaji), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
    $ulos = "<td><select name='uusi_alennuslaji'>";
    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
      $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
    }
    echo $ulos;

    echo "<td><input type='text' name='uusi_minkpl' value=$uusi_minkpl></td>";

    $sel = ($uusi_monikerta != '') ? 'selected' : '';
    echo "<td><select name='uusi_monikerta'>
          <option value=''>".t("Ei")."</option>
          <option value='K' $sel>".t("Kyll‰")."</option>
      </td>
      <td><input type='text' name='uusi_alkupvm' value=$uusi_alkupvm></td>
      <td><input type='text' name='uusi_loppupvm' value=$uusi_loppupvm></td>
      </tr>
      <tr><td colspan=8 class='back'><input type='submit' value='".t("Luo")."'></td></tr>
      </table>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
    echo "<input type='hidden' name='tee' value='uusi'>";
    echo "</form>";
  }

  # Haetaan ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
  function hae_ryhmat($alkuryhma, $loppuryhma) {
    global $kukarow;

    $ryhmat_query = "  SELECT distinct(ryhma)
              FROM perusalennus
              WHERE yhtio = '{$kukarow["yhtio"]}'
              AND ryhma BETWEEN '$alkuryhma' AND '$loppuryhma'";
    $ryhmat_result = pupe_query($ryhmat_query);

    while($row = mysql_fetch_array($ryhmat_result)) {
      $ryhmat[] = $row['ryhma'];
    }

    return $ryhmat;
  }

  include("inc/footer.inc");
