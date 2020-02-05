<?php

require "inc/parametrit.inc";

$vannedir = "vanteet/";
$autodir  = "autot/";

print "<font class='head'>Vanneselain</font><hr>";

function position_carpic($carpic_left , $carpic_top, $width, $height, $zindex) {
  echo "
  <style type=\"text/css\">
  div.carpic
  {
    position:absolute; left:".$carpic_left."px; top:".$carpic_top."px; width:".$width."px; height:".$height."px; z-index:".$zindex."
  }
  </style>
  ";
}


function position_front_tear($front_tear_left, $front_tear_top, $width, $height, $zindex) {
  echo "
  <style type=\"text/css\">
  div.front_tear
  {
    position:absolute; left:".$front_tear_left."px; top:".$front_tear_top."px; width:".$width."px; height:".$height."px; z-index:".$zindex."
  }
  </style>
  ";
}


function position_back_tear($back_tear_left, $back_tear_top, $width, $height, $zindex) {
  echo "
  <style type=\"text/css\">
  div.back_tear
  {
    position:absolute; left:".$back_tear_left."px; top:".$back_tear_top."px; width:".$width."px; height:".$height."px; z-index:".$zindex."
  }
  </style>
  ";
}


if ($merkki != $oldmerkki) {
  $malli = '';
  $korityyppi = '';
}

//***** Page code begins ******

echo "<form action='$_SERVER[PHP_SELF]' method='post'>";

$query = "select distinct merkki from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and autokuva <>'' order by merkki";
$res = mysql_query($query) or pupe_error($query);

echo "<select name='merkki' onchange='submit()'>";
echo "<option value=''>Valitse merkki</option>\n";

while ($rivi = mysql_fetch_array($res)) {
  $selected = '';
  if ($merkki == $rivi["merkki"]) $selected = 'SELECTED';
  echo "<option value='$rivi[merkki]' $selected>$rivi[merkki]</option>\n";
}

echo "</select>";

echo "<select name='malli' onchange='submit()'>";
echo "<option value=''>Valitse malli</option>\n";

//*******************************  MERKKI VALITTU  ****************************
if ($merkki != '') {

  $query = "select distinct malli from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and merkki='$merkki' and autokuva <>'' order by malli";
  $res = mysql_query($query) or pupe_error($query);

  while ($rivi = mysql_fetch_array($res)) {
    $selected = '';
    if ($malli == $rivi["malli"]) $selected='SELECTED';
    echo "<option value='$rivi[malli]' $selected>$rivi[malli]</option>\n";
  }
}

echo "</select>";

//*******************************  MALLI VALITTU   ***************************
echo "<select name='korityyppi' onchange='submit()'>";
echo "<option value=''>Valitse korityyppi</option>\n";

if ($malli != '') {

  $query = "select distinct korityyppi from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and autokuva <>'' and merkki='$merkki' and malli='$malli' order by korityyppi";
  $res = mysql_query($query) or pupe_error($query);

  while ($rivi = mysql_fetch_array($res)) {
    $selected='';
    if ($korityyppi == $rivi["korityyppi"]) $selected='SELECTED';
    echo "<option value='$rivi[korityyppi]' $selected>$rivi[korityyppi]</option>\n";
  }
}

echo "</select>";
echo "<input type='hidden' name='oldmerkki' value='$merkki'>\n";
echo "<input type='hidden' name='oldmalli' value='$malli'>\n";
echo "<input type='hidden' name='oldkorityyppi' value='$korityyppi'>\n";

echo "</form>\n";


