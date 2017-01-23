<?php
namespace bff\base;
class Locale
    {
    protected $languages = array();
    protected $languagesSettings = NULL;
    protected $lang = "";
    protected $def = "";
    protected $defUrlPrefix = false;
    const DEF = "def";
    const LNG_VAR = "lng";
    public function init($languages = array(), $defaultLanguageKey = "", $adminPanel = false)
        {
        if (defined("LNG"))
            {
            return $this->lang;
            }
        if (!empty($languages))
            {
            foreach ($languages as $key => $title)
                {
                $this->assignLanguage($key, $title);
                }
            }
        if (!empty($defaultLanguageKey))
            {
            $this->def = $defaultLanguageKey;
            }
        else
            {
            if (!empty($this->languages))
                {
                reset($this->languages);
                $this->def = key($this->languages);
                }
            }
        $lang   = $this->getCurrentLanguage();
        $locale = $this->gt_LocaleMessagesFolder($lang, true);
        putenv("LC_ALL=" . $locale);
        putenv("LC_MESSAGES=" . $locale);
        putenv("LANG=" . $locale);
        putenv("LANGUAGE=" . $locale);
        setlocale(defined("LC_MESSAGES") ? LC_MESSAGES : LC_ALL, $locale);
        setlocale(LC_TIME, $locale);
        if (function_exists("gettext"))
            {
            $domain = $this->gt_Domain() . "-" . $lang . ($adminPanel ? "-admin" : "");
            bindtextdomain($domain, $this->gt_Path());
            bind_textdomain_codeset($domain, "UTF-8");
            textdomain($domain);
            }
        define("LNG", $lang);
        return $lang;
        }
    public function assignLanguage($languageKey, $title = "Default", $charset = "UTF-8")
        {
        $this->languages[$languageKey] = array(
            "title" => $title,
            "folder" => $this->gt_LocaleMessagesFolder($languageKey, false),
            "charset" => $charset
        );
        return $this;
        }
    public function getCurrentLanguage()
        {
        if (!empty($this->lang))
            {
            return $this->lang;
            }
        if (1 < sizeof($this->languages))
            {
            $uri      = ltrim(Request::uri(), "/");
            $cookie   = isset($_COOKIE[self::LNG_VAR]) ? $_COOKIE[self::LNG_VAR] : "";
            $redirect = false;
            if (preg_match("/^(" . join("|", array_keys($this->languages)) . ")\\/(.*)/", $uri, $matches) === 1 && !empty($matches[1]))
                {
                list(, $lang, $uri) = $matches;
                if ($lang == $this->def && !$this->defUrlPrefix)
                    {
                    $redirect = $uri;
                    }
                if ($cookie != $lang)
                    {
                    \Request::setCOOKIE(self::LNG_VAR, $lang);
                    }
                }
            else
                {
                $lang = strval(isset($_GET[self::LNG_VAR]));
                if (empty($cookie) && empty($lang))
                    {
                    $langs = $this->getAcceptedLanguages();
                    if (!empty($langs) && array_key_exists(key($langs), $this->languages))
                        {
                        $lang = key($langs);
                        }
                    else
                        {
                        $lang = $this->def;
                        }
                    \Request::setCOOKIE(self::LNG_VAR, $lang);
                    }
                else
                    {
                    if (!array_key_exists($lang, $this->languages))
                        {
                        $lang = $this->def;
                        }
                    }
                if ($lang != $this->def || $this->defUrlPrefix)
                    {
                    $redirect = $lang . "/" . $uri;
                    }
                }
            if ($redirect !== false && !Request::isPOST())
                {
                \Request::redirect(Request::url(false) . "/" . $redirect);
                }
            }
        else
            {
            $lang = $this->def;
            }
        $this->setCurrentLanguage($lang);
        return $lang;
        }
    public function setCurrentLanguage($languageKey)
        {
        return $this->lang = $languageKey;
        }
    public function getAcceptedLanguages()
        {
        $httpLanguages = Request::getSERVER("HTTP_ACCEPT_LANGUAGE", "");
        $languages     = array();
        if (empty($httpLanguages))
            {
            return $languages;
            }
        $accepted = preg_split("/,\\s*/", $httpLanguages);
        foreach ($accepted as $accept)
            {
            $match  = null;
            $result = preg_match("/^([a-z]{1,8}(?:[-_][a-z]{1,8})*)(?:;\\s*q=(0(?:\\.[0-9]{1,3})?|1(?:\\.0{1,3})?))?\$/i", $accept, $match);
            if ($result < 1)
                {
                continue;
                }
            if (isset($match[2]) === true)
                {
                $quality = (double) $match[2];
                }
            else
                {
                $quality = 1;
                }
            $countrys = explode("-", $match[1]);
            $region   = array_shift($countrys);
            $country2 = explode("_", $region);
            $region   = array_shift($country2);
            if (isset($languages[$region]) === false || $languages[$region] < $quality)
                {
                $languages[$region] = $quality;
                }
            }
        return $languages;
        }
    public function getLanguageUrlPrefix($languageKey = LNG, $trailingSlash = false)
        {
        if ((sizeof($this->languages) == 1 || $languageKey == $this->def) && !$this->defUrlPrefix)
            {
            return $trailingSlash ? "/" : "";
            }
        return "/" . $languageKey . ($trailingSlash ? "/" : "");
        }
    public function setDefaultLanguageUrlPrefix($enabled)
        {
        return $this->defUrlPrefix = $enabled;
        }
    public function getDefaultLanguageUrlPrefix()
        {
        return $this->defUrlPrefix;
        }
    public function getDefaultLanguage()
        {
        return $this->def;
        }
    public function getLanguages($keywordsOnly = true)
        {
        return $keywordsOnly ? array_keys($this->languages) : $this->languages;
        }
    public function getLanguageSettings($languageKey, $key, $default = "")
        {
        if (is_null($this->languagesSettings))
            {
            $this->languagesSettings = require_once(PATH_BASE . "config" . DIRECTORY_SEPARATOR . "languages.php");
            }
        if (isset($this->languagesSettings[$languageKey]))
            {
            if (is_string($key))
                {
                if (isset($this->languagesSettings[$languageKey][$key]))
                    {
                    return $this->languagesSettings[$languageKey][$key];
                    }
                }
            else
                {
                return $this->languagesSettings[$languageKey];
                }
            }
        return $default;
        }
    public function buildForm(&$data, $prefix, $template, $extra = array())
        {
        $aData =& $data;
        $isTable    = !isset($extra["table"]) || !empty($extra["table"]);
        $isPopup    = !empty($extra["popup"]);
        $onChange   = !empty($extra["onchange"]) ? $extra["onchange"] : false;
        $langsCount = sizeof($this->languages);
        $tabs       = "";
        $form       = "";
        $i          = 0;
        foreach ($this->languages as $key => $lang)
            {
            $isActive = !$i;
            $lng      = $key;
            if ($isPopup)
                {
                $tabs .= "<span class=\"tab tab-lang lng-" . $key . ($isActive ? " tab-active" : "") . "\" onclick=\"bff.langTab('" . $key . "', '" . $prefix . "', this);" . ($onChange !== false ? $onChange . "('" . $key . "');" : "") . "\"></span>&nbsp;";
                }
            else
                {
                $tabs .= "<a href=\"#\" class=\"but lng-" . $key . ($isActive ? " active" : "") . " j-lang-toggler\" data-lng=\"" . $key . "\" onclick=\"bff.langTab('" . $key . "', '" . $prefix . "', this);" . ($onChange !== false ? $onChange . "('" . $key . "');" : "") . " return false;\" title=\"" . $lang["title"] . "\"></a>";
                }
            $form .= "<" . ($isTable ? "tbody" : "div") . " class=\"j-lang-form j-lang-form-" . $key . (!$isActive ? " displaynone" : "") . "\">";
            ob_start();
            eval(" ?>" . $template . "<?php ");
            $form .= ob_get_clean();
            $form .= "</" . ($isTable ? "tbody" : "div") . ">";
            $i++;
            }
        $HTML = "";
        if ($isPopup)
            {
            $tabs = "<div class=\"tabsBar\">" . $tabs . "</div>";
            }
        else
            {
            $tabs = "<div class=\"box-langs j-lang-togglers\">" . $tabs . "</div>";
            }
        if (1 < $langsCount)
            {
            if ($isTable)
                {
                if (!isset($extra["cols"]))
                    {
                    $extra["cols"] = 2;
                    }
                $HTML .= "<tbody><tr><td style=\"padding:0px;\" colspan=\"" . $extra["cols"] . "\">" . $tabs . "</td></tr></tbody>";
                }
            else
                {
                $HTML .= $tabs;
                }
            }
        $HTML .= $form;
        return $HTML;
        }
    public function formField($sName, $aData, $sType, $aAttr = array())
        {
        if (!isset($aAttr["style"]))
            {
            $aAttr["style"] = "";
            }
        if (!isset($aAttr["class"]))
            {
            $aAttr["class"] = "";
            }
        $hmtl       = "";
        $i          = 0;
        $classNames = $aAttr["class"];
        foreach ($this->languages as $lang => $v)
            {
            $aAttr["name"]  = $sName . "[" . $lang . "]";
            $aAttr["class"] = (!empty($classNames) ? $classNames . " " : "") . ($i++ ? " displaynone" : "") . " lang-field j-lang-form j-lang-form-" . $lang;
            switch ($sType)
            {
                case "textarea":
                    $hmtl .= "<textarea" . HTML::attributes($aAttr) . ">" . (isset($aData[$lang]) ? HTML::specialchars($aData[$lang]) : "") . "</textarea>";
                    break;
                default:
                    $aAttr["value"] = isset($aData[$lang]) ? $aData[$lang] : "";
                    $hmtl .= "<input type=\"text\"" . HTML::attributes($aAttr) . " />";
                    break;
            }
            }
        return $hmtl;
        }
    public function getMonthTitle($monthIndex = false, $languageKey = LNG)
        {
        $aMonths = $this->getLanguageSettings($languageKey, "month", array());
        if ($monthIndex === false)
            {
            return $aMonths;
            }
        return isset($aMonths[$monthIndex]) ? $aMonths[$monthIndex] : "";
        }
    public function gt_Path($languageKey = "")
        {
        $path = PATH_BASE . "files" . DIRECTORY_SEPARATOR . "locale";
        if (!empty($languageKey))
            {
            $path .= DIRECTORY_SEPARATOR . $this->gt_LocaleMessagesFolder($languageKey, false) . DIRECTORY_SEPARATOR . "LC_MESSAGES";
            }
        return $path;
        }
    public function gt_Domain($returnType = false)
        {
        $path = PATH_BASE . "files" . DIRECTORY_SEPARATOR . "locale" . DIRECTORY_SEPARATOR . "domain.php";
        if ($returnType === false)
            {
            return require($path);
            }
        if ($returnType == "lastmodify")
            {
            if (file_exists($path))
                {
                return @filemtime($path);
                }
            return false;
            }
        if ($returnType == "path")
            {
            return $path;
            }
        if ($returnType == "next")
            {
            $cur = require($path);
            return strval(intval($cur) + 1);
            }
        }
    public function gt_LocaleMessagesFolder($languageKey = "", $addCharset = false)
        {
        return $this->getLanguageSettings($languageKey, "locale") . ($addCharset ? ".UTF-8" : "");
        }
    }
?>