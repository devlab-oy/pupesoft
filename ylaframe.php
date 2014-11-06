<?php

$_GET["ohje"] = "off";

// haetaan jotain tarpeellisia funktioita mukaan..
if (@include_once "inc/parametrit.inc");
elseif (@include_once "parametrit.inc");
else exit;

echo "<table class='ylaframe'>";
echo "<tr>";
echo "<td width='305'><a class='puhdas' target='main' href='{$palvelin2}logout.php?toim=change'><img border='0' src='{$palvelin2}pics/logo.png' alt='logo'></a></td>";
echo "<td>$kukarow[nimi]</td>";
echo "<td><a class='puhdas' target='main' href='{$palvelin2}tervetuloa.php'><img border='0' src='{$palvelin2}pics/koti.png' alt='logo'></a></td>";
echo "<td><a class='puhdas' target='main' href='{$palvelin2}muokkaatilaus.php'><img border='0' src='{$palvelin2}pics/koti.png' alt='logo'></a></td>";
echo "<td><a class='puhdas' target='main' href='{$palvelin2}ulask.php'><img border='0' src='{$palvelin2}pics/koti.png' alt='logo'></a></td>";
echo "<td><a class='puhdas' target='main' href='{$palvelin2}muutosite.php'><img border='0' src='{$palvelin2}pics/koti.png' alt='logo'></a></td>";
echo "<td><a class='puhdas' target='main' href='{$palvelin2}tuote.php'><img border='0' src='{$palvelin2}pics/koti.png' alt='logo'></a></td>";
echo "</tr>";
echo "</table>";

echo "<div class='showhide_yla' id='maaginen_yla'><img src='{$palvelin2}pics/alas.gif'></div>";

echo "
  <script>
      $(document).ready(function(){
        $(\"#maaginen_yla\").click(function(){
           if (parent.document.getElementsByTagName('frameset')[0].rows==\"115,*\") {
             parent.document.getElementsByTagName('frameset')[0].rows=\"20,*\";
           }
           else {
             parent.document.getElementsByTagName('frameset')[0].rows=\"115,*\";
           }
        });
      });
      </script>";
