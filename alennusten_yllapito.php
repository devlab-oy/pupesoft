<?php
require("inc/parametrit.inc");

echo "<font class='head'>".t("Alennusten yll‰pito")."</font><hr>";
# Asiakkaan haku
echo "<form name=asiakas method=post>";
echo "<table>
    <tr>
        <th>Asiakas:</th>
        <td><input type='text' name='ytunnus'></td>
        <td><input type='submit' value='Hae'></td>
    </tr>
</table>";
echo "</form>";

if ($ytunnus != '' and $asiakasid == 0) {
    require ("inc/asiakashaku.inc");
    if ($asiakasid == 0) exit;
}

if ($asiakasid != "") {
    # Oletustyyppi
    if($tyyppi == "") $tyyppi = "ytunnus";

    # Haetaan asiakas
    $asiakas_query = "SELECT * FROM asiakas where yhtio='tkp' and tunnus='$asiakasid'";
    $asiakas_result = pupe_query($asiakas_query);

    if (mysql_num_rows($asiakas_result) != 1) {
        echo "<font class='error'>Asiakasta ei lˆytynynt.</font>";
        exit;
    }

    $asiakas = mysql_fetch_assoc($asiakas_result);

    # Asiakkaan tiedot
    echo "<table>
        <tr>
            <th>Nimi</th>
            <th>Tunnus</th>
            <th>Ytunnus</th>
        </tr>";
    echo "<tr>
        <td>$asiakas[nimi]</td>
        <td>$asiakas[tunnus]</td>
        <td>$asiakas[ytunnus]</td>
        </tr>
    </table>";
    
    echo "<form method='post' name='asiakasalennus'>";
    echo "<select name='tyyppi'onchange='submit()'>";
    
    if($tyyppi == "ytunnus") {
        echo "<option value='ytunnus' selected>Asiakasalennukset tallennetaan asiakkaan ytunnuksen mukaan</option>";
        echo "<option value='tunnus'>Asiakasalennukset tallennetaan vain valitulle asiakkalle</option>";
    }
    if($tyyppi == "tunnus") {
        echo "<option value='ytunnus'>Asiakasalennukset tallennetaan asiakkaan ytunnuksen mukaan</option>";
        echo "<option value='tunnus' selected>Asiakasalennukset tallennetaan vain valitulle asiakkalle</option>";
    }
    
    echo "</select>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "</form><br/>";

    if ($tee == "") $tee = "alennustaulukko";
}

# Uusi lomakkeen kenttien tarkistus 
if ($tee == 'uusi') {
    list($yyyy, $mm, $dd) = explode('-', $uusi_alkupvm);
    if ($uusi_alkupvm!= "" and $uusi_alkupvm != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
        echo "<font class='error'>Virheellinen alkupvm! $uusi_alkupvm</font><br/>";
        $tee = "";
    }

    list($yyyy, $mm, $dd) = explode('-', $uusi_loppupvm);
    if ($uusi_loppupvm!= "" and $uusi_loppupvm != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
        echo "<font class='error'>Virheellinen loppupvm! $uusi_loppupvm</font><br/>";
        $tee = "alennustaulukko";
    }
    if(empty($uusi_alkuryhma) or empty($uusi_loppuryhma)) {
        echo "<font class='error'>Alkuryhm‰ tai loppuryhm‰ puuttuu</font><br/>";
        $tee = "alennustaulukko";   
    }
    if (!is_numeric($uusi_alennus) and $uusi_alennus != "") {
        echo "<font class='error'>Alennuksen t‰ytyy olla numero.</font><br/>";
        $tee = "alennustaulukko";  
    }
    if (!is_numeric($uusi_minkpl) and $uusi_minkpl != "") {
        echo "<font class='error'>Minkpl t‰ytyy olla numero.</font><br/>";
        $tee = "alennustaulukko";  
    }
}

# Multiform kenttien tarkistus
if ($tee == 'lisaa') {
    foreach($alkuryhma as $i => $ryhma) {
        list($yyyy, $mm, $dd) = explode('-', $alkupvm[$i]);
        if ($alkupvm[$i] != "" and $alkupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
            echo "<font class='error'>Virheellinen alkupvm! $alkupvm[$i]</font><br/>";
            $tee = "alennustaulukko";
        }

        list($yyyy, $mm, $dd) = explode('-', $loppupvm[$i]);
        if ($loppupvm[$i] != "" and $loppupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
            echo "<font class='error'>Virheellinen loppupvm! $loppupvm[$i]</font><br/>";
            $tee = "alennustaulukko";
        }
        
        if (empty($alkuryhma[$i]) or empty($loppuryhma[$i])) {
            echo "<font class='error'>Alkuryhm‰ tai loppuryhm‰ puuttuu</font><br/>";
            $tee = "alennustaulukko";
        }
        if (!is_numeric($alennus[$i]) and $alennus[$i] != "") {
            echo "<font class='error'>Alennuksen t‰ytyy olla numero.</font><br/>";
            $tee = "alennustaulukko";
        }
        if (!is_numeric($minkpl[$i]) and $minkpl[$i] != "") {
            echo "<font class='error'>Minkpl t‰ytyy olla numero.</font><br/>";
            $tee = "alennustaulukko";
        }
    }
}

