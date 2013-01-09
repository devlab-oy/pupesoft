
<?php

require ("inc/parametrit.inc");

/** Lis‰‰ tuotteen ketjuun
 */
function lisaa_tuote($tuoteno = '', $vastaava, $ketju_id = '') {
    global $kukarow;

    // Tarkistetaan ett‰ tuote on olemassa
    $tuote = hae_tuote($vastaava);

    if ($tuote) {

        // Jos ketju on setattu, lis‰t‰‰n tuote haluttuun ketjuun
        if ($ketju_id != '') {

            // Tarkistetaan ett‰ tuote ei ole miss‰‰n ketjussa p‰‰tuotteena
            $paatuote = false;
            if (onko_paatuote($vastaava)) {
                $paatuote = true;
                echo "<font class='error'>".t("Lis‰ys ei onnistu! Tuote")." $vastaava ".t("on p‰‰tuotteena jossain toisessa ketjussa")."!</font><br><br>";
            }

            // Tarkistetaan ett‰ tuote ei jo ole kyseisess‰ ketjussa
            $query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$vastaava}' AND id='{$ketju_id}'";
            $result = pupe_query($query);

            // Tuote on jo ketjussa
            if (mysql_num_rows($result) > 0) {
                echo "<font class='error'>".t("Lis‰ys ei onnistu! Tuote")." $vastaava ".t("on jo ketjussa")." $ketju_id!</font><br><br>";
            }
            // Lis‰t‰‰n tuote haluttuun ketjuun
            elseif ($paatuote == false) {
                $query  = "INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                            VALUES ('$ketju_id', '{$tuote['tuoteno']}', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $result = pupe_query($query);
            }
        }
        //
        elseif ($tuoteno != '' and $ketju_id == '') {

            // Etsit‰‰n is‰ tuotetta (haettu tuoteno)
            $query  = "SELECT * FROM vastaavat WHERE tuoteno = '$tuoteno' AND yhtio = '$kukarow[yhtio]'";
            $result = pupe_query($query);

            if (mysql_num_rows($result) != 0) {
                //jos on, otetaan ID luku talteen...
                $row    = mysql_fetch_array($result);
                $fid    = $row['id'];
            }

            // Etsit‰‰n lis‰tt‰v‰‰ tuotetta
            // Vastaavissa tarkistetaan vain ett‰ tuote ei ole p‰‰tuotteena miss‰‰n toisessa ketjussa.
            // Jos is‰tuotetta ei lˆytynyt eik‰ lis‰tt‰v‰ tuote ole miss‰‰n ketjussa p‰‰tuotteena,
            // voidaan tuotteista tehd‰ uusi ketju
            $query  = "SELECT * FROM vastaavat WHERE tuoteno = '$vastaava' AND yhtio = '$kukarow[yhtio]' AND jarjestys='1'";
            $result = pupe_query($query);

            if (mysql_num_rows($result) != 0) {
                //vastaava on jo lis‰tty.. otetaan senki id..
                $row    = mysql_fetch_array($result);
                $cid    = $row['id'];
            }

            //jos kumpaakaan ei lˆytynyt...
            if (($cid == "") and ($fid == "")) {
                //silloin t‰m‰ on eka vastaava.. etsit‰‰n sopiva ID.
                $query  = "SELECT max(id) FROM vastaavat";
                $result = pupe_query($query);
                $row    = mysql_fetch_array($result);
                $id     = $row[0]+1;

                //lis‰t‰‰n "is‰ tuote"...
                $query  = " INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                            VALUES ('$id', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $result = pupe_query($query);

                // lis‰t‰‰n vastaava tuote...
                $query  = " INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                            VALUES ('$id', '$vastaava', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $result = pupe_query($query);
            }

            //lapsi on lˆytynyt, is‰‰ ei
            if (($cid != "") and ($fid == "")) {
                //lis‰t‰‰n "is‰ tuote"...
                $query  = " INSERT INTO vastaavat (id, tuoteno, yhtio, laatija, luontiaika, muutospvm, muuttaja)
                            VALUES ('$cid', '$tuoteno', '$kukarow[yhtio]', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $result = pupe_query($query);
            }

            //is‰ on lˆytynyt, lapsi ei
            if (($fid != "") and ($cid == "")) {
                # Siirret‰‰n ketjun muita eteenp‰in jarjestys + 1
                $query = "UPDATE vastaavat SET jarjestys=jarjestys+1
                            WHERE jarjestys!=0 AND id='$fid' AND yhtio='{$kukarow['yhtio']}'";
                $result = pupe_query($query);

                # Lis‰t‰‰n uusi aina p‰‰tuotteeksi jarjestys=1
                //lis‰t‰‰n vastaava p‰‰tuotteeksi
                $query  = " INSERT INTO vastaavat (id, tuoteno, yhtio, jarjestys, laatija, luontiaika, muutospvm, muuttaja)
                            VALUES ('$fid', '$vastaava', '$kukarow[yhtio]', '1', '$kukarow[kuka]', now(), now(), '$kukarow[kuka]')";
                $result = pupe_query($query);
            }

            //kummatkin lˆytyiv‰t.. ja ne korvaa toisensa
            if ($fid != "" and $cid != "" and $fid == $cid) {
                 echo "<font class='error'>".t("Tuotteet")." $vastaava <> $tuoteno ".t("ovat jo vastaavia")."!</font><br><br>";

            }
            elseif ($fid != "" and $cid != "" ) {
                echo "<font class='error'>".t("Tuotteet")." $vastaava, $tuoteno ".t("kuuluvat jo eri vastaavuusketjuihin")."!</font><br><br>";
            }
        }
        else {
            echo "<font class='error'>".t("Odottamaton virhe")."!</font><br><br>";
        }
    }
}

