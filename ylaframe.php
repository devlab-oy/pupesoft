<?php

$_GET["ohje"] = "off";

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/parametrit.inc");
elseif (@include_once "parametrit.inc");
else exit;

echo "<div id='ylaframe_big'>";
echo "<table class='ylaframe'>";
echo "<tr>";
echo "<td width='305'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php?toim=change'><img style='padding-left: 15px;' src='{$palvelin2}pics/facelift/logo.png' alt='logo'></a></td>";
echo "<td>$kukarow[nimi]</td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}tervetuloa.php'><img src='{$palvelin2}pics/facelift/koti.png' alt='logo'></a></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}muokkaatilaus.php'><img src='{$palvelin2}pics/facelift/graafi.png' alt='logo'></a></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}ulask.php'><img src='{$palvelin2}pics/facelift/kalenteri.png' alt='logo'></a></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}muutosite.php'><img src='{$palvelin2}pics/facelift/ratas.png' alt='logo'></a></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}tuote.php'><img src='{$palvelin2}pics/facelift/palkit.png' alt='logo'></a></td>";
echo "<td class='ylapalkki'><a class='puhdas' target='mainframe' href='{$palvelin2}kayttajat.php'><img src='{$palvelin2}pics/facelift/plussa.png' alt='logo'><br>".t("Lis‰‰")."</a></td>";


echo "<td class='ylapalkki' style='padding-left: 15px;'><a class='puhdas' target='mainframe' href='{$palvelin2}logout.php'><img src='{$palvelin2}pics/facelift/ratas.png' alt='logo'><br>Exit</a></td>";




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
