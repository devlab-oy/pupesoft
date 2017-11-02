<?php

//
// tarvitaan $kukarow ja $yhtiorow
//
// P‰ivitet‰‰n laskutettujen myyntien kesken j‰‰neet varastosiirrot vastaanotetuiksi
//

ini_set("memory_limit", "5G");

require "inc/parametrit.inc";

// Timeout in 5h
ini_set("mysql.connect_timeout", 18000);
ini_set("max_execution_time", 18000);

echo "<font class='head'>".t("Vastaanota keskener‰inen kirjanpidollinen varastosiirto")."</font><hr>";

  $_pvm = pupesoft_cleanstring($argv[2]);
  $pvm .= ' 99:99:99';
$_poikkeavalaskutuspvm = '';

if ($tee == "vastaanota" and $pvm != "") {
  $lkm = 0;
  require 'tilauskasittely/tilauksesta_varastosiirto.inc';
  
  $query = "SELECT myynti.tunnus mytunnus, myynti.laskunro mylaskunro, SUBSTRING(lasku.viesti, 38) nro, 
            lasku.tunnus, myynti.varastosiirto_tunnus, lasku.* 
            from lasku 
  	        join lasku as myynti on myynti.yhtio = lasku.yhtio 
  	        and myynti.tunnus = SUBSTRING(lasku.viesti, 38)
            and myynti.tila = 'L'
            and myynti.alatila = 'X'
  	        where lasku.yhtio = '{$kukarow[yhtio]}'
            and lasku.tila = 'G' 
  	        and lasku.alatila = ''
            and lasku.luontiaika <= '{$pvm}'
  	        and lasku.chn='KIR'";
  $varres = pupe_query($query);
            
  while ($varastosiirto = mysql_fetch_assoc($varres)) {
  
      $query = "SELECT * from lasku 
    	        where lasku.yhtio = '{$kukarow[yhtio]}' 
              and lasku.tunnus = '$varastosiirto[mytunnus]'";
  
      $myyres = pupe_query($query);
      if (mysql_num_rows($myyres) > 0) {
        $myyntitilaus = mysql_fetch_assoc($myyres);
        $varastosiirtorivit = hae_tilausrivit($varastosiirto['tunnus'], 'K', false);
   
        $varastosiirtorivit = aseta_varastosiirto_vastaanotetuksi($varastosiirto, $varastosiirtorivit, $_poikkeavalaskutuspvm);
              
        #echo "Varastosiirto $varastosiirto[tunnus] vastaanotettu (Myynti: $varastosiirto[mytunnus], $varastosiirto[mylaskunro])! \n\n";
        $lkm += 1;
      }  
  }
  
  echo "Varastosiirtoja vastaanotettu $lkm kappaletta! \n\n";
}
else {
  echo "<br><form method='post'>";
  echo "<input type='hidden' name='tee' value='vastaanota'>";

  echo "<table>";

  echo "</select></td></tr>";
  echo "<tr><th colspan='2'>".t("Anna p‰iv‰m‰‰r‰ (VVVV-KK-PP), jota vanhemmat kirjanpidolliset varastosiirrot vied‰‰n loppuun")."</th></tr>";
  echo "<tr><th>".t("P‰iv‰m‰‰r‰")."</th><td><input type='text' size='10' name='pvm'></td></tr>";
  
  echo "</table><br>";

  echo "<input type='submit' value='".t("Vastaanota")."'>";
  echo "</form>";
}
  