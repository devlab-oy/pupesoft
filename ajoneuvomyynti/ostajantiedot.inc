<?php

// Päivitetään tiedot tietokantaan
if ($tee == 'ostajantiedot' and !empty($laskurow['laskun_lisatiedottunnus']) and (isset($PAIVIT) or isset($VALMIS))) {

  $query = "UPDATE laskun_lisatiedot
            SET yhtio = yhtio
            WHERE yhtio = '$kukarow[yhtio]'
            and tunnus = '$tilausnumero'";
  pupe_query($query);

  if (isset($VALMIS)) {
    $tee = "";

    if ($lopetus != '') {
      lopetus($lopetus, "META");
    }
  }
}

if ($tee == 'ostajantiedot') {
  $query = "SELECT *
            FROM laskun_lisatiedot
            WHERE yhtio = '$kukarow[yhtio]'
            and otunnus = '$tilausnumero'";
  $lisres = mysql_query($query) or pupe_error($query);
  $lisrow = mysql_fetch_array($lisres);

  if ($lisrow["vakuutushak_alkamispaiva"] != '0000-00-00') {
    $kka = substr($lisrow["vakuutushak_alkamispaiva"], 5, 2);
    $vva = substr($lisrow["vakuutushak_alkamispaiva"], 0, 4);
    $ppa = substr($lisrow["vakuutushak_alkamispaiva"], 8, 2);
  }
  else {
    $kka = date("m", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $vva = date("Y", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $ppa = date("d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  }

  echo "<form method='post'>
      <input type='hidden' name='toim' value='$toim'>
      <input type='hidden' name='lopetus' value='$lopetus'>
      <input type='hidden' name='tilausnumero' value='$tilausnumero'>
      <input type='hidden' name='tee' value='ostajantiedot'>";

  // Ostajan tiedot
  echo "<table>";
  echo "<tr><td class='back' colspan='2'><span class='message'>".t("Ostaja")."</span></td></tr>";

  echo "<tr>
      <th>", t("Henkilötunnus / Y-tunnus"), ":</th>
      <td><input type='text' name='ytunnus' value='{$laskurow['ytunnus']}' /></td>
      </tr>";

  echo "<tr>
      <th>".t("Nimi").": </th>
      <td><input type='text' name='nimi' size='35' value='$laskurow[nimi]'></td>
    </tr>";

  echo "<tr>
      <th></th>
      <td><input type='text' name='nimitark' size='35' value='$laskurow[nimitark]'></td>
    </tr>";

  echo "<tr>
      <th>".t("Osoite").": </th>
      <td><input type='text' name='osoite' size='35' value='$laskurow[osoite]'></td>
    </tr>";

  echo "<tr>
      <th>".t("Postitp").": </th>
      <td><input type='text' name='postino' size='10' value='$laskurow[postino]'> <input type='text' name='postitp' size='21' value='$laskurow[postitp]'></td>
    </tr>";

  $query = "SELECT distinct koodi, nimi
          FROM maat
          WHERE nimi != ''
          ORDER BY koodi";
  $vresult = pupe_query($query);

  echo "<tr><th> ".t("Maa").": </th>
    <td><select name='maa' onChange='submit();' ".js_alasvetoMaxWidth("maa", 200).">";

  while ($vrow = mysql_fetch_assoc($vresult)) {
    $sel="";
    if (strtoupper($laskurow["maa"]) == strtoupper($vrow["koodi"])) {
      $sel = "selected";
    }
    elseif ($laskurow["maa"] == "" and strtoupper($vrow["koodi"]) == strtoupper($yhtiorow["maa"])) {
      $sel = "selected";
    }
    echo "<option value = '".strtoupper($vrow["koodi"])."' $sel>".t($vrow["nimi"])."</option>";
  }

  echo "</select></td>
      </tr>";

  echo "<tr>
    <th> ".t("Sähköposti").": </th>
    <td><input type='text' name='email' size='35' value='$laskurow[email]'></td></tr>";

  echo "<tr>
    <th>".t("Puhelin")."</th>
    <td><input type='text' name='puhelin' size='35' value='$laskurow[puhelin]'></td></tr>";

  //    A = Ajokortti
  //    H = Henkilökortti
  //    P = Passi
  //    (K = Kuvallinen pankkikortti)
  //    U = Kuvallinen Kela-kortti
  $sel = array();
  $sel[$lisrow["rahlaskelma_hetu_tarkistus"]] = "selected";

  echo "<tr>
      <th>".t("Henkilötietojen tarkistus")."</th>
      <td>
      <select name='rahlaskelma_hetu_tarkistus'>
      <option value=''>".t("Valitse")."</option>
      <option value='A' $sel[A]>".t("Ajokortti")."</option>
      <option value='H' $sel[H]>".t("Henkilökortti")."</option>
      <option value='P' $sel[P]>".t("Passi")."</option>
      <option value='K' $sel[K]>".t("Kuvallinen pankkikortti")."</option>
      <option value='U' $sel[U]>".t("Kuvallinen Kela-kortti")."</option>
      </select>
      </td></tr>";

  echo"<tr>
  <th>".t("Henkilötietojen asiakirjan nro / pvm")."</th>
  <td><input type='text' name='rahlaskelma_hetu_asiakirjanro' size='35' value='$lisrow[rahlaskelma_hetu_asiakirjanro]'></td>
  </tr>";

  echo"<tr>
  <th>".t("Henkilötietojen asiakirjan myöntäja")."</th>
  <td><input type='text' name='rahlaskelma_hetu_asiakirjamyontaja' size='35' value='$lisrow[rahlaskelma_hetu_asiakirjamyontaja]'></td>
  </tr>";

  echo"<tr>
  <th>".t("Henkilötietojen tarkastaja / pvm")."</th>
  <td><input type='text' name='rahlaskelma_hetu_tarkastaja' size='35' value='$lisrow[rahlaskelma_hetu_tarkastaja]'></td>
  </tr>";

  $sel = "";
  echo "<tr>
        <th>PEP (Politically Exposed Person)</th>
        <td><input type='checkbox' name='pep' value='X' $sel></td>
        </tr>";

  // Rinnakkaisostajan tiedot
  echo "<tr><td class='back' colspan='2'><span class='message'>".t("Rinnakkaisostaja")."</span></td></tr>";

  echo "<tr>
        <th>", t("Henkilötunnus / Y-tunnus"), ":</th>
        <td><input type='text' name='kolm_ovttunnus' value='{$laskurow['ytunnus']}' /></td>
        </tr>";

  echo "<tr>
      <th> ".t("Nimi").": </th>
      <td><input type='text' name='kolm_nimi' size='35' value='$laskurow[kolm_nimi]'></td>
      </tr>";

  echo "<tr>
      <th></th>
      <td><input type='text' name='kolm_nimitark' size='35' value='$laskurow[kolm_nimitark]'></td>
      </tr>";

  echo "<tr>
      <th>".t("Osoite").": </th>
      <td><input type='text' name='kolm_osoite' size='35' value='$laskurow[kolm_osoite]'></td>
      </tr>";

  echo "<tr>
      <th>".t("Postitp").": </th>
      <td><input type='text' name='kolm_postino' size='6' value='$laskurow[kolm_postino]'> <input type='text' name='kolm_postitp' size='21' value='$laskurow[kolm_postitp]'></td>
      </tr>";

  echo "<tr>
      <th valign='top'> ".t("Sähköposti").": </th>
      <td><input type='text' name='kolm_email' size='35' value='$laskurow[kolm_email]'></td>
      </tr>";

  echo "<tr>
      <th valign='top'>".t("Puhelin")."</th>
      <td><input type='text' name='kolm_puhelin' size='35' value='$laskurow[kolm_puhelin]'></td>
      </tr>";

  $sel = array();
  $sel[$lisrow["rahlaskelma_hetu_kolm_tarkistus"]] = "selected";

  echo "<tr>
        <th>".t("Rinnakkaisostajan henkilötietojen tarkistus")."</th>
        <td>
        <select name='rahlaskelma_hetu_kolm_tarkistus'>
        <option value=''>".t("Valitse")."</option>
        <option value='A' $sel[A]>".t("Ajokortti")."</option>
        <option value='H' $sel[H]>".t("Henkilökortti")."</option>
        <option value='P' $sel[P]>".t("Passi")."</option>
        <option value='K' $sel[K]>".t("Kuvallinen pankkikortti")."</option>
        <option value='U' $sel[U]>".t("Kuvallinen Kela-kortti")."</option>
        </select>
        </td></tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkilötietojen asiakirjan nro / pvm")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_asiakirjanro' size='35' value='$lisrow[rahlaskelma_hetu_kolm_asiakirjanro]'></td>
    </tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkilötietojen asiakirjan myöntäja")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_asiakirjamyontaja' size='35' value='$lisrow[rahlaskelma_hetu_kolm_asiakirjamyontaja]'></td>
    </tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkilötietojen tarkastaja / pvm")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_tarkastaja' size='35' value='$lisrow[rahlaskelma_hetu_kolm_tarkastaja]'></td>
    </tr>";

  $sel = "";

  echo "<tr>
          <th>PEP (Politically Exposed Person)</th>
          <td><input type='checkbox' name='pep' value='X' $sel></td>
          </tr>";

  // Haltijan / käyttäjän tiedot
  echo "<tr><td class='back' colspan='2'><span class='message'>".t("Muu haltija/käyttäjä")."</span></td></tr>";

  echo "<tr>
          <th>", t("Henkilötunnus / Y-tunnus"), ":</th>
          <td><input type='text' name='kolm_ovttunnus' value='{$laskurow['']}' /></td>
          </tr>";

  echo "<tr>
        <th> ".t("Nimi").": </th>
        <td><input type='text' name='kolm_nimi' size='35' value='{$laskurow['']}'></td>
        </tr>";

  echo "<tr>
        <th></th>
        <td><input type='text' name='kolm_nimitark' size='35' value='{$laskurow['']}'></td>
        </tr>";

  echo "<tr>
        <th>".t("Osoite").": </th>
        <td><input type='text' name='kolm_osoite' size='35' value='{$laskurow['']}'></td>
        </tr>";

  echo "<tr>
        <th>".t("Postitp").": </th>
        <td><input type='text' name='kolm_postino' size='6' value='{$laskurow['']}'> <input type='text' name='kolm_postitp' size='21' value='{$laskurow['']}'></td>
        </tr>";

  echo "<tr>
        <th valign='top'> ".t("Sähköposti").": </th>
        <td><input type='text' name='kolm_email' size='35' value='$laskurow[kolm_email]'></td>
        </tr>";

  echo "<tr>
        <th valign='top'>".t("Puhelin")."</th>
        <td><input type='text' name='kolm_puhelin' size='35' value='$laskurow[kolm_puhelin]'></td>
        </tr>";


  echo "<tr><td class='back'><br><br></td></tr>";

  echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='PAIVIT' value='".t("Tallenna")."'></td></tr>";
  echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='VALMIS' value='".t("Tallenna ja siirry takaisin tilaukselle")."'></td></tr>";
  echo "</form>";
  echo "</table>";
}
