<?php
/*
 * template.katelaskenta.php
 *
 * Template tiedosto katelaskenta toiminnolle. Huolehtii tietojen
 * tulostamisesta sivulle.
 *
 * Toistaiseksi tiedosto tulostaa vain hakutulostaulukon. Hakutoimintoon
 * tarkoitettu lomake piirretään kontrolleri -tiedostosta, koska sitä
 * ei ole vielä käännetty template muotoon.
 */
?>
<?php if (isset($template["flash_success"])) { ?>
<p style="color: green; font-weight: bold;"><?php echo $template["flash_success"]; ?>
</p>
<?php } ?>

<?php if (isset($template["flash_error"])) { ?>
<p style="color: red; font-weight: bold;"><?php echo $template["flash_error"]; ?>
</p>
<?php } ?>

<style>
    .changepricein + span {
        display: none;
    }
    .disabled {
        pointer-events: none;
        cursor: default;
        background-color: #eee;
    }
</style>
<?php if($hintojen_muutos) {
    ?>
    <style>
    #katelaskenta-hakutulokset > tfoot {
        display: none;
    }
    </style>
    <?php
}
?>


<?php if (!array_key_exists("ilmoitus", $template)) { // Tämä if-voidaan siirtää kontrolleriin, jos muutoksia vielä tehdään.?>
<form id="lomake-katelaskenta-hakutulokset"
    action="?submit_button=1&<?php echo $template["ulisa"] . $template["variaatio_query_param"]; ?>"
    method="post">
    <table id="katelaskenta-hakutulokset">
        <!--
                TFOOT elementti taulukon viimeinen rivi, jossa toiminnot
                koko taulun tietojen käsittelemiseen yhtäaikaisesti.
            -->
        <tfoot>
            <tr>
                <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="" /></td>
                <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
                    ?><td colspan="4">&nbsp;</td><?php
                } else {
                    ?><td colspan="4">&nbsp;</td>
                <?php } ?>
                <td>&nbsp;</td>
                <td><input type="text" name="myyntikate['']" value="" size=4 /></td>
                <td>&nbsp;</td>
                <td><input type="text" name="myymalakate['']" value="" size=4 /></td>
                <td>&nbsp;</td>
                <td><input type="text" name="nettokate['']" value="" size=4 /></td>
                <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
                    ?>
                    <td colspan="2">&nbsp;</td>
                    <td><input type="text" name="asiakashintakate['']" value="" size=4 /></td>
                    <?php
                } ?>
                <td><a href="#">Laske kaikki</a></td>
            </tr>
        </tfoot>

        <tbody>
            <tr>
                <th>Valinta</th>
                <th>Tuoteno</th>
                <th>Nimitys</th>
                <th>Osasto<br>Try</th>
                <th>Kehain</th>
                <th colspan="2">Myyntihin - Kate%</th>
                <th colspan="2">Myymälähin - Kate%</th>
                <th colspan="2">Nettohin - Kate%</th>
                <?php if($mul_asiakasryhma) {
                    ?><th class="tumma">Asiakasryhmä</th><?php
                } else if($mul_asiakaspiiri) {
                    ?><th class="tumma">Asiakaspiiri</th><?php
                } else if($mul_asiakashinnasto_asiakas) {
                    ?><th class="tumma">Asiakas</th><?php
                } ?>
                <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
                    ?><th class="tumma" colspan="2"><?php echo t('Asiakashin - Kate%'); ?></th><?php
                }?>
                <th>&nbsp</th>
            </tr>
            <?php
  // Käydään hakutulokset läpi.
  // $template muuttuja on alustettu tämän templaten ulkopuolella.
  foreach ($template["tuotteet"] as $haku_funktio_key => $template_tuote) {
    foreach ($template_tuote as $avain => &$tuote) {
      $tuotetunnus = $tuote["tunnus"]; ?>

            <tr class="aktiivi"
                id="rivi_<?php echo trim($tuote["tuoteno"]); ?>"
                data-kehahinta="<?php echo $tuote["kehahin"]; ?>" data-asiakashinta="<?php echo $tuote["asiakashinta_hinta"]; ?>">
                <td style="display: none;">
                        <input type="hidden"
                        value="<?php echo $tuote["kehahin"]; ?>"
                        name="valitutkeskihankintahinnat['<?php echo $tuotetunnus; ?>']" />
                        <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) { ?>
                        <input type="hidden"
                        value="<?php echo $tuote["asiakashinta_hinta"]; ?>"
                        name="valitutasiakashinnat['<?php echo $tuote["asiakashinta_asiakas_tunnus"]."!!!".$tuotetunnus; ?>']" />
                        <?php } ?>
                </td>
                <td><input type="checkbox" checked="checked"
                        name="valitutrivit['<?php echo $tuotetunnus; ?>']"
                        value="<?php echo $tuotetunnus; ?>" /></td>
                <td><?php echo $tuote["tuoteno"]; ?>
                </td>
                <td><?php echo $tuote["nimitys"]; ?>
                </td>
                <td><?php echo $tuote["osasto"] . "<br />" . $tuote["try"]; ?>
                </td>
                <td><?php echo $tuote["kehahin"]; ?>
                    <?php echo $template["yhtio"]["valkoodi"]; ?>
                </td>

                <?php 
                if($laskekate) {
                    $tuote = laske_kate($tuote);
                }
                ?>

                <td><?php if($hintojen_muutos) { ?><input type="text" size="6" class="changepricein" name="myyntihintamuutos" value="<?php echo $tuote["myyntihinta"]; ?>"></input><?php } ?><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["myyntihinta"]; ?></span>
                    <?php echo $template["yhtio"]["valkoodi"]; ?>
                </td>
                <td><input <?php if($hintojen_muutos) { ?>class="disabled"  <?php } ?> type="text"
                        name="myyntikate['<?php echo $tuotetunnus; ?>']"
                        value="<?php echo $tuote["myyntikate"]; ?>"
                        size=4 /></td>
                <td><?php if($hintojen_muutos) { ?><input type="text" size="6" class="changepricein" name="myymalahintamuutos" value="<?php echo $tuote["myymalahinta"]; ?>"></input><?php } ?><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["myymalahinta"]; ?></span>
                    <?php echo $template["yhtio"]["valkoodi"]; ?>
                </td>
                <td><input <?php if($hintojen_muutos) { ?>class="disabled"  <?php } ?> type="text"
                        name="myymalakate['<?php echo $tuotetunnus; ?>']"
                        value="<?php echo $tuote["myymalakate"]; ?>"
                        size=4 /></td>
                <td><?php if($hintojen_muutos) { ?><input type="text" size="6" class="changepricein" name="nettohintamuutos" value="<?php echo $tuote["nettohinta"]; ?>"></input><?php } ?><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["nettohinta"]; ?></span>
                    <?php echo $template["yhtio"]["valkoodi"]; ?>
                </td>
                <td><input <?php if($hintojen_muutos) { ?>class="disabled"  <?php } ?> type="text"
                        name="nettokate['<?php echo $tuotetunnus; ?>']"
                        value="<?php echo $tuote["nettokate"]; ?>"
                        size=4 /></td>
                <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
                    ?><td class="tumma"><?php echo $haku_funktio_key; ?></td><?php
                }?>
                <?php if($mul_asiakasryhma or $mul_asiakaspiiri or $mul_asiakashinnasto_asiakas) {
                ?>
                <td class="tumma"><?php if($hintojen_muutos) { ?><input type="text" size="6" class="changepricein" name="asiakashinta_hintamuutos" value="<?php echo $tuote["asiakashinta_hinta"]; ?>"></input><?php } ?><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["asiakashinta_hinta"]; ?></span>
                        <?php echo $template["yhtio"]["valkoodi"]; ?>
                </td>
                <td class="tumma"><input <?php if($hintojen_muutos) { ?>class="disabled"  <?php } ?> type="text"
                        name="asiakashintakate['<?php echo $tuote["asiakashinta_asiakas_tunnus"]."!!!".$tuotetunnus; ?>']"
                        value="<?php echo $tuote["asiakashinta_asiakas_myyntikate"]; ?>"
                        size=4 /></td>
                <?php
                }?>
                <td><a href="#">Laske</a></td>
            </tr>

            <?php
    }
  } // Suljetaan tulosrivin foreach?>

        </tbody>
    </table>

    <input type="submit" name="submit-katelaskenta" id="submit-katelaskenta" value="Laske ja talleta valitut" />
</form>
<?php } else { // array_key_exists() tarkistuksen else osio?>
<p>
    <font class="message"><?php echo $template["ilmoitus"]; ?>
    </font>
<p>
    <?php };  // array_key_exists() loppu?>

    <script src="scripts.katelaskenta.js" type="text/javascript"></script>