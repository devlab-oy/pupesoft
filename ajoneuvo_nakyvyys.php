<?php

require "inc/parametrit.inc";
require "inc/tecdoc.class.php";

if (!empty($_POST['ajax'])) {

  $type = mysql_real_escape_string($_POST['type']);
  $ohjelma_moduli = mysql_real_escape_string($_POST['ohjelma_moduli']);

  if ($_POST['action'] == 'insert') {

    if ($_POST['ajax'] == 'brand') {
      $hernr = (int) $_POST['hernr'];
      $insert_values = ", hernr = '{$hernr}'";
    }
    elseif ($_POST['ajax'] == 'model') {
      $kmodnr = (int) $_POST['kmodnr'];
      $insert_values = ", kmodnr = '{$kmodnr}'";
    }
    elseif ($_POST['ajax'] == 'variation') {
      $ktypnr = (int) $_POST['ktypnr'];
      $insert_values = ", ktypnr = '{$ktypnr}'";
    }
    else {
      $insert_values = "";
    }

    if (!empty($insert_values)) {
      $query = "INSERT INTO ajoneuvo_nakyvyys SET
                yhtio = '{$kukarow['yhtio']}',
                tyyppi = '{$type}',
                ohjelma_moduli = '{$ohjelma_moduli}',
                muuttaja = '',
                muutospvm = '0000-00-00 00:00:00',
                laatija = '{$kukarow['kuka']}',
                luontiaika = now()
                {$insert_values}";
      $res = pupe_query($query);
    }
  }

  if ($_POST['action'] == 'delete') {

    if ($_POST['ajax'] == 'brand') {
      $hernr = (int) $_POST['hernr'];
      $delete_values = "AND hernr = '{$hernr}'";
    }
    elseif ($_POST['ajax'] == 'model') {
      $kmodnr = (int) $_POST['kmodnr'];
      $delete_values = "AND kmodnr = '{$kmodnr}'";
    }
    elseif ($_POST['ajax'] == 'variation') {
      $ktypnr = (int) $_POST['ktypnr'];
      $delete_values = "AND ktypnr = '{$ktypnr}'";
    }
    else {
      $delete_values = "";
    }

    if (!empty($delete_values)) {
      $query = "DELETE FROM ajoneuvo_nakyvyys
                WHERE yhtio = '{$kukarow['yhtio']}'
                {$delete_values}";
      $res = pupe_query($query);
    }
  }

  if ($_POST['action'] == 'getmodels') {

    $hernr = (int) $_POST['hernr'];

    if ($kukarow['yhtio'] == 'mergr') {
      $td = new tecdoc($type, false);
    }
    else {
      $td = new tecdoc($type);
    }

    $models = $td->getModels($hernr);

    foreach($models as &$model) {
      $query = "SELECT *
                FROM ajoneuvo_nakyvyys
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tyyppi = '{$type}'
                AND ohjelma_moduli = '{$ohjelma_moduli}'
                AND kmodnr = '{$model['modelno']}'";
      $res = pupe_query($query);

      $model['checked'] = mysql_num_rows($res) != 0 ? true : false;
    }

    echo json_encode($models);
  }

  if ($_POST['action'] == 'getvariations') {

    $kmodnr = (int) $_POST['kmodnr'];

    if ($kukarow['yhtio'] == 'mergr') {
      $td = new tecdoc($type, false);
    }
    else {
      $td = new tecdoc($type);
    }

    $variations = $td->getVersions($kmodnr);

    foreach($variations as &$variation) {
      $query = "SELECT *
                FROM ajoneuvo_nakyvyys
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tyyppi = '{$type}'
                AND ohjelma_moduli = '{$ohjelma_moduli}'
                AND ktypnr = '{$variation['autoid']}'";
      $res = pupe_query($query);

      $variation['checked'] = mysql_num_rows($res) != 0 ? true : false;
    }

    echo json_encode($variations);
  }

  exit;
}

