<?php
  // Päivitetään tiedot tietokantaan
  if ($tee == 'vakuutushakemus' and $tilausnumero > 0 and (isset($PAIVIT) or isset($VALMIS))) {
    $query = "  SELECT tunnus
          FROM laskun_lisatiedot
          WHERE yhtio  = '$kukarow[yhtio]'
          and otunnus = '$tilausnumero'";
    $res = mysql_query($query) or pupe_error($query);

    if ($rekisteilmo_laminointi == "") {
      $rekisteilmo_laminointi = "E";
    }
    if ($rekisteilmo_suoramarkkinointi == "") {
      $rekisteilmo_suoramarkkinointi = "E";
    }

    if (mysql_num_rows($res) > 0) {
      $query = " UPDATE laskun_lisatiedot ";
      $postq = " WHERE yhtio  = '$kukarow[yhtio]' and otunnus = '$tilausnumero'";
    }
    else {
      $query = "INSERT INTO laskun_lisatiedot ";
      $postq = "";
    }

    $query  .= "  SET vakuutushak_vakuutusyhtio       = '$vakuutushak_vakuutusyhtio',
            vakuutushak_alkamispaiva         = '$vva-$kka-$ppa',
            vakuutushak_kaskolaji           = '$vakuutushak_kaskolaji',
            vakuutushak_maksuerat           = '$vakuutushak_maksuerat',
            vakuutushak_perusomavastuu         = '$vakuutushak_perusomavastuu',
            vakuutushak_runko_takila_purjeet     = '$vakuutushak_runko_takila_purjeet',
            vakuutushak_moottori           = '$vakuutushak_moottori',
            vakuutushak_varusteet           = '$vakuutushak_varusteet',
            vakuutushak_yhteensa           = '$vakuutushak_yhteensa',
            rekisteilmo_rekisterinumero       = '$rekisteilmo_rekisterinumero',
            rekisteilmo_paakayttokunta         = '$rekisteilmo_paakayttokunta',
            rekisteilmo_kieli             = '$rekisteilmo_kieli',
            rekisteilmo_tyyppi             = '$rekisteilmo_tyyppi',
            rekisteilmo_omistajienlkm         = '$rekisteilmo_omistajienlkm',
            rekisteilmo_omistajankotikunta       = '$rekisteilmo_omistajankotikunta',
            rekisteilmo_lisatietoja         = '$rekisteilmo_lisatietoja',
            rekisteilmo_laminointi           = '$rekisteilmo_laminointi',
            rekisteilmo_suoramarkkinointi       = '$rekisteilmo_suoramarkkinointi',
            rekisteilmo_veneen_nimi          = '$rekisteilmo_veneen_nimi',
            rekisteilmo_omistaja          = '$rekisteilmo_omistaja',
            rahlaskelma_hetu_tarkistus        = '$rahlaskelma_hetu_tarkistus',
            rahlaskelma_hetu_tarkastaja        = '$rahlaskelma_hetu_tarkastaja',
            rahlaskelma_hetu_asiakirjanro      = '$rahlaskelma_hetu_asiakirjanro',
            rahlaskelma_hetu_asiakirjamyontaja    = '$rahlaskelma_hetu_asiakirjamyontaja',
            rahlaskelma_hetu_kolm_tarkistus      = '$rahlaskelma_hetu_kolm_tarkistus',
            rahlaskelma_hetu_kolm_asiakirjanro    = '$rahlaskelma_hetu_kolm_asiakirjanro',
            rahlaskelma_hetu_kolm_asiakirjamyontaja  = '$rahlaskelma_hetu_kolm_asiakirjamyontaja',
            rahlaskelma_hetu_kolm_tarkastaja    = '$rahlaskelma_hetu_kolm_tarkastaja',
            rahlaskelma_takuukirja          = '$rahlaskelma_takuukirja',
            rahlaskelma_huoltokirja          = '$rahlaskelma_huoltokirja',
            rahlaskelma_kayttoohjeet        = '$rahlaskelma_kayttoohjeet',
            rahlaskelma_opastus            = '$rahlaskelma_opastus',
            rahlaskelma_kuntotestitodistus      = '$rahlaskelma_kuntotestitodistus',
            rahlaskelma_kayttotarkoitus        = '$rahlaskelma_kayttotarkoitus'";

    $res = mysql_query($query.$postq) or pupe_error($query.$postq);

    if (isset($VALMIS)) {
      $tee = "";

      if ($lopetus != '') {
        lopetus($lopetus, "META");
      }
    }
  }

  if ($tee == 'vakuutushakemus') {
    $query = "  SELECT *
          FROM laskun_lisatiedot
          WHERE yhtio  = '$kukarow[yhtio]'
          and otunnus = '$tilausnumero'";
    $lisres = mysql_query($query) or pupe_error($query);
    $lisrow = mysql_fetch_array($lisres);

    if ($lisrow["vakuutushak_alkamispaiva"] != '0000-00-00') {
      $kka = substr($lisrow["vakuutushak_alkamispaiva"],5,2);
      $vva = substr($lisrow["vakuutushak_alkamispaiva"],0,4);
      $ppa = substr($lisrow["vakuutushak_alkamispaiva"],8,2);
    }
    else {
      $kka = date("m",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $vva = date("Y",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
      $ppa = date("d",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    }

    echo "<form method='post'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='tee' value='vakuutushakemus'>";

    echo "<br><table>";
    echo "<tr>
        <th>".t("1. Vakuutusyhtiö")."</th>";

    $sel = array();
    $sel[$lisrow["vakuutushak_vakuutusyhtio"]] = "selected";

    echo "<td>
        <select name='vakuutushak_vakuutusyhtio'>
        <option value='41' $sel[41]>if / Sampo</option>
        <option value='03' $sel[03]>Lähivakuutus</option>
        <option value='04' $sel[04]>A-vakuutus</option>
        <option value='13' $sel[13]>Pohjantähti</option>
        <option value='15' $sel[15]>Volvia</option>
        <option value='20' $sel[20]>Nordea Vahinkovakuutus</option>
        <option value='22' $sel[22]>Tapiola</option>
        <option value='36' $sel[36]>Pohjola</option>
        <option value='50' $sel[50]>Veritas</option>
        <option value='56' $sel[56]>Turva</option>
        <option value='65' $sel[65]>Y-Fennia</option>
        <option value='66' $sel[66]>Ålandia</option>
        </select></td><td></td></tr>";

    echo "<tr>
        <th>".t("2. Alkamispäivä")."</th>
        <td><input type='text' name='ppa' size='3' value='$ppa'>
          <input type='text' name='kka' size='3' value='$kka'>
          <input type='text' name='vva' size='5' value='$vva'>
          </td><td></td></tr>";
    echo "<tr>
        <th>".t("3. Kaskolaji")."</th>";

    $sel = array();
    $sel[$lisrow["vakuutushak_kaskolaji"]] = "selected";

    echo "<td>
        <select name='vakuutushak_kaskolaji'>
        <option value='1' $sel[1]>Laajin</option>
        <option value='2' $sel[2]>Laaja</option>
        <option value='3' $sel[3]>Suppea</option>
        <option value='4' $sel[4]>Suppein</option>
        </select></td><td></td></tr>";


    echo "<tr>
        <th>".t("4. Maksetaan")."</th>";

    $sel = array();
    $sel[$lisrow["vakuutushak_maksuerat"]] = "selected";

    echo "<td>
        <select name='vakuutushak_maksuerat'>
        <option value='1' $sel[1]>1</option>
        <option value='2' $sel[2]>2</option>
        <option value='3' $sel[3]>3</option>
        <option value='4' $sel[4]>4</option>
        </select></td><td>".t("erässä")."</td></tr>";

    echo "<tr>
        <th>".t("5. Perusomavastuu")."</th>
        <td><input type='text' name='vakuutushak_perusomavastuu' size='14' value='$lisrow[vakuutushak_perusomavastuu]'></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr>
        <th>".t("6. Runko, takila, purjeet")."</th>
        <td><input type='text' name='vakuutushak_runko_takila_purjeet' size='14' value='$lisrow[vakuutushak_runko_takila_purjeet]'></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr>
        <th>".t("7. Moottori")."</th>
        <td><input type='text' name='vakuutushak_moottori' size='14' value='$lisrow[vakuutushak_moottori]'></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr>
        <th>".t("8. Varusteet")."</th>
        <td><input type='text' name='vakuutushak_varusteet' size='14' value='$lisrow[vakuutushak_varusteet]'></td><td>$laskurow[valkoodi]</td></tr>";
    echo "<tr>
        <th>".t("9. Yhteensä")."</th>
        <td><input type='text' name='vakuutushak_yhteensa' size='14' value='$lisrow[vakuutushak_yhteensa]'></td><td>$laskurow[valkoodi]</td></tr>";


    echo "<tr><td class='back'><br><br></td></tr>";


    echo "<tr>
        <th>".t("1. Rekisterinumero")."</th>
        <td><input type='text' name='rekisteilmo_rekisterinumero' size='14' value='$lisrow[rekisteilmo_rekisterinumero]'></td><td></td></tr>";
    echo "<tr>
        <th>".t("2. Pääasiallinen käyttökunta")."</th>
        <td><input type='text' name='rekisteilmo_paakayttokunta' size='14' value='$lisrow[rekisteilmo_paakayttokunta]'></td><td></td></tr>";


    $sel = array();
    $sel[$lisrow["rekisteilmo_kieli"]] = "selected";

    echo "<tr>
        <th>".t("3. Todistuksen kieli")."</th>
        <td>
        <select name='rekisteilmo_kieli'>
        <option value='SUOMI'  $sel[SUOMI]>".t("Suomenkielinen")."</option>
        <option value='RUOTSI' $sel[RUOTSI]>".t("Ruotsinkielinen")."</option>
        </select>
        </td><td></td></tr>";

    $sel = array();
    $sel[$lisrow["rekisteilmo_tyyppi"]] = "selected";

    echo "<tr>
        <th>".t("4. Reksiteröinnin tyyppi")."</th>
        <td>
        <select name='rekisteilmo_tyyppi'>
        <option value='UUSEREKISTERÖINTI'  $sel[UUSEREKISTERÖINTI]>".t("Ensirekisteröinti")."</option>
        <option value='MUUTOSILMOITUS'    $sel[MUUTOSILMOITUS]>".t("Muutosilmoitus")."</option>
        <option value='KAKSOISKPL'      $sel[KAKSOISKPL]>".t("Rek.todistuksen kaksoiskappale")."</option>
        <option value='POISTO'          $sel[POISTO]>".t("Poisto")."</option>
        <option value='TARKISTUS'        $sel[TARKISTUS]>".t("Tietojen tarkistus")."</option>
        <option value='KOETUNNUS'        $sel[KOETUNNUS]>".t("Koetunnus")."</option>
        </select>
        </td><td></td></tr>";

    echo "<tr>
        <th>".t("5. Omistajien lukumäärä")."</th>
        <td><input type='text' name='rekisteilmo_omistajienlkm' size='14' value='$lisrow[rekisteilmo_omistajienlkm]'></td><td></td></tr>";

    echo "<tr>
        <th>".t("6. Omistajan kotikunta")."</th>
        <td><input type='text' name='rekisteilmo_omistajankotikunta' size='14' value='$lisrow[rekisteilmo_omistajankotikunta]'></td><td></td></tr>";


    if ($lisrow["rekisteilmo_laminointi"] == "K" or $lisrow["rekisteilmo_laminointi"] == "") {
      $sel = "CHECKED";
    }
    else {
      $sel = "";
    }

    echo "<tr>
        <th>".t("7. Venetodistuksen laminointi")."</th>
        <td><input type='checkbox' name='rekisteilmo_laminointi' value='K' $sel></td><td></td></tr>";


    if ($lisrow["rekisteilmo_suoramarkkinointi"] == "K") {
      $sel = "CHECKED";
    }
    else {
      $sel = "";
    }

    echo "<tr>
        <th>".t("8. Tiedot saa luovuttaa suoramarkkinointikäyttöön")."</th>
        <td><input type='checkbox' name='rekisteilmo_suoramarkkinointi' value='K' $sel></td><td></td></tr>";

    echo "<tr>
        <th>".t("9. Vesikulkuneuvolle annettu nimi")."</th>
        <td><input type='text' name='rekisteilmo_veneen_nimi' size='14' value='$lisrow[rekisteilmo_veneen_nimi]'></td><td></td></tr>";


    echo "<tr><td class='back'><br><br></td></tr>";

    $sel = array();
    $sel[$lisrow["rekisteilmo_omistaja"]] = "selected";

    echo "<tr>
        <th>".t("Omistajan tiedot")."</th>
        <td>
        <select name='rekisteilmo_omistaja'>
        <option value='OSTAJA' $sel[OSTAJA]>".t("Ostaja")."</option>
        <option value='NORDEA' $sel[NORDEA]>".t("Nordea Rahoitus")."</option>
        <option value='GE'     $sel[GE]>".t("GE Money")."</option>
        <option value='POHJOLA'$sel[POHJOLA]>".t("Pohjola Pankki")."</option>
        <option value='SAMPO'  $sel[SAMPO]>".t("Sampo Rahoitus")."</option>
        <option value='SANTAN' $sel[SANTAN]>".t("Santander")."</option>
        <option value='HANDEL' $sel[HANDEL]>".t("Handelsbanken Rahoitus")."</option>";
    echo "  </select>
        </td><td></td></tr>";

    echo "<tr><td class='back'><br><br></td></tr>";

    //   Y = Yksityinen
    //   L = Luvanvarainen
    //   V = Vuokraus ilman kuljettajaa
    //   M = Myyntivarasto
    $sel = array();
    $sel[$lisrow["rahlaskelma_kayttotarkoitus"]] = "selected";

    echo "<tr>
        <th>".t("Käyttötarkoitus")."</th>
        <td>
        <select name='rahlaskelma_kayttotarkoitus'>
        <option value='Y' $sel[Y]>".t("Yksityinen")."</option>
        <option value='L' $sel[L]>".t("Luvanvarainen")."</option>
        <option value='V' $sel[V]>".t("Vuokraus ilman kuljettajaa")."</option>
        <option value='M' $sel[M]>".t("Myyntivarasto")."</option>
        </select>
        </td><td></td></tr>";

    $query = "  SELECT sarjanumeroseuranta.tunnus
          FROM tilausrivi
          JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
          JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='VENE'
          WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
          and tilausrivi.otunnus = '$tilausnumero'
          LIMIT 1";
    $venvelisres = mysql_query($query) or pupe_error($query);

    $query = "  SELECT sarjanumeroseuranta.tunnus
          FROM tilausrivi
          JOIN sarjanumeroseuranta ON tilausrivi.yhtio=sarjanumeroseuranta.yhtio and tilausrivi.tunnus=sarjanumeroseuranta.myyntirivitunnus
          JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.positio='MOOTTORI'
          WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
          and tilausrivi.otunnus = '$tilausnumero'
          LIMIT 1";
    $mootlisres = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($venvelisres) > 0) {

      $lisrow = mysql_fetch_array($venvelisres);

      $query = "  SELECT *
            FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
            WHERE yhtio     = '$kukarow[yhtio]'
            and liitostunnus = '$lisrow[tunnus]'";
      $lisares = mysql_query($query) or pupe_error($query);
      $lisarow = mysql_fetch_array($lisares);

      if ($lisarow["tunnus"] != 0) {
        $ylisa = "&tunnus=$lisarow[tunnus]";
      }
      else {
        $ylisa = "&liitostunnus=$lisrow[tunnus]&uusi=1";
      }

      if (mysql_num_rows($mootlisres) > 0) {
        $alanimi = "VAKHAKVENE";
      }
      else {
        $alanimi = "VAKHAK";
      }

      echo "<tr>
          <th>".t("Veneen tiedot")."</th>
          <td><a href='../yllapito.php?toim=sarjanumeron_lisatiedot!!!$alanimi!!!true$ylisa&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>".t("Veneen lisätiedot")."</a></td><td>".t("Tallenna tämä sivu ennen kuin siirryt lisätietoihin")."</td></tr>";
    }

    if (mysql_num_rows($mootlisres) > 0) {

      $lisrow = mysql_fetch_array($mootlisres);

      $query = "  SELECT *
            FROM sarjanumeron_lisatiedot use index (yhtio_liitostunnus)
            WHERE yhtio     = '$kukarow[yhtio]'
            and liitostunnus = '$lisrow[tunnus]'";
      $lisares = mysql_query($query) or pupe_error($query);
      $lisarow = mysql_fetch_array($lisares);

      if ($lisarow["tunnus"] != 0) {
        $ylisa = "&tunnus=$lisarow[tunnus]";
      }
      else {
        $ylisa = "&liitostunnus=$lisrow[tunnus]&uusi=1";
      }

      echo "<tr>
          <th>".t("Moottorin tiedot")."</th>
          <td>

          <a href='../yllapito.php?toim=sarjanumeron_lisatiedot!!!VAKHAKMOO!!!true$ylisa&lopetus=$PHP_SELF////toim=$toim//lopetus=$lopetus//tee=$tee//tilausnumero=$tilausnumero//from=LASKUTATILAUS'>".t("Moottorin lisätiedot")."</a>

          </td><td>".t("Tallenna tämä sivu ennen kuin siirryt lisätietoihin")."</td></tr>";
    }

    echo "<tr><td class='back'><br><br></td></tr>";

    echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='PAIVIT' value='".t("Tallenna")."'></td><td></td></tr>";
    echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='VALMIS' value='".t("Tallenna ja siirry takaisin tilaukselle")."'></td><td></td></tr>";
    echo "</form>";

    echo "<form name='valmis' action='tulostakopio.php' method='post' name='tulostaform_vakhak' id='tulostaform_vakhak' class='multisubmit'>
        <input type='hidden' name='tee' value='NAYTATILAUS'>
        <input type='hidden' name='otunnus' value='$tilausnumero'>";


    echo "<tr><th>".t("Näytä lomake").":</th>";

    echo "<td>";
    echo "<select name='toim'>";
    echo "<option value='TARJOUS'>Tarjous</value>";
    echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
    echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
    echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
    echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
    echo "<option value='REKISTERIILMOITUS'>Rekisteröinti-ilmoitus</value>";
    echo "</select></td>";
    echo "<td><input type='submit' value='".t("Näytä")."' onClick=\"js_openFormInNewWindow('tulostaform_vakhak', 'tulosta_vakhak'); return false;\"></td>";
    echo "</form></table>";
  }
