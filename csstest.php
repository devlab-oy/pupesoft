<?php

require "inc/parametrit.inc";

if ($tee == "update") {

  foreach ($vari as $regvari => $uusivari) {
    $yhtiorow["css"] = preg_replace("/(.*?):(.*?);(.*?\/\*$regvari\*\/)/i", "\\1: $uusivari; \\3", $yhtiorow["css"]);
    $yhtiorow["css"] = preg_replace("/ {2,}/", " ", $yhtiorow["css"]);
  }

  $query = "UPDATE yhtion_parametrit SET css='$yhtiorow[css]' WHERE yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF'>";
  exit;
}

$css_kuvaus = array(
  'BODY_BACKGROUND'    =>  'Sivun tausta'
  ,  'BODY_COLOR'      =>  'Sivun perusteksti'
  ,  'ERROR_COLOR'      =>  'Virheteksti'
  ,  'HEAD_COLOR'      =>  'Otsikkoteksti'
  ,  'HR_BACKGROUND'      =>  'Osioiden erotinviiva (esim. otsikon alla)'
  ,  'INFO_COLOR'      =>  'Infoteksti (fonttikooltaan normaalia pienempää)'
  ,  'INPUT_COLOR'      =>  'Painikkeiden teksti ja syötekenttien teksti'
  ,  'LINK_COLOR'      =>  'Linkit'
  ,  'LIVESEARCH_BACKGROUND'  =>  'Live-hakukenttien tausta'
  ,  'MENUHOVER_BACKGROUND'  =>  'Aktiivisen navigaatioelementin tausta'
  ,  'MENUHOVER_COLOR'    =>  'Aktiivisen navigaatioelementin teksti'
  ,  'MENULINK_COLOR'    =>  'Navigaatioelementin'
  ,  'MESSAGE_COLOR'      =>  'Lihavoitu infoteksti'
  ,  'OK_COLOR'        =>  'Onnistuneen tai hyväksytyn toiminnan palaute'
  ,  'POPUP_BACKGROUND'    =>  'Ponnahdusikkunoiden tausta'
  ,  'POPUP_COLOR'      =>  'Ponnahdusikkunoiden teksti'
  ,  'SPEC_BACKGROUND'    =>  'Erikoiselementin tausta'
  ,  'SPEC_COLOR'      =>  'Erikoiselementin teksti'
  ,  'TDLINKKI_COLOR'    =>  'Taulukon solussa oleva linkki'
  ,  'TH_BACKGROUND'      =>  'Taulukon otsikkosolu'
  ,  'TH_COLOR'        =>  'Taulukon otsikkosolun tausta'
  ,  'TRAKTIIVI_BACKGROUND'  =>  'Aktiivisen solun tausta'
  ,  'TRAKTIIVI_COLOR'    =>  'Aktiivisen solun teksti'
  ,  'TR_BACKGROUND'      =>  'Taulukon solun tausta'
  ,  'TR_COLOR'        =>  'Taulukon solun teksti'
  ,  'TUMMA_BACKGROUND'    =>  'Tumman elementin tausta'
  ,  'TUMMA_COLOR'      =>  'Tumman elementin teksti'
  ,  'ASIAKASFAKTA_COLOR'  =>  'Asiakasfakta otsikolla ja myyntitilauksella'
);

function getCSSDescription($css_name) {
  global $css_kuvaus;

  if (array_key_exists($css_name, $css_kuvaus)) {
    return $css_kuvaus[$css_name];
  }
  else return false;
}

echo "<font class='head'>CSS-testing:</font><hr><br>";

preg_match_all("/.*?\/\*(.*?(_COLOR|_BACKGROUND))\*\//", $yhtiorow['css'], $varitmatch);

$varit = array();

for ($i=0; $i<count($varitmatch[0]); $i++) {
  if (!isset($varit[$varitmatch[1][$i]])) {
    $varit[$varitmatch[1][$i]] = $varitmatch[0][$i];
  }
}

ksort($varit);

echo "
Tässä näkee miten formit käyttäytyy:<hr>
<form action='csstest.php' class='multisubmit'>
<select>
<option>1 - ensimmäinen</option>
<option>2 - toinen</option>
<option>3 - kolmas</option>
</select>
<input type='text'>
<input type='checkbox'  name='1'>
<input type='radio'   name='2'>
<input type='radio'   name='2'>
<input type='submit' value='Normaali submit-nappula'>
</form> pitäisi pysyä nipussa ilman suurempia aukkoja ja rivinvaihtoja.

<br>
<br>

Muutama nappula:<hr>
<input type='button' value='input type=button'>
<button class='valinta'>valinta:class button-nappula</button>
<button class='valinta'>normaali button-nappula</button>

<br>
<br>