//*******************************  KORITYYPPI VALITTU  ****************************
if ($korityyppi != '') {
  //Haetaan autoon liittyv‰t kuvat ja paikkatiedot kannasta
  $query = " select distinct malli, merkki, korityyppi, autokuva, etu_x, etu_y, taka_x, taka_y, tunnus from yhteensopivuus_auto where yhtio ='$kukarow[yhtio]' and merkki='$merkki' and malli='$malli' and korityyppi='$korityyppi' and autokuva <>'' ";
  $res = mysql_query($query) or pupe_error($query);

  //jos rivej‰ v‰hemm‰n kuin 1, kyseisest‰ korityypist‰ ei ole kuvaa kannassa, jos enemm‰n ei haittaa.
  if (mysql_num_rows($res) < 1)
    echo "Carpic not found, please update database!\n";
  else {
    $rivi = mysql_fetch_array($res);

    $autokuva    = $rivi["autokuva"];
    $etu_x      = $rivi["etu_x"];
    $etu_y      = $rivi["etu_y"];
    $taka_x      = $rivi["taka_x"];
    $taka_y      = $rivi["taka_y"];
    $tunnus      = $rivi["tunnus"];
    $malli      = $rivi["malli"];
    $merkki      = $rivi["merkki"];
    $korityyppi    = $rivi["korityyppi"];

  }
  $auto_tyokuva = $autodir.$autokuva;
  list($auto_width, $auto_height, $auto_type, $auto_attr) = getimagesize($auto_tyokuva);
  position_carpic("0", "82", $auto_height, $auto_height, "1");
  echo "
  <div class=\"carpic\" style='border-style:none'>
  <img src=\"".$auto_tyokuva."\" border='0'>
  </div>
  ";

  //Tehd‰‰n haku tuotekantaan, mist‰ valitaan kyseiseen autoon sopivat vanteet
  $query = "select * from yhteensopivuus_tuote where yhtio='$kukarow[yhtio]' and atunnus='$tunnus' and tyyppi='HA'";
  $res = mysql_query($query) or pupe_error($query);

  $tuotteet = "";
  while ($rivi = mysql_fetch_array($res)) {
    $tuotteet .= "'$rivi[tuoteno]',";
  }
  $tuotteet = substr($tuotteet, 0, -1);

  // mitkä tuoteryhmät näytetään selaimessa
  $temp = "'602','603','610','611','612'";

  $query = "select distinct tuoteno, nimitys,tuotekuva,tuotekorkeus,myyntihinta from tuote where yhtio='$kukarow[yhtio]' and tuoteno in ($tuotteet) and try in ($temp) and tuotekuva != '' order by 1";
  $res2 = mysql_query($query) or pupe_error($query);

  echo " <div STYLE=position:absolute;left:0;top:430>\n ";
  echo "<table><TR>\n";
  $rounder = 0;

  while ($rivi = mysql_fetch_array($res2)) {
    $rtuotekuva    = $rivi["tuotekuva"];
    $rtuotenumero  = $rivi["tuoteno"];
    $rtuotekorkeus  = $rivi["tuotekorkeus"];
    $rnimitys    = $rivi["nimitys"];
    $rhinta      = $rivi["myyntihinta"];
    $rkoko      = $rivi["tuotekorkeus"];

    if ($rtuotekuva != "") {
      if ($rounder == 4)
        echo "<TR>\n";
      echo "<TD class='back' align='center'>\n";
      echo "<form action='$_SERVER[PHP_SELF]' method='post'>\n";
      $rtuotekuva = $vannedir.$rtuotekuva;
      $selected='';

      echo "<input type='image'  name='bvanne'    src='$rtuotekuva' onclick='submit()'>\n";
      echo "<input type='hidden' name='vanne'      value='$rtuotenumero'>\n";
      echo "<input type='hidden' name='merkki'    value='$merkki'>\n";
      echo "<input type='hidden' name='malli'      value='$malli'>\n";
      echo "<input type='hidden' name='korityyppi'  value='$korityyppi'>\n";
      echo "<input type='hidden' name='tunnus'    value='$tunnus'>\n";
      echo "<input type='hidden' name='autokuva'    value='$autokuva'>\n";
      echo "<input type='hidden' name='etu_x'      value='$etu_x'>\n";
      echo "<input type='hidden' name='etu_y'      value='$etu_y'>\n";
      echo "<input type='hidden' name='taka_x'    value='$taka_x'>\n";
      echo "<input type='hidden' name='taka_y'    value='$taka_y'>\n";
      echo "<input type='hidden' name='oldmerkki'    value='$merkki'>\n";
      echo "<input type='hidden' name='nimitys'    value='$rnimitys'>\n";
      echo "<input type='hidden' name='hinta'      value='$rhinta'>\n";
      echo "<input type='hidden' name='koko'      value='$rkoko'>\n";
      echo "</form>\n";
      echo $tuotekorkeus;
      echo "</TD>\n";

      if ($rounder == 7) {
        echo "</TR>\n";
        $rounder = 0;
      }
      $rounder++;
    }
  }
  echo " </tr></table></div>\n";

  if ($vanne=='') {
    $query = "select distinct tuotekuva from tuote where yhtio='$kukarow[yhtio]' and tuoteno in ($tuotteet) and try in ($temp) order by myyntihinta desc";
    $res2 = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($res2)>0) {
      $rivi = mysql_fetch_array($res2);
      $rtuotekuva    = $rivi["tuotekuva"];
    }

    if ($rtuotekuva != '') {
      //      $vanne_koko = "16/";
      $vanne_tyokuva = $vannedir.$vanne_koko.$rtuotekuva;
      list($vanne_width, $vanne_height, $vanne_type, $vanne_attr) = getimagesize($vanne_tyokuva);
      $auto_tyokuva = $autodir.$autokuva;
      list($auto_width, $auto_height, $auto_type, $auto_attr) = getimagesize($auto_tyokuva);

      $etu_x  -= ($vanne_width/2);
      $etu_y  -= ($vanne_height/2);
      $taka_x  -= ($vanne_width/2);
      $taka_y  -= ($vanne_height/2);

      position_front_tear($etu_x, $etu_y, $vanne_width, $vanne_height, "1");
      position_back_tear($taka_x, $taka_y, $vanne_width, $vanne_height, "1");

      echo "
        <div class=\"front_tear\">
          <img src=\"".$vanne_tyokuva."\">
        </div>
        <div class=\"back_tear\">
          <img src=\"".$vanne_tyokuva."\">
        </div>
        ";
    }
  }


}

