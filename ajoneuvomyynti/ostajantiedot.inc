<?php

// P�ivitet��n tiedot tietokantaan
if ($tee == 'ostajantiedot' and $tilausnumero > 0 and (isset($PAIVIT) or isset($VALMIS))) {

  if ($rekisteilmo_laminointi == "") {
    $rekisteilmo_laminointi = "E";
  }
  if ($rekisteilmo_suoramarkkinointi == "") {
    $rekisteilmo_suoramarkkinointi = "E";
  }

  $query = "UPDATE laskun_lisatiedot
          SET vakuutushak_vakuutusyhtio       = '$vakuutushak_vakuutusyhtio',
          vakuutushak_alkamispaiva        = '$vva-$kka-$ppa',
          vakuutushak_kaskolaji           = '$vakuutushak_kaskolaji',
          vakuutushak_maksuerat           = '$vakuutushak_maksuerat',
          vakuutushak_perusomavastuu        = '$vakuutushak_perusomavastuu',
          vakuutushak_runko_takila_purjeet    = '$vakuutushak_runko_takila_purjeet',
          vakuutushak_moottori          = '$vakuutushak_moottori',
          vakuutushak_varusteet           = '$vakuutushak_varusteet',
          vakuutushak_yhteensa          = '$vakuutushak_yhteensa',
          rekisteilmo_rekisterinumero       = '$rekisteilmo_rekisterinumero',
          rekisteilmo_paakayttokunta        = '$rekisteilmo_paakayttokunta',
          rekisteilmo_kieli             = '$rekisteilmo_kieli',
          rekisteilmo_tyyppi            = '$rekisteilmo_tyyppi',
          rekisteilmo_omistajienlkm         = '$rekisteilmo_omistajienlkm',
          rekisteilmo_omistajankotikunta      = '$rekisteilmo_omistajankotikunta',
          rekisteilmo_lisatietoja         = '$rekisteilmo_lisatietoja',
          rekisteilmo_laminointi          = '$rekisteilmo_laminointi',
          rekisteilmo_suoramarkkinointi       = '$rekisteilmo_suoramarkkinointi',
          rekisteilmo_veneen_nimi         = '$rekisteilmo_veneen_nimi',
          rekisteilmo_omistaja          = '$rekisteilmo_omistaja',
          rahlaskelma_hetu_tarkistus        = '$rahlaskelma_hetu_tarkistus',
          rahlaskelma_hetu_tarkastaja       = '$rahlaskelma_hetu_tarkastaja',
          rahlaskelma_hetu_asiakirjanro     = '$rahlaskelma_hetu_asiakirjanro',
          rahlaskelma_hetu_asiakirjamyontaja    = '$rahlaskelma_hetu_asiakirjamyontaja',
          rahlaskelma_hetu_kolm_tarkistus     = '$rahlaskelma_hetu_kolm_tarkistus',
          rahlaskelma_hetu_kolm_asiakirjanro    = '$rahlaskelma_hetu_kolm_asiakirjanro',
          rahlaskelma_hetu_kolm_asiakirjamyontaja = '$rahlaskelma_hetu_kolm_asiakirjamyontaja',
          rahlaskelma_hetu_kolm_tarkastaja    = '$rahlaskelma_hetu_kolm_tarkastaja',
          rahlaskelma_takuukirja          = '$rahlaskelma_takuukirja',
          rahlaskelma_huoltokirja         = '$rahlaskelma_huoltokirja',
          rahlaskelma_kayttoohjeet        = '$rahlaskelma_kayttoohjeet',
          rahlaskelma_opastus           = '$rahlaskelma_opastus',
          rahlaskelma_kuntotestitodistus      = '$rahlaskelma_kuntotestitodistus',
          rahlaskelma_kayttotarkoitus       = '$rahlaskelma_kayttotarkoitus'
          WHERE yhtio  = '$kukarow[yhtio]'
          and otunnus = '$tilausnumero'";
  $res = mysql_query($query) or pupe_error($query);

  if (isset($VALMIS)) {
    $tee = "";

    if ($lopetus != '') {
      lopetus($lopetus, "META");
    }
  }
}