echo "<script type='text/javascript'>
        $(function() {

          $('input.brand').on('click', function() {

            var action = 'delete';

            if ($(this).is(':checked')) {
              action = 'insert'
            }

            $.ajax({
              async: true,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'brand',
                no_head: 'yes',
                ohje: 'off',
                action: action,
                hernr: $(this).val(),
                type: $('#type').val(),
                ohjelma_moduli: $('#ohjelma_moduli').val()
              }
            });
          });

          $('input.model').live('click', function() {

            var action = 'delete';

            if ($(this).is(':checked')) {
              action = 'insert'
            }

            $.ajax({
              async: true,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'model',
                no_head: 'yes',
                ohje: 'off',
                action: action,
                kmodnr: $(this).val(),
                type: $('#type').val(),
                ohjelma_moduli: $('#ohjelma_moduli').val()
              }
            });
          });

          $('input.variation').live('click', function() {

            var action = 'delete';

            if ($(this).is(':checked')) {
              action = 'insert'
            }

            $.ajax({
              async: true,
              type: 'POST',
              dataType: 'JSON',
              data: {
                ajax: 'variation',
                no_head: 'yes',
                ohje: 'off',
                action: action,
                ktypnr: $(this).val(),
                type: $('#type').val(),
                ohjelma_moduli: $('#ohjelma_moduli').val()
              }
            });
          });

          $('ul.main').on('click', 'li.brandrow', function(e) {

            if (e.target.nodeName.toLowerCase() != 'input') {

              var parent = $(this),
                  _id = parent.data('id'),
                  _src = '{$palvelin2}pics/lullacons/bullet-arrow-right.png';

              if ($('.'+_id+'_models').is(':visible')) {
                $('.'+_id+'_variations').hide();
                $('.'+_id+'_models').hide();
                $('.'+_id+'_models > img').attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
                $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
              }
              else if ($('.'+_id+'_models').length) {
                $('.'+_id+'_models').show();
                $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
              }
              else {

                $.ajax({
                  async: true,
                  type: 'POST',
                  data: {
                    ajax: 'brand',
                    no_head: 'yes',
                    ohje: 'off',
                    action: 'getmodels',
                    hernr: _id,
                    type: $('#type').val(),
                    ohjelma_moduli: $('#ohjelma_moduli').val()
                  },
                  success: function(data) {

                    $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');

                    data = $.parseJSON(data);

                    var ul = $('<ul></ul>');
                    ul.css('list-style-type', 'none');

                    $.each(data, function(indx, row) {
                      var li = $('<li class=\"'+_id+'_'+row.modelno+'_models '+_id+'_models modelsrow\" data-id=\"'+_id+'_'+row.modelno+'\"></li>'),
                          _checked = row.checked ? 'checked=\"checked\"' : '';

                      li.append($('<input type=\"checkbox\" class=\"model\" name=\"model[]\" value=\"'+row.modelno+'\" '+_checked+' />'));
                      li.append(' '+row.modelname);
                      li.append(' <img src=\"'+_src+'\" id=\"img_'+_id+'_'+row.modelno+'\" style=\"padding-left: 12px;\">');

                      ul.append(li);

                      parent.after(ul);
                    });
                  }
                });
              }
            }
          });

          $('ul.main').on('click', 'li.modelsrow', function(e) {

            if (e.target.nodeName.toLowerCase() != 'input') {

              var parent = $(this),
                  _id = parent.data('id');

              if ($('.'+_id+'_variations').is(':visible')) {
                $('.'+_id+'_variations').hide();
                $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-right.png');
              }
              else if ($('.'+_id+'_variations').length) {
                $('.'+_id+'_variations').show();
                $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');
              }
              else {

                ids = _id.split('_');

                $.ajax({
                  async: true,
                  type: 'POST',
                  data: {
                    ajax: 'model',
                    no_head: 'yes',
                    ohje: 'off',
                    action: 'getvariations',
                    kmodnr: ids[1],
                    type: $('#type').val(),
                    ohjelma_moduli: $('#ohjelma_moduli').val()
                  },
                  success: function(data) {

                    data = $.parseJSON(data);

                    if (data.length) {

                      $('#img_'+_id).attr('src', '{$palvelin2}pics/lullacons/bullet-arrow-down.png');

                      var ul = $('<ul></ul>');
                      ul.css('list-style-type', 'none');

                      $.each(data, function(indx, row) {
                        var li = $('<li class=\"'+_id+'_variations '+ids[0]+'_variations variationsrow\"></li>'),
                            _checked = row.checked ? 'checked=\"checked\"' : '';

                        li.append($('<input type=\"checkbox\" class=\"variation\" name=\"variation[]\" value=\"'+row.autoid+'\" '+_checked+' />'));
                        li.append(' '+row.capltr);

                        if (row.kw != undefined && row.hp != undefined)  {
                          li.append(' ('+row.kw+' / '+row.hp+')');
                        }

                        li.append(' '+row.version);
                        li.append(' '+row.year_txt);

                        ul.append(li);
                      });

                      parent.after(ul);
                    }
                  }
                });
              }
            }
          });
        });
      </script>";

