<?php
include "../inc/parametrit.inc";

echo "<font class='head'>".t("Asiakasraportit myyntilaskuista")."</font><hr>";

$oikeus = true; // includeissa tarkistetaan tämän avulla, onko käyttäjällä oikeutta tehdä ko. toimintoa

if ($tila == 'tee_raportti') {
  include('myyntilaskut_asiakasraportti_tee_raportti.php');
} else { // asiakkaan valintasivu
  include('myyntilaskut_asiakasraportti_asiakaslista.php');
}

/* visuaalinen esitys maksunopeudesta (hymynaama) */
/* palauttaa listan arvoja, joissa ensimmäisessä on
 * pelkkä img-tagi oikeaan naamaan ja toisessa
 * koko maksunopeus-HTML
 */
function laskeMaksunopeus($ytunnus, $yhtio) {

//  $html = "<h2>Asiakkaan maksunopeus</h2>";

// myohassa maksetut
  $query="	SELECT sum(if(erpcm < mapvm, summa, 0)) myohassa, sum(summa-saldo_maksettu) yhteensa
			from lasku,
			(select tunnus from asiakas where yhtio='$yhtio' and ytunnus='$ytunnus') valittu
			where yhtio='$yhtio' 
			and liitostunnus = valittu.tunnus 
			and tila = 'U'
			and alatila = 'X'
			and summa > 0 
			and mapvm > '0000-00-00'";
  $result = mysql_query($query) or pupe_error($query);
  $laskut = mysql_fetch_array($result);

  if ($laskut['yhteensa'] != 0)
    $maksunopeus = $laskut['myohassa']/$laskut['yhteensa']*100;
  else
    $maksunopeus = "N/A";

  $maksunopeusvari="lightgreen";
  $kuva="asiakas_jee.gif";
  if ($maksunopeus > 10) {
    $maksunopeusvari="orange";
    $kuva="asiakas_hui.gif";
  }
  if ($maksunopeus > 50) {
    $maksunopeusvari="red";
    $kuva="asiakas_argh.gif";
  }

  //echo "<br><br>";
 $html .= '<font color="'.$maksunopeusvari.'">';
 $html .= "".t("Myöhässä maksettuja laskuja").": ";
 $html .= sprintf('%.0f', $maksunopeus);
 $html .= " % </font>";
 $kuvaurl = "<img valign='bottom' src=\"../pics/$kuva\">";

 //$html .= $kuvaurl;

 return array ($kuvaurl, $html);

}


include "../inc/footer.inc";

?>
