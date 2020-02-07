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
require_once "../../../pupesoft/perinta/malli/suoritus.php";

class SuoritusTest extends PHPUnit_Framework_TestCase {
    protected $laskuViite;

    protected function setUp() {
        global $argv, $argc;
        $this->assertGreaterThan(2, $argc, 'Virheelliset parametrit.');
        $this->laskuViite = $argv[2];
    }

    /*
     * Testaa funktiot haeSuoritukset, annaRivi
     */
    public function testSuoritukset1() {
        $suoritukset = Suoritus::haeSuoritukset($this->laskuViite);
        $this->assertGreaterThan(0, count($suoritukset), 'Virhe tai parametrin viitteellä ei suorituksia.');
        foreach($suoritukset as $suoritus) {
            $suoritusRivi = $suoritus->annaRivi();

            $this->assertNotNull($suoritusRivi, "suoritusRivi on null" );
            //var_dump($suoritusRivi);
        }
    }

    /*
     * Testaa funktion haeKasittelemattomatSuoritukset, haeOhimaksuSuoritukset,
     * kasitelty, haeSuoritukset
     */
    public function testSuoritukset2() {
        $suoritukset = Suoritus::haeKasittelemattomatSuoritukset($this->laskuViite);
        $this->assertGreaterThan(0, count($suoritukset), 'Virhe tai parametrin viitteellä ei käsittelemättömiä suorituksia.');
        foreach($suoritukset as $suoritus) {
            $suoritusRivi = $suoritus->annaRivi();

            $this->assertNotNull($suoritusRivi, "suoritusRivi on null" );
            $this->assertFalse($suoritusRivi['kasitelty']);

            KantaOperaatiot::luoPerintaSuoritus($suoritusRivi['tunnus'], $suoritusRivi['viite']);
            //var_dump($suoritusRivi);
        }

        $suoritukset = Suoritus::haeOhimaksuSuoritukset($this->laskuViite);
        $this->assertGreaterThan(0, count($suoritukset), 'Ei ohimaksusuorituksia');
        foreach($suoritukset as $suoritus) {
            $suoritusRivi = $suoritus->annaRivi();

            $this->assertNotNull($suoritusRivi, "suoritusRivi on null" );
            $this->assertFalse($suoritusRivi['kasitelty']);

            $suoritus->kasitelty();
        }

        $suoritukset = Suoritus::haeSuoritukset($this->laskuViite);
        $this->assertGreaterThan(0, count($suoritukset), 'Ei suorituksia viitteellä');
        foreach($suoritukset as $suoritus) {
            $suoritusRivi = $suoritus->annaRivi();

            $this->assertNotNull($suoritusRivi, "suoritusRivi on null" );
            $this->assertTrue($suoritusRivi['kasitelty']);
        }
    }

    public function testSiivous() {
        pupe_query("DELETE FROM perinta_suoritus WHERE perinta_suoritus.lasku_viite=".$this->laskuViite);
    }
}
