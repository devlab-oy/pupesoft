<?php

if (!function_exists("handle_cookie")) {
  /**
   * Handlaa cookien settauksen ja unsettauksen
   *
   * @param string  $nimi            Cookien ja inputin nimi
   * @param string  $formin_submitti Submitattavan formin submit-nappulan nimi
   */


  function handle_cookie($nimi, $formin_submitti) {
    if (isset($_POST[$formin_submitti]) && !isset($_POST[$nimi])) {
      setcookie($nimi, "", time() - 3600);
      unset($_REQUEST[$nimi]);
    }
    elseif (isset($_POST[$nimi]) and $_POST[$nimi] == "1") {
      setcookie($nimi, 1);
    }
  }
}
