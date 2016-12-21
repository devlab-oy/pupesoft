<?php
require "inc/parametrit.inc";

echo " <!-- Enabloidaan shiftill‰ checkboxien chekkaus //-->
    <script src='inc/checkboxrange.js'></script>

    <script language='javascript' type='text/javascript'>
      $(document).ready(function(){
        $(\".shift\").shiftcheckbox();
      });
    </script>";

echo "<font class='head'>".t("K‰ytt‰j‰profiilit").":</font><hr>";

//tehd‰‰n tsekki, ett‰ ei tehd‰ profiilia samannimiseksi kuin joku k‰ytt‰j‰
if ($profiili != '') {
  $query = "SELECT nimi
            FROM kuka use index (kuka_index)
            WHERE kuka='$profiili' and yhtio='$kukarow[yhtio]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    $tee = "";
    $profiili = "";
    echo "<br><font class='error'>".t("VIRHE: Profiilin nimi on jo k‰ytˆss‰. Valitse toinen nimi")."</font><br><br>";
  }
}

if ($tee == 'POISTA' and $profiili != "") {

  $query = "DELETE
            FROM oikeu
            WHERE yhtio  = '$kukarow[yhtio]'
            AND kuka     = '$profiili'
            AND profiili = '$profiili'
            AND lukittu  = ''";
  $result = pupe_query($query);
  $maara = mysql_affected_rows();

  // p‰ivite‰‰n kuka-tauluun mitk‰ k‰ytt‰j‰t on aktiivisia ja mitk‰ poistettuja
  paivita_aktiiviset_kayttajat();

  echo "<font class='message'>".t("Poistettiin")." $maara ".t("rivi‰")."</font><br>";

  $profiili = "";
  $tee = '';
}

