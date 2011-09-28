<?php

// estet‰‰n sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

echo "<font class='head'>".t("Karhuttujen laskujen listaus")."</font>";


$tila = 'lista';
if ((int)$order==0) $order=1;

$query = "SELECT l.nimi as nimi, l.summa-l.saldo_maksettu as summa, l.erpcm as erpcm,
	TO_DAYS(now()) - TO_DAYS(l.erpcm) as ika,
	l.tunnus as tunnus,
	kk.pvm as kpvm
	FROM lasku l
	inner join karhu_lasku kl on (l.tunnus=kl.ltunnus)
	inner join karhukierros kk on (kk.tunnus=kl.ktunnus)
	WHERE
	l.erpcm < now() AND
	l.mapvm='0000-00-00' AND
	l.tila = 'U' AND
	l.yhtio = '$kukarow[yhtio]'
	ORDER BY $order";

$result = mysql_query($query) or pupe_error($query);

echo "<table><tr>";
echo "<th><a href='$PHP_SELF?order=1'>".t("Nimi")."</a></th>";
echo "<th><a href='$PHP_SELF?order=2'>".t("Summa")."</a></th>";
echo "<th><a href='$PHP_SELF?order=3'>".t("Er‰p‰iv‰")."</a></th>";
echo "<th><a href='$PHP_SELF?order=4'>".t("Ik‰ p‰iv‰‰")."</a></th>";
echo "<th><a href='$PHP_SELF?order=6'>".t("Karhu pvm")."</a></th></tr>";

while ($lasku=mysql_fetch_object ($result)) {
  echo "<tr><td>";
  echo $lasku->nimi;
  echo "</td><td>";
  echo $lasku->summa;
  echo "</td><td>";
  echo $lasku->erpcm;
  echo "</td><td>";
  echo $lasku->ika;
  echo "</td><td>";
  echo $lasku->kpvm;
  echo "</td></tr>\n";
}

echo "</table>";
?>
