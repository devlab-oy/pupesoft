<?php

class Unifaun {

	private $hostname;
	private $username;
	private $password;
	private $ftppath;
	private $ftpport;
	private $ftpfail;
	private $ftpsucc;
	private $yhtiorow;
	private $kukarow;
	private $postirow;
	private $toitarow;
	private $rakir_row;
	private $asiakasrow;
	private $yhteensa;
	private $viite;
	private $mehto;
	private $xml;
	private $kirjoitin;

	public function __construct($hostname, $username, $password, $ftppath, $ftpport, $ftpfail, $ftpsucc) {
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->ftppath 	= $ftppath;
		$this->ftpport 	= $ftpport;
		$this->ftpfail 	= $ftpfail;
		$this->ftpsucc 	= $ftpsucc;
	}

	public function setYhtioRow($yhtiorow) {
		$this->yhtiorow = $yhtiorow;
	}

	public function setKukaRow($kukarow) {
		$this->kukarow = $kukarow;
	}

	public function setToimitustapaRow($toitarow) {
		$this->toitarow = $toitarow;
	}

	public function setRahtikirjaRow($rakir_row) {
		$this->rakir_row = $rakir_row;
	}

	public function setYhteensa($yhteensa) {
		$this->yhteensa = $yhteensa;
	}

	public function setViite($viite) {
		$this->viite = $viite;
	}

	public function setMehto($mehto) {
		$this->mehto = $mehto;
	}

	private function setAsiakasRow() {
		//	Haetaan asiakastiedot
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					and tunnus  = '{$this->postirow['liitostunnus']}'";
		$asres = mysql_query($query);
		$this->asiakasrow = mysql_fetch_assoc($asres);
	}

	public function setPostiRow($postirow) {
		$this->postirow = $postirow;

		// haetaan varaston osoitetiedot, käytetään niitä lähetystietoina
		$query = "	SELECT nimi, nimitark, osoite, postino, postitp, maa
					FROM varastopaikat
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND tunnus  = '{$this->postirow['varasto']}'
					AND nimi != ''
					AND osoite != ''";
		$tempr = mysql_query($query);
		$postirow_varasto = mysql_fetch_assoc($tempr);

		// jos varastolle on annettu joku osoite, käytetään sitä
		if ($postirow_varasto["nimi"] != "") {
			$this->postirow["yhtio_nimi"]     = $postirow_varasto["nimi"];
			$this->postirow['yhtio_nimitark'] = $postirow_varasto["nimitark"];
			$this->postirow["yhtio_osoite"]   = $postirow_varasto["osoite"];
			$this->postirow["yhtio_postino"]  = $postirow_varasto["postino"];
			$this->postirow["yhtio_postitp"]  = $postirow_varasto["postitp"];
			$this->postirow["yhtio_maa"]      = $postirow_varasto["maa"];
		}

		$this->setAsiakasRow();
	}

	public function setKirjoitin($kirjoitin) {
		$this->kirjoitin = $kirjoitin;
	}

	public function ftpSend() {

		$filenimi = "/tmp/unifaun-".md5(uniqid(rand(),true)).".txt";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
			echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus epäonnistui")."!</font><br>";
		}

