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
  private $developerid;
  private $unifauntype;
  private $vak_tiedot = array();
  private $pikahaku_tunnus = null;

  public function __construct() {
  }

  // FTP-siirto
  public static function online($hostname, $username, $password, $ftppath, $ftpport, $ftpfail, $ftpsucc) {
    $instance = new self();

    $instance->unifauntype = "ONLINE";
    $instance->hostname    = $hostname;
    $instance->username    = $username;
    $instance->password    = $password;
    $instance->ftppath     = $ftppath;
    $instance->ftpport     = $ftpport;
    $instance->ftpfail     = $ftpfail;
    $instance->ftpsucc     = $ftpsucc;

    return $instance;
  }

  // XML-posting
  public static function xmlposting($developerid, $username, $password, $ftpfail, $ftpsucc, $unifaun_packsoft) {
    $instance = new self();

    $instance->unifauntype = "XMLPOSTING";
    $instance->developerid = $developerid;
    $instance->username    = $username;
    $instance->password    = $password;
    $instance->ftpfail     = $ftpfail;
    $instance->ftpsucc     = $ftpsucc;
    $instance->packsoft    = $unifaun_packsoft;

    return $instance;
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

  public function setVak(Array $vak) {
    $this->vak_tiedot = $vak;
  }

  public function onko_vak() {
    // jos on vak tietoja, on vak tuotteita
    return (count($this->vak_tiedot) > 0);
  }

  public function onko_lq() {
    // ohitetaan toitaiseksi aina LQ, pit‰‰ testata unifaunin kanssa tarkemmin
    // t‰m‰n funktion logiikka on kuitenkin oikein. xml tiedostoon tulee todenn‰kˆisesti
    // muutoksia, mik‰li LQ on p‰‰ll‰, joita ei ole viel‰ tehty
    return false;

    // valitaan vaan elementit, jossa on limited_qty
    $lq = array_filter($this->vak_tiedot, function($vak) {
      return !empty($vak['limited_qty']);
    });

    // on LQ jos tilauksella on vak tietoja, ja kaikki niist‰ on LQ tietoja
    return ($this->onko_vak() and count($lq) == count($this->vak_tiedot));
  }

  private function setAsiakasRow() {
    //  Haetaan asiakastiedot
    if ($this->postirow['liitostunnus'] == '999999999') {

      $this->asiakasrow = array(
        'tunnus' => $this->postirow['clearing'],
        'osoite' => $this->postirow['osoite'],
        'postino' => $this->postirow['postino'],
        'postitp' => $this->postirow['postitp'],
        'toim_osoite' => $this->postirow['toim_osoite'],
        'toim_postino' => $this->postirow['toim_postino'],
        'toim_postitp' => $this->postirow['toim_postitp'],
        'fax' => '',
      );
    }
    else {
      $query = "SELECT *
                FROM asiakas
                WHERE yhtio = '{$this->kukarow['yhtio']}'
                and tunnus  = '{$this->postirow['liitostunnus']}'";
      $asres = pupe_query($query);
      $this->asiakasrow = mysql_fetch_assoc($asres);

      if (!empty($this->postirow['toim_email'])) {
        $this->asiakasrow['email'] = $this->postirow['toim_email'];
      }
    }
  }

  public function sanitizeEmails($emails) {
    $emails = str_replace(" ", "", $emails);
    $emails = str_replace(",", ";", $emails);

    return $emails;
  }

  public function setPostiRow($postirow) {
    $this->postirow = $postirow;

    // haetaan varaston osoitetiedot, k‰ytet‰‰n niit‰ l‰hetystietoina
    $query = "SELECT osoite, postino, postitp, maa
              FROM varastopaikat
              WHERE yhtio  = '{$this->kukarow['yhtio']}'
              AND tunnus   = '{$this->postirow['varasto']}'
              AND osoite  != ''
              AND postino != ''
              AND postitp != ''
              AND maa     != ''";
    $tempr = pupe_query($query);

    // jos varastolle on annettu osoite, k‰ytet‰‰n sit‰
    if (mysql_num_rows($tempr) == 1) {
      $postirow_varasto = mysql_fetch_assoc($tempr);

      $this->postirow["yhtio_osoite"]  = $postirow_varasto["osoite"];
      $this->postirow["yhtio_postino"] = $postirow_varasto["postino"];
      $this->postirow["yhtio_postitp"] = $postirow_varasto["postitp"];
      $this->postirow["yhtio_maa"]     = $postirow_varasto["maa"];
    }

    // haetaan laskun_lisatiedot, k‰ytet‰‰n noutopisteen_tunnus jos se on != ''
    $ll_query = "SELECT noutopisteen_tunnus
                 FROM laskun_lisatiedot
                 WHERE yhtio              = '{$this->kukarow['yhtio']}'
                 AND otunnus              = '{$this->postirow['tunnus']}'
                 AND noutopisteen_tunnus != ''";
    $ll_res = pupe_query($ll_query);

    if ($ll_row = mysql_fetch_assoc($ll_res)) {
      $this->postirow["noutopisteen_tunnus"] = $ll_row['noutopisteen_tunnus'];
    }

    $this->setAsiakasRow();
  }

  public function setKirjoitin($kirjoitin) {
    $this->kirjoitin = $kirjoitin;
  }

  public function ftpSend() {

    $filenimi = "/tmp/unifaun-".md5(uniqid(rand(), true)).".txt";

    //kirjoitetaan faili levylle..
    if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
      echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus ep‰onnistui")."!</font><br>";
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

      // L‰hetet‰‰n UTF-8 muodossa jos PUPE_UNICODE on true
      $ftputf8 = PUPE_UNICODE;

      require "inc/ftp-send.inc";
    }
  }

  public function xmlPost() {
    $filenimi = "/tmp/unifaun-".md5(uniqid(rand(), true)).".txt";

    //kirjoitetaan faili levylle..
    if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
      echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus ep‰onnistui")."!</font><br>";
    }
    else {

      $url      = "https://www.unifaunonline.com/ufoweb/order";
      $user     = $this->username;
      $pass     = $this->password;
      $devi     = $this->developerid;
      $ftpfail  = $this->ftpfail;
      $ftpsucc  = $this->ftpsucc;
      $packsoft = $this->packsoft;

      if ($packsoft) {
        $session = "po_SE";
      }
      else {
        $session = "ufo_SE";
      }

      $f = fopen($filenimi, "r");

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "{$url}?session={$session}&user={$user}&pin={$pass}&developerid={$devi}&type=xml");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
      curl_setopt($ch, CURLOPT_POSTFIELDS, fread($f, filesize($filenimi)));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $result = curl_exec($ch);

      if ($result === FALSE) {
        echo "<font class='error'>VIRHE: \n";
        echo curl_errno($ch) . " - " . curl_error($ch) . "</font><br>";
        exit(1);
      }
      else {
        $basefile = basename($filenimi);

        $xml = simplexml_load_string($result);

        foreach ($xml->val as $val) {
          if ($val->attributes()->n == "status") {
            $xmlstatus = $val;
          }
          if ($val->attributes()->n == "message") {
            $xmlmessage = $val;
          }
        }

        if ($xmlstatus == "201" and !empty($ftpsucc)) {
          // Siirret‰‰n faili talteen jos siirto onnasi ja ok failit halutaan tallentaa
          rename($filenimi, $ftpsucc."/".$basefile);
        }
        elseif (!empty($ftpfail)) {
          // Siirret‰‰n faili outboxiin odottamaan uutta yrityst‰ jos siirto feilasi
          rename($filenimi, $ftpfail."/".$basefile);

          echo "<font class='error'>VIRHE: \n";
          echo "$xmlstatus, $xmlmessage</font><br>";
          exit(1);
        }
      }

      curl_close($ch);
    }
  }

  public function _saveForDebug($postfix = "") {
    $filenimi = "/tmp/unifaun-{$postfix}-{$this->toitarow['selite']}-".md5(uniqid(rand(), true)).".txt";

    //kirjoitetaan faili levylle..
    if (file_put_contents($filenimi, $this->xml->asXML()) === FALSE) {
      echo "<br><font class='error'>".t("VIRHE: tiedoston kirjoitus ep‰onnistui")."!</font><br>";
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

    if (is_numeric($parcelno) and substr($parcelno, 0, 2) == '00') {
      $parcelno = substr($parcelno, 2);
    }

    $uni_discard_val = $uni_discard->addChild('val', $parcelno);
    $uni_discard_val->addAttribute('n', 'parcelno');

    $this->xml = $xml;
  }

  public function _getXML() {

    if ($this->unifauntype == "ONLINE") {
    $xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><printserver></printserver>';
    }
    else {
      $xmlstr  = '<?xml version="1.0" encoding="UTF-8"?><unifaunonline></unifaunonline>';
    }

    $query = "SELECT *
              FROM rahdinkuljettajat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND koodi   = '{$this->toitarow['rahdinkuljettaja']}'";
    $rahdinkuljettaja_res = pupe_query($query);
    $rahdinkuljettaja_row = mysql_fetch_assoc($rahdinkuljettaja_res);

    // Luodaan UNIFAUN-XML
    $xml = new SimpleXMLElement($xmlstr);

    // Metatiedot
    $uni_meta = $xml->addChild('meta');

    // siivotaan puhelinnumerosta v‰lilyˆnnit ja muut erikoismerkit veke
    $this->postirow["toim_puh"] = preg_replace("/[^0-9\+]/", "", $this->postirow["toim_puh"]);

    // $uni_meta_val = $uni_meta->addChild('val', '|LASER1|'); # Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
    $uni_meta_val = $uni_meta->addChild('val', "{$this->kirjoitin}"); // Sends the print job to defined printer/ID. The value must be enclosed in pipe characters, |.
    $uni_meta_val->addAttribute('n', 'printer');

    // Kovakoodataan t‰h‰n VAK. Unifaun Onliness‰ tule perustaa t‰m‰n niminen Tulostussuosikki (nimiketaso) jos halutaan k‰ytt‰‰ VAK -koodeja.
    $uni_meta_val = $uni_meta->addChild('val', 'VAK'); # Defines the print favourite in the online system which is used to auto-complete the order file if necessary.
    $uni_meta_val->addAttribute('n', 'favorite');

    //$uni_meta_val = $uni_meta->addChild('val', ''); # Defines the profile group where the shipment should be stored in the online system.
    //$uni_meta_val->addAttribute('n', 'partition');

    // L‰hett‰j‰n tiedot
    $uni_sender = $xml->addChild('sender');  // Attribute sndid corresponds to sender ID/quick ID. Any contents. Mandatory
    $uni_sender->addAttribute('sndid', $this->pikahakuarvo());

    // $uni_snd_val = $uni_sender->addChild('val', str_replace($search, $replace, $this->postirow["yhtio_nimi"])); # Sender's name
    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_nimi"])); // Sender's name
    $uni_snd_val->addAttribute('n', "name");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_osoite"])); // Address line 1
    $uni_snd_val->addAttribute('n', "address1");

    //$uni_snd_val = $uni_sender->addChild('val', ""); # Address line 2
    //$uni_snd_val->addAttribute('n', "address2");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_postino"])); // Zip code
    $uni_snd_val->addAttribute('n', "zipcode");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_postitp"])); // City
    $uni_snd_val->addAttribute('n', "city");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->postirow["yhtio_maa"])); // Country code according to ISO-standard
    $uni_snd_val->addAttribute('n', "country");

    //$uni_snd_val = $uni_sender->addChild('val', ""); # Contact person
    //$uni_snd_val->addAttribute('n', "contact");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->yhtiorow["puhelin"])); // Phone number
    $uni_snd_val->addAttribute('n', "phone");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->yhtiorow["fax"])); // Fax number
    $uni_snd_val->addAttribute('n', "fax");

    //$uni_snd_val = $uni_sender->addChild('val', ""); # Organisation number (only for Sweden)
    //$uni_snd_val->addAttribute('n', "orgno");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode(preg_replace("/^0037/", "FI", $this->postirow["yhtio_ovttunnus"]))); // VAT number
    $uni_snd_val->addAttribute('n', "vatno");

    $uni_snd_val = $uni_sender->addChild('val', utf8_encode($this->sanitizeEmails($this->yhtiorow["email"]))); // E-mail
    $uni_snd_val->addAttribute('n', "email");

    //$uni_snd_val = $uni_sender->addChild('val', ""); # Mobile phone number. In Sweden the number must begin with 07 and contain 10 digits.
    //$uni_snd_val->addAttribute('n', "sms");

    // L‰hett‰j‰n rahtisopimustiedot
    $uni_sender_partner = $uni_sender->addChild('partner'); // Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
    $uni_sender_partner->addAttribute('parid', utf8_encode($this->toitarow['rahdinkuljettaja']));
    $lahettajan_sopimusnro = $this->toitarow['sopimusnro'] != "" ? $this->toitarow['sopimusnro'] : "n/a";

    $uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($lahettajan_sopimusnro));   // Customer number
    $uni_par_val->addAttribute('n', "custno");

    // DPDFI (PostNord DPD Classic) keississ‰ laitetaan v‰h‰n extraa
    if ($this->toitarow['rahdinkuljettaja'] == 'DPDFI' or $this->toitarow['rahdinkuljettaja'] == 'PLSCM') {
      $uni_par_val = $uni_sender_partner->addChild('val', "Z14");   // custnoissuercode
      $uni_par_val->addAttribute('n', "custnoissuercode");
    }

    $uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->toitarow['sopimusnro']));   // Customer number for international services
    $uni_par_val->addAttribute('n', "custno_international");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # EDI-address. UFPS only.
    //$uni_par_val->addAttribute('n', "ediaddress");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Pallet reg. number for EUR-pallets
    //$uni_par_val->addAttribute('n', "palletregno");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Terminal, used with delivery terms for international freights.
    //$uni_par_val->addAttribute('n', "terminal");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Number for PlusGiro. Used mostly by Cash On Delivery add-on.
    //$uni_par_val->addAttribute('n', "postgiro");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Number for BankGiro. Used mostly by Cash On Delivery add-on.
    //$uni_par_val->addAttribute('n', "bankgiro");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Specifies an offshore account.
    //$uni_par_val->addAttribute('n', "konto");

    $uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->yhtiorow["pankkiiban1"]));   // IBAN account number
    $uni_par_val->addAttribute('n', "iban");

    $uni_par_val = $uni_sender_partner->addChild('val', utf8_encode($this->yhtiorow["pankkiswift1"]));   // BIC number
    $uni_par_val->addAttribute('n', "bic");

    //$uni_par_val = $uni_sender_partner->addChild('val', "");   # Payment method. Used for Posten's mail services. Valid values: INVO = Credit without delivery note INVODN = Credit with delivery note METERED = Domestic franking STAMP = Stamp/cash
    //$uni_par_val->addAttribute('n', "paymentmethod");

    // Vastaanottajan tiedot
    $uni_receiver = $xml->addChild('receiver');  // Any contents. Mandatory.

    $uni_receiver->addAttribute('rcvid', utf8_encode($this->asiakasrow["tunnus"]));

    // jos vastaanottaja maksaa rahdin, haetaan sopimusnumero asiakkaan rahtisopimukselta
    if ($this->rakir_row['merahti'] == '') {

      // L‰hett‰j‰n rahtisopimustiedot
      $uni_receiver_partner = $uni_receiver->addChild('partner'); // Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
      $uni_receiver_partner->addAttribute('parid', utf8_encode($this->toitarow['rahdinkuljettaja']));

      $rahtisopimusnro = $this->rakir_row['rahtisopimus'];

      // Jos sopparinumero puuttuu
      if ((is_numeric($rahtisopimusnro) and (int) $rahtisopimusnro == 0) or (!is_numeric($rahtisopimusnro) and (string) $rahtisopimusnro == "")) {

        if ($this->toitarow['virallinen_selite'] == "KKSTD") $rahtisopimusnro = 999999;
        elseif ($this->toitarow['virallinen_selite'] == "TPSTD"
          or $this->toitarow['virallinen_selite'] == "PO2102_09"
          or $this->toitarow['virallinen_selite'] == "PO2102"
          or $this->toitarow['virallinen_selite'] == "PO2103"
          or $this->toitarow['virallinen_selite'] == "PO2104"
          or $this->toitarow['virallinen_selite'] == "PO2461"
          or $this->toitarow['virallinen_selite'] == "PO2711") $rahtisopimusnro = 123456;
        elseif ($this->toitarow['virallinen_selite'] == "KLGRP") $rahtisopimusnro = 1234567;
        elseif ($this->toitarow['virallinen_selite'] == "MH10" or $this->toitarow['virallinen_selite'] == "MH30") $rahtisopimusnro = 12345678;
        elseif ($this->toitarow['virallinen_selite'] == "MH10_SPTR" or $this->toitarow['virallinen_selite'] == "MH30_SPTR") $rahtisopimusnro = 12345678;
        else $rahtisopimusnro = 123;
      }

      // Jos sopparinumero on, mutta se on virheellinen
      if ((is_numeric($rahtisopimusnro) and (int) $rahtisopimusnro > 0) or (!is_numeric($rahtisopimusnro) and (string) $rahtisopimusnro != "")) {

        if (($this->toitarow['virallinen_selite'] == "PO2102_09"
            or $this->toitarow['virallinen_selite'] == "PO2102"
            or $this->toitarow['virallinen_selite'] == "PO2103"
            or $this->toitarow['virallinen_selite'] == "PO2104"
            or $this->toitarow['virallinen_selite'] == "PO2461"
            or $this->toitarow['virallinen_selite'] == "PO2711") and strlen($rahtisopimusnro) != 6) {
          $rahtisopimusnro = 999999;
        }

        if ($this->toitarow['virallinen_selite'] == "KLGRP" and strlen($rahtisopimusnro) < 7) {
          $rahtisopimusnro = 9999999;
        }
      }

      $uni_par_val = $uni_receiver_partner->addChild('val', utf8_encode($rahtisopimusnro));   // Customer number
      $uni_par_val->addAttribute('n', "custno");

      // N‰m‰ ovat nyt k‰ytˆss‰:
      //FREEG     --> OPAY tai RPAY, numeric, minlength(1), maxlength(15)
      //H48     --> RPAY
      //PO2102_09     --> OPAY
      //PO2102     --> OPAY
      //KKSTD     --> OPAY tai RPAY, 6 numeroa
      //TPSTD     --> RPAY, 6 numeroa
      //KLGRP     --> OPAY tai RPAY, min 7 max 8
      //MH10     --> RPAY, numeric minlength(8)
      //MH20     --> N/A
      //MH30     --> RPAY
      //SBTLFIEXP   --> N/A
      //TPSTD     --> RPAY, 1-6 numeroa

      // RPAY (receiver pays) and OPAY (other payer)
      $allowed_rpay = array(
        'FREEG',
        'H48',
        'KKSTD',
        'TPSTD',
        'KLGRP',
        'MH10',
        'MH30',
        'MH10_SPTR',
        'MH30_SPTR',
        'P83FI',
        'SBTLFIEXP',
      );

      $allowed_opay = array(
        'PO2102',
        'PO2102_09',
        'PO2102_00',
        'PO2103',
        'PO2104',
        'POF1',
        'PO2144',
        'PO2144_09',
        'PO2144_21',
        'PO2144_00',
        'ITKY14I',
        'IT14I',
        'PO2711',
        'PO2461',
      );
    }

    if ($this->toitarow['rahdinkuljettaja'] == 'HIT') {
      // HITin id4user -kent‰n arvo m‰‰ritet‰‰n asiakas/toimitusosoitekohtaiseksi
      if (trim($this->asiakasrow["toim_nimi"]) == "") {
        $toim_osoite  = $this->asiakasrow["osoite"];
        $toim_postino  = $this->asiakasrow["postino"];
        $toim_postitp  = $this->asiakasrow["postitp"];
      }
      else {
        $toim_osoite  = $this->asiakasrow["toim_osoite"];
        $toim_postino  = $this->asiakasrow["toim_postino"];
        $toim_postitp  = $this->asiakasrow["toim_postitp"];
      }

      if ($this->postirow["toim_osoite"]  == $toim_osoite and $this->postirow["toim_postino"] == $toim_postino and $this->postirow["toim_postitp"] == $toim_postitp) {
        // Asiakkaan oletustoimitusosoite, k‰ytet‰‰n asiakkaan tunnusta
        $id4user = $this->postirow["liitostunnus"];
      }
      else {
        // K‰sin syˆtetty toimitusosite, generoidaan md5
        $id4user = md5($this->postirow["liitostunnus"].$this->postirow["toim_osoite"].$this->postirow["toim_postino"].$this->postirow["toim_postitp"]);
      }

      $uni_rcv_val = $uni_receiver->addChild('val', $id4user); // HIT InNight mandatory.
      $uni_rcv_val->addAttribute('n', "id4user");
    }

    $_toim_nimi = utf8_encode($this->rakir_row["toim_nimi"]);
    $_toim_nimitark = utf8_encode($this->rakir_row["toim_nimitark"]);

    $uni_rcv_val = $uni_receiver->addChild('val', trim($_toim_nimi." ".$_toim_nimitark)); // Receiver's name
    $uni_rcv_val->addAttribute('n', "name");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_osoite"])); // Address line 1
    $uni_rcv_val->addAttribute('n', "address1");

    //$uni_rcv_val = $uni_receiver->addChild('val', ""); # Address line 2
    //$uni_rcv_val->addAttribute('n', "address2");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_postino"])); // Zipcode
    $uni_rcv_val->addAttribute('n', "zipcode");

    // jos asiakkaan toim_maa on tyhj‰ niin oletetaan, ett‰ se on sama ku yhtiˆn maa
    $toim_maa = ($this->rakir_row['toim_maa'] != "") ? $this->rakir_row['toim_maa'] : $this->postirow["yhtio_maa"];

    if ($toim_maa == "US") {

      list($toim_postitp, $state) = explode(" ", $this->rakir_row["toim_postitp"]);

      if ($state != '') {

        $this->rakir_row["toim_postitp"] = $toim_postitp;
        $state = $toim_maa."_".$state;
        $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($state)); # State
        $uni_rcv_val->addAttribute('n', "state");
      }
    }

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->rakir_row["toim_postitp"])); // City
    $uni_rcv_val->addAttribute('n', "city");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($toim_maa)); // Country code according to ISO standard
    $uni_rcv_val->addAttribute('n', "country");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["tilausyhteyshenkilo"])); // Contact person
    $uni_rcv_val->addAttribute('n', "contact");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["toim_puh"])); // Phone number
    $uni_rcv_val->addAttribute('n', "phone");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->asiakasrow["fax"])); // Fax number
    $uni_rcv_val->addAttribute('n', "fax");

    //$uni_rcv_val = $uni_receiver->addChild('val', ""); # Organisation number (for Sweden only)
    //$uni_rcv_val->addAttribute('n', "orgno");

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["ytunnus"])); // VAT number
    $uni_rcv_val->addAttribute('n', "vatno");

    if ($this->asiakasrow["email"] != "" and strpos($this->asiakasrow["email"], ",") !== FALSE) {
      $asiakasemail = substr($this->asiakasrow["email"], 0, strpos($this->asiakasrow["email"], ","));
    }
    else {
      $asiakasemail = $this->asiakasrow["email"];
    }

    if ($this->asiakasrow["email"] != "") {
      $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->sanitizeEmails($asiakasemail))); // E-mail
      $uni_rcv_val->addAttribute('n', "email");
    }

    $uni_rcv_val = $uni_receiver->addChild('val', utf8_encode($this->postirow["toim_puh"])); // Mobile phone number. For Sweden, the number must begin with 07 and contain 10 characters.
    $uni_rcv_val->addAttribute('n', "sms");

    //$uni_rcv_val = $uni_receiver->addChild('val', ""); # Door code
    //$uni_rcv_val->addAttribute('n', "doorcode");

    // Vastaanottajan rahtisopimustiedot
    // t‰m‰ on jo setattu ylemp‰‰
    //$uni_partner = $uni_receiver->addChild('partner'); # Attribute parid corresponds to carrier's ID. See SUP-112-Services-en.xls.
    //$uni_partner->addAttribute('parid', "");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Customer number
    //$uni_par_val->addAttribute('n', "custno");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Pallet reg. number for EUR-pallets
    //$uni_par_val->addAttribute('n', "palletregno");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Terminal, used with delivery terms for international freights.
    //$uni_par_val->addAttribute('n', "terminal");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Number for PlusGiro. Used mostly by Cash On Delivery add-on.
    //$uni_par_val->addAttribute('n', "postgiro");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Number for BankGiro. Used mostly by Cash On Delivery add-on.
    //$uni_par_val->addAttribute('n', "bankgiro");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Specifies an offshore account.
    //$uni_par_val->addAttribute('n', "konto");

    //$uni_par_val = $uni_receiver_partner->addChild('val', "");   # Agent's identity, mandatory value for DBSchenker PrivPak. Normally set by application.
    //$uni_par_val->addAttribute('n', "agentno");

    // K‰ytet‰‰n vastaanottajan rahtisopimusta: rahdinmaksajan tiedot aina ilmoitttava sanomalla
    $_nimi = utf8_encode($this->rakir_row["nimi"]);
    $_nimitark = utf8_encode($this->rakir_row["nimitark"]);
    $_osoite = utf8_encode($this->rakir_row["osoite"]);
    $_toim_osoite = utf8_encode($this->rakir_row["toim_osoite"]);
    
    if ($this->rakir_row['merahti'] == "" and ($this->toitarow["virallinen_selite"] == "POF1"
      or $this->toitarow["virallinen_selite"] == "SBTLFIEXP") 
      and ($_nimi != $_toim_nimi or $_nimitark != $_toim_nimitark or $_osoite != $_toim_osoite)) {
      $uni_party = $xml->addChild('party');  // Any contents. Mandatory.

      $uni_party->addAttribute('ptyid', utf8_encode($this->asiakasrow["tunnus"]));
      $shipment_payer = utf8_encode($this->asiakasrow["tunnus"]);

      $_nimi = utf8_encode($this->rakir_row["nimi"]);
      $_nimitark = utf8_encode($this->rakir_row["nimitark"]);

      $uni_prt_val = $uni_party->addChild('val', trim($_nimi.' '.$_nimitark)); // Party's name
      $uni_prt_val->addAttribute('n', "name");

      $uni_prt_val = $uni_party->addChild('val', utf8_encode($this->rakir_row["osoite"])); // Party's name
      $uni_prt_val->addAttribute('n', "address1");

      $uni_prt_val = $uni_party->addChild('val', utf8_encode($this->rakir_row["postino"])); // Party's name
      $uni_prt_val->addAttribute('n', "zipcode");

      $uni_prt_val = $uni_party->addChild('val', utf8_encode($this->rakir_row["postitp"])); // Party's name
      $uni_prt_val->addAttribute('n', "city");

      $uni_prt_val = $uni_party->addChild('val', utf8_encode($this->rakir_row["maa"])); // Party's name
      $uni_prt_val->addAttribute('n', "country");

    }
    // L‰hetyksen tiedot
    $uni_shipment = $xml->addChild('shipment');  // Unique order number. Any contents. Mandatory. Order number is searchable in the system but not printed on shipping documents.
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

    $uni_shi_val = $uni_shipment->addChild('val', $this->pikahakuarvo()); // Defines the sender. Refers to the sndid value for sender.
    $uni_shi_val->addAttribute('n', "from");

    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->asiakasrow["tunnus"])); // Defines the receiver. Refers to rcvid value for receiver.
    $uni_shi_val->addAttribute('n', "to");

    if ($this->rakir_row['merahti'] == "" and ($this->toitarow['virallinen_selite'] == 'POF1'
       or $this->toitarow["virallinen_selite"] == "SBTLFIEXP")) {
      $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($shipment_payer)); // Defines the receiver. Refers to rcvid value for receiver.
      $uni_shi_val->addAttribute('n', "payerfreight");
    }

    if ($this->toitarow['virallinen_selite'] == 'PO2711') {

      $uni_shi_val = $uni_shipment->addChild('val', $this->pikahakuarvo()); # Juridisen l‰hett‰j‰n tunnus (sndid). Ei tulostu rahtiasiakirjaan.
      $uni_shi_val->addAttribute('n', "legalfrom");

      $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->asiakasrow["tunnus"])); # Palautusvastaanottajan tunnus (rcvid).
      $uni_shi_val->addAttribute('n', "returnto");
    }

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the legal receiver (not printed on shipping documents).
    //$uni_shi_val->addAttribute('n', "legalto");

    if (!empty($this->postirow["noutopisteen_tunnus"])) {
      //<val n="agentto">1234</val> # definaa noutopisteen tunnuksen
      $agentto_noutopiste = $uni_shipment->addChild('val', utf8_encode($this->postirow["noutopisteen_tunnus"])); // Defines the agent's ID for recipient in shipment. Used by DBSchenker PrivPak to store the agent's details, address etc. For Bring it's used to store address details to MyQuickBox machine.
      $agentto_noutopiste->addAttribute('n', "agentto");
    }

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Exporter
    //$uni_shi_val->addAttribute('n', "customsfrom");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Importer
    //$uni_shi_val->addAttribute('n', "customsto");

    //$uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->postirow["tunnus"])); # Shipment ID. UFPS only.
    //$uni_shi_val->addAttribute('n', "shpid");


    if ($this->yhtiorow['kerayserat'] == 'K') {
      $pakkausid = chr(64+$this->postirow['pakkausid']);
    }
    else {
      $pakkausid = $this->postirow['kuljetusohjeet'];
    }

    // PakkausID
    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($pakkausid, 0, 30))); // Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
    $uni_shi_val->addAttribute('n', "freetext1");

    // kollilaji
    if ($this->yhtiorow['kerayserat'] == 'K') {
      $kollilaji = $this->postirow['kollilaji']." / ".$this->kukarow['keraajanro']." / ".$this->postirow['tunnus']." / ".$this->postirow['rivimaara'];
    }
    else {
      $kollilaji = "";
    }

    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode(substr($kollilaji, 0, 30))); // Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
    $uni_shi_val->addAttribute('n', "freetext2");

    if (strlen($this->postirow["ohjausmerkki"]) > 30) {
      $ohjausmerkki1 = substr($this->postirow["ohjausmerkki"], 0, 30);
      $ohjausmerkki2 = substr($this->postirow["ohjausmerkki"], 31, 30);
    }
    else {
      $ohjausmerkki1 = $this->postirow["ohjausmerkki"];
      $ohjausmerkki2 = "";
    }

    // ohjausmerkki
    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($ohjausmerkki1)); // Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
    $uni_shi_val->addAttribute('n', "freetext3");

    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->rakir_row["viesti"])); // Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 4 lines available, freetext1-4. Max. 30 characters/line.
    $uni_shi_val->addAttribute('n', "freetext4");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Number of EUR pallets in the shipment. Requires palletregno for sender and receiver.
    //$uni_shi_val->addAttribute('n', "eurpallets");

    // l‰hett‰j‰n viite
    $reference = $this->sender_reference();

    $uni_shi_val = $uni_shipment->addChild('val', $reference); // Shipment reference. Any contents. Max. 17 characters.
    $uni_shi_val->addAttribute('n', "reference");

    if ($reference != "" and is_int($reference)) {
      $uni_shi_val = $uni_shipment->addChild('val', $reference); // Shipment reference as barcode. Max. 17 numeric characters.
      $uni_shi_val->addAttribute('n', "referencebarcode");
    }

    // vastaanottajan viite
    $reference = substr(preg_replace("/[^\x20-\xFF]/", "", trim($this->postirow["asiakkaan_tilausnumero"])), 0, 17);

    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($reference)); // Receiver's reference. Any contents. Max. 17 characters.
    $uni_shi_val->addAttribute('n', "rcvreference");

    // Vain tietyille palveluille sallitaan goodsdescription
    $_goodsdescription = array(
      'DAME',
      'DAECX',
    );

    if (in_array($this->toitarow['virallinen_selite'], $_goodsdescription)) {
      $uni_shi_val = $uni_shipment->addChild('val', utf8_encode($this->postirow['kuljetusohjeet'])); // Description of contents, only used for some carriers.
      $uni_shi_val->addAttribute('n', "goodsdescription");
    }

    //$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "sisfreetext1");

    //$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "sisfreetext2");

    //$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "sisfreetext3");

    //$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "sisfreetext4");

    //$uni_shi_val = $uni_shipment->addChild('val', "sisfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. It is printed on shipping documents. 5 lines available, sisfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "sisfreetext5");

    //$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext1"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrfreetext1");

    //$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext2"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrfreetext2");

    //$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext3"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrfreetext3");

    //$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext4"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrfreetext4");

    //$uni_shi_val = $uni_shipment->addChild('val', "cmrfreetext5"); # Free text field with any contents. Can be used for delivery instructions, for example. Only printed on CMR waybill. 5 lines available, cmrfreetext1-5. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrfreetext5");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrdocuments1");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Fields for additional documents. 2 lines available, cmrdocuments1-2. Max. 30 characters/line.
    //$uni_shi_val->addAttribute('n', "cmrdocuments2");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Specifies any special agreement. Max. 30 characters.
    //$uni_shi_val->addAttribute('n', "cmrspecialagreement");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Delivery terms. See SUP-112-Services-en.xls for valid delivery terms.
    //$uni_shi_val->addAttribute('n', "termcode");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines the location where takeover for the specified delivery term is done.
    //$uni_shi_val->addAttribute('n', "termlocation");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Defines which documents to print. Pipe characters are mandatory. Valid values: |label| = Label only, |sis| = Waybill only, |*| = None of the above
    //$uni_shi_val->addAttribute('n', "printset");

    $uni_shi_val = $uni_shipment->addChild('val', utf8_encode(date("Y-m-d"))); // Defines shipment date. Printed on shipping documents. The default value is the current date. Please note that EDI is sent on this date.
    $uni_shi_val->addAttribute('n', "shipdate");

    //$uni_shi_val = $uni_shipment->addChild('val', ""); # Customs currency unit
    //$uni_shi_val->addAttribute('n', "customsunit");

    if (trim($rahdinkuljettaja_row['neutraali']) != "") {
      $uni_service = $uni_shipment->addChild('service'); // Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.
      $uni_service->addAttribute('srvid', utf8_encode('FREEG'));
      $uni_service->addAttribute('subid', utf8_encode($this->toitarow['rahdinkuljettaja']));
    }
    else {
      $uni_service = $uni_shipment->addChild('service'); // Corresponds to carrier's service. See SUP-112-Services-en.xls for valid services.

      if (strpos($this->toitarow['virallinen_selite'], "PO2102") !== FALSE) {
        $_virallinen_selite = "PO2102";
      }
      elseif (strpos($this->toitarow['virallinen_selite'], "PO2144") !== FALSE) {
        $_virallinen_selite = "PO2144";
      }
      elseif (strpos($this->toitarow['virallinen_selite'], "MH10_") !== FALSE) {
        $_virallinen_selite = "MH10";
      }
      elseif (strpos($this->toitarow['virallinen_selite'], "MH20_") !== FALSE) {
        $_virallinen_selite = "MH20";
      }
      elseif (strpos($this->toitarow['virallinen_selite'], "MH3050_") !== FALSE) {
        $_virallinen_selite = "MH3050";
      }
      else {
        $_virallinen_selite = $this->toitarow['virallinen_selite'];
      }

      $uni_service->addAttribute('srvid', utf8_encode($_virallinen_selite));
    }

    $_DLV09 = array(
      'PO2102_09',
      'PO2144_09',
    );
    $_DLV21 = array(
      'PO2144_21',
    );
    $_DLV00 = array(
      'PO2102_00',
      'PO2144_00',
    );
    // Matkahuollon "tankomainen" lis‰palvelu
    $_MA_SPTR = array(
      'MH10_SPTR',
      'MH20_SPTR',
      'MH3050_SPTR',
    );
    // Postnord "Ennakkoilmoitus puhelimitse " lis‰palvelu
    $_PO_DRVNOT = array(
      'P83FI',
    );

    if (in_array($this->toitarow['virallinen_selite'], $_DLV09)) {
      $uni_ser_val = $uni_service->addChild('addon');
      $uni_ser_val->addAttribute('adnid', "DLV09");
    }

    if (in_array($this->toitarow['virallinen_selite'], $_DLV21)) {
      $uni_ser_val = $uni_service->addChild('addon');
      $uni_ser_val->addAttribute('adnid', "DLV21");
    }

    if (in_array($this->toitarow['virallinen_selite'], $_DLV00)) {
      $uni_ser_val = $uni_service->addChild('addon');
      $uni_ser_val->addAttribute('adnid', "DLV00");
    }

    if (in_array($this->toitarow['virallinen_selite'], $_MA_SPTR)) {
      $uni_ser_val = $uni_service->addChild('addon');
      $uni_ser_val->addAttribute('adnid', "SPTR");
    }

    if (in_array($this->toitarow['virallinen_selite'], $_PO_DRVNOT) and $this->postirow["toim_puh"] != "") {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', "DRVNOT");

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["toim_puh"]));
      $uni_add_val->addAttribute('n', 'misc');
    }

    // Postin palvelukoodit, jotka tukevat lis‰palvelua FDNGPP: "LQ Prosessilupa"
    $_sallitut_posti_lq_palvelut = array(
      'PO2102', // Express-paketti
      'PO2103', // Postipaketti
      'PO2104', // Kotipaketti
      'PO2108', // Palautus
    );

    // Postin palvelukoodit, jotka tukevat lis‰palvelua DNG: "Vaarallisten aineiden kuljetus (VAK) / LQ Kuljetus"
    $_sallitut_posti_vak_palvelut = array(
      'PO2102', // Express-paketti
      'PO2104', // Kotipaketti
      'PO2144', // Express-rahti
      'POF1',   // Rahti
    );

    // Jos meill‰ on vain LQ tuotteita tilauksella ja sopiva palvelukoodi, lis‰t‰‰n FDNGPP lis‰palvelu
    if ($this->onko_lq() and in_array($_virallinen_selite, $_sallitut_posti_lq_palvelut)) {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', "FDNGPP");

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["yhtio_nimi"]));
      $uni_add_val->addAttribute('n', 'declarant');
    }
    // Jos meill‰ on VAK tuotteita tilauksella ja sopiva palvelukoodi, lis‰t‰‰n DNG lis‰palvelu
    elseif ($this->onko_vak() and in_array($_virallinen_selite, $_sallitut_posti_vak_palvelut)) {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', "DNG");

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["yhtio_nimi"]));
      $uni_add_val->addAttribute('n', 'declarant');
    }

    if (trim($this->toitarow['lajittelupiste']) != "") {
      $uni_ser_val = $uni_service->addChild('val', $this->toitarow['lajittelupiste']); // HIT InNight mandatory.
      $uni_ser_val->addAttribute('n', "sortpos");
    }

    $uni_ser_val = $uni_service->addChild('val', "no"); // Defines if the shipment is a return shipment or not. Valid values: yes, no
    $uni_ser_val->addAttribute('n', "returnlabel");

    $uni_ser_val = $uni_service->addChild('val', "RETURN"); // Defines action when the package is undeliverable. Only for Posten Postpaket Utrikes. RETURN = Return to sender, ABANDON = Treat as abandoned in receiver's country.
    $uni_ser_val->addAttribute('n', "nondelivery");

    //$uni_booking = $uni_service->addChild('booking'); # Booking information for pick up with DBSchenker. UFPS only.

    //$uni_ser_val = $uni_booking->addChild('val', ""); # OPAL-number. Acquired from DBSchenker.
    //$uni_ser_val->addAttribute('n', "bookingid");

    //$uni_ser_val = $uni_booking->addChild('val', ""); # Booking office, numeric code. See SUP-112-Services-en.xls for valid codes.
    //$uni_ser_val->addAttribute('n', "bookingoffice");

    if ($this->rakir_row['merahti'] == "" and in_array($this->toitarow['virallinen_selite'], $allowed_rpay)) {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', 'rpay');

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($rahtisopimusnro));
      $uni_add_val->addAttribute('n', 'custno');
    }
    elseif ($this->rakir_row['merahti'] == "" and in_array($this->toitarow['virallinen_selite'], $allowed_opay)) {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', 'opay');

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($rahtisopimusnro));
      $uni_add_val->addAttribute('n', 'custno');

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["tunnus"])); // Payment reference. Used with add-on COD.
      $uni_add_val->addAttribute('n', "reference");
    }

    // n‰m‰ tukee mps palvelua
    $posti_mps = array(
      'PO2102_09',
      'PO2144_09',
      'PO2102',
      'PO2144',
      'ITKY14I',
      'IT14I',
      'PO2102_00',
      'PO2144_00',
      'PO2104',
      'PO2144_21',
    );

    $_mps = $this->toitarow["rahtikirja"] == 'rahtikirja_unifaun_ps_siirto.inc' ? true : false;

    // perille toimitettavissa Postin toimitustavoissa k‰ytet‰‰n MPS:s‰‰, jos printserver
    if ($_mps and empty($this->rakir_row["jv"]) and empty($this->mehto['jv']) and in_array($this->toitarow['virallinen_selite'], $posti_mps)) {
      $uni_addon = $uni_service->addChild('addon');
      $uni_addon->addAttribute('adnid', 'cons');

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["ytunnus"])); // MPS-l‰hetyksen kokoava tekij‰
      $uni_add_val->addAttribute('n', "reference");
    }

    if (($this->toitarow['virallinen_selite'] == "P19" or $this->toitarow['virallinen_selite'] == "P19FI") and $this->postirow["toim_puh"] != "") {
      $uni_addon = $uni_service->addChild('addon'); // Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
      $uni_addon->addAttribute('adnid', "NOTSMS");
      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["toim_puh"])); // Defines value for misctype.
      $uni_add_val->addAttribute('n', "misc");
      $uni_add_val = $uni_addon->addChild('val', "PHONE"); // Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
      $uni_add_val->addAttribute('n', "misctype");
    }
    elseif ($this->toitarow['virallinen_selite'] == "PO2103" and ($this->postirow["toim_puh"] != "" or $this->asiakasrow["email"] != "")) {
      $uni_addon = $uni_service->addChild('addon'); // Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
      $uni_addon->addAttribute('adnid', "NOT");

      if ($this->postirow["toim_puh"] != '') {
        $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->postirow["toim_puh"])); // Used to define notification mode for add-on NOT.
        $uni_add_val->addAttribute('n', "text3");
      }
      if ($this->asiakasrow["email"] != '') {
        $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->sanitizeEmails($asiakasemail))); // Used to define notification mode for add-on NOT.
        $uni_add_val->addAttribute('n', "text4");
      }
    }
    elseif ($this->toitarow['virallinen_selite'] == "P19" or $this->toitarow['virallinen_selite'] == "P19FI") {
      $uni_addon = $uni_service->addChild('addon'); // Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
      $uni_addon->addAttribute('adnid', "NOTLTR");
    }

    if ($this->rakir_row["jv"] != '' or $this->mehto['jv'] != '') {
      $uni_addon = $uni_service->addChild('addon'); // Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
      $uni_addon->addAttribute('adnid', "COD");

      $uni_par_val = $uni_addon->addChild('val', utf8_encode($this->yhtiorow["pankkiswift1"]));   // BIC number
      $uni_par_val->addAttribute('n', "bank");

      $uni_par_val = $uni_addon->addChild('val', utf8_encode($this->yhtiorow["pankkiiban1"]));   // IBAN account number
      $uni_par_val->addAttribute('n', "account");

      $uni_par_val = $uni_addon->addChild('val', "IBAN");   // Account type
      $uni_par_val->addAttribute('n', "accounttype");

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->yhteensa)); // Amount. Used only with add-on COD (Cash On Delivery).
      $uni_add_val->addAttribute('n', "amount");

      $uni_add_val = $uni_addon->addChild('val', utf8_encode($this->viite)); // Payment reference. Used with add-on COD.
      $uni_add_val->addAttribute('n', "reference");
    }

    //$uni_addon = $uni_service->addChild('addon'); # Corresponds to add-on service. See SUP-112-Services-en.xls for valid add-on services.
    //$uni_addon->addAttribute('adnid', "");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Amount. Used only with add-on COD (Cash On Delivery).
    //  $uni_add_val->addAttribute('n', "amount");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Customer number for carrier. Used primarily with RPAY (receiver pays) and OPAY (other payer).
    //  $uni_add_val->addAttribute('n', "custno");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Payment reference. Used with add-on COD.
    //  $uni_add_val->addAttribute('n', "reference");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Defines value for misctype.
    //  $uni_add_val->addAttribute('n', "misc");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Used to define notification mode for add-on NOT. Valid values: PHONE = Phone, FAX = Fax.
    //  $uni_add_val->addAttribute('n', "misctype");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Defines name of collection point for Posten add-on DLVNOT.
    //  $uni_add_val->addAttribute('n', "text1");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Defines address for collection point for Posten add-on DLVNOT.
    //  $uni_add_val->addAttribute('n', "text2");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Defines phone number for Posten add-ons PODNOT, DLVNOT and PRENOT.
    //  $uni_add_val->addAttribute('n', "text3");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Defines e-mail address for Posten add-ons PODNOT, DLVNOT and PRENOT.
    //  $uni_add_val->addAttribute('n', "text4");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Max. temperature allowed. Used with DBSchenker ColdSped.
    //  $uni_add_val->addAttribute('n', "tempmax");
    //
    //  $uni_add_val = $uni_addon->addChild('val', ""); # Min. temperature allowed. Used by DBSchenker ColdSped.
    //  $uni_add_val->addAttribute('n', "tempmin");

    // Katsotaan onko s‰hkˆinen ennakkoilmoitus k‰ytˆss‰
    $query = "SELECT *
              FROM toimitustavan_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND liitostunnus   = '{$this->toitarow['tunnus']}'
              AND laji = 'unifaun_lisapalvelu'
              AND selite = 'enot'
              AND selitetark = 'k'";
    $toimitustavan_avainsanat_res = pupe_query($query);

    if (mysql_num_rows($toimitustavan_avainsanat_res) == 1) {

      $uni_ufonline = $uni_shipment->addChild('ufonline');
      $uni_option = $uni_ufonline->addChild('option');
      $uni_option->addAttribute('optid', utf8_encode('ENOT'));

      $uni_opt_val = $uni_option->addChild('val', utf8_encode($this->yhtiorow["postittaja_email"]));
      $uni_opt_val->addAttribute('n', "from");

      $uni_opt_val = $uni_option->addChild('val', utf8_encode($this->asiakasrow["email"]));
      $uni_opt_val->addAttribute('n', "to");

      $uni_opt_val = $uni_option->addChild('val', "");
      $uni_opt_val->addAttribute('n', "cc");

      $uni_opt_val = $uni_option->addChild('val', "");
      $uni_opt_val->addAttribute('n', "bcc");

      // Haetaan avainsanoista ennakkoilmoituksen vakioviesti

      if ($message_avainsanasta = t_avainsana("UNIFAUN_MESSAGE", '', '', '', '', "selitetark")) {
        $message = $message_avainsanasta;

        $uni_opt_val = $uni_option->addChild('val', utf8_encode($message));
        $uni_opt_val->addAttribute('n', "message");
      }

      /*
      $uni_opt_val = $uni_option->addChild('val', "messagea t‰h‰n");
      $uni_opt_val->addAttribute('n', "message");

      $uni_opt_val = $uni_option->addChild('val', "template name");
      $uni_opt_val->addAttribute('n', "mailtemplate");
      */
    }

    $this->xml = $xml;
  }

  public function setContainerRow($pakkaustiedot) {

    // $uni_parcel = $uni_shipment->addChild('container'); # Parcel information can be supplied in various ways. See p. 7.
    $uni_parcel = $this->xml->shipment->addChild('container'); // Parcel information can be supplied in various ways. See p. 7.
    $uni_parcel->addAttribute('type', "parcel");

    $uni_par_val = $uni_parcel->addChild('val', round(utf8_encode($pakkaustiedot['maara']))); // Number of parcels, pyˆristet‰‰n varmuuden vuoksi l‰himp‰‰n kokonaislukuun
    $uni_par_val->addAttribute('n', "copies");

    //$uni_par_val = $uni_parcel->addChild('val', ""); # Parcel ID. Used only for custom parcel ID. UFPS only. Cntid is to be incremented according to number of parcels.
    //$uni_par_val->addAttribute('n', "cntid1");

    //$uni_par_val = $uni_parcel->addChild('val', ""); # Goods marking
    //$uni_par_val->addAttribute('n', "marking");

    $pakkauskoodi = $pakkaustiedot['pakkauskoodi'] != '' ? $pakkaustiedot['pakkauskoodi'] : "PC";

    if ($pakkauskoodi == 'PC' and $this->toitarow['rahdinkuljettaja'] == 'TP') {
      $pakkauskoodi = "KLI";
    }

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkauskoodi)); // Package code. See SUP-112-Services-en.xls for valid package codes.
    $uni_par_val->addAttribute('n', "packagecode");

    $pakkaustiedot['paino'] = $pakkaustiedot['paino'] < 0.1 ? 0.1 : $pakkaustiedot['paino'];

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['paino'])); // Weight
    $uni_par_val->addAttribute('n', "weight");

    $query = "SELECT koodi, pakkauksen_sarman_minimimitta
              FROM rahdinkuljettajat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND koodi   = '{$this->toitarow['rahdinkuljettaja']}'";
    $rahdinkuljettajat_result = pupe_query($query);
    $rahdinkuljettaja_rivi = mysql_fetch_assoc($rahdinkuljettajat_result);

    if (empty($rahdinkuljettaja_rivi['pakkauksen_sarman_minimimitta'])) {
      $pakkauksen_sarman_minimimitta = 0.25;
    }
    else {
      $pakkauksen_sarman_minimimitta = $rahdinkuljettaja_rivi['pakkauksen_sarman_minimimitta'];
    }

    $pakkaustiedot['syvyys'] = $pakkaustiedot['syvyys'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : $pakkaustiedot['syvyys'];
    $pakkaustiedot['leveys'] = $pakkaustiedot['leveys'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : $pakkaustiedot['leveys'];

    /*
    Otamme pakkauksen kaksi mittaa (pohjan mitat) syvyys ja leveys, korkeuden laskemme tilavuudesta jakamalla tilavuuden pohjan alalla.
    Teoreettisesti ajatelle n‰in saisimme tarkimmin viedyksi rahtitietoihin kollin mitat.
    Pohjan alahan ei muutu, mutta pakkausta puukotetaan matalammaksi (pahvilaatikon reunoja leikataan/viikataan matalammaksi)
    tai lavapakkausta rakennetaan alhaalta ylˆsp‰in niin korkealle kuin tavaraa on laittaa.
    */
    if ($pakkaustiedot['kuutiot'] > 0) {
      $pakkaustiedot['korkeus'] = $pakkaustiedot['kuutiot'] / ($pakkaustiedot['syvyys'] * $pakkaustiedot['leveys']);
      $pakkaustiedot['korkeus'] = $pakkaustiedot['korkeus'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : round($pakkaustiedot['korkeus'], 2);
    }
    else {
      $pakkaustiedot['korkeus'] = $pakkaustiedot['korkeus'] < $pakkauksen_sarman_minimimitta ? $pakkauksen_sarman_minimimitta : $pakkaustiedot['korkeus'];
      $pakkaustiedot['kuutiot'] = $pakkaustiedot['korkeus'] * $pakkaustiedot['leveys'] * $pakkaustiedot['syvyys'];
    }

    $pakkaustiedot['kuutiot'] = $pakkaustiedot['kuutiot'] < 0.01 ? 0.01 : $pakkaustiedot['kuutiot'];

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['kuutiot'])); // Volume
    $uni_par_val->addAttribute('n', "volume");

    if ($pakkaustiedot['lavametri'] > 0) {
      $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['lavametri'])); // Loadmeter. Can only be specified for entire shipment.
      $uni_par_val->addAttribute('n', "area");
    }

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['syvyys'])); // Length
    $uni_par_val->addAttribute('n', "length");

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['leveys'])); // Width
    $uni_par_val->addAttribute('n', "width");

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['korkeus'])); // Height
    $uni_par_val->addAttribute('n', "height");

    //$uni_par_val = $uni_parcel->addChild('val', ""); # Item number
    //$uni_par_val->addAttribute('n', "itemno");

    $uni_par_val = $uni_parcel->addChild('val', utf8_encode($pakkaustiedot['pakkauskuvaus'])); // Contents
    $uni_par_val->addAttribute('n', "contents");

    if ($this->toitarow['rahdinkuljettaja'] == 'POSTI' and (count($pakkaustiedot['vakkoodi']) > 0 or $pakkaustiedot['pakkauskuvaus'] == 'MUU KOLLI')) {
      /*
      TEHDƒƒN TƒMƒ JOS VAK TAI MUU KOLLI
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
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['tuoteno'])); // article number
          $uni_par_val->addAttribute('n', "articleno");
        }

        if (isset($vak['tuotenimitys']) and $vak['tuotenimitys'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak["tuotenimitys"])); // UN-number for ADR. Supplied as a 4 digit code.
          $uni_par_val->addAttribute('n', "name");
        }

        if (isset($vak['kpl']) and $vak['kpl'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['kpl'])); // UN-number for ADR. Supplied as a 4 digit code.
          $uni_par_val->addAttribute('n', "count");
        }

        // VAK tiedot annetaan aina l‰hetyskohtaisesti. Jos on useampi kolli, niin tiedot menee per kolli l‰hetykseen,
        // joten painot pit‰‰ jakaa kollien m‰‰r‰ll‰, jottei painot moninkertaistu
        if (isset($pakkaustiedot['rahtikirjan_kollit_yhteensa']) and $pakkaustiedot['rahtikirjan_kollit_yhteensa'] > 1) {
          $_jakaja = $pakkaustiedot['rahtikirjan_kollit_yhteensa'];
        }
        else {
          $_jakaja = 1;
        }

        if (isset($vak['kpl_paino']) and $vak['kpl_paino'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['kpl_paino'] / $_jakaja)); // UN-number for ADR. Supplied as a 4 digit code.
          $uni_par_val->addAttribute('n', "weight");
        }

        if (isset($vak['yk_nro']) and $vak['yk_nro'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['yk_nro'])); // UN-number for ADR. Supplied as a 4 digit code.
          $uni_par_val->addAttribute('n', "dnguncode");
        }

        if (isset($vak['lipukkeet']) and $vak['lipukkeet'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['lipukkeet'])); // Label number for ADR
          $uni_par_val->addAttribute('n', "dnghzcode");
        }

        if (!empty($vak['pakkausryhma'])) {
          $_pakkausryhma = strtoupper(trim($vak['pakkausryhma']));

          // Voidaan antaa numeerinen arvo, joka k‰‰nnet‰‰n roomalaisiksi
          if (is_numeric($_pakkausryhma)) {
            $_pakkausryhma = (int) $_pakkausryhma;
            $_pakkausryhma = ($_pakkausryhma < 1 or $_pakkausryhma > 3) ? 0 : $_pakkausryhma;
            $_pakkausryhma = str_repeat("I", $_pakkausryhma);
          }

          // Packaging group/ADR-class. Supplied as I, II or III.
          if (in_array($_pakkausryhma, array('I', 'II', 'III'))) {
            $uni_par_val = $uni_article->addChild('val', $_pakkausryhma);
            $uni_par_val->addAttribute('n', "dngpkcode");
          }
        }

        if (isset($vak['luokka']) and $vak['luokka'] != '') {
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['luokka'])); // ADR-class
          $uni_par_val->addAttribute('n', "dngadrclass");
        }

        if (isset($vak['nimi_ja_kuvaus']) and $vak['nimi_ja_kuvaus'] != '') {

          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak["nimi_ja_kuvaus"])); // Official transport name for item regarding ADR
          $uni_par_val->addAttribute('n', "dngdescr");
        }

        //$uni_par_val = $uni_article->addChild('val', ""); # Defines if the contents contaminate the marine environment, ADR only. Valid values: 1 = Toxic and 2 = Non-toxic for the marine environment
        //$uni_par_val->addAttribute('n', "dngmpcode");

        if (isset($vak['limited_qty']) and $vak['limited_qty'] != '' and $this->onko_lq()) {
          $uni_par_val = $uni_article->addChild('val', $vak['limited_qty']); // Note for ADR goods
          $uni_par_val->addAttribute('n', "dngnote");

          // Defines if the parcel row contains Limited Quantities of dangerous goods. Valid values: YES/NO.
          $uni_par_val = $uni_article->addChild('val', 'YES');
          $uni_par_val->addAttribute('n', "dnglimitedquantities");
        }

        if (isset($vak['paino']) and $vak['paino'] != '') {

          $vak['paino'] = $vak['paino'] / $_jakaja;
          $vak['paino'] = $vak['paino'] < 0.01 ? 0.01 : $vak['paino']; //nettopaino vakilla pit‰‰ olla v‰hint‰‰n 0.01

          // Net weight for ADR goods class I (usually explosive contents). Always mandatory for DBSchenker, regardless of class. Defined in kg.
          $uni_par_val = $uni_article->addChild('val', utf8_encode($vak['paino']));
          $uni_par_val->addAttribute('n', "dngnetweight");
        }

        if (isset($vak['kuljetus_kategoria']) and $vak['kuljetus_kategoria'] != '') {

          // Tunnel restriction code, lˆytyy sulkujen sis‰lt‰ kuljetus_kategoria kent‰st‰
          preg_match("/\((.*)\)/", $vak['kuljetus_kategoria'], $dngtrcode);

          $uni_par_val = $uni_article->addChild('val', utf8_encode($dngtrcode[1]));
          $uni_par_val->addAttribute('n', "dngtrcode");
        }
      }
    }
  }

  private function pikahakuarvo() {
    /**
     * tehd‰‰n yhtiˆn parametri "unifaun pikahakuarvo"
     * voidaan silloin j‰tt‰‰ nimitiedot tyhjiksi
     * lis‰ksi pikahakuarvo lˆytyy hiukan alempaa shipment kohdan alta (from)
     *
     * Tarkistetaan t‰ss‰ kohtaa otetaanko pikahakuarvo yhtiorow tunnuksesta,
     * vaiko yhtion_toimipaikat.pikahakuarvo -kent‰st‰ (eli toimipaikkakohtaisesti),
     * vaiko varastopaikat.pikahakuarvo -kent‰st‰ (eli varastokohtaisesti)
     */

    // memoization, ei tehd‰ turhaa toista kertaa
    if (!is_null($this->pikahaku_tunnus)) {
      return $this->pikahaku_tunnus;
    }

    // oletus on yhtiˆn tunnus
    $this->pikahaku_tunnus = $this->yhtiorow['tunnus'];

    // tarkistetaan onko meill‰ pikahakuarvo varaston takana
    $query = "SELECT pikahakuarvo
              FROM varastopaikat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tunnus = '{$this->postirow['varasto']}'";
    $varastopaikat_res = pupe_query($query);
    $varastopaikat_row = mysql_fetch_assoc($varastopaikat_res);

    // k‰ytet‰‰n varaston pikahakuarvoa
    if (!empty($varastopaikat_row['pikahakuarvo'])) {
      $this->pikahaku_tunnus = $varastopaikat_row['pikahakuarvo'];

      return $this->pikahaku_tunnus;
    }

    // palautetaan oletus (yhtiˆ tunnus), jos ei ole toimipaikkaa
    if (empty($this->postirow['yhtio_toimipaikka'])) {
      return $this->pikahaku_tunnus;
    }

    // tarkistetaan onko meill‰ pikahakuarvo toimipaikan takana
    $query = "SELECT pikahakuarvo
              FROM yhtion_toimipaikat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tunnus = '{$this->postirow['yhtio_toimipaikka']}'";
    $toimipaikka_res = pupe_query($query);
    $toimipaikka_row = mysql_fetch_assoc($toimipaikka_res);

    // k‰ytet‰‰n toimipaikan pikahakuarvoa
    if (!empty($toimipaikka_row['pikahakuarvo'])) {
      $this->pikahaku_tunnus = $toimipaikka_row['pikahakuarvo'];

      return $this->pikahaku_tunnus;
    }

    // palautetaan oletus (yhtiˆ tunnus)
    return $this->pikahaku_tunnus;
  }

  private function sender_reference() {
    $key = unifaun_sender_reference();
    $value = $this->postirow[$key];
    $value = trim($value);
    $value = preg_replace("/[^\x20-\xFF]/", "", $value);
    $value = substr($value, 0, 17);
    $value = utf8_encode($value);

    return $value;
  }
}
