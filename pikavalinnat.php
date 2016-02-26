<?php

if (@include "inc/parametrit.inc");
elseif (@include "parametrit.inc");
else exit;

echo "<font class='head'>".t("Muokkaa pikavalintoja").":</font><hr>";

$kuvakkeet_img = array (
  "Kalenteri"    => "calendar.png",
  "Crm"          => "crm-2.png",
  "Crm-sydän"    => "crm.png",
  "Lisää_useita" => "icon-add-multiple.png",
  "Lisää"        => "icon-add.png",
  "Palkit"       => "icon-adjust.png",
  "Tiedosto"     => "icon-document.png",
  "Alas"         => "icon-download.png",
  "Koti"         => "icon-home.png",
  "Säätimet"     => "icon-setting.png",
  "Struktuuri"   => "icon-structure.png",
  "Lamppu"       => "icon-tip.png",
  "Ylös"         => "icon-upload.png",
  "Vähennä"      => "icon-vahenna.png",
  "Myynti"       => "myynti.png",
  "Neula"        => "pin.png",
  "Ratas"        => "ratas.png",
  "Laskin"       => "calc.png");

if ($tee == "tallenna") {
  foreach ($skriptit as $i => $skripti) {

    if ($jarjestykset[$i] == "") {
      $jarjestykset[$i] = 99;
    }

    // Tsekataan, että kaikki tiedot on syötetty
    if (empty($skriptit[$i]) or empty($kuvakkeet[$i]) or empty($tekstit[$i])) {
      unset($skriptit[$i]);
      unset($kuvakkeet[$i]);
      unset($tekstit[$i]);
      unset($jarjestykset[$i]);
    }
  }

  asort($jarjestykset);

  $jarj = 1;
  $tallennettavat = array();

  // laitetaan järjestykset kuntoon
  foreach ($jarjestykset as $i => $j) {
    $tallennettavat["skriptit"][]     = $skriptit[$i];
    $tallennettavat["kuvakkeet"][]    = $kuvakkeet[$i];
    $tallennettavat["tekstit"][]      = $tekstit[$i];
    $tallennettavat["jarjestykset"][] = $jarj;
    $jarj++;
  }

  $tallennettavat = serialize($tallennettavat);
}

if ($tee == "tallenna") {
  $query = "INSERT into  extranet_kayttajan_lisatiedot set
            yhtio        = '$kukarow[yhtio]',
            laatija      = '$kukarow[kuka]',
            luontiaika   = now(),
            muuttaja     = '$kukarow[kuka]',
            muutospvm    = now(),
            laji         = 'PIKAVALINTA',
            liitostunnus = '{$kukarow['tunnus']}',
            selite       = 'PIKAVALINTA',
            selitetark   = '$tallennettavat'
            ON DUPLICATE KEY UPDATE
            muuttaja     = '$kukarow[kuka]',
            muutospvm    = now(),
            selitetark   = '$tallennettavat' ";
  pupe_query($query);


  echo "<script>";
  echo "parent.ylaframe.location.href = 'ylaframe.php';";
  echo "</script>";

}

echo "<form method='post' action='pikavalinnat.php'>";
echo "<input type='hidden' name='tee' value='tallenna'>";

echo "<table>";
echo "<tr>
      <th>".t("Ohjelma")."</th>
      <th>".t("Kuvake")."</th>
      <th>".t("Teksti")."</th>
      <th>".t("Järjestys")."</th>
      </tr>";


if ($kukarow["extranet"] != "") {
  $sovellus_rajaus = " and sovellus like 'Extranet%' ";
}
else {
  $sovellus_rajaus = " and sovellus not like 'Extranet%' ";
}

$query = "SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
          FROM oikeu
          WHERE yhtio = '$kukarow[yhtio]'
          and kuka    = '$kukarow[kuka]'
          {$sovellus_rajaus}
          GROUP BY sovellus, nimi, alanimi
          ORDER BY sovellus, jarjestys, jarjestys2";