Taulukko:<hr>
<table>
<tr><th>TH => TH_BACKGROUND ja TH_COLOR</th><th>Taulukon otsikkosolun taustaväri sekä tekstin väri</th></tr>
<tr><th><a href='#'>TH linkki eli otsikkosolun linkki</a></th><th><a href='#'>TH_COLOR vaihtaa linkin väriä</th></tr>
<tr><td>TD => TR_BACKGROUND ja TR_COLOR</td><td>Taulukon solun tekstin väri, tekstin väri vaihtuu TR_COLOR ja taustaväri TR_BACKGROUND</td></tr>
<tr><td><a href='#'>TDLINKKI_COLOR</a></td><td><a href='#'>Linkki taulukon solussa</a></td></tr>
<tr><td><a class='td' href='#'>TD linkki class='td'</a></td><td><a class='td' href='#'>Linkki ilman alaviivaa, väri sama kuin TDLINKKI_COLOR</a></td></tr>
<tr class='aktiivi'><td>TRAKTIIVI_BACKGROUND ja TRAKTIIVI_COLOR</td><td>Aktiivisen taulukon solun taustaväri ja tekstin väri (kokeile hiirellä)</td></tr>
<tr><td class='back'>TD class='back'</td><td class='back'>Solun taustaväri on sama kuin sivulla, vaihtuu, jos vaihdat BODY_BACKGROUND</td></tr>
<tr><td class='ok'>TD class='ok'</td><td class='ok'>Vaihtuu samasta kuin OK_COLOR</td></tr>
<tr><td class='error'>TD class='error'</td><td class='error'>Vaihtuu samasta kuin ERROR_COLOR</td></tr>
<tr><td class='spec'>TD class='spec'</td><td class='spec'>speciaali taulukon solu, esim. tausta sama kuin TD:ssä mutta fontinväri sama ku TH:ssa</td></tr>
<tr><td class='tumma'>TD class='tumma'</td><td class='tumma'>speciaali taulukon solu, esim. fontti ja tausta samanväriset kuin TH:ssa</td></tr>
<tr><td class='liveSearch'>TD class='liveSearch'</td><td class='liveSearch'>käytetään jossain searchissa</td></tr>
</table>

<br>
Linkit:<hr>
<div style='width:300px'>
<p><a href='#'>oletus-linkki</a></p>
<p><a class='kale' href='#'>class kale linkki</a></p>
<p><a class='menu' href='#'>class menu linkki</a></p>
<p><a class='td' href='#'>class td linkki</a></p>
</div>

<br>
Fonttien värit:<hr>
BODY_COLOR: Sivun perusteksti.<br>
<font class='head'>HEAD_COLOR: Sivun yläotsikko.</font><br>
<font class='message'>MESSAGE_COLOR: Lihavoitu infoteksti.</font><br>
<font class='error'>ERROR_COLOR: Virheteksti, suositeltavaa käyttää esimerkiksi punaista.</font><br>
<font class='ok'>OK_COLOR: Onnistuneen/hyväksytyn toiminnon palaute (esim. lomakkeen lähettämisen jälkeen)</font><br>
<font class='info'>INFO_COLOR: Fonttikooltaan pienempi infoteksti.</font><br>
<font class='kaleinfo'>INFO_COLOR: INFO_COLOR muuttaa myös KALEINFO tekstiä, joka on fonttikooltaan perustekstin kokoista.</font><br>
<pre>PRE-tekstiä: Tämä on monospace fontti. Väriä tai fonttia ei voi vaihtaa.</pre>
<div class='popup' style='visibility:visible'>div class='popup' tulee yleensä taulukon päälle, pitää olla hyvän näköinen suhteessa muihin väreihin</div>

<br><br><br>";

echo "  <script language='javascript'>
      function variupdate (vari_index) {
        document.getElementById(\"2_\"+vari_index).style.backgroundColor = document.getElementById(\"1_\"+vari_index).value;
      }
    </script> ";

echo "Muuta CSS:n värejä:";
echo "<table><form method='post'>";
echo "<input type='hidden' name='tee' value='update'>";

echo "  <tr>
    <th>Index-nimi</th>
    <th>Uusi värikoodi</th>
    <th>Väri</th>
    <th>Värikoodi</th>
    <th>Kuvaus</th>
    </tr>";

foreach ($varit as $vari_index => $vari) {

  preg_match("/(#[a-f0-9]{3,6});/i", $vari, $varirgb);

  echo "<tr>
      <td>$vari_index</td><td><input type='text' name = 'vari[$vari_index]' value='$varirgb[1]' id='1_$vari_index' onkeyup='variupdate(\"$vari_index\");' onblur='variupdate(\"$vari_index\");'></td>
      <td id='2_$vari_index' style='background-color:$varirgb[0];'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td>$varirgb[0]</td>
      <td>".getCSSDescription($vari_index)."</td>
    </tr>";
}

echo "</table><input type='submit' value='Päivitä'></form><br><br><br><br>";

require "inc/footer.inc";
