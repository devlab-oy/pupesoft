<?php

require_once('CSVDumper.php');

class PaikkaCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'kohde'	 => 'KUSTPAIKKA',
			'nimi'	 => 'LISASIJAINTI',
			'osoite' => 'SIJAINTI',
			'kuvaus' => 'SIJAINTI',
		);
		$required_fields = array(
			'kohde',
		);

		$this->setFilepath("/tmp/konversio/LAITE.csv");
		$this->setSeparator(';#x#');
		$this->setKonversioArray($konversio_array);
		$this->setRequiredFields($required_fields);
		$this->setTable('paikka');
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
			if ($key == 'kohde') {
				$kohde_tunnus = $this->hae_kohde_tunnus($value);
				if ($kohde_tunnus == 0 and in_array($key, $this->required_fields)) {
					$this->errors[$index][] = t('Kohdetta')." <b>{$value}</b> ".t('ei löydy');
					$valid = false;
				}
				else {
					$rivi[$key] = $kohde_tunnus;
				}
			}
			else {
				if (in_array($key, $this->required_fields) and $value == '') {
					$valid = false;
				}
			}
		}

		//Valitoidaan löytyykö kohteelle jo LISASIJAINTI niminen paikka
		$paikat = $this->unique_values[$rivi['kohde']];
		if (!empty($paikat)) {
			foreach ($paikat as $paikka) {
				if ($paikka == $rivi['nimi']) {
					//kyseinen paikka on jo kohteella
					$valid = false;
					break;
				}
			}
		}

		//Jos paikka on validi niin se voidaan lisätä kohteen paikkoihin
		if ($valid) {
			$this->unique_values[$rivi['kohde']][] = $rivi['nimi'];
		}

		return $valid;
	}

	private function hae_kohde_tunnus($kohde_nimi) {
		$query = '	SELECT tunnus
					FROM kohde
					WHERE yhtio = "'.$this->kukarow['yhtio'].'"
					AND nimi = "'.$kohde_nimi.'"
					LIMIT 1';
		$result = pupe_query($query);
		$kohderow = mysql_fetch_assoc($result);

		if (!empty($kohderow)) {
			return $kohderow['tunnus'];
		}

		return 0;
	}

}