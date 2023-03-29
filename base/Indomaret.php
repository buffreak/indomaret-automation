<?php
require_once __DIR__.'/Loader.php';
require_once __DIR__."/AdaOtp.php";
require_once __DIR__."/Getnada.php";
require_once __DIR__.'/../vendor/autoload.php';

class Indomaret extends Loader {

  protected $adaotp, $phoneNumber, $email, $password, $faker, $getnada, $registerData, $loginData, $jwtToken, $mfpId, $custId, $deviceId;

  public function __construct(){
    parent::__construct();
    $this->adaotp = new AdaOtp();
    $this->faker = Faker\Factory::create('id_ID');
    $this->getnada = new Getnada();
  }

  protected function bearerSignaturePreRegistration($phoneNumber, $timestamp){
    return strtoupper(sha1(strtoupper(md5(strtoupper('0tPcU$t0M3rKl!Kklikindomaret'.$phoneNumber.$timestamp)))));
  }

  protected function bearerSignatureShoppingChart(){
    return strtoupper(sha1(strtoupper(md5("Auth.V.00.00.00.1"))));
  }

  public function checkIsAlreadyRegistered($phoneNumber): bool{
    $response = Request::curl(
      "GET", 
      "https://prd-api.klikindomaret.com/Account/Customer/MobilePhone?MobilePhone=".$phoneNumber,
      null,
      [...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')]
    );
    $body = json_decode($response['body'], true);
    if($body){
      if($body["statusCode"] === "204" && $body["message"] === "NoContent"){
        $this->phoneNumber = $phoneNumber;
        return true;
      }
    }
    return false;
  }

  public function preRegisterNumber(){
    sleep(1);
    $timenow = date("m/d/Y H:i:s");
    $query = http_build_query(['MobilePhone' => $this->phoneNumber, 'timestamp' => $timenow]);
    $response = Request::curl(
      "GET",
      "https://prd-api.klikindomaret.com/Account/PreRegistration/Verification?".$query,
      null,
      [
        "authorization: Bearer ".$this->bearerSignaturePreRegistration($this->phoneNumber, $timenow),
        ...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt'),
      ]
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      return true;
    }
    return false;
  }

  public function verificationNumber(){
    sleep(1);
    $timenow = date("m/d/Y H:i:s");
    $param = json_encode(['MobilePhone' => $this->phoneNumber, 'Method' => 'SMS', 'TimeStamp' => $timenow, 'Type' => 'regist']);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/PreRegistration/Verification",
      $param,
      [
        "authorization: Bearer ".$this->bearerSignaturePreRegistration($this->phoneNumber, $timenow),
        ...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt'),
      ]
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      return true;
    }
    return false;
  }

