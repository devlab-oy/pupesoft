<?php

if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {
  $no_head = "yes";
}

require 'inc/parametrit.inc';

$saamuokata = false;
$saamuokataliitoksia = false;

if ($oikeurow['paivitys'] == '1') {
  $saamuokata = true;
}

if (tarkista_oikeus('yllapito.php', 'puun_alkio', 1)) {
  $saamuokataliitoksia = true;
}

if (!isset($mista) and !empty($laji)) {
  $mista = $laji;
}
elseif (!isset($mista)) {
  $mista = "";
}

if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {

  if ($tee == 'hae_laji') {

    $val = '';

    if (isset($avainsanan_tunnus)) {
      $avainsanan_tunnus = (int) $avainsanan_tunnus;

      $query = "SELECT *
                FROM dynaaminen_puu_avainsanat
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus  = '{$avainsanan_tunnus}'";
      $res = pupe_query($query);
      $row = mysql_fetch_assoc($res);
      $val = $row['tarkenne'];
      $lang = $row['kieli'];

      echo "<input type='hidden' id='avainsanan_tunnus' name='avainsanan_tunnus' value='{$avainsanan_tunnus}' />";
    }

    echo "<input type='text' id='keywords_value' name='keywords_value' value='{$val}' /><br>";

    echo "<select name='lang' id='keywords_language' name='keywords_language'>";

    foreach ($GLOBALS["sanakirja_kielet"] as $sanakirja_kieli => $sanakirja_kieli_nimi) {
      $sel = $lang == $sanakirja_kieli ? "selected" : '';

      echo "<option value='{$sanakirja_kieli}' {$sel}>".t($sanakirja_kieli_nimi)."</option>";
    }

    echo "</select>";

    exit;
  }
  elseif ($tee == 'hae_avainsana_lista') {
    $query = "SELECT *
              FROM dynaaminen_puu_avainsanat
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$nodeid}'
              AND laji         = '{$toim}'
              ORDER BY laji, avainsana, tarkenne";
    $dpavainsanat_res = pupe_query($query);

    while ($dp_row = mysql_fetch_assoc($dpavainsanat_res)) {
      $_selitetark = t_avainsana("DPAVAINSANALAJI", "", "and avainsana.selitetark = '{$dp_row['avainsana']}' and avainsana.selitetark_2 = '{$toim}'", "", "", "selitetark");

      if ($saamuokata) {
        echo "<a class='remove_keyword' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/stop.png' alt='", t('Poista'), "'/></a>&nbsp;&nbsp;";
        echo "<a style='float: right;' class='edit_keyword' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t('Muokkaa'), "'/></a>";
        echo "<input type='hidden' class='edit_keyword_class' id='{$dp_row['tunnus']}_class' value='{$dp_row['avainsana']}' />";
      }

      echo "<span style='font-weight: bold;'>{$_selitetark}</span> &raquo; $dp_row[tarkenne] $dp_row[kieli]";


      echo "<br />";
    }

    exit;
  }
  elseif ($tee == 'hae_liite_lista') {
    $query = "SELECT *
              FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND liitos = 'dynaaminen_puu'
              AND liitostunnus = '$nodeid'";
    $dpliitteet_res = pupe_query($query);

    while ($dp_row = mysql_fetch_assoc($dpliitteet_res)) {
      if ($saamuokata) {
        echo "<a class='remove_attachment' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/stop.png' alt='", t('Poista'), "'/></a>&nbsp;&nbsp;";
        echo "<a style='float: right;' class='edit_attachment' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t('Muokkaa'), "'/></a>";
        echo "<input type='hidden' class='edit_attachment_st' id='{$dp_row['tunnus']}_st' value='{$dp_row['selite']}' />";
        echo "<input type='hidden' class='edit_attachment_kt' id='{$dp_row['tunnus']}_kt' value='{$dp_row['kayttotarkoitus']}' />";
      }
      $lkt = t_avainsana("LITETY", '', "and selite = '{$dp_row['kayttotarkoitus']}'", '', '', "selitetark");
      echo "<span style='font-weight: bold;'>{$dp_row['selite']} / $lkt: ".js_openUrlNewWindow("{$palvelin2}view.php?id={$dp_row['tunnus']}", t('N‰yt‰ liite'), NULL, 800, 600)."</span>";
      echo "<br />";
    }

    exit;
  }
  elseif ($tee == 'poista_avainsana' and !empty($avainsanan_tunnus)) {
    $avainsanan_tunnus = (int) $avainsanan_tunnus;

    $query = "DELETE FROM dynaaminen_puu_avainsanat
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$avainsanan_tunnus}'";
    $delres = pupe_query($query);
    exit;
  }
  elseif ($tee == 'poista_liite' and !empty($liitteen_tunnus)) {
    $liitteen_tunnus = (int) $liitteen_tunnus;

    $query = "DELETE FROM liitetiedostot
              WHERE yhtio = '{$kukarow['yhtio']}'
              AND tunnus  = '{$liitteen_tunnus}'";
    $delres = pupe_query($query);
    exit;
  }

  // tarvii romplata tekstimuuttujia kun tehdaan jQuery.ajaxin kanssa
  $uusi_nimi  = (isset($uusi_nimi)) ? utf8_decode($uusi_nimi): "";
  $uusi_nimi_en  = (isset($uusi_nimi_en)) ? utf8_decode($uusi_nimi_en): "";
  $uusi_koodi  = (isset($uusi_koodi)) ? utf8_decode($uusi_koodi): "";

  function getnoderow($toim, $nodeid) {
    global $yhtiorow, $kukarow;

    $qu = "SELECT *
           FROM dynaaminen_puu
           WHERE dynaaminen_puu.yhtio = '{$yhtiorow['yhtio']}'
           AND dynaaminen_puu.laji    = '{$toim}'
           AND dynaaminen_puu.tunnus  = '{$nodeid}'";
    $re = pupe_query($qu);
    $numres = mysql_num_rows($re);

    if ($numres > 0) {
      $row = mysql_fetch_assoc($re);
      return $row;
    }
    else {
      return false;
    }
  }

  // nodeid tarvitaan aina
  if (isset($nodeid) and isset($toim) and trim($toim) != "") {

    $noderow = getnoderow($toim, $nodeid);

    // muokkaustoiminnot
    if (isset($tee) and $tee != '') {

      if ($saamuokata and !in_array($tee, array('addtotree', 'removefromtree'))) {

        // Siirret‰‰n haaraa j‰rjestyksess‰ ylˆs tai alas
        if ($tee == 'ylos' or $tee == 'alas') {
          $src['lft'] = $noderow['lft'];
          $src['rgt'] = $noderow['rgt'];

          // $tee:ssa on suunta mihin siirret‰‰n
          $kohde = SiirraTaso($toim, $src, $tee);
        }
        elseif ($tee == 'lisaa' and isset($uusi_nimi) and trim($uusi_nimi) != "") {
          // lisataan lapsitaso
          $uusi_koodi = $uusi_koodi == '' ? '0' : $uusi_koodi;

          $uusirivi = LisaaLapsi($toim, $noderow['lft'], $noderow['syvyys'], $uusi_koodi, $uusi_nimi, $uusi_nimi_en);
          paivitapuunsyvyys($toim);

          echo "<input type='hidden' id='newid' value='{$uusirivi['tunnus']}' />
              <input type='hidden' id='newcode' value='{$uusirivi['koodi']}' />";
        }
        elseif ($tee == 'lisaa_avainsana' and !empty($laji) and !empty($avainsana) and !empty($toim)) {

          if (!empty($avainsanan_tunnus)) {
            $avainsanan_tunnus = (int) $avainsanan_tunnus;

            $query = "UPDATE dynaaminen_puu_avainsanat SET
                      avainsana   = '{$laji}',
                      tarkenne    = '{$avainsana}',
                      kieli       = '{$kieli}'
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$avainsanan_tunnus}'";
            $updres = pupe_query($query);
          }
          else {

            // Tarkistetaan onko avainsana jo tallennettu kantaa. Duplikaatteja ei sallita.
            $query = "SELECT tunnus
                      FROM dynaaminen_puu_avainsanat
                      WHERE yhtio      = '{$kukarow['yhtio']}'
                      AND liitostunnus = '{$nodeid}'
                      AND laji         = '{$toim}'
                      AND avainsana    = '{$laji}'
                      AND tarkenne     = '{$avainsana}'";
            $chk = pupe_query($query);

            if (mysql_num_rows($chk) == 0) {
              $query = "INSERT INTO dynaaminen_puu_avainsanat SET
                        yhtio        = '{$kukarow['yhtio']}',
                        liitostunnus = '{$nodeid}',
                        kieli        = '{$kieli}',
                        laji         = '{$toim}',
                        avainsana    = '{$laji}',
                        tarkenne     = '{$avainsana}',
                        muuttaja     = '{$kukarow['kuka']}',
                        laatija      = '{$kukarow['kuka']}',
                        luontiaika   = now(),
                        muutospvm    = now()";
              $insquery = pupe_query($query);
            }
          }

          exit;
        }
        elseif ($tee == 'lisaa_liite' and !empty($liite_selite) and !empty($toim) and empty($liitteen_tunnus)) {
          tallenna_liite("liite_data", "dynaaminen_puu", $nodeid, $liite_selite, $liite_kayttotarkoitus);
          exit;
        }
        elseif ($tee == 'lisaa_liite' and !empty($liite_selite) and !empty($toim) and !empty($liitteen_tunnus)) {
          $query = "UPDATE liitetiedostot SET
                    selite           = '{$liite_selite}',
                    kayttotarkoitus  = '{$liite_kayttotarkoitus}'
                    WHERE yhtio      = '{$kukarow['yhtio']}'
                    AND liitos       = 'dynaaminen_puu'
                    AND liitostunnus = '$nodeid'
                    AND tunnus       = '{$liitteen_tunnus}'";
          pupe_query($query);
          exit;
        }
        elseif ($tee == 'poista') {
          // poistaa ja upgradettaa alemmat lapset isommaksi.
          PoistaLapset($toim, $noderow['lft']);
          paivitapuunsyvyys($toim);
        }
        elseif ($tee == 'muokkaa' and isset($uusi_nimi) and trim($uusi_nimi) != "") {
          $uusi_koodi = $uusi_koodi == '' ? '0' : $uusi_koodi;
          paivitakat($toim, $uusi_koodi, $uusi_nimi, $nodeid, $uusi_nimi_en);

          echo "<input type='hidden' id='newcode' value='{$uusi_koodi}' />";
        }
        elseif ($tee == 'siirrataso' and isset($kohdetaso) and $kohdetaso != "") {
          // haetaan kohdenode
          $targetnoderow = getnoderow($toim, $kohdetaso);

          if ($targetnoderow != FALSE) {
            $src['lft'] = $noderow['lft'];
            $src['rgt'] = $noderow['rgt'];
            siirraOksa($toim, $src, $targetnoderow['rgt']);
            paivitapuunsyvyys($toim);
          }
        }

        // haetaan uudelleen paivittyneet
        $noderow = getnoderow($toim, $nodeid);
      }
      elseif ($saamuokataliitoksia) {

        if ($tee == 'addtotree') {
          TuotteenAlkiot($toim, $liitos, $nodeid, $kieli, $mista);
        }
        elseif ($tee == 'removefromtree') {
          $qu = "DELETE FROM puun_alkio
                 WHERE yhtio     = '{$yhtiorow["yhtio"]}'
                 AND laji        = '{$toim}'
                 AND liitos ='{$liitos}'
                 AND puun_tunnus = {$nodeid}";
          $re = pupe_query($qu);
        }
      }
      $tee = '';
    }

    if ($noderow == FALSE) {
      echo "<p>".t("Valitse uusi taso")."...</p>";
      exit;
    }

    if (!empty($noderow['nimi_en'])) {
      $_nimi_en = "<p><font class='message'>".t("Nimi en").":".$noderow['nimi_en']."<br />";
    }
    else {
      $_nimi_en = "";
    }

    echo "<h2 style='font-size: 20px'>".$noderow['nimi']."</h2><hr />
      {$_nimi_en}
        <font class='message'>".t("Koodi").":</font> ".$noderow['koodi']."<br />".
      "<font class='message'>".t("Tunnus").":</font> ".$noderow['tunnus']."<br />".
      " <font class='message'>".t("Syvyys").":</font> ".$noderow['syvyys']."<br />".
      " <font class='message'>lft / rgt:</font> ".$noderow['lft']." / ".$noderow['rgt'].
      "</p>";

    // " <font class='message'>".t("Toimittajan koodi").":</font> ".$noderow['toimittajan_koodi'].

    // tuotteet
    $qu = "SELECT count(*) lkm
           FROM puun_alkio
           WHERE yhtio     = '{$yhtiorow['yhtio']}'
           AND laji        = '{$toim}'
           AND puun_tunnus = '{$nodeid}'";
    $re = pupe_query($qu);
    $row = mysql_fetch_assoc($re);
    $own_items = $row['lkm'];

    // lapsitasojen tuotteet
    $qu = "SELECT
           count(distinct puu.tunnus) plkm,
           count(alkio.tunnus) lkm
           FROM dynaaminen_puu puu
           LEFT JOIN puun_alkio alkio ON (puu.yhtio = alkio.yhtio AND puu.laji=alkio.laji AND puu.tunnus = alkio.puun_tunnus)
           WHERE puu.yhtio = '{$yhtiorow['yhtio']}'
           AND puu.laji    = '{$toim}'
           AND puu.lft     > {$noderow['lft']}
           AND puu.rgt     < {$noderow['rgt']}";
    $re = pupe_query($qu);
    $row = mysql_fetch_assoc($re);

    $child_nodes = $row['plkm'];
    $child_items = $row['lkm'];

    echo "<p>";

    if ($child_nodes > 0) echo "<font class='message'>".t("Lapsitasoja").":</font>".$child_nodes."<br />";
    if ($own_items > 0) echo "<font class='message'>".t("Liitoksia").":</font> <a href='yllapito.php?toim=puun_alkio&laji={$toim}&haku[5]={$nodeid}' target='_blank'>".$own_items."</a><br />";
    if ($child_items > 0) echo "<font class='message'>".t("Liitoksia lapsitasoilla").":</font>".$child_items;

    echo "</p>";

    echo "<hr /><div id='editbuttons'>";
    if ($saamuokata) {
      echo "  <a href='#' id='showeditbox' id='muokkaa'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t('Muokkaa lapsikategoriaa'), "'/> ".t('Muokkaa tason tietoja')."</a><br /><br />
          <a href='#' class='editbtn' id='ylos'><img src='{$palvelin2}pics/lullacons/arrow-single-up-green.png' alt='", t('Siirr‰ ylˆsp‰in'), "'/> ".t('Siirr‰ tasoa ylˆsp‰in')."</a><br />
          <a href='#' class='editbtn' id='alas'><img src='{$palvelin2}pics/lullacons/arrow-single-down-green.png' alt='", t('Siirr‰ alasp‰in'), "'/> ".t('Siirr‰ tasoa alasp‰in')."</a><br /><br />";

      if ($child_nodes == 0) {
        echo "<a href='#' id='showmovebox'> <img src='{$palvelin2}pics/lullacons/arrow-single-right-green.png' alt='", t('Siirr‰ alatasoksi'), "'/> ".t('Siirr‰ oksa alatasoksi')."</a><br /><br />";
      }

      echo "<a href='#' id='showaddbox'><img src='{$palvelin2}pics/lullacons/add.png' alt='", t('Lis‰‰'), "'/>".t('Lis‰‰ uusi lapsitaso')."</a><br /><br />";

      // poistonappi aktiivinen vain jos ei ole liitoksia
      if ($own_items > 0 or $child_items > 0) {
        echo "<font style='info'>".t("Poistaminen ei ole mahdollista kun tasolla on liitoksia.")."</font>";
      }
      else {
        echo "<a href='#' class='editbtn' id='poista'><img src='{$palvelin2}pics/lullacons/stop.png' alt='", t('Poista'), "'/> ".t('Poista taso')."</a>";
      }
    }

    if ($saamuokataliitoksia) {
      // tarkistetaan onko jo liitetty
      $qu = "SELECT *
             FROM puun_alkio
             WHERE yhtio     = '{$yhtiorow["yhtio"]}'
             AND laji        = '{$toim}'
             AND liitos      = '{$liitos}'
             AND puun_tunnus = {$noderow["tunnus"]}";
      $re = pupe_query($qu);

      echo "<br /><br />";

      if (mysql_num_rows($re) > 0) {
        $row = mysql_fetch_assoc($re);
        echo "<a class='editbtn' id='removefromtree'>".t("Poista liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
      }
      else {
        echo "<a class='editbtn' id='addtotree'>".t("Tee liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
      }
    }
    echo "</div>";

    // tason siirtolaatikko
    echo "<div id='movebox' style='display: none'>
        <form id='moveform'>
        <fieldset>
          <legend style='font-weight: bold'>".t("Siirr‰ valitun tason alatasoksi")."</legend>
          <ul style='list-style:none; padding: 5px'>
            <li style='padding: 3px'>
              <label style='display: inline-block; width: 125px'>".t("Kohdetason tunnus")." <font class='error'>*</font></label>
              <input size='5' id='kohdetaso' autocomplete='off' />
            </li>
          </ul>
          <input type='submit' id='movesubmitbtn' value='".t("Siirr‰")."' />
          </form>
        </div>
        ";

    // tason muokkauslaatikko
    echo "<div id='nodebox' style='display: none'>
      <form id='tasoform'>
      <fieldset>
        <legend style='font-weight: bold' id='nodeboxtitle'></legend>
        <ul style='list-style:none; padding: 5px'>
          <li style='padding: 3px'>
            <label style='display: inline-block; width: 50px'>".t("Nimi")." <font class='error'>*</font></label>
            <input size='35' id='uusi_nimi' autocomplete='off' />
          </li>
          <li style='padding: 3px'>
            <label style='display: inline-block; width: 50px'>".t("Nimi en")." <font class='error'>*</font></label>
            <input size='35' id='uusi_nimi_en' autocomplete='off' />
          </li>
          <li style='padding: 3px'>
            <label style='display: inline-block; width: 50px'>".t("Koodi")."</label>
            <input size='35' id='uusi_koodi' autocomplete='off' />
          </li>
        </ul>
        <input type='hidden' id='tee' />
        <p style='display: none; color: red' id='nodeboxerr'>".t("Nimi tai koodi ei saa olla tyhj‰").".</p>
        <input type='submit' id='editsubmitbtn' value='".t("Tallenna")."' />
      </fieldset>
      </form>
    </div>";

?>
    <script language="javascript">
    var params = new Object();
    <?php
    echo "params['toim'] = '{$toim}';
        params['kieli'] = '{$kieli}';
       ";

    if ($saamuokata or $saamuokataliitoksia) {

      if (!empty($nodeid)) {
        echo "params['nodeid'] = {$nodeid};";
      }
      elseif (!empty($noderow['tunnus'])) {
        echo "params['nodeid']  = '{$noderow["tunnus"]}';";
      }

      echo "var nimi = '{$noderow["nimi"]}';";
      echo "var nimi_en = '{$noderow["nimi_en"]}';";
      echo "var koodi = '{$noderow["koodi"]}';";
      echo "params['liitos']  = '{$liitos}';";
?>

      jQuery(".editbtn").click(function(){
        params["tee"] = this.id;
        editNode(params);
        return false;
      });

      var nodebox      = jQuery("#nodebox");
      var movebox      = jQuery("#movebox");
      var addboxbutton  = jQuery("#showaddbox");
      var moveboxbutton  = jQuery("#showmovebox");
      var editboxbutton  = jQuery("#showeditbox");
      var nodeboxtitle  = jQuery("#nodeboxtitle");
      var nodeboxname    = jQuery("#uusi_nimi");
      var nodeboxname_en    = jQuery("#uusi_nimi_en");
      var nodeboxcode    = jQuery("#uusi_koodi");
      var tee        = jQuery("#tee");

      var nodebox_keywords    = jQuery("#nodebox_keywords");
      var nodebox_keywords_title  = jQuery("#nodebox_keywords_title");
      var addboxbutton_keywords  = jQuery("#showaddbox_keywords");

      var nodebox_attachments    = jQuery("#nodebox_attachments");
      var nodebox_attachments_title  = jQuery("#nodebox_attachments_title");
      var addboxbutton_attachments  = jQuery("#showaddbox_attachments");

      var keywords_category     = jQuery("#keywords_category");
      var keywords_value       = jQuery("#keywords_value");
      var keywords_language    = jQuery("#keywords_language");

      addboxbutton.click(function() {
        tee.val("lisaa");
        nodeboxtitle.html("Lis‰‰ taso");
        addboxbutton.replaceWith(nodebox);
        nodeboxname.val("").focus();
        nodeboxname_en.val("");
        nodebox.show();
        nodeboxcode.val("");
        return false;
      });

      addboxbutton_keywords.click(function() {
        nodebox_keywords_title.html("Lis‰‰ avainsana");
        addboxbutton_keywords.hide();
        addboxbutton_keywords.after(nodebox_keywords);
        jQuery('#keywords_value').hide();
        jQuery('#keywords_language').hide();
        nodebox_keywords.show();
        return false;
      });

      addboxbutton_attachments.click(function() {
        nodebox_attachments_title.html("Lis‰‰ liitetiedosto");
        addboxbutton_attachments.hide();
        addboxbutton_attachments.after(nodebox_attachments);
        jQuery('#attachments_value').hide();
        jQuery('#attachments_language').hide();
        nodebox_attachments.show();
        return false;
      });

      keywords_category.on('change', function() {

        if (jQuery('#avainsanan_tunnus').val() !== 'undefined') {
          params["tee"] = 'hae_laji';
          params["laji"] = jQuery("#keywords_category option:selected").html();

          editNode_keywords(params);
        }
      });

      moveboxbutton.click(function () {
        moveboxbutton.replaceWith(movebox);
        movebox.show();
        return false;
      });

      editboxbutton.click(function() {
        tee.val("muokkaa");
        nodeboxtitle.html("Muokkaa tasoa");
        editboxbutton.replaceWith(nodebox);
        nodebox.show();
        nodeboxname.val(nimi).focus();
        nodeboxname_en.val(nimi_en);
        nodeboxcode.val(koodi);
        return false;
      });

      jQuery("#tasoform").submit(function() {
        params["uusi_nimi"]    = jQuery("#uusi_nimi").val();
        params["uusi_nimi_en"]    = jQuery("#uusi_nimi_en").val();
        params["uusi_koodi"]  = jQuery("#uusi_koodi").val();
        params["tee"]      = jQuery("#tee").val();

        if (params["uusi_nimi"] == "") {
          jQuery("#nodeboxerr").show();
          return false;
        }

        editNode(params);

        return false;
      });

      jQuery("#keywordsform").live('submit', function() {
        params["laji"]       = jQuery("#keywords_category option:selected").html();
        params["avainsana"]  = jQuery("#keywords_value").val();
        params["kieli"]      = jQuery("#keywords_language").val();
        params["tee"]        = 'lisaa_avainsana';
        params["toim"]       = jQuery("#toim").val();

        if (jQuery("#keywords_category option:selected").val() == "" || params['avainsana'] == "") {
          jQuery("#nodebox_keywords_err").show();
          return false;
        }

        params['avainsanan_tunnus'] = jQuery("#avainsanan_tunnus") ? jQuery("#avainsanan_tunnus").val() : null;

        editNode_keywords(params);

        return false;
      });

      jQuery(".remove_keyword").live('click', function(e) {
        e.preventDefault();

        if (confirm("<?php echo t("Poista avainsana"); ?>")) {
          params["tee"] = 'poista_avainsana';
          params['avainsanan_tunnus'] = $(this).attr('id');
          editNode_keywords(params);
        }

        return false;
      });

      jQuery(".edit_keyword").live('click', function(e) {
        e.preventDefault();

        var nodebox_keywords = jQuery("#nodebox_keywords");

        jQuery("#nodebox_keywords_title").html("Muokkaa avainsanaa");
        jQuery("#showaddbox_keywords").hide();
        jQuery("#showaddbox_keywords").after(nodebox_keywords);
        nodebox_keywords.show();

        params["tee"] = 'hae_laji';
        params["avainsanan_tunnus"] = $(this).attr('id');
        editNode_keywords(params);
      });

      jQuery("#moveform").submit(function() {
        params["kohdetaso"]  = jQuery("#kohdetaso").val();
        params["tee"]    = "siirrataso";

        editNode(params);
        return false;
      });

      jQuery("#attachmentsform").live('submit', function() {
        params["tee"]  = 'lisaa_liite';
        params["toim"] = jQuery("#toim").val();

        editNode_attachments(params);

        return false;
      });

      jQuery(".remove_attachment").live('click', function(e) {
        e.preventDefault();

        if (confirm("<?php echo t("Poista liite"); ?>")) {
          params["tee"] = 'poista_liite';
          params["toim"] = jQuery("#toim").val();
          params['liitteen_tunnus'] = $(this).attr('id');

          editNode_attachments(params);
        }

        return false;
      });

      jQuery(".edit_attachment").live('click', function(e) {
        e.preventDefault();

        var nodebox_attachments = jQuery("#nodebox_attachments");

        jQuery("#nodebox_attachments_title").html("Muokkaa liitett‰");
        jQuery("#showaddbox_attachments").hide();
        jQuery("#showaddbox_attachments").after(nodebox_attachments);
        nodebox_attachments.show();

        params["tee"] = 'muokkaa_liite';
        params["toim"] = jQuery("#toim").val();
        params["liitteen_tunnus"] = $(this).attr('id');

        editNode_attachments(params);
      });

      jQuery("#moveform").submit(function() {
        params["kohdetaso"]  = jQuery("#kohdetaso").val();
        params["tee"]    = "siirrataso";

        editNode(params);
        return false;
      });

      <?php
    }
?>
    </script>
    <?php
    // suljetaan nodelaatikko
    echo "</div>";

    // noden avainsanatlaatikko
    echo "<br /><hr /><br />";
    echo "<div id='infobox_keywords' class='spec' style='padding: 20px; border: 1px solid black;'>";
    echo "<h2 style='font-size: 20px'>", t("Avainsanat"), "</h2><hr />";

    $query = "SELECT *
              FROM dynaaminen_puu_avainsanat
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitostunnus = '{$nodeid}'
              AND laji         = '{$toim}'
              ORDER BY laji, avainsana, tarkenne";
    $dpavainsanat_res = pupe_query($query);

    echo "<div id='infobox_keywords_list' style='line-height: 16px;'>";

    if (mysql_num_rows($dpavainsanat_res) > 0) {

      while ($dp_row = mysql_fetch_assoc($dpavainsanat_res)) {
        $_selitetark = t_avainsana("DPAVAINSANALAJI", "", "and avainsana.selitetark = '{$dp_row['avainsana']}' and avainsana.selitetark_2 = '{$toim}'", "", "", "selitetark");

        if ($saamuokata) {
          echo "<a class='remove_keyword' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/stop.png' alt='", t('Poista'), "'/></a>&nbsp;&nbsp;";
          echo "<a style='float: right;' class='edit_keyword' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t('Muokkaa'), "'/></a>";
          echo "<input type='hidden' class='edit_keyword_class' id='{$dp_row['tunnus']}_class' value='{$dp_row['avainsana']}' />";
        }

        echo "<span style='font-weight: bold;'>{$_selitetark}</span> &raquo; $dp_row[tarkenne] ($dp_row[kieli])";

        echo "<br />";
      }
    }

    echo "</div>";

    echo "<br />";
    echo "<div id='editbuttons_keywords'>";

    if ($saamuokata) {
      echo "<a href='#' id='showaddbox_keywords'><img src='{$palvelin2}pics/lullacons/add.png' alt='", t('Lis‰‰'), "'/>", t('Lis‰‰ uusi avainsana'), "</a><br /><br />";
    }

    echo "</div>";

    // tason avainsana lis‰yslaatikko
    $vresult = t_avainsana("DPAVAINSANALAJI", "", "and avainsana.selitetark_2 = '{$toim}'");

    echo "<div id='nodebox_keywords' style='display: none'>
      <form id='keywordsform'>
      <fieldset>
        <legend style='font-weight: bold' id='nodebox_keywords_title'></legend>
        <ul style='list-style:none; padding: 5px'>";

    echo "<li style='padding: 3px'>";
    echo "<label style='display: inline-block; width: 50px'>".t("Laji")." <font class='error'>*</font></label>";
    echo "<select id='keywords_category' name='keywords_category' style='float: right;'>";
    echo "<option value=''>", t("Valitse laji"), "</option>";

    while ($row = mysql_fetch_assoc($vresult)) {
      echo "<option value='{$row['selite']}'>{$row['selitetark']}</option>";
    }

    echo "</select>";
    echo "</li>";

    echo "<li style='padding: 3px' id='keywords_value_box'>";
    echo "<label style='display: inline-block; width: 50px;'>".t("Avainsana")."</label>";
    echo "<span style='float: right;' id='keywords_value_select'></span>";
    echo "</li>";

    echo "</ul>
        <input type='hidden' id='tee' value='' />
        <input type='hidden' id='toim' value='{$toim}' />
        <p style='display: none; color: red' id='nodebox_keywords_err'>".t("Laji ja avainsana ei saa olla tyhji‰").".</p>
        <input type='submit' id='editsubmitbtn' value='".t("Tallenna")."' />
      </fieldset>
      </form>
    </div>";

    echo "</div>";


    // noden liitelaatikko
    echo "<br /><hr /><br />";
    echo "<div id='infobox_attachments' class='spec' style='padding: 20px; border: 1px solid black;'>";
    echo "<h2 style='font-size: 20px'>", t("Liitteet"), "</h2><hr />";

    echo "<div id='infobox_attachments_list' style='line-height: 16px;'>";

    $query = "SELECT *
              FROM liitetiedostot
              WHERE yhtio      = '{$kukarow['yhtio']}'
              AND liitos       = 'dynaaminen_puu'
              AND liitostunnus = '{$nodeid}'";
    $dpliitteet_res = pupe_query($query);

    if (mysql_num_rows($dpliitteet_res) > 0) {

      while ($dp_row = mysql_fetch_assoc($dpliitteet_res)) {
        if ($saamuokata) {
          echo "<a class='remove_attachment' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/stop.png' alt='", t('Poista'), "'/></a>&nbsp;&nbsp;";
          echo "<a style='float: right;' class='edit_attachment' id='{$dp_row['tunnus']}'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='", t('Muokkaa'), "'/></a>";
          echo "<input type='hidden' class='edit_attachment_st' id='{$dp_row['tunnus']}_st' value='{$dp_row['selite']}' />";
          echo "<input type='hidden' class='edit_attachment_kt' id='{$dp_row['tunnus']}_kt' value='{$dp_row['kayttotarkoitus']}' />";
        }

        $lkt = t_avainsana("LITETY", '', "and selite = '{$dp_row['kayttotarkoitus']}'", '', '', "selitetark");
        echo "<span style='font-weight: bold;'>{$dp_row['selite']} / $lkt: ".js_openUrlNewWindow("{$palvelin2}view.php?id={$dp_row['tunnus']}", t('N‰yt‰ liite'), NULL, 800, 600)."</span>";

        echo "<br />";
      }
    }

    echo "</div>";

    echo "<br />";
    echo "<div id='editbuttons_attachments'>";

    if ($saamuokata) {
      echo "<a href='#' id='showaddbox_attachments'><img src='{$palvelin2}pics/lullacons/add.png' alt='", t('Lis‰‰'), "'/>", t('Lis‰‰ uusi liite'), "</a><br /><br />";
    }

    echo "</div>";

    echo "<div id='nodebox_attachments' style='display: none'>
      <form id='attachmentsform'>
      <fieldset>
        <legend style='font-weight: bold' id='nodebox_attachments_title'></legend>
        <ul style='list-style:none; padding: 5px'>";

    echo "<li style='padding: 3px' id='attachments_value_box'>";
    echo "<label style='display: inline-block; width: 50px;'>".t("Selite")."</label>";
    echo "<input type='text' name='liite_selite' id='liite_selite' />";

    echo "</li><li>";
    echo "<label style='display: inline-block; width: 100px;'>".t("K‰yttˆtarkoitus")."</label>";

    $kires = t_avainsana("LITETY");

    echo "<select name='liite_kayttotarkoitus' id='liite_kayttotarkoitus'>";

    while ($kirow = mysql_fetch_array($kires)) {
      if ($kirow["selite"] == $trow[$i]) {
        $select = 'SELECTED';
        $natsasko = TRUE;
      }
      else $select = '';

      if ($kirow["selitetark_2"] == "PAKOLLINEN") {
        $paklisa = "*";
      }
      else {
        $paklisa = "";
      }

      echo "<option value='$kirow[selite]' $select>$paklisa $kirow[selitetark]</option>";
    }

    echo "<option value='MU'>".t("Yleinen")."</option>";
    echo "</select>";

    echo "</li><li>";
    echo "<label style='display: inline-block; width: 100px;'>".t("Data")."</label>";
    echo "<input type = 'file' name = 'liite_data' id = 'liite_data'>";
    echo "</li>";

    echo "</ul>
        <input type='hidden' id='tee' value='' />
        <input type='hidden' id='toim' value='{$toim}' />
        <p style='display: none; color: red' id='nodebox_attachments_err'>".t("Laji ja avainsana ei saa olla tyhji‰").".</p>
        <input type='submit' id='editsubmitbtn' value='".t("Tallenna")."' />
      </fieldset>
      </form>
    </div>";

    echo "</div>";

  }
  else {
    echo "<p>".t("virhe: nodeid tai toim puuttuu")."</p>";
  }

  exit;
}

