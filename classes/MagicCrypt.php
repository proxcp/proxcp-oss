<?php
  namespace org\magiclen\magiccrypt;

	class MagicCrypt {
		private $iv;
		private $key;
		private $bit;

		public function __construct($key = '', $bit = 128, $iv = '') {
		    switch($bit){
		        case 64: {
		            $this->key = MagicCrypt::crc64($key);
		            if($iv != ''){
				        $this->iv = MagicCrypt::crc64($iv);
			        }else{
				        $this->iv = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
			        }
		        }
		        break;
		        case 128:
		        case 192:
		        case 256: {
		            switch ($bit) {
		                case 128:
		                    $this->key = hash('MD5', $key, true);
		                    break;
		                case 192:
		                    $this->key = hash('tiger192,3', $key, true);
		                    break;
		                case 256:
		                    $this->key = hash('SHA256', $key, true);
		                    break;
		            }
		            if($iv != ''){
				        $this->iv = hash('MD5', $iv, true);
			        }else{
				        $this->iv = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
			        }
		        }
		        break;
                default:
                    throw new \Exception('The key must be 8 bytes(64 bits), 16 bytes(128 bits), 24 bytes(192 bits) or 32 bytes(256 bits)!');
		    }
		    $this->bit = $bit;
		}

		public function encrypt($str) {
        $ciphertext = openssl_encrypt(
          $str,
          'aes-256-cbc',
          $this->key,
          OPENSSL_RAW_DATA,
          $this->iv
        );
        return base64_encode($ciphertext);
		}

		public function decrypt($str) {
      return openssl_decrypt(
        base64_decode($str),
        'aes-256-cbc',
        $this->key,
        OPENSSL_RAW_DATA,
        $this->iv
      );
		}

		private static function crc64Table() {
            $crc64tab = [];
            $poly64rev = (0x42F0E1EB << 32) | 0xA9EA3693;

            $mask1 = 1 << 63;
            $mask2 = 1;
            for ($i = 1; $i < 64; ++$i) {
		        $mask2 = ($mask2 << 1) + 1;
		    }

            for ($i = 0; $i < 256; ++$i) {
            	$v = $i;
            	for ($j = 0; $j < 64; ++$j) {
            		if (($v & $mask1) == 0) {
            			$v = $v << 1;
            		} else {
            			$v = $v << 1;
                    	$v = $v ^ $poly64rev;
            		}
            	}
            	$crc64tab[$i] = $v & $mask2;
            }

            return $crc64tab;
        }

        private static function crc64($string) {
            static $crc64tab;
            if ($crc64tab === null) {
                $crc64tab = MagicCrypt::crc64Table();
            }

            $h8Mask = ~(0xff << 56);

            $crc = ~0;
            $length = strlen($string);
            for ($i = 0; $i < $length; ++$i) {
            	$lookupidx = ((($crc >> 56) & $h8Mask) ^ ord($string[$i])) & 0xff;
                $crc = ($crc << 8) ^ $crc64tab[$lookupidx];
            }
            $crc = $crc ^ ~0;

            return pack('CCCCCCCC', ($crc >> 56) & $h8Mask, (($crc << 8) >> 56) & $h8Mask, (($crc << 16) >> 56) & $h8Mask, (($crc << 24) >> 56) & $h8Mask, (($crc << 32) >> 56) & $h8Mask, (($crc << 40) >> 56) & $h8Mask, (($crc << 48) >> 56) & $h8Mask, (($crc << 56) >> 56) & $h8Mask);
        }
	}
?>
