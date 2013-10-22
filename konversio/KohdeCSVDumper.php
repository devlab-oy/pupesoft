<?php

require_once('CSVDumper.php');

class KohdeCSVDumper extends CSVDumper {

	protected $unique_values = array();

	public function __construct($kukarow) {
		parent::__construct($kukarow);

		$konversio_array = array(
			'asiakas'	 => 'ASIAKAS',
			//'nimi'		 => 'LINJA', Perl-skripteistä lunttasin, että näin pitäis olla, mutta aineistoa kun kattoo niin ei ehkä.
			'nimi'		 => 'NIMI',
			'nimitark'	 => 'KOODI',
			'osoite'	 => 'KATUOS',
			'postitp'	 => 'POSTIOS',
			'postino'	 => 'LISATIETO',
			'puhelin'	 => 'PUHELIN2',
			'fax'		 => 'FAX',
			'email'		 => 'LISATIETO2',
			'yhteyshlo'	 => 'YHTHENK2',
			'kommentti'	 => 'PUHELIN1',
		);
		$required_fields = array(
			'asiakas',
			'nimi'
		);
		$columns_to_be_utf8_decoded = array(
			'nimi',
			'nimitark',
			'osoite',
			'postitp',
			'yhteyshlo',
			'kommentti',
		);

		$this->setFilepath("/tmp/turvanasi_kohde.csv");
		$this->setSeparator(';');
		$this->setKonversioArray($konversio_array);


		$this->setRequiredFields($required_fields);
		$this->setColumnsToBeUtf8Decoded($columns_to_be_utf8_decoded);
		$this->setTable('kohde');
	}

	protected function konvertoi_rivit() {
		$progressbar = new ProgressBar(t('Konvertoidaan rivit'));
		$progressbar->initialize(count($this->rivit));

		foreach ($this->rivit as $index => &$rivi) {
			$rivi = $this->konvertoi_rivi($rivi, $index + 2);
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
						$this->errors[$index][] = t('Asiakasta')." <b>{$rivi['ASIAKAS']} ".utf8_decode($rivi['NIMI'])."</b> ".t('ei löydy');
					}
					$rivi_temp[$konvertoitu_header] = $asiakas_tunnus;
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
				$this->errors[$index][] = t('Pakollinen kenttä')." <b>$required_field</b> ".t('puuttuu');
				$valid = false;
			}
			else if ($required_field == 'asiakas' and $rivi[$required_field] == 0) {
//			Asiakkaan validointi tapahtuu konvertoi_rivi funktiossa @TODO konvertoi_rivit() järjestys pitää refaktoroida
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