// tehd‰‰n oikeuksien p‰ivitys
if ($tee == 'PAIVITA' and $profiili != "") {

  // poistetaan ihan aluksi kaikki.
  $query = "DELETE
            FROM oikeu
            WHERE yhtio  = '$kukarow[yhtio]'
            AND kuka     = '$profiili'
            AND profiili = '$profiili'";

  if ($sovellus != '' and $sovellus != 'kaikki_sovellukset') {
    $query .= " AND sovellus='$sovellus'";
  }

  $result = pupe_query($query);

  // sitten tutkaillaan onko jotain ruksattu...
  if (count($valittu) != 0) {
    foreach ($valittu as $inde => $rastit) { // Tehd‰‰n oikeudet
      list ($nimi, $alanimi, $sov) = explode("#", $rastit);

      // haetaan menu itemi
      $query = "SELECT nimi, nimitys, jarjestys, alanimi, sovellus, jarjestys2, hidden
                FROM oikeu
                WHERE yhtio  = '$kukarow[yhtio]'
                AND kuka     = ''
                AND sovellus = '$sov'
                AND nimi     = '$nimi'
                AND alanimi  = '$alanimi'";
      $result = pupe_query($query);
      $trow = mysql_fetch_assoc($result);

      $paivitys_ins = '';

      if (isset($paivitys[$inde]) and $paivitys[$inde] != "") {
        $paivitys_ins = '1';
      }

      $query = "INSERT into oikeu
                SET kuka  = '$profiili',
                profiili   = '$profiili',
                sovellus   = '$trow[sovellus]',
                nimi       = '$trow[nimi]',
                alanimi    = '$trow[alanimi]',
                paivitys   = '$paivitys_ins',
                lukittu    = '',
                nimitys    = '$trow[nimitys]',
                jarjestys  = '$trow[jarjestys]',
                jarjestys2 = '$trow[jarjestys2]',
                hidden     = '$trow[hidden]',
                yhtio      = '$kukarow[yhtio]',
                laatija    = '{$kukarow['kuka']}',
                luontiaika = now(),
                muutospvm  = now(),
                muuttaja   = '{$kukarow['kuka']}'";
      $result = pupe_query($query);
    }
    echo "<font class='message'>".t("K‰yttˆoikeudet p‰ivitetty")."!</font><br>";
  }

  //p‰ivitet‰‰n k‰ytt‰jien profiilit (joilla on k‰ytˆss‰ t‰m‰ profiili)
  $query = "SELECT *
            FROM kuka
             WHERE yhtio  = '$kukarow[yhtio]'
            AND profiilit like '%$profiili%'";
  $kres = pupe_query($query);

  while ($krow = mysql_fetch_assoc($kres)) {
    $profiilit = explode(',', $krow["profiilit"]);

    if (count($profiilit) > 0) {
      //k‰yd‰‰n l‰pi k‰ytt‰j‰n kaikki profiilit
      $triggeri = "";
      foreach ($profiilit as $prof) {
        //jos t‰m‰ kyseinen profiili on ollut k‰ytt‰j‰ll‰ aikaisemmin, niin joudumme p‰ivitt‰m‰‰n oikeudet
        if (strtoupper($prof) == strtoupper($profiili)) {
          $triggeri = "HAPPY";
        }
      }

      if ($triggeri == "HAPPY") {
        // poistetaan k‰ytt‰j‰n vanhat
        $query = "DELETE FROM oikeu
                  WHERE yhtio   = '$kukarow[yhtio]'
                  AND kuka      = '$krow[kuka]'
                  AND kuka     != ''
                  AND profiili  = ''
                  AND lukittu   = ''";
        $pres = pupe_query($query);

        // k‰yd‰‰n uudestaan profiili l‰pi
        foreach ($profiilit as $prof) {
          $query = "SELECT *
                    FROM oikeu
                    WHERE yhtio  = '$kukarow[yhtio]'
                    AND kuka     = '$prof'
                    AND profiili = '$prof'";
          $pres = pupe_query($query);

          while ($trow = mysql_fetch_assoc($pres)) {
            // joudumme tarkistamaan ettei t‰t‰ oikeutta ole jo t‰ll‰ k‰ytt‰j‰ll‰.
            // voi olla esim jos se on lukittuna annettu
            $query = "SELECT yhtio, paivitys
                      FROM oikeu use index (sovellus_index)
                      WHERE yhtio  = '$kukarow[yhtio]'
                      AND kuka     = '$krow[kuka]'
                      AND sovellus = '$trow[sovellus]'
                      AND nimi     = '$trow[nimi]'
                      AND alanimi  = '$trow[alanimi]'";
            $tarkesult = pupe_query($query);

            if (mysql_num_rows($tarkesult) == 0) {
              $query = "INSERT into oikeu
                        SET kuka  = '$krow[kuka]',
                        user_id    = '{$krow['tunnus']}',
                        sovellus   = '$trow[sovellus]',
                        nimi       = '$trow[nimi]',
                        alanimi    = '$trow[alanimi]',
                        paivitys   = '$trow[paivitys]',
                        nimitys    = '$trow[nimitys]',
                        jarjestys  = '$trow[jarjestys]',
                        jarjestys2 = '$trow[jarjestys2]',
                        hidden     = '$trow[hidden]',
                        yhtio      = '$kukarow[yhtio]',
                        laatija    = '{$kukarow['kuka']}',
                        luontiaika = now(),
                        muutospvm  = now(),
                        muuttaja   = '{$kukarow['kuka']}'";
              $rresult = pupe_query($query);
            }
            else {
              $tarkrow = mysql_fetch_assoc($tarkesult);

              if ($trow["paivitys"] == 1 and $tarkrow["paivitys"] != 1) {
                // Meill‰ ei v‰ltt‰m‰tt‰ ollut p‰ivitysoikeutta, koska aiempi checki ei huomio sit‰. Lis‰t‰‰n p‰ivitysoikeus.
                $query = "UPDATE oikeu
                          SET paivitys   = 1,
                          muutospvm    = now(),
                          muuttaja     = '{$kukarow['kuka']}'
                          WHERE yhtio  = '$kukarow[yhtio]'
                          AND kuka     = '$krow[kuka]'
                          AND sovellus = '$trow[sovellus]'
                          AND nimi     = '$trow[nimi]'
                          AND alanimi  = '$trow[alanimi]'";
                $tarkesult = pupe_query($query);
              }
            }
          }
        }
      }
    }
  }

  // p‰ivite‰‰n kuka-tauluun mitk‰ k‰ytt‰j‰t on aktiivisia ja mitk‰ poistettuja
  paivita_aktiiviset_kayttajat();
}

