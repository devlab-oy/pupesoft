<?php

class NetsCloudClient
{
  private $baseUrl;
  private $username;
  private $password;
  private $terminalId;
  private $token;
  private $latestTransaction;

  /**
   * NetsCloudClient constructor.
   *
   * @param string $base_url
   * @param string $username
   * @param string $password
   * @param string $terminal_id
   */
  public function __construct($base_url, $username, $password, $terminal_id) {
    $this->baseUrl = $base_url;
    $this->username = $username;
    $this->password = $password;
    $this->terminalId = $terminal_id;
  }

  /**
   * @param int $amount Amount in cents
   * @param int $transaction_type Transaction type as an integer
   *
   * @return bool
   */
  public function startTransaction($amount, $transaction_type = 0) {
    $this->log(
      "Starting transaction. Amount: {$amount}. " .
      "Transaction type: {$this->transactionTypeToString($transaction_type)}."
    );

    $params = array(
      'transactionType' => $this->transactionTypeToString($transaction_type),
      'amount' => $amount,
    );

    $response = $this->request('terminal/' . $this->terminalId . '/transaction', $params);

    if ($response['status'] == 201) {
      $this->log(
        "Successfully completed transaction. " .
        "Amount: {$amount}. " .
        "Transaction type: {$this->transactionTypeToString($transaction_type)}."
      );

      $this->latestTransaction = $response['body']['result'][0];

      return true;
    }
    else {
      $this->log(
        "Failed to complete transaction. " .
        "Amount: {$amount}. " .
        "Transaction type: {$this->transactionTypeToString($transaction_type)}. " .
        "Response status: {$response['status']}. " .
        "Response body: " . json_encode($response['body']) . '.'
      );

      return false;
    }
  }

  /**
   * @param int $amount Amount in cents
   *
   * @return bool
   */
  public function reverseTransaction($amount) {
    $this->log("Reversing transaction. Amount: {$amount}.");

    $params = array(
      'amount' => $amount,
    );

    $response = $this->request('terminal/' . $this->terminalId . '/transaction', $params, 'DELETE');

    if ($response['status'] == 201) {
      $this->log(
        "Successfully reversed transaction. " .
        "Amount: {$amount}. "
      );

      $this->latestTransaction = $response['body']['result'][0];

      return true;
    }
    else {
      $this->log(
        "Failed to reverse transaction. " .
        "Amount: {$amount}. " .
        "Response status: {$response['status']}. " .
        "Response body: " . json_encode($response['body']) . '.'
      );

      return false;
    }
  }

  /**
   * @return array|null
   */
  public function getLatestTransaction() {
    if ($this->latestTransaction) {
      $this->log("Returning cached data for latest transaction");

      return $this->latestTransaction;
    }

    $this->log("Starting to fetch latest transation.");

    $response = $this->request('terminal/' . $this->terminalId . '/transaction', array(), 'GET');

    if ($response['status'] == 200) {
      $this->log("Successfully fetched latest transaction.");

      $this->latestTransaction = $response['body']['result'];

      return $this->latestTransaction;
    }
    else {
      $this->log(
        "Failed to fetch latest transaction. " .
        "Response status: {$response['status']}. " .
        "Response body: " . json_encode($response['body']) . '.'
      );

      return null;
    }
  }

  /**
   * @return string
   */
  public function getCustomerReceipt() {
    $latest_transaction = $this->getLatestTransaction();

    return $latest_transaction['customerReceipt'];
  }

  /**
   * @return string
   */
  public function getMerchantReceipt() {
    $latest_transaction = $this->getLatestTransaction();

    return $latest_transaction['merchantReceipt'];
  }

  /**
   * @throws Exception
   */
  public function getPreviousReceipts() {
    throw new Exception("Not implemented since the NETS Cloud API doesn't support fetching previous transactions");
  }

  private function log($message) {
    pupesoft_log('nets_cloud_client', $message);
  }

  /**
   * @param int $transaction_type
   *
   * @return string
   */
  private function transactionTypeToString($transaction_type) {
    $map = array(
      0 => 'purchase',
      1 => 'returnOfGoods',
    );

    return $map[$transaction_type];
  }

  /**
   * @param string $path
   * @param array $data
   * @param string $method
   * @param bool $include_token
   *
   * @return array
   */
  private function request($path, $data, $method = 'POST', $include_token = true) {
    $headers = array(
      'Accept: application/json',
      'Content-Type: application/json',
    );

    if ($include_token) {
      $headers[] = 'Authorization: Bearer ' . $this->getToken();
    }

    $params = array(
      'url' => $this->baseUrl . '/' . $path,
      'data' => $data,
      'method' => $method,
      'headers' => $headers,
    );

    list($response_status, $response_body) = pupesoft_rest($params);

    return array(
      'status' => $response_status,
      'body' => $response_body,
    );
  }

  /**
   * @return string
   */
  private function getToken() {
    if (!empty($this->token)) return $this->token;

    $params = array(
      'username' => $this->username,
      'password' => $this->password,
    );

    $response = $this->request('login', $params, 'POST', false);

    if ($response['status'] == 200) {
      $this->log("Authenticated as {$this->username}.");
    }
    else {
      $this->log(
        "Failed to authenticate as {$this->username}. " .
        "Response status {$response['status']}. " .
        "Response body: {$response['body']}."
      );
    }

    $this->token = $response['body']['token'];

    return $this->token;
  }
}
