<?php

require "inc/parametrit.inc";

echo "<font class='head'>", t("Messenger"), "</font><hr>";

$query = "SELECT distinct yhtio
          FROM yhtio
          WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
$result = pupe_query($query);
$konsyhtiot = "";

while ($row = mysql_fetch_array($result)) {
  $konsyhtiot .= " '".$row["yhtio"]."' ,";
}
$konsyhtiot = " in (".substr($konsyhtiot, 0, -1).") ";

$vastaanottajat = array();

if (isset($messenger) and $message != "") {

  // jos kyseess‰ ryhm‰
  if (substr($vastaanottaja, 0, 5) == '!###!') {
    $vastaanottajat_res = t_avainsana("MESSENGER_RYHMA", "", "and selite = '".substr($vastaanottaja, 5)."'");
    while ($vastaanottaja_row = mysql_fetch_assoc($vastaanottajat_res)) {
      $vastaanottajat[] = array('vastaanottaja' => $vastaanottaja_row['selitetark'],
                                'ryhma' => substr($vastaanottaja, 5),);
    }
  }
  else {
    $vastaanottajat[] = array('vastaanottaja' => $vastaanottaja,
                              'ryhma' => '',);
  }

  foreach ($vastaanottajat as $vastaanottaja) {
    $query = "INSERT INTO messenger
              SET yhtio='$kukarow[yhtio]', kuka='$kukarow[kuka]', vastaanottaja='{$vastaanottaja['vastaanottaja']}', ryhma='{$vastaanottaja['ryhma']}', viesti='$message', status='$status', luontiaika=now()";
    $messenger_result = pupe_query($query);
  }
}

if (!isset($kpl)) {
  $kpl = 20;
}

$query = "SELECT DISTINCT if(messenger.ryhma != '', messenger.ryhma, messenger.vastaanottaja) viimeisin
          FROM kuka
          LEFT JOIN messenger ON (messenger.yhtio=kuka.yhtio AND messenger.kuka=kuka.kuka)
          WHERE kuka.extranet  = ''
          AND messenger.tunnus = (SELECT max(tunnus) FROM messenger WHERE kuka='$kukarow[kuka]')
          ORDER BY viimeisin DESC";
$viimeisin_result = pupe_query($query);
$viimeisin_row = mysql_fetch_array($viimeisin_result);

echo "<table>";
echo "<form method='post' name='messenger_form'>";
echo "<input type='hidden' name='messenger' value='X'>";
echo "<input type='hidden' name='status' value='X'>";
echo "<tr><th>".t("L‰het‰ viesti")." --> ".t("Vastaanottaja").": <select name='vastaanottaja'>";

// haetaan messenger ryhm‰t
$query = "SELECT DISTINCT selite
          FROM avainsana
          WHERE yhtio = '{$yhtiorow['yhtio']}'
          AND laji = 'MESSENGER_RYHMA'
          ORDER BY selite";
$ryhmaresult = pupe_query($query);

if (mysql_num_rows($ryhmaresult) > 0) {
  echo "<optgroup label='".t("Messenger ryhm‰t")."'>";
  while ($ryhmarow = mysql_fetch_assoc($ryhmaresult)) {
    if ($viimeisin_row["viimeisin"] == $ryhmarow["kuka"]) {
      echo "<option value='!###!{$ryhmarow['selite']}' selected>{$ryhmarow['selite']}</option>";
    }
    else {
      echo "<option value='!###!{$ryhmarow['selite']}'>{$ryhmarow['selite']}</option>";
    }
  }
}

$query = "SELECT DISTINCT kuka.nimi, kuka.kuka
          FROM kuka
          WHERE kuka.yhtio $konsyhtiot
          AND kuka.aktiivinen  = 1
          AND kuka.extranet    = ''
          AND kuka.nimi       != ''
          ORDER BY kuka.nimi, kuka.kuka";
$result = pupe_query($query);
echo "<optgroup label='".t("Messenger k‰ytt‰j‰t")."'>";

while ($userrow = mysql_fetch_array($result)) {
  if ($viimeisin_row["viimeisin"] == $userrow["kuka"]) {
    echo "<option value='{$userrow['kuka']}' selected>{$userrow['nimi']}</option>";
  }
  else {
    echo "<option value='{$userrow['kuka']}'>{$userrow['nimi']}</option>";
  }
}

echo "</select></th></tr>";
echo "<tr><td><textarea rows='20' cols='80' name='message'>";
echo "</textarea></td></tr>";

echo "<tr><td class='back' align='right'><input type='submit' name='submit' value='".t("L‰het‰")."'></td></tr>";

echo "</form></table>";

if (!isset($kuka) or $kuka == "vastaanotettua") {
  $kuka = "vastaanottaja";
  $sel2 = "selected";
  $sel3 = "";
}
else {
  $kuka = "kuka";
  $sel2 = "";
  $sel3 = "selected";
}

$query = "SELECT messenger.tunnus, messenger.status, messenger.viesti, (SELECT nimi FROM kuka WHERE kuka.yhtio $konsyhtiot AND kuka.kuka = messenger.vastaanottaja LIMIT 1) vastaanottaja, kuka.nimi, messenger.luontiaika
          FROM messenger
          JOIN kuka ON (kuka.yhtio=messenger.yhtio AND kuka.kuka=messenger.kuka)
          WHERE messenger.yhtio $konsyhtiot
          AND messenger.$kuka='$kukarow[kuka]'
          AND extranet=''
          ORDER BY messenger.luontiaika
          DESC LIMIT $kpl";
$result = pupe_query($query);

echo "<br>".t("N‰yt‰")." ";
echo "  <form method='post'>
      <select name='kpl' onChange='javascript:submit()'>";

$sel = "";
$y = 5;

for ($i = 0; $i <= 3; $i++) {

  if ($y == $kpl) {
    $sel = "selected";
  }
  else {
    $sel = "";
  }

  echo "<option value='$y' $sel>$y</option>";
  $y = $y * 2;
}

echo "    </select> ".t("viimeisint‰")."
      <select name='kuka' onChange='javascript:submit()'>
        <option value='vastaanotettua' $sel2>".t("vastaanotettua")."</option>
        <option value='l‰hetetty‰' $sel3>".t("l‰hetetty‰")."</option>
      </select>
    ".t("viesti‰").":
    </form><br><br>";

echo "<table>";
echo "<tr>";
echo "<th>".t("K‰ytt‰j‰")."</th>";
echo "<th>".t("P‰iv‰m‰‰r‰")."</th>";
echo "<th>".t("Viesti")."</th>";

echo "</tr>";

while ($row = mysql_fetch_array($result)) {

  echo "<tr>";

  echo "<td>";
  if ($kuka == "vastaanottaja") {
    echo $row['nimi'];
  }
  else {
    echo $row['vastaanottaja'];
  }
  echo "</td>";

  echo "<td>";
  echo tv1dateconv($row['luontiaika'], 'yes');
  echo "</td>";

  echo "<td>";
  echo "{$row['viesti']}";
  echo "</td>";

  echo "<td class='back'>";
  if ($row["status"] == "") {
    echo "<font color='red'>".t("Kuitattu")."</font> ";
  }
  echo "</td>";
  echo "</tr>";
}

echo "</table>";

require "inc/footer.inc";
