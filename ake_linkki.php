<?php

require "inc/parametrit.inc";
require "inc/tecdoc.inc";

// funktio joka tekee rekisteriliitoksen, taman voi siirtaa functions.php:hen sitten kun tarvii muissakin ohjelmissa
function rekisteriliitos($rekno, $autoid, $type) {
  global $kukarow, $yhtiorow;

  if ($rekno != '' and $autoid > 0 and $type != '') {
    /* poistetaan eka vanha */
    $remq = "DELETE FROM yhteensopivuus_rekisteri WHERE yhtio = '{$yhtiorow['yhtio']}' AND rekno = '{$rekno}'";
    $re = pupe_query($remq);

    $addq = "INSERT INTO yhteensopivuus_rekisteri (yhtio, maa, rekno, ajoneuvolaji, autoid, laatija, luontiaika, muutospvm, muuttaja)
             VALUES('{$yhtiorow['yhtio']}', 'fi', '$rekno', 0, $autoid, '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
    $re = pupe_query($addq);

    $updateq = "UPDATE rekisteritiedot
                SET kohdistettu = 'X'
                WHERE yhtio = '{$yhtiorow['yhtio']}' AND rekno = '{$rekno}'";
    $re = pupe_query($updateq);
  }
}


echo "<font class='head'>".t("Yhdistä rekisteri yhteensopivuuksiin")."</font><hr />";

// default type = pc
$type = $type == '' ? 'pc' : $type;

if ($tee == 'tallenna' and $rekno != '' and $selversion != '') {
  rekisteriliitos($rekno, $selversion, "pc");
}

if (isset($rekno) and $rekno != '') {
  $qu = "SELECT *
         FROM rekisteritiedot
         WHERE yhtio = '{$yhtiorow['yhtio']}'
         AND rekno   = '{$rekno}'";
  $re = pupe_query($qu);

  if (mysql_num_rows($re) > 0) {
    $rekrow = mysql_fetch_assoc($re);

    $rekrow['oma_massa'] = (int)$rekrow['oma_massa'];
    $rekrow['kok_massa'] = (int)$rekrow['kok_massa'];
    $rekrow['pituus'] = (int)$rekrow['pituus'];
    $rekrow['hp'] = (int)($rekrow['teho'] * 1.36);

    $rekrow['kayttoonotto_siisti'] = substr($rekrow['kayttoonotto'], 6, 2).".".substr($rekrow['kayttoonotto'], 4, 2).".".substr($rekrow['kayttoonotto'], 0, 4);

    echo "<div><table>
          <tr>
            <th>".t("Malli")."</th><td colspan='5'>{$rekrow['merkki']} {$rekrow['malli']}</td>
            <th>".t("Käyttöönottopvm")."</th><td>{$rekrow['kayttoonotto_siisti']}</td>
          </tr>
          <tr>
            <th>".t("moottorin tilavuus")."</th><td colspan='5'>{$rekrow['moottorin_til']} L</td>
            <th>".t("Valmistenumero")."</th><td>{$rekrow['valmistenumero']}</td>
          </tr>
          <tr>
            <th>".t("Käyttövoima")."</th><td colspan='5'>{$rekrow['k_voima']}</td>
            <th>".t("Tyyppikoodi")."</th><td>{$rekrow['tyyppikoodi']}</td>
          </tr>
          <tr>
            <th>".t("Teho")."</th><td colspan='5'>{$rekrow['teho']} kW / {$rekrow['hp']} Hp</td>
            <th>".t("variantti")."</th><td>{$rekrow['variantti']}</td>
          </tr>
          <tr>
            <th>".t("Omamassa")." / ".t("kok. massa")."</th><td colspan='5'>{$rekrow['oma_massa']} / {$rekrow['kok_massa']} kg</td>
            <th>".t("versio")."</th><td>{$rekrow['versio']}</td>
          </tr>
          <tr>
            <th>".t("Pituus")."</th><td colspan='3'>{$rekrow['pituus']}</td>
            <th>".t("Vetävät akselit")."</th><td>{$rekrow['vetavat_akselit']}</td>
            <th>".t("moottoritunnus")."</th><td>{$rekrow['moottoritunnus']}</td>

          </tr>
          <tr>
            <th>".t("ajoneuvolaji")."</th><td>{$rekrow['ajoneuvolaji']}</td>
            <th>".t("rinnakkaistuonti")."</th><td>{$rekrow['rinnakkaistuonti']}</td>
            <th>".t("Vähäpäästöisyys")."</th><td>{$rekrow['vahapaastoisyys']}</td>
            <th>EU ".t("tyyppinumero")."</th><td>{$rekrow['EU_tyyppinumero']}</td>
          </tr>
          <tr>
            <th>".t("Renkaat")."</th><td colspan='8'>{$rekrow['renkaat']}</td>
          </tr>
        </table></div><hr />";
  }
}

// haetaan rekno-ajoneuvo liitos
if ($rekno != '') {
  $qu = "SELECT *
         FROM yhteensopivuus_rekisteri
         WHERE yhtio = '{$yhtiorow['yhtio']}'
         AND rekno   = '{$rekno}'";
  $re = pupe_query($qu);

  if (mysql_num_rows($re) > 1) {
    $msg = t("Löytyi useita, tallenna oikea liitos niin ylimääräiset liitokset poistetaan").".";
  }

  $liitos = mysql_fetch_assoc($re);

  $td_liitos = td_getversion(array('tyyppi' => $type, 'autoid' => $liitos['autoid']));
  $td_liitos = mysql_fetch_assoc($td_liitos);

  // laitetaan tiedot muuttujiin jotta uutta ajoneuvoa etsiessa paastaan suoraan liitettyyn ajoneuvoon
  if (($selbrand == '' and $selmodel == '' and $selversion == '') or $rekno != $prevrekno) {
    $selbrand = $td_liitos['manuid'];
    $selmodel = $td_liitos['modelno'];
    $selversion = $td_liitos['autoid'];
  }
}

if ($type == 'pc') { $sel_pc = 'checked'; $sel_cv = ''; }
elseif ($type == 'cv') { $sel_cv = 'checked'; $sel_pc = ''; }

if ($msg != '') {
  echo "<p class='error'>$msg</p>";
}

echo "<form id='pickvehicle' name='pickvehicle' method='GET'>
      <table>
        <tr>
          <th>".t("Rekisterinumero")."</th>
          <td>
            <input type='text' name='rekno' value='{$rekno}'/>
            <input type='hidden' name='prevrekno' value='{$rekno}' />
            <input type='submit' value='Hae' />
          </td>
        </tr>
        <tr>
          <th>".t("Ajoneuvotyyppi")."</th>
          <td><input class='td' type='radio' name='type' {$sel_pc} value='pc' /><label>HA</label>&nbsp;&nbsp;&nbsp;<input disabled='disabled' class='td' type='radio' name='type' {$sel_cv} value='cv' /><label>RA</label></td>
        </tr>
        <tr><th>".t("Valitse ajoneuvo")."</th><td>";


$brands = td_getbrands(array('tyyppi' => $type));

if ($brands != FALSE) {

  echo "<select class='td' name='selbrand'><option value=''>".t("valitse merkki")."</option>";

  while ($brandrow = mysql_fetch_assoc($brands)) {

    $selected = $brandrow["manuid"] == $selbrand ? "selected='selected'" : "";

    echo "<option value='{$brandrow['manuid']}' {$selected}>{$brandrow['name']}</option>";
  }

  echo "</select>";

}


if ($selbrand != "") {

  echo "<select class='td' name='selmodel'><option value=''>".("Valitse malli")."</option>";

  $models = td_getmodels(array('tyyppi' => $type, 'merkkino' => $selbrand));

  if ($models != FALSE) {

    while ($modelrow = mysql_fetch_assoc($models)) {

      $selected = $modelrow["modelno"] == $selmodel ? "selected='selected'" : "";
      echo "<option value='{$modelrow['modelno']}' {$selected}>{$modelrow['modelname']} ".td_niceyear($modelrow['vma'], $modelrow['vml'])."</option>";
    }

  }

  echo "</select>";

}

echo "</td></tr></table>";

if ($selmodel != "") {
  $versions = td_getversion(array('tyyppi' => $type, 'mallino' => $selmodel));

  if ($versions != FALSE) {
    echo "<table>
          <tr>
            <th></th>
            <th>autoid</th>
            <th>".t("Versio")."</th>
            <th>".t("moottorikoodit")."</th>
            <th>".t("Vm")."</th>
            <th>kw / hp</th>
            <th>cc</th>
            <th>cyl</th>
            <th>valves</th>
            <th>capltr</th>
            <th>enginetype</th>
            <th>fuelmixture</th>
            <th>Drivetype</th>
            <th>".t("Korityyppi")."</th>
          </tr>";

    while ($versionrow = mysql_fetch_assoc($versions)) {

      $onliitos = $liitos['autoid'] == $versionrow['autoid'] ? "checked" : "";
      $onliitos_style = $onliitos != '' ? " style='background:lightgreen'" : "";

      echo "<tr{$onliitos_style}>
            <td><input type='radio' value='{$versionrow['autoid']}' name='selversion' {$onliitos} /></td>
            <td>{$versionrow['autoid']}</td>
            <td>{$versionrow['version']}</td>
            <td>{$versionrow['mcodes']}</td>
            <td>".td_niceyear($versionrow['vma'], $versionrow['vml'])."</td>
            <td>{$versionrow['kw']} / {$versionrow['hp']}</td>
            <td>{$versionrow['cc']}</td>
            <td>{$versionrow['cyl']}</td>
            <td>{$versionrow['valves']}</td>
            <td>{$versionrow['capltr']}</td>
            <td>{$versionrow['enginetype']}</td>
            <td>{$versionrow['fuelmixture']}</td>
            <td>{$versionrow['drivetype']}</td>
            <td>{$versionrow['bodytype']}</td>
          </tr>";
    }

    if ($rekrow['rekno'] == '') $disabled = 'disabled';

    echo "<tr>
            <td>
              <input {$disabled} type='submit' value='Tallenna' id='tallenna' />
              <input type='hidden' name='tee' id='tee' />
            </td>
            <td colspan='17'></td>
          </tr>";
    echo "</table>";
  }
}

echo "</form>";

?>
  <script language="javascript">

    jQuery('.td').change(function () {
      jQuery('#pickvehicle').submit();
    });

    jQuery('#tallenna').click(function() {
      jQuery('#tee').val('tallenna');
    });

  </script>
  <?php

require "inc/footer.inc";
