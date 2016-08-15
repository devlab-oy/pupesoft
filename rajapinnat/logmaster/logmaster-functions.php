<?php

if (!function_exists('logmaster_send_email')) {
  function logmaster_send_email($params) {
    $body        = "";
    $email       = $params['email'];
    $email_array = $params['email_array'];
    $log_name    = $params['log_name'];
    $subject     = "";

    if (empty($email) or !is_array($email_array) or count($email_array) == 0) {
      return;
    }

    switch ($log_name) {
    case 'logmaster_outbound_delivery_confirmation':
      $subject = t("Logmaster: Keräyksen kuittaus");
      break;

    case 'logmaster_inbound_delivery_confirmation':
      $subject = t("Logmaster: Saapumisen kuittaus");
      break;

    case 'logmaster_stock_report':
      $subject = t("Logmaster saldovertailu");
      break;

    default:
      return;
    }

    foreach ($email_array as $msg) {
      $body .= $msg."<br>\n";
    }

    $args = array(
      'body' => $body,
      'cc' => '',
      'ctype' => 'html',
      'subject' => $subject,
      'to' => $email,
    );

    if (pupesoft_sahkoposti($args)) {
      pupesoft_log($log_name, "Sähköposti lähetetty onnistuneesti osoitteeseen {$email}");
    }
    else {
      pupesoft_log($log_name, "Sähköpostin lähetys epäonnistui osoitteeseen {$email}");
    }
  }
}

if (!function_exists('logmaster_send_file')) {
  function logmaster_send_file($filename) {
    global $kukarow, $yhtiorow, $logmaster;

    // Lähetetään aina UTF-8 muodossa
    $ftputf8 = true;

    $ftphost = $logmaster['host'];
    $ftpuser = $logmaster['user'];
    $ftppass = $logmaster['pass'];
    $ftppath = $logmaster['path'];

    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    return $palautus;
  }
}

if (!function_exists('logmaster_field')) {
  function logmaster_field($column_name) {

    switch ($column_name) {
    case 'ItemNumber':
      $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ItemNumber' ", '', '', 'selitetark');

      if (empty($key)) {
        $key = 'tuoteno';
      }

      break;
    case 'ProdGroup1':
      $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ProdGroup1' ", '', '', 'selitetark');

      if (empty($key)) {
        $key = 'try';
      }

      break;
      case 'ProdGroup2':
        $key = t_avainsana('POSTEN_TKOODI', '', " and avainsana.selite = 'ProdGroup2' ", '', '', 'selitetark');

        if (empty($key)) {
          $key = '';
        }

        break;
    default:
      die(t('Annettu arvo ei ole käytössä'));
    }

    return mysql_real_escape_string($key);
  }
}

if (!function_exists('check_file_extension')) {
  function check_file_extension($file_name, $extension) {

    if (!is_file($file_name) or !is_readable($file_name)) {
      return false;
    }

    $path_parts = pathinfo($file_name);

    if (empty($path_parts['extension'])) {
      return false;
    }

    if (strtoupper($path_parts['extension']) == strtoupper($extension)) {
      return true;
    }

    return false;
  }
}

if (!function_exists('logmaster_message_type')) {
  function logmaster_message_type($file_name) {
    $extension = check_file_extension($file_name, 'XML');

    if ($extension === false) {
      return false;
    }

    $xml = simplexml_load_file($file_name);

    if (!is_object($xml)) {
      return false;
    }

    if (isset($xml->MessageHeader) and isset($xml->MessageHeader->MessageType)) {
      return trim($xml->MessageHeader->MessageType);
    }

    return false;
  }
}
