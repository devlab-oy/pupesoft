<?php
error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED);
ini_set("display_errors", 1);

require "../../inc/salasanat.php";

require "../../inc/functions.inc";
require_once "../ajax/perinta_ajax.php";
require_once "../sql/kantaoperaatiot.php";

$sessio = (isset($_COOKIE["pupesoft_session"]) and $_COOKIE["pupesoft_session"] != "deleted") ? $_COOKIE["pupesoft_session"] : "";
$phpnimi = basename($_SERVER["SCRIPT_NAME"]);

$ajax = new PerintaAjax();
$kukarow = $ajax->haeKukarow($sessio);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!$ajax->tarkistaOikeudet($phpnimi)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $ajax->tulostaVirhe();
        die();
    }

    if(!$ajax->suoritaPost($kukarow['tunnus'], json_decode(file_get_contents('php://input'), true))) {
        header("HTTP/1.0 400 Bad Request", 400);
        $ajax->tulostaVirhe();
        die();
    }

    header("HTTP/1.0 200 OK");
    $ajax->tulostaVastaus();
    die();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if(!$ajax->tarkistaOikeudet($phpnimi)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $ajax->tulostaVirhe();
        die();
    }

    if(!$ajax->suoritaGet($kukarow['tunnus'], $_GET)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $ajax->tulostaVirhe();
        die();
    }
    header("HTTP/1.0 200 OK", 200);
    $ajax->tulostaVastaus();
    die();
}
?>
