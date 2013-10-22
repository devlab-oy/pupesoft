<?php

require_once('CSVDumper.php');

class AsiakasalennusCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'asiakas'	 => 'ASIAKAS',
			'ryhma'		 => 'TUNNUS',
			'alennus'	 => 'PROS',
		);
		$required_fields = array(
			'asiakas',
			'ryhma',
			'alennus'
		);
		$columns_to_be_utf8_decoded = array();

		$this->setFilepath("/tmp/turvanasi_asiakasalennus.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('asiakasalennus');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi, $index);
			$rivi = $this->decode_to_utf8($rivi);
			$rivi = $this->lisaa_pakolliset_kentat($rivi);

			//index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
			$valid = $this->validoi_rivi($rivi, $index + 2);

			if (!$valid) {
				unset($this->rivit[$index]);
			}

			$progressbar->increase();
		}
	}

	protected function konvertoi_rivi($rivi, $index) {
		$rivi_temp = array();

		foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
			if (array_key_exists($csv_header, $rivi)) {
				if ($konvertoitu_header == 'asiakas') {
					$asiakas_tunnus = $this->hae_asiakas_tunnus($rivi['ASIAKAS']);
					if ($asiakas_tunnus == 0) {
						$this->errors[$index][] = t('Asiakasta')." <b>{$rivi['ASIAKAS']}</b> ".t('ei löydy');
					}
					$rivi_temp[$konvertoitu_header] = $asiakas_tunnus;
				}
				else if ($konvertoitu_header == 'ryhma') {
					//Oletetaan, että solusta löytyy vain yksi luku jolloin siihen voidaan viitata 0 indeksillä
					$matches = array();
					preg_match('/\d+/', $rivi[$csv_header], $matches);
					$rivi_temp[$konvertoitu_header] = $matches[0];
				}
				else {
					$rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
				}
			}
		}

		return $rivi_temp;
	}

	protected function validoi_rivi($rivi, $index) {
		$valid = true;
		foreach ($this->required_fields as $required_field) {
			if ($rivi[$required_field] == '' and $required_field != 'asiakas') {
				$this->errors[$index][] = t('Pakollinen kenttä')." $required_field ".t('puuttuu');
				$valid = false;
			}
			else if ($required_field == 'asiakas' and $rivi[$required_field] == 0) {
//				Asiakkaan validointi tapahtuu konvertoi_rivi funktiossa @TODO konvertoi_rivit() järjestys pitää refaktoroida
//				$this->errors[$index][] = t('Pakollinen kenttä')." $required_field ".t('asiakasta ei löydy');
				$valid = false;
			}
		}

		return $valid;
	}

	private function hae_asiakas_tunnus($asiakasnro) {
		$query = "	SELECT tunnus
					FROM asiakas
					WHERE yhtio = '{$this->kukarow['yhtio']}'
					AND asiakasnro = '{$asiakasnro}'";
		$result = pupe_query($query);
		$asiakasrow = mysql_fetch_assoc($result);

		if (!empty($asiakasrow)) {
			return $asiakasrow['tunnus'];
		}

		return 0;
	}

}