<?php

require_once('CSVDumper.php');

class LaiteCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'tuoteno'	 => 'MALLI',
			'sarjanro'	 => 'MITAT',
			'valm_pvm'	 => 'TOIMPVM', //?? sarake on kauttaaltaan tyhjä
			'oma_numero' => 'DATA20',
			'paikka'	 => 'LISASIJAINTI',
			'sijainti'	 => 'LISASIJAINTI',
		);
		$required_fields = array(
			'tuoteno',
			'paikka',
		);

		$this->setFilepath("/tmp/konversio/LAITE.csv");
		$this->setSeparator(';#x#');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('laite');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->decode_to_utf8($rivi);
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
				$rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
			}
		}

		return $rivi_temp;
	}

	protected function validoi_rivi(&$rivi, $index) {
		$valid = true;
		foreach ($rivi as $key => $value) {
			if ($key == 'paikka') {
				$paikka_tunnus = $this->hae_paikka_tunnus($value);
				if ($paikka_tunnus == 0 and in_array($key, $this->required_fields)) {
					$this->errors[$index][] = t('Paikkaa')." <b>{$value}</b> ".t('ei löydy');
					$valid = false;
				}
				else {
					$rivi[$key] = $paikka_tunnus;
				}
			}
			else if ($key == 'tuoteno') {
				if ($valid) {
					$valid = $this->loytyyko_tuote($rivi[$key]);
					if (!$valid) {
						$this->errors[$index][] = t('Tuote')." {$rivi[$key]} ".t('puuttuu');
					}
				}
			}
			else {
				if (in_array($key, $this->required_fields) and $value == '') {
					$valid = false;
				}
			}
		}

		if (!in_array($rivi['sarjanro'], $this->unique_values)) {
			$this->unique_values[] = $rivi['sarjanro'];
		}
		else {
			$this->errors[$index][] = t('Uniikki kenttä sarjanro')." <b>{$rivi['sarjanro']}</b> ".t('löytyy jo aineistosta');
			$valid = false;
		}

		return $valid;
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

	private function hae_paikka_tunnus($paikan_nimi) {
		$query = '	SELECT tunnus
					FROM paikka
					WHERE yhtio = "'.$this->kukarow['yhtio'].'"
					AND nimi = "'.$paikan_nimi.'"';
		$result = pupe_query($query);
		$paikkarow = mysql_fetch_assoc($result);

		if (!empty($paikkarow)) {
			return $paikkarow['tunnus'];
		}

		return 0;
	}

}