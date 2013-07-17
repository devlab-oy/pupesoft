<?php

if (!isset($sel))
    $sel = array();
if (!isset($select_kieli))
    $select_kieli = 'fi';
if (!isset($select_laji))
    $select_laji = array();
if (!isset($submitnappi))
    $submitnappi = '';

echo "<font class='head'>", t("Luo ennakkotuotteiden hinnasto", $kieli), "</font><hr>";

echo "<form method='post'>";

$monivalintalaatikot = array("OSASTO", "TRY", "TUOTEMERKKI", "MALLI", "MALLI/MALLITARK");
$monivalintalaatikot_normaali = array();

require ("../tilauskasittely/monivalintalaatikot.inc");

echo "<br />";

$sel[$select_kieli] = ' selected';

echo "<table>";

echo "<tr><th>", t("Valitse kieli", $kieli), ":</th>";
echo "<td><select name='select_kieli'>";
echo "<option value='fi'{$sel['fi']}>FI</option>";
echo "<option value='se'{$sel['se']}>SE</option>";
echo "</select></td></tr>";

echo "<tr><th>", t("Laji", $kieli), ":</th>";
echo "<td><select name='select_laji[]' multiple size='7'>";

$query = "	SELECT DISTINCT laji
					FROM tuotteen_avainsanat
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND (laji LIKE 'parametri_ennakko_%' OR laji LIKE 'parametri_kuvasto_%')
					AND selite = 'x'";
$laji_result = mysql_query($query) or pupe_error($query);

while ($laji_row = mysql_fetch_assoc($laji_result)) {
    if (in_array($laji_row['laji'], $select_laji))
        $sel[$laji_row['laji']] = ' selected';

    echo "<option value='{$laji_row['laji']}'{$sel[$laji_row['laji']]}>", str_replace(array('parametri_ennakko_', 'parametri_kuvasto_'), '', $laji_row['laji']), "</option>";
}

echo "</select>";

echo "<tr><th>", t("Luo tiedosto", $kieli), ":</th><td><input type='submit' name='submitnappi' value='", t("Luo tiedosto", $kieli), "' /></td></tr>";

echo "</table>";
echo "</form>";

