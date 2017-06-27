<?php

include "../inc/parametrit.inc";

echo "<font class='head'>".t("Varauskalenteri")." $toim</font><hr>";

if ($tee == "SYOTA") {
  include 'varauskalenteri_syota.php';
  if ($jatko != 1) {
    exit;
  }
}

if ($tee == "LISAA") {
  include 'varauskalenteri_lisaa.php';
}

if ($tee == "POISTA") {
  include 'varauskalenteri_poista.php';
  if ($jatko != 1) {
    exit;
  }
}

if ($tee == "NAYTA") {
  include 'varauskalenteri_nayta.php';
  if ($jatko != 1) {
    exit;
  }
}

$MONTH_ARRAY    = array(1=> t('Tammikuu'), t('Helmikuu'), t('Maaliskuu'), t('Huhtikuu'), t('Toukokuu'), t('Kes‰kuu'), t('Hein‰kuu'), t('Elokuu'), t('Syyskuu'), t('Lokakuu'), t('Marraskuu'), t('Joulukuu'));
$paivat     = array(1=> t('Maanantai'), t('Tiistai'), t('Keskiviikko'), t('Torstai'), t('Perjantai'), t('Lauantai'), t('Sunnuntai'));

//kalenteritoiminnot
function days_in_month($month, $year) {
  // calculate number of days in a month
  return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function weekday_name($day, $month, $year) {
  // calculate weekday name
  $day = array(t('Maanantai'), t('Tiistai'), t('Keskiviikko'), t('Torstai'), t('Perjantai'), t('Lauantai'), t('Sunnuntai'));
  $nro = date("w", mktime(0, 0, 0, $month, $day, $year));
  if ($nro==0) $nro=6;
  else $nro--;

  return $day[$nro];
}

function weekday_number($day, $month, $year) {
  // calculate weekday number
  $nro = date("w", mktime(0, 0, 0, $month, $day, $year));
  if ($nro==0) $nro=6;
  else $nro--;

  return $nro;
}

function month_name($month) {
  // display long month name
  $kk = $MONTH_ARRAY;
  return $kk[$month-1];
}

// otetaan oletukseksi t‰m‰ kuukausi ja t‰m‰ vuosi
if ($month == '')   $month = date("n");
if ($year == '')    $year  = date("Y");
if ($day == '')   $day   = date("j");

//lasketaan edellinen ja seuraava paiva kun siirytaan yksi paiva
$backmday = date("n", mktime(0, 0, 0, $month, $day-1,  $year));
$backyday = date("Y", mktime(0, 0, 0, $month, $day-1,  $year));
$backdday = date("j", mktime(0, 0, 0, $month, $day-1,  $year));

$nextmday = date("n", mktime(0, 0, 0, $month, $day+1,  $year));
$nextyday = date("Y", mktime(0, 0, 0, $month, $day+1,  $year));
$nextdday = date("j", mktime(0, 0, 0, $month, $day+1,  $year));

//lasketaan edellinen ja seuraava paiva kun siirytaan yksi kuukausi
$backmmonth = date("n", mktime(0, 0, 0, $month-1, $day,  $year));
$backymonth = date("Y", mktime(0, 0, 0, $month-1, $day,  $year));
$backdmonth = date("j", mktime(0, 0, 0, $month-1, $day,  $year));

$nextmmonth = date("n", mktime(0, 0, 0, $month+1, $day,  $year));
$nextymonth = date("Y", mktime(0, 0, 0, $month+1, $day,  $year));
$nextdmonth = date("j", mktime(0, 0, 0, $month+1, $day,  $year));

//viela muuttujat mysql kyselyja varten, (etunollat pitaa olla...)
$mymonth = sprintf('%02d', $month);
$myday   = sprintf('%02d', $day);



//paivan tapahtumat
echo "  <table align='left' width='50%'>
    <tr>
    <td class='back' colspan='4' align='center'>
    <a href='$PHP_SELF?day=$backdday&month=$backmday&year=$backyday&toim=$toim'><< ".t("Edellinen p‰iv‰")."</a>&nbsp;&nbsp;&nbsp;
    <b>$day. ". $MONTH_ARRAY[$month] ." $year</b>&nbsp;&nbsp;&nbsp;
    <a href='$PHP_SELF?day=$nextdday&month=$nextmday&year=$nextyday&toim=$toim'>".t("Seuraava p‰iv‰")." >></a>
    </tr>
    <tr>
    <th align='left' width='100'>".t("Kello") ."</th>
    <th align='left' width='80'>" .t("Lis‰‰") ."</th>
    <th align='left' width='130'>".t("Kuka")  ."</th>
    <th align='left' colspan='2'>".t("Viesti")."</th>
    </tr>";

$min = 0;
$tun = 8;

//N‰ytet‰‰n aina konsernikohtaisesti
$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
$result = pupe_query($query);
$konsernit = "";

while ($row = mysql_fetch_array($result)) {
  $konsernit .= " '".$row["yhtio"]."' ,";
}
$konsernit = " and yhtio in (".substr($konsernit, 0, -1).") ";


for ($i=800; $i < 2200; $i++) {

  $kello = sprintf('%02d', $tun).':'.sprintf('%02d', $min);

  if ($i == 800) {
    $query = "  select if('$year-$mymonth-$myday 00:00:00' > pvmalku, '08:00', substring(pvmalku,12,5)) aikaalku, substring(pvmloppu,12,5) aikaloppu, tapa, left(pvmloppu,10) pvmloppu, left(pvmalku,10) pvmalku, tunnus, kuka, kentta01, kentta02, kentta03, kentta04, kentta05, yhtio
          from kalenteri
          where pvmalku < '$year-$mymonth-$myday'
          and pvmloppu >= '$year-$mymonth-$myday'
          and tyyppi='varauskalenteri'
          and tapa='$toim'
          $konsernit";
    $lres = pupe_query($query);

    if (mysql_num_rows($lres) == 0) {
      $query = "  select if('$year-$mymonth-$myday 00:00:00' > pvmalku, '08:00', substring(pvmalku,12,5)) aikaalku, substring(pvmloppu,12,5) aikaloppu, tapa, left(pvmloppu,10) pvmloppu, left(pvmalku,10) pvmalku, tunnus, kuka, kentta01, kentta02, kentta03, kentta04, kentta05, yhtio
            from kalenteri
            where pvmalku <='$year-$mymonth-$myday $kello'
            and pvmloppu >= '$year-$mymonth-$myday $kello'
            and tyyppi='varauskalenteri'
            and tapa='$toim'
            $konsernit";
    }
  }
  else {
    $query = "  select if('$year-$mymonth-$myday 00:00:00' > pvmalku, '08:00', substring(pvmalku,12,5)) aikaalku, substring(pvmloppu,12,5) aikaloppu, tapa, left(pvmloppu,10) pvmloppu, left(pvmalku,10) pvmalku, tunnus, kuka, kentta01, kentta02, kentta03, kentta04, kentta05, yhtio
          from kalenteri
          where pvmalku ='$year-$mymonth-$myday $kello'
          and pvmloppu >= '$year-$mymonth-$myday $kello'
          and tyyppi='varauskalenteri'
          and tapa='$toim'
          $konsernit";
  }

  $result = pupe_query($query);

  if (mysql_num_rows($result) != 0) {

    $row = mysql_fetch_array($result);

    if (str_replace('-', '', $row["pvmloppu"]) > $year.$mymonth.$myday) {
      $row["aikaloppu"] = "22:00-->";
    }
    if (str_replace('-', '', $row["pvmalku"]) < $year.$mymonth.$myday) {
      $row["aikaalku"] = "-->08:00";
    }


    echo "  <tr>
        <td nowrap><b>$row[aikaalku]-$row[aikaloppu]</b></td>
        <td><a href='$PHP_SELF?tunnus=$row[tunnus]&year=$year&month=$month&day=$day&tee=POISTA&toim=$toim'>".t("Poista")."</a></td>";

    $query = "  select nimi
          from kuka
          where kuka='$row[kuka]'
          $konsernit";
    $result = pupe_query($query);
    $krow = mysql_fetch_array($result);

    if ($krow["nimi"] != '') {
      echo "<td>$krow[nimi] ($row[yhtio])</td>";
    }
    else {
      echo "<td>$row[kuka] ($row[yhtio])</td>";
    }


    echo "<td colspan='2'>";

    echo "<a href='$PHP_SELF?tee=NAYTA&year=$year&month=$month&day=$day&tunnus=$row[tunnus]&toim=$toim'>".t("Lis‰tiedot")."</a>";
    echo "  &nbsp;&nbsp;$row[kentta05]</td>
        </tr>";

    $aika = explode(':', $row["aikaloppu"]);
    $tun = $aika[0];
    $min = $aika[1];

    if ($tun <= 16) {
      $min = $min;
    }
    else {
      $min = $min;
    }
    if ($min == 60) {
      $tun = $tun+1;
      $min = "00";
    }
  }
  else {
    echo "  <tr>
        <td><b>$kello</b></td>
        <td>";

    if ($tun.$min <= 2130) {
      echo "<a href='$PHP_SELF?kello=$kello&year=$year&month=$month&day=$day&tee=SYOTA&toim=$toim'>".t("Lis‰‰")."</a>";
    }
    echo "  </td>
        <td></td>
        <td colspan='2'></td>
        </tr>";

    if ($tun <= 21) {
      $min = $min+30;
    }
    else {
      $min = $min+60;
    }

    if ($min == 60) {
      $tun = $tun+1;
      $min = "00";
    }
  }
  $i = $tun.$min;
}

echo "</table>";


//pikkukalenteri
echo "<table width='250'>
  <tr>
  <td align='center' colspan='8' class='back'>
  <form action = '?day=$day&year=$year&toim=$toim' method='post'>
  <select name='month' Onchange='submit();'>";

$i=1;
foreach ($MONTH_ARRAY as $val) {
  if ($i == $month) {
    $sel = "selected";
  }
  else {
    $sel = "";
  }
  echo "<option value='$i' $sel>$val</option>";
  $i++;
}

echo "  </select>
    </form>
    </td>
    </tr>
    <tr>
    <th>Vk.</th>
    <th>Ma</font></th>
    <th>Ti</font></th>
    <th>Ke</font></th>
    <th>To</font></th>
    <th>Pe</font></th>
    <th>La</font></th>
    <th>Su</font></th>
    </tr>";

echo "<tr><th>".date("W", mktime(0, 0, 0, $month, 1, $year))."</th>";

// kirjotetaan alkuun tyhji‰ soluja
for ($i=0; $i < weekday_number("1", $month, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

// kirjoitetaan p‰iv‰m‰‰r‰t taulukkoon..
for ($i=1; $i <= days_in_month($month, $year); $i++) {
  $pva = sprintf('%02d', $i);

  $query = "  select tunnus
        from kalenteri
        where pvmalku <= '$year-$mymonth-$pva 23:59:59'
        and pvmloppu >= '$year-$mymonth-$pva 00:00:00'
        and tyyppi='varauskalenteri'
        and tapa='$toim'
        $konsernit";
  $result = pupe_query($query);

  if (date("Y-n-j") == "$year-$mymonth-$myday") {
    $font=$today;
    $num =$i;
  }
  else {
    $font="<font class='message'>";
    $num =$i;
  }

  if ($day == $i) {
    $style = "style=\"border : 1px solid #FF0000;\"";
  }
  else {
    $style = "";
  }

  if (mysql_num_rows($result) != 0) {
    $style .= " class='tumma'";
  }

  echo "  <td align='center' $style><a href='$PHP_SELF?session=$session&year=$year&month=$month&day=$i&toim=$toim'>$font $num</font></a></td>\n";

  // jos kyseess‰ on sunnuntai, tehd‰‰n rivinvaihto..
  if (weekday_number($i, $month, $year) == 6) {
    // kirjotetaan viikon numero jos seuraava viikko on olemassa
    if (days_in_month($month, $year) != $i) {
      $weeknro = date("W", mktime(0, 0, 0, $month, $i+1, $year));
      echo "</tr><tr><th>$weeknro</th>";
    }

  }
}

//kirjoitetaan loppuun tyhji‰ soluja
for ($i=0; $i < 6-weekday_number(days_in_month($month, $year), $month, $year); $i++) {
  echo "<td>&nbsp;</td>";
}

echo "</tr>";

echo "<tr><td class='back' align='center' colspan='8'><a href='$PHP_SELF?day=1&month=$backmmonth&year=$backymonth&toim=$toim'>".t("Edellinen")."</a>  - <a href='$PHP_SELF?day=1&month=$nextmmonth&year=$nextymonth&toim=$toim'>".t("Seuraava")."</a></td></tr>\n";

echo "</table></th></tr></table>";

require "../inc/footer.inc";
