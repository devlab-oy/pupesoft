<?php

require_once('CSVDumper.php');

class TarkastuksetCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
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
		);
		$required_fields = array(
			'laite',
			'toimenpide',
		);

		$this->setFilepath("/tmp/konversio/TARKASTUKSET.csv");
		$this->setSeparator(';#x#');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('tyomaarays');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

//			index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
			$valid = $this->validoi_rivi($rivi, $index + 2);

			if (!$valid) {
				unset($this->rivit[$index]);
			}

			$progressbar->increase();
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
				$laite_tunnus = $this->hae_laite_koodilla($rivi[$key]);

				if (isset($laite_tunnus) and is_numeric($laite_tunnus)) {
					$asiakas_tunnus = $this->hae_laitteen_asiakas($laite_tunnus);
					if ($asiakas_tunnus) {
						$rivi[$key] = $laite_tunnus;
						$rivi['liitostunnus'] = $asiakas_tunnus;
					}
					else {
						$this->errors[$index][] = t('FATAL!! Laitteelta puuttuu asiakas')." <b>{$rivi[$key]}</b> ";
						$valid = false;
					}
				}
				else {
					$this->errors[$index][] = t('Laitetta')." <b>{$rivi[$key]}</b> ".t('ei löytynyt');
					$valid = false;
				}
			}
			else if ($key == 'toimenpide') {
				if (!$this->loytyyko_tuote($rivi[$key])) {
					$this->errors[$index][] = t('Toimenpide tuotetta')." <b>{$rivi[$key]}</b> ".t('ei löytynyt');
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
		$progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
		$progress_bar->initialize(count($this->rivit));
		foreach ($this->rivit as $rivi) {

			$query = "	INSERT INTO {$this->table}
						(".implode(", ", array_keys($rivi)).")
						VALUES
						('".implode("', '", array_values($rivi))."')";

			//Purkka fix
			$query = str_replace("'now()'", 'now()', $query);
			pupe_query($query);

			$progress_bar->increase();
		}
	}

	private function hae_laite_koodilla($koodi) {
		$query = "	SELECT tunnus
					FROM laite
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND koodi = '{$koodi}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) != 1) {
			return false;
		}

		$laite = mysql_fetch_assoc($result);

		return $laite['tunnus'];
	}

	private function hae_laitteen_asiakas($laite_tunnus) {
		$query = "	SELECT asiakas.tunnus
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
					WHERE laite.yhtio = '{$this->kukarow['yhtio']}'
					AND laite.tunnus = '{$laite_tunnus}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			return false;
		}

		$asiakas = mysql_fetch_assoc($result);

		return $asiakas['tunnus'];
	}

	private function loytyyko_tuote($tuoteno) {
		$query = "	SELECT tunnus
					FROM tuote
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		if (mysql_num_rows($result) == 1) {
			return true;
		}

		return false;
	}

}
