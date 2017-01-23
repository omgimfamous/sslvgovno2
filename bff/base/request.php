<?php
namespace bff\base;
abstract class Request
    {
    public static function redirect($d5e06c2d4b47, $n2048fc22f5 = false, $ae8363d8985a58 = false)
        {
        if (headers_sent() || $ae8363d8985a58)
            {
            echo "<script type=\"text/javascript\">location='" . $d5e06c2d4b47 . "';</script>";
            }
        else
            {
            if (!is_integer($n2048fc22f5))
                {
                $n2048fc22f5 = null;
                }
            header("location: " . $d5e06c2d4b47, true, $n2048fc22f5);
            }
        exit();
        }
    public static function isPOST()
        {
        return self::method() == "POST";
        }
    public static function isGET()
        {
        return self::method() == "GET";
        }
    public static function isHTTPS()
        {
        return self::scheme() == "https";
        }
    public static function isAJAX($deb1ff2c142042559 = "POST")
        {
        if (self::getSERVER("HTTP_X_REQUESTED_WITH") == "XMLHttpRequest")
            {
            if (!empty($deb1ff2c142042559))
                {
                return self::method() == $deb1ff2c142042559;
                }
            return true;
            }
        return false;
        }
    public static function remoteAddress($ab7784 = false, $a2ad0d97756eab2 = false)
        {
        $a2ad0245 = "";
        if ($a2ad0d97756eab2)
            {
            $a2ad0245 = self::getSERVER("HTTP_X_FORWARDED_FOR", "");
            }
        if (empty($a2ad0245))
            {
            $a2ad0245 = self::getSERVER("REMOTE_ADDR", "");
            }
        if (!empty($a2ad0245) && ($a2add256 = strpos($a2ad0245, ",")) !== false)
            {
            $a2ad0245 = mb_substr($a2ad0245, 0, $a2add256);
            }
        if ($ab7784)
            {
            $a2ad0245 = empty($a2ad0245) ? 0 : sprintf("%u", ip2long($a2ad0245));
            }
        return $a2ad0245;
        }
    public static function host($ffd2df30e499 = "")
        {
        return self::getSERVER("HTTP_HOST", $ffd2df30e499);
        }
    public static function uri($m0f688451 = "")
        {
        return self::getSERVER("REQUEST_URI", $m0f688451);
        }
    public static function url($e6548d4 = false)
        {
        return self::scheme() . "://" . self::getSERVER("HTTP_HOST") . ($e6548d4 ? self::getSERVER("REQUEST_URI") : "");
        }
    public static function referer($a70d6da47694b = "")
        {
        return self::getSERVER("HTTP_REFERER", $a70d6da47694b);
        }
    public static function userAgent($P21153f563ce29ab = "")
        {
        return self::getSERVER("HTTP_USER_AGENT", $P21153f563ce29ab);
        }
    public static function scheme()
        {
        $p2add25f = "http";
        $p2add5h = self::getSERVER("HTTPS");
        if ($p2add5h)
            {
            $p2add25f = $p2add5h == "off" ? "http" : "https";
            }
        return $p2add25f;
        }
    public static function method()
        {
        return self::getSERVER("REQUEST_METHOD");
        }
    public static function getSERVER($a464de5c0, $p23562e89903d86 = "")
        {
        if (array_key_exists($a464de5c0, $_SERVER))
            {
            return $_SERVER[$a464de5c0];
            }
        if (array_key_exists("HTTP_" . $a464de5c0, $_SERVER))
            {
            return $_SERVER["HTTP_" . $a464de5c0];
            }
        return $p23562e89903d86;
        }
    public static function setCOOKIE($n836f63, $jf0fc46c3985a19, $Jb76672e072c = 30, $e9d20c958 = "/", $La228d2a = false)
        {
        if (headers_sent($j2add5k, $j2add5ku))
            {
            if (BFF_DEBUG)
                {
                trigger_error("Output has already been sent to the browser at " . $j2add5k . ":" . $j2add5ku . ".");
                }
            return false;
            }
        $Jb76672e072c = is_null($Jb76672e072c) ? null : time() + 86400 * $Jb76672e072c;
        $La228d2a     = $La228d2a !== false ? $La228d2a : "." . SITEHOST;
        return setcookie($n836f63, $jf0fc46c3985a19, $Jb76672e072c, $e9d20c958, $La228d2a);
        }
    public static function deleteCOOKIE($u3059e2da5bed, $T7a8016b52a28fe = "/", $b87d08dadf9c4620 = false)
        {
        if (empty($b87d08dadf9c4620))
            {
            $b87d08dadf9c4620 = "." . SITEHOST;
            }
        return setcookie($u3059e2da5bed, null, time() - 691200, $T7a8016b52a28fe, $b87d08dadf9c4620);
        }
    }
?>