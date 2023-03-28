<?php
class Loader {
  protected $config;

  public function __construct(){
    $openConfig = json_decode(file_get_contents(__DIR__."/../config.json"));
    if(!$openConfig){
      die("Failed open config.json");
    }
    $this->config = $openConfig;
  }

  public static function staticConfig(){
    $openConfig = json_decode(file_get_contents(__DIR__."/../config.json"));
    if(!$openConfig){
      die("Failed open config.json");
    }
    return $openConfig;
  }
}
?>