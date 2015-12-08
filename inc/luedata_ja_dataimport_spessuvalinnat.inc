<?php

switch ($table) {
case "autoid_lisatieto":
  echo "<tr><th>", t("Valitse liitos"), ":</th>
        <td><select name='autoid_liitos'>
        <option value=''>", t("Autoid"), "</option>
        <option value='malli'>", t("Mallinumero"), "</option>
        </select></td>
    </tr>";
  break;
case "yhteyshenkilo":
case "asiakkaan_avainsanat":
case "kalenteri":
  echo "<tr><th>".t("Ytunnus-tarkkuus").":</th>
        <td><select name='ytunnustarkkuus'>
        <option value=''>".t("Päivitetään vain, jos Ytunnuksella löytyy yksi rivi")."</option>
        <option value='2'>".t("Päivitetään kaikki syötetyllä Ytunnuksella löytyvät asiakkaat")."</option>
        </select></td>
    </tr>";
  break;
case "puun_alkio_asiakas":
case "puun_alkio_tuote":
  echo "<tr><th>", t("Valitse liitos"), ":</th>
        <td><select name='dynaamisen_taulun_liitos'>";

  if ($table == 'puun_alkio_asiakas') {
    echo "<option value=''>", t("Asiakkaan tunnus"), "</option>
          <option value='ytunnus'>", t("Asiakkaan ytunnus"), "</option>
          <option value='toim_ovttunnus'>", t("Asiakkaan toimitusosoitteen ovttunnus"), "</option>
          <option value='asiakasnro'>", t("Asiakkaan asiakasnumero"), "</option>";
  }
  else {
    echo "<option value=''>", t("Puun alkion tunnus"), "</option>
          <option value='koodi'>", t("Puun alkion koodi"), "</option>";
  }

  echo "</select></td></tr>";
  break;
case "asiakasalennus":
case "asiakashinta":
  echo "<tr><th>".t("Segmentin valinta").":</th>
        <td><select name='segmenttivalinta'>
        <option value='1'>".t("Valitaan käytettäväksi asiakas-segmentin koodia")."</option>
        <option value='2'>".t("Valitaan käytettäväksi asiakas-segmentin tunnusta ")."</option>
        </select></td>
    </tr>";
  echo "<tr><th>".t("Asiakkaan valinta").":</th>
        <td><select name='asiakkaanvalinta'>
        <option value='1'>".t("Asiakas-sarakkeessa asiakkaan tunnus")."</option>
        <option value='2'>".t("Asiakas-sarakkeessa asiakkaan toim_ovttunnus")."</option>
        <option value='3'>".t("Asiakas-sarakkeessa asiakkaan asiakasnumero")."</option>
        </select></td>
    </tr>";
  break;
case "tuote":
  echo "<tr><th>".t("Myyntihinnan päivitys").":</th>
        <td><select name='myyntihinnan_paivitys'>
        <option value='2'>".t("Oletus")."</option>
        <option value='1'>".t("Päivitetään myyntihinta vain jos tiedostossa oleva on suurempi kuin nykyinen")."</option>
        </select></td>
    </tr>";
  break;
case "tuotepaikat":
  if ($yhtiorow['toimipaikkakasittely'] == "L") {
    $_toimipaikat = hae_toimipaikat();

    echo "<tr><th>".t("Minkä toimipaikan käyttäjänä tuotepaikat käsitellään").":</th>";
    echo "<td><select name='toimipaikkavalinta'>";
    echo "<option value='0'>", t("Ei toimipaikkaa"), "</option>";

    foreach ($_toimipaikat as $_toimipaikka) {
      if (!isset($toimipaikkavalinta) and $kukarow['toimipaikka'] == $_toimipaikka['tunnus']) {
        $sel = "selected";
        $toimipaikkavalinta = $_toimipaikka['tunnus'];
      }
      elseif (!empty($toimipaikkavalinta) and $toimipaikkavalinta == $_toimipaikka['tunnus']) {
        $sel = "selected";
        $toimipaikkavalinta = $_toimipaikka['tunnus'];
      }
      else {
        $sel = "";
      }

      echo "<option value='{$_toimipaikka['tunnus']}' {$sel}>{$_toimipaikka['nimi']}</option>";
    }

    echo "</select></td>";
    echo "</tr>";
  }
  break;
case "tuotteen_toimittajat":
  echo "<tr><th>".t("Toimittajan valinta").":</th>
        <td><select name='toimittajavalinta'>
        <option value='1'>".t("Käytetään toimittajan tunnusta")."</option>
        <option value='2'>".t("Käytetään toimittajan ytunnusta")."</option>
        <option value='3'>".t("Käytetään toimittajan toimittajanumeroa")."</option>
        </select></td>
    </tr>";
  break;
case "tuotteen_toimittajat_pakkauskoot":
  echo "<tr><th>".t("Tuotteen toimittajan tunnuksen valinta").":</th>
        <td><select name='toimitunnusvalinta'>
        <option value='1'>".t("Käytetään tuotteen toimittajan tunnusta")."</option>
        <option value='2'>".t("Käytetään toimittajan ytunnusta sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='3'>".t("Käytetään toimittajan ytunnusta sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        <option value='4'>".t("Käytetään toimittajan toimittajanumeroa sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='5'>".t("Käytetään toimittajan toimittajanumeroa sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        <option value='6'>".t("Käytetään toimittajan tunnusta sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='7'>".t("Käytetään toimittajan tunnusta sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        </select></td>
    </tr>";
  break;
case "tuotteen_toimittajat_tuotenumerot":
  echo "<tr><th>".t("Tuotteen toimittajan tunnuksen valinta").":</th>
        <td><select name='toimitunnusvalinta'>
        <option value='1'>".t("Käytetään tuotteen toimittajan tunnusta")."</option>
        <option value='2'>".t("Käytetään toimittajan ytunnusta sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='3'>".t("Käytetään toimittajan ytunnusta sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        <option value='4'>".t("Käytetään toimittajan toimittajanumeroa sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='5'>".t("Käytetään toimittajan toimittajanumeroa sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        <option value='6'>".t("Käytetään toimittajan tunnusta sekä omassa sarakkeessa tuotenumero:a")."</option>
        <option value='7'>".t("Käytetään toimittajan tunnusta sekä omassa sarakkeessa toim_tuoteno:a")."</option>
        </select></td>
    </tr>";
  break;
case "extranet_kayttajan_lisatiedot":
  echo "<tr><th>".t("Liitostunnus").":</th>
      <td><select name='liitostunnusvalinta'>
      <option value='1'>".t("Liitostunnus-sarakkeessa liitostunnus")."</option>
      <option value='2'>".t("Liitostunnus-sarakkeessa käyttäjänimi")."</option>
      </select></td>
  </tr>";
  break;
}
