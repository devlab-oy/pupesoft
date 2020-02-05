<?php

if ($tee == "rivi_loop") {

  if ($table_mysql == 'autoid_lisatieto' and $otsikko == 'AUTOID_LUKITTU' and isset($autoid_liitos) and $autoid_liitos == '') {
    $taulunrivit[$taulu][$eriviindex][$r] = 1;
  }

  if ($table_mysql == 'autoid_lisatieto' and $otsikko == 'TYPNR' and $taulunrivit[$taulu][$eriviindex][$r] != '' and !in_array($eriviindex, $lisatyt_indeksit) and isset($autoid_liitos) and $autoid_liitos == 'malli') {

    $query_chk = "SELECT DISTINCT ktypnr
                  FROM td_pc
                  WHERE kmodnr = '{$taulunrivit[$taulu][$eriviindex][$r]}'";
    $loop_res = pupe_query($query_chk);

    $eka_loop = true;

    while ($loop_row = mysql_fetch_assoc($loop_res)) {

      $query_chk = "SELECT autoid_lukittu
                    FROM autoid_lisatieto
                    WHERE yhtio = '{$kukarow['yhtio']}'
                    AND typnr   = '{$loop_row['ktypnr']}'";
      $chk_res = pupe_query($query_chk);

      while ($chk_row = mysql_fetch_assoc($chk_res)) if ($chk_row['autoid_lukittu']) continue 2;

        $_arr = $taulunrivit[$taulu][$eriviindex];
      $_arr[$r] = $loop_row['ktypnr'];

      if ($eka_loop) {
        $taulunrivit[$taulu][$eriviindex] = $_arr;
        $eka_loop = false;
        $lisatyt_indeksit[] = $eriviindex;
      }
      else {
        $taulunrivit[$taulu][] = $_arr;
        $lisatyt_indeksit[] = count($taulunrivit[$taulu])-1;

        $rivimaara++;
      }
    }
  }
}

if ($tee == "pre_rivi_loop") {

  if ($table_mysql == 'autoid_lisatieto' and $taulunotsikot[$taulu][$j] == 'TYPNR' and $taulunrivit[$taulu][$eriviindex][$j] != '' and isset($autoid_liitos) and $autoid_liitos == 'malli') {

    $query_chk = "SELECT ktypnr
                  FROM td_pc
                  WHERE kmodnr = '{$taulunrivit[$taulu][$eriviindex][$j]}'
                  ORDER BY ktypnr ASC
                  LIMIT 1";
    $loop_res = pupe_query($query_chk);

    if (mysql_num_rows($loop_res) == 1) {
      $loop_row = mysql_fetch_assoc($loop_res);
      $valinta .= " AND typnr = '{$loop_row['ktypnr']}' ";
    }

  }
}
