<?php

require_once('CSVDumper.php');

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/laite_huolto_functions.inc");

class TarkastuksetCSVDumper extends CSVDumper {

	protected $unique_values = array();
	protected $products = array();
	protected $laitteet = array();

	public function __construct($kukarow, $filepath) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'laite'		 => 'LAITE',
			'toimenpide' => 'TUOTENRO',
			'nimitys'	 => 'NIMIKE',
			'poikkeus'	 => 'LAATU',
			'tilkpl'	 => 'KPL',
			'hinta'		 => 'HINTA',
			'ale1'		 => 'ALE',
			'kommentti'	 => 'HUOM',
			'toimaika'	 => 'ED',
			'toimitettu' => 'SEUR',
			'status'	 => 'STATUS',
			'id'		 => 'ID' // for debug reasons, unset
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
		$this->setProggressBar(true);
	}

	protected function konvertoi_rivit() {
		if ($this->is_proggressbar_on) {
			$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
			$progressbar->initialize(count($this->rivit));
		}

		$this->hae_kaikki_laitteet();
		$this->hae_tuotteet();

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

//			index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
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
				$this->errors[$index][] = t('Pakollinen kenttä')." <b>{$key}</b> ".t('puuttuu');
				$valid = false;
			}

			if ($key == 'laite') {
				$laite = $this->laitteet[$rivi[$key]];
				//$laite = $this->hae_laite_koodilla($rivi[$key]);

				if (isset($laite['laite_tunnus']) and is_numeric($laite['laite_tunnus'])) {
					$rivi[$key] = $laite['laite_tunnus'];
					$rivi['laite_tuoteno'] = $laite['tuoteno'];
					$rivi['liitostunnus'] = $laite['asiakas_tunnus'];
					$rivi['kohde_nimi'] = $laite['kohde_nimi'];
					$rivi['paikka_nimi'] = $laite['paikka_nimi'];
				}
				else {
					$this->errors[$index][] = t('FATAL Laitetta')." <b>{$rivi[$key]}</b> ".t('ei löytynyt');
					$valid = false;
				}
			}
			else if ($key == 'toimenpide') {
				if (!$this->loytyyko_tuote($rivi[$key])) {
					$this->errors[$index][] = t('Toimenpide tuotetta')." <b>{$rivi[$key]}</b> ".t('ei löytynyt');
					$valid = false;
				}
				else {
					//loytyyko_tuote metodi populoi products arrayta
					$rivi['toimenpide_tuotteen_tyyppi'] = $this->products[$rivi[$key]]['selite'];
					//HUOM tarvitseeko hae_huoltosykli $rivi['toimenpide_tuotteen_tyyppi'] ??

					$huoltosyklit = $this->hae_huoltosyklit($rivi['laite']);

					$tehtava_huolto = search_array_key_for_value_recursive($huoltosyklit, 'toimenpide', $rivi['toimenpide']);
					$tehtava_huolto = $tehtava_huolto[0];

					$muut_huollot = array();
					foreach ($huoltosyklit as $huoltosykli) {
						if ($huoltosykli['huoltosykli_tunnus'] != $tehtava_huolto['huoltosykli_tunnus']) {
							$muut_huollot[] = $huoltosykli;
						}
					}

					$rivi['tehtava_huolto'] = $tehtava_huolto;
					$rivi['muut_huollot'] = $muut_huollot;
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
			$progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
			$progress_bar->initialize(count($this->rivit));
		}
		$i = 1;
		foreach ($this->rivit as $rivi) {
			$params = array(
				'asiakas_tunnus'			 => $rivi['liitostunnus'],
				'toimenpide_tuotteen_tyyppi' => $rivi['toimenpide_tuotteen_tyyppi'],
				'toimenpide'				 => $rivi['toimenpide'],
				'laite_tunnus'				 => $rivi['laite'],
				'huoltosykli_tunnus'		 => $rivi['huoltosykli_tunnus'],
				'tuoteno'					 => $rivi['laite_tuoteno'],
				'kohde_nimi'				 => $rivi['kohde_nimi'],
				'paikka_nimi'				 => $rivi['kohde_nimi'],
				'tyojono'					 => 'joonas',
				'viimeinen_tapahtuma'		 => $rivi['toimitettu'],
			);
			$tyomaarays_tunnus = generoi_tyomaarays($params, array(), $rivi['toimaika']);

			if (empty($tyomaarays_tunnus)) {
				echo "<pre>";
				var_dump($params);
				echo "</pre>";
			}

			if (!empty($tyomaarays_tunnus)) {
				$params = array(
					'lasku_tunnukset'	 => array($tyomaarays_tunnus),
					'toimitettuaika'	 => $rivi['toimitettu'],
				);
				merkkaa_tyomaarays_tehdyksi($params);
				paivita_viimenen_tapahtuma_laitteen_huoltosyklille($rivi['laite'], $rivi['huoltosykli_tunnus'], $rivi['toimitettu']);
			}

			//jos kyseessä on koeponnistus tai huolto niin pitäisi osata merkata huollon/koeponnistuksen ja tarkastuksen viimeinen tapahtuma oikein
			if ($rivi['tehtava_huolto']['selite'] == 'huolto' or $rivi['tehtava_huolto']['selite'] == 'koeponnistus') {
				foreach ($rivi['muut_huollot'] as $muu_huolto) {
					paivita_viimenen_tapahtuma_laitteen_huoltosyklille($rivi['laite'], $muu_huolto['huoltosykli_tunnus'], $rivi['toimitettu']);
				}
			}

			//poikkeukset pitää merkata historiaan.
			if (!empty($rivi['poikkeus'])) {

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
		$query = "	SELECT laite.tunnus AS laite_tunnus,
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

	private function hae_tuotteet() {
		$query = "	SELECT tuote.tuoteno,
					tuotteen_avainsanat.selite
					FROM tuote
					JOIN tuotteen_avainsanat
					ON ( tuotteen_avainsanat.yhtio = tuote.yhtio
						AND tuotteen_avainsanat.tuoteno = tuote.tuoteno
						AND tuotteen_avainsanat.laji = 'tyomaarayksen_ryhmittely' )
					WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'";
		$result = pupe_query($query);
		while ($tuote = mysql_fetch_assoc($result)) {
			$this->products[$tuote['tuoteno']] = $tuote;
		}
	}

	private function loytyyko_tuote($tuoteno) {
		if (array_key_exists($tuoteno, $this->products)) {
			return true;
		}

		return false;
	}

	private function hae_huoltosyklit($laite_tunnus) {
		$query = "	SELECT huoltosykli.tunnus AS huoltosykli_tunnus,
					huoltosykli.toimenpide AS toimenpide,
					tuotteen_avainsanat.selite
					FROM huoltosykli
					JOIN huoltosyklit_laitteet
					ON ( huoltosyklit_laitteet.yhtio = huoltosykli.yhtio
						AND huoltosyklit_laitteet.huoltosykli_tunnus = huoltosykli.tunnus
						AND huoltosyklit_laitteet.laite_tunnus = '{$laite_tunnus}' )
					JOIN tuotteen_avainsanat
					ON ( tuotteen_avainsanat.yhtio = huoltosykli.yhtio
						AND tuotteen_avainsanat.tuoteno = huoltosykli.toimenpide )
					WHERE huoltosykli.yhtio = '{$this->kukarow['yhtio']}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			return false;
		}

		$huoltosyklit = array();
		while ($huoltosykli = mysql_fetch_assoc($result)) {
			$huoltosyklit[] = $huoltosykli;
		}

		return $huoltosyklit;
	}

	private function paivita_tyomaarayksen_kommentti($tunnus, $kommentti) {
		$query = "	UPDATE tilausrivi
					SET kommentti = '{$kommentti}'
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND otunnus = '{$tunnus}'";
		pupe_query($query);
	}

	protected function tarkistukset() {
//		echo "Ei tarkistuksia";
	}

}
