<?php

/**
 * Компонент обработки видео ссылок объявлений
 * @modified 18.jan.2014
 */

use bff\utils\VideoParser;

class BBSItemVideo extends VideoParser
{
    /**
     * Получение данных о видео по ссылке на видео, указанной пользователем
     * @param string $url video-ссылка, embed-ссылка, iframe-код
     * @return array
     */
    public function parse($url)
    {
        $aEmbed = $this->embed($url);
        if (empty($aEmbed) || $aEmbed['provider_id'] == self::PROVIDER_UNKNOWN) {
            return array();
        }

        return $aEmbed;
    }

    /**
     * Формируем video-слайд для компонента Fotorama
     * @param string $videoEmbed сериализованные данные о video
     * @return string HTML
     */
    public function viewFotorama($videoEmbed = '')
    {
        if (!empty($videoEmbed) && strpos($videoEmbed, 'a:') === 0) {
            $videoEmbed = func::unserialize($videoEmbed);
            if (!empty($videoEmbed)) {
                if ($videoEmbed['provider_id'] == self::PROVIDER_RUTUBE) {
                    return '<a href="' . HTML::escape($videoEmbed['embed_url']) . '" data-video="true" class="j-view-images-frame">' . (!empty($videoEmbed['thumbnail_url']) ? '<img src="' . $videoEmbed['thumbnail_url'] . '" />' : '') . '</a>';
                }

                return '<a href="' . HTML::escape($videoEmbed['video_url']) . '" data-video="true" class="j-view-images-frame"></a>';
            }
        }

        return '';
    }
}