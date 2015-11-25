<?php

require 'inc/edifact_functions.inc';
require "inc/parametrit.inc";

if (!isset($errors)) $errors = array();

if (isset($task) and $task == 'hae_tiedot') {

$alku = explode(".", $pvm1);
$alkupvm = $alku[2] . '-' . $alku[1] . '-' . $alku[0] . ' 00:00:00';

$loppu = explode(".", $pvm2);
$loppupvm = $loppu[2] . '-' . $loppu[1] . '-' . $loppu[0] . ' 23:59:59';


$query = "SELECT sum(ss.massa)
          FROM sarjanumeroseuranta AS ss
          JOIN tilausrivi AS otr
            ON (otr.yhtio = ss.yhtio
            AND otr.tunnus = ss.ostorivitunnus
            AND otr.tyyppi = 'O')
          WHERE ss.yhtio = '{$kukarow['yhtio']}'
          AND otr.toimitettuaika > '{$alkupvm}'
          AND otr.toimitettuaika < '{$loppupvm}'";
$result = pupe_query($query);

$puretut = mysql_result($result, 0);

$query = "SELECT sum(ss.massa)
          FROM sarjanumeroseuranta AS ss
          JOIN tilausrivi AS mtr
            ON (mtr.yhtio = ss.yhtio
            AND mtr.tunnus = ss.myyntirivitunnus
            AND mtr.tyyppi = 'L')
          WHERE ss.yhtio = '{$kukarow['yhtio']}'
          AND mtr.toimitettuaika > '{$alkupvm}'
          AND mtr.toimitettuaika < '{$loppupvm}'";
$result = pupe_query($query);

$lastatut = mysql_result($result, 0);

  echo "<font class='head'>".t("Lastatut ja puretut tonnit")."</font></a><hr><br>";


  echo "V&auml;lill&auml; " . $pvm1 . " ja " . $pvm2 . '<br>';

  echo 'Purettu: ' . (int) $puretut . ' kg<br>';

    echo 'Lastattu: ' . (int) $lastatut . ' kg<br><br>';

	echo "<a href='puretut_lastatut.php'>Uusi  haku</a>";

}

if (!isset($task)) {

  echo "<font class='head'>".t("Lastatut ja puretut tonnit")."</font></a><hr><br>";

  $nyt = date("d.m.Y");

  echo "
    <script>
      $(function($){
         $.datepicker.regional['fi'] = {
                     closeText: 'Sulje',
                     prevText: '&laquo;Edellinen',
                     nextText: 'Seuraava&raquo;',
                     currentText: 'T&auml;n&auml;&auml;n',
             monthNames: ['Tammikuu','Helmikuu','Maaliskuu','Huhtikuu','Toukokuu','Kes&auml;kuu',
              'Hein&auml;kuu','Elokuu','Syyskuu','Lokakuu','Marraskuu','Joulukuu'],
              monthNamesShort: ['Tammi','Helmi','Maalis','Huhti','Touko','Kes&auml;',
              'Hein&auml;','Elo','Syys','Loka','Marras','Joulu'],
                      dayNamesShort: ['Su','Ma','Ti','Ke','To','Pe','Su'],
                      dayNames: ['Sunnuntai','Maanantai','Tiistai','Keskiviikko','Torstai','Perjantai','Lauantai'],
                      dayNamesMin: ['Su','Ma','Ti','Ke','To','Pe','La'],
                      weekHeader: 'Vk',
              dateFormat: 'dd.mm.yy',
                      firstDay: 1,
                      isRTL: false,
                      showMonthAfterYear: false,
                      yearSuffix: ''};
          $.datepicker.setDefaults($.datepicker.regional['fi']);
      });

      $(function() {
        $('#pvm1').datepicker();
		$('#pvm2').datepicker();
      });
      </script>
  ";

  echo "
    <form method='post'>
    <table>
    <tr><th>" . t("Alku") ."</th><td><input type='text' id='pvm1' name='pvm1' value='{$nyt}' /></td></tr>

	    <tr><th>" . t("Loppu") ."</th><td><input type='text' id='pvm2' name='pvm2' value='{$nyt}' /></td></tr>

  <tr><th></th><td align='right'><input type='submit' value='". t("Hae tiedot") ."' /></td></tr>
  </table>
  <input type='hidden' name='task' value='hae_tiedot' />
  </form>";


}



require "inc/footer.inc";
