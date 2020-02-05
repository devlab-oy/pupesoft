<?php

// mobiilitilasto.php
// Myyntitilastot Pupesta ja Unikosta kauniissa muodossa mobiilivehkeisiin
// -Henkka
//
// Kirjaudu pupeen näin:,
// https://pupe.palvelin.com/pupesoft/jotain/mobiilitilasto.php?user=UUSERI&pass=PASSWORDI&yhtio=YHTIÖ
// Vaatii sen, että ohjelma löytyy Pupen valikoista ja käyttäjällä on käyttöoikeus softaan
// -juppe

$no_head = "yes";

require "../inc/parametrit.inc";

function jqtoolbar($title, $back) {
  echo "<div class='toolbar'><h1>$title</h1>";
  if ($back != "") echo "<a class='back' href='#$back'>Paluu</a>";
  echo "</div>";
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <script type="text/javascript" src="../inc/jquery.min.js"></script>
  <script src="jqtouch/jqtouch.min.js" type="application/x-javascript" charset="utf-8"></script>
  <style type="text/css" media="screen">@import "jqtouch/jqtouch.min.css";</style>
  <style type="text/css" media="screen">@import "themes/jqt/theme.min.css";</style>
<style type="text/css">
h2 {
  color: #ffffff;
  text-shadow: rgba(255,255,255,.2) 0 5px 5px;
}
table {
  width: 100%;
  padding: 2px;
  border-collapse: collapse;
  font-size: 12pt;
  }
th, td { margin: 4px; }
thead {
  text-align: left;
  color:#3E9AC3;
  font-size:16px;
  text-shadow:0 1px 0 #000000;
  background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgba(0,0,0,0)), to(rgba(0,0,0,.5)));
}
td {
  background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#1e1f21), to(#272729));
  border-bottom: 2px solid #000;
  border-top: 1px solid #4a4b4d;
}
span.prcnt {font-size: 12px;}
</style>
<script type="text/javascript" language="javascript">
$.jQTouch({
    icon: 'jqtouch.png',
    statusBar: 'black-translucent',
    preloadImages: [
        'themes/jqt/img/chevron.png',
        'themes/jqt/img/back_button_clicked.png',
        'themes/jqt/img/button_clicked.png',
        'themes/jqt/img/toolbar.png'
        ]
});
</script>
</head>
<body>
  <div id="home" class="current">
    <div class="toolbar">
      <h1>&Ouml;rum tilastot</h1>
    </div>
    <h2>Myyntitilastot</h2>
    <ul class="rounded">
      <li class="arrow"><a href="#mytilpupe">Pupesoft</a></li>
      <li class="arrow"><a href="#mytilunikko">Unikko</a></li>
    </ul>
  </div>

<?php
// myynti pupesta
echo "<div id='mytilpupe'>",
jqtoolbar("Pupesoft", "home");

// pvm haarukka
$date1 = date("Y-m-d");
$date2 = date("Y-m-d", strtotime("-1 week"));

// vanne
echo "<h2>Vanne</h2>";

$query = "SELECT
          DATE_FORMAT(tilausrivi.laskutettuaika, '%e.%c.') pvm,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.rivihinta,0)),0) myyntinyt,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.kate,0)),0) katenyt
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi = 'L'
          LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
          LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
          WHERE lasku.yhtio       = 'artr'
          AND lasku.tila          = 'U'
          AND lasku.alatila       = 'X'
          AND ((lasku.tapvm >= '$date2'  and lasku.tapvm <= '$date1'))
          AND tuote.osasto        = '6'
          AND tilausrivi.tuoteno != '150'
          group by pvm
          order by pvm desc";
$result = mysql_query($query) or mysql_error($query);

echo "<table class='smooth'><thead><tr><th>pvm</th><th>myynti</th><th>kate</th></tr></thead><tbody>";

while ($row = mysql_fetch_assoc($result)) {
  echo  "<tr>",
  "<td>", $row["pvm"], "</td>",
  "<td>", $row["myyntinyt"], "</td>",
  "<td>", round(($row["katenyt"] / $row["myyntinyt"]) * 100, 1), "%</td>",
  "</tr>";
}

echo "</tbody></table>";

echo "<h2>Varaosa</h2>";

$query = "SELECT
          DATE_FORMAT(tilausrivi.laskutettuaika, '%e.%c.') pvm,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.rivihinta,0)),0) myyntinyt,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.kate,0)),0) katenyt
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi = 'L'
          LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
          LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
          WHERE lasku.yhtio        = 'artr'
          AND  lasku.tila          = 'U'
          AND  lasku.alatila       = 'X'
          AND  ((lasku.tapvm >= '$date2'  and lasku.tapvm <= '$date1'))
          AND  tuote.osasto       != '6'
          AND  tilausrivi.tuoteno != '150'
          group by pvm
          order by pvm desc";
$result = mysql_query($query) or mysql_error($query);

