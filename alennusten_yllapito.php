<?php

require("inc/parametrit.inc");

echo "<font class='head'>".t("Alennusten yll‰pito")."</font><hr>";
echo "<form name=asiakas>";
echo "<table>
    <tr>
        <th>Asiakas:</th>
        <td><input type='text' name='asiakas'></td>
        <td><input type='submit' value='Hae'></td>
    </tr>
    <tr>
        <th>Ytunnus:</th>
        <td><input type='text' name='tunnus'></td>
        <td><input type='submit' value='Hae'></td>
    </tr>
</table>";
echo "</form>";

// Syˆtteen tarkistus
if(empty($_GET['tunnus']) and empty($_GET['asiakas'])) {
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
        <th>Alkupvm</th>
        <th>Loppupvm</th>
    </tr>";

$tunnus = $_GET['tunnus'];

$query = "SELECT asiakasalennus.asiakas, 
                asiakasalennus.ryhma,
                asiakasalennus.alennuslaji,
                asiakasalennus.minkpl,
                asiakasalennus.monikerta,
                asiakasalennus.alkupvm,
                asiakasalennus.loppupvm,
                ifnull(asiakasalennus.alennus, perusalennus.alennus) alennus
        FROM perusalennus 
        LEFT JOIN asiakasalennus ON (perusalennus.ryhma = asiakasalennus.ryhma
            AND perusalennus.yhtio = asiakasalennus.yhtio
            AND asiakasalennus.asiakas = $tunnus) 
        WHERE asiakasalennus.yhtio='$kukarow[yhtio]' 
        and ((alkupvm <= current_date and if (loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) 
        or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
        ORDER BY asiakasalennus.ryhma";

$result = pupe_query($query);

$edellinen_rivi = "";
$alkuryhma = "";

while($row = mysql_fetch_array($result)) {
    if($alkuryhma == "") {
        $alkuryhma = $row['ryhma'];
    }

    // Rivit
    if($edellinen_rivi != "" and ($edellinen_rivi['alennus'] != $row['alennus'] 
        or $edellinen_rivi['alennuslaji'] != $row['alennuslaji'] 
        or $edellinen_rivi['minkpl'] != $row['minkpl']
        or $edellinen_rivi['monikerta'] != $row['monikerta'])) {

        echo "<tr>";
        echo "<td>".$alkuryhma."</td>";
        echo "<td>".$edellinen_rivi['ryhma']."</td>";
        echo "<td><input type='text' name='alennus[]' value=".$edellinen_rivi['alennus']."></td>";

        $sel = array_fill_keys(array($edellinen_rivi['alennuslaji']), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
        $ulos = "<td><select name='alennuslaji[]'>";
  
        for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
            $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
        }
       
        $ulos .= "</select></td>\n";
        echo $ulos;

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
        
        echo "<td><input type='text' name='alkupvm[]' value=".$edellinen_rivi['alkupvm']."></td>";
        echo "<td><input type='text' name='loppupvm[]' value=".$edellinen_rivi['loppupvm']."></td>";
        echo "</tr>";
        
        $alkuryhma = $row['ryhma'];
    }

    $edellinen_rivi = $row;
    $i++;
}
    // Tulostetaan viel‰ viimeinen rivi
    echo "<tr>";
    echo "<td>".$alkuryhma."</td>";
    echo "<td>".$edellinen_rivi['ryhma']."</td>";
    echo "<td><input type='text' value=".$edellinen_rivi['alennus']."></td>";

    $sel = array_fill_keys(array($edellinen_rivi['alennuslaji']), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
    $ulos = "<td><select name='alennuslaji[]'>";

    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
    }
   
    $ulos .= "</select></td>\n";
    echo $ulos;

    echo "<td><input type='text' value=".$edellinen_rivi['minkpl']."></td>";
    $sel = $row['monikerta'];

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

    echo "<td><input type='text' name='kissa' value=".$edellinen_rivi['alkupvm']."></td>";
    echo "<td><input type='text' value=".$edellinen_rivi['loppupvm']."></td>";
    echo "</tr>";
    echo "<tr><td colspan=8><input type='submit' value='P‰ivit‰'></td></tr>";
    echo "<input type='hidden' name='tee' value='paivita'>";
    echo "</table>";
    echo "</form>";

//// UUSI ////
    echo "<br/><font class='head'>".t("Uusi")."</font><hr>";
    echo "<form name='uusi_alennus' method='post'>";
    echo "<table>
    <tr>
        <th>alkuryhma‰</th>
        <th>Loppuryhm‰</th>
        <th>Alennus</th>
        <th>Alennuslaji</th>
        <th>Minkpl</th>
        <th>Monikerta</th>
        <th>Alkupvm</th>
        <th>Loppupvm</th>
    </tr>
    <tr>
        <td><input type='text' name='alkuryhma'></td>
        <td><input type='text' name='loppuryhma'></td>
        <td><input type='text' name='alennus' value=0></td>";

    $sel = array_fill_keys(array($trow[$i]), " selected") + array_fill(1, $yhtiorow['myynnin_alekentat'], '');
    $ulos = "<td><select name='alennuslaji'>";
   
    for ($alepostfix = 1; $alepostfix <= $yhtiorow['myynnin_alekentat']; $alepostfix++) {
        $ulos .= "<option value = '$alepostfix' {$sel[$alepostfix]}>".t("Alennus")." $alepostfix</option>";
    }
    echo $ulos;

    echo "<td><input type='text' name='minkpl' value=0></td>
        <td><select name='text' value=".$edellinen_rivi['monikerta'].">
                <option value=''>Ei</option>
                <option value='K'>Kyll‰</option>
        </td>        
        <td><input type='text' name='alkupvm'></td>
        <td><input type='text' name='loppupvm'></td>
        </tr>
        <tr><td colspan=8><input type='submit' value='Luo'></td></tr>
        </table>";
    echo "<input type='hidden' name='tee' value='uusi'>";
    echo "</form>";


if($tee == 'paivita') {
    echo "P‰ivitet‰‰n tiedot<br/><pre>";
    echo var_dump($_POST);
}

if($tee == 'uusi') {
    # Tarkista pakolliset kent‰t, eli kaikki kent‰t?

    # Ryhm‰t perusalennus-taulusta, between $alkuryhma and $loppuryhma
    // $ryhma_query = "SELECT ryhma FROM perusalennus 
    //             WHERE yhtio='$kukarow[yhtio]' 
    //             AND asiakas='$tunnus' 
    //             AND ryhma BETWEEN $alkuryhma AND $loppuryhma";
    // $ryhmat = pupe_query($ryhma_query);

    # Lis‰t‰‰n asiakasalennus-tauluun uudet rivit, loopataan ryhm‰v‰li l‰pi.
    // $query =  "INSERT INTO asiakasalennus SET 
    //         yhtio = {$kukarow['yhtio']},
    //         ryhma = {$ryhmat[$i]},
    //         alennus = ?,
    //         alennuslaji = ?,
    //         minkpl = ?,
    //         monikerta = ?,
    //         alkupvm = ?,
    //         loppupvm = ?
    //         ";

    echo "P‰ivitet‰‰n tiedot<br/><pre>";
    echo var_dump($_POST);
}
///// DEBUG /////
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