if (strtoupper($toim) == "TUOTE") {
  $otsikko = t("Tuotepuu");
}
elseif (strtoupper($toim) == "ASIAKAS") {
  $otsikko = t("Asiakaspuu");
}
else {
  $otsikko = t("Organisaatiopuu");
}

echo "<font class='head'>{$otsikko}</font><hr /><br />";
echo "<input type='hidden' id='mista' value='{$mista}' />";

// luodaan uusi root node
if (isset($tee) and isset($toim)) {

  if ($tee == 'valitsesegmentti') {
    // haetaan valitut segmentit ja enabloidaan valintaominaisuudet yms
    $qu = "SELECT puun_tunnus
           FROM puun_alkio
           WHERE yhtio = '{$yhtiorow['yhtio']}'
           AND laji    = '{$toim}'
           AND liitos  = '{$liitos}'";
    $re = pupe_query($qu);
    // haetaan tiedot arrayhin myohempaa kayttoa varten
    while ($row = mysql_fetch_assoc($re)) {
      $valitutnodet[] = $row['puun_tunnus'];
    }
  }
  elseif ($tee == 'paakat' and isset($uusi_nimi) and $uusi_nimi != "") {
    // luodaan uusi paakategoria
    LisaaPaaKat($toim, $uusi_nimi);
    $tee = '';
  }
  paivitapuunsyvyys($toim);
}

