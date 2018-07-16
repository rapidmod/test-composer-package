<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Data;
use \DateTime;

class Format
{
    public function displayDateFromTimestamp($date){
        if(empty($date)){return "";}
        $date = DateTime::createFromFormat("Y-m-d H:i:s", $date);
        return $date->format("m/d/y g:i a");
    }

    /**
     *
     * @param $array
     * @return array
     *
     * this function will walk through an entire array and "normalize" all of the keys
     * creating a slug like key with underscores as spaces splitting Camelcase words and
     * stripping unwanted characters
     *
     */
    public function normalizeArrayKeys($array){
        $ArrayModel = new \RcoreDataArray();
        return $ArrayModel->normalizeKeys($array,"ksort");
        $data = array();
        if(is_array($array) && !empty($array)){
            $i = 0;
            foreach($array as $k => $v){
                if(!is_numeric($k)){
                    $kv = $this->slug(
                        $this->splitCamelCase($k),
                        "_",
                        array("ignore_symbols"=>1)
                    );

                }
                else{$kv = $k;}

                if(empty($kv)){ $kv = $i; $i++; }

                if(!is_array($v) && !empty($v)){ $data[$kv] = $v;}
                else{ $data[$kv] = $this->normalizeArrayKeys($v);}
            }
            ksort($data);
        }
        return $data;
    }

    public  function plain_text($string){
        return preg_replace("%[^a-zA-Z0-9\s]%","",strip_tags($this->string($string)));
    }

    /**
     *Phone($phone)
     * @param (string) $phone
     * //this function only formats us 10 digit phone numbers
     * @return 1234567890 //numbers as a string
     * @todo implement more number formats
     *
     */
    public function phone($phone){
        $phone = $this->string($phone);
        $phone = trim($phone);
        $phone = preg_replace( "/[^0-9]/" , "" , $phone );
        $len = strlen( $phone );
        if( $len != 10 ){
            if( $len < 10 || $len > 11 ) { $phone = false; }
            if( $len == 11 ) {
                if( $phone[0] === "1" ) { $phone =  ltrim ( $phone , '1' ); }
                else{ $phone = false; }
            }
        }
        if( strlen($phone) == 10 ){ return $phone; }
        else{ return false; }
    }
    /**
     *Phone($phone)
     * @param (string) $phone
     * //this function only formats us 10 digit phone numbers
     * @return 1234567890 //numbers as a string
     * @todo implement more number formats
     *
     */
    public function phone_display($phone){
        $p = $this->phone($phone);
        if( strlen($p) == 10 ){ return "(".substr($p,0,3).") ".substr($p,3,3)."-".substr($p,6); }
        else{ return $this->string($phone); }
    }


    public function string($string,$encoding = "UTF-8"){
        switch(strtolower($encoding)){
            default: $encoding = "UTF-8";
        }
        $check = mb_check_encoding( $string , $encoding );
        if( !$check ){
            $a_encoding = mb_detect_encoding( $string , "auto" );
            $string = mb_convert_encoding($string, $encoding , $a_encoding);
        }
        $string = str_ireplace("#39;", "'", $string);
        $string = str_ireplace("quot;", "\"", $string);

        return (string)$string;
    }

    public function slug($string, $sep = "-",$options = array()){
        $string = $this->string( trim( strtolower( $string )) );
        if(!isset($options["ignore_symbols"])){
            $string = str_replace("&",$sep."and".$sep, $string);
            $string = str_replace("@",$sep."at".$sep, $string);
            $string = str_replace("'",$sep, $string);
        }
        if($sep !== "-"){
            $string = str_replace("-",$sep,$string);
        }
        //$string = str_replace(".","-dot-", $string);
        $string = str_replace(",",$sep, $string);
        $string = preg_replace( '/[^A-Za-z0-9-]+/', $sep, $string );
        $string = str_replace($sep.$sep.$sep,$sep, $string);
        $string = str_replace($sep.$sep,$sep, $string);
        return trim($string,$sep);
    }

    # @url http://stackoverflow.com/questions/4519739/split-camelcase-word-into-words-with-php-preg-match-regular-expression
    public function splitCamelCase($ccWord){
        $regex =
            '/# Match position between camelCase "words".
            (?<=[a-z])  # Position is after a lowercase,
            (?=[A-Z])   # and before an uppercase letter.
            /x'
        ;
        $result =  preg_split($regex, $ccWord);
        if(is_array($result)){
            return trim(implode(" ",$result));
        }else{
            return $result;
        }
    }
}