/** Hakee tuotteen tiedot
 */
function hae_tuote($tuoteno) {
    global $kukarow;

    if (empty($tuoteno)) exit("ei voida hakea olematonta tuotetta!");

    // Haetaan tuotteen tiedot
    $query = "SELECT * FROM tuote WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
    $result = pupe_query($query);
    $tuote = mysql_fetch_assoc($result);

    return $tuote;
}

/** Hakee vastaavat tuoteketjun
 */
function hae_vastaavat_ketjut($tuoteno) {
    global $kukarow;

    // Haetaan ketjut johon tuote kuuluu
    $query = "SELECT id FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
    $result = pupe_query($query);

    // Haetaan ketjujen tuotteet
    $ketjut = array();
    while ($ketju = mysql_fetch_array($result)) {
        $ketju_query = "SELECT * FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND id='{$ketju['id']}'
                        ORDER BY if(jarjestys=0, 9999, jarjestys), tuoteno";
        $ketju_result = pupe_query($ketju_query);

        $tuotteet = array();
        while ($tuote = mysql_fetch_assoc($ketju_result)) {
            $tuotteet[] = $tuote;
        }

        $ketjut[$ketju['id']] = $tuotteet;
    }

    return $ketjut;
}

/** Tarkistaa onko tuote mink‰‰n ketjun p‰‰tuotteena
 */
function onko_paatuote($tuoteno) {

    // Haetaan kaikki tuotteen ketjut
    $ketjut = hae_vastaavat_ketjut($tuoteno);

    // Loopataan ketjut l‰pi ja tarkistetaan p‰‰tuote
    foreach ($ketjut as $id => $ketju) {
        if (strtoupper($tuoteno) == strtoupper($ketju[0]['tuoteno'])) {
            return true;
        }
    }

    return false;
}

echo "<font class='head'>".t("Vastaavien yll‰pito")."</font><hr>";

echo "<form method='get' name='etsituote' autocomplete='off'>
      ".t("Etsi tuotetta")." <input type='text' name='tuoteno'>
      <input type='submit' value='".t("Hae")."'>
      </form><br><br>";

if (!isset($tee)) $tee = '';

// Poistetaan vastaava ketjusta
if ($tee == 'del') {
    $query = "DELETE FROM vastaavat WHERE yhtio='{$kukarow['yhtio']}' AND tunnus='{$tunnus}'";
    $result = pupe_query($query);
}

if ($tee == 'vaihtoehtoinen') {
    //haetaan tuotteen id.. k‰ytt‰j‰st‰v‰llist‰..
    $query  = "SELECT * FROM vastaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $row    = mysql_fetch_array($result);
    $id     = $row['id'];

    // Jos setattu vaihetoehtoiseksi?
    if ($vaihtoehtoinen == 'true' and $row['vaihtoehtoinen'] != 'K') {

        // Tuote voi olla useammassa ketjussa vain vaihtoehtoinen tai vastaava. Ei molempia.
        $query = "UPDATE vastaavat SET vaihtoehtoinen='K' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
        $result = pupe_query($query);
    }
    else if ($vaihtoehtoinen != true and $row['vaihtoehtoinen'] == 'K') {
        $query = "UPDATE vastaavat SET vaihtoehtoinen='' WHERE yhtio='{$kukarow['yhtio']}' AND tuoteno='{$tuoteno}'";
        $result = pupe_query($query);
    }
}

