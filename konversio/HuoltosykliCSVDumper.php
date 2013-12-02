<?php

require_once('CSVDumper.php');

class HuoltosykliCSVDumper extends CSVDumper{

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'laite'		 => 'LAITE',
			'toimenpide' => 'TUOTENRO',
			'nimitys'	 => 'NIMIKE',
			'huoltovali' => 'VALI',
		);
		$required_fields = array(
			'laite',
			'toimenpide',
		);

		$this->setFilepath("/tmp/konversio/TUOTETARK.csv");
		$this->setSeparator(';#x#');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('huoltosykli');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

			//index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
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
				if ($konvertoitu_header == 'huoltovali') {
					$rivi_temp[$konvertoitu_header] = (int)$rivi[$csv_header] * 30;
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
				$attrs = $this->hae_tuotteen_tyyppi_koko($rivi[$key]);

				if ($attrs == 1) {
					$this->errors[$index][] = t('Huoltosyklin laitteelle')." <b>{$rivi[$key]}</b> ".t('löytyi enemmän kuin 2 laitteen koko tai tyyppiä');
					$valid = false;
				}
				else if ($attrs == 0) {
					$this->errors[$index][] = t('Huoltosyklin laitteelle')." <b>{$rivi[$key]}</b> ".t('löytyi 0 laitteen koko tai tyyppiä');
					$valid = false;
				}
				else {
					$rivi['tyyppi'] = $attrs['tyyppi'];
					$rivi['koko'] = $attrs['koko'];
				}
			}
			else if ($key == 'toimenpide') {
				$valid_temp = $this->loytyyko_toimenpide_tuote($rivi[$key]);
				if (!$valid_temp) {
					$this->errors[$index][] = t('Toimenpide tuotetta')." <b>{$rivi[$key]}</b> ".t('ei löytynyt');
					if ($valid) {
						$valid = false;
					}
				}
			}
		}

		return $valid;
	}

	protected function dump_data() {
		$progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
		$progress_bar->initialize(count($this->rivit));
		foreach ($this->rivit as $rivi) {
			$nimitys_temp = $rivi['nimitys'];
			unset($rivi['nimitys']);
			unset($rivi['laite']);

			$query = "	INSERT INTO {$this->table}
						(".implode(", ", array_keys($rivi)).")
						VALUES
						('".implode("', '", array_values($rivi))."')";

			//Purkka fix
			$query = str_replace("'now()'", 'now()', $query);
			pupe_query($query);

			$rivi['olosuhde'] = 'X';

			if (stristr($nimitys_temp, 'tarkastus')) {
				$rivi['huoltovali'] = $rivi['huoltovali'] / 2;
			}

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

	protected function lisaa_pakolliset_kentat($rivi) {
		$rivi = parent::lisaa_pakolliset_kentat($rivi);
		$rivi['pakollisuus'] = '1';
		$rivi['olosuhde'] = 'A'; //Olosuhde sisällä

		return $rivi;
	}

	private function hae_tuotteen_tyyppi_koko($tuoteno) {
		$query = "	SELECT tuotteen_avainsanat.laji,
					tuotteen_avainsanat.selite
					FROM tuotteen_avainsanat
					WHERE tuotteen_avainsanat.yhtio = '{$this->kukarow['yhtio']}'
					AND tuotteen_avainsanat.laji IN ('sammutin_koko','sammutin_tyyppi')
					AND tuotteen_avainsanat.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);
		$attrs = array();

		if (mysql_num_rows($result) > 2) {
			return 1;
		}

		if (mysql_num_rows($result) == 0) {
			return 0;
		}

		while ($attr = mysql_fetch_assoc($result)) {
			$attrs[str_replace('sammutin_', '', $attr['laji'])] = $attr['selite'];
		}

		return $attrs;
	}

	private function loytyyko_toimenpide_tuote($tuoteno) {
		$query = "	SELECT *
					FROM tuote
					WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
					AND tuote.tuoteno = '{$tuoteno}'";
		$result = pupe_query($query);

		if (mysql_num_rows($result) == 0) {
			return false;
		}

		return true;
	}

}
