<?php
require_once __DIR__.'/base/Indomaret.php';
require_once __DIR__.'/base/AdaOtp.php';

$adaotp = new AdaOtp();
$indomaret = new Indomaret();
echo "1. Auto Register\n2. Pilih Barang yang akan di Checkout\n3. Auto Checkout\n4. Hapus Semua Barang\nMasukkan Pilihan: ";
$pilihan = (int) Request::input();
if($pilihan === 1){
  while(1){
    $phoneNumber = $adaotp->order();
  
    if($phoneNumber){
      if($indomaret->checkIsAlreadyRegistered($phoneNumber)){
        if($indomaret->preRegisterNumber()){
          if($indomaret->verificationNumber()){
            $otp = $adaotp->getOrder('/([0-9]{3,})/');
            if($otp){
              $validation = $indomaret->validationNumber($otp);
              if($validation){
                $register = $indomaret->userRegistration();
                if($register){
                  $activation = $indomaret->userActivation();
                  if($activation){
                    $credential = $indomaret->getLastUserRegistered();
                    $indomaret->login($credential['email'], $credential['password'], $credential['device_id'])->getAccountDetail()->setAddress();
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}else if($pilihan === 2){
  
}
?>