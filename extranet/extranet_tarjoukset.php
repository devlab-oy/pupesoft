<?php

require ("../inc/parametrit.inc");
require ("validation/Validation.php");
enable_ajax();

echo "<font class='head'>" . t("Ext-tarjoukset") . "</font><hr>";
?>
<style>
    .tr_border_top {
        border-top: 1px solid;
    }
</style>
<script>
    function tarkista() {
        ok = confirm($('#hyvaksy_hylkaa_message').val());
						
        if(ok) {
            return true;
        }
        else {
            return false;
        }
    }
</script>
<?php

$request = array(
    "toim" => $toim,
    "kayttajaan_liitetty_asiakas" => $kayttajaan_liitetty_asiakas,
    "asiakkaan_tarjoukset" => $asiakkaan_tarjoukset,
    "action" => $action,
    "valittu_tarjous_tunnus" => $valittu_tarjous_tunnus,
    "hyvaksy" => $hyvaksy,
    "hylkaa" => $hylkaa,
);

$request['kayttajaan_liitetty_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();


if ($request['action'] == 'nayta_tarjous') {
    nayta_tarjous($request['valittu_tarjous_tunnus']);
}
elseif ($request['action'] == 'hyvaksy_tai_hylkaa') {

    if (isset($request['hyvaksy'])) {
        $onnistuiko_toiminto = hyvaksy_tarjous($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot);
    }
    else {
        $onnistuiko_toiminto = hylkaa_tarjous($request['valittu_tarjous_tunnus']);
    }
    if (!$onnistuiko_toiminto) {
        echo "<font class='error'>" . t("Toiminto epäonnistui") . "</font>";
        echo "<br>";
        echo "<br>";
    }

    //$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus'], $request['toim']);
    $request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset(57620, $request['toim']);
    $header_values = array(
        'nimi' => t('Nimi'),
        'hinta' => t('Tarjouksen hinta'),
        'toimaika' => t('Viimeinen voimasssaolopäivämäärä'),
    );

    echo_rows_in_table($request['asiakkaan_tarjoukset'], $header_values, array(), 'tee_tarjouksen_nimesta_linkki');
}
else {
    //$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus'], $request['toim']);
    $request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset(57620, $request['toim']);
    $header_values = array(
        'nimi' => t('Nimi'),
        'hinta' => t('Tarjouksen hinta'),
        'toimaika' => t('Viimeinen voimasssaolopäivämäärä'),
    );

    echo_rows_in_table($request['asiakkaan_tarjoukset'], $header_values, array(), 'tee_tarjouksen_nimesta_linkki');
}

function hyvaksy_tarjous($valittu_tarjous_tunnus, $syotetyt_lisatiedot) {
    global $kukarow, $yhtiorow;
    $validations = array(
        'syotetyt_lisatiedot' => 'kirjain_numero',
    );

    $validator = new FormValidator($validations);

    if ($validator->validate(array('syotetyt_lisatiedot' => $syotetyt_lisatiedot))) {

        $kukarow['kesken'] = $valittu_tarjous_tunnus;
        $laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus);

        ///* Reload ja back-nappulatsekki *///
        if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
            echo "<font class='error'> " . t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä") . "! </font>";
            exit;
        }

        //Luodaan valituista riveistä suoraan normaali ostotilaus
        require("tilauskasittely/tilauksesta_ostotilaus.inc");

        $tilauksesta_ostotilaus = tilauksesta_ostotilaus($kukarow["kesken"], 'T');
        $tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"], 'U');

        if ($tilauksesta_ostotilaus != '')
            echo "$tilauksesta_ostotilaus<br><br>";

        // Kopsataan valitut rivit uudelle myyntitilaukselle
        require("tilauskasittely/tilauksesta_myyntitilaus.inc");

        $tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '', '', '', $perusta_tilaustyyppi);
        if ($tilauksesta_myyntitilaus != '')
            echo "$tilauksesta_myyntitilaus<br><br>";

        $query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
        $result = pupe_query($query);

        //	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hyväksytyiksi
        $query = "SELECT tunnusnippu from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu]";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);

            $query = "UPDATE lasku SET alatila='T' where yhtio='$kukarow[yhtio]' and tunnusnippu = $row[tunnusnippu] and tunnus!='$kukarow[kesken]'";
            $result = pupe_query($query);
        }

        $query = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
        $result = pupe_query($query);

        $aika = date("d.m.y @ G:i:s", time());
        echo "<font class='message'>$otsikko $kukarow[kesken] " . t("valmis") . "!</font><br><br>";

        $tee = '';
        $tilausnumero = '';
        $laskurow = '';
        $kukarow['kesken'] = '';
        return true;
    }
    return false;
}