if ($tee == 'muutaprio') {
    //haetaan poistettavan tuotteen id.. k‰ytt‰j‰st‰v‰llist‰..
    $query  = "SELECT * FROM vastaavat WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);
    $row    = mysql_fetch_array($result);
    $id     = $row['id'];

    // Tarkistetaan onko 'seuraava' ketjun p‰‰tuote p‰‰tuotteena jossakin toisessa ketjussa?
    if (onko_paatuote($row['tuoteno'])) {
        echo "<font class='error'>".t("Huom! Muutit p‰‰tuotetta tai tuote on p‰‰tuotteena jossakin toisessa ketjussa")."!</font><br><br>";

    }

    // Siirret‰‰n ketjun muita eteenp‰in, jarjestys + 1
    if ($prio != 0 and $prio != $row['jarjestys']) {
        $query = "UPDATE vastaavat SET jarjestys=jarjestys+1, muuttaja='{$kukarow['kuka']}', muutospvm=now()
                    WHERE jarjestys!=0 AND id='$id' AND yhtio='{$kukarow['yhtio']}' AND tunnus!=$tunnus AND jarjestys >= $prio";
        $result = pupe_query($query);
    }

    // muutetaan prioriteetti
    $query  = "     UPDATE vastaavat SET
                    jarjestys = '$prio',
                    muutospvm = now(),
                    muuttaja = '$kukarow[kuka]'
                    WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);
}

if ($tee == 'add') {

    // tutkitaan onko lis‰tt‰v‰ tuote oikea tuote...
    if (!hae_tuote($vastaava)) {
        echo "<font class='error'>".t("Lis‰ys ei onnistu! Tuotetta")." $vastaava ".t("ei lˆydy")."!</font><br><br>";
    }
    // Lis‰t‰‰n haluttuun ketjuun
    else if (!empty($ketju_id)) {
        lisaa_tuote($tuoteno, $vastaava, $ketju_id);
    }
    else {
        lisaa_tuote($tuoteno, $vastaava);
    }
}

if ($tuoteno != '') {

    echo "<font class='head'>".t("Tuotenumero").": $tuoteno</font><hr>";

    $tuote = hae_tuote($tuoteno);

    // Jos tuote on olemassa
    if (!$tuote) {
        echo "<br><font class='error'>".t("Tuotenumeroa")." $tuoteno ".t("ei ole perustettu")."!</font><br>";
    }
    else {
        $ketjut = hae_vastaavat_ketjut($tuoteno);
        // Jos ketjuja ei lˆytynyt
        if (!$ketjut) {
            echo "<br><font class='message'>".t("Tuotteella ei ole vastaavia tuotteita")."!</font>";

            echo "<form method='post' autocomplete='off'>
                    <input type='hidden' name='tuoteno' value='$tuoteno'>
                    <input type='hidden' name='tee' value='add'>
                    <hr>";

            echo t("Lis‰‰ vastaava tuote").": ";

            echo "<input type='text' name='vastaava'>
                    <input type='submit' value='".t("Lis‰‰")."'>
                    </form>";
        }
        // Loopataan ketjut l‰pi
        else {
            foreach($ketjut as $id => $tuotteet) {
                echo "<br><table>";
                echo "<tr><th colspan=3>Ketju $id</th></tr>";
                echo "<tr>";
                echo "<th>".t("Vastaavia tuotteita")."</td>";
                echo "<th>".t("J‰rjestys")."</th>";
                echo "<th>".t("Vaihtoehtoinen")."</th>";
                echo "<td class='back'></td></tr>";

                // Loopataan ketjun tuotteet l‰pi
                foreach ($tuotteet as $tuote) {

                    // Tsekataan ett‰ ketjun tuotteet ovat olemassa
                    $error = '';
                    if (!hae_tuote($tuote['tuoteno'])) {
                        $error = "<font class='error'>(".t("Tuote ei en‰‰ rekisteriss‰")."!)</font>";
                    }

                    echo "<tr><td>$tuote[tuoteno] $error</td>";
                    echo "<form method='post' autocomplete='off'>
                        <td><input size='5' type='text' name='prio' value='$tuote[jarjestys]'>
                        <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
                        <input type='hidden' name='tee' value='muutaprio'></td>
                        </form>";

                    $checked = ($tuote['vaihtoehtoinen'] == 'K') ? 'checked' : '';
                    echo "<td><form method='post'>
                        <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
                        <input type='hidden' name='tuoteno' value='$tuote[tuoteno]'>
                        <input type='hidden' name='tee' value='vaihtoehtoinen'>
                        <input type='checkbox' name='vaihtoehtoinen' value='true' onclick='submit()' $checked>
                        </form></td>";

                    echo"<form method='post'>
                        <td class='back'>
                        <input type='hidden' name='tunnus' value='$tuote[tunnus]'>
                        <input type='hidden' name='tee' value='del'>
                        <input type='submit' value='".t("Poista")."'>
                        </td>
                        </form>
                        </tr>";
                }
            echo "</table>";

            echo "<form method='post' autocomplete='off'>
                    <input type='hidden' name='tuoteno' value='$tuoteno'>
                    <input type='hidden' name='ketju_id' value='$id'>
                    <input type='hidden' name='tee' value='add'>
                    <hr>";
            echo t("Lis‰‰ vastaava tuote").": ";

            echo "<input type='text' name='vastaava'>
                    <input type='submit' value='".t("Lis‰‰")."'>
                    </form><br>";
            }
        }
    }
}

$formi = 'etsituote';
$kentta = 'tuoteno';

require "inc/footer.inc";