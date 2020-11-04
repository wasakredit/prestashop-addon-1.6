<?php

namespace Sdk;

/**
 * Client
 *
 * The main client that orchestrates requests to the API.
 * It also delegates other responsibilities, such as fetching an access token
 * to other classes.
 *
 * @package    Client PHP SDK
 * @author     Jim Skogman <jim.skogman@starrepublic.com>
 */

class Client
{

  private $base_url;
  private $token_client;
  private $client_id;
  private $client_secret;
  private $test_mode;
  private $api_client;
  private $codes = array(
    '100' => 'Continue',
    '200' => 'OK',
    '201' => 'Created',
    '202' => 'Accepted',
    '203' => 'Non-Authoritative Information',
    '204' => 'No Content',
    '205' => 'Reset Content',
    '206' => 'Partial Content',
    '300' => 'Multiple Choices',
    '301' => 'Moved Permanently',
    '302' => 'Found',
    '303' => 'See Other',
    '304' => 'Not Modified',
    '305' => 'Use Proxy',
    '307' => 'Temporary Redirect',
    '400' => 'Bad Request',
    '401' => 'Unauthorized',
    '402' => 'Payment Required',
    '403' => 'Forbidden',
    '404' => 'Not Found',
    '405' => 'Method Not Allowed',
    '406' => 'Not Acceptable',
    '409' => 'Conflict',
    '410' => 'Gone',
    '411' => 'Length Required',
    '412' => 'Precondition Failed',
    '413' => 'Request Entity Too Large',
    '414' => 'Request-URI Too Long',
    '415' => 'Unsupported Media Type',
    '416' => 'Requested Range Not Satisfiable',
    '417' => 'Expectation Failed',
    '500' => 'Internal Server Error',
    '501' => 'Not Implemented',
    '503' => 'Service Unavailable'
  );

  public function __construct($clientId, $clientSecret, $testMode = true)
  {
    $this->base_url = wasa_config('base_url');
    $this->token_client = new AccessToken($clientId, $clientSecret, $testMode);
    $this->api_client = new Api($clientId, $clientSecret, $testMode);
  }

  public function calculate_leasing_cost($calculateLeasingBody)
  {
    return $this->api_client->execute($this->base_url . "/v1/leasing", "POST", $calculateLeasingBody);
  }

  public function calculate_total_leasing_cost($calculateTotalLeasingCostBody)
  {
    return $this->api_client->execute($this->base_url . "/v1/leasing/total-cost", "POST", $calculateTotalLeasingCostBody);
  }

  public function create_checkout($createCheckoutBody)
  {
    return $this->api_client->execute($this->base_url . "/v2/checkouts", "POST", $createCheckoutBody);
  }

  public function validate_allowed_leasing_amount($amount)
  {
    return $this->api_client->execute($this->base_url . "/v1/leasing/validate-amount?amount=" . $amount, "GET", null);
  }

  public function create_product_widget($productPrice)
  {
    return $this->api_client->execute($this->base_url . "/v1/checkouts/widget", "POST", $productPrice);
  }

}
