<?php
require "inc/parametrit.inc";

echo "<font class='head'>".t("Kopioi käyttäjäprofiileja").":</font><hr>";

if (isset($fromkuka)) {
  $fromkuka = explode('##', $fromkuka);

  $fromyhtio = $fromkuka[1];
  $fromkuka  = $fromkuka[0];
}

if ($copyready != '') {
  echo "<font class='message'>".t("Kopioitiin käyttäjäprofiili")." $fromkuka ($fromyhtio) --> Yhtiölle $tokuka</font><br><br>";

  //haetaan profiili kaikki tiedot
  $query = "SELECT * FROM oikeu where kuka='$fromkuka' and profiili='$fromkuka' and yhtio='$fromyhtio'";
  $kukar = pupe_query($query);

  //poistetaan se uudelta yhtiöltä jos se on olemassa
  $query = "DELETE from oikeu
            where kuka   = '$fromkuka'
            and profiili = '$fromkuka'
            and yhtio    = '$tokuka'";
  $delre = pupe_query($query);

  while ($row = mysql_fetch_array($kukar)) {
    $query = "INSERT into oikeu SET
              kuka       = '{$fromkuka}',
              sovellus   = '{$row['sovellus']}',
              nimi       = '{$row['nimi']}',
              alanimi    = '{$row['alanimi']}',
              paivitys   = '{$row['paivitys']}',
              nimitys    = '{$row['nimitys']}',
              jarjestys  = '{$row['jarjestys']}',
              jarjestys2 = '{$row['jarjestys2']}',
              profiili   = '{$fromkuka}',
              yhtio      = '{$tokuka}',
              hidden     = '{$row['hidden']}',
              laatija    = '{$kukarow['kuka']}',
              luontiaika = now(),
              muutospvm  = now(),
              muuttaja   = '{$kukarow['kuka']}'";
    $upres = pupe_query($query);
  }

  //päivitetään myös käyttäjien tiedot joilla on tämä profiili
  $query = "SELECT *
            FROM kuka
            WHERE yhtio    = '$tokuka'
            and profiilit != ''";
  $kres = pupe_query($query);

  while ($krow = mysql_fetch_array($kres)) {

    $profiilit = explode(',', $krow["profiilit"]);

    if (count($profiilit) > 0) {
      //käydään läpi käyttäjän kaikki profiilit
      $triggeri = "";
      foreach ($profiilit as $prof) {
        //jos tämä kyseinen profiili on ollut käyttäjällä aikaisemmin, niin joudumme päivittämään oikeudet
        if (strtoupper($prof) == strtoupper($fromkuka)) {
          $triggeri = "HAPPY";
        }
      }

      if ($triggeri == "HAPPY") {
        //poistetaan käyttäjän vanhat
        $query = "DELETE FROM oikeu
                  WHERE yhtio = '$tokuka'
                  and kuka    = '$krow[kuka]'
                  and lukittu = ''";
        $pres = pupe_query($query);

        //käydään uudestaan profiili läpi
        foreach ($profiilit as $prof) {
          $query = "SELECT *
                    FROM oikeu
                    WHERE yhtio  = '$tokuka'
                    and kuka     = '$prof'
                    and profiili = '$prof'";
          $pres = pupe_query($query);

          while ($trow = mysql_fetch_array($pres)) {
            //joudumme tarkistamaan ettei tätä oikeutta ole jo tällä käyttäjällä.
            //voi olla esim jos se on lukittuna annettu
            $query = "SELECT yhtio
                      FROM oikeu
                      WHERE yhtio  = '$tokuka'
                      AND kuka     = '$krow[kuka]'
                      AND sovellus = '$trow[sovellus]'
                      AND nimi     = '$trow[nimi]'
                      AND alanimi  = '$trow[alanimi]'";
            $tarkesult = pupe_query($query);

            if (mysql_num_rows($tarkesult) == 0) {
              $query = "INSERT into oikeu
                        SET
                        kuka       = '$krow[kuka]',
                        user_id    = '{$krow['tunnus']}',
                        sovellus   = '$trow[sovellus]',
                        nimi       = '$trow[nimi]',
                        alanimi    = '$trow[alanimi]',
                        paivitys   = '$trow[paivitys]',
                        nimitys    = '$trow[nimitys]',
                        jarjestys  = '$trow[jarjestys]',
                        jarjestys2 = '$trow[jarjestys2]',
                        yhtio      = '$tokuka',
                        laatija    = '{$kukarow['kuka']}',
                        luontiaika = now(),
                        muutospvm  = now(),
                        muuttaja   = '{$kukarow['kuka']}'";
              $rresult = pupe_query($query);
            }
          }
        }
        echo "<font class='message'>Päivitettiin käyttäjän $krow[kuka] profiili $prof</font><br>";
      }
    }
  }

  // päiviteään kuka-tauluun mitkä käyttäjät on aktiivisia ja mitkä poistettuja
  paivita_aktiiviset_kayttajat();
  paivita_aktiiviset_kayttajat("", $tokuka);

  $fromkuka='';
  $tokuka='';
  $fromyhtio='';
  $toyhtio='';
}

