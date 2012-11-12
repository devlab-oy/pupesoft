<?php

require_once '../inc/pupeExcel.inc';

class vuosisopimus_asiakkaat_excel {

	protected $excel;
	protected $excel_rivi = 0;
	protected $excel_sarake = 0;
	protected $asiakas = array();
	protected $tilaus_rivit = array();
	protected $summat_tuoteryhmittain = array();
	protected $summat_osastoittain = array();
	protected $rajaus_paivat = array();
	protected $yhtiorow;
	protected $kieli;

	function __construct() {
		$this->excel = new pupeExcel();
	}

	public function generoi() {
		$this->alku();

		$this->add_tyhja_rivi(4);

		$this->rivit_osastoittain();
		$this->move_seuraavalle_riville();
		$this->summat('osastoittain');

		$this->move_seuraavalle_riville();
		$this->move_seuraavalle_riville();
		$this->move_seuraavalle_riville();

		$this->rivit_tuoteryhmittain();
		$this->move_seuraavalle_riville();

		return $this->excel->close();
	}

	private function alku() {
		$this->write_solu($this->yhtiorow['nimi']);
		$this->add_tyhja_kolumni(3);
		$this->write_solu(t('OSTOSEURANTA', $this->kieli));

		$this->add_tyhja_rivi(2);

		$this->write_solu($this->asiakas['ytunnus']);
		$this->add_tyhja_kolumni(3);
		$this->write_solu(t('Hyvä yhteistyökumppanimme,', $this->kieli));

		$this->move_seuraavalle_riville();

		$this->write_solu($this->asiakas['nimi']);

		$this->move_seuraavalle_riville();

		$this->write_solu($this->asiakas['nimitark']);
		$this->add_tyhja_kolumni(3);
		$this->write_solu(t('Ohessa tämänhetkinen ostotilanteenne.', $this->kieli));

		$this->move_seuraavalle_riville();

		$this->write_solu($this->asiakas['osoite']);

		$this->move_seuraavalle_riville();

		$this->write_solu($this->asiakas['postino'].' '.$this->asiakas['postitp']);
		$this->add_tyhja_kolumni(3);
		$this->write_solu(t('Kunnioittaen', $this->kieli).','.$this->yhtiorow['nimi']);

		$this->move_seuraavalle_riville();

		$this->write_solu($this->asiakas['maa']);
	}

	private function rivit_osastoittain() {
		$this->write_solu(t('Rajaus:', $this->kieli));
		$this->write_solu($this->rajaus_paivat['alkupaiva'] . ' - ' . $this->rajaus_paivat['loppupaiva']);
		$this->move_seuraavalle_riville();
		$this->print_headers('osasto');
		$this->move_seuraavalle_riville();

		foreach ($this->tilaus_rivit['osastoittain'] as $rivi) {
			$this->print_rivi($rivi, 'osastoittain');
			$this->move_seuraavalle_riville();
		}
	}

	private function rivit_tuoteryhmittain() {
		$this->write_solu('Rajaus:');
		$this->write_solu($this->rajaus_paivat['alkupaiva'] . ' - ' . $this->rajaus_paivat['loppupaiva']);
		$this->move_seuraavalle_riville();
		$this->print_headers('tuoteryhma');
		$this->move_seuraavalle_riville();

		$edellinen_rivi = '';
		foreach ($this->tilaus_rivit['tuoteryhmittain'] as $rivi) {
			if ($edellinen_rivi != $rivi['osasto']) {
				$this->move_seuraavalle_riville();
				$this->move_seuraavalle_riville();
				$this->write_solu("Osasto {$rivi['osasto']} - ".$rivi['osasto_nimitys']);
				$this->move_seuraavalle_riville();
			}
			$this->print_rivi($rivi, 'tuoteryhmittain');
			$this->move_seuraavalle_riville();
			$edellinen_rivi = $rivi['osasto'];
		}
	}

	private function print_headers($tyyppi = '') {
		if($tyyppi == 'tuoteryhma') {
			$this->write_solu(t('Tuoteryhma:', $this->kieli));
		}
		else{
			$this->write_solu(t('Osasto:', $this->kieli));
		}
		
		$this->add_tyhja_kolumni(2);
		$this->write_solu(t('Kpl', $this->kieli) . ' 2012');
		$this->write_solu(t('Kpl', $this->kieli) . ' 2011');
		$this->write_solu(t('Osasto', $this->kieli) . ' 2012');
		$this->write_solu(t('Osasto', $this->kieli) . ' 2011');
		$this->write_solu(t('Indeksi', $this->kieli));
	}