/* html list */
$qu = "SELECT
       node.lft AS lft,
       node.rgt AS rgt,
       node.nimi AS node_nimi,
       node.nimi_en AS node_nimi_en,
       node.koodi AS node_koodi,
       node.tunnus AS node_tunnus,
       node.syvyys as node_syvyys,
       (COUNT(node.tunnus) - 1) AS syvyys
       FROM dynaaminen_puu AS node
       JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND node.lft BETWEEN parent.lft AND parent.rgt
       WHERE node.yhtio = '{$kukarow["yhtio"]}'
       AND node.laji    = '{$toim}'
       GROUP BY node.lft
       ORDER BY node.lft";
$re = pupe_query($qu);

// handlataan tilanne kun ei ole viela puun root nodea
if (mysql_num_rows($re) == 0) {
  echo "<form method='POST'>
      <fieldset>
        <legend>".t("Luo uusi puu")."</legend>
        <label>".t("Nimi").": </label><input type='text' name='uusi_nimi' />
        <input type='hidden' name='toim' value='".$toim."' />
        <input type='hidden' name='mista' value='{$mista}' />
        <input type='hidden' name='tee' value='paakat' />
        <input type='submit' value='".t("Tallenna")."' />
      </fieldset>
    </form>";
}
// muutoin jatketaan normaalisti
else {
  echo "<div class='spec' style='border: 1px solid black; width: 500px;'>";
  echo "<ul id='eka'>";

  $prevdepth = 0;

  while ($row = mysql_fetch_assoc($re)) {

    // vahan kikkailua jotta saadaan list elementit suljettua standardin mukaisesti
    $diff = $row['syvyys'] - $prevdepth;
    $diffi = $diff;

    while ($diff > 0) {
      echo "\n<ul>";
      $diff--;
    }
    while ($diff < 0) {
      echo "</li>\n</ul>\n</li>";
      $diff++;
    }
    if ($diffi == 0) echo "</li>";

    echo "<li class='nodes' id='{$row['node_tunnus']}'>{$row['node_nimi']} ({$row['node_tunnus']} / {$row['node_koodi']})";

    $prevdepth = $row['syvyys'];
  }

  echo "</ul></div>
      <div id='infobox' class='spec' style='padding: 20px; border: 1px solid black; left: 520px; top: 52px; float: right; position: absolute;'></div>";

?>
  <script language="javascript">

  var dynpuuparams = new Object();

  <?php
  echo  'dynpuuparams["toim"] = "'.$toim.'";
       dynpuuparams["tee"] = "'.$tee.'";
       dynpuuparams["kieli"] = "'.$kieli.'";';

  if (isset($liitos) and $liitos != "") {
    echo 'dynpuuparams["liitos"] = "'.$liitos.'";';
  }
?>

  var loadimg = "<img src='pics/loading_orange.gif' id='loading' />";
  var activenode;

  var mista = jQuery('#mista').val();

  jQuery.ajaxSetup({
    url: "dynaaminen_puu.php?ajax=OK",
    type: "POST",
    cache: false
  });

  function enableNodes() {
    jQuery(".nodes").click(function() {
      $("#"+activenode).removeClass("ok");
      activenode = this.id;
      $(this).addClass("ok");
      jQuery("#infobox").html(loadimg);
      jQuery("#infobox").css('top', function() {

        if (window.pageYOffset < 45) {
          return 52;
        }

        return window.pageYOffset + 20;
      });

      dynpuuparams["nodeid"] = this.id;

      jQuery.ajax({
        data: dynpuuparams,
        success: function(retval) {
          jQuery("#infobox").html(retval);
        }
      });
      return(false);
    });
  }

  enableNodes();

  function editNode(params) {
    var editbox = jQuery("#editbuttons");
    jQuery(editbox).hide().after(loadimg);

    params.mista = mista;

    jQuery.ajax({
      data: params,
      success: function(retval) {
        jQuery("#infobox").html(retval);

        if (params["tee"] == "ylos") {
          var current = jQuery("#"+params["nodeid"]);
          current.prev().before(current);
        }
        else if (params["tee"] == "alas") {
          var current = jQuery("#"+params["nodeid"]);
          current.next().after(current);
        }
        else if (params["tee"] == "lisaa") {
          var nodeulli = jQuery("#"+params["nodeid"]+" > ul > li:first");
          var newli = "<li class='nodes' id='"+jQuery("#newid").val()+"'>"+params["uusi_nimi"]+" ("+jQuery("#newid").val()+" / "+jQuery("#newcode").val()+")</li>";
          if (nodeulli.size()) {
            nodeulli.before(newli);
          }
          else {
            jQuery("#"+params["nodeid"]).append("<ul>"+newli+"</ul>");
          }
          enableNodes();
        }
        else if (params["tee"] == "muokkaa") {
          var updli = jQuery("#"+params["nodeid"]);
          var childul = jQuery("#"+params["nodeid"]+" > ul");
          updli.html(params["uusi_nimi"]+" ("+params["nodeid"]+" / "+jQuery("#newcode").val()+")");
          if (childul.size() > 0) {
            updli.append("<ul>"+childul.html()+"</ul>");
          }
        }
        else if (params["tee"] == "poista") {
          var remli = jQuery("#"+params["nodeid"]);
          var parentul = remli.parent();
          remli.remove();
          if (!(parentul.children("li")[0])) {
            parentul.remove();
          }
        }
        else if (params["tee"] == "addtotree") {
          jQuery("#"+params["nodeid"]).removeClass("ok");
          jQuery("#"+params["nodeid"]).addClass("error");
        }
        else if (params["tee"] == "removefromtree") {
          jQuery("#"+params["nodeid"]).removeClass("error");
        }
        else if (params["tee"] == "siirrataso") {
          window.location.reload();
        }
      }
    });
  }

  function editNode_keywords(params) {
    var editbox = jQuery("#editbuttons_keywords");

    if (params.tee != 'hae_laji' && params.tee != 'lisaa_avainsana' && params.tee != 'poista_avainsana') {
      jQuery(editbox).hide().after(loadimg);
    }

    jQuery.ajax({
      data: params,
      async: false,
      success: function(retval) {
        if (params.tee == 'hae_laji') {
          jQuery("#keywords_value_select").html(retval);

          jQuery("#keywords_value_select > input").live('keyup', function() {
            $('#keywordsform').closest('#tee').val('lisaa_avainsana');
          });

          if (params.avainsanan_tunnus) {

            var laji_chk = jQuery('#'+params["avainsanan_tunnus"]+'_class').val();

            if (params.laji) laji_chk = params.laji;

            jQuery('#keywords_category > option').each(function() {
              $(this).prop('selected', ($(this).html() == laji_chk));
            });
          }

          return false;
        }
        else if (params.tee == 'lisaa_avainsana') {

          if (params.avainsanan_tunnus) jQuery('#avainsanan_tunnus').remove();

          var nodebox_keywords  = jQuery("#nodebox_keywords");
          jQuery(nodebox_keywords).hide();

          jQuery(editbox).show();

          var showaddbox_keywords = jQuery('#showaddbox_keywords');
          jQuery(showaddbox_keywords).show();

          params.tee = 'hae_avainsana_lista';
        }
        else if (params.tee == 'poista_avainsana') {
          params.tee = 'hae_avainsana_lista';
        }
        else {
          jQuery("#infobox_keywords").html(retval);
          return false;
        }
      }
    });

    if (params.tee == 'hae_avainsana_lista') {
      jQuery.ajax({
        data: params,
        async: false,
        success: function(retval) {
          jQuery('#infobox_keywords_list').html(retval);

          jQuery('#keywords_category > option').each(function() {
            $(this).removeAttr('selected');
          });

          jQuery('#keywords_value > input').val('');
          jQuery('#nodebox_keywords_err').hide();
        }
      });
    }

    return false;
  }

 function editNode_attachments(params) {
   var editbox = jQuery("#editbuttons_attachments");

   if (params.tee != 'lisaa_liite' && params.tee != 'poista_liite' && params.tee != 'muokkaa_liite') {
     jQuery(editbox).hide().after(loadimg);
   }

   if (params.tee == 'lisaa_liite') {
     var myForm = document.getElementById('attachmentsform');
     formData = new FormData(myForm);
     formData.append('tee', params.tee);
     formData.append('toim', params.toim);
     formData.append('nodeid', params.nodeid);

     if (params.liitteen_tunnus > 0) {
      formData.append('liitteen_tunnus', params.liitteen_tunnus);
     }
   }
   else {
    formData = new FormData();
    formData.append('tee', params.tee);
    formData.append('toim', params.toim);
    formData.append('nodeid', params.nodeid);
    formData.append('liitteen_tunnus', params.liitteen_tunnus);
   }

   jQuery.ajax({
     type: "POST",
     data: formData,
     async: false,
     cache: false,
     contentType: false,
     processData: false,
     success: function(retval) {
       if (params.tee == 'muokkaa_liite') {
        jQuery("#liite_selite").val(jQuery('#'+params["liitteen_tunnus"]+'_st').val());

        var litety_chk = jQuery('#'+params["liitteen_tunnus"]+'_kt').val();

        jQuery('#liite_kayttotarkoitus > option').each(function() {
          $(this).prop('selected', ($(this).val() == litety_chk));
        });

        jQuery('#liite_data').hide();

        return false;
       }
       else if (params.tee == 'lisaa_liite') {
         params.liitteen_tunnus = null;

         var nodebox_attachments  = jQuery("#nodebox_attachments");
         jQuery(nodebox_attachments).hide();

         jQuery(editbox).show();

         var showaddbox_attachments = jQuery('#showaddbox_attachments');
         jQuery(showaddbox_attachments).show();

         params.tee = 'hae_liite_lista';
       }
       else if (params.tee == 'poista_liite') {
         params.tee = 'hae_liite_lista';
       }
       else {
         jQuery("#infobox_attachments").html(retval);
         return false;
       }
     }
   });

   if (params.tee == 'hae_liite_lista') {
     jQuery.ajax({
       data: params,
       async: false,
       success: function(retval) {
         jQuery('#infobox_attachments_list').html(retval);
         jQuery('#liite_selite').val('');
         jQuery('#liite_kayttotarkoitus').val('');
         jQuery('#liite_data').val('');
         jQuery('#nodebox_keywords_err').hide();
       }
     });
   }

   return false;
 }

  <?php
  // tarvittavat javascriptit kun muokataan liitoksia
  if ($tee == 'valitsesegmentti') {
    $nodet = implode("','", $valitutnodet);
    echo "var valitutnodet = ['".$nodet."'];";
?>
    jQuery.each(valitutnodet, function() {
      jQuery("#"+this).addClass("error");
    });

  <?php
  }
?>
  </script>
  <?php
}

require 'inc/footer.inc';
