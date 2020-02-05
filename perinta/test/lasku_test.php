<?php
require "../../inc/salasanat.php";

$link = mysql_connect($dbhost, $dbuser, $dbpass);
mysql_select_db($dbkanta);

function pupe_query($query) {
    $resp = mysql_query($query);
    if($resp === false) {
        echo mysql_error();
    }
    return $resp;
}

require_once "../../../pupesoft/perinta/sql/kantaoperaatiot.php";
require_once "../../../pupesoft/perinta/malli/lasku.php";

class LaskuTest extends PHPUnit_Framework_TestCase
{
    protected $laskuTunnus1;
    protected $laskuMaksettu;
    protected $laskuTunnus2;
    private $lasku1;
    private $lasku2;

    protected function setUp() {
        global $argv, $argc;
        $this->assertGreaterThan(3, $argc, 'Invalid parameters.');
        $this->laskuTunnus1 = $argv[2];
        $this->laskuTunnus2 = $argv[3];
        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $this->laskuMaksettu = $rivi['lasku_maksettu'];
        echo $this->laskuMaksettu;
    }

    private function haeLasku1() {
        $laskut = Lasku::haeLaskutTunnuksilla(0, array(intval($this->laskuTunnus1)));
        Lasku::haeVipit($laskut);
        Lasku::haeKarhukierrokset($laskut);
        $this->assertEquals(count($laskut), 1);
        $this->lasku1 = $laskut[0];
    }


    private function haeLasku2() {
        $laskut = Lasku::haeLaskutTunnuksilla(0, array(intval($this->laskuTunnus2)));
        Lasku::haeVipit($laskut);
        Lasku::haeKarhukierrokset($laskut);
        $this->assertEquals(count($laskut), 1);
        $this->lasku2 = $laskut[0];
    }


