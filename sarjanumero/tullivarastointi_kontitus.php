<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

if (@include_once "../inc/parametrit.inc");
elseif (@include_once "inc/parametrit.inc");

require '../inc/edifact_functions.inc';

if (isset($task) and $task == 'kontitus') {





  $query = "UPDATE tilausrivi SET
            kerattyaika = NOW(),
            keratty = '{$kukarow['kuka']}'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus IN ({$rivitunnukset})";
  pupe_query($query);

  $query = "UPDATE lasku SET
            kerayspvm = NOW(),
            alatila = 'C'
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus = '{$toimitustunnus}'";
  pupe_query($query);

  $viestit[] = t("Er‰") . ' ' . $toimitustunnus . ' ' . t("merkitty ker‰tyksi");

}


$query = "SELECT
          tilausrivi.nimitys,
          tilausrivi.tunnus,
          tuote.malli,
          SUM(tilausrivi.tilkpl) AS kpl,
          lasku.tunnus AS toimitustunnus,
          lasku.nimi
          FROM lasku
          JOIN tilausrivi
            ON tilausrivi.yhtio = lasku.yhtio
            AND tilausrivi.otunnus = lasku.tunnus
          JOIN tuote
            ON tuote.yhtio = lasku.yhtio
            AND tuote.tuoteno = tilausrivi.tuoteno
          JOIN laskun_lisatiedot
            ON laskun_lisatiedot.yhtio = lasku.yhtio
            AND laskun_lisatiedot.otunnus = lasku.tunnus
          WHERE lasku.yhtio = '{$kukarow['yhtio']}'
          AND lasku.viesti = 'tullivarastotoimitus'
          AND lasku.tila = 'L'
          AND lasku.alatila = 'A'
          GROUP BY tilausrivi.tuoteno";
$result = pupe_query($query);

while ($rivi = mysql_fetch_assoc($result)) {

  $toimitukset[$rivi['toimitustunnus']]['asiakas'] = $rivi['nimi'];
  $toimitukset[$rivi['toimitustunnus']]['rivit'][] = $rivi;
}

$otsikko = t("Valitse kontitettava er‰");
$view = 'valinta';

echo "<meta name='viewport' content='width=device-width, maximum-scale=1.0' />\n";
echo "<link rel='stylesheet' type='text/css' href='ipad.css' />\n";
echo "<body>";

echo "<div class='header'>";

echo "<div class='header_left'>";
echo "<a href='index.php' class='button header_button'>";
echo t("P‰‰valikko");
echo "</a>";
echo "</div>";

echo "<div class='header_center'>";
echo "<h1>";
echo $otsikko;
echo "</h1>";
echo "</div>";

echo "<div class='header_right'>";
echo "<a href='{$palvelin2}logout.php?location={$palvelin2}sarjanumero' class='button header_button'>";
echo t("Kirjaudu ulos");
echo "</a>";
echo "</div>";

echo "</div>";
echo "<div style='text-align:center;padding:10px 0 0 0; margin:0 auto;'>";
echo "<div style='text-align:center;width: 700px; margin:20px auto;'>";


if (count($viestit) > 0) {
  echo "<div class='viesti' style='text-align:center'>";
  foreach ($viestit as $viesti) {
    echo $viesti."<br>";
  }
  echo "<br></div>";
}



if ($view == 'valinta') {

  foreach ($toimitukset as $toimitustunnus => $toimitus) {

    echo "<form method='post' action=''><div style='margin-bottom:10px; background:silver;   border-radius: 5px;'>";
    echo "<table border='0' cellspacing='5' cellpadding='0'>";
    echo "<tr>";
    echo "<td valign='top' style='background:white; padding:5px; margin:5px; width:190px;  border-radius: 3px; text-align:left; line-height:20px;'>";
    echo $toimitus['asiakas'] . "<br>";
    echo t("Numero: ") . $toimitustunnus . "<br>";
    echo "</td>";
    echo "<td valign='top' style=' padding:0px; margin:0px; width:430px; border-radius: 3px;'>";

    $rivitunnukset = '';

    foreach ($toimitus['rivit'] as $toimitusrivi) {

      echo "<div style='text-align:left; padding:10px; background:#e7e7e7; border-radius: 3px; margin:3px; '>";
      echo "<div style='text-align:left;display:inline-block; width:150px;'>";
      echo "<input style='font-size:1em; width:60px;' type ='text' name='kpl[".$toimitusrivi['tunnus']."]' value='" . (int) $toimitusrivi['kpl'] . "' />";
      echo "<input type='hidden' name='alku_kpl[".$toimitusrivi['tunnus']."]' value='" . (int) $toimitusrivi['kpl'] . "' />";
      echo "&nbsp;" . t("kpl");
      echo "</div>";
      echo "<div style='text-align:left;display:inline-block; margin-right:20px;'>";
      echo $toimitusrivi['nimitys'] . ' - ' . $toimitusrivi['malli'];
      echo "</div>";
      echo "</div>";

      $rivitunnukset .= $toimitusrivi['tunnus'] . ',';
    }
    echo "</td>";
    echo "<td style='background:silver; padding:5px; margin:5px; border-radius: 3px;'>";

    $rivitunnukset = rtrim($rivitunnukset, ',');

    echo "
        <input type='hidden' name='rivitunnukset' value='{$rivitunnukset}' />
        <input type='hidden' name='toimitustunnus' value='{$toimitustunnus}' />
        <input type='hidden' name='konttimaara' value='{$toimitus['konttimaara']}' />
        <button name='task' value='kontitus' onclick='submit();' class='button'>&#10145;</button>
      </form>";
    echo "</td>";

    echo "</tr>";
    echo "</table>";
    echo "</div>";
  }

  echo "</div>";

  echo "<script type='text/javascript'>";

  echo "

    $('.tapfocus').bind('touchstart',function(){
      $('input').focus();
      $('input').setSelectionRange(0, 9999);
    });

  </script>";

}

require 'inc/footer.inc';