$query  = "SELECT distinct yhtio, nimi
           from yhtio
           where konserni  = '$yhtiorow[konserni]'
           and konserni   != ''";
$result = pupe_query($query);

if (mysql_num_rows($result) == 0) {
  echo "<br><font class='message'>".t("Palvelimella ei ole muita yhtiöitä")."!</font>";
  exit;
}

$sovyhtiot = "'$kukarow[yhtio]'";

while ($prow = mysql_fetch_array($result)) {
  $sovyhtiot .= ",'$prow[yhtio]'";
}

// tehdään käyttäjälistaukset
$query = "SELECT distinct kuka, profiili, yhtio
          FROM oikeu
          WHERE kuka    = profiili
          and profiili != ''
          and yhtio     in ($sovyhtiot)";
$kukar = pupe_query($query);

echo "<br><form method='post'>";
echo "<input type='hidden' name='tila' value='copy'>";

echo "<font class='message'>".t("Kopioitava profiili").":</font>";

echo "<table><tr><th align='left'>".t("Profiili").":</th><td>
<select name='fromkuka' onchange='submit()'>
<option value=''>".t("Valitse profiili")."</option>";

while ($kurow=mysql_fetch_array($kukar)) {
  if ($fromkuka==$kurow["profiili"] and $fromyhtio == $kurow["yhtio"]) $select='selected';
  else $select='';

  echo "<option $select value='$kurow[profiili]##$kurow[yhtio]'>$kurow[profiili] ($kurow[yhtio])</option>";
}

echo "</select></td></tr>";
echo "</table>";

echo "<br><br><font class='message'>".t("Mille yhtiölle kopioidaan").":</font>";

// tehdään käyttäjälistaukset

$query = "SELECT distinct yhtio, nimi FROM yhtio WHERE yhtio in ($sovyhtiot) and yhtio!='$fromyhtio'";
$kukar = pupe_query($query);

echo "<table><tr><th align='left'>".t("Yhtiö").":</th><td>
<select name='tokuka' onchange='submit()'>
<option value=''>".t("Valitse yhtiö")."</option>";

while ($kurow=mysql_fetch_array($kukar)) {
  if ($tokuka==$kurow["yhtio"]) {
    $select = 'selected';
    $tonimi = $kurow["nimi"];
  }
  else $select='';

  echo "<option $select value='$kurow[yhtio]'>$kurow[nimi] ($kurow[yhtio])</option>";
}

echo "</select></td></tr>";
echo "</table>";

if (($tokuka!='') and ($fromkuka!='')) {
  echo "<br><br>";
  echo "<input type='submit' name='copyready' value='".t("Kopioi käyttöprofiili")." $fromkuka --> Yhtiölle $tonimi'>";
}

echo "</form>";

require "inc/footer.inc";