function hylkaa_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $kukarow['kesken'] = $valittu_tarjous_tunnus;
    $laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus);

    ///* Reload ja back-nappulatsekki *///
    if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
        echo "<font class='error'> " . t("Taisit painaa takaisin tai päivitä nappia. Näin ei saa tehdä") . "! </font>";
        exit;
    }

    $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    //Nollataan sarjanumerolinkit
    vapauta_sarjanumerot($toim, $kukarow["kesken"]);

    //	Päivitetään myös muut tunnusnipun jäsenet sympatian vuoksi hylätyiksi *** tämän voisi varmaan tehdä myös kaikki kerralla? ***
    $query = "SELECT tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
    $abures = pupe_query($query);

    if (mysql_num_rows($abures) > 0) {
        while ($row = mysql_fetch_assoc($abures)) {
            $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
            $result = pupe_query($query);

            $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
            $result = pupe_query($query);

            //Nollataan sarjanumerolinkit
            vapauta_sarjanumerot($toim, $row["tunnus"]);
        }
    }

    $query = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);

    $aika = date("d.m.y @ G:i:s", time());
    echo "<font class='message'>$otsikko $kukarow[kesken] " . t("valmis") . "!</font><br><br>";

    $tee = '';
    $tilausnumero = '';
    $laskurow = '';
    $kukarow['kesken'] = '';
    return true;
}

function hae_extranet_tarjoukset($asiakasid, $toim) {
    global $kukarow, $yhtiorow;

    if ($toim == 'EXTTARJOUS') {
        $where = "  AND lasku.clearing = 'EXTTARJOUS' AND lasku.tila = 'T'";
    }
    else {
        $where = "  AND lasku.clearing = 'EXTENNAKKO' AND lasku.tila = 'N'";
    }
    /* AND lasku.clearing = 'EXTTARJOUS' */
    $query = "  SELECT concat_ws('!!!', lasku.tunnus, lasku.nimi) AS nimi,
                lasku.hinta,
                lasku.toimaika,
                lasku.tunnus as tunnus
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.liitostunnus = '{$asiakasid}'
                AND lasku.alatila = ''
                {$where}";
    $result = pupe_query($query);

    $haetut_tarjoukset = array();

    while ($haettu_tarjous = mysql_fetch_assoc($result)) {
        $haetut_tarjoukset[] = $haettu_tarjous;
    }
    return $haetut_tarjoukset;
}

function hae_extranet_tarjous($tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tunnus}'";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
}

function hae_extranet_kayttajaan_liitetty_asiakas() {
    global $kukarow, $yhtiorow;

    $query = "  SELECT asiakas.*
                FROM asiakas
                JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' AND kuka.kuka = '{$kukarow['kuka']}' )
                WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
}

function tee_tarjouksen_nimesta_linkki($header, $solu, $force_to_string) {
    global $kukarow, $yhtiorow;

    if (!stristr($header, 'tunnus')) {
        if ($header == 'nimi') {
            $tunnus_nimi_array = explode('!!!', $solu);
            echo "<td>";
            echo "<a href='extranet_tarjoukset.php?action=nayta_tarjous&valittu_tarjous_tunnus={$tunnus_nimi_array[0]}'>{$tunnus_nimi_array[1]}</a>";
            echo "</td>";
        }
        else if ($header == 'hinta') {
            echo "<td align='right'>";
            echo $solu;
            echo "</td>";
        }
        else {
            echo "<td>";
            echo $solu;
            echo "</td>";
        }
    }
}

function nayta_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $tarjous = hae_tarjous($valittu_tarjous_tunnus);

    echo_tarjouksen_otsikko($tarjous);
    echo_tarjouksen_tilausrivit($tarjous);
}

function hae_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT *
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);

    $tarjous = mysql_fetch_assoc($result);
    $tarjous['tilausrivit'] = hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus);

    return $tarjous;
}

function hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT '' as nro, tunnus, perheid as perheid_tunnus, tuoteno,nimitys, tilkpl as kpl,hinta as rivihinta,alv
                FROM tilausrivi
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND otunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);

    $tilausrivit = array();

    while ($tilausrivi = mysql_fetch_assoc($result)) {
        $tilausrivit[] = $tilausrivi;
    }

    return $tilausrivit;
}

