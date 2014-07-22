<?php

require_once('CSVDumper.php');

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/laite_huolto_functions.inc");

class TarkastuksetCSVDumper extends CSVDumper {

  protected $unique_values = array();
  protected $tuotteet = array();
  protected $laitteet = array();
  protected $huoltosyklit = array();
  protected $yhtio = array();
  protected $asiakkaat = array();
  private $kaato_tilausrivi = array();
  private $kaato_tilausrivin_lisatiedot = array();

  public function __construct($kukarow, $filepath) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'laite'      => 'LAITE',
        'koodi'      => 'LAITE', //for debug reasons
        'toimenpide' => 'TUOTENRO',
        'nimitys'    => 'NIMIKE',
        'poikkeus'   => 'LAATU',
        'tilkpl'     => 'KPL',
        'hinta'      => 'HINTA',
        'ale1'       => 'ALE',
        'kommentti'  => 'HUOM',
        'toimaika'   => 'ED', //tämä pitää mennä huoltosyklit_laitteet.viimeinen_tapahtuma
        'toimitettu' => 'SEUR', //tämä tilausriville toimajaksi
        'status'     => 'STATUS',
        'id'         => 'ID' // for debug reasons
    );
    $required_fields = array(
        'laite',
        'toimenpide',
    );

    $this->setFilepath($filepath);
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('tyomaarays');
    $this->setColumnCount(26);
    $this->setProggressBar(false);
  }

  protected function konvertoi_rivit() {
    if ($this->is_proggressbar_on) {
      $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
      $progressbar->initialize(count($this->rivit));
    }

    $this->hae_kaikki_laitteet();
    $this->hae_kaikki_tuotteet();
    $this->hae_kaikkien_laitteiden_huoltosyklit();
    $this->hae_yhtio();
    $this->hae_kaikki_asiakkaat();

    foreach ($this->rivit as $index => &$rivi) {
      $rivi = $this->konvertoi_rivi($rivi);
      $rivi = $this->lisaa_pakolliset_kentat($rivi);

//      index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
      $valid = $this->validoi_rivi($rivi, $index + 2);

      if (!$valid) {
        unset($this->rivit[$index]);
      }

      if ($this->is_proggressbar_on) {
        $progressbar->increase();
      }
    }
  }

  protected function konvertoi_rivi($rivi) {
    $rivi_temp = array();

    foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
      if (array_key_exists($csv_header, $rivi)) {
        if ($konvertoitu_header == 'hinta') {
          $rivi_temp[$konvertoitu_header] = str_replace(',', '.', $rivi[$csv_header]);
        }
        else if ($konvertoitu_header == 'toimenpide') {
          if ($rivi[$csv_header] == '990001' or $rivi[$csv_header] == '990011') {
            $rivi_temp[$konvertoitu_header] = 'KAYNTI';
          }
          else {
            $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
          }
        }
        else if ($konvertoitu_header == 'kommentti') {
          if ($rivi[$csv_header] == 'None') {
            $rivi_temp[$konvertoitu_header] = '';
          }
          else {
            $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
          }
        }
        else {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
      }
    }

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;
    foreach ($rivi as $key => $value) {
      if (in_array($key, $this->required_fields) and $value == '') {
        $this->errors[$index][] = t('Pakollinen kenttä') . " <b>{$key}</b> " . t('puuttuu');
        $valid = false;
      }

      if ($key == 'laite') {
        $laite = $this->laitteet[$rivi[$key]];

        if (isset($laite['laite_tunnus']) and is_numeric($laite['laite_tunnus'])) {
          $rivi[$key] = $laite['laite_tunnus'];
          $rivi['laite_tuoteno'] = $laite['tuoteno'];
          $rivi['liitostunnus'] = $laite['asiakas_tunnus'];
          $rivi['kohde_nimi'] = $laite['kohde_nimi'];
          $rivi['paikka_nimi'] = $laite['paikka_nimi'];
        }
        else {
          $this->errors[$index][] = t('FATAL Laitetta') . " <b>{$rivi[$key]}</b> " . t('ei löytynyt');
          $valid = false;
        }
      }
      else if ($key == 'toimenpide') {
        if (!$this->loytyyko_tuote($rivi[$key])) {
          $this->errors[$index][] = t('Toimenpide tuotetta') . " <b>{$rivi[$key]}</b> " . t('ei löytynyt');
          $valid = false;
        }
        else {
          //loytyyko_tuote metodi populoi tuotteet arrayta
          $rivi['toimenpide_tuotteen_tyyppi'] = $this->tuotteet[$rivi[$key]]['toimenpide_tuotteen_tyyppi'];

          $huoltosyklit = $this->huoltosyklit[$rivi['laite']];

          if (!empty($huoltosyklit)) {
            $tehtava_huolto = search_array_key_for_value_recursive($huoltosyklit, 'toimenpide', $rivi['toimenpide']);
            $tehtava_huolto = $tehtava_huolto[0];

            $muut_huollot = array();
            foreach ($huoltosyklit as $huoltosykli) {
              if ($huoltosykli['huoltosykli_tunnus'] != $tehtava_huolto['huoltosykli_tunnus']) {
                $muut_huollot[] = $huoltosykli;
              }
            }
          }

          $rivi['tehtava_huolto'] = $tehtava_huolto;
          $rivi['muut_huollot'] = $muut_huollot;
        }
      }
      else if ($key == 'toimaika') {
        if (empty($rivi[$key])) {
          $valid = false;
        }
      }
      else if ($key == 'status') {
        if (!stristr($rivi[$key], 'valmis')) {
          $valid = false;
        }
      }
    }

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['kieli'] = 'fi';

    return $rivi;
  }

  protected function dump_data() {
    if ($this->is_proggressbar_on) {
      $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan') . ' : ' . count($this->rivit));
      $progress_bar->initialize(count($this->rivit));
    }
    $i = 1;
    foreach ($this->rivit as $rivi) {
      $asiakas = $this->asiakkaat[$rivi['liitostunnus']];
      $toimenpide_tuote = $this->tuotteet[$rivi['toimenpide']];
      $tyomaarays_tunnus = $this->luo_tyomaarays($asiakas, $rivi['toimaika'], $rivi['hinta'], true);

      $this->luo_tilausrivi($tyomaarays_tunnus, $toimenpide_tuote, $rivi['laite'], $rivi['toimaika'], $rivi['toimitettu'], true);

      paivita_viimenen_tapahtuma_laitteen_huoltosyklille($rivi['laite'], $rivi['tehtava_huolto']['huoltosykli_tunnus'], $rivi['toimaika']);

      //jos kyseessä on koeponnistus tai huolto niin pitäisi osata merkata huollon/koeponnistuksen ja tarkastuksen viimeinen tapahtuma oikein
      if ($rivi['tehtava_huolto']['selite'] == 'huolto' or $rivi['tehtava_huolto']['selite'] == 'koeponnistus') {
        foreach ($rivi['muut_huollot'] as $muu_huolto) {
          if ($rivi['tehtava_huolto']['viimeinen_tapahtuma'] >= $muu_huolto['viimeinen_tapahtuma']) {
            paivita_viimenen_tapahtuma_laitteen_huoltosyklille($rivi['laite'], $muu_huolto['huoltosykli_tunnus'], $rivi['toimaika']);
          }
        }
      }

      //poikkeukset pitää merkata historiaan.
      if (!empty($rivi['poikkeus'])) {
        $tekematon_tilausrivi = $this->hae_tyomaarayksen_tilausrivi($tyomaarays_tunnus);
        $kaato_tilausrivi = $this->hae_kaato_tilausrivi();
        $kaato_tilausrivin_lisatiedot = $this->hae_kaato_tilausrivin_lisatiedot();

        aseta_tyomaarays_var($tekematon_tilausrivi['tunnus'], 'P');
        aseta_tyomaarays_status('V', $tekematon_tilausrivi['tunnus']);

        $kaato_tilausrivi['kommentti'] = $rivi['kommentti'];
        $kaato_tilausrivi['otunnus'] = $tyomaarays_tunnus;

        $kaato_tilausrivin_lisatiedot['tilausrivilinkki'] = $tekematon_tilausrivi['tunnus'];
        $kaato_tilausrivin_lisatiedot['vanha_otunnus'] = $tyomaarays_tunnus;
        $kaato_tilausrivin_lisatiedot['asiakkaan_positio'] = $tekematon_tilausrivi['asiakkaan_positio'];

        $kaato_tilausrivin_lisatiedot['tilausrivitunnus'] = $this->tallenna_poikkeus_rivi($kaato_tilausrivi, false);
        $this->tallenna_poikkeus_rivi($kaato_tilausrivin_lisatiedot, true);
      }

      if (!empty($rivi['kommentti'])) {
        $this->paivita_tyomaarayksen_kommentti($tyomaarays_tunnus, $rivi['kommentti']);
      }

      if ($this->is_proggressbar_on) {
        $progress_bar->increase();
      }

      $i++;
    }
  }

  private function hae_kaikki_laitteet() {
    $query = "SELECT laite.tunnus AS laite_tunnus,
              laite.tuoteno,
              laite.koodi,
              paikka.nimi AS paikka_nimi,
              kohde.nimi AS kohde_nimi,
              asiakas.tunnus AS asiakas_tunnus
              FROM laite
              JOIN paikka
              ON ( paikka.yhtio = laite.yhtio
                AND paikka.tunnus = laite.paikka )
              JOIN kohde
              ON ( kohde.yhtio = paikka.yhtio
                AND kohde.tunnus = paikka.kohde )
              JOIN asiakas
              ON ( asiakas.yhtio = kohde.yhtio
                AND asiakas.tunnus = kohde.asiakas )
              WHERE laite.yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    while ($laite = mysql_fetch_assoc($result)) {
      $this->laitteet[$laite['koodi']] = $laite;
    }

    return true;
  }

  private function hae_kaikki_tuotteet() {
    $query = "SELECT tuote.*,
              tuotteen_avainsanat.selite AS toimenpide_tuotteen_tyyppi
              FROM tuote
              LEFT JOIN tuotteen_avainsanat
              ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
                AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
                AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
              WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);
    while ($tuote = mysql_fetch_assoc($result)) {
      $this->tuotteet[$tuote['tuoteno']] = $tuote;
    }
  }

  private function loytyyko_tuote($tuoteno) {
    if (array_key_exists($tuoteno, $this->tuotteet)) {
      return true;
    }

    return false;
  }

  private function hae_kaikkien_laitteiden_huoltosyklit() {
    $query = "SELECT huoltosykli.tunnus AS huoltosykli_tunnus,
              huoltosykli.toimenpide AS toimenpide,
              IFNULL(huoltosyklit_laitteet.viimeinen_tapahtuma, '0000-00-00') AS viimeinen_tapahtuma,
              huoltosyklit_laitteet.huoltovali AS huoltovali,
              huoltosyklit_laitteet.laite_tunnus AS laite_tunnus,
              tuotteen_avainsanat.selite
              FROM huoltosykli
              JOIN huoltosyklit_laitteet
              ON ( huoltosyklit_laitteet.yhtio = huoltosykli.yhtio
                AND huoltosyklit_laitteet.huoltosykli_tunnus = huoltosykli.tunnus)
              JOIN tuotteen_avainsanat
              ON ( tuotteen_avainsanat.yhtio = huoltosykli.yhtio
                AND tuotteen_avainsanat.tuoteno = huoltosykli.toimenpide )
              WHERE huoltosykli.yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      return false;
    }

    while ($huoltosykli = mysql_fetch_assoc($result)) {
      $this->huoltosyklit[$huoltosykli['laite_tunnus']][] = $huoltosykli;
    }
  }

  private function paivita_tyomaarayksen_kommentti($tunnus, $kommentti) {
    $query = "UPDATE tilausrivi
              SET kommentti = '{$kommentti}'
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND otunnus = '{$tunnus}'";
    pupe_query($query);
  }

  private function hae_tyomaarayksen_tilausrivi($tyomaarays_tunnus) {
    //Työmääräyksien generointi vaiheessa tiedetään, että yhdellä työmääräyksellä voi olla vian yksi tilausrivi
    $query = "SELECT tilausrivi.*,
              tilausrivin_lisatiedot.asiakkaan_positio
              FROM tilausrivi
              JOIN tilausrivin_lisatiedot
              ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio
                AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
              WHERE tilausrivi.yhtio = '{$this->kukarow['yhtio']}'
              AND tilausrivi.var != 'P'
              AND tilausrivi.otunnus = '{$tyomaarays_tunnus}'";
    $result = pupe_query($query);

    $tilausrivi = mysql_fetch_assoc($result);

    return $tilausrivi;
  }

  private function hae_kaato_tilausrivi() {
    if (!empty($this->kaato_tilausrivi)) {
      return $this->kaato_tilausrivi;
    }

    //Kaato-tilausrivejä on vain yksi per yhtiö
    $query = "SELECT tilausrivi.*
              FROM tilausrivi
              WHERE tilausrivi.yhtio = '{$this->kukarow['yhtio']}'
              AND tilausrivi.tuoteno = 'kaato_tuote'";
    $result = pupe_query($query);

    $kaato_tilausrivi = mysql_fetch_assoc($result);

    $this->kaato_tilausrivi = $kaato_tilausrivi;

    return $this->kaato_tilausrivi;
  }

  private function hae_kaato_tilausrivin_lisatiedot() {
    if (!empty($this->kaato_tilausrivin_lisatiedot)) {
      return $this->kaato_tilausrivin_lisatiedot;
    }

    $query = "SELECT tilausrivin_lisatiedot.*
              FROM tilausrivin_lisatiedot
              JOIN tilausrivi
              ON ( tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio
                AND tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
                AND tilausrivi.tuoteno = 'kaato_tuote')
              WHERE tilausrivin_lisatiedot.yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    $kaato_tilausrivi = mysql_fetch_assoc($result);

    $this->kaato_tilausrivin_lisatiedot = $kaato_tilausrivi;

    return $this->kaato_tilausrivin_lisatiedot;
  }

  private function tallenna_poikkeus_rivi($rivi, $onko_lisatieto_rivi = false) {
    unset($rivi['laatija']);
    unset($rivi['luontiaika']);
    unset($rivi['laadittu']);
    unset($rivi['tunnus']);

    $taulu = 'tilausrivi';
    $laadittu = 'laadittu';
    if ($onko_lisatieto_rivi) {
      $taulu = 'tilausrivin_lisatiedot';
      $laadittu = 'luontiaika';
    }

    $query = "INSERT INTO
              {$taulu} (" . implode(", ", array_keys($rivi)) . ", {$laadittu}, laatija)
              VALUES('" . implode("', '", array_values($rivi)) . "', now(), 'import')";
    pupe_query($query);

    return mysql_insert_id();
  }

  public static function split_file($filepath) {
    $filepaths = array();

    $folder = dirname($filepath);
    // Otetaan tiedostosta ensimmäinen rivi talteen, siinä on headerit
    $file = fopen($filepath, "r") or die(t("Tiedoston avaus epäonnistui") . "!");
    $header_rivi = fgets($file);
    fclose($file);

    $header_file = "{$folder}/header_file";
    // Laitetaan header fileen, koska filejen mergettäminen on nopeempaa komentoriviltä
    file_put_contents($header_file, $header_rivi);

    chdir($folder);
    system("split -l 1000 $filepath");

    // Poistetaan alkuperäinen
    unlink($filepath);

    // Loopataan läpi kaikki splitatut tiedostot
    if ($handle = opendir($folder)) {
      while (false !== ($file = readdir($handle))) {
        if (!in_array($file, array('.', '..', '.DS_Store', 'header_file')) and is_file($file)) {
          // Jos kyseessä on eka file (loppuu "aa"), ei laiteta headeriä
          $new_file_filepath = $folder . "/{$file}";
          if (substr($file, -2) != "aa") {
            // Keksitään temp file
            $temp_file = $folder . "/{$file}_s";

            // Concatenoidaan headerifile ja tämä file temppi fileen
            system("cat " . escapeshellarg($header_file) . " " . escapeshellarg($file) . " > " . escapeshellarg($temp_file));

            // Poistetaan alkuperäinen file
            unlink($file);
            $new_file_filepath = $folder . "/{$file}_s";
          }

          $filepaths[] = $new_file_filepath;
        }
      }
      closedir($handle);
    }

    unlink($header_file);

    return $filepaths;
  }

  private function hae_yhtio() {
    $query = "SELECT *
              FROM yhtio
              WHERE yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      die('Ei yhtiötä');
    }

    $this->yhtio = mysql_fetch_assoc($result);
  }

  private function hae_kaikki_asiakkaat() {
    $query = "SELECT *
              FROM asiakas
              WHERE yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    while ($asiakas = mysql_fetch_assoc($result)) {
      $this->asiakkaat[$asiakas['tunnus']] = $asiakas;
    }
  }

  private function luo_tyomaarays($asiakas, $toimaika, $summa, $alv_23 = true) {
    $kuka = $this->kukarow;
    $yhtio = $this->yhtio;
    if ($alv_23) {
      $alv = '23.00';
    }
    else {
      $alv = '24.00';
    }
    $query = "INSERT INTO lasku SET
              yhtio = '{$kuka['yhtio']}',
              yhtio_nimi = '{$yhtio['nimi']}',
              yhtio_osoite = '{$yhtio['osoite']}',
              yhtio_postino = '{$yhtio['postino']}',
              yhtio_postitp = '{$yhtio['postitp']}',
              yhtio_maa = '{$yhtio['maa']}',
              yhtio_ovttunnus = '{$yhtio['ovttunnus']}',
              yhtio_kotipaikka = '{$yhtio['kotipaikka']}',
              yhtio_toimipaikka = '{$yhtio['toimipaikka']}',
              nimi = '{$asiakas['nimi']}',
              nimitark = '{$asiakas['nimitark']}',
              osoite = '{$asiakas['osoite']}',
              osoitetark = '{$asiakas['osoitetark']}',
              postino = '{$asiakas['postino']}',
              postitp = '{$asiakas['postitp']}',
              maa = '{$asiakas['maa']}',
              toim_nimi = '{$asiakas['toim_nimi']}',
              toim_nimitark = '{$asiakas['toim_nimitark']}',
              toim_osoite = '{$asiakas['osoite']}',
              toim_postino = '{$asiakas['postino']}',
              toim_postitp = '{$asiakas['postitp']}',
              toim_maa = '{$asiakas['maa']}',
              pankki_haltija = '',
              tilinumero = '',
              swift = '',
              pankki1 = '',
              pankki2 = '',
              pankki3 = '',
              pankki4 = '',
              ultilno_maa = '',
              ultilno = '',
              clearing = '',
              maksutyyppi = '',
              valkoodi = 'EUR',
              alv = '{$alv}',
              lapvm = '0000-00-00',
              tapvm = '0000-00-00',
              kapvm = '0000-00-00',
              erpcm = '0000-00-00',
              suoraveloitus = '',
              olmapvm = '0000-00-00',
              toimaika = '{$toimaika}',
              toimvko = '',
              kerayspvm = NOW(),
              keraysvko = '',
              summa = '{$summa}',
              summa_valuutassa = 0.00,
              kasumma = 0.00,
              kasumma_valuutassa = 0.00,
              hinta = '{$summa}',
              kate = 0.00,
              kate_korjattu = NULL,
              arvo = 0.00,
              arvo_valuutassa = 0.00,
              saldo_maksettu = 0.00,
              saldo_maksettu_valuutassa = 0.00,
              pyoristys = 0.00,
              pyoristys_valuutassa = 0.00,
              pyoristys_erot = 0.00,
              pyoristys_erot_alv = 0.00,
              laatija = 'import',
              luontiaika = NOW(),
              maksaja = '',
              maksuaika = '0000-00-00 00:00:00',
              lahetepvm = '0000-00-00 00:00:00',
              lahetetyyppi = 'tulosta_lahete_eiale_eihinta.inc',
              laskutyyppi = '-9',
              laskutettu = '0000-00-00 00:00:00',
              hyvak1 = '',
              h1time = '0000-00-00 00:00:00',
              hyvak2 = '',
              h2time = '0000-00-00 00:00:00',
              hyvak3 = '',
              h3time = '0000-00-00 00:00:00',
              hyvak4 = '',
              h4time = '0000-00-00 00:00:00',
              hyvak5 = '',
              hyvaksyja_nyt = '',
              h5time = '0000-00-00 00:00:00',
              hyvaksynnanmuutos = '',
              prioriteettinro = 9,
              vakisin_kerays = '',
              viite = '',
              laskunro = 0,
              viesti = '',
              sisviesti1 = '',
              sisviesti2 = '',
              sisviesti3 = '',
              comments = '',
              ohjausmerkki = '',
              tilausyhteyshenkilo = '',
              asiakkaan_tilausnumero = '',
              kohde = '',
              myyja = '0',
              allekirjoittaja = 0,
              maksuehto = '0',
              toimitustapa = 'Nouto',
              toimitustavan_lahto = 0,
              toimitustavan_lahto_siirto = 0,
              rahtivapaa = '',
              rahtisopimus = '',
              ebid = '',
              ytunnus = '{$asiakas['ytunnus']}',
              verkkotunnus = '',
              ovttunnus = '{$asiakas['ovttunnus']}',
              toim_ovttunnus = '{$asiakas['ovttunnus']}',
              chn = '100',
              mapvm = '0000-00-00',
              popvm = '0000-00-00 00:00:00',
              vienti_kurssi = 1.00,
              maksu_kurssi = 0.00,
              maksu_tili = '',
              alv_tili = '{$yhtio['alv']}',
              tila = 'L',
              alatila = 'D',
              huolitsija = '',
              jakelu = '',
              kuljetus = '',
              maksuteksti = '',
              muutospvm = NOW(),
              muuttaja = 'import',
              vakuutus = '',
              kassalipas = '',
              ketjutus = '',
              sisainen = '',
              osatoimitus = '',
              splittauskielto = '',
              jtkielto = '',
              tilaustyyppi = 'A',
              eilahetetta = '',
              tilausvahvistus = '',
              laskutusvkopv = 0,
              toimitusehto = '',
              vienti = '',
              kolmikantakauppa = '',
              viitetxt = '',
              ostotilauksen_kasittely = '',
              erikoisale = 0.00,
              erikoisale_saapuminen = 0.00,
              kerayslista = 0,
              liitostunnus = '{$asiakas['tunnus']}',
              viikorkopros = 10.00,
              viikorkoeur = 0.00,
              varasto = 0,
              tulostusalue = '',
              kirjoitin = '',
              noutaja = '',
              kohdistettu = '',
              rahti_huolinta = 0.00,
              rahti = 0.00,
              rahti_etu = 0.00,
              rahti_etu_alv = 0.00,
              osto_rahti_alv = 0.00,
              osto_kulu_alv = 0.00,
              osto_rivi_kulu_alv = 0.00,
              osto_rahti = 0.00,
              osto_kulu = 0.00,
              osto_rivi_kulu = 0.00,
              maa_lahetys = '',
              maa_maara = '',
              maa_alkupera = '',
              kuljetusmuoto = 0,
              kauppatapahtuman_luonne = 0,
              bruttopaino = 0.00,
              sisamaan_kuljetus = '',
              sisamaan_kuljetus_kansallisuus = '',
              aktiivinen_kuljetus = '',
              kontti = 0,
              valmistuksen_tila = '',
              aktiivinen_kuljetus_kansallisuus = '',
              sisamaan_kuljetusmuoto = 0,
              poistumistoimipaikka = '',
              poistumistoimipaikka_koodi = '',
              lisattava_era = 0.00,
              vahennettava_era = 0.00,
              tullausnumero = '',
              vientipaperit_palautettu = '',
              piiri = '',
              pakkaamo = 0,
              jaksotettu = 0,
              factoringsiirtonumero = 0,
              ohjelma_moduli = 'PUPESOFT',
              label = 0,
              tunnusnippu = 0,
              vanhatunnus = 0";
    pupe_query($query);
    $tunnus = mysql_insert_id();

    $query = "INSERT INTO laskun_lisatiedot SET
              yhtio = '{$kuka['yhtio']}',
              otunnus = '{$tunnus}',
              kolm_ovttunnus = 'FI',
              kolm_maa = '{$asiakas['maa']}',
              laskutus_nimi = '{$asiakas['nimi']}',
              laskutus_osoite = '{$asiakas['osoite']}',
              laskutus_postino = '{$asiakas['postino']}',
              laskutus_postitp = '{$asiakas['postitp']}',
              laskutus_maa = '{$asiakas['maa']}',
              laatija = 'import',
              luontiaika = NOW()";
    pupe_query($query);

    $query = "INSERT INTO tyomaarays SET
              yhtio = '{$kuka['yhtio']}',
              laatija = 'import',
              luontiaika = NOW(),
              tyostatus = 'X',
              tyojono = 'import',
              otunnus = '{$tunnus}'";
    pupe_query($query);

    return $tunnus;
  }

  private function luo_tilausrivi($tyomaarays_tunnus, $tuote, $laite_tunnus, $toimaika, $toimitettu, $alv_23 = true) {
    $kuka = $this->kukarow;
    $yhtio = $this->yhtio;
    if ($alv_23) {
      $alv = '23.00';
    }
    else {
      $alv = '24.00';
    }
    $query = "INSERT INTO tilausrivi SET
              yhtio = '{$kuka['yhtio']}',
              tyyppi = 'L',
              toimaika = '{$toimaika}',
              kerayspvm = '{$toimaika}',
              otunnus = '{$tyomaarays_tunnus}',
              tuoteno = '{$tuote['tuoteno']}',
              try = '{$tuote['try']}',
              osasto = 0,
              nimitys = '{$tuote['nimitys']}',
              kpl = 0.00,
              kpl2 = 0.00,
              tilkpl = 1.00,
              yksikko = '',
              varattu = 1.00,
              jt = 0.00,
              hinta = {$tuote['myyntihinta']},
              hinta_valuutassa = 0.00,
              hinta_alkuperainen = 0.00,
              alv = {$alv},
              rivihinta = 0.00,
              erikoisale = 0.00,
              erikoisale_saapuminen = 0.00,
              ale1 = 0.00,
              ale2 = 0.00,
              ale3 = 0.00,
              kate = 0.00,
              kommentti = '',
              laatija = 'import',
              laadittu = NOW(),
              keratty = 'saldoton',
              kerattyaika = NOW(),
              toimitettu = 'import',
              toimitettuaika = '{$toimitettu}',
              varastoon = 1";

    pupe_query($query);
    $tunnus = mysql_insert_id();

    $query = "INSERT INTO tilausrivin_lisatiedot SET
              yhtio = '{$kuka['yhtio']}',
              tilausrivitunnus = '{$tunnus}',
              asiakkaan_positio = '{$laite_tunnus}',
              vanha_otunnus = '{$tyomaarays_tunnus}',
              luontiaika = NOW(),
              laatija = 'import'";
    pupe_query($query);
  }

  protected function tarkistukset() {
//    echo "Ei tarkistuksia";
  }
}