$oikeures = pupe_query($query);

$query = "SELECT *
          FROM extranet_kayttajan_lisatiedot
          WHERE yhtio      = '{$kukarow['yhtio']}'
          AND laji         = 'PIKAVALINTA'
          AND liitostunnus = '{$kukarow['tunnus']}'
          ORDER BY selite+0";
$result = pupe_query($query);
$row = mysql_fetch_assoc($result);

$tallennetut = unserialize($row['selitetark']);

foreach ($tallennetut["skriptit"] as $i => $skripti) {

  $kuvake    = $tallennetut["kuvakkeet"][$i];
  $teksti    = $tallennetut["tekstit"][$i];
  $jarjestys = $tallennetut["jarjestykset"][$i];

  echo "<tr>";
  echo "<td><select name='skriptit[]'><option value=''>".t("Valitse ohjelma")."</option>";

  $sel = "";
  if (isset($skripti) and $skripti == "LASKIN") {
    $sel = "SELECTED";
  }

  echo "<option value='LASKIN' $sel>Pupesoft-laskin</option>";

  while ($oikeurow = mysql_fetch_assoc($oikeures)) {
    $sel = "";
    if (isset($skripti) and $skripti == $oikeurow["sovellus"]."###".$oikeurow["nimi"]."###".$oikeurow["alanimi"]) {
      $sel = "SELECTED";
    }

    echo "<option value='$oikeurow[sovellus]###$oikeurow[nimi]###$oikeurow[alanimi]' $sel>$oikeurow[sovellus] --> $oikeurow[nimitys]</option>";
  }

  mysql_data_seek($oikeures, 0);

  echo "</select></td>";
  echo "<td><select name='kuvakkeet[]'><option value=''>".t("Valitse kuvake")."</option>";

  foreach ($kuvakkeet_img as $kuvanimi => $kuva) {
    $sel = "";
    if (isset($kuvake) and $kuvake == $kuva) {
      $sel = "SELECTED";
    }

    echo "<option value='$kuva' $sel>".t($kuvanimi)."</option>";
  }

  echo "</select></td>";
  echo "<td><input type='text' name='tekstit[]' value='$teksti' size='30'></td>";
  echo "<td><input type='text' name='jarjestykset[]' value='$jarjestys' size='4'></td>";
  echo "<td class='back'><input type='submit' class='tallenna_btn' value='".t("Tallenna")."'></td>";
  echo "</tr>";
}

echo "<tr>";
echo "<td><select name='skriptit[]'><option value=''>".t("Valitse ohjelma")."</option>";
echo "<option value='LASKIN'>Pupesoft-laskin</option>";

while ($oikeurow = mysql_fetch_assoc($oikeures)) {
  echo "<option value='$oikeurow[sovellus]###$oikeurow[nimi]###$oikeurow[alanimi]'>$oikeurow[sovellus] --> $oikeurow[nimitys]</option>";
}

echo "</select></td>";
echo "<td><select name='kuvakkeet[]'><option value=''>".t("Valitse kuvake")."</option>";

foreach ($kuvakkeet_img as $kuvanimi => $kuva) {
  echo "<option value='$kuva'>".t($kuvanimi)."</option>";
}

echo "</select></td>";
echo "<td><input type='text' name='tekstit[]' value='' size='30'></td>";
echo "<td><input type='text' name='jarjestykset[]' value='' size='4'></td>";
echo "<td class='back'><input type='submit' class='lisaa_btn' value='".t("Lisää")."'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

echo "<br><br><br>Käytettävissä olevat kuvakkeet:<br><br>";


echo "<table><tr>";
$kala = 0;

foreach ($kuvakkeet_img as $kuvanimi => $kuva) {
  echo "<td><img src='{$palvelin2}pics/facelift/icons/$kuva'> ".t($kuvanimi)."</td>";

  $kala++;

  if ($kala % 4 == 0) {
    echo "</tr><tr>";
  }
}

echo "</tr></table>";

require "inc/footer.inc";
