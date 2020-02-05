<?php
error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED);
ini_set("display_errors", 1);

require "../../inc/salasanat.php";

require_once "../integraatio/perinta_integraatio_api.php";

$api = new PerintaIntegraatioApi();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!$api->tarkistaToken($_GET)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $api->tulostaVirhe();
        die();
    } else if(!$api->suoritaPost($_GET, json_decode(file_get_contents('php://input'), true))) {
        header("HTTP/1.0 400 Bad Request", 400);
        $api->tulostaVirhe();
        die();
    }

    header("HTTP/1.0 200 OK", 200);
    $api->tulostaVastaus();
    die();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if(!$api->tarkistaToken($_GET)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $api->tulostaVirhe();
        die();
    } else if(!$api->suoritaGet($_GET)) {
        header("HTTP/1.0 400 Bad Request", 400);
        $api->tulostaVirhe();
        die();
    }
    header("HTTP/1.0 200 OK", 200);
    $api->tulostaVastaus();
    die();
}

header("HTTP/1.0 400 Bad Request");
?>
