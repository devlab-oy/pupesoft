<?php

require("inc/parametrit.inc");

echo "<font class='head'>".t("Alennusten yll‰pito")."</font><hr>";

# Kenttien tarkistus
if ($tee == 'lisaa') {
    foreach($alkuryhma as $i => $ryhma) {
        list($yyyy, $mm, $dd) = explode('-', $alkupvm[$i]);
        if ($alkupvm[$i] != "" and $alkupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
            echo "<font class='error'>Virheellinen p‰iv‰m‰‰r‰! $alkupvm[$i]</font><br/>";
            $tee = "";
        }
        list($yyyy, $mm, $dd) = explode('-', $loppupvm[$i]);
        if ($loppupvm[$i] != "" and $loppupvm[$i] != "0000-00-00" and !checkdate($mm, $dd, $yyyy)) {
            echo "<font class='error'>Virheellinen p‰iv‰m‰‰r‰! $loppupvm[$i]</font><br/>";
            $tee = "";
        }
        if(empty($alkuryhma[$i]) or empty($loppuryhma[$i])) {
            echo "<font class='error'>Alkuryhm‰ tai loppuryhm‰ puuttuu</font><br/>";
            $tee = "";   
        }
    }
}

# P‰ivitet‰‰n ryhm‰t
if ($tee == 'lisaa') {
    for($i = 0; $i < count($alkuryhma); $i++) {
        # Haetaan ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
        $ryhmat = hae_ryhmat($alkuryhma[$i], $loppuryhma[$i], $kukarow['yhtio']);

        foreach($ryhmat as $ryhma) {          
            
            $query = "INSERT INTO asiakasalennus SET
                yhtio = '{$kukarow['yhtio']}',
                ryhma = '$ryhma',
                asiakas = '$tunnus',
                ytunnus = '',
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
}

echo "<form name=asiakas>";
echo "<table>
    <tr>
        <th>Asiakas:</th>
        <td><input type='text' name='tunnus'></td>
        <td><input type='submit' value='Hae'></td>
    </tr>
    <tr>
        <th>Ytunnus:</th>
        <td><input type='text' name='ytunnus'></td>
        <td><input type='submit' value='Hae'></td>
    </tr>
</table>";
echo "</form>";

// Syˆtteen tarkistus
if(empty($_GET['tunnus']) and empty($_GET['ytunnus'])) {
    echo "Tyhj‰ asiakas tai tunnus";
    break;
}

echo "<br/><font class='head'>".t("Alennustaulukko")."</font><hr>";

echo "<form method='post' name='asiakasalennus'>";
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
    </tr>";

$tunnus = $_GET['tunnus'];

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
            AND asiakasalennus.asiakas = $tunnus
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
        
        #$loppupvm[$i] = ($loppupvm[$i] == $edellinen_rivi['loppupvm']) ? $edellinen_rivi['loppupvm'] : $loppupvm[$i];

        echo "<tr>";
        echo $loppupvm[$i]."<br/>";
        echo "<td><input type='hidden' name='alkuryhma[]' value=$ensimmainen_ryhma>".$ensimmainen_ryhma."</td>";
        echo "<td><input type='hidden' name='loppuryhma[]' value=$edellinen_rivi[ryhma]>".$edellinen_rivi['ryhma']."</td>";
        echo "<td><input type='text' name='alennus[]' value=".$edellinen_rivi['alennus']."></td>";

        $sel = array_fill_keys(array($edellinen_rivi['alennuslaji']), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
        $ulos = "<td><select name='alennuslaji[]'>";
  
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
        }
       
        $ulos .= "</select></td>\n";
        echo $ulos;

        $edellinen_rivi['minkpl'] = ($edellinen_rivi['minkpl'] == '0') ? "" : $edellinen_rivi['minkpl'];
        echo "<td><input type='text' name='minkpl[]' value=".$edellinen_rivi['minkpl']."></td>";
        
        echo "<td><select name='monikerta[]' value=".$edellinen_rivi['monikerta'].">";
        if(empty($edellinen_rivi['monikerta'])) {
            echo "<option value='' selected>Ei</option>";
            echo "<option value='K'>Kyll‰</option>";
        }
        else {
            echo "<option value='' >Ei</option>";
            echo "<option value='K' selected>Kyll‰</option>";
        }
        echo "</td>";
        
        $edellinen_rivi['alkupvm'] = ($edellinen_rivi['alkupvm'] == '0000-00-00') ? "" : $edellinen_rivi['alkupvm'];
        $edellinen_rivi['loppupvm'] = ($edellinen_rivi['loppupvm'] == '0000-00-00') ? "" : $edellinen_rivi['loppupvm'];
        echo "<td><input type='text' name='alkupvm[]' value=".$edellinen_rivi['alkupvm']."></td>";
        echo "<td><input type='text' name='loppupvm[]' value=".$loppupvm[$i]."></td>";
        echo "</tr>";

        $ensimmainen_ryhma = $row['ryhma'];
        $i++;
    }
    $edellinen_rivi = $row;
} while ($row);

echo "<tr><td colspan=8><input type='submit' value='P‰ivit‰'></td></tr>";
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
    <td><input type='text' name='alkuryhma[]' value=$alkuryhma[0]></td>
    <td><input type='text' name='loppuryhma[]' value=$loppuryhma[0]></td>
    <td><input type='text' name='alennus[]' value=$alennus[0]></td>";

$sel = array_fill_keys(array($alennuslaji[0]), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
$ulos = "<td><select name='alennuslaji[]'>";

for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
    $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
}
echo $ulos;

echo "<td><input type='text' name='minkpl' value=$minkpl[0]></td>";

$sel = ($monikerta[0] != '') ? 'selected' : '';
echo "<td><select name='monikerta[]'>
            <option value=''>Ei</option>
            <option value='K' $sel>Kyll‰</option>
    </td>        
    <td><input type='text' name='alkupvm[]' value=$alkupvm[0]></td>
    <td><input type='text' name='loppupvm[]' value=$loppupvm[0]></td>
    </tr>
    <tr><td colspan=8><input type='submit' value='Luo'></td></tr>
    </table>";
echo "<input type='hidden' name='tee' value='lisaa'>";
echo "</form>";

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
    echo "<br><br>";
}
