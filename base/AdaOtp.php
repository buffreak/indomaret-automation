<?php
require_once __DIR__."/Request.php";
require_once __DIR__."/Loader.php";
require_once __DIR__.'/../vendor/autoload.php';

class AdaOtp extends Loader{

  protected $phoneNumber, $orderId, $otp, $faker;

  public function __construct(){
    $this->faker = Faker\Factory::create('id_ID');
    parent::__construct();
  }

  public function order(){
    $response = Request::curl("GET", "https://adaotp.com/api/set-orders/".$this->config->adaotp->apikey."/".$this->config->adaotp->service_id);
    $body = json_decode($response['body'], true);
    if($body['success']){
      $this->phoneNumber = $body["data"]["data"]["number"];
      $this->orderId = $body["data"]["data"]["order_id"];
      return $body["data"]["data"]["number"];
    }
    return false;
  }

  public function getOrder($pattern){
    sleep($this->config->adaotp->delay);
    $response = Request::curl("GET", "https://adaotp.com/api/get-orders/".$this->config->adaotp->apikey."/".$this->orderId);
    $body = json_decode($response['body'], true);
    if($body['success']){
      $body = json_decode($body["data"]["data"][0]["sms"], true);
      @preg_match($pattern, $body[0]['sms'], $otp);
      if($otp){
        $this->finishOrder();
        $this->otp = trim($otp[1]);
        return trim($otp[1]);
      }
    }
    $this->cancelOrder();
    return false;
  }

  protected function finishOrder(){
    $response = Request::curl("GET", "https://adaotp.com/api/finish-orders/".$this->config->adaotp->apikey."/".$this->orderId);
    $body = json_decode($response['body'], true);
    if($body['success']){
      return true;
    }
    return false;
  }

  protected function cancelOrder(){
    $response = Request::curl("GET", "https://adaotp.com/api/cancle-orders/".$this->config->adaotp->apikey."/".$this->orderId);
    $body = json_decode($response['body'], true);
    if($body['success']){
      return true;
    }
    return false;
  }

}
?>