  public function validationNumber($otp){
    sleep(1);
    $timenow = date("m/d/Y H:i:s");
    $param = json_encode(['MobilePhone' => $this->phoneNumber, 'OTPCode' => $otp, 'Method' => 'SMS', 'TimeStamp' => $timenow, 'Type' => 'regist']);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/PreRegistration/Validation",
      $param,
      [
        "authorization: Bearer ".$this->bearerSignaturePreRegistration($this->phoneNumber, $timenow),
        ...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt'),
      ]
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      return true;
    }
    return false;
  }

  public function userRegistration(){
    sleep(1);
    $birth = $this->faker->dateTimeBetween('-30 years', '-15 years')->format('Y-m-d');
    $this->password = str_replace(" ", "", $this->faker->name('male')).$this->faker->regexify('[0-9]{2}');
    $this->deviceId = $this->faker->regexify('[a-f0-9]{16}');
    $ipAddress = $this->faker->ipv4();

    $this->email = $this->getnada->getEmail();
    $param = json_encode([
      'ID' => '00000000-0000-0000-0000-000000000000',
      'Mobile' => $this->phoneNumber,
      'FName' => $this->faker->firstName('male'),
      'LName' => $this->faker->lastName('male'),
      'Email' => $this->email,
      'Gender' => 'Pria',
      'DateOfBirth' => $birth.'T07:00:00.000Z',
      'DateOfBirthStringFormatted' => $birth,
      'DateOfBirthExists' => '0001-01-01T00:00:00',
      'Password' => $this->password,
      'ConfirmPassword' => $this->password,
      'ReferrerCode' => $this->config->indomaret->referral_code,
      'DeviceID' =>  $this->deviceId,
      'IsNewsLetterSubscriber' => 0,
      'IsSubscribed' => 0,
      'AllowSMS' => false,
      'IPAddress' => $ipAddress,
      'IsActivated' => false,
      'MobileVerified' => true,
      'IsConfirmed' => true,
      'IsFromOtherSystem' => false,
      'IsNewAccount' => true,
      'IsUpload' => false,
      'LastUpdate' => "0001-01-01T00:00:00",
      'OTPAvailable' => 0,
      'OTPCount' => 0,
      'OTPValidationExpired' => false,
      'Origin' => 'Registrasi Website',
      'TypePushEmail' => 0,
      'isVaildPhoneNo' => false
    ]);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/Customer/Registration?districtID=2483&mfp_id=1",
      $param,
      Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      $this->registerData = $body['data'];
      return true;
    }
    return false;
  }

  public function userActivation(){
    sleep(1);
    $otp = $this->getnada->getMailboxByRegex('/KodePIN=(.*?)"/', 20);
    $param = json_encode(['PINCode' => $otp, 'Token' => $this->registerData['ResponseObject']]);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/Customer/ActivationWithPINCode?mfp_id=1",
      $param,
      Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      fwrite(fopen(__DIR__.'/../data/indomaret_results.txt', 'a'), $this->phoneNumber."|".$this->email."|".$this->password."|".$this->deviceId.PHP_EOL);
      return true;
    }
    return false;
  }

  public function getLastUserRegistered(){
    return ['email' => $this->email, 'phone_number' => $this->phoneNumber, 'password' => $this->password, 'device_id' => $this->deviceId, 'data' => $this->registerData];
  }

  public function login($email, $password, $deviceId){
    sleep(1);
    $param = json_encode(['Email' => $email, 'Password' => $password]);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/Customer/Login?isMobile=true&method=APPS&mfp_id=1&deviceID=".$deviceId."&deviceName=Chrome%20WebView&device_token=null&districtID=2483&type=REGIST&Location=undefined",
      $param,
      Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      $this->email = $email;
      $this->password = $password;
      $this->jwtToken = $body['data']['ResponseObject']['ID']."#".$body['data']['ResponseObject']['Token'];
      $this->mfpId = $body['data']['ResponseID'];
      $this->custId = $body['data']['ResponseObject']['ID'];
    }
    return $this;
  }

  public function getAccountDetail(){
    sleep(1);
    $response = Request::curl(
      "GET",
      "https://prd-api.klikindomaret.com/Account/Customer/Account?access_token=".$this->mfpId,
      null,
      ["applicationkey: indomaret", "authorization: Bearer ".$this->jwtToken, ...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')]
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      $this->loginData = $body['data'][0];
    }
    return $this;
  }

  public function getLastUserLogin(){
    return ['email' => $this->email, 'phone_number' => $this->phoneNumber, 'password' => $this->password, 'data' => $this->loginData];
  }

  public function setAddress(){
    sleep(1);
    $param = (array) $this->config->indomaret->address;
    $param['CustomerID'] = $this->custId;
    $param['Phone'] = $this->loginData['Mobile'];
    $param['ReceiverPhone'] = $this->loginData['Mobile'];
    $param = json_encode($param);
    $response = Request::curl(
      "POST",
      "https://prd-api.klikindomaret.com/Account/CustomerAddress/InsertAddress?mfp_id=".$this->mfpId,
      $param,
      ["applicationkey: indomaret", "authorization: Bearer ".$this->jwtToken, ...Request::splitTextFromFile(__DIR__.'/../data/cookie/indomaret_1.txt')]
    );
    $body = json_decode($response['body'], true);
    if($body['statusCode'] === "200" && $body['message'] === "OK"){
      return true;
    }
    return false;
  }

  public function getBarang($keyword){

  }

}
?>