# P‰ivitet‰‰n ryhm‰t
if ($tee == 'lisaa' or $tee == 'uusi') {

    if($tee == 'uusi') {
        # Uusi lomakkeen muunnos.
        $alkuryhma[] = $uusi_alkuryhma;
        $loppuryhma[] = $uusi_loppuryhma;
        $alennus[] = $uusi_alennus;
        $alennuslaji[] = $uusi_alennuslaji;
        $minkpl[] = $uusi_minkpl;
        $monikerta[] = $uusi_monikerta;
        $alkupvm[] = $uusi_alkupvm;
        $loppupvm[] = $uusi_loppupvm;
    }   
    for($i = 0; $i < count($alkuryhma); $i++) {
        # Haetaan ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
        $ryhmat = hae_ryhmat($alkuryhma[$i], $loppuryhma[$i], $kukarow['yhtio']);

        foreach($ryhmat as $ryhma) {          
            if ($tyyppi == 'ytunnus') {
                $query_lisa = "ytunnus = '$ytunnus'";
            }
            else {
                $query_lisa = "asiakas = '$asiakasid'";
            }            
             
            if($poista[$i] != "") {
                
                if ($alkupvm[$i] == "") {
                    $alkupvm[$i] = "0000-00-00";
                }
                if ($loppupvm[$i] == "") {
                    $loppupvm[$i] = "0000-00-00";
                }

                $poista_query = "DELETE FROM asiakasalennus
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND $query_lisa
                AND ryhma = '$ryhma' 
                AND alennus = '{$alennus[$i]}' 
                AND alennuslaji = '{$alennuslaji[$i]}'
                AND minkpl = '{$minkpl[$i]}'
                AND monikerta = '{$monikerta[$i]}'
                AND alkupvm = '{$alkupvm[$i]}'
                AND loppupvm = '{$loppupvm[$i]}'
                ";
                $poista_result = pupe_query($poista_query);
                $poista[$i] = NULL;
            }
            else {
                $query = "INSERT INTO asiakasalennus SET
                yhtio = '{$kukarow['yhtio']}',
                ryhma = '$ryhma',
                $query_lisa,
                alennus = '{$alennus[$i]}',
                alennuslaji = '{$alennuslaji[$i]}',
                minkpl = '{$minkpl[$i]}',
                monikerta = '{$monikerta[$i]}',
                alkupvm = '{$alkupvm[$i]}',
                loppupvm = '{$loppupvm[$i]}',
                laatija = '{$kukarow['kuka']}',
                luontiaika = now(),
                muutospvm = now(),
                muuttaja = '{$kukarow['kuka']}'";

                $lisaa_result = pupe_query($query);
            }
        }
    $tee = "alennustaulukko";
    }
}

