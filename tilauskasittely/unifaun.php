<?php

class Unifaun {

	private $hostname;
	private $username;
	private $password;
	private $path;
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

	public function __construct($hostname, $username, $password, $path) {

		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->path = $path;

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

	public function ftpSend() {
		if ($this->path != '') {
			if (substr($this->path,-1) != '/') {
				$this->path .= '/';
			}
			$filenimi = $this->path."unifaun-".md5(uniqid(rand(),true)).".txt";
		}
		else {
			$filenimi = "/tmp/unifaun-".md5(uniqid(rand(),true)).".txt";
		}

		$filenimi = dirname(dirname(__FILE__))."/dataout/unifaun-".md5(uniqid(rand(),true)).".txt";

		/* HUOM! TESTIPATH */
		// $filenimi = "/Users/sami/Sites/pupesoft/dataout/unifaun-".md5(uniqid(rand(),true)).".txt";
		$filenimi = "/tmp/unifaun-".md5(uniqid(rand(),true)).".txt";

		//kirjoitetaan faili levylle..
		if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
			echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus epäonnistui")."!</font><br>";
		}

		if ($this->hostname != "" and $this->username != "" and $this->password != "" and $this->path != "") {
			// tarvitaan  $ftphost $ftpuser $ftppass $ftppath $ftpfile
			// palautetaan $palautus ja $syy
			$ftphost = $this->hostname;
			$ftpuser = $this->username;
			$ftppass = $this->password;
			$ftppath = $this->path;
			$ftpfile = realpath($filenimi);

			require ("inc/ftp-send.inc");
		}
	}

	public function _saveForDebug() {
		$filenimi = "/Users/sami/Sites/pupesoft/dataout/unifaun-".md5(uniqid(rand(),true)).".txt";

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

		$uni_ready_val = $uni_ready->addChild('val', $mergeid);
		$uni_ready_val->addAttribute('n', 'mergeid');

		$uni_close = $control->addChild('close');

		$uni_close_val = $uni_close->addChild('val', $printer);
		$uni_close_val->addAttribute('n', 'printer');

		$uni_close_val = $uni_close->addChild('val', $mergeid);
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

		if ($mergeid != 0) {
			$uni_discard_val = $uni_discard->addChild('val', $mergeid);
			$uni_discard_val->addAttribute('n', 'mergeid');
		}

		$uni_discard_val = $uni_discard->addChild('val', $parcelno);
		$uni_discard_val->addAttribute('n', 'parcelno');

		$this->xml = $xml;

	}