//*******************************  VANNE VALITTU  ****************************
if ($vanne != '') {

  $query    = "select tuotekuva from tuote where yhtio='$kukarow[yhtio]' and tuoteno='".$vanne."'";
  $res    = mysql_query($query) or pupe_error($query);
  $rivi    = mysql_fetch_array($res);

  $vannekuva  = $rivi["tuotekuva"];
  //  $vanne_koko = "16/";
  $vanne_tyokuva = $vannedir.$vanne_koko.$vannekuva;

  $auto_tyokuva = $autodir.$autokuva;

  list($vanne_width, $vanne_height, $vanne_type, $vanne_attr) = getimagesize($vanne_tyokuva);
  list($auto_width, $auto_height, $auto_type, $auto_attr) = getimagesize($auto_tyokuva);

  $etu_x  -= ($vanne_width/2);
  $etu_y  -= ($vanne_height/2);
  $taka_x  -= ($vanne_width/2);
  $taka_y  -= ($vanne_height/2);

  //M‰‰r‰t‰‰n elementtien paikat.
  position_carpic("0", "82", $auto_height, $auto_height, "1");
  position_front_tear($etu_x, $etu_y, $vanne_width, $vanne_height, "2");
  position_back_tear($taka_x, $taka_y, $vanne_width, $vanne_height, "2");

  echo "
  <div class=\"carpic\">
  <img src=\"".$auto_tyokuva."\">
  </div>
  <div class=\"front_tear\">
    <img src=\"".$vanne_tyokuva."\">
  </div>
  <div class=\"back_tear\">
    <img src=\"".$vanne_tyokuva."\">
  </div>
  ";

  //VANNEINFOT OIKEALLE

  $palaset = explode(".", $vannekuva);
  //  $uuskuva = $vannedir.$palaset[0]."_info.jpg";
  //  echo "<DIV STYLE='position:absolute;left:$auto_width;top:90;font-size:12;font-family:tahoma;z-index:2'>";
  //  echo "<img src='$uuskuva'>";
  //  echo "</DIV>";
  echo "<DIV STYLE='position:absolute;left:$auto_width;top:80;font-size:12;font-family:tahoma;z-index:2'>";
  echo "<TABLE><TR><TD><B>Tuotenimi</B></TD><TD>$nimitys</TD></TR>";
  echo "<TR><TD><B>Tuotenumero</B></TD><TD>$vanne</B></TD></TR>";
  echo "<TR><TD><B>Tuumakoko</B></TD><TD>$koko</TD></TR>";
  echo "<TR><TD><B>Myyntihinta</B></TD><TD>$hinta &euro;/kpl</TD></TR>";
  echo "</TABLE><BR>";
  echo "</DIV>";
}

require "inc/footer.inc";
