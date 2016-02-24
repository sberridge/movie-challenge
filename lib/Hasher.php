<?php
class Hasher {
    public static function hash($string) {
        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890/.";
        $salt = "";
        for($i = 0; $i < 22; $i++) {
            $salt .= $characters[mt_rand(0,strlen($characters)-1)];
        }
        $hash = crypt($string,"$2y$10$".$salt);
        return $hash;
    }
    
    public static function check($string,$hash) {
        $check = crypt($string,$hash);
        return $check === $hash;
    }
}
?>
