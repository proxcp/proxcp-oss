<?php
class Language {
  private $_langCode;

  public function __construct($langCode) {
    $this->_langCode = $langCode;
  }

  public function load() {
    $json = file_get_contents('lang/' . $this->_langCode . '.json');
    if($json === false) {
      return false;
    }else{
      $array = json_decode($json, true);
      if(!$array) {
        return false;
      }else{
        return $array;
      }
    }
  }
}
?>
