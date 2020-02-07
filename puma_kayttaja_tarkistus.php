<?php
// Tarkistellaan k‰yt‰tt‰jien asetukset

require "inc/parametrit.inc";

if (!(isset($toimip))) {
  $toimip = '';
}

echo "<font class='head'>".t("K‰ytt‰jien asetusten tarkistus")."</font><hr>";

echo "<p>".t("Listasta voidaan tarkistaa toimipisteen k‰ytt‰j‰t sek‰ varmistaa ett‰ heill‰ on toimipistett‰‰n vastaavat asetukset").".</p>";

// Tehd‰‰n toimipaikkavalikko

$qu = "SELECT tunnus, nimi FROM yhtion_toimipaikat WHERE yhtio = '{$kukarow['yhtio']}' ORDER BY nimi";
$re = pupe_query($qu);

echo "<form>
    <label>".t("Toimipaikka").": </label>
    <select name='toimip'>
      <option value=''>".t("Valitse")."...</option>
      <option value='kaikki'>".t("N‰yt‰ kaikki")."</option>";
$sel = '';
while ($row = mysql_fetch_assoc($re)) {
  $sel = $row['tunnus'] == $toimip ? " selected" : "";
  echo "<option value='{$row['tunnus']}'$sel>{$row['nimi']}</option>";
}

echo "  </select>
    <input type='submit' value='Hae' />
  </form>";

if ($toimip != '') {

  if ($toimip == 'kaikki') {
    $toimipaikka = "kuka.toimipaikka > 0";
  }
  else {
    if ((int)$toimip == 0) {
      // security + suotta n‰ytell‰‰n toimipaikattomia k‰ytt‰ji‰
      require 'inc/footer.inc';
      exit;
    }

    $toimipaikka = "kuka.toimipaikka = '$toimip'";
  }

  $qu = "SELECT
         yhtion_toimipaikat.nimi 'toimipaikka',
         kuka.kuka,
         kuka.nimi 'k‰ytt‰j‰n nimi',
         varastopaikat.nimitys 'Varaston nimi',
         if(kuka.oletus_varasto=varastopaikat.tunnus, 'OK', '') 'oletusvarasto',
         if(kuka.oletus_ostovarasto=varastopaikat.tunnus, 'OK', '') 'oletus_ostovarasto',
         if(kuka.varasto=varastopaikat.tunnus, 'OK', '') 'varaston oikeudet',
         kuka.eposti,
         if(kuka.kassalipas_otto=kassalipas.tunnus, 'OK', '') 'kassalipas',
         if(lisa.selite=kuka.kuka, k2.kuka, '') as 'orumnet_linkitys'
         FROM kuka
         LEFT JOIN yhtion_toimipaikat ON ( yhtion_toimipaikat.tunnus = kuka.toimipaikka )
         LEFT JOIN varastopaikat ON (
         varastopaikat.yhtio       = 'atarv' AND
         varastopaikat.toimipaikka = kuka.toimipaikka
         )
         LEFT JOIN extranet_kayttajan_lisatiedot lisa ON (
         lisa.yhtio                = 'artr' AND
         lisa.laji                 = 'LINKITYS' AND
         lisa.selite               = kuka.kuka
         )
         LEFT JOIN kuka k2 ON ( k2.tunnus = lisa.liitostunnus )
         LEFT JOIN kassalipas ON (
         kassalipas.yhtio          = 'atarv' AND
         kassalipas.toimipaikka    = kuka.toimipaikka
         )
         WHERE
         kuka.yhtio                = 'atarv' AND
         $toimipaikka
         ";

  $re = pupe_query($qu);

  if (mysql_num_rows($re) == 0) {
    echo "<p>".t("Ei k‰ytt‰ji‰")."!</p>";
  }
  else {

    echo "<table>";
    echo "<tr>
        <th>".t("Toimipaikka")."</th>
        <th>".t("k‰ytt‰j‰nimi")."</th>
        <th>".t("Nimi")."</th>
        <th>".t("Varasto")."</th>
        <th>".t("oletusvarasto")."</th>
        <th>".t("oletus_ostovarasto")."</th>
        <th>".t("Varaston oikeudet")."</th>
        <th>".t("sposti")."</th>
        <th>".t("kassalipas")."</th>
        <th>".t("÷rumnet linkki")."</th>
      </tr>";

    while ($row = mysql_fetch_assoc($re)) {
      echo "<tr>";
      foreach ($row as $col) {
        echo "<td>$col</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  }
}

require "inc/footer.inc";
