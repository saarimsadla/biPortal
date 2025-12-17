<?php
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);


// The nested array to hold all the arrays
$the_big_array = [];
// Open the file for reading
if (($h = fopen($argv[1], "r")) !== FALSE)
{
  // Each line in the file is converted into an individual array that we call $data
  // The items of the array are comma separated
  if($argv[4] != null){
    $delim = $argv[4];
  }else{
    $delim = ",";
  }

  $file = fopen($argv[2],"w");
  $decrypt_columns = explode(',',$argv[3]);
  $counter = 0;
  while (($data = fgetcsv($h, 1000, $delim)) !== FALSE)
  {
    // Each individual array is being pushed into the nested array
    $the_big_array = [];
    $the_big_array = $data;

    //echo var_dump($the_big_array);

    //foreach($the_big_array as $subarray){
      //foreach( $subarray1 as $key => $subarray){

      if ($counter == 0){
        fwrite($file, implode($delim,$the_big_array).'
        ');
        $counter++;
        continue;

      }

      foreach($decrypt_columns as $column_num){
        $the_big_array[$column_num] = decrypt($the_big_array[$column_num]);
      }


      fwrite($file, implode($delim,$the_big_array).'
      ');

      //}
    //}

  }

  // Close the file
  fclose($file);
  fclose($h);
}






function decrypt(string $input): string {
    if (strpos($input, 'v2:') === 0) {
        $encrypted = explode(':', $input, 2)[1];
        return decrypt_new($encrypted);
    } else {
        //return $input;
        return decrypt_old($input);
    }
}

function decrypt_old(string $in): string {
    $key = "appsonoma2282";
    $pieces = explode('|', $in);
    $encrypted = base64_decode($pieces[0]);
    $iv = base64_decode($pieces[1]);
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
    $ks = mcrypt_enc_get_key_size($td);
    $_key = substr(md5($key), 0, $ks);
    mcrypt_generic_init($td, $_key, $iv);
    $decrypted = mdecrypt_generic($td, $encrypted);
    $check = explode(';', $decrypted);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    $decryptPieces = explode(':', $check[0]);
    if( empty(trim($check[1])) && !empty($decryptPieces[2])) {
        return trim(str_replace('"', '', $decryptPieces[2]));
    } else {
        return false;
    }
}

function decrypt_new(string $in): string {
    $payload = base64_decode($in);

    $ivLength = openssl_cipher_iv_length('AES-256-CBC');

    $iv = substr($payload, 0, $ivLength);

    $subarrayue = substr($payload, $ivLength);

    //$salt = 'sdfljklksdjfo23432';
    $salt = '';
    $key=openssl_digest('appsonoma2282' . $salt, 'sha256');

    $decrypted = openssl_decrypt($subarrayue, 'AES-256-CBC', $key,
        OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        throw new Exception('Decryption failed: ' . openssl_error_string());
    }

    return json_decode($decrypted, true);

}
