<?php
/*
 * template.katelaskenta.php
 *
 * Template tiedosto katelaskenta toiminnolle. Huolehtii tietojen
 * tulostamisesta sivulle.
 *
 * Toistaiseksi tiedosto tulostaa vain hakutulostaulukon. Hakutoimintoon
 * tarkoitettu lomake piirret��n kontrolleri -tiedostosta, koska sit�
 * ei ole viel� k��nnetty template muotoon.
 */
?>
<?php if (isset($template["flash_success"])) { ?>
    <p style="color: green; font-weight: bold;"><?php echo $template["flash_success"]; ?></p>
<?php } ?>

<?php if (isset($template["flash_error"])) { ?>
    <p style="color: red; font-weight: bold;"><?php echo $template["flash_error"]; ?></p>
<?php } ?>


<?php if (!array_key_exists("ilmoitus", $template)) { // T�m� if-voidaan siirt�� kontrolleriin, jos muutoksia viel� tehd��n. ?>
    <form id="lomake-katelaskenta-hakutulokset"
          action="?submit_button=1&<?php echo $template["ulisa"] . $template["variaatio_query_param"]; ?>"
          method="post">
        <table id="katelaskenta-hakutulokset">
            <!--
                TFOOT elementti taulukon viimeinen rivi, jossa toiminnot
                koko taulun tietojen k�sittelemiseen yht�aikaisesti.
            -->
            <tfoot>
                <tr>
                    <td><input type="checkbox" checked="checked" name="valitutrivit[]" value="" /></td>
                    <td colspan="4">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="myyntikate['']" value="" size=4 /></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="myymalakate['']" value="" size=4 /></td>
                    <td>&nbsp;</td>
                    <td><input type="text" name="nettokate['']" value="" size=4 /></td>
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
                    <th colspan="2">Myym�l�hin - Kate%</th>
                    <th colspan="2">Nettohin - Kate%</th>
                    <th>&nbsp</th>
                </tr>
                <?php
  // K�yd��n hakutulokset l�pi.
  // $template muuttuja on alustettu t�m�n templaten ulkopuolella.
  foreach ($template["tuotteet"] as $avain => &$tuote) {
    $tuotetunnus = $tuote["tunnus"];
?>

                    <tr class="aktiivi" id="rivi_<?php echo trim($tuote["tuoteno"]); ?>" data-kehahinta="<?php echo $tuote["kehahin"]; ?>">
                        <td style="display: none;"><input type="hidden" value="<?php echo $tuote["kehahin"]; ?>" name="valitutkeskihankintahinnat['<?php echo $tuotetunnus; ?>']" /></td>
                        <td><input type="checkbox" checked="checked" name="valitutrivit['<?php echo $tuotetunnus; ?>']" value="<?php echo $tuotetunnus; ?>" /></td>
                        <td><?php echo $tuote["tuoteno"]; ?></td>
                        <td><?php echo $tuote["nimitys"]; ?></td>
                        <td><?php echo $tuote["osasto"] . "<br />" . $tuote["try"]; ?></td>
                        <td><?php echo $tuote["kehahin"]; ?> <?php echo $template["yhtio"]["valkoodi"]; ?></td>
                        <td><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["myyntihinta"]; ?></span> <?php echo $template["yhtio"]["valkoodi"]; ?></td>
                        <td><input type="text" name="myyntikate['<?php echo $tuotetunnus; ?>']" value="<?php echo $tuote["myyntikate"]; ?>" size=4 /></td>
                        <td><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["myymalahinta"]; ?></span> <?php echo $template["yhtio"]["valkoodi"]; ?></td>
                        <td><input type="text" name="myymalakate['<?php echo $tuotetunnus; ?>']" value="<?php echo $tuote["myymalakate"]; ?>" size=4 /></td>
                        <td><span class="hinta" style="vertical-align: baseline;"><?php echo $tuote["nettohinta"]; ?></span> <?php echo $template["yhtio"]["valkoodi"]; ?></td>
                        <td><input type="text" name="nettokate['<?php echo $tuotetunnus; ?>']" value="<?php echo $tuote["nettokate"]; ?>" size=4 /></td>
                        <td><a href="#">Laske</a></td>
                    </tr>

                <?php } // Suljetaan tulosrivin foreach ?>

            </tbody>
        </table>

        <input type="submit"
               name="submit-katelaskenta"
               id="submit-katelaskenta"
               value="Laske ja talleta valitut" />
    </form>
<?php } else { // array_key_exists() tarkistuksen else osio ?>
    <p><font class="message"><?php echo $template["ilmoitus"]; ?></font><p>
    <?php };  // array_key_exists() loppu ?>

<script src="scripts.katelaskenta.js" type="text/javascript"></script>
