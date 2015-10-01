<?php

/*
 * Siirret‰‰n asiakastiedot M3:seen
*/

//* T‰m‰ skripti k‰ytt‰‰ slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st‰
if (php_sapi_name() != 'cli') {
  die ("T‰t‰ scripti‰ voi ajaa vain komentorivilt‰!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhtiˆ on annettava!!");
}

$tallenna_polku = "";

if (!empty($argv[2])) {
  $tallenna_polku = realpath($argv[2]);
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(__FILE__)));

require 'inc/connect.inc';
require 'inc/functions.inc';

// Logitetaan ajo
cron_log();

// Yhtiˆ
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/customers_{$yhtio}_".date("Y-m-d_His").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep‰onnistui: $filepath\n");
}

// Otsikkotieto
$header  = "OKCONO;";
$header .= "OKDIVI;";
$header .= "OKSTAT;";
$header .= "OKCUNO;";
$header .= "OKCUCL;";
$header .= "OKCUTP;";
$header .= "OKALCU;";
$header .= "OKCUNM;";
$header .= "OKCUA1;";
$header .= "OKCUA2;";
$header .= "OKCUA3;";
$header .= "OKCUA4;";
$header .= "OKADID;";
$header .= "OKADBO;";
$header .= "OKPHNO;";
$header .= "OKPHN2;";
$header .= "OKTFNO;";
$header .= "OKCORG;";
$header .= "OKCOR2;";
$header .= "OKYREF;";
$header .= "OKYRE2;";
$header .= "OKOREF;";
$header .= "OKCUSU;";
$header .= "OKEXCD;";
$header .= "OKTEPY;";
$header .= "OKOT75;";
$header .= "OKTECD;";
$header .= "OKTEDL;";
$header .= "OKMODL;";
$header .= "OKSMCD;";
$header .= "OKRESP;";
$header .= "OKRONO;";
$header .= "OKDIPC;";
$header .= "OKDISY;";
$header .= "OKDIGC;";
$header .= "OKVTCD;";
$header .= "OKTXAP;";
$header .= "OKCUCD;";
$header .= "OKCRTP;";
$header .= "OKPLTB;";
$header .= "OKPRVG;";
$header .= "OKBREC;";
$header .= "OKDTFM;";
$header .= "OKEDIT;";
$header .= "OKORTP;";
$header .= "OKWHLO;";
$header .= "OKPRIC;";
$header .= "OKSDST;";
$header .= "OKCSCD;";
$header .= "OKAGNT;";
$header .= "OKAGN2;";
$header .= "OKAGN3;";
$header .= "OKAGN4;";
$header .= "OKAGN5;";
$header .= "OKAGN6;";
$header .= "OKAGN7;";
$header .= "OKINSU;";
$header .= "OKPYNO;";
$header .= "OKCUST;";
$header .= "OKTEPA;";
$header .= "OKLHCD;";
$header .= "OKCRLM;";
$header .= "OKCRL2;";
$header .= "OKCRL3;";
$header .= "OKBLCD;";
$header .= "OKPRIO;";
$header .= "OKTBLG;";
$header .= "OKTOIN;";
$header .= "OKTDIN;";
$header .= "OKLIDT;";
$header .= "OKININ;";
$header .= "OKACRF;";
$header .= "OKAICD;";
$header .= "OKBOCD;";
$header .= "OKFRE1;";
$header .= "OKFRE2;";
$header .= "OKBGRP;";
$header .= "OKDOGR;";
$header .= "OKBLII;";
$header .= "OKIICT;";
$header .= "OKCLCD;";
$header .= "OKBLPR;";
$header .= "OKRMCT;";
$header .= "OKBLAC;";
$header .= "OKADCA;";
$header .= "OKPYDI;";
$header .= "OKPONO;";
$header .= "OKAUGI;";
$header .= "OKAGPA;";
$header .= "OKCCUS;";
$header .= "OKWAYB;";
$header .= "OKADTG;";
$header .= "OKODTG;";
$header .= "OKMAIL;";
$header .= "OKENHD;";
$header .= "OKEURI;";
$header .= "OKEDIP;";
$header .= "OKCFC1;";
$header .= "OKCFC2;";
$header .= "OKCFC3;";
$header .= "OKCFC4;";
$header .= "OKCFC5;";
$header .= "OKCFC6;";
$header .= "OKCFC7;";
$header .= "OKCFC8;";
$header .= "OKCFC9;";
$header .= "OKCFC0;";
$header .= "OKLSID;";
$header .= "OKLSAD;";
$header .= "OKMEAL;";
$header .= "OKVRNO;";
$header .= "OKAGCH;";
$header .= "OKAGCT;";
$header .= "OKDUCD;";
$header .= "OKUSR1;";
$header .= "OKUSR2;";
$header .= "OKUSR3;";
$header .= "OKDTE1;";
$header .= "OKDTE2;";
$header .= "OKDTE3;";
$header .= "OKCDRC;";
$header .= "OKINCO;";
$header .= "OKINSN;";
$header .= "OKCUIC;";
$header .= "OKINSS;";
$header .= "OKNALI;";
$header .= "OKDTL1;";
$header .= "OKUSL1;";
$header .= "OKINLI;";
$header .= "OKDTL2;";
$header .= "OKUSL2;";
$header .= "OKVRCD;";
$header .= "OKEDES;";
$header .= "OKROUT;";
$header .= "OKRODN;";
$header .= "OKULZO;";
$header .= "OKECLC;";
$header .= "OKECF1;";
$header .= "OKECF2;";
$header .= "OKECF3;";
$header .= "OKECF4;";
$header .= "OKECF5;";
$header .= "OKPYCD;";
$header .= "OKGRPY;";
$header .= "OKTXID;";
$header .= "OKSERC;";
$header .= "OKSRES;";
$header .= "OKTECH;";
$header .= "OKTRTI;";
$header .= "OKLZON;";
$header .= "OKDIST;";
$header .= "OKTVCD;";
$header .= "OKSOTP;";
$header .= "OKMTIC;";
$header .= "OKPWMT;";
$header .= "OKBPCD;";
$header .= "OKBPEX;";
$header .= "OKACHK;";
$header .= "OKTINC;";
$header .= "OKBUSE;";
$header .= "OKTCEX;";
$header .= "OKPYOP;";
$header .= "OKALWT;";
$header .= "OKPOPN;";
$header .= "OKSOOP;";
$header .= "OKPRS1;";
$header .= "OKPRS2;";
$header .= "OKPRS3;";
$header .= "OKPRS4;";
$header .= "OKPRS5;";
$header .= "OKDMSO;";
$header .= "OKLSOI;";
$header .= "OKODUD;";
$header .= "OKODUE;";
$header .= "OKEALO;";
$header .= "OKECAR;";
$header .= "OKGEOC;";
$header .= "OKTECN;";
$header .= "OKTEEC;";
$header .= "OKAGPY;";
$header .= "OKAGCP;";
$header .= "OKAGAC;";
$header .= "OKAGBP;";
$header .= "OKACLB;";
$header .= "OKAACB;";
$header .= "OKAGPN;";
$header .= "OKAGBG;";
$header .= "OKAGPG;";
$header .= "OKAGCA;";
$header .= "OKAGTD;";
$header .= "OKAGTN;";
$header .= "OKINRC;";
$header .= "OKCESA;";
$header .= "OKCHSY;";
$header .= "OKTAXC;";
$header .= "OKHAFE;";
$header .= "OKOT89;";
$header .= "OKPRDL;";
$header .= "OKMCON;";
$header .= "OKRAN1;";
$header .= "OKRAN2;";
$header .= "OKRAN3;";
$header .= "OKRAN4;";
$header .= "OKQUCK;";
$header .= "OKIVGP;";
$header .= "OKACEI;";
$header .= "OKVDLA;";
$header .= "OKFACI;";
$header .= "OKDTID;";
$header .= "OKDESV;";
$header .= "OKCHCL;";
$header .= "OKMCOS;";
$header .= "OKSPLM;";
$header .= "OKEXPT;";
$header .= "OKATPR;";
$header .= "OKACGR;";
$header .= "OKSTMS;";
$header .= "OKSTMR;";
$header .= "OKBCKO;";
$header .= "OKPADL;";
$header .= "OKTOWN;";
$header .= "OKADVI;";
$header .= "OKESAL;";
$header .= "OKRGDT;";
$header .= "OKRGTM;";
$header .= "OKLMDT;";
$header .= "OKCHNO;";
$header .= "OKCHID;";
$header .= "OKLMTS;";
$header .= "OKSCED";
$header .= "\n";

