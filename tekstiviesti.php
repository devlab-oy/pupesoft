<?php

  require ("inc/parametrit.inc");

  echo "<font class='head'>Tekstiviestien l‰hett‰minen</font><hr>";

  if ($tee == 'laheta') {

    $kotinums2 = explode("\r", $kotinums);
    $lahetetyt = array();

    foreach ($kotinums2 as $kotinum) {
      $kotinum = str_replace ("-", "", $kotinum);
      $kotinum = str_replace ("+", "", $kotinum);
      $kotinum = str_replace ("\n", "", $kotinum);
      $kotinum = str_replace ("\r", "", $kotinum);
      $ok = 1;
  
      if (is_numeric($kotinum) and strlen($teksti) > 0) {
  
        $host = $sms_header;
        $teksti = str_replace($host, "", $teksti);
        $teksti = str_replace(str_replace("'", "", $host), "", $teksti);
        $teksti = $host.$teksti;

        if (!in_array($kotinum, $lahetetyt)) {
          array_push($lahetetyt, $kotinum);

          if (SendSms($sms_palvelintyyppi, $kukarow['yhtio'], $kukarow['kuka'], $kotinum, $teksti)) {
            $ok = 0;
          }
          else {
            $ok = 1;
          }
        }
        else {
          $ok = 2;
        }
      }

      if ($ok == 1) {
        echo "<font class='error'>VIRHE: Tekstiviestin l‰hetys ep‰onnistui! $retval</font>";
        echo " ".$kotinum;
        echo "<br>";
      }
  
      if ($ok == 0) {
        echo "<font class='message'>Tekstiviesti l‰hetetty!</font>";
        echo " ".$kotinum;
        echo "<br>";
      }

      if ($ok == 2) {
        echo "<font class='message'>Samaa tekstiviestia ei l‰hetetty uudestaan samaan numeroon!</font>";
        echo " ".$kotinum;
        echo "<br>";
      }
  
      $tee = "";
    }
  }
  echo "<br/>";

  if ($tee == "") {

    echo "<form name='form' method='post' name='tekstari'>";
    echo "<input type='hidden' name='tee' value = 'laheta'>";
    echo "<table>";

    $query = "SELECT * FROM avainsana WHERE yhtio = '$kukarow[yhtio]' AND laji = 'sms_palvelin' ORDER BY jarjestys";
    $vresult = mysql_query($query) or pupe_error($query);

    if (mysql_num_rows($vresult) > 0) {

      echo "<tr><th>Tekstiviestipalvelin</th><td>";
       echo "<select name='sms_palvelintyyppi'>";

      while ($vrow = mysql_fetch_array($vresult)) {
        if ($sms_palvelintyyppi == $vrow["selite"]) {
          $selectedi = "selected";
        }
        else {
          $selectedi = "";
        }
        echo "<option value='$vrow[selite]' ".$selectedi.">$vrow[selitetark]</option>";
      }
      
      echo "</select>\n";
      echo "</td></tr>";
    }
    else {
      echo "<input type='hidden' name='sms_palvelintyyppi' value='pupe'>";
    }

    echo "<tr><th>Puh. numerot<br/>Erottele rivinvaihdoilla</th>";
    echo "<td><textarea name='kotinums' cols='45' rows='6' wrap='soft'>$kotinums</textarea></td>
        </tr>
        <tr>
          <th>Viesti</th>
          <td><textarea name='teksti' cols='45' rows='6' wrap='soft'>$teksti</textarea></td>
        </tr>
      </table>

      <br><input type='submit' value = 'L‰het‰'>

      </form>";

  }

  require ("inc/footer.inc");

  function SendSms($sms_palvelintyyppi, $yhtio, $kuka, $vastaanottaja, $viesti) {

    $res = false;

    if ($sms_palvelintyyppi == "pupe") {
      $res = SendPupesoftSms($vastaanottaja, $viesti);
    }
    else if ($sms_palvelintyyppi == "kannel") {
      $res = SendKannelSms($vastaanottaja, $viesti);
    }
    else if ($sms_palvelintyyppi == "clickatell") {
      $res = SendClickatellSms($vastaanottaja, $viesti);
    }
    else {
      echo "<font class='error'>VIRHE: Valitsemasi tekstiviestipalvelimen tyyppi $sms_palvelintyyppi ei ole tuettu!</font><br/><br/>";
      return false;
    }

    if (!$res) {
      return false;
    }

    if ($tee_lokimerkinta) {
      $credits = floor(strlen($viesti) / 159) + 1;
      // Lis‰t‰‰n viesti sms-tauluun
      $query = "  INSERT INTO sms SET 
            yhtio = '".$yhtio."', 
            viesteja = '".$credits."',
            vastaanottaja = '".addslashes($vastaanottaja)."',
            viesti = '".addslashes($viesti)."',
            luontiaika = now(),
            laatija = '$kuka'";
      $result = mysql_query($query) or pupe_error($query);
    }

    return true;
  }


  function SendPupesoftSms($vastaanottaja, $viesti) {

    if (strlen($viesti) > 160) {
      echo "<font class='error'>VIRHE: Tekstiviestin maksimipituus on 160 merkki‰!</font><br><br>";
      return false;
    }

    global $sms_palvelin;
    global $sms_user;
    global $sms_pass;

    if ($sms_palvelin == "" or $sms_user == "" or $sms_pass == "") {
      echo "<font class='error'>VIRHE: Tarkista tekstiviestipalvelimen m‰‰ritykset asetustiedostossa!</font><br><br>";
      return false;
    }

    if (is_numeric($vastaanottaja) and strlen($viesti) > 0 and strlen($viesti) <= 160) {
      $viesti = urlencode($viesti);
      $retval = file_get_contents("$sms_palvelin?user=$sms_user&pass=$sms_pass&numero=$vastaanottaja&viesti=$viesti");
      if (trim($retval) == "0") return true;
    }

    return false;
  }

  function SendClickatellSms($vastaanottaja, $viesti) {
    global $clickatell_api_id;
    global $clickatell_username;
    global $clickatell_password;
    global $clickatell_sender_name;

    $continue = true;
    if ($clickatell_api_id == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$clickatell_api_id ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if ($clickatell_username == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$clickatell_username ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if ($clickatell_password == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$clickatell_password ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if (!$continue) {
      echo "<br/><br/>";
      return false;
    }

    $mysms = new sms($clickatell_api_id, $clickatell_username, $clickatell_password);
    //echo $mysms->session;

    if ($mysms->send ($vastaanottaja, $clickatell_sender_name, $viesti)) {
      echo "Tekstiviestitilin saldo: ".$mysms->getbalance()."<br/>";

      return true;
    }
    
    $credits = floor(strlen($viesti) / 159) + 1;
    // Lis‰t‰‰n viesti sms-tauluun
    $query = "  INSERT INTO sms SET 
          yhtio = '".$yhtio."', 
          viesteja = '".$credits."',
          vastaanottaja = '".addslashes($vastaanottaja)."',
          viesti = '".addslashes($viesti)."',
          luontiaika = now(),
          laatija = '$kuka'";
    $result = mysql_query($query) or pupe_error($query);
    
    return false;
  }

  function SendKannelSms($receiver_number, $message) {

    global $kannel_host_url;
    global $kannel_username;
    global $kannel_password;

    $continue = true;
    if ($kannel_host_url == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$kannel_host_url ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if ($kannel_username == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$kannel_username ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if ($kannel_password == "") {
      echo "<font class='error'>VIRHE: Tarkista asetukset! \$kannel_password ei ole m‰‰ritelty.</font><br/>";
      $continue = false;
    }
    if (!$continue) {
      echo "<br/><br/>";
      return false;
    }

    $message = str_replace("\r", "", $message);
    $message = urlencode($message);            
  
    $receiver_number = str_replace("+", "", $receiver_number);
    $receiver_number = str_replace("\n", "", $receiver_number);        
    $receiver_number = urlencode($receiver_number);
      
    $url = $kannel_host_url."?username=".$kannel_username."&password=".$kannel_password."&to=".$receiver_number."&text=".$message;
    $ch = curl_init($url);        
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_GET, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    if (strpos("a".$output, "0: Accepted for delivery") > 0) {      
      return(true);
    }
    return(false);
    
    $credits = floor(strlen($viesti) / 159) + 1;
    // Lis‰t‰‰n viesti sms-tauluun
    $query = "  INSERT INTO sms SET 
          yhtio = '".$yhtio."', 
          viesteja = '".$credits."',
          vastaanottaja = '".addslashes($vastaanottaja)."',
          viesti = '".addslashes($viesti)."',
          luontiaika = now(),
          laatija = '$kuka'";
    $result = mysql_query($query) or pupe_error($query);
    
  }

?>


<?php
/**
 * CLICKATELL SMS API
 *
 * This class is meant to send SMS messages (with unicode support) via 
 * the Clickatell gateway and provides support to authenticate to this service, 
 * spending an vouchers and also query for the current account balance. This class
 * use the fopen or CURL module to communicate with the gateway via HTTP/S.
 *
 * For more information about CLICKATELL service visit http://www.clickatell.com
 *
 * @version 1.6
 * @package sms_api
 * @author Aleksandar Markovic <mikikg@gmail.com>
 * @copyright Copyright (c) 2004 - 2007 Aleksandar Markovic
 * @link http://sourceforge.net/projects/sms-api/ SMS-API Sourceforge project page
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * Main SMS-API class
 *
 * Example:
 * <code>
 * <?php
 * require_once ("sms_api.php");
 * $mysms = new sms();
 * echo $mysms->session;
 * echo $mysms->getbalance();
 * // $mysms->token_pay("1234567890123456"); //spend voucher with SMS credits
 * $mysms->send ("38160123", "netsector", "TEST MESSAGE");
 * ?>
 * </code>
 * @package sms_api
 */

class sms {

    /**
    * Clickatell API-ID
    * @link http://sourceforge.net/forum/forum.php?thread_id=1005106&forum_id=344522 How to get CLICKATELL API ID?
    * @var integer
    */
    var $api_id = "";

    /**
    * Clickatell username
    * @var mixed
    */
    var $user = "";

    /**
    * Clickatell password
    * @var mixed
    */
    var $password = "";

    /**
    * Use SSL (HTTPS) protocol
    * @var bool
    */
    var $use_ssl = true;

    /**
    * Define SMS balance limit below class will not work
    * @var integer
    */
    var $balace_limit = 0;

    /**
    * Gateway command sending method (curl,fopen)
    * @var mixed
    */
    var $sending_method = "fopen";

    /**
    * Does to use facility for delivering Unicode messages
    * @var bool
    */
    var $unicode = false;

    /**
    * Optional CURL Proxy
    * @var bool
    */
    var $curl_use_proxy = false;

    /**
    * Proxy URL and PORT
    * @var mixed
    */
    var $curl_proxy = "http://127.0.0.1:8080";

    /**
    * Proxy username and password
    * @var mixed
    */
    var $curl_proxyuserpwd = "login:secretpass";

    /**
    * Callback
    * 0 - Off
    * 1 - Returns only intermediate statuses
    * 2 - Returns only final statuses
    * 3 - Returns both intermediate and final statuses
    * @var integer
    */
    var $callback = 0;

    /**
    * Session variable
    * @var mixed
    */
    var $session;

    /**
    * Class constructor
    * Create SMS object and authenticate SMS gateway
    * @return object New SMS object.
    * @access public
    */
    function sms ($api_id, $username, $password) {

  $this->api_id = $api_id;
  $this->user = $username;
  $this->password = $password;

        if ($this->use_ssl) {
            $this->base   = "http://api.clickatell.com/http";
            $this->base_s = "https://api.clickatell.com/http";
        } else {
            $this->base   = "http://api.clickatell.com/http";
            $this->base_s = $this->base;
        }

        $this->_auth();
    }

    /**
    * Authenticate SMS gateway
    * @return mixed  "OK" or script die
    * @access private
    */
    function _auth() {
      $comm = sprintf ("%s/auth?api_id=%s&user=%s&password=%s", $this->base_s, $this->api_id, $this->user, $this->password);
        $this->session = $this->_parse_auth ($this->_execgw($comm));
    }

    /**
    * Query SMS credis balance
    * @return integer  number of SMS credits
    * @access public
    */
    function getbalance() {
      $comm = sprintf ("%s/getbalance?session_id=%s", $this->base, $this->session);
        return $this->_parse_getbalance ($this->_execgw($comm));
    }

    /**
    * Send SMS message
    * @param to mixed  The destination address.
    * @param from mixed  The source/sender address
    * @param text mixed  The text content of the message
    * @return mixed  "OK" or script die
    * @access public
    */
    function send($to=null, $from=null, $text=null) {

      /* Check SMS credits balance */
      if ($this->getbalance() < $this->balace_limit) {
          die ("You have reach the SMS credit limit!");
      };

      /* Check SMS $text length */
        if ($this->unicode == true) {
            $this->_chk_mbstring();
            if (mb_strlen ($text) > 210) {
              die ("Your unicode message is too long! (Current lenght=".mb_strlen ($text).")");
          }
          /* Does message need to be concatenate */
            if (mb_strlen ($text) > 70) {
                $concat = "&concat=3";
          } else {
                $concat = "";
            }
        } else {
            if (strlen ($text) > 459) {
              die ("Your message is too long! (Current lenght=".strlen ($text).")");
          }
          /* Does message need to be concatenate */
            if (strlen ($text) > 160) {
                $concat = "&concat=3";
          } else {
                $concat = "";
            }
        }

      /* Check $to and $from is not empty */
        if (empty ($to)) {
          die ("You not specify destination address (TO)!");
      }
        if (empty ($from)) {
          die ("You not specify source address (FROM)!");
      }

      /* Reformat $to number */
        $cleanup_chr = array ("+", " ", "(", ")", "\r", "\n", "\r\n");
        $to = str_replace($cleanup_chr, "", $to);

      /* Send SMS now */
      $comm = sprintf ("%s/sendmsg?session_id=%s&to=%s&from=%s&text=%s&callback=%s&unicode=%s%s",
            $this->base,
            $this->session,
            rawurlencode($to),
            rawurlencode($from),
            $this->encode_message($text),
            $this->callback,
            $this->unicode,
            $concat
        );
        return $this->_parse_send ($this->_execgw($comm));
    }

    /**
    * Encode message text according to required standard
    * @param text mixed  Input text of message.
    * @return mixed  Return encoded text of message.
    * @access public
    */
    function encode_message ($text) {
        if ($this->unicode != true) {
            //standard encoding
            return rawurlencode($text);
        } else {
            //unicode encoding
            $uni_text_len = mb_strlen ($text, "UTF-8");
            $out_text = "";

            //encode each character in text
            for ($i=0; $i<$uni_text_len; $i++) {
                $out_text .= $this->uniord(mb_substr ($text, $i, 1, "UTF-8"));
            }

            return $out_text;
        }
    }

    /**
    * Unicode function replacement for ord()
    * @param c mixed  Unicode character.
    * @return mixed  Return HEX value (with leading zero) of unicode character.
    * @access public
    */
    function uniord($c) {
        $ud = 0;
        if (ord($c{0})>=0 && ord($c{0})<=127)
            $ud = ord($c{0});
        if (ord($c{0})>=192 && ord($c{0})<=223)
            $ud = (ord($c{0})-192)*64 + (ord($c{1})-128);
        if (ord($c{0})>=224 && ord($c{0})<=239)
            $ud = (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
        if (ord($c{0})>=240 && ord($c{0})<=247)
            $ud = (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
        if (ord($c{0})>=248 && ord($c{0})<=251)
            $ud = (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
        if (ord($c{0})>=252 && ord($c{0})<=253)
            $ud = (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
        if (ord($c{0})>=254 && ord($c{0})<=255) //error
            $ud = false;
        return sprintf("%04x", $ud);
    }

    /**
    * Spend voucher with sms credits
    * @param token mixed  The 16 character voucher number.
    * @return mixed  Status code
    * @access public
    */
    function token_pay ($token) {
        $comm = sprintf ("%s/http/token_pay?session_id=%s&token=%s",
        $this->base,
        $this->session,
        $token);

        return $this->_execgw($comm);
    }

    /**
    * Execute gateway commands
    * @access private
    */
    function _execgw($command) {
        if ($this->sending_method == "curl")
            return $this->_curl($command);
        if ($this->sending_method == "fopen")
            return $this->_fopen($command);
        die ("Unsupported sending method!");
    }

    /**
    * CURL sending method
    * @access private
    */
    function _curl($command) {
        $this->_chk_curl();
        $ch = curl_init ($command);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,0);
        if ($this->curl_use_proxy) {
            curl_setopt ($ch, CURLOPT_PROXY, $this->curl_proxy);
            curl_setopt ($ch, CURLOPT_PROXYUSERPWD, $this->curl_proxyuserpwd);
        }
        $result=curl_exec ($ch);
        curl_close ($ch);
        return $result;
    }

    /**
    * fopen sending method
    * @access private
    */
    function _fopen($command) {
        $result = '';
        $handler = @fopen ($command, 'r');
        if ($handler) {
            while ($line = @fgets($handler,1024)) {
                $result .= $line;
            }
            fclose ($handler);
            return $result;
        } else {
            die ("Error while executing fopen sending method!<br>Please check does PHP have OpenSSL support and check does PHP version is greater than 4.3.0.");
        }
    }

    /**
    * Parse authentication command response text
    * @access private
    */
    function _parse_auth ($result) {
      $session = substr($result, 4);
        $code = substr($result, 0, 2);
        if ($code!="OK") {
            die ("Error in SMS authorization! ($result)");
        }
        return $session;
    }

    /**
    * Parse send command response text
    * @access private
    */
    function _parse_send ($result) {
      $code = substr($result, 0, 2);
      if ($code!="ID") {
          die ("Error sending SMS! ($result)");
      } else {
          $code = "OK";
      }
        return $code;
    }

    /**
    * Parse getbalance command response text
    * @access private
    */
    function _parse_getbalance ($result) {
      $result = substr($result, 8);
        return (int)$result;
    }

    /**
    * Check for CURL PHP module
    * @access private
    */
    function _chk_curl() {
        if (!extension_loaded('curl')) {
            die ("This SMS API class can not work without CURL PHP module! Try using fopen sending method.");
        }
    }

    /**
    * Check for Multibyte String Functions PHP module - mbstring
    * @access private
    */
    function _chk_mbstring() {
        if (!extension_loaded('mbstring')) {
            die ("Error. This SMS API class is setup to use Multibyte String Functions module - mbstring, but module not found. Please try to set unicode=false in class or install mbstring module into PHP.");
        }
    }

}
