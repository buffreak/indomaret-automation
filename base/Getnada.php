<?php
require_once __DIR__.'/Request.php';
require_once __DIR__.'/../vendor/autoload.php';

class Getnada
{
  protected $email, $verificationCode, $faker;

  const GETNADA_URL = [
    'check_inbox' => 'https://getnada.com/api/v1/inboxes/',
    'get_message' => 'https://getnada.com/api/v1/messages/html/',
    'refresh_mailbox'=> 'https://getnada.com/api/v1/u/', // Next Domain email name and timestamp
  ];

  public function __construct(){
    $this->faker = Faker\Factory::create('id_ID');
  }
  /**
   * Generate New Email
   * @param bool $random
   * @param string $email if random set to false you must fill $email parameter
   * @return string
   */
  public function getEmail($random = true, $email = ""){
    $this->email = $random ? $this->faker->word().$this->faker->numberBetween(10, 1000)."@vomoto.com" : $email;
    return $this->email;
  }

  /**
   * Get Email By Regex, any pattern will be match to index 1, make sure to wrap regex inside "(...)"
   * @param string $pattern
   * @param int $sleep How Many Time Sleep until email received
   * @return string
   */
  public function getMailboxByRegex($pattern, int $sleep = 5){
      sleep($sleep);
      Request::curl("GET", self::GETNADA_URL['refresh_mailbox'].$this->email."/".time())['body'];
      $checkMailBox = json_decode(Request::curl("GET", self::GETNADA_URL['check_inbox'].$this->email)['body'], true);
      $readInbox = Request::curl("GET", self::GETNADA_URL['get_message'].$checkMailBox['msgs'][0]['uid'])['body'];
      preg_match($pattern, $readInbox, $verificationCode);
      $this->verificationCode = trim($verificationCode[1]);
      return $this->verificationCode;
  }
}