	public function _getXML() {

		$xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><printserver></printserver>';

		// Luodaan UNIFAUN-XML
		$xml = new SimpleXMLElement($xmlstr);

			// Metatiedot
			$uni_meta = $xml->addChild('meta');

			// $uni_meta_val = $uni_meta->addChild('val', '|LASER1|'); # Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			$uni_meta_val = $uni_meta->addChild('val', ''); # Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
			$uni_meta_val->addAttribute('n', 'printer');

			#$uni_meta_val = $uni_meta->addChild('val', ''); # Defines the print favourite in the online system which is used to auto-complete the order file if necessary.
			#$uni_meta_val->addAttribute('n', 'favorite');

			#$uni_meta_val = $uni_meta->addChild('val', ''); # Defines the profile group where the shipment should be stored in the online system.
			#$uni_meta_val->addAttribute('n', 'partition');

			// Lähettäjän tiedot
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
				$uni_partner = $uni_sender->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				$uni_partner->addAttribute('parid', utf8_encode($this->toitarow['rahdinkuljettaja']));

					$uni_par_val = $uni_partner->addChild('val', utf8_encode($this->toitarow['sopimusnro'])); 	# Customer number
					$uni_par_val->addAttribute('n', "custno");

					$uni_par_val = $uni_partner->addChild('val', utf8_encode($this->toitarow['sopimusnro'])); 	# Customer number for international services
					$uni_par_val->addAttribute('n', "custno_international");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# EDI-address. UFPS only.
					#$uni_par_val->addAttribute('n', "ediaddress");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					#$uni_par_val->addAttribute('n', "palletregno");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					#$uni_par_val->addAttribute('n', "terminal");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "postgiro");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "bankgiro");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Specifies an offshore account.
					#$uni_par_val->addAttribute('n', "konto");

					$uni_par_val = $uni_partner->addChild('val', utf8_encode($this->yhtiorow["pankkitili1"])); 	# IBAN account number
					$uni_par_val->addAttribute('n', "iban");

					$uni_par_val = $uni_partner->addChild('val', utf8_encode($this->yhtiorow["pankkiswift1"])); 	# BIC number
					$uni_par_val->addAttribute('n', "bic");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
					#$uni_par_val->addAttribute('n', "paymentmethod");

			// Vastaanottajan tiedot
			$uni_receiver = $xml->addChild('receiver');	# Any contents. Mandatory.
			$uni_receiver->addAttribute('rcvid', utf8_encode($this->asiakasrow["tunnus"]));

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

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row['toim_maa'])); # Country code according to ISO standard
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

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->asiakasrow["email"])); # E-mail
				$uni_rcv_val->addAttribute('n', "email");

				$uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["puhelin"])); # Mobile phone number. For Sweden, the number must begin with 07 and contain 10 characters.
				$uni_rcv_val->addAttribute('n', "sms");

				#$uni_rcv_val = $uni_receiver->addChild('val', ""); # Door code
				#$uni_rcv_val->addAttribute('n', "doorcode");

				// Vastaanottajan rahtisopimustiedot
				#$uni_partner = $uni_receiver->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
				#$uni_partner->addAttribute('parid', "");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Customer number
					#$uni_par_val->addAttribute('n', "custno");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Pallet reg. number for EUR-pallets
					#$uni_par_val->addAttribute('n', "palletregno");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Terminal, used with delivery terms for international freights.
					#$uni_par_val->addAttribute('n', "terminal");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for PlusGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "postgiro");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Number for BankGiro. Used mostly by Cash On Delivery add-on.
					#$uni_par_val->addAttribute('n', "bankgiro");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Specifies an offshore account.
					#$uni_par_val->addAttribute('n', "konto");

					#$uni_par_val = $uni_partner->addChild('val', ""); 	# Agent's identity, mandatory value for DBSchenker PrivPak. Normally set by application.
					#$uni_par_val->addAttribute('n', "agentno");

			// Lähetyksen tiedot
			$uni_shipment = $xml->addChild('shipment');	# Unique order number. Any contents. Mandatory. Order number is searchable in the system but not printed on shipping documents.
			$uni_shipment->addAttribute('orderno', utf8_encode($this->postirow["shipment_unique_id"]));

			if ($this->toitarow['tulostustapa'] == 'E') {
				$uni_shipment->addAttribute('mergeid', utf8_encode($this->postirow["toimitustavan_lahto"]));
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

				# PakkausID
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr(chr(64+$this->postirow['pakkausid']), 0, 30))); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext1");

				# kollilaji
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($this->postirow['kollilaji'], 0, 30))); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
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

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($ohjausmerkki2)); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
				$uni_shi_val->addAttribute('n', "freetext4");

				#$uni_shi_val = $uni_shipment->addChild('val', ""); # Number of EUR pallets in the shipment. Requires palletregno for sender and receiver.
				#$uni_shi_val->addAttribute('n', "eurpallets");

				# lähettäjän viite
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->postirow["sscc"])); # Shipment reference. Any contents. Max. 17 characters.
				$uni_shi_val->addAttribute('n', "reference");

				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->postirow["sscc"])); # Shipment reference as barcode. Max. 17 numeric characters.
				$uni_shi_val->addAttribute('n', "referencebarcode");

				# vastaanottajan viite
				$uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($this->viite, 0, 17))); # Receiver's reference. Any contents. Max. 17 characters.
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

				$uni_service = $uni_shipment->addChild('service'); # Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
				$uni_service->addAttribute('srvid', utf8_encode($this->toitarow['virallinen_selite']));

					$uni_ser_val = $uni_service->addChild('val', "no"); # Defines if the shipment is a return shipment or not. Valid values: yes, no
					$uni_ser_val->addAttribute('n', "returnlabel");

					$uni_ser_val = $uni_service->addChild('val', "RETURN"); # Defines action when the package is undeliverable. Only for Posten Postpaket Utrikes. RETURN = Return to sender, ABANDON = Treat as abandoned in receiver's country.
					$uni_ser_val->addAttribute('n', "nondelivery");

					#$uni_booking = $uni_service->addChild('booking'); # Booking information for pick up with DBSchenker. UFPS only.

						#$uni_ser_val = $uni_booking->addChild('val', ""); # OPAL-number. Acquired from DBSchenker.
						#$uni_ser_val->addAttribute('n', "bookingid");

						#$uni_ser_val = $uni_booking->addChild('val', ""); # Booking office, numeric code. See SUP-112-Services-en.xls for valid codes.
						#$uni_ser_val->addAttribute('n', "bookingoffice");


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

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['maara'])); # Number of parcels
		$uni_par_val->addAttribute('n', "copies");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Parcel ID. Used only for custom parcel ID. UFPS only. Cntid is to be incremented according to number of parcels.
		#$uni_par_val->addAttribute('n', "cntid1");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Goods marking
		#$uni_par_val->addAttribute('n', "marking");

		$uni_par_val = $uni_parcel->addChild('val', "PC"); # Package code. See SUP-112-Services-en.xls for valid package codes.
		$uni_par_val->addAttribute('n', "packagecode");

		$pakkaustiedot['paino'] = $pakkaustiedot['paino'] < 1 ? 1 : $pakkaustiedot['paino'];		

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['paino'])); # Weight
		$uni_par_val->addAttribute('n', "weight");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Volume
		#$uni_par_val->addAttribute('n', "volume");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Loadmeter. Can only be specified for entire shipment.
		#$uni_par_val->addAttribute('n', "area");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Length
		#$uni_par_val->addAttribute('n', "length");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Width
		#$uni_par_val->addAttribute('n', "width");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Height
		#$uni_par_val->addAttribute('n', "height");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Item number
		#$uni_par_val->addAttribute('n', "itemno");

		$uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['pakkauskuvaus'])); # Contents
		$uni_par_val->addAttribute('n', "contents");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # UN-number for ADR. Supplied as a 4 digit code.
		#$uni_par_val->addAttribute('n', "dnguncode");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Label number for ADR
		#$uni_par_val->addAttribute('n', "dnghzcode");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Packaging group/ADR-class. Supplied as I, II or III.
		#$uni_par_val->addAttribute('n', "dngpkcode");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # ADR-class
		#$uni_par_val->addAttribute('n', "dngadrclass");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Official transport name for item regarding ADR
		#$uni_par_val->addAttribute('n', "dngdescr");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Defines if the contents contaminate the marine environment, ADR only. Valid values: 1 = Toxic and 2 = Non-toxic for the marine environment
		#$uni_par_val->addAttribute('n', "dngmpcode");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Note for ADR goods
		#$uni_par_val->addAttribute('n', "dngnote");

		#$uni_par_val = $uni_parcel->addChild('val', ""); # Net weight for ADR goods class I (usually explosive contents). Always mandatory for DBSchenker, regardless of class. Defined in kg.
		#$uni_par_val->addAttribute('n', "dngnetweight");

	}

}
