<?php
require_once __DIR__."/Loader.php";

class Request extends Loader{

  public static function curl(string $method = "GET", string $url, string $postData = null, array $headers = []): array{
    $config = Loader::staticConfig();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/110.0");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    count($headers) > 0 ? curl_setopt($ch, CURLOPT_HTTPHEADER, $headers) : '';
    $postData !== null ? curl_setopt($ch, CURLOPT_POSTFIELDS, $postData) : '';
    $exec = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    if($config->debug_mode){
      print_r("\n\n".substr($exec, 0, $headerSize)."\n".substr($exec, $headerSize)."\n\n");
    }
    return ['header' => substr($exec, 0, $headerSize), 'body' => substr($exec, $headerSize)];
  }

  public static function input(){
    return trim(fgets(STDIN));
  }

  public static function splitTextFromFile($filename, $delimiter = "\n"): array {
    $container = [];
    $contents = explode($delimiter, file_get_contents($filename));
    foreach($contents as $content){
      $container[] = trim($content);
    }
    return $container;
  }
}
?>