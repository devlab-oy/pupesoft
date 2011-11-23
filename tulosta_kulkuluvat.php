<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

if($_POST["tee"] == "tulosta") {
	$nayta_pdf=1;
}

require("inc/parametrit.inc");

if($tee == "tulosta") {
	// jos php-gd on installoitu niin loidataab barcode library
	if (in_array("gd", get_loaded_extensions())) {
		if (@include_once("viivakoodi/Barcode.php")) {
		}
		else {
			include_once("Barcode.php");
		}
	}

	require_once('pdflib/pupepdf.class.php');

	if (!function_exists('alku')) {
		function alku ($pdf) {
			global $yhtiorow, $kukarow, $fonts, $h;

			if(!is_object($pdf)) {

				//	Aloitellaan PDF dokkari
				$pdf = new pdf;

				$pdf->set_default('margin-top', 	mm_pt(5));
				$pdf->set_default('margin-bottom', 	mm_pt(5));
				$pdf->set_default('margin-left', 	mm_pt(5));
				$pdf->set_default('margin-right',	mm_pt(5));

				$fonts["norm"]['height']	= 16;
				$fonts["norm"]['font']		= "Helvetica";


				//	Tehdään sivu jotta saadaan currentPage kuntoon
				$pdf->addPage("a4");

				$h=282;
			}

			return $pdf;
		}
	}

	$pdf = alku($pdf);
	$i=0;
	foreach($ketka as $kuka => $x) {
		if($x!="") {
			$query = "	SELECT nimi, left(md5(concat(tunnus,kuka)), 16) avain
						FROM kuka
						WHERE yhtio='$kukarow[yhtio]' and tunnus='$kuka'";
			$result = mysql_query($query) or pupe_error($query);
			if(mysql_num_rows($result) > 0) {

				while($row = mysql_fetch_array($result)) {

					if($i==0) {
						$w = 15;
						$wk = 10;
					}
					else {
						$w = 115;
						$wk = 110;
						$i=-1;
					}

					$pdf->write($h, 1, $w, ($w+70), $row["nimi"], "norm", "C", "");
					$h -= 20;

					if (class_exists("Image_Barcode")) {

						//viivakoodi
						$nimi = "/tmp/".md5(uniqid(rand(),true)).".jpg";
						imagejpeg(Image_Barcode::draw($row["avain"], 'code128', 'jpg', false, 1, 25), $nimi, 100);

						$pdf->placeImage($h, $wk, $nimi);

						system("rm -f $nimi");
					}

					if($i == 0) {
						$h += 20;
					}
					else {
						$h -= 10;
					}

					$i++;
				}
			}
		}
	}

	$filenimi = "testi.pdf";
	$fh = fopen($filenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF create error $filenimi");
	fclose($fh);

	echo file_get_contents($filenimi);
}

if($tee == "") {

	$query = "	SELECT nimi, tunnus
				FROM kuka
				WHERE yhtio = '$kukarow[yhtio]' and nimi != ''";
	$result = mysql_query($query) or pupe_error($query);

	echo "	<font class='head'>".t("Tulosta kulunvalvontalaput")."</font><hr><br><br>
			<form method='post' name='tulosta'>
			<input type='hidden' name='tee' value='tulosta'>
			<table>
				<tr>
					<th>".t("Valitse")."</th><th>".t("Kuka")."</th>
				</tr>";
	while($row = mysql_fetch_array($result)) {
		echo "	<tr>
					<td><input type='checkbox' name='ketka[{$row["tunnus"]}]'></td><td>$row[nimi]</td>
				</tr>";
	}

	echo "	<tr>
				<td colspan='2' class='back'><input type='submit' value='".t("Tulosta")."'></td>
			</tr>";
	echo "	</table>

			</form>";
}

require("inc/footer.inc");

?>