function echo_tarjouksen_otsikko($tarjous) {
    global $kukarow, $yhtiorow;

    echo "<input type='hidden' id='hyvaksy_hylkaa_message' value='" . t("Oletko varma, että haluat suorittaa valitun toimenpiteen") . "'/>";
    echo "<a href=extranet_tarjoukset.php>" . t("Palaa takaisin") . "</a>";
    echo "<br>";
    echo "<br>";
    echo "<table>";

    echo "<tr>";
    echo "<th>" . t("Nimi") . "</th>";
    echo "<th>" . t("Viimeinen voimassaolopvm") . "</th>";
    echo "<tr>";
    echo "<td>";
    echo "{$tarjous['nimi']}";
    echo "</td>";
    echo "<td>";
    echo "{$tarjous['toimaika']}";
    echo "</td>";

    echo "</table>";
    echo "<br>";
}

function echo_tarjouksen_tilausrivit($tarjous) {
    global $kukarow, $yhtiorow;

    echo "<font class='message'>" . t("Tilausrivit") . "</font>";
    echo "<br>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='action' value='hyvaksy_tai_hylkaa' />";
    echo "<input type='hidden' name='valittu_tarjous_tunnus' value='{$tarjous['tunnus']}'/ >";

    $header_values = array(
        'tuotenro' => t('Tuotenro'),
        'nimi' => t('Nimi'),
        'kpl' => t('Kpl'),
        'rivihinta' => t('Rivihinta'),
        'alv' => t('Alv'),
    );
    echo_tarjous_rows_in_table($tarjous['tilausrivit'], $header_values);
    echo "<br>";
    echo "<textarea rows='4' cols='20' maxlength='1000' name='syotetyt_lisatiedot' placeholder='" . t("Lisätietoja") . "'>";
    echo "</textarea>";
    echo "<br>";
    echo "<br>";
    echo "<input type='submit' name='hyvaksy' value='" . t("Hyväksy") . "' onclick='return tarkista();'/>";
    echo "<input type='submit' name='hylkaa' value='" . t("Hylkää") . "' onclick='return tarkista();'/>";
    echo "</form>";
}

function echo_tarjous_rows_in_table(&$rivit, $header_values = array(), $force_to_string = array()) {
    echo "<table>";
    if (count($rivit) > 0) {
        _echo_tarjous_table_headers($rivit[0], $header_values);
        _echo_tarjous_table_rows($rivit, $force_to_string);
    }
    else {
        echo "<tr><td>" . t("Ei tulostettavia rivejä") . "</td></tr>";
    }
    echo "</table>";
}

function _echo_tarjous_table_headers($rivi, $header_values) {
    echo "<tr>";
    foreach ($rivi as $header_text => $value) {
        if (!stristr($header_text, 'tunnus')) {
            if (array_key_exists($header_text, $header_values)) {
                echo "<th>{$header_values[$header_text]}</th>";
            }
            else {
                //fail safe
                echo "<th>{$header_text}</th>";
            }
        }
    }
    echo "</tr>";
}

function _echo_tarjous_table_rows(&$rivit, $force_to_string = array(), $callback = '') {
    $index = 0;
    foreach ($rivit as $rivi) {
        $class = "";
        if ($rivi['tunnus'] == $rivi['perheid_tunnus'] or $rivi['perheid_tunnus'] == 0) {
            $class = "tr_border_top";
            $index++;
        }
        echo "<tr>";
        foreach ($rivi as $header => &$solu) {
            _echo_tarjous_table_row_td($header, $solu, $force_to_string, $class, $index);
        }
        echo "</tr>";
    }
}

function _echo_tarjous_table_row_td($header, $solu, $force_to_string, $class, $index) {
    if (!stristr($header, 'tunnus')) {
        if ($header == 'nro') {
            if ($class != '') {
                echo "<td class='{$class}'>{$index}</td>";
            }
            else {
                echo "<td class='{$class}'></td>";
            }
        }
        else {
            if (is_numeric($solu) and !ctype_digit($solu) and !in_array($header, $force_to_string)) {
                $solu = number_format($solu, 2);
            }
            echo "<td class='{$class}'>{$solu}</td>";
        }
    }
}

require ("inc/footer.inc");