    public function testPerintaLuonti() {
        echo "Testataan perinnän luontia";
        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $this->assertNull($rivi["perinta_tekija"]);
        $this->assertEquals($rivi["perinta_summa"], 0.0);
        $this->assertEquals($rivi["perinta_maksettu"], 0.0);
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'eiperinnassa');
        $this->assertNull($rivi["perinta_luonti"]);
        $this->assertNull($rivi["perinta_paivitys"]);
        $this->assertNull($rivi["perinta_siirto"]);
        $this->lasku1->viePerintaan();

        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $this->assertInternalType("string", $rivi["perinta_tekija"]);
        $this->assertEquals($rivi["perinta_tekija"], '0');
        $this->assertEquals($rivi["perinta_summa"], round($rivi['lasku_summa']-$rivi['lasku_maksettu'], 2));
        $this->assertEquals($rivi["perinta_maksettu"], 0.0);
        $this->assertInternalType("string", $rivi["perinta_luonti"]);
        $this->assertInternalType("string", $rivi["perinta_paivitys"]);
        $this->assertEquals($rivi["perinta_siirto"], '0000-00-00 00:00:00');
        $this->assertInternalType("string", $rivi["perinta_siirto"]);
        $this->assertEquals($rivi["perinta_siirto"], '0000-00-00 00:00:00');
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'luotu');
    }

    public function testPerintaanVietavatLaskut() {
        echo "Testataan perintään vientiä";
        $laskut = Lasku::haePerintaanVietavatLaskut(0);
        $this->assertEquals(count($laskut['uudet']), 1);
        $rivi = $laskut['uudet'][0]->annaRivi();
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'luotu');
        $this->assertEquals($rivi["lasku_tunnus"], intval($this->laskuTunnus1));
        $this->assertEquals($rivi["perinta_toimeksiantotunnus"], '1');
    }

    public function testVietyPerintaan() {
        $this->haeLasku1();
        $this->lasku1->vietyPerintaan();

        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'perinnassa');
    }

    /* Not relevant anymore
    public function testPerintaanVietavatLaskutVanha() {
        $this->haeLasku2();
        $rivi = $this->lasku2->annaRivi();
        $this->assertNull($rivi["perinta_tekija"]);
        $this->assertEquals($rivi["perinta_summa"], 0.0);
        $this->assertEquals($rivi["perinta_maksettu"], 0.0);
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'eiperinnassa');
        $this->assertNull($rivi["perinta_luonti"]);
        $this->assertNull($rivi["perinta_paivitys"]);
        $this->assertNull($rivi["perinta_siirto"]);

        $this->lasku2->viePerintaan();


        $laskut = Lasku::haePerintaanVietavatLaskut(0);
        $this->assertEquals(count($laskut['vanhat']), 1);
        $rivi = $laskut['vanhat'][0]->annaRivi();
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'luotu');
        $this->assertEquals($rivi["lasku_tunnus"], intval($this->laskuTunnus2));
        $this->assertEquals($rivi["perinta_toimeksiantotunnus"], '1');
    }
    */

    public function testMuutoshistoria() {
        echo "Testataan muutoshistoriaa";
        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $lasku = $this->lasku1;

        $lasku->lisaaMuutoshistoria('luonti');
        $lasku->lisaaMuutoshistoria('muutos');
        $lasku->lisaaMuutoshistoria('peruutus');

        $vastaus = pupe_query("SELECT * FROM perinta_muutoshistoria WHERE perinta_muutoshistoria.lasku_tunnus=".$this->laskuTunnus1." ORDER BY id ASC");
        $this->assertEquals(mysql_num_rows($vastaus), 3);

        $taulu = array();
        while($rivi2 = mysql_fetch_assoc($vastaus)) {
            echo "ASD";
            array_push($taulu, $rivi2);
        }

        $this->assertEquals($taulu[0]['tyyppi'],'luonti');
        $this->assertEquals($taulu[0]['lasku_tunnus'], $this->laskuTunnus1);
        $this->assertEquals($taulu[0]['summa'],round($rivi['lasku_summa']-$rivi['lasku_maksettu'], 2));
    }

    public function testPaivitys () {
        echo "Testataan päivitystä";
        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $this->laskuMaksettu = $rivi['lasku_maksettu'];

        $jaljella = round($rivi['lasku_summa'] - $rivi['lasku_maksettu'] + $this->laskuMaksettu, 2);

        pupe_query("update lasku set saldo_maksettu=" . $jaljella .
            " where tunnus=".$this->laskuTunnus1);

        $this->haeLasku1();
        $this->lasku1->paivitettava();

        $laskut = Lasku::haePaivitettavatLaskut(0);
        $this->assertEquals(count($laskut), 1);
        $rivi = $laskut[0]->annaRivi();
        $lasku = $laskut[0];

        $this->assertInternalType("string", $rivi["perinta_paivitettava"]);
        $this->assertEquals($rivi["perinta_paivitettava"], '1');

        $lasku->paivitaPerintaSumma(0.0);
        $lasku->kuittaaPaivitys();

        $this->haeLasku1();
        $rivi = $this->lasku1->annaRivi();
        $lasku = $this->lasku1;

        $this->assertEquals($rivi["perinta_summa"], 0.0);
        $this->assertInternalType("string", $rivi["perinta_tila"]);
        $this->assertEquals($rivi["perinta_tila"], 'valmis');

    }

    public function testPoistaPerintaRivi() {
        echo "Siivotaan merkinnät";
        pupe_query("DELETE FROM perinta WHERE perinta.lasku_tunnus=".$this->laskuTunnus1);
        pupe_query("DELETE FROM perinta WHERE perinta.lasku_tunnus=".$this->laskuTunnus2);
    }

    public function testPoistaMuutoshistoriaRivit() {
        pupe_query("DELETE FROM perinta_muutoshistoria WHERE perinta_muutoshistoria.lasku_tunnus=".$this->laskuTunnus1);
    }

    public function testPalautaMaksettu() {
        echo "update lasku set saldo_maksettu=".$this->laskuMaksettu." where tunnus=".$this->laskuTunnus1;
        pupe_query("update lasku set saldo_maksettu=".$this->laskuMaksettu." where tunnus=".$this->laskuTunnus1);
    }
}
?>
