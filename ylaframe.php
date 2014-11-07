<?php

$_GET["ohje"] = "off";

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/parametrit.inc");
elseif (@include_once "parametrit.inc");
else exit;

echo "<div id='ylaframe_big'>";
echo "<table class='ylaframe'>";
echo "<tr>";
echo "<td width='305'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php?toim=change'><img style='padding-left: 15px;' src='{$palvelin2}pics/facelift/logo.png'></a></td>";
echo "<td>$kukarow[nimi]</td>";

echo "<td class='ylapalkki'><a class='puhdas' target='top' href='{$palvelin2}'><img src='{$palvelin2}pics/facelift/koti.png'><br>".t("Etusivu")."</a></td>";

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
  
  echo "<td class='ylapalkki'><a class='puhdas' target='top' href='{$palvelin2}$skriptilisa'><img src='{$palvelin2}pics/facelift/$kuvake'><br>$teksti</a></td>";
}

echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}pikavalinnat.php'><img src='{$palvelin2}pics/facelift/plussa.png'><br>".t("Lis‰‰")."</a></td>";
echo "<td class='ylapalkki' style='padding-left: 15px;'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php'><img src='{$palvelin2}pics/facelift/ratas.png'><br>Exit</a></td>";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<div class='showhide_yla' id='maaginen_yla'><img src='{$palvelin2}pics/lullacons/switch_gray.png'></div>";

echo "
  <script>
      $(document).ready(function(){
        $(\"#maaginen_yla\").click(function(){
           if (parent.document.getElementsByTagName('frameset')[0].rows==\"80,*\") {
             parent.document.getElementsByTagName('frameset')[0].rows=\"20,*\";
             $('#ylaframe_big').hide();
           }
           else {
             parent.document.getElementsByTagName('frameset')[0].rows=\"80,*\";
             $('#ylaframe_big').show();             
           }
        });
      });
      </script>";