fwrite($fp, $header);

// Haetaan asiakkaat
$query = "SELECT asiakas.*,
          if(asiakas.gsm != '', asiakas.gsm,
            if(asiakas.puhelin != '', asiakas.puhelin, '')) AS puhelin,
          kustannuspaikka.nimi AS kustannuspaikka
          FROM asiakas
          LEFT JOIN kustannuspaikka ON (kustannuspaikka.yhtio = asiakas.yhtio
            AND kustannuspaikka.tunnus = asiakas.kustannuspaikka)
          WHERE asiakas.yhtio          = '{$yhtio}'
          AND asiakas.laji             NOT IN ('P','R')
          AND asiakas.myynninseuranta  = ''
          ORDER BY asiakas.tunnus";
$res = pupe_query($query);

// Kerrotaan montako rivi‰ k‰sitell‰‰n
$rows = mysql_num_rows($res);

echo "Asiakasrivej‰ {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  // OKCONO
  // Company
  // 1
  $rivi = "1;";

  // OKDIVI
  // Division
  //
  $rivi .= ";";

  // OKSTAT
  // Status
  // 20
  $rivi .= ";";

  // OKCUNO
  // Customer
  // 12345
  $rivi .= "{$row['asiakasnro']};";

  // OKCUCL
  // Customer group
  // FI
  $rivi .= ";";

  // OKCUTP
  // Customer type
  // 0
  $rivi .= ";";

  // OKALCU
  // Search key
  // FIFIOY
  $rivi .= ";";

  // OKCUNM
  // Name
  // Firma Oy
  $rivi .= trim(pupesoft_csvstring($row['nimi']." ".$row['nimitark'])).";";

  // OKCUA1
  // Address line 1
  // Firma Oy
  $rivi .= trim(pupesoft_csvstring($row['nimi']." ".$row['nimitark'])).";";

  // OKCUA2
  // Address line 2
  // Abc-katu 10-12
  $rivi .= pupesoft_csvstring($row['osoite']).";";

  // OKCUA3
  // Address line 3
  //
  $rivi .= ";";

  // OKCUA4
  // Address line 4
  // 10400 HELSINKI
  $rivi .= pupesoft_csvstring($row['postino']." ".$row['postitp']).";";

  // OKADID
  // Address number
  //
  $rivi .= ";";

  // OKADBO
  // Separate invoices
  // 1
  $rivi .= ";";

  // OKPHNO
  // Telephone number 1
  // +358 123456
  $rivi .= pupesoft_csvstring($row['puhelin']).";";

  // OKPHN2
  // Telephone number 2
  // +358 123456
  $rivi .= pupesoft_csvstring($row['tyopuhelin']).";";

  // OKTFNO
  // Facsimile transmission number
  //
  $rivi .= pupesoft_csvstring($row['fax']).";";

  // OKCORG
  // Organization number 1
  //
  $rivi .= pupesoft_csvstring($row['ytunnus']).";";

  // OKCOR2
  // Organization number 2
  //
  $rivi .= pupesoft_csvstring($row['ovttunnus']).";";

  // OKYREF
  // Your reference 1
  // Pekka
  $rivi .= pupesoft_csvstring($row['tilaus_viesti']).";";

  // OKYRE2
  // Your reference 2
  //
  $rivi .= pupesoft_csvstring($row['sisviesti1']).";";

  // OKOREF
  // Our reference
  //
  $rivi .= ";";

  // OKCUSU
  // Our supplier number at customer
  //
  $rivi .= ";";

  // OKEXCD
  // Service charge
  //
  $rivi .= ";";

  $query = "SELECT *
            FROM maksuehto
            WHERE tunnus = $row[maksuehto]";
  $masres = pupe_query($query);
  $masrow = mysql_fetch_assoc($masres);

  // OKTEPY
  // Payment terms
  // 030
  $rivi .= "{$masrow['rel_pvm']};";

  // OKOT75
  // Self invoicing
  // 0
  $rivi .= ";";

  // OKTECD
  // Cash discount term
  //
  $rivi .= "{$masrow['kassa_relpvm']};";

  // OKTEDL
  // Delivery terms
  // CIF
  $rivi .= substr($row['toimitusehto'], 0, 3).";";

  // OKMODL
  // Delivery method
  // F25
  $rivi .= pupesoft_csvstring($row['toimitustapa']).";";

  // OKSMCD
  // Salesperson
  // F100
  $rivi .= ";";

  // OKRESP
  // Responsible
  // username
  $rivi .= ";";

  // OKRONO
  // Run
  //
  $rivi .= ";";

  // OKDIPC
  // Discount
  // 0
  $rivi .= ";";

  // OKDISY
  // Discount model
  // FSTD
  $rivi .= ";";

  // OKDIGC
  // Discount group - customer
  //
  $rivi .= ";";

  // OKVTCD
  // VAT code
  // 1
  $rivi .= ";";

  // OKTXAP
  // Tax applicable
  // 1
  if ($row['alv'] > 0) {
    $rivi .= "1;";
  }
  else {
    $rivi .= "0;";
  }

  // OKCUCD
  // Currency
  // EUR
  $rivi .= pupesoft_csvstring($row['valkoodi']).";";

  // OKCRTP
  // Exchange rate type
  // 1
  $rivi .= ";";

  // OKPLTB
  // Price list table
  // 123PRL
  $rivi .= ";";

  // OKPRVG
  // Commission group
  //
  $rivi .= ";";

  // OKBREC
  // Recipient agreement type 9 0 bonus
  //
  $rivi .= ";";

  // OKDTFM
  // Date format
  // YMD
  $rivi .= ";";

  // OKEDIT
  // Date editing
  // .
  $rivi .= ";";

  // OKORTP
  // Customer order type
  // F01
  $rivi .= ";";

  // OKWHLO
  // Warehouse
  // 123
  $rivi .= ";";

  // OKPRIC
  // Price printout
  // 1
  $rivi .= ";";

  // OKSDST
  // District
  // FI
  $rivi .= pupesoft_csvstring($row['maa']).";";

  // OKCSCD
  // Country
  // FI
  $rivi .= pupesoft_csvstring($row['maa']).";";

  // OKAGNT
  // Recipient agreement type 1 commission
  //
  $rivi .= ";";

  // OKAGN2
  // Recipient agreement type 2 commission
  //
  $rivi .= ";";

  // OKAGN3
  // Recipient agreement type 3 commission
  //
  $rivi .= ";";

  // OKAGN4
  // Recipient agreement type 4 commission
  //
  $rivi .= ";";

  // OKAGN5
  // Recipient agreement type 5 commission
  //
  $rivi .= ";";

  // OKAGN6
  // Recipient agreement type 6 commission
  //
  $rivi .= ";";

  // OKAGN7
  // Recipient agreement type 7 commission
  //
  $rivi .= ";";

  // OKINSU
  // Insurance agent
  //
  $rivi .= ";";

  // OKPYNO
  // Payer
  //
  $rivi .= ";";

  // OKCUST
  // Statistics customer
  //
  $rivi .= ";";

  // OKTEPA
  // Packaging terms
  // 10
  $rivi .= ";";

  // OKLHCD
  // Language
  // FI
  $rivi .= pupesoft_csvstring($row['kieli']).";";

  // OKCRLM
  // Credit limit 1
  // 0
  $rivi .= pupesoft_csvstring($row['luottoraja']).";";

  // OKCRL2
  // Credit limit 2
  // 0
  $rivi .= ";";

  // OKCRL3
  // Credit limit 3
  // 0
  $rivi .= ";";

  // OKBLCD
  // Customer stop
  // 0
  $rivi .= ";";

  // OKPRIO
  // Priority
  // 5
  $rivi .= ";";

  // OKTBLG
  // Order value not invoiced
  // 0
  $rivi .= ";";

  // OKTOIN
  // Outstanding invoice amount
  // 0
  $rivi .= ";";

  // OKTDIN
  // Overdue invoice amount
  // 0
  $rivi .= ";";

  // OKLIDT
  // Last invoice date
  // 0
  $rivi .= ";";

  // OKININ
  // End date - invoicing range
  // 0
  $rivi .= ";";

  // OKACRF
  // User-defined accounting control object
  //
  $rivi .= ";";

  // OKAICD
  // Summary invoice
  // 2
  $rivi .= ";";

  // OKBOCD
  // Backorder permitted
  // 0
  if ($row['jtkielto'] != "") {
    $rivi .= "1;";
  }
  else {
    $rivi .= "0;";
  }

  // OKFRE1
  // Statistics identity 1 customer
  //
  $rivi .= ";";

  // OKFRE2
  // Statistics identity 2 customer
  // NA
  $rivi .= ";";

  // OKBGRP
  // Bonus group
  //
  $rivi .= ";";

  // OKDOGR
  // Customer document group
  //
  $rivi .= ";";

  // OKBLII
  // Interest invoicing
  // 0
  $rivi .= ";";

  // OKIICT
  // Interest rule
  //
  $rivi .= ";";

  // OKCLCD
  // Collection
  // 0
  $rivi .= ";";

  // OKBLPR
  // Payment reminder code
  // 0
  $rivi .= ";";

  // OKRMCT
  // Payment reminder rule
  //
  $rivi .= ";";

  // OKBLAC
  // Advice code
  // 0
  $rivi .= ";";

  // OKADCA
  // Advice rule
  //
  $rivi .= ";";

  // OKPYDI
  // Payment instruction
  //
  $rivi .= ";";

  // OKPONO
  // Postal code
  // 10400
  $rivi .= pupesoft_csvstring($row['postino']).";";

  // OKAUGI
  // Auto giro
  // 0
  $rivi .= ";";

  // OKAGPA
  // Auto giro payer
  // 0
  $rivi .= ";";

  // OKCCUS
  // Company group customer number
  //
  $rivi .= ";";

  // OKWAYB
  // Waybill
  // 0
  $rivi .= ";";

  // OKADTG
  // Address tag
  // 0
  $rivi .= ";";

  // OKODTG
  // Odette-tag
  // 0
  $rivi .= ";";

  // OKMAIL
  // Mail type
  // 0
  $rivi .= ";";

  // OKENHD
  // Document
  // 0
  $rivi .= ";";

  // OKEURI
  // EUR1 certificate
  // 0
  $rivi .= ";";

  // OKEDIP
  // EDI-package note
  // 0
  $rivi .= ";";

  // OKCFC1
  // User-defined field 1 - customer
  //
  $rivi .= ";";

  // OKCFC2
  // User-defined field 2 - customer
  // 0
  $rivi .= ";";

  // OKCFC3
  // User-defined field 3 - customer
  //
  $rivi .= ";";

  // OKCFC4
  // User-defined field 4 - customer
  //
  $rivi .= ";";

  // OKCFC5
  // User-defined field 5 - customer
  // N
  $rivi .= ";";

  // OKCFC6
  // User-defined field 6 - customer
  //
  $rivi .= ";";

  // OKCFC7
  // User-defined field 7 - customer
  // 0
  $rivi .= ";";

  // OKCFC8
  // User-defined field 8 - customer
  //
  $rivi .= ";";

  // OKCFC9
  // User-defined field 9 - customer
  //
  $rivi .= ";";

  // OKCFC0
  // User-defined field 10 - customer
  //
  $rivi .= ";";

  // OKLSID
  // User
  //
  $rivi .= ";";

  // OKLSAD
  // Address
  //
  $rivi .= ";";

  // OKMEAL
  // Valid media
  // 1
  $rivi .= ";";

  // OKVRNO
  // VAT registration number
  //
  $rivi .= pupesoft_csvstring($row['ytunnus']).";";

  // OKAGCH
  // Agreement check - order header
  // 0
  $rivi .= ";";

  // OKAGCT
  // Agreement check - order lines
  // 0
  $rivi .= ";";

  // OKDUCD
  // Due date base
  // 1
  $rivi .= ";";

  // OKUSR1
  // Credit limit 1 changed by
  // username
  $rivi .= ";";

  // OKUSR2
  // Credit limit 2 changed by
  // username
  $rivi .= ";";

  // OKUSR3
  // Credit limit 3 changed by
  // username
  $rivi .= ";";

  // OKDTE1
  // Change date - credit limit 1
  // 20150813
  $rivi .= ";";

  // OKDTE2
  // Change date - credit limit 2
  // 20150813
  $rivi .= ";";

  // OKDTE3
  // Change date - credit limit 3
  // 20150813
  $rivi .= ";";

  // OKCDRC
  // Credit department reference
  //
  $rivi .= ";";

  // OKINCO
  // Insurance company
  //
  $rivi .= ";";

  // OKINSN
  // Insurance number
  //
  $rivi .= ";";

  // OKCUIC
  // Customer number at the insurance company
  //
  $rivi .= ";";

  // OKINSS
  // Insurance status
  // 0
  $rivi .= ";";

  // OKNALI
  // Unapproved limit
  // 0
  $rivi .= ";";

  // OKDTL1
  // Change date not approved limit
  // 0
  $rivi .= ";";

  // OKUSL1
  // Changed by
  //
  $rivi .= ";";

  // OKINLI
  // Insurance limit
  // 0
  $rivi .= ";";

  // OKDTL2
  // Change date insurance limit
  // 0
  $rivi .= ";";

  // OKUSL2
  // Changed by
  //
  $rivi .= ";";

  // OKVRCD
  // Business type - trade statistics (TST)
  //
  $rivi .= ";";

  // OKEDES
  // Place
  // FI
  $rivi .= ";";

  // OKROUT
  // Route
  //
  $rivi .= ";";

  // OKRODN
  // Route departure
  // 0
  $rivi .= ";";

  // OKULZO
  // Unloading zone
  //
  $rivi .= ";";

  // OKECLC
  // Labor code - trade statistics (TST)
  // 1
  $rivi .= ";";

  // OKECF1
  // User-defined TST field 1
  //
  $rivi .= ";";

  // OKECF2
  // User-defined TST field 2
  //
  $rivi .= ";";

  // OKECF3
  // User-defined TST field 3
  //
  $rivi .= ";";

  // OKECF4
  // User-defined TST field  4
  //
  $rivi .= ";";

  // OKECF5
  // User-defined TST field 5
  //
  $rivi .= ";";

  // OKPYCD
  // Payment method - accounts receivable
  // 480
  $rivi .= ";";

  // OKGRPY
  // Group invoice
  // 0
  $rivi .= ";";

  // OKTXID
  // Text identity
  // 0
  $rivi .= ";";

  // OKSERC
  // Service code
  // 0
  $rivi .= ";";

  // OKSRES
  // Service manager
  //
  $rivi .= ";";

  // OKTECH
  // Technician
  //
  $rivi .= ";";

  // OKTRTI
  // Planned travel time
  // 0
  $rivi .= ";";

  // OKLZON
  // Service zone
  // 0
  $rivi .= ";";

  // OKDIST
  // Number of kilometer
  // 0
  $rivi .= ";";

  // OKTVCD
  // Travel type
  //
  $rivi .= ";";

  // OKSOTP
  // Service order type
  //
  $rivi .= ";";

  // OKMTIC
  // Credit check - MTI
  // 0
  $rivi .= ";";

  // OKPWMT
  // PIN code
  //
  $rivi .= ";";

  // OKBPCD
  // Buying pattern type
  // 0
  $rivi .= ";";

  // OKBPEX
  // Buying pattern exception
  // 0
  $rivi .= ";";

  // OKACHK
  // Assortment check
  // 0
  $rivi .= ";";

  // OKTINC
  // VAT included
  // 0
  $rivi .= ";";

  // OKBUSE
  // Bonus and/or commission active
  // 1
  $rivi .= ";";

  // OKTCEX
  // Included in business chain
  // 0
  $rivi .= ";";

  // OKPYOP
  // Search path - payer
  // 0
  $rivi .= ";";

  // OKALWT
  // Alias category
  // 0
  $rivi .= ";";

  // OKPOPN
  // Alias number
  //
  $rivi .= ";";

  // OKSOOP
  //
  // 0
  $rivi .= ";";

  // OKPRS1
  // Price list SO
  //
  $rivi .= ";";

  // OKPRS2
  // Price list 2 SO
  //
  $rivi .= ";";

  // OKPRS3
  // Price list 3 SO
  //
  $rivi .= ";";

  // OKPRS4
  // Price list 4 SO
  //
  $rivi .= ";";

  // OKPRS5
  // Price list 5 SO
  //
  $rivi .= ";";

  // OKDMSO
  // Discount model SO
  //
  $rivi .= ";";

  // OKLSOI
  // Last SO invoice date
  // 0
  $rivi .= ";";

  // OKODUD
  // Credit limit 4
  // 0
  $rivi .= ";";

  // OKODUE
  // Number of overdue days
  // 0
  $rivi .= ";";

  // OKEALO
  // EAN location code
  // 0
  $rivi .= ";";

  // OKECAR
  // State
  //
  $rivi .= ";";

  // OKGEOC
  // Geographical code
  // 0
  $rivi .= ";";

  // OKTECN
  // Tax exemption contract number
  //
  $rivi .= ";";

  // OKTEEC
  // Tax exemption expiry date
  // 0
  $rivi .= ";";

  // OKAGPY
  // Payer - postal giro
  //
  $rivi .= ";";

  // OKAGCP
  // Clearing number
  //
  $rivi .= ";";

  // OKAGAC
  // Bank account number - postal giro
  //
  $rivi .= ";";

  // OKAGBP
  // Payer - bank giro
  //
  $rivi .= ";";

  // OKACLB
  // Clearing number
  //
  $rivi .= ";";

  // OKAACB
  // Bank account number - bank giro
  //
  $rivi .= ";";

  // OKAGPN
  // Personal ID
  //
  $rivi .= ";";

  // OKAGBG
  // Bank giro number
  // 0
  $rivi .= ";";

  // OKAGPG
  // Postal giro number
  // 0
  $rivi .= ";";

  // OKAGCA
  // Cancellation
  // 0
  $rivi .= ";";

  // OKAGTD
  // Transmission date
  // 0
  $rivi .= ";";

  // OKAGTN
  // Transmission number
  // 0
  $rivi .= ";";

  // OKINRC
  // Invoice recipient
  //
  $rivi .= ";";

  // OKCESA
  // Marketing ID - M3 SMS
  //
  $rivi .= ";";

  // OKCHSY
  // Line charge model
  //
  $rivi .= ";";

  // OKTAXC
  // Tax code customer/address
  //
  $rivi .= ";";

  // OKHAFE
  // Harbor or airport
  //
  $rivi .= ";";

  // OKOT89
  // Check planned split
  // 0
  $rivi .= ";";

  // OKPRDL
  // Print delivery information
  // 0
  $rivi .= ";";

  // OKMCON
  // Mandatory entry of customers order numbe
  // 0
  $rivi .= ";";

  // OKRAN1
  // Fixed due date
  // 0
  $rivi .= ";";

  // OKRAN2
  // Fixed due date
  // 0
  $rivi .= ";";

  // OKRAN3
  // Fixed due date
  // 0
  $rivi .= ";";

  // OKRAN4
  // Fixed due date
  // 0
  $rivi .= ";";

  // OKQUCK
  // Quotation check
  // 0
  $rivi .= ";";

  // OKIVGP
  // Invoicing group
  // 01
  $rivi .= ";";

  // OKACEI
  // Advance invoicing of ECI
  // 0
  $rivi .= ";";

  // OKVDLA
  // Display valid delivery addresses
  // 0
  $rivi .= ";";

  // OKFACI
  // Facility
  //
  $rivi .= ";";

  // OKDTID
  // Data identity
  // 0
  $rivi .= ";";

  // OKDESV
  // Alternate lang for option description
  //
  $rivi .= ";";

  // OKCHCL
  // Charge calculation
  // 0
  $rivi .= ";";

  // OKMCOS
  // CO number mandatory
  // 0
  $rivi .= ";";

  // OKSPLM
  // Supply model
  //
  $rivi .= ";";

  // OKEXPT
  // Exclude payment terms
  // 0
  $rivi .= ";";

  // OKATPR
  // Attribute pricing rule
  // 1
  $rivi .= ";";

  // OKACGR
  // Object access group
  //
  $rivi .= ";";

  // OKSTMS
  // Statement code
  // 2
  $rivi .= ";";

  // OKSTMR
  // Statement rule
  //
  $rivi .= ";";

  // OKBCKO
  // Backorder
  // 1
  $rivi .= ";";

  // OKPADL
  // Partial delivery
  // 1
  $rivi .= ";";

  // OKTOWN
  // City
  // HELSINKI
  $rivi .= pupesoft_csvstring($row['postitp']).";";

  // OKADVI
  // Ship-via address
  //
  $rivi .= ";";

  // OKESAL
  // E-Sales customer
  // 0
  $rivi .= ";";

  // OKRGDT
  // Entry date
  // 20150813
  $rivi .= substr(str_replace("-", "", $row['luontiaika']), 0, 8).";";

  // OKRGTM
  // Entry time
  // 103926
  $rivi .= substr(str_replace(array(" ","-",":"), "", $row['luontiaika']), 8, 6).";";

  // OKLMDT
  // Change date
  // 20150813
  $rivi .= substr(str_replace("-", "", $row['muutospvm']), 0, 8).";";

  // OKCHNO
  // Change number
  // 21
  $rivi .= ";";

  // OKCHID
  // Changed by
  // username
  $rivi .= ";";

  // OKLMTS
  // Timestamp
  // 1439457749878
  $rivi .= ";";

  // OKSCED
  // Delivery regrouping
  // 0
  $rivi .= "";

  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K‰sitell‰‰n rivi‰ {$k_rivi}\n";
  }
}

fclose($fp);

if (!empty($tallenna_polku)) {
  rename($filepath, $tallenna_polku."/".basename($filepath));
}

echo "Valmis.\n";
