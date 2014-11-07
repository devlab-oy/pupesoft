<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Muokkaa pikavalintoja").":</font><hr>";

$kuvakkeet_img = array ("Käppyrä"   => "graafi.png",
                        "Kalenteri" => "kalenteri.png",
                        "Koti"      => "koti.png",
                        "Pylväät"   => "palkit.png",
                        "Ratas"     => "ratas.png");

if ($tee == "tallenna") {
  foreach($skriptit as $i => $skripti) {
    // Tsekataan, että kaikki tiedot on syötetty
    if (empty($skriptit[$i]) or empty($kuvakkeet) or empty($tekstit)) {
        unset($skriptit[$i]);
        unset($kuvakkeet[$i]);
        unset($tekstit[$i]);
    }
  }

  $tallennettavat = serialize(array("skriptit"     => $skriptit,
                                    "kuvakkeet"    => $kuvakkeet,
                                    "tekstit"      => $tekstit));

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
}

echo "<form method='post' action='pikavalinnat.php'>";
echo "<input type='hidden' name='tee' value='tallenna'>";

echo "<table>";
echo "<tr>
      <th>".t("Ohjelma")."</th>
      <th>".t("Kuvake")."</th>
      <th>".t("Teksti")."</th>
      </tr>";

$query = "SELECT sovellus, nimi, alanimi, min(nimitys) nimitys, min(jarjestys) jarjestys, min(jarjestys2) jarjestys2, max(hidden) hidden
          FROM oikeu
          WHERE yhtio  = '$kukarow[yhtio]'
          and kuka     = ''
          and profiili = ''
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

  echo "<tr>";
  echo "<td><select name='skriptit[]'><option value=''>".t("Valitse ohjelma")."</option>";

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
  echo "<td class='back'><input type='Submit' class='tallenna_btn' value='".t("Tallenna")."'></td>";
  echo "</tr>";
}

echo "<tr>";
echo "<td><select name='skriptit[]'><option value=''>".t("Valitse ohjelma")."</option>";

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
echo "<td class='back'><input type='Submit' class='lisaa_btn' value='".t("Lisää")."'></td>";
echo "</tr>";

echo "</table>";
echo "</form>";

echo "<br><br><br>Käytettävissä olevat kuvakkeet:<br><br>";

foreach ($kuvakkeet_img as $kuvanimi => $kuva) {
  echo "<img src='{$palvelin2}pics/facelift/$kuva'> $kuvanimi<br>";
}

require "inc/footer.inc";
