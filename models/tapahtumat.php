<?php

/**
 * Laskun tapahtumat
 *
 */
class Tapahtumat {

    /**
     * Luo tapahtuman kun k�ytt�j� muokkaa ty�m��r�yst�
     */
    public static function lasku($tyomaarayksen_numero) {
        global $kukarow;

        // Virheentarkistus
        $errors = array();
        if ($kukarow['kuka'] == '') {
            $errors[] = "K�ytt�j�� ei l�ydy";
        }
        if ($kukarow['yhtio'] == '') {
            $errors[] = "Yhti�t� ei l�ydy";
        }

        // Lis�t��n tapahtuma
        if (empty($errors)) {
            $query = "INSERT INTO laskun_tapahtumat
                    SET yhtio = '{$kukarow['yhtio']}',
                    tyomaarays_numero = '$tyomaarayksen_numero',
                    muuttaja = '{$kukarow['kuka']}',
                    muutospvm = now()";
            pupe_query($query);
            return true;
        }
        else {
            foreach($errors as $error) {
                echo $error."<br>";
            }
            return false;
        }
    }
}