	private function print_rivi(array $rivi, $tyyppi) {
		$indeksi = 0;
		if ($rivi['ed'] != 0) {
			$indeksi = round($rivi['va'] / $rivi['ed'], 2) * 100;
		}

		if($tyyppi == 'osastoittain') {
			$numero = $rivi['osasto'];
			$nimitys = $rivi['osasto_nimitys'];
		}
		else {
			$numero = $rivi['try'];
			$nimitys = $rivi['tuoteryhma_nimitys'];
		}

		$this->write_solu($numero . ' - ' . $nimitys);
		$this->add_tyhja_kolumni(2);
		$this->write_numero_solu($rivi['kplva']);
		$this->write_numero_solu($rivi['kpled']);
		$this->write_numero_solu($rivi['va']);
		$this->write_numero_solu($rivi['ed']);
		$this->write_numero_solu($indeksi);
	}

	private function summat($tyyppi = '') {
		if ($tyyppi == 'tuoteryhmittain') {
			$temp = $this->get_summat_tuoteryhmittain();
		}
		else if ($tyyppi == 'osastoittain') {
			$temp = $this->get_summat_osastoittain();
		}

		$sum_indeksi = 0;
		if ($temp['sumed'] != 0) {
			$sum_indeksi = round($temp['sumva'] / $temp['sumed'], 2) * 100;
		}

		$this->add_tyhja_kolumni(2);
		$this->write_solu(t('Yhteensä:', $this->kieli));
		$this->write_numero_solu($temp['sumkplva']);
		$this->write_numero_solu($temp['sumkpled']);
		$this->write_numero_solu($temp['sumva']);
		$this->write_numero_solu($temp['sumed']);
		$this->write_numero_solu($sum_indeksi);

	}

	private function write_solu($data) {
		//tämä funktio kirjoittaa tämän hetkiselle riville soluun datan ja siirtyy seuraavaan soluun samalla rivillä
		$this->excel->write($this->excel_rivi, $this->excel_sarake, $data);
		$this->excel_sarake++;
	}

	private function write_numero_solu($numero) {
		$this->excel->writeNumber($this->excel_rivi, $this->excel_sarake, $numero);
		$this->excel_sarake++;
	}

	private function write_aika_solu($aika) {
		$this->excel->writeDate($this->excel_rivi, $this->excel_sarake, $aika);
		$this->excel_sarake++;
	}

	private function add_tyhja_kolumni($kolumni_numero = 1) {
		$this->excel_sarake += $kolumni_numero;
	}

	private function add_tyhja_rivi($rivi_numero = 1) {
		$this->excel_rivi += $rivi_numero;
		$this->excel_sarake = 0;
	}

	private function move_kolumniin($kolumni_numero) {
		$this->excel_sarake = $kolumni_numero;
	}

	private function move_seuraavalle_riville() {
		$this->excel_rivi++;
		$this->excel_sarake = 0;
	}

	public function get_asiakas() {
		return $this->asiakas;
	}

	public function set_asiakas(array $asiakas) {
		$this->asiakas = $asiakas;
	}

	public function get_tilaus_rivit() {
		return $this->tilaus_rivit;
	}

	public function set_tilausrivit(array $tilausrivit) {
		$this->tilaus_rivit = $tilausrivit;
	}

	public function get_summat_tuoteryhmittain() {
		return $this->summat_tuoteryhmittain;
	}

	public function set_summat_tuoteryhmittain(array $summat_tuoteryhmittain) {
		$this->summat_tuoteryhmittain = $summat_tuoteryhmittain;
	}

	public function get_summat_osastoittain() {
		return $this->summat_osastoittain;
	}

	public function set_summat_osastoittain(array $summat_osastoittain) {
		$this->summat_osastoittain = $summat_osastoittain;
	}

	public function get_rajaus_paivat() {
		return $this->rajaus_paivat;
	}

	public function set_rajaus_paivat(array $rajaus_paivat) {
		$this->rajaus_paivat = $rajaus_paivat;
	}

	public function get_yhtiorow() {
		return $this->yhtiorow;
	}

	public function set_yhtiorow(array $yhtiorow) {
		$this->yhtiorow = $yhtiorow;
	}

	public function get_kieli() {
		return $this->kieli;
	}

	public function set_kieli($kieli) {
		$this->kieli = $kieli;
	}

}

?>
