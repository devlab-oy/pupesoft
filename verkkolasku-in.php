#!/usr/bin/php
<?php

	if ($argc == 0) die ("Tätä scriptiä voi ajaa vain komentoriviltä!");

	// pitää siirtyä www roottiin
	chdir("/var/www/html/pupesoft");

	// määritellään polut
    $laskut     = "/home/verkkolaskut";
    $oklaskut   = "/home/verkkolaskut/ok";
    $origlaskut = "/home/verkkolaskut/orig";
    $errlaskut  = "/home/verkkolaskut/error";

	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	require ("inc/connect.inc"); // otetaan tietokantayhteys
    require ("inc/verkkolasku-in.inc"); // täällä on itse koodi
    require ("inc/verkkolasku-in-erittele-laskut.inc"); // täällä pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

	// Käsitellään ensin kaikki Finvoicet
	if ($handle = opendir($laskut)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($laskut."/".$file)) {

				$nimi = $laskut."/".$file;
				$luotiinlaskuja = erittele_laskut($nimi);

				// Jos tiedostosta luotiin laskuja siirretään se tieltä pois
				if ($luotiinlaskuja > 0) {
					system("mv ".$laskut."/".$file." ".$origlaskut."/".$file);
				}
			}
		}
	}

	if ($handle = opendir($laskut)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($laskut."/".$file)) {

				// $yhtiorow ja $xmlstr
				unset($yhtiorow);
				unset($xmlstr);

				$nimi = $laskut."/".$file;
				$laskuvirhe = verkkolasku_in($nimi);

			    if ($laskuvirhe == "") {
			    	system("mv -f $nimi $oklaskut/$file");
			    }
			    else {
			    	system("mv -f $nimi $errlaskut/$file");
				}
			}
		}
	}

?>
