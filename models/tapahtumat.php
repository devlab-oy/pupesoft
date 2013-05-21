<?php

/**
 * Laskun tapahtumat
 *
 */
class Tapahtumat {

    /**
     * Luo tapahtuman kun käyttäjä muokkaa työmääräystä
     */
    public static function lasku($tyomaarayksen_numero) {
        global $kukarow;

        // Virheentarkistus
        $errors = array();
        if ($kukarow['kuka'] == '') {
            $errors[] = "Käyttäjää ei löydy";
        }
        if ($kukarow['yhtio'] == '') {
            $errors[] = "Yhtiötä ei löydy";
        }

        // Lisätään tapahtuma
        if (empty($errors)) {
            $query = "INSERT INTO laskun_tapahtumat
                    SET yhtio = '{$kukarow['yhtio']}',
                    tyomaarays_numero = $tyomaarayksen_numero,
                    muuttaja = '{$kukarow['kuka']}',
                    muutettu = now()";
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