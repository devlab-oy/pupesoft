<?php

$_GET["ohje"] = "off";

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/parametrit.inc");
elseif (@include_once "parametrit.inc");
else exit;

$hiddenlisa = "";

if (isset($_COOKIE["yla_frame_showhide"]) and $_COOKIE["yla_frame_showhide"] == "hidden") {
  $hiddenlisa = " style='display: none;'";
}

echo "<div id='ylaframe_container'{$hiddenlisa}>";
echo "<table class='ylaframe'>";
echo "<tr>";

if ($kukarow["extranet"] != "") {
  $logokuva = "extranet_logo.png";
}
else {
  $logokuva = "logo.png";
}

echo "<td style='width: 175px; padding-right: 0;'>
        <a class='puhdas' target='mainframe' href='{$palvelin2}logout.php?toim=change'>
        <img style='padding-left: 15px;' src='{$palvelin2}pics/facelift/{$logokuva}'>
        </a>
      </td>";

unset($isizelogo);

if ((int) $yhtiorow["logo"] > 0) {
  $liite = hae_liite($yhtiorow["logo"], "Yllapito", "array");

  if ($liite !== false) {
    $isizelogo[0] = $liite["image_width"];
    $isizelogo[1] = $liite["image_height"];
  }
  unset($liite);
}

if (isset($isizelogo) and is_array($isizelogo)) {
  $logo = "view.php?id=".$yhtiorow["logo"];
  $ix   = $isizelogo[0];      // kuvan x
  $iy   = $isizelogo[1];      // kuvan y

  if ($iy > 40) {
    $koko = "height='40'";
  }
  else {
    $koko = "height='$iy'";
  }

  echo "<td style='width: 1px; padding: 0; margin: 0;'><img src='{$palvelin2}pics/facelift/divider.png'></td>";
  echo "<td style='width: 20px; padding: 0; margin: 0;'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php?toim=change'><img style='padding-left: 15px;' border='0' src='$logo' alt='logo' $koko ></a></td>";
}

echo "<td style='width: 1px; padding: 0; margin: 0; padding-left: 15px;'><img src='{$palvelin2}pics/facelift/divider.png'></td>";
echo "<td style='padding-left: 15px;'>$yhtiorow[nimi]<br>$kukarow[nimi]</td>";
echo "<td class='ylapalkki'><a class='puhdas' target='_top' href='{$palvelin2}'><img src='{$palvelin2}pics/facelift/icons/icon-home.png'><br>".t("Etusivu")."</a></td>";

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

  list($goso, $go, $golisa) = explode("###", $skripti);

  $skriptilisa = "?goso=$goso&go=$go";

  if (!empty($golisa)) {
    $skriptilisa .= "?toim=".$golisa;
  }

  echo "<td class='ylapalkki'><a class='puhdas' target='_top' href='{$palvelin2}$skriptilisa'><img src='{$palvelin2}pics/facelift/icons/$kuvake'><br>$teksti</a></td>";
}

echo "<td style='padding: 0px; text-align: center;'><img src='{$palvelin2}pics/facelift/divider.png'></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}pikavalinnat.php'><img src='{$palvelin2}pics/facelift/plussa.png'><br>".t("Lis��")."</a></td>";
echo "<td style='padding: 0px; text-align: center;'><img src='{$palvelin2}pics/facelift/divider.png'></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php'><img src='{$palvelin2}pics/facelift/icon-exit.gif'><br>Exit</a></td>";
echo "</tr>";
echo "</table>";
echo "</div>";

if (isset($_COOKIE["yla_frame_showhide"]) and $_COOKIE["yla_frame_showhide"] == "hidden") {
  echo "<div class='showhide_yla' id='maaginen_yla'><img id='showhide_upper' src='{$palvelin2}pics/facelift/show_upper.png'></div>";
}
else {
  echo "<div class='showhide_yla' id='maaginen_yla'><img id='showhide_upper' src='{$palvelin2}pics/facelift/hide_upper.png'></div>";
}

echo "
  <script>
      $(document).ready(function(){
        $('#maaginen_yla').click(function(){
           if (parent.document.getElementsByTagName('frameset')[0].rows=='90,*') {
             parent.document.getElementsByTagName('frameset')[0].rows='20,*';
             $('#showhide_upper').attr('src', '{$palvelin2}pics/facelift/show_upper.png');
             $('#ylaframe_container').hide();
             document.cookie = \"yla_frame_showhide=hidden;7\";
           }
           else {
             parent.document.getElementsByTagName('frameset')[0].rows='90,*';
             $('#showhide_upper').attr('src', '{$palvelin2}pics/facelift/hide_upper.png');
             $('#ylaframe_container').show();
             document.cookie = \"yla_frame_showhide=;7\";
           }
        });
      });
      </script>";