if (!isset($type)) $type = 'pc';
if (!isset($ohjelma_moduli)) $ohjelma_moduli = 'PUPESOFT';
if (!isset($tee)) $tee = '';

echo "<font class='head'>", t("Ajoneuvon näkyvyys"), "</font><hr>";

echo "<form method='post'>";
echo "<input type='hidden' name='tee' value='hae' />";
echo "<table>";
echo "<tr>";
echo "<th>",t("Valitse moduli"),"</th>";

$query = "SELECT DISTINCT TRIM(REPLACE(profiili, 'Extranet', '')) profiilinimi
          FROM oikeu
          WHERE yhtio   = '{$kukarow['yhtio']}'
          AND profiili != ''
          AND profiili  LIKE 'extranet%'
          HAVING profiilinimi != ''
          ORDER BY profiili";
$res = pupe_query($query);

echo "<td><select id='ohjelma_moduli' name='ohjelma_moduli'>";
echo "<option value='PUPESOFT'>Pupesoft</option>";

while ($row = mysql_fetch_assoc($res)) {
  $sel = $ohjelma_moduli == $row['profiilinimi'] ? "selected" : "";

  echo "<option value='{$row['profiilinimi']}' {$sel}>{$row['profiilinimi']}</option>";
}

echo "</select></td>";
echo "<td class='back'></td>";
echo "</tr>";
echo "<tr>";

$sel = array($type => " selected") + array('pc' => '', 'cv' => '');

echo "<th>",t("Valitse ajoneuvolaji"),"</th>";
echo "<td><select id='type' name='type'>";
echo "<option value='pc' {$sel['pc']}>",t("Henkilöauto"),"</option>";
echo "<option value='cv' {$sel['cv']}>",t("Hyötyajoneuvo"),"</option>";
echo "<td class='back'><input type='submit' value='",t("Hae"),"' />";
echo "</tr>";
echo "</table>";
echo "</form>";

if ($tee == 'hae') {

  if ($kukarow['yhtio'] == 'mergr') {
    $td = new tecdoc($type, false);
  }
  else {
    $td = new tecdoc($type);
  }

  $brands = $td->getBrands();

  if (!empty($brands)) {

    echo "<br />";
    echo "<ul class='main' style='width: 50%; list-style-type: none;'>";

    foreach ($brands as $brand) {
      echo "<li class='brandrow' data-id='{$brand['manuid']}'>";

      $query = "SELECT *
                FROM ajoneuvo_nakyvyys
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND hernr = '{$brand['manuid']}'
                AND tyyppi = '{$type}'
                AND ohjelma_moduli = '{$ohjelma_moduli}'";
      $chk_res = pupe_query($query);

      $chk = mysql_num_rows($chk_res) != 0 ? "checked" : "";

      echo "<input type='checkbox' class='brand' name='brand[]' value='{$brand['manuid']}' {$chk} /> ";
      echo $brand['name'];

      $_src = "{$palvelin2}pics/lullacons/bullet-arrow-right.png";
      echo "<img src='{$_src}' id='img_{$brand['manuid']}' style='padding-left: 12px;'>";

      echo "</li>";
    }

    echo "</ul>";
  }
}

require "inc/footer.inc";