if ($tee == "alennustaulukko") {
    echo "<font class='head'>".t("Alennustaulukko")."</font><hr>";

    echo "<form name='paivita_alennukset' method='post'>";
    echo "<table>
        <tr>
            <th>Alkuryhm‰</th>
            <th>Loppuryhm‰</th>
            <th>Alennus</th>
            <th>Alennuslaji</th>
            <th>Minkpl</th>
            <th>Monikerta</th>
            <th>Alkupvm (VVVV-KK-PP)</th>
            <th>Loppupvm (VVVV-KK-PP)</th>
            <th>Poista</th>
        </tr>";

    if ($tyyppi == 'ytunnus') {
        $query_lisa = "AND asiakasalennus.ytunnus = '$ytunnus'";
    }
    else {
        $query_lisa = "AND asiakasalennus.asiakas = '$asiakasid'";
    }

    $query = "SELECT asiakasalennus.asiakas, 
                    perusalennus.ryhma,
                    asiakasalennus.alennuslaji,
                    asiakasalennus.minkpl,
                    asiakasalennus.monikerta,
                    asiakasalennus.alkupvm,
                    asiakasalennus.loppupvm,
                    ifnull(asiakasalennus.alennus, perusalennus.alennus) alennus
            FROM perusalennus 
            LEFT JOIN asiakasalennus ON (perusalennus.ryhma = asiakasalennus.ryhma
                AND perusalennus.yhtio = asiakasalennus.yhtio
                $query_lisa
                AND ((asiakasalennus.alkupvm <= current_date and if (asiakasalennus.loppupvm = '0000-00-00','9999-12-31', asiakasalennus.loppupvm) >= current_date) 
                    OR (asiakasalennus.alkupvm='0000-00-00' and asiakasalennus.loppupvm='0000-00-00'))) 
            WHERE perusalennus.yhtio='$kukarow[yhtio]' 
            ORDER BY perusalennus.ryhma";

    $result = pupe_query($query);

    $edellinen_rivi = "";
    $ensimmainen_ryhma = "";

    # Alennustaulukko
    $count = mysql_num_rows($result);
    $i = 0;
    do {
        $row = mysql_fetch_array($result);
        if ($ensimmainen_ryhma == "") {
            $ensimmainen_ryhma = $row['ryhma'];
        }

        // Rivit
        if ($edellinen_rivi != "" and ($edellinen_rivi['alennus'] != $row['alennus'] 
            or $edellinen_rivi['alennuslaji'] != $row['alennuslaji'] 
            or $edellinen_rivi['minkpl'] != $row['minkpl']
            or $edellinen_rivi['monikerta'] != $row['monikerta'])) {
            
            echo "<tr>";
            echo "<td><input type='hidden' name='alkuryhma[$i]' value=$ensimmainen_ryhma>".$ensimmainen_ryhma."</td>";
            echo "<td><input type='hidden' name='loppuryhma[$i]' value=$edellinen_rivi[ryhma]>".$edellinen_rivi['ryhma']."</td>";

            if ($alennus[$i] != "") {
                $edellinen_rivi['alennus'] = ($edellinen_rivi['alennus'] == $alennus[$i]) ? $edellinen_rivi['alennus'] : $alennus[$i];
            }
            echo "<td><input type='text' name='alennus[$i]' value=".$edellinen_rivi['alennus']."></td>";

            $edellinen_rivi['alennuslaji'] = ($edellinen_rivi['alennuslaji'] == $alennuslaji[$i]) ? $edellinen_rivi['alennuslaji'] : $alennuslaji[$i];

            $sel = array_fill_keys(array($edellinen_rivi['alennuslaji']), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
            $ulos = "<td><select name='alennuslaji[$i]'>";
      
            for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
                $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
            }
           
            $ulos .= "</select></td>\n";
            echo $ulos;
            if($minkpl[$i] != "" or $edellinen_rivi['minkpl'] == 0) {
                $edellinen_rivi['minkpl'] = ($edellinen_rivi['minkpl'] == $minkpl[$i]) ? $edellinen_rivi['minkpl'] : $minkpl[$i];
                $edellinen_rivi['minkpl'] = ($edellinen_rivi['minkpl'] == '0') ? "" : $edellinen_rivi['minkpl'];
            }
            echo "<td><input type='text' name='minkpl[$i]' value=".$edellinen_rivi['minkpl']."></td>";
            
            $edellinen_rivi['monikerta'] = ($edellinen_rivi['monikerta'] == $monikerta[$i]) ? $edellinen_rivi['monikerta'] : $monikerta[$i];
            echo "<td><select name='monikerta[$i]' value=".$edellinen_rivi['monikerta'].">";
            if(empty($edellinen_rivi['monikerta'])) {
                echo "<option value='' selected>Ei</option>";
                echo "<option value='K'>Kyll‰</option>";
            }
            else {
                echo "<option value='' >Ei</option>";
                echo "<option value='K' selected>Kyll‰</option>";
            }
            echo "</td>";
            
            # Jos p‰iv‰m‰‰r‰t tulevat lomakkeelta ja ovat v‰‰rin...
            if ($alkupvm[$i] != "" or $loppupvm[$i] != "") {
                $edellinen_rivi['alkupvm'] = ($edellinen_rivi['alkupvm'] == $alkupvm[$i]) ? $edellinen_rivi['alkupvm'] : $alkupvm[$i];
                $edellinen_rivi['loppupvm'] = ($edellinen_rivi['loppupvm'] == $loppupvm[$i]) ? $edellinen_rivi['loppupvm'] : $loppupvm[$i]; 
            }
            
            # Jos p‰iv‰m‰‰r‰ on 0000-00-00
            $edellinen_rivi['alkupvm'] = ($edellinen_rivi['alkupvm'] == '0000-00-00') ? "" : $edellinen_rivi['alkupvm'];
            $edellinen_rivi['loppupvm'] = ($edellinen_rivi['loppupvm'] == '0000-00-00') ? "" : $edellinen_rivi['loppupvm'];
            
            echo "<td><input type='text' name='alkupvm[$i]' value=".$edellinen_rivi['alkupvm']."></td>";
            echo "<td><input type='text' name='loppupvm[$i]' value=".$edellinen_rivi['loppupvm']."></td>";

            $checked = (isset($poista[$i])) ? "checked" : "";
            echo "<td><input type='checkbox' name='poista[$i]' $checked></td>";
            echo "</tr>";

            $ensimmainen_ryhma = $row['ryhma'];
            $i++;
        }
        $edellinen_rivi = $row;
    } while ($row);

    echo "<tr><td colspan=8><input type='submit' value='P‰ivit‰'></td></tr>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";

    echo "<input type='hidden' name='tee' value='lisaa'>";
    echo "</table>";
    echo "</form>";

    # Uuden ryhm‰n luominen
    echo "<br/><font class='head'>".t("Uusi")."</font><hr>";
    echo "<form name='uusi_alennus' method='post'>";
    echo "<table>
    <tr>
        <th>alkuryhm‰</th>
        <th>Loppuryhm‰</th>
        <th>Alennus</th>
        <th>Alennuslaji</th>
        <th>Minkpl</th>
        <th>Monikerta</th>
        <th>Alkupvm (VVVV-KK-PP)</th>
        <th>Loppupvm (VVVV-KK-PP)</th>
    </tr>
    <tr>
        <td><input type='text' name='uusi_alkuryhma' value=$uusi_alkuryhma></td>
        <td><input type='text' name='uusi_loppuryhma' value=$uusi_loppuryhma></td>
        <td><input type='text' name='uusi_alennus' value=$uusi_alennus></td>";

    $sel = array_fill_keys(array($uusi_alennuslaji), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
    $ulos = "<td><select name='uusi_alennuslaji'>";

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
    }
    echo $ulos;

    echo "<td><input type='text' name='uusi_minkpl' value=$uusi_minkpl></td>";

    $sel = ($uusi_monikerta != '') ? 'selected' : '';
    echo "<td><select name='uusi_monikerta'>
                <option value=''>Ei</option>
                <option value='K' $sel>Kyll‰</option>
        </td>        
        <td><input type='text' name='uusi_alkupvm' value=$uusi_alkupvm></td>
        <td><input type='text' name='uusi_loppupvm' value=$uusi_loppupvm></td>
        </tr>
        <tr><td colspan=8><input type='submit' value='Luo'></td></tr>
        </table>";
    echo "<input type='hidden' name='asiakasid' value='$asiakasid'>";
    echo "<input type='hidden' name='ytunnus' value='$ytunnus'>";
    echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";
    echo "<input type='hidden' name='tee' value='uusi'>";
    echo "</form>";
}