echo "<SCRIPT LANGUAGE=JAVASCRIPT>
      function verify(){
        msg = '".t("Haluatko todella poistaa t‰m‰n profiilin ja k‰ytt‰jilt‰ oikeudet t‰h‰n profiiliin?")."';

        if (confirm(msg)) {
          return true;
        }
        else {
          skippaa_tama_submitti = true;
          return false;
        }
      }
  </SCRIPT>";

echo "<table>

    <form method='post'>
    <tr>
      <th>".t("Luo uusi profiili").":</th>
      <td><input type='text' name='uusiprofiili' size='25'></td>
      <td class='back'><input type='submit' value='".t("Luo uusi profiili")."'></td>
    </tr>
  </form>
  <form method='post'>
  <input type='hidden' name='sovellus' value='$sovellus'>
  <input type='hidden' name='vainval' value='$vainval'>

  <tr>
    <th>".t("Valitse Profiili").":</th>
    <td><select name='profiili' onchange='submit()'>";

if ($uusiprofiili == "") {
  $query = "SELECT distinct profiili
            FROM oikeu
            WHERE yhtio='$kukarow[yhtio]' and profiili!=''
            ORDER BY profiili";
  $kukares = pupe_query($query);

  while ($kurow=mysql_fetch_assoc($kukares)) {
    $sel = "";

    if ($profiili == $kurow["profiili"]) {
      $sel = "SELECTED";
    }

    echo "<option value='$kurow[profiili]' $sel>$kurow[profiili]</option>";
  }
  echo "</select>";
}
else {
  echo "<option value='$uusiprofiili'>$uusiprofiili</option></select>";
  echo "<input type='hidden' name='uusiprofiili' value='$uusiprofiili'>";
}

echo "</td><td class='back'><input type='submit' value='".t("Valitse profiili")."'></form></td>";

if ($profiili != '') {
  echo "<form method='post' onSubmit = 'return verify()'>
      <input type='hidden' name='tee' value='POISTA'>
      <input type='hidden' name='profiili' value='$profiili'>
      <td class='back'><input type='submit' value='".t("Poista t‰m‰ profiili")."'></td></form>";
}

echo "</tr></table><br><br>";