		if ($this->hostname != "" and $this->username != "" and $this->password != "" and $this->ftppath != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost  = $this->hostname;
			$ftpuser  = $this->username;
			$ftppass  = $this->password;
			$ftppath  = $this->ftppath;
			$ftpport  = $this->ftpport;
			$ftpfail  = $this->ftpfail;
			$ftpsucc  = $this->ftpsucc;
			$yhtiorow = $this->yhtiorow;

			$ftpfile = realpath($filenimi);

			require ("inc/ftp-send.inc");
		}
	}

	public function _saveForDebug($postfix = "") {
		#$filenimi = "/Users/sami/Sites/pupesoft/dataout/unifaun-{$postfix}-{$this->toitarow['selite']}-".md5(uniqid(rand(),true)).".txt";
		$filenimi = "/tmp/unifaun-{$postfix}-{$this->toitarow['selite']}-".md5(uniqid(rand(),true)).".txt";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
			echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus epäonnistui")."!</font><br>";
		}
	}

	public function _closeWithPrinter($mergeid, $printer) {
		$xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><printserver></printserver>';

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement($xmlstr);

		$control = $xml->addChild('control');

		$uni_ready = $control->addChild('ready');

		$uni_ready_val = $uni_ready->addChild('val', utf8_encode($mergeid));
		$uni_ready_val->addAttribute('n', 'mergeid');

		$uni_close = $control->addChild('close');

		$uni_close_val = $uni_close->addChild('val', $printer);
		$uni_close_val->addAttribute('n', 'printer');

		$uni_close_val = $uni_close->addChild('val', utf8_encode($mergeid));
		$uni_close_val->addAttribute('n', 'mergeid');

		$this->xml = $xml;
	}

	public function _discardParcel($mergeid, $parcelno) {

		$xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><printserver></printserver>';

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement($xmlstr);

		$control = $xml->addChild('control');

		$uni_discard = $control->addChild('discard');
		$uni_discard->addAttribute('type', 'parcel');

		if ($mergeid != "") {
			$uni_discard_val = $uni_discard->addChild('val', utf8_encode($mergeid));
			$uni_discard_val->addAttribute('n', 'mergeid');
		}

		$uni_discard_val = $uni_discard->addChild('val', $parcelno);
		$uni_discard_val->addAttribute('n', 'parcelno');

		$this->xml = $xml;

	}

	public function _getXML() {

		$xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><printserver></printserver>';

		$query = "	SELECT *
					FROM rahdinkuljettajat
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND koodi = '{$this->toitarow['rahdinkuljettaja']}'";
		$rahdinkuljettaja_res = pupe_query($query);
		$rahdinkuljettaja_row = mysql_fetch_assoc($rahdinkuljettaja_res);

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement($xmlstr);

			// Metatiedot
			$uni_meta = $xml->addChild('meta');

			// $uni_meta_val = $uni_meta->addChild('val', '|LASER1|'); # Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			$uni_meta_val = $uni_meta->addChild('val', "{$this->kirjoitin}"); # Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			$uni_meta_val->addAttribute('n', 'printer');

			#$uni_meta_val = $uni_meta->addChild('val', ''); # Defines the print favourite in the online system which is used to auto-complete the order file if necessary.
			#$uni_meta_val->addAttribute('n', 'favorite');

			#$uni_meta_val = $uni_meta->addChild('val', ''); # Defines the profile group where the shipment should be stored in the online system.
			#$uni_meta_val->addAttribute('n', 'partition');

			// Lähettäjän tiedot
			/** tehdään yhtiön parametri "unifaun pikahakuarvo"
			 * voidaan silloin jättää nimitiedot tyhjiksi
			*/
			$uni_sender = $xml->addChild('sender');	# Attribute sndid corresponds to sender ID/quick ID. Any contents. Mandatory
			$uni_sender->addAttribute('sndid', utf8_encode("{$this->yhtiorow["tunnus"]}"));

				// $uni_snd_val = $uni_sender->addChild('val', str_replace($search, $replace, $this->postirow["yhtio_nimi"])); # Sender's name
				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_nimi"])); # Sender's name
				$uni_snd_val->addAttribute('n', "name");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_osoite"])); # Address line 1
				$uni_snd_val->addAttribute('n', "address1");

				#$uni_snd_val = $uni_sender->addChild('val', ""); # Address line 2
				#$uni_snd_val->addAttribute('n', "address2");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_postino"])); # Zip code
				$uni_snd_val->addAttribute('n', "zipcode");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_postitp"])); # City
				$uni_snd_val->addAttribute('n', "city");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_maa"])); # Country code according to ISO-standard
				$uni_snd_val->addAttribute('n', "country");

				#$uni_snd_val = $uni_sender->addChild('val', ""); # Contact person
				#$uni_snd_val->addAttribute('n', "contact");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->yhtiorow["puhelin"])); # Phone number
				$uni_snd_val->addAttribute('n', "phone");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->yhtiorow["fax"])); # Fax number
				$uni_snd_val->addAttribute('n', "fax");

				#$uni_snd_val = $uni_sender->addChild('val', ""); # Organisation number (only for Sweden)
				#$uni_snd_val->addAttribute('n', "orgno");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode(str_replace("0037", "FI", $this->postirow["yhtio_ovttunnus"]))); # VAT number
				$uni_snd_val->addAttribute('n', "vatno");

				$uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->yhtiorow["email"])); # E-mail
				$uni_snd_val->addAttribute('n', "email");

				#$uni_snd_val = $uni_sender->addChild('val', ""); # Mobile phone number. In Sweden the number must begin with 07 and contain 10 digits.
				#$uni_snd_val->addAttribute('n', "sms");

				// Lähettäjän rahtisopimustiedot
				$uni_sender_partner = $uni_sender->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				$uni_sender_partner->addAttribute('parid', utf8_encode($this->toitarow['rahdinkuljettaja']));

					$lahettajan_sopimusnro = $this->toitarow['sopimusnro'] != "" ? $this->toitarow['sopimusnro'] : "n/a";

					$uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($lahettajan_sopimusnro)); 	# Customer number
					$uni_par_val->addAttribute('n', "custno");

					$uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->toitarow['sopimusnro'])); 	# Customer number for international services
					$uni_par_val->addAttribute('n', "custno_international");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# EDI-address. UFPS only.
					#$uni_par_val->addAttribute('n', "ediaddress");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					#$uni_par_val->addAttribute('n', "palletregno");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					#$uni_par_val->addAttribute('n', "terminal");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "postgiro");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "bankgiro");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Specifies an offshore account.
					#$uni_par_val->addAttribute('n', "konto");

					$uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->yhtiorow["pankkitili1"])); 	# IBAN account number
					$uni_par_val->addAttribute('n', "iban");

					$uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->yhtiorow["pankkiswift1"])); 	# BIC number
					$uni_par_val->addAttribute('n', "bic");

					#$uni_par_val = $uni_sender_partner->addChild('val', ""); 	# Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
					#$uni_par_val->addAttribute('n', "paymentmethod");

			// Vastaanottajan tiedot
			$uni_receiver = $xml->addChild('receiver');	# Any contents. Mandatory.

			$uni_receiver->addAttribute('rcvid', utf8_encode($this->asiakasrow["tunnus"]));

				// jos vastaanottaja maksaa rahdin, haetaan sopimusnumero asiakkaan rahtisopimukselta
				if ($this->toitarow['merahti'] == '') {

					// Lähettäjän rahtisopimustiedot
					$uni_receiver_partner = $uni_receiver->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
					$uni_receiver_partner->addAttribute('parid', utf8_encode($this->toitarow['rahdinkuljettaja']));

					$query = "	SELECT rahtisopimus
								FROM rahtisopimukset
								WHERE yhtio = '{$this->kukarow['yhtio']}'
								AND (asiakas = '{$this->postirow['liitostunnus']}' OR ytunnus = '{$this->postirow['ytunnus']}')
								AND toimitustapa = '{$this->toitarow['selite']}'
								AND rahtisopimus != ''
								ORDER BY asiakas DESC
								LIMIT 1";
					$rahtisopimus_res = pupe_query($query);
					$rahtisopimus_row = mysql_fetch_assoc($rahtisopimus_res);

					$rahtisopimusnro = mysql_num_rows($rahtisopimus_res) == 0 ? 0 : $rahtisopimus_row['rahtisopimus'];

					// Jos sopparinumero puuttuu
					if ((is_numeric($rahtisopimusnro) and (int) $rahtisopimusnro == 0) or (!is_numeric($rahtisopimusnro) and (string) $rahtisopimusnro == "")) {

						if ($this->toitarow['virallinen_selite'] == "KKSTD") $rahtisopimusnro = 999999;
						elseif ($this->toitarow['virallinen_selite'] == "TPSTD" or $this->toitarow['virallinen_selite'] == "IT09" or $this->toitarow['virallinen_selite'] == "IT14") $rahtisopimusnro = 123456;
						elseif ($this->toitarow['virallinen_selite'] == "KLGRP") $rahtisopimusnro = 1234567;
						elseif ($this->toitarow['virallinen_selite'] == "MH10" or $this->toitarow['virallinen_selite'] == "MH30") $rahtisopimusnro = 12345678;
						else $rahtisopimusnro = 123;
					}

					// Jos sopparinumero on, mutta se on virheellinen
					if ((is_numeric($rahtisopimusnro) and (int) $rahtisopimusnro > 0) or (!is_numeric($rahtisopimusnro) and (string) $rahtisopimusnro != "")) {

						if (($this->toitarow['virallinen_selite'] == "IT09" or $this->toitarow['virallinen_selite'] == "IT14") and strlen($rahtisopimusnro) != 6) {
							$rahtisopimusnro = 999999;
						}

						if ($this->toitarow['virallinen_selite'] == "KLGRP" and strlen($rahtisopimusnro) < 7) {
							$rahtisopimusnro = 9999999;
						}
					}

					$uni_par_val = $uni_receiver_partner->addChild('val', utf8_encode($rahtisopimusnro)); 	# Customer number
					$uni_par_val->addAttribute('n', "custno");

					// Nämä ovat nyt käytössä:
					#FREEG 		--> OPAY tai RPAY, numeric, minlength(1), maxlength(15)
					#H48 		--> RPAY
					#IT09 		--> OPAY
					#IT14 		--> OPAY
					#KKSTD 		--> OPAY tai RPAY, 6 numeroa
					#TPSTD 		--> RPAY, 6 numeroa
					#KLGRP 		--> OPAY tai RPAY, min 7 max 8
					#MH10 		--> RPAY, numeric minlength(8)
					#MH20 		--> N/A
					#MH30	 	--> RPAY
					#SBTLFIEXP 	--> N/A
					#TPSTD 		--> RPAY, 1-6 numeroa

					// RPAY (receiver pays) and OPAY (other payer)
					$allowed_rpay = array(
						'FREEG',
						'H48',
						'KKSTD',
						'TPSTD',
						'KLGRP',
						'MH10',
						'MH30',
					);

					$allowed_opay = array(
						'IT09',
						'IT14',
					);
				}

				if ($this->toitarow['rahdinkuljettaja'] == 'HIT') {
					// HITin id4user -kentän arvo määritetään asiakas/toimitusosoitekohtaiseksi
					if (trim($this->asiakasrow["toim_nimi"]) == "") {
						$toim_osoite	= $this->asiakasrow["osoite"];
						$toim_postino	= $this->asiakasrow["postino"];
						$toim_postitp	= $this->asiakasrow["postitp"];
					}
					else {
						$toim_osoite	= $this->asiakasrow["toim_osoite"];
						$toim_postino	= $this->asiakasrow["toim_postino"];
						$toim_postitp	= $this->asiakasrow["toim_postitp"];
					}

					if ($this->postirow["toim_osoite"]  == $toim_osoite and $this->postirow["toim_postino"] == $toim_postino and $this->postirow["toim_postitp"] == $toim_postitp) {
						// Asiakkaan oletustoimitusosoite, käytetään asiakkaan tunnusta
						$id4user = $this->postirow["liitostunnus"];
					}
					else {
						// Käsin syötetty toimitusosite, generoidaan md5
						$id4user = md5($this->postirow["liitostunnus"].$this->postirow["toim_osoite"].$this->postirow["toim_postino"].$this->postirow["toim_postitp"]);
					}

					$uni_rcv_val = $uni_receiver->addChild('val', $id4user); # HIT InNight mandatory.
					$uni_rcv_val->addAttribute('n', "id4user");
				}

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode(trim($this->rakir_row["toim_nimi"]." ".$this->rakir_row["toim_nimitark"]))); # Receiver's name
				$uni_rcv_val->addAttribute('n', "name");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_osoite"])); # Address line 1
				$uni_rcv_val->addAttribute('n', "address1");

				#$uni_rcv_val = $uni_receiver->addChild('val', ""); # Address line 2
				#$uni_rcv_val->addAttribute('n', "address2");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_postino"])); # Zipcode
				$uni_rcv_val->addAttribute('n', "zipcode");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_postitp"])); # City
				$uni_rcv_val->addAttribute('n', "city");

				#$uni_rcv_val = $uni_receiver->addChild('val', ""); # State
				#$uni_rcv_val->addAttribute('n', "state");

				// jos asiakkaan toim_maa on tyhjä niin oletetaan, että se on sama ku yhtiön maa
				$toim_maa = ($this->rakir_row['toim_maa'] != "") ? $this->rakir_row['toim_maa'] : $this->postirow["yhtio_maa"];

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($toim_maa)); # Country code according to ISO standard
				$uni_rcv_val->addAttribute('n', "country");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["tilausyhteyshenkilo"])); # Contact person
				$uni_rcv_val->addAttribute('n', "contact");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["puhelin"])); # Phone number
				$uni_rcv_val->addAttribute('n', "phone");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->asiakasrow["fax"])); # Fax number
				$uni_rcv_val->addAttribute('n', "fax");

				#$uni_rcv_val = $uni_receiver->addChild('val', ""); # Organisation number (for Sweden only)
				#$uni_rcv_val->addAttribute('n', "orgno");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["ytunnus"])); # VAT number
				$uni_rcv_val->addAttribute('n', "vatno");

				#$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->asiakasrow["email"])); # E-mail
				#$uni_rcv_val->addAttribute('n', "email");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["puhelin"])); # Mobile phone number. For Sweden, the number must begin with 07 and contain 10 characters.
				$uni_rcv_val->addAttribute('n', "sms");

				#$uni_rcv_val = $uni_receiver->addChild('val', ""); # Door code
				#$uni_rcv_val->addAttribute('n', "doorcode");

				// Vastaanottajan rahtisopimustiedot
				// tämä on jo setattu ylempää
				#$uni_partner = $uni_receiver->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				#$uni_partner->addAttribute('parid', "");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Customer number
					#$uni_par_val->addAttribute('n', "custno");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					#$uni_par_val->addAttribute('n', "palletregno");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					#$uni_par_val->addAttribute('n', "terminal");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "postgiro");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "bankgiro");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Specifies an offshore account.
					#$uni_par_val->addAttribute('n', "konto");

					#$uni_par_val = $uni_receiver_partner->addChild('val', ""); 	# Agent's identity, mandatory value for DBSchenker PrivPak. Normally set by application.
					#$uni_par_val->addAttribute('n', "agentno");

			// Lähetyksen tiedot
			$uni_shipment = $xml->addChild('shipment');	# Unique order number. Any contents. Mandatory. Order number is searchable in the system but not printed on shipping documents.
			$uni_shipment->addAttribute('orderno', utf8_encode($this->postirow["shipment_unique_id"]));

			if ($this->toitarow['tulostustapa'] == 'E') {

				if ($this->yhtiorow['kerayserat'] == 'K') {
					$mergeid = md5($this->postirow["toimitustavan_lahto"].$this->postirow["ytunnus"].$this->postirow["toim_osoite"].$this->postirow["toim_postino"].$this->postirow["toim_postitp"]);
				}
				else {
					$mergeid = md5(date("Ymd").$this->postirow["ytunnus"].$this->postirow["toim_osoite"].$this->postirow["toim_postino"].$this->postirow["toim_postitp"]);
				}

				$uni_shipment->addAttribute('mergeid', utf8_encode($mergeid));
			}

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->yhtiorow["tunnus"])); # Defines the sender. Refers to the sndid value for sender.
				$uni_shi_val->addAttribute('n', "from");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the legal sender (not printed on shipping documents).
				#$uni_shi_val->addAttribute('n', "legalfrom");

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->asiakasrow["tunnus"])); # Defines the receiver. Refers to rcvid value for receiver.
				$uni_shi_val->addAttribute('n', "to");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the legal receiver (not printed on shipping documents).
				#$uni_shi_val->addAttribute('n', "legalto");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the agent's ID for recipient in shipment. Used by DBSchenker PrivPak to store the agent's details, address etc. For Bring it's used to store address details to MyQuickBox machine.
				#$uni_shi_val->addAttribute('n', "agentto");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Exporter
				#$uni_shi_val->addAttribute('n', "customsfrom");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Importer
				#$uni_shi_val->addAttribute('n', "customsto");

				#$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->postirow["tunnus"])); # Shipment ID. UFPS only.
				#$uni_shi_val->addAttribute('n', "shpid");


				if ($this->yhtiorow['kerayserat'] == 'K') {
					$pakkausid = chr(64+$this->postirow['pakkausid']);
				}
				else {
					$pakkausid = $this->postirow['kuljetusohjeet'];
				}

				# PakkausID
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($pakkausid, 0, 30))); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext1");

				# kollilaji
				if ($this->yhtiorow['kerayserat'] == 'K') {
					$kollilaji = $this->postirow['kollilaji']." / ".$this->kukarow['keraajanro']." / ".$this->postirow['tunnus']." / ".$this->postirow['rivimaara'];
				}
				else {
					$kollilaji = "";
				}

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($kollilaji, 0, 30))); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext2");

				if (strlen($this->postirow["ohjausmerkki"]) > 30) {
					$ohjausmerkki1 = substr($this->postirow["ohjausmerkki"], 0, 30);
					$ohjausmerkki2 = substr($this->postirow["ohjausmerkki"], 31, 30);
				}
				else {
					$ohjausmerkki1 = $this->postirow["ohjausmerkki"];
					$ohjausmerkki2 = "";
				}

				# ohjausmerkki
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($ohjausmerkki1)); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext3");

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->rakir_row["viesti"])); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext4");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Number of EUR pallets in the shipment. Requires palletregno for sender and receiver.
				#$uni_shi_val->addAttribute('n', "eurpallets");

				# lähettäjän viite
				$reference = utf8_encode(substr(preg_replace("/[^\x20-\xFF]/", "", trim($this->postirow["viesti"])), 0, 17));

				$uni_shi_val = $uni_shipment->addChild('val', $reference); # Shipment reference. Any contents. Max. 17 characters.
				$uni_shi_val->addAttribute('n', "reference");

				if (trim($reference) != "" and is_int($reference)) {
					$uni_shi_val = $uni_shipment->addChild('val', $reference); # Shipment reference as barcode. Max. 17 numeric characters.
					$uni_shi_val->addAttribute('n', "referencebarcode");
				}

				# vastaanottajan viite
				$reference = utf8_encode(substr(preg_replace("/[^\x20-\xFF]/", "", trim($this->postirow["asiakkaan_tilausnumero"])), 0, 17));

				$uni_shi_val = $uni_shipment->addChild('val', $reference); # Receiver's reference. Any contents. Max. 17 characters.
				$uni_shi_val->addAttribute('n', "rcvreference");

				#$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "sisfreetext1");

				#$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "sisfreetext2");

				#$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "sisfreetext3");

				#$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "sisfreetext4");

				#$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "sisfreetext5");

				#$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrfreetext1");

				#$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrfreetext2");

				#$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrfreetext3");

				#$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrfreetext4");

				#$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrfreetext5");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrdocuments1");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
				#$uni_shi_val->addAttribute('n', "cmrdocuments2");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Specifies any special agreement. Max. 30 characters.
				#$uni_shi_val->addAttribute('n', "cmrspecialagreement");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Delivery terms. See SUP-112-Services-en.xls for valid delivery terms.
				#$uni_shi_val->addAttribute('n', "termcode");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the location where takeover for the specified delivery term is done.
				#$uni_shi_val->addAttribute('n', "termlocation");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines which documents to print. Pipe characters are mandatory. Valid values: |label| = Label only, |sis| = Waybill only, |*| = None of the above
				#$uni_shi_val->addAttribute('n', "printset");

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(date("Y-m-d"))); # Defines shipment date. Printed on shipping documents. The default value is the current date. Please note that EDI is sent on this date.
				$uni_shi_val->addAttribute('n', "shipdate");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Customs currency unit
				#$uni_shi_val->addAttribute('n', "customsunit");

				if (trim($rahdinkuljettaja_row['neutraali']) != "") {
					$uni_service = $uni_shipment->addChild('service'); # Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
					$uni_service->addAttribute('srvid', utf8_encode('FREEG'));
					$uni_service->addAttribute('subid', utf8_encode($this->toitarow['rahdinkuljettaja']));
				}
				else {
					$uni_service = $uni_shipment->addChild('service'); # Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
					$uni_service->addAttribute('srvid', utf8_encode($this->toitarow['virallinen_selite']));
				}

					if (trim($this->toitarow['lajittelupiste']) != "") {
						$uni_ser_val = $uni_service->addChild('val', $this->toitarow['lajittelupiste']); # HIT InNight mandatory.
						$uni_ser_val->addAttribute('n', "sortpos");
					}

					$uni_ser_val = $uni_service->addChild('val', "no"); # Defines if the shipment is a return shipment or not. Valid values: yes, no
					$uni_ser_val->addAttribute('n', "returnlabel");

					$uni_ser_val = $uni_service->addChild('val', "RETURN"); # Defines action when the package is undeliverable. Only for Posten Postpaket Utrikes. RETURN = Return to sender, ABANDON = Treat as abandoned in receiver's country.
					$uni_ser_val->addAttribute('n', "nondelivery");

					#$uni_booking = $uni_service->addChild('booking'); # Booking information for pick up with DBSchenker. UFPS only.

						#$uni_ser_val = $uni_booking->addChild('val', ""); # OPAL-number. Acquired from DBSchenker.
						#$uni_ser_val->addAttribute('n', "bookingid");

						#$uni_ser_val = $uni_booking->addChild('val', ""); # Booking office, numeric code. See SUP-112-Services-en.xls for valid codes.
						#$uni_ser_val->addAttribute('n', "bookingoffice");

					if ($this->toitarow['merahti'] == "" and in_array($this->toitarow['virallinen_selite'], $allowed_rpay)) {
						$uni_addon = $uni_service->addChild('addon');
						$uni_addon->addAttribute('adnid', 'rpay');

						$uni_add_val = $uni_addon->addChild('val', utf8_encode($rahtisopimusnro));
						$uni_add_val->addAttribute('n', 'custno');
					}
					elseif ($this->toitarow['merahti'] == "" and in_array($this->toitarow['virallinen_selite'], $allowed_opay)) {
						$uni_addon = $uni_service->addChild('addon');
						$uni_addon->addAttribute('adnid', 'opay');

						$uni_add_val = $uni_addon->addChild('val', utf8_encode($rahtisopimusnro));
						$uni_add_val->addAttribute('n', 'custno');

						if ($this->toitarow['virallinen_selite'] == "IT09" or $this->toitarow['virallinen_selite'] == "IT14") {
							$uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["tunnus"])); # Payment reference. Used with add-on COD.
							$uni_add_val->addAttribute('n', "reference");
						}
					}

					if ($this->toitarow['virallinen_selite'] == "P19" and $this->rakir_row["puhelin"] != "") {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "NOTSMS");
						$uni_add_val = $uni_addon->addChild('val', utf8_encode($this->rakir_row["puhelin"])); # Defines value for misctype.
						$uni_add_val->addAttribute('n', "misc");
						$uni_add_val = $uni_addon->addChild('val', "PHONE"); # Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
						$uni_add_val->addAttribute('n', "misctype");
					}
					elseif ($this->toitarow['virallinen_selite'] == "P19") {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "NOTLTR");
					}

					if ($this->rakir_row["jv"] != '' or $this->mehto['jv'] != '') {
						$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
						$uni_addon->addAttribute('adnid', "COD");

							$uni_add_val = $uni_addon->addChild('val', utf8_encode($this->yhteensa)); # Amount. Used only with add-on COD (Cash On Delivery).
							$uni_add_val->addAttribute('n', "amount");

							$uni_add_val = $uni_addon->addChild('val', utf8_encode($this->viite)); # Payment reference. Used with add-on COD.
							$uni_add_val->addAttribute('n', "reference");
					}

					#$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
					#$uni_addon->addAttribute('adnid', "");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Amount. Used only with add-on COD (Cash On Delivery).
					#	$uni_add_val->addAttribute('n', "amount");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Customer number for carrier. Used primarily with RPAY (receiver pays) and OPAY (other payer).
					#	$uni_add_val->addAttribute('n', "custno");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Payment reference. Used with add-on COD.
					#	$uni_add_val->addAttribute('n', "reference");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines value for misctype.
					#	$uni_add_val->addAttribute('n', "misc");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
					#	$uni_add_val->addAttribute('n', "misctype");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines name of collection point for Posten add-on DLVNOT.
					#	$uni_add_val->addAttribute('n', "text1");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines address for collection point for Posten add-on DLVNOT.
					#	$uni_add_val->addAttribute('n', "text2");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines phone number for Posten add-ons PODNOT, DLVNOT and PRENOT.
					#	$uni_add_val->addAttribute('n', "text3");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Defines e-mail address for Posten add-ons PODNOT, DLVNOT and PRENOT.
					#	$uni_add_val->addAttribute('n', "text4");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Max. temperature allowed. Used with DBSchenker ColdSped.
					#	$uni_add_val->addAttribute('n', "tempmax");
                    #
					#	$uni_add_val = $uni_addon->addChild('val', ""); # Min. temperature allowed. Used by DBSchenker ColdSped.
					#	$uni_add_val->addAttribute('n', "tempmin");

		$this->xml = $xml;
	}

	public function setContainerRow($pakkaustiedot) {

		// $uni_parcel = $uni_shipment->addChild('container'); # Parcel information can be supplied in various ways. See p. 7.
		$uni_parcel = $this->xml->shipment->addChild('container'); # Parcel information can be supplied in various ways. See p. 7.
		$uni_parcel->addAttribute('type', "parcel");

		$uni_par_val = $uni_parcel->addChild('val', round(utf8_encode($pakkaustiedot['maara']))); # Number of parcels, pyöristetään varmuuden vuoksi lähimpään kokonaislukuun
		$uni_par_val->addAttribute('n', "copies");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Parcel ID. Used only for custom parcel ID. UFPS only. Cntid is to be incremented according to number of parcels.
		#$uni_par_val->addAttribute('n', "cntid1");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Goods marking
		#$uni_par_val->addAttribute('n', "marking");

		$pakkauskoodi = $pakkaustiedot['pakkauskoodi'] != '' ? $pakkaustiedot['pakkauskoodi'] : "PC";

		if ($pakkauskoodi == 'PC' and $this->toitarow['rahdinkuljettaja'] == 'TP') {
			$pakkauskoodi = "KLI";
		}

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkauskoodi)); # Package code. See SUP-112-Services-en.xls for valid package codes.
		$uni_par_val->addAttribute('n', "packagecode");

		$pakkaustiedot['paino'] = $pakkaustiedot['paino'] < 1 ? 1 : $pakkaustiedot['paino'];

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['paino'])); # Weight
		$uni_par_val->addAttribute('n', "weight");

		$query = "	SELECT koodi,
					pakkauksen_sarman_minimimitta
					FROM rahdinkuljettajat
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND koodi = '{$this->toitarow['rahdinkuljettaja']}'";
		$rahdinkuljettajat_result = pupe_query($query);
		$rahdinkuljettaja_rivi = mysql_fetch_assoc($rahdinkuljettajat_result);

		if (empty($rahdinkuljettaja_rivi['pakkauksen_sarman_minimimitta'])) {
			$pakkauksen_sarman_minimimitta = 0.01;
		}
		else {
			$pakkauksen_sarman_minimimitta = $rahdinkuljettaja_rivi['pakkauksen_sarman_minimimitta'];
		}

		$pakkaustiedot['syvyys'] = $pakkaustiedot['syvyys'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : $pakkaustiedot['syvyys'];
		$pakkaustiedot['leveys'] = $pakkaustiedot['leveys'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : $pakkaustiedot['leveys'];

		/*
		Otamme pakkauksen kaksi mittaa (pohjan mitat) syvyys ja leveys, korkeuden laskemme tilavuudesta jakamalla tilavuuden pohjan alalla.
		Teoreettisesti ajatelle näin saisimme tarkimmin viedyksi rahtitietoihin kollin mitat.
		Pohjan alahan ei muutu, mutta pakkausta puukotetaan matalammaksi (pahvilaatikon reunoja leikataan/viikataan matalammaksi)
		tai lavapakkausta rakennetaan alhaalta ylöspäin niin korkealle kuin tavaraa on laittaa.
		*/
		if ($pakkaustiedot['kuutiot'] > 0) {
			$pakkaustiedot['korkeus'] = $pakkaustiedot['kuutiot'] / ($pakkaustiedot['syvyys'] * $pakkaustiedot['leveys']);

			$pakkaustiedot['korkeus'] = $pakkaustiedot['korkeus'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : round($pakkaustiedot['korkeus'], 2);
		}
		else {
			$pakkaustiedot['korkeus'] = $pakkauksen_sarman_minimimitta;
			$pakkaustiedot['kuutiot'] = $pakkauksen_sarman_minimimitta * $pakkauksen_sarman_minimimitta * $pakkauksen_sarman_minimimitta;
			$pakkaustiedot['kuutiot'] = $pakkaustiedot['kuutiot'] < 0.01 ? 0.01 : $pakkaustiedot['kuutiot'];
		}

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['kuutiot'])); # Volume
		$uni_par_val->addAttribute('n', "volume");

		if ($pakkaustiedot['lavametri'] > 0) {
			$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['lavametri'])); # Loadmeter. Can only be specified for entire shipment.
			$uni_par_val->addAttribute('n', "area");
		}

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['syvyys'])); # Length
		$uni_par_val->addAttribute('n', "length");

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['leveys'])); # Width
		$uni_par_val->addAttribute('n', "width");

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['korkeus'])); # Height
		$uni_par_val->addAttribute('n', "height");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Item number
		#$uni_par_val->addAttribute('n', "itemno");

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['pakkauskuvaus'])); # Contents
		$uni_par_val->addAttribute('n', "contents");

		if ($this->toitarow['rahdinkuljettaja'] == 'POSTI' and (count($pakkaustiedot['vakkoodi']) > 0 or $pakkaustiedot['pakkauskuvaus'] == 'MUU KOLLI')) {
			/*
			TEHDÄÄN TÄMÄ JOS VAK TAI MUU KOLLI
			$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
			$uni_addon->addAttribute('adnid', "SPTR");
			*/
			$uni_addon = $this->xml->shipment->service->addChild('addon');
			$uni_addon->addAttribute('adnid', "SPTR");
		}

		if (count($pakkaustiedot['vakkoodi']) > 0) {

			foreach ($pakkaustiedot['vakkoodi'] as $vak) {

				$uni_article = $uni_parcel->addChild('article');

				if (isset($vak['tuoteno']) and $vak['tuoteno'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['tuoteno'])); # article number
					$uni_par_val->addAttribute('n', "articleno");
				}

				if (isset($vak['tuotenimitys']) and $vak['tuotenimitys'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['tuotenimitys'])); # UN-number for ADR. Supplied as a 4 digit code.
					$uni_par_val->addAttribute('n', "name");
				}

				if (isset($vak['kpl']) and $vak['kpl'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['kpl'])); # UN-number for ADR. Supplied as a 4 digit code.
					$uni_par_val->addAttribute('n', "count");
				}

				if (isset($vak['kpl_paino']) and $vak['kpl_paino'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['kpl_paino'])); # UN-number for ADR. Supplied as a 4 digit code.
					$uni_par_val->addAttribute('n', "weight");
				}

				if (isset($vak['yk_nro']) and $vak['yk_nro'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['yk_nro'])); # UN-number for ADR. Supplied as a 4 digit code.
					$uni_par_val->addAttribute('n', "dnguncode");
				}

				if (isset($vak['lipukkeet']) and $vak['lipukkeet'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['lipukkeet'])); # Label number for ADR
					$uni_par_val->addAttribute('n', "dnghzcode");
				}

				if (isset($vak['pakkausryhma']) and (int) $vak['pakkausryhma'] > 0) {
					$vak['pakkausryhma'] = (int) $vak['pakkausryhma'];
					$vak['pakkausryhma'] = $vak['pakkausryhma'] > 3 ? 3 : $vak['pakkausryhma'];

					$uni_par_val = $uni_article->addChild('val', utf8_encode(str_repeat("I", $vak['pakkausryhma']))); # Packaging group/ADR-class. Supplied as I, II or III.
					$uni_par_val->addAttribute('n', "dngpkcode");
				}

				if (isset($vak['luokka']) and $vak['luokka'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['luokka'])); # ADR-class
					$uni_par_val->addAttribute('n', "dngadrclass");
				}

				if (isset($vak['nimi_ja_kuvaus']) and $vak['nimi_ja_kuvaus'] != '') {
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['nimi_ja_kuvaus'])); # Official transport name for item regarding ADR
					$uni_par_val->addAttribute('n', "dngdescr");
				}

				#$uni_par_val = $uni_article->addChild('val', ""); # Defines if the contents contaminate the marine environment, ADR only. Valid values: 1 = Toxic and 2 = Non-toxic for the marine environment
				#$uni_par_val->addAttribute('n', "dngmpcode");

				if (isset($vak['limited_qty']) and $vak['limited_qty'] != '') {
					$uni_par_val = $uni_article->addChild('val', $vak['limited_qty']); # Note for ADR goods
					$uni_par_val->addAttribute('n', "dngnote");
				}

				if (isset($vak['paino']) and $vak['paino'] != '') {

					$vak['paino'] = $vak['paino'] < 1 ? 1 : $vak['paino'];

					# Net weight for ADR goods class I (usually explosive contents). Always mandatory for DBSchenker, regardless of class. Defined in kg.
					$uni_par_val = $uni_article->addChild('val', utf8_encode($vak['paino']));
					$uni_par_val->addAttribute('n', "dngnetweight");
				}
			}
		}
	}
}
