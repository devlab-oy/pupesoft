<?php

require("inc/parametrit.inc");

echo "<font class='head'>".t("Alennusten ylläpito")."</font><hr>";
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

// Syötteen tarkistus
if(empty($_GET['tunnus']) and empty($_GET['asiakas'])) {
    echo "Tyhjä asiakas tai tunnus";
    break;
}
    
echo "<br/><font class='head'>".t("Alennustaulukko")."</font><hr>";

echo "<form name=>";
echo "<table>
    <tr>
        <th>Alkuryhmä</th>
        <th>Loppuryhmä</th>
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
        WHERE asiakasalennus.yhtio='$kukarow[yhtio]' ORDER BY asiakasalennus.ryhma 
        LIMIT 10000";

$result = pupe_query($query);

$edellinen_alennus = "";
$alkuryhma = "";

while($row = mysql_fetch_array($result)) {
    if($alkuryhma == "") {
        $alkuryhma = $row['ryhma'];
    }

    // Rivit
    if($edellinen_rivi['alennus'] != $row['alennus'] and $edellinen_rivi['alennus'] != "") {
        echo "<tr>";
        echo "<td>".$alkuryhma."</td>";
        echo "<td>".$edellinen_rivi['ryhma']."</td>";
        echo "<td><input type='text' value=".$edellinen_rivi['alennus']."></td>";
        echo "<td>".$edellinen_rivi['alennuslaji']."</td>";
        echo "<td>".$edellinen_rivi['minkpl']."</td>";
        echo "<td>".$edellinen_rivi['monikerta']."</td>";
        echo "<td>".$edellinen_rivi['alkupvm']."</td>";
        echo "<td>".$edellinen_rivi['loppupvm']."</td>";
        echo "</tr>";
        $alkuryhma = $row['ryhma'];
    }

    $edellinen_rivi = $row;
}
    // Tulostetaan vielä viimeinen rivi
    echo "<tr>";
    echo "<td>".$alkuryhma."</td>";
    echo "<td>".$edellinen_rivi['ryhma']."</td>";
    echo "<td><input type='text' value=".$edellinen_rivi['alennus']."></td>";
    echo "<td>".$edellinen_rivi['alennuslaji']."</td>";
    echo "<td>".$edellinen_rivi['minkpl']."</td>";
    echo "<td>".$edellinen_rivi['monikerta']."</td>";
    echo "<td>".$edellinen_rivi['alkupvm']."</td>";
    echo "<td>".$edellinen_rivi['loppupvm']."</td>";
    echo "</tr>";
    echo "<tr><td colspan=8><input type='submit' value='Päivitä'></td></tr>";
    echo "</table>";
    echo "</form>";

///////
echo "<br/><font class='head'>".t("Uusi")."</font><hr>";
echo "<form name=''>";
echo "<table>
    <tr>
        <th>Alkuryhmä</th>
        <th>Loppuryhmä</th>
        <th>Alennus</th>
        <th>Alennuslaji</th>
        <th>Minkpl</th>
        <th>Monikerta</th>
        <th>Alkupvm</th>
        <th>Loppupvm</th>
    </tr>
    <tr>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
        <td><input type='text' name=''></td>
    </tr>
    <tr><td colspan=8><input type='submit' value='Luo'></td></tr>
</table>";
echo "</form>";
?>
