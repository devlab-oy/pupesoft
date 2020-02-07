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
require_once "../../../pupesoft/perinta/malli/tilitys.php";

class TilitysTest extends PHPUnit_Framework_TestCase {
    //protected $laskuViite;

    protected function setUp() {
        global $argv, $argc;
        //$this->assertGreaterThan(2, $argc, 'Virheelliset parametrit.');
        //$this->laskuViite = $argv[2];
    }

    /*
     * Testaa funktiot haeSuoritukset, annaRivi
     */
    public function testTilitykset1() {
        $rivi = array(
            'viite' => 123,
            'summa' => 50.87,
            'maksaja' => 'TESTI',
            'kirjauspaiva' => '2015-09-15',
            'maksupaiva' => '2015-09-14'
        );

        $tilitys = Tilitys::kirjaaTilitys($rivi);

        $this->assertTrue(KantaOperaatiot::loytyykoTilitysrivi($rivi['viite'], $rivi['summa'], $rivi['kirjauspaiva'], $rivi['maksupaiva']));
    }

    public function testSiivous() {
        pupe_query("DELETE FROM perinta_tilitys WHERE perinta_tilitys.lasku_viite='123'");
    }
}
