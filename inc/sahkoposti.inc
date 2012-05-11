<?php

	/* Tällä funktiolla lähetetään sähköposti, johon voidaan liittää yksi tai useampi liitetiedosto.

	Parametrinä seuraavanlainen array:

	$parametri = array( "to" 		=> "devlab@devlab.fi",
						"cc" 		=> "",
						"subject"	=> "Mail from Pupesoft!",
						"ctype"		=> "html",
						"body"		=> "<h1>Hello from Pupesoft!</h1>",

						"attachements" => array(0 	=> array(
													"filename"		=> "/foo/baz/file1.txt",
													"newfilename"	=> "report.txt",
													"ctype"			=> "text"),

												1 	=> array(
													"filename"		=> "/bar/foo/file2",
													"newfilename"	=> "pretty_report.xls",
													"ctype"			=> "excel"),
						)
	);

	*/

	if (!function_exists("pupesoft_sahkoposti")) {

		function pupesoft_sahkoposti($parametrit) {

			global $yhtiorow, $kukarow;

			// Tarvitaan seuraavat parametrit
			$email_to		= isset($parametrit["to"]) ? $parametrit["to"] : "";
			$email_cc		= isset($parametrit["cc"]) ? $parametrit["cc"] : "";
			$email_subject	= isset($parametrit["subject"]) ? $parametrit["subject"] : "";
			$email_ctype 	= isset($parametrit["ctype"]) ? strtoupper($parametrit["ctype"]) : "";
			$email_body 	= isset($parametrit["body"]) ? $parametrit["body"] : "";

			// Liitetiedostoja tulee parametreissä arrayna
			$liitteet		= (isset($parametrit["attachements"]) and is_array($parametrit["attachements"])) ? $parametrit["attachements"] : "";

			// Kaikki pitää tulla kunnossa (paitsi body ja cc)
			if ($email_to == "" or $email_subject == "" or $email_ctype == "" or $liitteet == "") {
				return false;
			}

			$content = "";
			$bound = uniqid(time()."_") ;
			$header = "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <{$yhtiorow["postittaja_email"]}>\n";

			if ($email_cc != "") {
				$header .= "Cc: $email_cc\n";
			}

			$header .= "MIME-Version: 1.0\n" ;
			$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

			if ($email_body != "") {
				$content .= "--$bound\n";
				if ($email_ctype == "HTML") {
					$content .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
				}
				else {
					$content .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
				}
				$content .= "Content-Transfer-Encoding: quoted-printable\n\n";
				$content .= "$email_body\n\n";
			}

			$content .= "--$bound";

			foreach ($liitteet as $liite) {

				// Tarkistetaan $liite arrayn oikeellisuus
				$attachement_filename		= isset($liite["filename"]) ? $liite["filename"] : "";
				$attachement_newfilename	= isset($liite["newfilename"]) ? $liite["newfilename"] : "";
				$attachement_ctype			= isset($liite["ctype"]) ? strtoupper($liite["ctype"]) : "";

				// Jos tiedostoa ei löydy tai ctype on tyjää, hypätään filen yli.
				if (!file_exists($attachement_filename) or !is_readable($attachement_filename) or $attachement_ctype == "") {
					continue;
				}

				// Otetaan tiedoston sisältö muuttujaan
				$attachement_content = chunk_split(base64_encode(file_get_contents($attachement_filename)));

				// Tiedostosta vain nimi
				$attachement_name = basename($attachement_filename);

				// Jos on poikkeava nimi, otetaan se
				if ($attachement_newfilename != "") {
					$attachement_name = basename($attachement_newfilename);
				}
				// Lisätään extensioni filenameen
				$attachement_name .= ".$attachement_ctype";

				// Boundin jälkeen aina rivinvaihto
				$content .= "\n";

				if ($attachement_ctype == "TEXT") {
					$content .= "Content-Type: text/plain; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "PS") {
					$content .= "Content-Type: application/ps; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "JPG" or $attachement_ctype == "JPEG") {
					$content .= "Content-Type: image/jpeg; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "PNG") {
					$content .= "Content-Type: image/png; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "GIF") {
					$content .= "Content-Type: image/gif; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "TIF" or $attachement_ctype == "TIFF") {
					$content .= "Content-Type: image/tiff; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "BMP") {
					$content .= "Content-Type: image/bmp; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "EXCEL") {
					$content .= "Content-Type: application/excel; name=\"$attachement_name\"\n";
				}
				elseif ($attachement_ctype == "PDF") {
					$content .= "Content-Type: application/pdf; name=\"$attachement_name\"\n";
				}
				else {
					// Defaulttina myös pdf ?
					$content .= "Content-Type: application/pdf; name=\"$attachement_name\"\n";
				}

				$content .= "Content-Transfer-Encoding: base64\n";
				$content .= "Content-Disposition: attachment; filename=\"$attachement_name\"\n\n";
				$content .= $attachement_content;
				$content .= "\n";
				$content .= "--$bound";
			}

			// Vikassa boundissa pitää olla -- lopussa.
			$content .= "--\n";

			$boob = mail($email_to, mb_encode_mimeheader($email_subject, "ISO-8859-1", "Q"), $content, $header, "-f {$yhtiorow["postittaja_email"]}");

			if ($boob === FALSE) echo t("Sähköpostin lähetys epäonnistui").": $email_to<br>";

			return $boob;
		}
	}
