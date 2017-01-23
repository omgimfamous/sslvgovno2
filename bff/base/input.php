<?php namespace bff\base;

class Input
{
    protected $superglobal_lookup = array( "g" => "_GET", "p" => "_POST", "r" => "_REQUEST", "c" => "_COOKIE", "s" => "_SERVER", "e" => "_ENV", "f" => "_FILES" );
    protected $locale;
    const convertSingle = 100;
    const convertKeys = 200;

    public function __construct()
    {
        foreach( array_keys($_COOKIE) as $obd8cb3518a5ce598a ) 
        {
            unset($_REQUEST[$obd8cb3518a5ce598a]);
            if( isset($_POST[$obd8cb3518a5ce598a]) ) 
            {
                $_REQUEST[$obd8cb3518a5ce598a] =& $_POST[$obd8cb3518a5ce598a];
            }
            else
            {
                if( isset($_GET[$obd8cb3518a5ce598a]) ) 
                {
                    $_REQUEST[$obd8cb3518a5ce598a] =& $_GET[$obd8cb3518a5ce598a];
                }

            }

        }
        $this->locale = \bff::locale();
    }
    public function clean_array(&$aec9d3b143a42a, $zc5bde74a8f110, &$f3fbd6e2f3ea96987 = array(  ))
    {
        if( !is_array($aec9d3b143a42a) ) 
        {
            $aec9d3b143a42a = array(  );
        }

        foreach( $zc5bde74a8f110 as $a00006jfk => $a00006jfl ) 
        {
            if( is_array($a00006jfl) && !empty($a00006jfl) ) 
            {
                $f3fbd6e2f3ea96987[$a00006jfk] = $this->clean($aec9d3b143a42a[$a00006jfk], $a00006jfl[0], isset($aec9d3b143a42a[$a00006jfk]), $a00006jfl);
            }
            else
            {
                $f3fbd6e2f3ea96987[$a00006jfk] = $this->clean($aec9d3b143a42a[$a00006jfk], $a00006jfl, isset($aec9d3b143a42a[$a00006jfk]));
            }

        }
        return $f3fbd6e2f3ea96987;
    }
    public function clean_array_gpc($v5d9d922fd659eb0c, array $Wcb7e71ef53d, &$sdc220afe87 = array(  ), $adc14b8a3d1c6 = false)
    {
        $a00006jfm =& $GLOBALS[$this->superglobal_lookup[$v5d9d922fd659eb0c]];
        $a00006jfn = !empty($adc14b8a3d1c6);
        foreach( $Wcb7e71ef53d as $a00006jfo => $a00006jfp ) 
        {
            $a00006jfq = ($a00006jfn ? $adc14b8a3d1c6 . $a00006jfo : $a00006jfo);
            if( is_array($a00006jfp) && !empty($a00006jfp) ) 
            {
                $a00006jfr = $a00006jfp[0];
                unset($a00006jfp[0]);
                if( $a00006jfr == TYPE_ARRAY_ARRAY ) 
                {
                    $a00006jfs = $this->clean($a00006jfm[$a00006jfq], TYPE_ARRAY_ARRAY, isset($a00006jfm[$a00006jfq]));
                    foreach( $a00006jfs as $a00006jft => $a00006jfu ) 
                    {
                        $this->clean_array($a00006jfs[$a00006jft], $a00006jfp);
                    }
                }
                else
                {
                    if( $a00006jfr == TYPE_ARRAY ) 
                    {
                        $this->clean($a00006jfm[$a00006jfq], TYPE_ARRAY, isset($a00006jfm[$a00006jfq]));
                        $a00006jfs = $this->clean_array($a00006jfm[$a00006jfq], $a00006jfp);
                    }
                    else
                    {
                        $a00006jfs = $this->clean($a00006jfm[$a00006jfq], $a00006jfr, isset($a00006jfm[$a00006jfq]), $a00006jfp);
                    }

                }

                $sdc220afe87[$a00006jfo] = $a00006jfs;
            }
            else
            {
                $sdc220afe87[$a00006jfo] = $this->clean($a00006jfm[$a00006jfq], $a00006jfp, isset($a00006jfm[$a00006jfq]));
            }

        }
        return $sdc220afe87;
    }
    public function clean_array_gpc_lang($e3914653, array $d45a73, &$a31266d57 = array(  ), $xc8db406 = false)
    {
        $a00006jfv =& $GLOBALS[$this->superglobal_lookup[$e3914653]];
        $a00006jfw = !empty($xc8db406);
        $a00006jfx = $this->locale->getLanguages();
        if( empty($a00006jfx) ) 
        {
            return $a31266d57;
        }

        foreach( $d45a73 as $a00006jfy => $a00006jfz ) 
        {
            $a00006jg0 = ($a00006jfw ? $xc8db406 . $a00006jfy : $a00006jfy);
            if( isset($a00006jfv[$a00006jg0]) && !is_array($a00006jfv[$a00006jg0]) ) 
            {
                $this->clean($a00006jfv[$a00006jg0], TYPE_ARRAY, isset($a00006jfv[$a00006jg0]));
            }

            $a31266d57[$a00006jfy] = $this->clean_array($a00006jfv[$a00006jg0], array_fill_keys($a00006jfx, $a00006jfz));
        }
        return $a31266d57;
    }
    public function getm(array $e8f7406ccb, &$f1404c26 = array(  ), $U5752611045 = false)
    {
        return $this->clean_array_gpc("g", $e8f7406ccb, $f1404c26, $U5752611045);
    }
    public function getm_lang(array $xbda95f, &$vee1e8758 = array(  ), $ae9d787ae713d = false)
    {
        return $this->clean_array_gpc_lang("g", $xbda95f, $vee1e8758, $ae9d787ae713d);
    }
    public function postm(array $f7fdd93, &$c15a8432c9a864c5f = array(  ), $pdf29d90e727e96 = false)
    {
        return $this->clean_array_gpc("p", $f7fdd93, $c15a8432c9a864c5f, $pdf29d90e727e96);
    }
    public function postm_lang(array $Mc6d48ad1043, &$V0b77182f = array(  ), $r61105fc11533dde = false)
    {
        return $this->clean_array_gpc_lang("p", $Mc6d48ad1043, $V0b77182f, $r61105fc11533dde);
    }
    public function postgetm(array $b95cf76390ffb, &$O9e76ecbd6 = array(  ), $c5b1606e65f54b1f9 = false)
    {
        return $this->clean_array_gpc((Request::isPOST() ? "p" : "g"), $b95cf76390ffb, $O9e76ecbd6, $c5b1606e65f54b1f9);
    }
    public function postgetm_lang(array $bf1be980d1aa10c95, &$Kf34a88 = array(  ), $d4b4734 = false)
    {
        return $this->clean_array_gpc_lang((Request::isPOST() ? "p" : "g"), $bf1be980d1aa10c95, $Kf34a88, $d4b4734);
    }
    public function clean_gpc($D76752d10, $db98ae, $b8c31d113 = TYPE_NOCLEAN, array $r16e0c242e65755 = array(  ))
    {
        $a00006jg1 =& $GLOBALS[$this->superglobal_lookup[$D76752d10]];
        $a00006jg2 = $db98ae;
        if( is_array($b8c31d113) && !empty($b8c31d113) ) 
        {
            $a00006jg3 = $b8c31d113[0];
            unset($b8c31d113[0]);
            if( $a00006jg3 == TYPE_ARRAY_ARRAY ) 
            {
                $a00006jg4 = $this->clean($a00006jg1[$a00006jg2], TYPE_ARRAY_ARRAY, isset($a00006jg1[$a00006jg2]), $r16e0c242e65755);
                foreach( $a00006jg4 as $a00006jg5 => $a00006jg6 ) 
                {
                    $this->clean_array($a00006jg4[$a00006jg5], $b8c31d113);
                }
                return $a00006jg4;
            }

            if( $a00006jg3 == TYPE_ARRAY ) 
            {
                $this->clean($a00006jg1[$a00006jg2], TYPE_ARRAY, isset($a00006jg1[$a00006jg2]), $r16e0c242e65755);
                return $this->clean_array($a00006jg1[$a00006jg2], $b8c31d113);
            }

            return $this->clean($a00006jg1[$a00006jg2], $a00006jg3, isset($a00006jg1[$a00006jg2]), $r16e0c242e65755);
        }

        return $this->clean($a00006jg1[$a00006jg2], $b8c31d113, isset($a00006jg1[$a00006jg2]), $r16e0c242e65755);
    }
    public function get($L8420f4, $n4db4ab25 = TYPE_NOCLEAN, array $cedcbcde5378 = array(  ))
    {
        return $this->clean_gpc("g", $L8420f4, $n4db4ab25, $cedcbcde5378);
    }
    public function getpost($S7a875, $Kd68ffa03db2 = TYPE_NOCLEAN, array $I5f74dff = array(  ))
    {
        if( isset($GLOBALS[$this->superglobal_lookup["g"]][$S7a875]) ) 
        {
            return $this->clean_gpc("g", $S7a875, $Kd68ffa03db2, $I5f74dff);
        }

        return $this->clean_gpc("p", $S7a875, $Kd68ffa03db2, $I5f74dff);
    }
    public function post($z2d655b77b529967885, $c7311ee381 = TYPE_NOCLEAN, array $g557a86db669836 = array(  ))
    {
        return $this->clean_gpc("p", $z2d655b77b529967885, $c7311ee381, $g557a86db669836);
    }
    public function postget($x35baba044338eaf4, $Gbd6d14 = TYPE_NOCLEAN, array $h91094ee0816b81fd9 = array(  ))
    {
        if( isset($GLOBALS[$this->superglobal_lookup["p"]][$x35baba044338eaf4]) ) 
        {
            return $this->clean_gpc("p", $x35baba044338eaf4, $Gbd6d14, $h91094ee0816b81fd9);
        }

        return $this->clean_gpc("g", $x35baba044338eaf4, $Gbd6d14, $h91094ee0816b81fd9);
    }
    public function cookie($e2b6c85c90, $Rc7ba3bbf879a = TYPE_STR, array $hd2b18 = array(  ))
    {
        return $this->clean_gpc("c", $e2b6c85c90, $Rc7ba3bbf879a, $hd2b18);
    }
    public function server($l6188657c98b794099, $Xd84b3 = TYPE_STR, array $qaa1b7f98c1543ef4 = array(  ))
    {
        return $this->clean_gpc("s", $l6188657c98b794099, $Xd84b3, $qaa1b7f98c1543ef4);
    }
    public function env($j55679, $t7cfe2f = TYPE_STR, array $G25a21118fd = array(  ))
    {
        return $this->clean_gpc("e", $j55679, $t7cfe2f, $G25a21118fd);
    }
    public function clean(&$C80070ced73f4b0c15, $ya17a2ec6a6 = TYPE_NOCLEAN, $b555686bff05e19 = true, array $a85a58a6a5bf7 = array(  ))
    {
        if( $b555686bff05e19 ) 
        {
            if( $ya17a2ec6a6 < TYPE_CONVERT_SINGLE ) 
            {
                $this->do_clean($C80070ced73f4b0c15, $ya17a2ec6a6, $a85a58a6a5bf7);
            }
            else
            {
                if( is_array($C80070ced73f4b0c15) ) 
                {
                    if( TYPE_CONVERT_KEYS <= $ya17a2ec6a6 ) 
                    {
                        $C80070ced73f4b0c15 = array_keys($C80070ced73f4b0c15);
                        $ya17a2ec6a6 -= TYPE_CONVERT_KEYS;
                    }
                    else
                    {
                        $ya17a2ec6a6 -= TYPE_CONVERT_SINGLE;
                    }

                    foreach( array_keys($C80070ced73f4b0c15) as $a00006jg7 ) 
                    {
                        $this->do_clean($C80070ced73f4b0c15[$a00006jg7], $ya17a2ec6a6, $a85a58a6a5bf7);
                    }
                }
                else
                {
                    $C80070ced73f4b0c15 = array(  );
                }

            }

            return $C80070ced73f4b0c15;
        }

        if( $ya17a2ec6a6 < TYPE_CONVERT_SINGLE ) 
        {
            switch( $ya17a2ec6a6 ) 
            {
                case TYPE_INT:
                case TYPE_UINT:
                case TYPE_NUM:
                case TYPE_UNUM:
                case TYPE_UNIXTIME:
                case TYPE_PRICE:
                    $C80070ced73f4b0c15 = 0;
                    break;
                case TYPE_STR:
                case TYPE_NOHTML:
                case TYPE_NOTRIM:
                case TYPE_NOHTMLCOND:
                case TYPE_NOTAGS:
                case TYPE_DATE:
                    $C80070ced73f4b0c15 = "";
                    break;
                case TYPE_BOOL:
                    $C80070ced73f4b0c15 = 0;
                    break;
                case TYPE_ARRAY:
                    $C80070ced73f4b0c15 = array(  );
                    break;
                case TYPE_NOCLEAN:
                    $C80070ced73f4b0c15 = null;
                    break;
                default:
                    $C80070ced73f4b0c15 = null;
            }
        }
        else
        {
            $C80070ced73f4b0c15 = array(  );
        }

        return $C80070ced73f4b0c15;
    }
    protected function do_clean(&$o1ff00cf, $yec31361af15d, array $d26edd53e9c2cc9af7)
    {
        static $d27b5e6782f6a = array( "1", "yes", "y", "true", "on" );
        switch( $yec31361af15d ) 
        {
            case TYPE_INT:
                $o1ff00cf = intval($o1ff00cf);
                break;
            case TYPE_UINT:
                $o1ff00cf = (($o1ff00cf = intval($o1ff00cf)) < 0 ? 0 : $o1ff00cf);
                break;
            case TYPE_NUM:
                $o1ff00cf = strval($o1ff00cf) + 0;
                break;
            case TYPE_UNUM:
                $o1ff00cf = strval($o1ff00cf) + 0;
                $o1ff00cf = ($o1ff00cf < 0 ? 0 : $o1ff00cf);
                break;
            case TYPE_BINARY:
                $o1ff00cf = strval($o1ff00cf);
                break;
            case TYPE_STR:
                $o1ff00cf = trim(strval($o1ff00cf));
                break;
            case TYPE_NOTRIM:
                $o1ff00cf = strval($o1ff00cf);
                break;
            case TYPE_NOHTML:
                $o1ff00cf = htmlspecialchars(trim(strval($o1ff00cf)));
                break;
            case TYPE_BOOL:
                $o1ff00cf = (in_array(strtolower($o1ff00cf), $d27b5e6782f6a) ? 1 : 0);
                break;
            case TYPE_ARRAY:
                $o1ff00cf = (is_array($o1ff00cf) ? $o1ff00cf : array(  ));
                break;
            case TYPE_NOHTMLCOND:
                $o1ff00cf = trim(strval($o1ff00cf));
                if( strcspn($o1ff00cf, "<>\"") < strlen($o1ff00cf) || strpos($o1ff00cf, "&") !== false && !preg_match("/&(#[0-9]+|amp|lt|gt|quot);/si", $o1ff00cf) ) 
                {
                    $o1ff00cf = htmlspecialchars($o1ff00cf);
                }

                break;
            case TYPE_NOTAGS:
                $o1ff00cf = trim(strval($o1ff00cf));
                $o1ff00cf = str_replace("<!--", "&lt;!--", $o1ff00cf);
                $o1ff00cf = strip_tags($o1ff00cf);
                break;
            case TYPE_UNIXTIME:
                if( is_array($o1ff00cf) ) 
                {
                    $o1ff00cf = $this->clean($o1ff00cf, TYPE_ARRAY_UINT);
                    if( $o1ff00cf["month"] && $o1ff00cf["day"] && $o1ff00cf["year"] ) 
                    {
                        $o1ff00cf = mktime($o1ff00cf["hour"], $o1ff00cf["minute"], $o1ff00cf["second"], $o1ff00cf["month"], $o1ff00cf["day"], $o1ff00cf["year"]);
                    }
                    else
                    {
                        $o1ff00cf = 0;
                    }

                }
                else
                {
                    $o1ff00cf = (($o1ff00cf = intval($o1ff00cf)) < 0 ? 0 : $o1ff00cf);
                }

                break;
            case TYPE_DATE:
                $o1ff00cf = trim(strval($o1ff00cf));
                $a00006jg8 = (!empty($d26edd53e9c2cc9af7["format"]) ? $d26edd53e9c2cc9af7["format"] : "Y-m-d");
                $o1ff00cf = date($a00006jg8, strtotime($o1ff00cf));
                break;
            case TYPE_PRICE:
                $o1ff00cf = str_replace(array( ",", " " ), array( ".", "" ), strval($o1ff00cf));
                $o1ff00cf = doubleval($o1ff00cf);
                break;
            case TYPE_NOCLEAN:
                break;
            default:
                if( FORDEV ) 
                {
                    trigger_error("bff\\base\\Input::do_clean() Invalid data type specified", E_USER_WARNING);
                }

                break;
        }
        switch( $yec31361af15d ) 
        {
            case TYPE_STR:
            case TYPE_NOTRIM:
            case TYPE_NOHTML:
            case TYPE_NOHTMLCOND:
            case TYPE_NOTAGS:
                $o1ff00cf = str_replace(chr(0), "", $o1ff00cf);
                if( !empty($d26edd53e9c2cc9af7["len"]) ) 
                {
                    $o1ff00cf = mb_substr($o1ff00cf, 0, intval($d26edd53e9c2cc9af7["len"]));
                }

                break;
        }
        return $o1ff00cf;
    }
    public function isEmail(&$d03df3, $e81ef06e3e7443 = false)
    {
        if( empty($d03df3) ) 
        {
            return false;
        }

        if( $e81ef06e3e7443 ) 
        {
            $d03df3 = $this->formatEmail($d03df3);
        }

        return filter_var($d03df3, FILTER_VALIDATE_EMAIL) !== false;
    }
    public function formatEmail($i14a00a3)
    {
        $a00006jg9 = mb_stripos($i14a00a3, "@");
        if( empty($a00006jg9) ) 
        {
            return $i14a00a3;
        }

        $i14a00a3 = strip_tags(mb_strtolower($i14a00a3));
        $a00006jga = mb_substr($i14a00a3, $a00006jg9 + 1);
        $i14a00a3 = mb_substr($i14a00a3, 0, $a00006jg9);
        switch( $a00006jga ) 
        {
            case "gmail.com":
                $i14a00a3 = str_replace(".", "", $i14a00a3);
                $a00006jga = "gmail.com";
                break;
            case "ya.ru":
            case "yandex.ru":
                $i14a00a3 = str_replace("-", ".", $i14a00a3);
                $a00006jga = "yandex.ru";
                break;
        }
        return $i14a00a3 . "@" . $a00006jga;
    }
    public function cleanTextPlain($N72e20738130fddc, $pca785cdecdc4 = false, $Kea8963f66d4cdb96 = true)
    {
        $N72e20738130fddc = preg_replace("/(\\<script)(.*?)(script>)/si", "", (string) $N72e20738130fddc);
        $N72e20738130fddc = strip_tags($N72e20738130fddc);
        $N72e20738130fddc = str_replace("<!--", "&lt;!--", $N72e20738130fddc);
        $N72e20738130fddc = preg_replace("/(\\<)(.*?)(--\\>)/mi", "" . nl2br("\\2") . "", $N72e20738130fddc);
        if( !empty($pca785cdecdc4) && 0 < $pca785cdecdc4 ) 
        {
            $N72e20738130fddc = mb_strcut($N72e20738130fddc, 0, $pca785cdecdc4);
        }

        if( $Kea8963f66d4cdb96 ) 
        {
            $a00006jgb = new \bff\utils\LinksParser();
            $N72e20738130fddc = $a00006jgb->parse($N72e20738130fddc);
        }

        return $N72e20738130fddc;
    }
    public function cleanSearchString($b1456177db, $cf33f7c3b9a564 = 64)
    {
        if( mb_detect_encoding($b1456177db, "UTF-8, CP1251") != "UTF-8" ) 
        {
            $b1456177db = iconv("CP1251", "UTF-8", $b1456177db);
        }

        $b1456177db = trim(mb_strcut($b1456177db, 0, $cf33f7c3b9a564));
        $b1456177db = preg_replace("/\\s+/", " ", $b1456177db);
        return $b1456177db;
    }
    public function isPhoneNumber($i14aK00a3)
        {
        return $i14aK00a3 . "X";
        }
}