<?php
namespace bff\utils;
class VideoParser
    {
    protected $providers = NULL;
    const PROVIDER_UNKNOWN = 0;
    const PROVIDER_YOUTUBE = 1;
    const PROVIDER_VIMEO = 2;
    const PROVIDER_RUTUBE = 4;
    const PROVIDER_VK = 8;
    public function __construct()
        {
        $this->providers = array(
            self::PROVIDER_YOUTUBE => array(
                "host" => array(
                    "youtube.com",
                    "youtu.be"
                ),
                "video_url" => "http://youtube.com/watch?v=[ID]",
                "flash_url" => "http://www.youtube.com/v/[ID]",
                "embed_api" => "http://www.youtube.com/oembed",
                "embed_url" => "/(embed)\\/([^\\?]+)/"
            ),
            self::PROVIDER_VIMEO => array(
                "host" => array(
                    "vimeo.com",
                    "player.vimeo.com"
                ),
                "video_url" => "http://vimeo.com/[ID]",
                "flash_url" => "http://vimeo.com/moogaloop.swf?clip_id=[ID]&server=vimeo.com&show_title=1&fullscreen=1",
                "embed_api" => "http://vimeo.com/api/oembed.json",
                "embed_url" => "/(video)\\/(.*)\\??/"
            ),
            self::PROVIDER_RUTUBE => array(
                "host" => array(
                    "rutube.ru",
                    "video.rutube.ru"
                ),
                "video_url" => "http://rutube.ru/tracks/[ID].html",
                "flash_url" => "http://video.rutube.ru/[ID]",
                "embed_api" => "http://rutube.ru/api/oembed/",
                "embed_url" => "/(video|play)\\/embed\\/([\\d]+)\\??/"
            ),
            self::PROVIDER_VK => array(
                "host" => array(
                    "vk.com"
                ),
                "video_url" => "",
                "flash_url" => "",
                "embed_api" => false,
                "embed_url" => "/(video_ext)\\.php\\?oid=[\\d]+&id=([\\d]+)&?/"
            )
        );
        }
    public function embed($A0c027a94ce55f3e, $Gd3b8da92685fd75e34 = false)
        {
        $zc5bfr58 = array();
        while (empty($A0c027a94ce55f3e) || !is_string($A0c027a94ce55f3e))
            {
            break;
            }
        $A0c027a94ce55f3e = trim($A0c027a94ce55f3e);
        $zc5bfr59         = $A0c027a94ce55f3e;
        $zc5bfr60         = false;
        if (stripos($A0c027a94ce55f3e, "<iframe") === 0)
            {
            $zc5bfr59 = $this->parseAttribute($A0c027a94ce55f3e, "src");
            $zc5bfr60 = true;
            }
        if (strpos($zc5bfr59, "//") === 0)
            {
            $zc5bfr59 = "http:" . $zc5bfr59;
            }
        $zc5bfr61 = $this->providerIdByURL($zc5bfr59);
        $zc5bfr62 = $this->providerData($zc5bfr61);
        if (empty($zc5bfr62))
            {
            break;
            }
        $zc5bfr63 = array();
        if (empty($zc5bfr62["embed_api"]))
            {
            if ($zc5bfr61 == self::PROVIDER_VK)
                {
                $zc5bfr63["title"]            = "";
                $zc5bfr63["thumbnail_url"]    = "";
                $zc5bfr63["thumbnail_width"]  = 0;
                $zc5bfr63["thumbnail_height"] = 0;
                $zc5bfr63["provider_name"]    = "vk";
                $zc5bfr63["provider_url"]     = "http://vk.com";
                $zc5bfr63["html"]             = $A0c027a94ce55f3e;
                $zc5bfr63["embed_url"]        = $zc5bfr59;
                $zc5bfr63["video_url"]        = "";
                $zc5bfr63["width"]            = (int) $this->parseAttribute($A0c027a94ce55f3e, "width");
                $zc5bfr63["height"]           = (int) $this->parseAttribute($A0c027a94ce55f3e, "height");
                }
            }
        else
            {
            if ($zc5bfr60)
                {
                $zc5bfr59 = $this->embedToVideoURL($zc5bfr59, $zc5bfr61);
                }
            else
                {
                if (preg_match($zc5bfr62["embed_url"], $zc5bfr59))
                    {
                    $zc5bfr59 = $this->embedToVideoURL($zc5bfr59, $zc5bfr61);
                    }
                }
            $zc5bfr63 = $this->request($zc5bfr62["embed_api"], array(
                "url" => $zc5bfr59,
                "format" => "json"
            ), false);
            if (empty($zc5bfr63) || strpos($zc5bfr63, "{") !== 0)
                {
                break;
                }
            $zc5bfr63 = json_decode($zc5bfr63, true);
            if (!empty($zc5bfr63["html"]))
                {
                $zc5bfr63["embed_url"] = $this->parseAttribute($zc5bfr63["html"], "src");
                if (strpos($zc5bfr63["embed_url"], "//") === 0)
                    {
                    $zc5bfr63["embed_url"] = "http:" . $zc5bfr63["embed_url"];
                    }
                }
            $zc5bfr63["video_url"] = $zc5bfr59;
            }
        $zc5bfr63["provider_name"] = strtolower($zc5bfr63["provider_name"]);
        $zc5bfr63["provider_id"]   = $zc5bfr61;
        $zc5bfr63["video_id"]      = $this->videoIdByEmbedURL($zc5bfr63["embed_url"], $zc5bfr61);
        if (!empty($zc5bfr62["flash_url"]))
            {
            $zc5bfr63["flash_url"] = strtr($zc5bfr62["flash_url"], array(
                "[ID]" => $zc5bfr63["video_id"]
            ));
            }
        else
            {
            $zc5bfr63["flash_url"] = $zc5bfr59;
            }
        if ($Gd3b8da92685fd75e34)
            {
            $zc5bfr63["thumbnail_url_ex"] = $this->thumbnailEx($zc5bfr63);
            }
        $zc5bfr58 = $zc5bfr63;
        if (!false)
            {
            return $zc5bfr58;
            }
        }
    protected function thumbnailEx($d884e379)
        {
        $zc5bfr64 = "";
        while (empty($d884e379) || $d884e379["provider_id"] == self::PROVIDER_UNKNOWN)
            {
            break;
            }
        $zc5bfr65 = $d884e379["video_id"];
        if (empty($zc5bfr65))
            {
            break;
            }
        switch ($d884e379["provider_id"])
        {
            case self::PROVIDER_YOUTUBE:
                $zc5bfr64 = "http://img.youtube.com/vi/" . $zc5bfr65 . "/0.jpg";
                break;
            case self::PROVIDER_VIMEO:
                if ($zc5bfr66 = simplexml_load_file("http://vimeo.com/api/v2/video/" . $zc5bfr65 . ".xml"))
                    {
                    $zc5bfr64 = $zc5bfr66->video->thumbnail_large ? (string) $zc5bfr66->video->thumbnail_large : (string) $zc5bfr66->video->thumbnail_medium;
                    }
                break;
            case self::PROVIDER_RUTUBE:
                if ($zc5bfr66 = simplexml_load_file("http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=movie&rt_movie_id=" . $zc5bfr65 . "&utf=1"))
                    {
                    $zc5bfr64 = (string) $zc5bfr66->movie->thumbnailLink;
                    }
                break;
            case self::PROVIDER_VK:
        }
        if (!false)
            {
            return $zc5bfr64;
            }
        }
    protected function embedToVideoURL($U52407221af, $M7f6fbe7308487922 = self::PROVIDER_UNKNOWN)
        {
        if (empty($U52407221af))
            {
            return "";
            }
        $zc5bfr67 = "";
        if (is_array($U52407221af))
            {
            if (empty($U52407221af["embed_url"]))
                {
                return "";
                }
            $zc5bfr67 = $U52407221af["embed_url"];
            }
        else
            {
            if (is_string($U52407221af))
                {
                if (stripos($U52407221af, "<iframe") === 0)
                    {
                    $zc5bfr67 = $this->parseAttribute($U52407221af, "src");
                    }
                else
                    {
                    if (strpos($U52407221af, "a:") === 0)
                        {
                        $U52407221af = unserialize($U52407221af);
                        return $this->embedToVideoURL($U52407221af, $M7f6fbe7308487922);
                        }
                    $zc5bfr67 = $U52407221af;
                    }
                }
            }
        if ($M7f6fbe7308487922 == self::PROVIDER_UNKNOWN)
            {
            $M7f6fbe7308487922 = $this->providerIdByURL($zc5bfr67);
            if ($M7f6fbe7308487922 == self::PROVIDER_UNKNOWN)
                {
                return "";
                }
            }
        $zc5bfr68 = $this->providerData($M7f6fbe7308487922);
        if (empty($zc5bfr68))
            {
            return "";
            }
        if (empty($zc5bfr68["embed_api"]))
            {
            return $zc5bfr67;
            }
        $zc5bfr69 = $this->videoIdByEmbedURL($zc5bfr67, $M7f6fbe7308487922);
        return strtr($zc5bfr68["video_url"], array(
            "[ID]" => $zc5bfr69
        ));
        }
    protected function videoIdByEmbedURL($O4ea4d36ee, $vb68dde3627c11f31)
        {
        $zc5bfr70 = $this->providerData($vb68dde3627c11f31);
        if (!empty($zc5bfr70) && !empty($zc5bfr70["embed_url"]) && preg_match($zc5bfr70["embed_url"], $O4ea4d36ee, $zc5bfr71) && !empty($zc5bfr71[2]))
            {
            return $zc5bfr71[2];
            }
        return "";
        }
    protected function providerIdByURL($eb1fda2af)
        {
        $zc5bfr72 = self::PROVIDER_UNKNOWN;
        while (empty($eb1fda2af) || !is_string($eb1fda2af) || mb_strlen($eb1fda2af) < 10)
            {
            break;
            }
        if (strpos($eb1fda2af, "//") === 0)
            {
            $eb1fda2af = "http:" . $eb1fda2af;
            }
        $zc5bfr73 = parse_url($eb1fda2af);
        if (empty($zc5bfr73["host"]))
            {
            break;
            }
        $zc5bfr73["host"] = str_replace("www.", "", $zc5bfr73["host"]);
        foreach ($this->providers as $zc5bfr74 => $zc5bfr75)
            {
            foreach ($zc5bfr75["host"] as $zc5bfr76)
                {
                if (stripos($zc5bfr73["host"], $zc5bfr76) === 0)
                    {
                    $zc5bfr72 = $zc5bfr74;
                    break 2;
                    }
                }
            }
        if (!false)
            {
            return $zc5bfr72;
            }
        }
    protected function providerData($ab05c478da)
        {
        return isset($this->providers[$ab05c478da]) ? $this->providers[$ab05c478da] : false;
        }
    protected function setProviderData($bba4ddcc442, $l5788e72968de9e8b = array())
        {
        $this->providers[$bba4ddcc442] = $l5788e72968de9e8b;
        }
    protected function parseAttribute($ad5c0e31234d2dc3, $Z7e273105943011, $He069f = "\"")
        {
        if (preg_match("/" . preg_quote($Z7e273105943011) . "=" . $He069f . "([^" . $He069f . "]+)" . $He069f . "/", $ad5c0e31234d2dc3, $zc5bfr77) && !empty($zc5bfr77[1]))
            {
            return $zc5bfr77[1];
            }
        return "";
        }
    protected function request($ba7b022f29, $F14781af = array(), $e321e6c = false)
        {
        if (!$e321e6c)
            {
            $ba7b022f29 .= "?" . http_build_query($F14781af);
            }
        $zc5bfr78 = curl_init($ba7b022f29);
        curl_setopt($zc5bfr78, CURLOPT_FAILONERROR, true);
        curl_setopt($zc5bfr78, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($zc5bfr78, CURLOPT_TIMEOUT, 10);
        if ($e321e6c)
            {
            curl_setopt($zc5bfr78, CURLOPT_POST, true);
            if (!empty($F14781af))
                {
                curl_setopt($zc5bfr78, CURLOPT_POSTFIELDS, $F14781af);
                }
            }
        $zc5bfr79 = curl_exec($zc5bfr78);
        curl_close($zc5bfr78);
        return $zc5bfr79;
        }
    }
?>