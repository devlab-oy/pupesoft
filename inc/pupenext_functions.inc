<?php

if (!function_exists("pupenext_rest")) {
  function pupenext_rest($data, $resource, $method = "GET") {
    global $palvelin_pupenext, $pupesoft_session;

    $parameters = array(
      "method" => $method,
      "data" => $data,
      "url" => "{$palvelin_pupenext}{$resource}",
      "cookie" => "pupesoft_session={$pupesoft_session}"
    );

    $response = pupesoft_rest($parameters);

    return $response[1];
  }
}

if (!function_exists("qr_koodi")) {
  function qr_koodi($string, $size = 3, $format = "jpg") {
    $data = array(
      "string" => $string,
      "format" => $format,
      "size" => $size
    );

    $response = pupenext_rest($data, "qr_codes/generate");

    return file_get_contents($response["filename"]);
  }
}