if ($profiili != '') {

  if (stripos($profiili, "EXTRANET") !== FALSE) {
    $sovellus_rajaus = " and sovellus like 'Extranet%' ";
  }
  else {
    $sovellus_rajaus = " and sovellus not like 'Extranet%' ";
  }

  echo "<table>";

  $query = "SELECT distinct sovellus
            FROM oikeu
            where yhtio = '$kukarow[yhtio]'
            $sovellus_rajaus
            order by sovellus";
  $result = pupe_query($query);

  if (mysql_num_rows($result) >= 1) {

    $sel = $sovellus == "kaikki_sovellukset" ? " selected" : "";

    echo "  <form name='vaihdaSovellus' method='POST'>
        <input type='hidden' name='profiili' value='$profiili'>
        <input type='hidden' name='uusiprofiili' value='$uusiprofiili'>
        <tr><th>".t("Valitse sovellus").":</th><td>
        <select name='sovellus' onchange='submit()'>
        <option value=''>".t("Valitse")."</option>
        <option value='kaikki_sovellukset'$sel>".t("Nayta kaikki")."</option>";

    while ($orow = mysql_fetch_assoc($result)) {
      $sel = '';
      if ($sovellus == $orow["sovellus"]) {
        $sel = "SELECTED";
      }
      echo "<option value='$orow[sovellus]' $sel>$orow[sovellus]</option>";
    }
  }

  echo "</select></td></tr>";

  $chk = "";
  if ($vainval != "") {
    $chk = "CHECKED";
    $lisa = " kuka=profiili and profiili = '$profiili' ";
  }
  else {
    $lisa = " kuka = '' and profiili = '' ";
  }

  echo "<tr><th>".t("N‰yt‰ vain ruksatut")."</th><td><input type='checkbox' name='vainval' $chk onClick='submit();'></td></tr>";

  echo "</table></form>";

  if ($sovellus == "") {
    require "inc/footer.inc";
    exit;
  }

  // n‰ytet‰‰n oikeuslista
  echo "<table>";

  $query = "SELECT *
            FROM oikeu
            WHERE
            $lisa
            $sovellus_rajaus
            and yhtio = '$kukarow[yhtio]'";

  if ($sovellus != "kaikki_sovellukset") {
    $query .= " and sovellus = '$sovellus'";
  }

  $query .= "  ORDER BY sovellus, jarjestys, jarjestys2";
  $result = pupe_query($query);

  print " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
    <!--

    function toggleAll(toggleBox) {

      var currForm = toggleBox.form;
      var isChecked = toggleBox.checked;
      var nimi = toggleBox.name;

      for (var elementIdx=0; elementIdx<currForm.elements.length; elementIdx++) {
        if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,3) == nimi) {
          currForm.elements[elementIdx].checked = isChecked;
        }
      }
    }

    //-->
    </script>";

  echo "<form name='suojax' method='post'>
      <input type='hidden' name='tee' value='PAIVITA'>
      <input type='hidden' name='sovellus' value='$sovellus'>
      <input type='hidden' name='profiili' value='$profiili'>";

  $lask = 1;

  while ($orow=mysql_fetch_assoc($result)) {


    if ($vsove != $orow['sovellus']) {
      echo "<tr><td class='back colspan='5'><br></td></tr>";
      echo "<tr><th>".t("Sovellus")."</th>
        <th colspan='2'>".t("Toiminto")."</th>
        <th>".t("K‰yttˆ")."</th>
        <th>".t("P‰ivitys")."</th>
        </tr>";
    }

    $checked  = '';
    $paivit    = '';

    $oq = "SELECT *
           FROM oikeu
           WHERE yhtio  = '$kukarow[yhtio]'
           and kuka     = '$profiili'
           and profiili = '$profiili'
           and nimi     = '$orow[nimi]'
           and alanimi  = '$orow[alanimi]'
           and sovellus = '$orow[sovellus]'";
    $or = pupe_query($oq);

    if (mysql_num_rows($or) != 0) {
      $checked = "CHECKED";

      $oikeurow = mysql_fetch_assoc($or);

      if ($oikeurow["paivitys"] == 1) {
        $paivit = "CHECKED";
      }
    }

    echo "<tr><td>".t("$orow[sovellus]")."</td>";

    if ($orow['jarjestys2']!='0') {
      echo "<td class='back'>--></td><td>";
    }
    else {
      echo "<td colspan='2'>";
    }

    echo "  ".t("$orow[nimitys]");

    if ($orow["nimi"] == 'yllapito.php' and strpos($orow["alanimi"], "!!!") !== FALSE) {
      list( , $aliassetti, ) = explode("!!!", $orow["alanimi"]);
      echo "  ($aliassetti)";
    }

    echo "</td>
        <td align='center'><input type='checkbox' class='A".str_pad($lask, 6, 0, STR_PAD_LEFT)." shift' $checked   value='$orow[nimi]#$orow[alanimi]#$orow[sovellus]' name='valittu[$lask]'></td>
        <td align='center'><input type='checkbox' class='B".str_pad($lask, 6, 0, STR_PAD_LEFT)." shift' $paivit    value='$orow[nimi]#$orow[alanimi]#$orow[sovellus]' name='paivitys[$lask]'></td>
        </tr>";

    $vsove = $orow['sovellus'];
    $lask++;

  }

  echo "<tr>
      <th colspan='3'>".t("Ruksaa kaikki")."</th>
      <td align='center'><input type='checkbox' name='val' onclick='toggleAll(this);'></td>
      <td align='center'><input type='checkbox' name='pai' onclick='toggleAll(this)'></td>
      </tr>";
  echo "</table>";

  echo "<br>";
  echo "<input type='submit' value='".t("P‰ivit‰ tiedot")."'></form>";
}

require "inc/footer.inc";
