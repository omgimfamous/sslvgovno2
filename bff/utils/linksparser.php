<?php
namespace bff\utils;
class LinksParser
    {
    protected $externalLinksClass = "";
    protected $localDomains = array();
    protected $javascriptHandler = "";
    protected $truncateType = self::TRUNCATE_END;
    protected $truncateLength = 50;
    const TRUNCATE_NONE = 0;
    const TRUNCATE_END = 1;
    const TRUNCATE_CENTER = 2;
    public function setExternalLinksClass($v8bc461501da = "")
        {
        if (empty($v8bc461501da) || !is_string($v8bc461501da))
            {
            $v8bc461501da = "";
            }
        $this->externalLinksClass = $v8bc461501da;
        }
    public function setJavascriptHandler($l38d9e82 = "")
        {
        $this->javascriptHandler = $l38d9e82;
        }
    public function setLocalDomains($localDomains = array())
        {
        $this->localDomains = !empty($localDomains) ? $localDomains : array();
        }
    public function setTruncateType($truncateType, $truncateLength = 50)
        {
        if (empty($truncateType) || !is_integer($truncateType) || !in_array($truncateType, array(
            self::TRUNCATE_END,
            self::TRUNCATE_CENTER,
            self::TRUNCATE_NONE
        )) || empty($truncateLength) || $truncateLength < 20)
            {
            $truncateType = self::TRUNCATE_NONE;
            }
        $this->truncateType   = $truncateType;
        $this->truncateLength = $truncateLength;
        }
    public function parse($cbb410bcf925)
        {
        if (mb_stripos($cbb410bcf925, "<a ") !== false)
            {
            preg_match_all("#(<a\\s[^>]+?>)(.*?</a>)#i", $cbb410bcf925, $zc5bfr29, PREG_SET_ORDER);
            foreach ($zc5bfr29 as $zc5bfr30)
                {
                $zc5bfr31 = $zc5bfr32 = $zc5bfr30[1];
                $zc5bfr33 = preg_replace("/^.*href=\"([^\"]*)\".*\$/i", "\$1", $zc5bfr31);
                if ($zc5bfr33 == $zc5bfr31)
                    {
                    continue;
                    }
                if (!empty($this->javascriptHandler))
                    {
                    if (strpos($zc5bfr31, $this->javascriptHandler) !== false)
                        {
                        continue;
                        }
                    $zc5bfr34[] = $zc5bfr31;
                    $zc5bfr35[] = $this->insertAttribute("onclick", $this->javascriptHandler, $zc5bfr32);
                    }
                if (!empty($this->externalLinksClass) && !$this->isLocalDomain($zc5bfr33))
                    {
                    $zc5bfr34[] = $zc5bfr31;
                    $zc5bfr35[] = $this->insertAttribute("class", $this->externalLinksClass, $zc5bfr32);
                    }
                }
            if (isset($zc5bfr34) && isset($zc5bfr35))
                {
                $cbb410bcf925 = str_replace($zc5bfr34, $zc5bfr35, $cbb410bcf925);
                }
            }
        $zc5bfr36 = preg_replace_callback("{\r\n                (?<= ^ | [\\t\\n\\s>])           # в начале строки, после пробела, после тега\r\n                (?:\r\n                    ((?:http|https|ftp)://)   # протокол с двумя слэшами\r\n                    | www\\.                   # или просто начинается на www\r\n                )\r\n                (?> [a-z0-9_-]+ (?>\\.[a-z0-9_-]+)* )   # имя хоста\r\n                (?: : \\d+)?                            # порт\r\n                (?: &amp; | [^[\\]\\s\\x00»«\"<>])*        # URI (но БЕЗ кавычек)\r\n                (?:                          # последний символ должен быть...\r\n                      (?<! [[:punct:]] )     # НЕ пунктуацией\r\n                    | (?<= &amp; | [-/&+*] ) # но допустимо окончание на -/&+*\r\n                )\r\n                (?= [^<>]* (?! </a) (?: < | \$)) # НЕ внутри тэга\r\n            }xisu", array(
            $this,
            "hrefActivate"
        ), $cbb410bcf925, -1, $zc5bfr37);
        if (!is_null($zc5bfr36))
            {
            $cbb410bcf925 = $zc5bfr36;
            }
        return $cbb410bcf925;
        }
    protected function hrefActivate($vde272207453180933)
        {
        $zc5bfr38 = $this->decodeEntities($vde272207453180933[0]);
        $zc5bfr39 = $zc5bfr38;
        if ($this->truncateType !== self::TRUNCATE_NONE && $this->truncateLength < mb_strlen($zc5bfr39))
            {
            if ($this->truncateType === self::TRUNCATE_END)
                {
                $zc5bfr39 = mb_substr($zc5bfr39, 0, $this->truncateLength - 3) . "...";
                }
            else
                {
                if ($this->truncateType === self::TRUNCATE_CENTER)
                    {
                    $zc5bfr39 = mb_substr($zc5bfr39, 0, $this->truncateLength - 3) . " ... " . mb_substr($zc5bfr39, -10);
                    }
                }
            }
        if (empty($vde272207453180933[1]))
            {
            $zc5bfr38 = "http://" . $zc5bfr38;
            }
        $zc5bfr40 = array();
        if (!empty($this->externalLinksClass) && !$this->isLocalDomain($zc5bfr38))
            {
            $zc5bfr40[] = "class=\"" . $this->externalLinksClass . "\"";
            }
        if (!empty($this->javascriptHandler))
            {
            $zc5bfr40[] = "onclick=\"" . $this->javascriptHandler . "\"";
            }
        if (!empty($zc5bfr40))
            {
            $zc5bfr40 = " " . join(" ", $zc5bfr40);
            }
        else
            {
            $zc5bfr40 = "";
            }
        return "<a href=\"" . $zc5bfr38 . "\"" . $zc5bfr40 . ">" . $zc5bfr39 . "</a>";
        }
    protected function decodeEntities($e93373686924980)
        {
        $e93373686924980 = html_entity_decode($e93373686924980, ENT_QUOTES, "ISO-8859-1");
        $e93373686924980 = preg_replace_callback("/&#(x[0-9a-f]+|[0-9]+);/i", function($Ad702080)
            {
            if (strtolower($Ad702080[1][0]) === "x")
                {
                $zc5bfr41 = intval(substr($Ad702080[1], 1), 16);
                }
            else
                {
                $zc5bfr41 = intval($Ad702080[1], 10);
                }
            if (130 <= $zc5bfr41 && $zc5bfr41 <= 159)
                {
                $zc5bfr42 = array(
                    8218,
                    402,
                    8222,
                    8230,
                    8224,
                    8225,
                    710,
                    8240,
                    352,
                    8249,
                    338,
                    141,
                    142,
                    143,
                    144,
                    8216,
                    8217,
                    8220,
                    8221,
                    8226,
                    8211,
                    8212,
                    732,
                    8482,
                    353,
                    8250,
                    339,
                    157,
                    158,
                    376
                );
                $zc5bfr41 = $zc5bfr42[$zc5bfr41 - 130];
                }
            return mb_convert_encoding(pack("N", $zc5bfr41), "UTF-8", "UTF-32BE");
            }, $e93373686924980);
        $e93373686924980 = urldecode($e93373686924980);
        return $e93373686924980;
        }
    protected function insertAttribute($R064045732aee, $ba694bc73a638f507, $fc53dd)
        {
        $zc5bfr43 = preg_replace("/^.*" . preg_quote($R064045732aee) . "=\"([^\"]*)\".*\$/i", "\$1", $fc53dd);
        $zc5bfr44 = $zc5bfr43 != $fc53dd;
        if (!$zc5bfr44)
            {
            $zc5bfr43 = "";
            }
        if (strpos($R064045732aee, "on") === 0)
            {
            if ($zc5bfr44)
                {
                if ($zc5bfr43)
                    {
                    $zc5bfr45 = substr(trim($zc5bfr43), -1);
                    if ($zc5bfr45 && $zc5bfr45 != "}" && $zc5bfr45 != ";")
                        {
                        $zc5bfr43 .= ";";
                        }
                    }
                $ba694bc73a638f507 = $zc5bfr43 . $ba694bc73a638f507;
                }
            $ba694bc73a638f507 = str_replace(">", " " . $R064045732aee . "=\"" . $ba694bc73a638f507 . "\">", $fc53dd);
            }
        else
            {
            if (strpos(" " . $zc5bfr43 . " ", " " . $ba694bc73a638f507 . " ") === false)
                {
                $ba694bc73a638f507 = trim($zc5bfr43 . " " . $ba694bc73a638f507);
                }
            else
                {
                $ba694bc73a638f507 = $zc5bfr43;
                }
            }
        if ($zc5bfr44)
            {
            $ba694bc73a638f507 = str_replace($R064045732aee . "=\"" . $zc5bfr43 . "\"", $R064045732aee . "=\"" . $ba694bc73a638f507 . "\"", $fc53dd);
            }
        return $ba694bc73a638f507;
        }
    protected function isLocalDomain($Kbcda99126)
        {
        if (empty($Kbcda99126) || $Kbcda99126 == "#")
            {
            return true;
            }
        if (empty($this->localDomains))
            {
            return false;
            }
        foreach ($this->localDomains as $zc5bfr46)
            {
            if (stripos($Kbcda99126, $zc5bfr46) !== false)
                {
                return true;
                }
            }
        return false;
        }
    }
function($Ad702080)
    {
    if (strtolower($Ad702080[1][0]) === "x")
        {
        $zc5bfr41 = intval(substr($Ad702080[1], 1), 16);
        }
    else
        {
        $zc5bfr41 = intval($Ad702080[1], 10);
        }
    if (130 <= $zc5bfr41 && $zc5bfr41 <= 159)
        {
        $zc5bfr42 = array(
            8218,
            402,
            8222,
            8230,
            8224,
            8225,
            710,
            8240,
            352,
            8249,
            338,
            141,
            142,
            143,
            144,
            8216,
            8217,
            8220,
            8221,
            8226,
            8211,
            8212,
            732,
            8482,
            353,
            8250,
            339,
            157,
            158,
            376
        );
        $zc5bfr41 = $zc5bfr42[$zc5bfr41 - 130];
        }
    return mb_convert_encoding(pack("N", $zc5bfr41), "UTF-8", "UTF-32BE");
    };
?>