echo "<table class='smooth'><thead><tr><th>pvm</th><th>myynti</th><th>kate</th></tr></thead><tbody>";

while ($row = mysql_fetch_assoc($result)) {
  echo  "<tr>",
  "<td>", $row["pvm"], "</td>",
  "<td>", $row["myyntinyt"], "</td>",
  "<td>", round(($row["katenyt"] / $row["myyntinyt"]) * 100, 1), "%</td>",
  "</tr>";
}
echo "</tbody></table>";

// Turvata
echo "<h2>Turvata</h2>";

$query = "SELECT
          DATE_FORMAT(tilausrivi.laskutettuaika, '%e.%c.') pvm,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.rivihinta,0)),0) myyntinyt,
          ROUND(sum(if(tilausrivi.laskutettuaika >= '$date2'  and tilausrivi.laskutettuaika <= '$date1', tilausrivi.kate,0)),0) katenyt
          FROM lasku use index (yhtio_tila_tapvm)
          JOIN tilausrivi use index (uusiotunnus_index) ON tilausrivi.yhtio=lasku.yhtio and tilausrivi.uusiotunnus=lasku.tunnus and tilausrivi.tyyppi = 'L'
          LEFT JOIN tuote use index (tuoteno_index) ON tuote.yhtio=lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno
          LEFT JOIN asiakas use index (PRIMARY) ON asiakas.yhtio=lasku.yhtio and asiakas.tunnus=lasku.liitostunnus
          LEFT JOIN toimitustapa ON lasku.yhtio=toimitustapa.yhtio and lasku.toimitustapa=toimitustapa.selite
          WHERE lasku.yhtio       = 'turva'
          AND lasku.tila          = 'U'
          AND lasku.alatila       = 'X'
          AND ((lasku.tapvm >= '$date2'  and lasku.tapvm <= '$date1'))
          AND tilausrivi.tuoteno != '150'
          group by pvm
          order by pvm desc";
$result = mysql_query($query) or mysql_error($query);

echo "<table class='smooth'><thead><tr><th>pvm</th><th>myynti</th><th>kate</th></tr></thead><tbody>";

while ($row = mysql_fetch_assoc($result)) {
  echo  "<tr>",
  "<td>", $row["pvm"], "</td>",
  "<td>", $row["myyntinyt"], "</td>",
  "<td>", round(($row["katenyt"] / $row["myyntinyt"]) * 100, 1), "%</td>",
  "</tr>";
}
echo "</tbody></table>";

echo "<br /><div class='info'><p>L&auml;hde: Pupesoft, ", date("d.m.Y, h:i:s"), "</p></div>";

echo "</div>";

?>

  <div id="mytilunikko">
<?php // Unikko

echo jqtoolbar("Unikko", "home");

// sallitaan unikon puolen tilastot vaan tiettyyn aikaan (koska db on alhaalla yöllä)
if (strtotime("22:30") > strtotime("now") && strtotime("now") > strtotime("03:00")) {
  // Haetaan data

  $doc = new DOMDocument();

  // Pluto -palvelin, ainoastaan sisäverkossa käytössä
  $doc->load('http://193.185.248.20/cgi-bin/wspd_cgi.sh/WService=weborum/tools/dst/dst-xml-w.p?key=St544dgDamIf3a');

  echo "<table>
        <thead>
        <tr><th>pvm</th><th>tilaus</th><th>kuitti</th><th>laskutus</th></tr>
        </thead>
        <tbody>";

  $daystats = $doc->getElementsByTagName("drow");
  $i = 1;

  foreach ($daystats as $daystat) {
    $date = $daystat->getElementsByTagName("date");  $dateval = $date->item(0)->nodeValue;
    $ord = $daystat->getElementsByTagName("ord");  $ordval = $ord->item(0)->nodeValue;
    $ordp = $daystat->getElementsByTagName("ordp");  $ordpval = $ordp->item(0)->nodeValue;
    $rdy = $daystat->getElementsByTagName("rdy");  $rdyval = $rdy->item(0)->nodeValue;
    $rdyp = $daystat->getElementsByTagName("rdyp");  $rdypval = $rdyp->item(0)->nodeValue;
    $inv = $daystat->getElementsByTagName("inv");  $invval = $inv->item(0)->nodeValue;
    $invp = $daystat->getElementsByTagName("invp");  $invpval = $invp->item(0)->nodeValue;

    echo "  <tr><td>$dateval</td>
            <td>$ordval <span class='prcnt'>($ordpval%)</span></td>
            <td>$rdyval <span class='prcnt'>($rdypval%)</span></td>
            <td>$invval <span class='prcnt'>($invpval%)</span></td>
            </tr>";
    $i++;
  }

  echo "</tbody>
    </table>";
}
else {
  echo "<p>Unikko -tilastot ei saatavilla klo 22:30 - 03:00 v&auml;lisen&auml; aikana.</p>";
}

echo "</div>";

require "inc/footer.inc";

echo "</body></html>\n";