if (trim($submitnappi) != '' and count($select_laji) > 0) {

    if (!@include('Spreadsheet/Excel/Writer.php')) {
        echo "<br /><font class='error'>", t("VIRHE: Pupe-asennuksesi ei tue Excel-kirjoitusta."), "</font><br />";
        require ("../inc/footer.inc");
        exit;
    }

    $tuote_lisa = "";
    $tuotteen_avainsanat_lisa = "";

    if (count($mul_osasto) > 0) {
        $tuote_lisa = " and tuote.osasto in ('" . implode("','", $mul_osasto) . "') ";
    }

    if (count($mul_try) > 0) {
        $tuote_lisa .= " and tuote.try in ('" . implode("','", $mul_try) . "') ";
    }

    if (count($mul_tme) > 0) {
        $tuote_lisa .= " and tuote.tuotemerkki in ('" . implode("','", $mul_tme) . "') ";
    }

    if (count($mul_malli) > 0) {
        $tuote_lisa .= " and tuote.malli in ('" . implode("','", $mul_malli) . "') ";
    }

    if (count($mul_mallitarkenne) > 0) {
        $tuote_lisa .= " and tuote.mallitarkenne in ('" . implode("','", $mul_mallitarkenne) . "') ";
    }

    if (count($select_laji) > 0) {
        $tuotteen_avainsanat_lisa = " and tuotteen_avainsanat.laji in ('" . implode("','", $select_laji) . "') and tuotteen_avainsanat.kieli = '{$select_kieli}' ";
    }

    $query = "	SELECT t2.tuoteno, t2.selite AS ennakko_pros_a
						FROM tuote
						JOIN tuotteen_avainsanat ON (tuotteen_avainsanat.yhtio = tuote.yhtio and tuotteen_avainsanat.tuoteno = tuote.tuoteno and tuotteen_avainsanat.selite = 'x' $tuotteen_avainsanat_lisa)
						JOIN tuotteen_avainsanat AS t2 ON (t2.yhtio = tuotteen_avainsanat.yhtio AND t2.laji = 'parametri_ennakkoale_a' AND t2.selite != '' AND t2.kieli = '{$select_kieli}' and t2.tuoteno = tuote.tuoteno)
						WHERE tuote.yhtio = '{$kukarow['yhtio']}'
						$tuote_lisa
						GROUP BY 1";
    $result = mysql_query($query) or pupe_error($query);

    flush();

    mysql_data_seek($result, 0);
    $row = mysql_fetch_assoc($result);

    list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    $excelnimi = md5(uniqid(mt_rand(), true)) . ".xls";

    $workbook = new Spreadsheet_Excel_Writer('/tmp/' . $excelnimi);
    $workbook->setVersion(8);
    $worksheet = & $workbook->addWorksheet('Sheet 1');

    $format_bold = & $workbook->addFormat();
    $format_bold->setBold();

    $excelrivi = 0;
    $excelsarake = 0;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Tuotenumero")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Tuote")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Kpl")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Osh")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Ennakkohinta")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Alennusprosentti")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Ennakkohinta")), $format_bold);
    $excelsarake++;

    $worksheet->writeString($excelrivi, $excelsarake, ucfirst(t("Alennusprosentti")), $format_bold);


    $excelsarake = 0;
    $excelrivi++;

    mysql_data_seek($result, 0);

    while ($row = mysql_fetch_assoc($result)) {

        $query = "	SELECT selite AS ennakko_pros_b
							FROM tuotteen_avainsanat
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND laji = 'parametri_ennakkoale_b'
							AND selite != ''
							AND kieli = '{$select_kieli}'
							AND tuoteno = '{$row['tuoteno']}'";
        $ennakko_pros_b_res = mysql_query($query) or pupe_error($query);
        $ennakko_pros_b_row = mysql_fetch_assoc($ennakko_pros_b_res);

        if ($select_kieli == 'se') {
            $query = "	SELECT ifnull(selite, '') AS nimitys
								FROM tuotteen_avainsanat
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND laji = 'nimitys'
								AND selite != ''
								AND kieli = '{$select_kieli}'
								AND tuoteno = '{$row['tuoteno']}'";
            $nimitys_res = mysql_query($query) or pupe_error($query);
            $nimitys_row = mysql_fetch_assoc($nimitys_res);
        }

        $select_lisa = '';

        $alv_lisa = $yhtiorow['alv_kasittely'] != '' ? " (1 + (tuote.alv / 100)) * " : '';

        if (trim($ennakko_pros_b_row['ennakko_pros_b']) == '') {
            $select_lisa = ", '' AS ennakko_pros_b ";
        }
        else {
            $select_lisa = ", ($alv_lisa tuote.myyntihinta) * (1 - (" . str_replace(",", ".", $ennakko_pros_b_row['ennakko_pros_b']) . " / 100)) AS ennakko_pros_b ";
        }

        $query = "	SELECT tuote.*,
							($alv_lisa tuote.myyntihinta) AS osh,
							($alv_lisa tuote.myyntihinta) * (1 - (" . str_replace(",", ".", $row['ennakko_pros_a']) . " / 100)) AS ennakko_pros_a
							$select_lisa
							FROM tuote
							WHERE tuote.yhtio = '{$kukarow['yhtio']}'
							AND tuote.tuoteno = '{$row['tuoteno']}'";
        $tuote_result = mysql_query($query) or pupe_error($query);
        $tuote_row = mysql_fetch_assoc($tuote_result);

        $worksheet->writeString($excelrivi, $excelsarake, $row['tuoteno']);
        $excelsarake++;

        if ($select_kieli == 'se' and trim($nimitys_row['nimitys']) != '') {
            $worksheet->writeString($excelrivi, $excelsarake, $nimitys_row['nimitys']);
        }
        else {
            $worksheet->writeString($excelrivi, $excelsarake, $tuote_row['nimitys']);
        }
        $excelsarake++;

        // kpl kenttä ohitetaan
        $excelsarake++;

        if ($select_kieli == 'se') {
            $query = "	SELECT kurssi
								FROM valuu
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND nimi = 'SEK'";
            $vienti_kurssi_res = mysql_query($query) or pupe_error($query);
            $vienti_kurssi_row = mysql_fetch_assoc($vienti_kurssi_res);

            $wquery = "SELECT selite from avainsana where yhtio = '$kukarow[yhtio]' and laji = 'alvulk' and selitetark != '' and selitetark_2 = 'SE'";
            $wtres = mysql_query($wquery) or pupe_error($wquery);
            $wtrow = mysql_fetch_assoc($wtres);

            $laskurow["valkoodi"] = 'SEK';
            $laskurow['maa'] = 'SE';
            $laskurow['vienti_kurssi'] = $vienti_kurssi_row['kurssi'];
            $laskurow['vienti'] = '';
            $laskurow['alv'] = $wtrow['selite'];

            $hinta = tuotteen_myyntihinta($laskurow, $tuote_row, 1, 'ei näytetä nettohintaa');

            $query = "	SELECT alv
								FROM tuotteen_alv
								WHERE yhtio = '{$kukarow['yhtio']}'
								AND maa = '{$laskurow['maa']}'
								AND tuoteno = '{$tuote_row['tuoteno']}'
								LIMIT 1";
            $alhire = mysql_query($query) or pupe_error($query);

            if (mysql_num_rows($alhire) == 1) {
                $alv_row = mysql_fetch_assoc($alhire);
                $alv = $alv_row['alv'];
            }
            else {
                $alv = $wtrow['selite'];
            }

            // otetaan alv pois ruotsin hinnoista
            if ($yhtiorow['alv_kasittely'] == '') {

                $tuote_row['ennakko_pros_a'] = ($hinta / (1 + ($alv / 100))) * (1 - ($row['ennakko_pros_a'] / 100));

                if (trim($tuote_row['ennakko_pros_b']) != '' and trim($tuote_row['ennakko_pros_b']) > 0) {
                    $tuote_row['ennakko_pros_b'] = ($hinta / (1 + ($alv / 100))) * (1 - ($ennakko_pros_b_row['ennakko_pros_b'] / 100));
                }
            }
            else {
                $tuote_row['ennakko_pros_a'] = $hinta * (1 - ($row['ennakko_pros_a'] / 100));

                if (trim($ennakko_pros_b_row['ennakko_pros_b']) != '' and trim($ennakko_pros_b_row['ennakko_pros_b']) > 0) {
                    $tuote_row['ennakko_pros_b'] = $hinta * (1 - ($ennakko_pros_b_row['ennakko_pros_b'] / 100));
                }
            }
        }
        else {
            $hinta = $tuote_row['osh'];
        }

        $worksheet->write($excelrivi, $excelsarake, number_format(round($hinta, 1), 2, ',', ' '));
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, number_format(round($tuote_row['ennakko_pros_a'], 1), 2, ',', ' '));
        $excelsarake++;

        $worksheet->write($excelrivi, $excelsarake, $row['ennakko_pros_a'] . '%');
        $excelsarake++;

        if (trim($tuote_row['ennakko_pros_b']) != '' and trim($tuote_row['ennakko_pros_b']) > 0) {
            $worksheet->write($excelrivi, $excelsarake, number_format(round($tuote_row['ennakko_pros_b'], 1), 2, ',', ' '));
            $excelsarake++;

            $worksheet->write($excelrivi, $excelsarake, $ennakko_pros_b_row['ennakko_pros_b'] . '%');
        }

        $excelsarake = 0;
        $excelrivi++;
    }

    flush();

    $workbook->close();

    echo "<br /><table>";
    echo "<tr><th>", t("Tallenna raportti (xls)"), ":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto' />";
    echo "<input type='hidden' name='kaunisnimi' value='parametri_kuvasto" . date("dmYHis") . ".xls' />";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi' />";
    echo "<td class='back'><input type='submit' value='", t("Tallenna"), "'></td></tr></form>";
    echo "</table><br />";
}