if ($tee == 'ostajantiedot') {
  $query = "  SELECT *
        FROM laskun_lisatiedot
        WHERE yhtio = '$kukarow[yhtio]'
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
      <input type='hidden' name='tee' value='ostajantiedot'>";

  // Ostajan tiedot
  echo "<table>";
  echo "<tr><td class='back' colspan='2'><span class='message'>".t("Ostaja")."</span></td></tr>";

  echo "<tr>
      <th>", t("Henkil�tunnus / Y-tunnus"), ":</th>
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
    <th> ".t("S�hk�posti").": </th>
    <td><input type='text' name='email' size='35' value='$srow[email]'></td></tr>";

echo "<tr>
    <th>".t("Puhelin")."</th>
    <td><input type='text' name='puhelin' size='35' value='$srow[puhelin]'></td></tr>";

//    A = Ajokortti
//    H = Henkil�kortti
//    P = Passi
//    (K = Kuvallinen pankkikortti)
//    U = Kuvallinen Kela-kortti
$sel = array();
$sel[$lisrow["rahlaskelma_hetu_tarkistus"]] = "selected";

echo "<tr>
      <th>".t("Henkil�tietojen tarkistus")."</th>
      <td>
      <select name='rahlaskelma_hetu_tarkistus'>
      <option value=''>".t("Valitse")."</option>
      <option value='A' $sel[A]>".t("Ajokortti")."</option>
      <option value='H' $sel[H]>".t("Henkil�kortti")."</option>
      <option value='P' $sel[P]>".t("Passi")."</option>
      <option value='K' $sel[K]>".t("Kuvallinen pankkikortti")."</option>
      <option value='U' $sel[U]>".t("Kuvallinen Kela-kortti")."</option>
      </select>
      </td></tr>";

echo"<tr>
  <th>".t("Henkil�tietojen asiakirjan nro / pvm")."</th>
  <td><input type='text' name='rahlaskelma_hetu_asiakirjanro' size='35' value='$lisrow[rahlaskelma_hetu_asiakirjanro]'></td>
  </tr>";

echo"<tr>
  <th>".t("Henkil�tietojen asiakirjan my�nt�ja")."</th>
  <td><input type='text' name='rahlaskelma_hetu_asiakirjamyontaja' size='35' value='$lisrow[rahlaskelma_hetu_asiakirjamyontaja]'></td>
  </tr>";

echo"<tr>
  <th>".t("Henkil�tietojen tarkastaja / pvm")."</th>
  <td><input type='text' name='rahlaskelma_hetu_tarkastaja' size='35' value='$lisrow[rahlaskelma_hetu_tarkastaja]'></td>
  </tr>";

$sel = array();
$sel[$lisrow["rahlaskelma_pep"]] = "selected";

  echo "<tr>
        <th>PEP (Politically Exposed Person)</th>
        <td><input type='checkbox' name='pep' value='X' $sel></td>
        </tr>";
  echo "<tr><td class='back'><br><br></td></tr>";

  // Rinnakkaisostajan tiedot
  echo "<tr><td class='back' colspan='2'><span class='message'>".t("Rinnakkaisostaja")."</span></td></tr>";

  echo "<tr>
        <th>", t("Henkil�tunnus / Y-tunnus"), ":</th>
        <td><input type='text' name='kolm_ovttunnus' value='{$laskurow['ytunnus']}' /></td>
        </tr>";

  echo "<tr>
      <th> ".t("Nimi").": </th>
      <td><input type='text' name='kolm_nimi' size='35' value='$srow[kolm_nimi]'></td>
      </tr>";

  echo "<tr>
      <th></th>
      <td><input type='text' name='kolm_nimitark' size='35' value='$srow[kolm_nimitark]'></td>
      </tr>";

  echo "<tr>
      <th>".t("Osoite").": </th>
      <td><input type='text' name='kolm_osoite' size='35' value='$srow[kolm_osoite]'></td>
      </tr>";

  echo "<tr>
      <th>".t("Postitp").": </th>
      <td><input type='text' name='kolm_postino' size='6' value='$srow[kolm_postino]'> <input type='text' name='kolm_postitp' size='21' value='$srow[kolm_postitp]'></td>
      </tr>";

  echo "<tr>
      <th valign='top'> ".t("S�hk�posti").": </th>
      <td><input type='text' name='kolm_email' size='35' value='$srow[kolm_email]'></td>
      </tr>";

  echo "<tr>
      <th valign='top'>".t("Puhelin")."</th>
      <td><input type='text' name='kolm_puhelin' size='35' value='$srow[kolm_puhelin]'></td>
      </tr>";

  $sel = array();
  $sel[$lisrow["rahlaskelma_hetu_kolm_tarkistus"]] = "selected";

  echo "<tr>
        <th>".t("Rinnakkaisostajan henkil�tietojen tarkistus")."</th>
        <td>
        <select name='rahlaskelma_hetu_kolm_tarkistus'>
        <option value=''>".t("Valitse")."</option>
        <option value='A' $sel[A]>".t("Ajokortti")."</option>
        <option value='H' $sel[H]>".t("Henkil�kortti")."</option>
        <option value='P' $sel[P]>".t("Passi")."</option>
        <option value='K' $sel[K]>".t("Kuvallinen pankkikortti")."</option>
        <option value='U' $sel[U]>".t("Kuvallinen Kela-kortti")."</option>
        </select>
        </td></tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkil�tietojen asiakirjan nro / pvm")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_asiakirjanro' size='35' value='$lisrow[rahlaskelma_hetu_kolm_asiakirjanro]'></td>
    </tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkil�tietojen asiakirjan my�nt�ja")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_asiakirjamyontaja' size='35' value='$lisrow[rahlaskelma_hetu_kolm_asiakirjamyontaja]'></td>
    </tr>";

  echo"<tr>
    <th>".t("Rinnakkaisostajan henkil�tietojen tarkastaja / pvm")."</th>
    <td><input type='text' name='rahlaskelma_hetu_kolm_tarkastaja' size='35' value='$lisrow[rahlaskelma_hetu_kolm_tarkastaja]'></td>
    </tr>";

  $sel = array();
  $sel[$lisrow["rahlaskelma_pep"]] = "selected";

    echo "<tr>
          <th>PEP (Politically Exposed Person)</th>
          <td><input type='checkbox' name='pep' value='X' $sel></td>
          </tr>";
    echo "<tr><td class='back'><br><br></td></tr>";

    // Haltijan / k�ytt�j�n tiedot
    echo "<tr><td class='back' colspan='2'><span class='message'>".t("Muu haltija/k�ytt�j�")."</span></td></tr>";

    echo "<tr>
          <th>", t("Henkil�tunnus / Y-tunnus"), ":</th>
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
        <th valign='top'> ".t("S�hk�posti").": </th>
        <td><input type='text' name='kolm_email' size='35' value='$srow[kolm_email]'></td>
        </tr>";

    echo "<tr>
        <th valign='top'>".t("Puhelin")."</th>
        <td><input type='text' name='kolm_puhelin' size='35' value='$srow[kolm_puhelin]'></td>
        </tr>";


  echo "<tr><td class='back'><br><br></td></tr>";

  echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='PAIVIT' value='".t("Tallenna")."'></td></tr>";
  echo "<tr><th>".t("Tallenna").":</th><td><input type='submit' name='VALMIS' value='".t("Tallenna ja siirry takaisin tilaukselle")."'></td></tr>";
  echo "</form>";

  echo "<form name='valmis' action='tulostakopio.php' method='post' name='tulostaform_vakhak' id='tulostaform_vakhak' class='multisubmit'>
      <input type='hidden' name='tee' value='NAYTATILAUS'>
      <input type='hidden' name='otunnus' value='$tilausnumero'>";


  echo "<tr><th>".t("N�yt� lomake").":</th>";

  echo "<td>";
  echo "<select name='toim'>";
  echo "<option value='TARJOUS'>Tarjous</value>";
  echo "<option value='MYYNTISOPIMUS'>Myyntisopimus</value>";
  echo "<option value='OSAMAKSUSOPIMUS'>Osamaksusopimus</value>";
  echo "<option value='LUOVUTUSTODISTUS'>Luovutustodistus</value>";
  echo "<option value='VAKUUTUSHAKEMUS'>Vakuutushakemus</value>";
  echo "<option value='REKISTERIILMOITUS'>Rekister�inti-ilmoitus</value>";
  echo "</select></td>";
  echo "<td><input type='submit' value='".t("N�yt�")."' onClick=\"js_openFormInNewWindow('tulostaform_vakhak', 'tulosta_vakhak'); return false;\"></td>";
  echo "</form></table>";
}