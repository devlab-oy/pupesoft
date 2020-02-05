<?php

require "inc/parametrit.inc";

echo "<style type='text/css'>
  div.grid
  {
    position:absolute;
    width:850px;
    height:450px;
    z-index:3;
  }
</style>";

echo "<DIV class='grid'><IMG SRC='pixel-grid.gif'></DIV>";

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
print "<font class='head'>Vanneselain ylläpito</font><hr>";

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

if ($update != '') {
  $sql = "UPDATE yhteensopivuus_auto SET autokuva='$autokuva', etu_x='$etu_x', etu_y='$etu_y', taka_x='$taka_x', taka_y='$taka_y' where merkki='$merkki' and korityyppi='$korityyppi' and yhtio='$kukarow[yhtio]'";
  echo "<DIV>Tiedot päivitetty!</div>";
  $res = mysql_query($sql) or pupe_error($sql);
}

//*****************  EI VALITTUJA ************************************

echo "<DIV>Valittuna<BR> $merkki - $malli - $korityyppi</div>";
echo "<form method='post'>";

$query = "select distinct merkki from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' order by merkki";
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




//*****************  MERKKI VALITTU **********************************
if ($merkki != '') {

  $query = "select distinct malli from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and merkki='$merkki' order by malli";
  $res = mysql_query($query) or pupe_error($query);

  while ($rivi = mysql_fetch_array($res)) {
    $selected = '';
    if ($malli == $rivi["malli"]) $selected='SELECTED';
    echo "<option value='$rivi[malli]' $selected>$rivi[malli]</option>\n";
  }
}

echo "</select>";

//*****************  MERKKI JA MALLI VALITTU***************************

echo "<select name='korityyppi' onchange='submit()'>";
echo "<option value=''>Valitse korityyppi</option>\n";

if ($malli != '') {

  $query = "select distinct korityyppi from yhteensopivuus_auto where yhtio='$kukarow[yhtio]' and merkki='$merkki' and malli='$malli' order by korityyppi";
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

//*****************  MERKKI,MALLI JA KORITTYYPPI VALITTU **************

if ($korityyppi != '') {
  echo "<DIV>";
  $sql = "SELECT  distinct tunnus,malli,autokuva,etu_x,etu_y,taka_x,taka_y from yhteensopivuus_auto where merkki='$merkki' and korityyppi='$korityyppi' order by malli";

  $sql_search    = mysql_query($sql) or pupe_error($sql);
  $mysql_tiedot  = mysql_fetch_assoc($sql_search);
  $malli      = $mysql_tiedot["malli"];
  $autokuva    = $mysql_tiedot["autokuva"];
  $etu_x      = $mysql_tiedot["etu_x"];
  $etu_y      = $mysql_tiedot["etu_y"];
  $taka_x      = $mysql_tiedot["taka_x"];
  $taka_y      = $mysql_tiedot["taka_y"];
  $tunnus      = $mysql_tiedot["tunnus"];


  print("
    <BR>
    <form method=\"POST\">
    <table>
    <TR><TD>Autokuvan nimi</TD>
    <TD><input type =\"text\" name=\"autokuva\" value=".$autokuva."></TD></TR>
    <TR><TD>Oikeanpuoleisen renkaan kuvan X asema</TD>
    <TD><input type =\"text\" name=\"etu_x\" value=".$etu_x."></TD></TR>
    <TR><TD>Oikeanpuoleisen renkaan kuvan Y asema</TD>
    <TD><input type =\"text\" name=\"etu_y\" value=".$etu_y."></TD></TR>
    <TR><TD>Vasemmanpuoleisen renkaan kuvan X asema</TD>
    <TD><input type =\"text\" name=\"taka_x\" value=".$taka_x."></TD></TR>
    <TR><TD>Vasemmanpuoleisen renkaan kuvan Y asema</TD>
    <TD><input type =\"text\" name=\"taka_y\" value=".$taka_y."></TD></TR>
    ");
  echo "<input type='hidden' name='merkki' value='$merkki'>\n";
  echo "<input type='hidden' name='malli' value='$malli'>\n";
  echo "<input type='hidden' name='korityyppi' value='$korityyppi'>\n";
  echo "<input type='hidden' name='oldmerkki' value='$merkki'>\n";
  echo "<input type='hidden' name='update' value='1'>\n";
  print("
    <TR><TD><input type =\"submit\" value=\"päivitä\"></TD></TR>
    </TABLE>

    ");
  echo "</form><a href='$PHP_SELF'>takaisin alkuun</a></div>";


  $temp = "'602','603','610','611','612'";
  $query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuotekuva <>'' and try in ($temp)";
  $res2 = mysql_query($query) or pupe_error($query);

  if (mysql_num_rows($res2)>0) {
    $rivi = mysql_fetch_array($res2);
    $rtuotekuva    = $rivi["tuotekuva"];
  }

  $vannedir = "vanteet/";
  $autodir  = "autot/";

  if ($rtuotekuva != 'x' and $autokuva != '') {
    $vanne_tyokuva = $vannedir.$vanne_koko.$rtuotekuva;
    list($vanne_width, $vanne_height, $vanne_type, $vanne_attr) = getimagesize($vanne_tyokuva);
    $auto_tyokuva = $autodir.$autokuva;
    list($auto_width, $auto_height, $auto_type, $auto_attr) = getimagesize($auto_tyokuva);

    $etu_x  -= ($vanne_width/2);
    $etu_y  -= ($vanne_height/2);
    $taka_x  -= ($vanne_width/2);
    $taka_y  -= ($vanne_height/2);

    position_carpic("0", "82", $auto_height, $auto_height, "0");
    position_front_tear($etu_x, $etu_y, $vanne_width, $vanne_height, "1");
    position_back_tear($taka_x, $taka_y, $vanne_width, $vanne_height, "1");

    print("
      <div class=\"carpic\">
      <img src=\"".$auto_tyokuva."\">
      </div>
      <div class=\"front_tear\">
        <img src=\"".$vanne_tyokuva."\">
      </div>
      <div class=\"back_tear\">
        <img src=\"".$vanne_tyokuva."\">
      </div>
      ");
  }
}

require "inc/footer.inc";
