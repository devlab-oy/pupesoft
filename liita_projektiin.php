<?php

require ("inc/parametrit.inc");


/*
  Tyäkalu laskujen/tilausten liittämiseksi osaksi projekti

  Näin esim erilliset rahtilaskut,hyvitykset voidaan liittää osaksi projektia myös jälkikäteen

  Tästä on apua kun laskemme koko projektin arvoa.

*/

echo "<font class='head'>".t("Liitä tilaus projektiin")."</font><hr><br><br>";


if($tee=="KORJAA" or $tee=="LIITA") {
  //  tarkastetaan että tunnusnippu on edelleen ok
  $query = "  SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('R', 'L','N') and tunnusnippu>0 and tunnus='$tunnusnippu'";
  $result = mysql_query($query) or pupe_error($query);
  if(mysql_num_rows($result)>0) {
    $laskurow=mysql_fetch_array($result);

    $query = "  SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('L','G','E','V','W','N','T','C') and tunnus='$tunnus'";
    $res = mysql_query($query) or pupe_error($query);
    if(mysql_num_rows($res)>0) {
      $row=mysql_fetch_array($res);

      if($tee=="LIITA") {
        $query="update lasku set tunnusnippu='$tunnusnippu' where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
        $updres=mysql_query($query) or pupe_error($query);
        echo "<font class='message'>".t("Liitettiin tilaus")." $tunnus ".t("tilaukseen")." $tunnusnippu</font><br><br>";

        $tee="";
        $tunnus="";
      }
      else {
        $laskutyyppi=$laskurow["tila"];
        $alatila=$laskurow["alatila"];

        //tehdään selväkielinen tila/alatila
        require "inc/laskutyyppi.inc";

        echo "<table>
            <tr>
              <th>".t("Tilaus johon liitetään")."</th>
            </tr>
            <tr>
              <td>$laskurow[tunnusnippu] $laskurow[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>
            </tr>
            <tr>
              <td class='back'><br></td>
            </tr>";

        $laskutyyppi=$row["tila"];
        $alatila=$row["alatila"];

        //tehdään selväkielinen tila/alatila
        require "inc/laskutyyppi.inc";

        if($row["tunnusnippu"]>0) {
          $lisa="<td class='back'><font class='message'>".t("HUOM: tilaus on jo liitettynä projektiin")." $row[tunnusnippu]</font></td>";
        }
        else {
          $lisa = "";
        }

        echo "<table>
            <tr>
              <th>".t("Tilaus joka liitetään")."</th>
            </tr>
            <tr>
              <td>$row[tunnus] $row[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>$lisa
            </tr>
            <tr>
              <td class='back'><br></td>
            </tr>";


        echo "  <tr>
              <form method='post' name='projekti' autocomplete='off'>
              <input type='hidden' name='tee' value='LIITA'>
              <input type='hidden' name='tunnusnippu' value='$tunnusnippu'>
              <input type='hidden' name='tunnus' value='$tunnus'>
              <td class='back' align='right'><input type='Submit' value='".t("liitä")."'></td>
              </form>
            </tr>
          </table>";
      }
    }
    else {
      $tunnusvirhe = "<font class='error'>".("Tilausta ei voida liittää. Tilausnumero voi olla väärä tai tilaus on päätilaus")."</font><br>";
      $tee="HAE";
    }
  }
  else {
    $tunnusnippuvirhe = "Sopivaa tilausta ei löydy. Tilauksen pitää olla normaali tilaus tai projekti.";
    $tee="";
  }
}

if($tee == "HAE") {
  $query = "  SELECT nimi, nimitark, tila, alatila, tunnusnippu, tunnus from lasku where yhtio='$kukarow[yhtio]' and tila IN ('R', 'L','N') and tunnus='$tunnusnippu'";
  $result = mysql_query($query) or pupe_error($query);
  if(mysql_num_rows($result)>0) {
    $laskurow=mysql_fetch_array($result);
    if($laskurow["tunnusnippu"]>0) {
      $laskutyyppi=$laskurow["tila"];
      $alatila=$laskurow["alatila"];

      //tehdään selväkielinen tila/alatila
      require "inc/laskutyyppi.inc";

      echo "<table>
          <tr>
            <th>".t("Tilaus johon liitetään")."</th>
          </tr>
          <tr>
            <td>$laskurow[tunnusnippu] $laskurow[nimi] - ".t("$laskutyyppi")." ".t("$alatila")."</td>
          </tr>
          <tr>
            <td class='back'><br></td>
          </tr>
          <tr>
            <th>".t("Anna tilausnumero jonka haluat liittää")."</th>
          </tr>
          <tr>
            <form method='post' name='projekti' autocomplete='off'>
            <input type='hidden' name='tee' value='KORJAA'>
            <input type='hidden' name='tunnusnippu' value='$tunnusnippu'>
            <td><input type='text' name='tunnus' size='15' maxlength='14' value='$tunnus'></td>
            <td class='back'><input type='Submit' value='".t("Jatka")."'></td>
            <td class='back'><font class='error'>".t($tunnusvirhe)."</font></td>
            </form>
          </tr>
        </table>";
    }
    else {
      //  pitäisikö sallia sellainen tehdä?
      $tunnusnippuvirhe =  "Tilauksella ei ole tunnusnippua";
      $tee="";
    }
  }
  else {
    $tunnusnippuvirhe = "Sopivaa tilausta ei löydy. Tilauksen pitää olla projekti.";
    $tee="";
  }
}


if($tee == "") {
  echo "<table>
      <tr>
        <th>".t("Anna projekti/tilausnumero")."<br>".t("johon haluat liittää tilauksen")."</th>
      </tr>
      <tr>
        <form method='post' name='projekti' autocomplete='off'>
        <input type='hidden' name='tee' value='HAE'>
        <td><input type='text' name='tunnusnippu' size='15' maxlength='14' value='$tunnusnippu'></td>
        <td class='back'><input type='Submit' value='".t("Jatka")."'></td>
        <td class='back'><font class='error'>".t($tunnusnippuvirhe)."</font></td>
        </form>
      </tr>
    </table>";
}


require ("inc/footer.inc");