# Haetaan ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
function hae_ryhmat($alkuryhma, $loppuryhma, $yhtio) {
    $ryhmat_query = "SELECT ryhma FROM perusalennus 
        WHERE yhtio='$yhtio' 
        AND ryhma BETWEEN '$alkuryhma' AND '$loppuryhma'";
    
    $ryhmat_result = pupe_query($ryhmat_query);
    
    while($row = mysql_fetch_array($ryhmat_result)) {
        $ryhmat[] = $row['ryhma'];
    }
    
    return $ryhmat;
}

# Debug koodia
if (isset($GLOBALS["pupe_query_debug"]) and $GLOBALS["pupe_query_debug"] > 0) {

    echo "<font style='font-family: monospace; font-size: 9pt; white-space: nowrap;'>";

    $loppumem = memory_get_usage();
    echo "php: ".sprintf("%.04f", $aika)." sec, ".round((($loppumem - $alkumem) / 1024 / 1024), 2)." mb<br>";
    echo "sql: ".sprintf("%.04f", array_sum($aika_debug_array))." sec, ".sprintf("%.04f", array_sum($aika_debug_array) / count($aika_debug_array))." sec/query (".count($aika_debug_array)." queries)<br>";
    echo "<hr>";

    // Sortataan ketoj‰rjestykseen
    array_multisort($aika_debug_array, SORT_DESC, $quer_debug_array);

    for ($i=0; $i<count($aika_debug_array); $i++) {
        if ($aika_debug_array[$i] > $GLOBALS["pupe_query_debug"]) {
            echo sprintf("%.04f", $aika_debug_array[$i]).": ", query_dump($quer_debug_array[$i]);
        }
    }

    echo "</font>";
}
