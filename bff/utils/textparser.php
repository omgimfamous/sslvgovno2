<?php namespace bff\utils;

/**
 * Класс вспомогательных методов обработки текста
 * @version 0.4
 * @modified 20.aug.2015
 */

class TextParser
{
    /**
     * Инициализация компонента Jevix
     * @return \Jevix
     */
    public function jevix()
    {
        static $i;
        if (!isset($i)) {
            require_once(PATH_CORE . 'external/jevix/jevix.class.php');
            $i = new \Jevix();
        }

        return $i;
    }

    /**
     * Парсит текст комментария (без HTML тегов)
     * @param string $sText текст комментария
     * @param integer $nMaxLength максимально допустимое кол-во символов или 0 (без ограничений)
     * @param boolean|array $mActivateLinks активировать ссылки true|false или массив настроек обработки ссылок (true)
     * @return string
     */
    public function parseCommentPlain($sMessage, $nMaxLength = 0, $mActivateLinks = false)
    {
        $sMessage = preg_replace("/(\<script)(.*?)(script>)/si", '', $sMessage);
        $sMessage = htmlspecialchars($sMessage);
        $sMessage = preg_replace("/(\<)(.*?)(--\>)/mi", nl2br("\\2"), $sMessage);
        if (!empty($nMaxLength) && $nMaxLength > 0) {
            $sMessage = mb_substr($sMessage, 0, intval($nMaxLength));
        }
        if (!empty($mActivateLinks)) {
            $oParser = new LinksParser();
            $aParserOptions = (is_array($mActivateLinks) ? $mActivateLinks : array());
            $sMessage = $oParser->parse($sMessage, $aParserOptions);
        }

        return $sMessage;
    }

    /**
     * Парсинг wysiwyg текста
     * Метод используется компонентом {bff\db\Publicator}
     * @param string $sText текст
     * @param array $aParams доп. настройки:
     *   boolean 'scripts' - разрешать вставку script тегов
     *   boolean 'iframes' - разрешать вставку iframe тегов
     *   array 'links_parser' - настройки обработки ссылок
     * @return string
     */
    public function parseWysiwygText($sText, $aParams = array())
    {
        static $configured;

        $j = $this->jevix();

        if (!isset($configured)) {
            $configured = true;

            # 1. Разрешённые теги. (Все неразрешенные теги считаются запрещенными.)
            $allowedTags = array(
                'a', 'img',
                'i', 'b', 'u', 's', 'em', 'strong', 'small', 'font',
                'nobr', 'map', 'area', 'col', 'colgroup',
                'ul', 'li', 'ol',
                'dd', 'dl', 'dt',
                'sub', 'sup', 'abbr', 'acronym',
                'pre', 'code',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'div', 'p', 'span', 'br', 'hr',
                'object', 'param', 'embed', 'video', 'audio', 'source', 'track',
                'blockquote', 'q', 'caption',
                'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
                # form
                'form', 'input', 'button', 'textarea', 'noscript', 'select', 'opt', 'option', 'optgroup',
                'fieldset', 'label', 'legend',
                # html5:
                'article', 'aside', 'bdi', 'bdo', 'details', 'dialog', 'figcaption', 'figure',
                'footer', 'header', 'main', 'mark', 'menu', 'menuitem', 'meter', 'nav', 'progress',
                'rp', 'rt', 'ruby', 'section', 'summary', 'time', 'wbr',
                'datalist', 'keygen', 'output', 'canvas', 'svg',
            );
            $j->cfgAllowTags($allowedTags);

            # 2. Коротие теги. (не имеющие закрывающего тега)
            $j->cfgSetTagShort(array('br', 'img', 'hr'));

            # 3. Преформатированные теги. (в них всё будет заменяться на HTML сущности)
            $j->cfgSetTagPreformatted(array('pre'));

            # 4. Теги, которые необходимо вырезать из текста вместе с контентом.
            if (!empty($aParams['scripts'])) {
                $j->cfgAllowTags(array('script')); $allowedTags[] = 'script';
                $j->cfgSetTagIsEmpty(array('script','div','span'));
                $j->cfgAllowTagParams('script', array('src', 'type', 'charset', 'async', 'defer'));
                $j->cfgSetTagCallback('script', function($content){ return $content; });
            } else {
                $j->cfgSetTagCutWithContent(array('script'));
            }
            if (!empty($aParams['iframes'])) {
                $j->cfgAllowTags(array('iframe')); $allowedTags[] = 'iframe';
                $j->cfgSetTagIsEmpty(array('iframe'));
                $j->cfgAllowTagParams('iframe', array(
                        'name', 'align', 'src', 'frameborder',
                        'height', 'width', 'scrolling',
                        'marginwidth', 'marginheight'
                    )
                );
            } else {
                $j->cfgSetTagCutWithContent(array('iframe'));
            }
            $j->cfgSetTagCutWithContent(array('style'));

            # 5. Разрешённые параметры тегов. Также можно устанавливать допустимые значения этих параметров.
            $j->cfgAllowTagParams('a', array('title', 'href', 'target', 'rel'));
            $j->cfgAllowTagParams('img', array(
                    'src',
                    'alt'    => '#text',
                    'title',
                    'align'  => array('right', 'left', 'center'),
                    'width'  => '#int',
                    'height' => '#int'
                )
            );

            # specials:
            $j->cfgAllowTagParams('blockquote', array('data-instgrm-captioned', 'data-instgrm-version'));
            $j->cfgAllowTagParams('font', array('color'));

            # allow: style, class, id, lang
            foreach ($allowedTags as $tag) {
                $j->cfgAllowTagParams($tag, array('style', 'class', 'id', 'lang'));
            }

            # allow: align
            foreach (array('span','div','p','blockquote') as $tag) {
                $j->cfgAllowTagParams($tag, array('align'));
            }

            # 6. Параметры тегов являющиеся обязательными. Без них вырезаем тег оставляя содержимое.
            $j->cfgSetTagParamsRequired('img', 'src');

            # 7. Теги которые может содержать тег контейнер
            //    cfgSetTagChilds($tag, $childs, $isContainerOnly, $isChildOnly)
            //       $isContainerOnly : тег является только контейнером для других тегов и не может содержать текст (по умолчанию false)
            //       $isChildOnly : вложенные теги не могут присутствовать нигде кроме указанного тега (по умолчанию false)
            $j->cfgSetTagChilds('ul', 'li', true, false);
            $j->cfgSetTagChilds('ol', 'li', true, false);

            # 8. Атрибуты тегов, которые будут добавляться автоматически
            $j->cfgSetLinkProtocolAllow(array('mailto','skype'));
            //$j->cfgSetTagParamsAutoAdd('a', array('rel' => 'nofollow'));
            //$j->cfgSetTagParamsAutoAdd('a', array('name'=>'rel', 'value' => 'nofollow', 'rewrite' => true));

            $j->cfgSetTagParamDefault('img', 'width', '565px');

            # 9. Автозамена
            $j->cfgSetAutoReplace(array('+/-', '(c)', '(r)'), array('±', '©', '®'));

            # 10. Включаем режим XHTML. (по умолчанию включен)
            $j->cfgSetXHTMLMode(true);

            # 11. Выключаем режим замены переноса строк на тег <br/>. (по умолчанию включен)
            $j->cfgSetAutoBrMode(false);

            # 12. Включаем режим автоматического определения ссылок. (по умолчанию включен)
            $j->cfgSetAutoLinkMode(true);

            # 13. Отключаем типографирование в определенных тегах
            $j->cfgSetTagNoTypography(array('code','video'));
        }

        $sText = str_replace('&nbsp;', ' ', $sText);

        # Подсвечиваем внешние ссылки
        if (!empty($aParams['links_parser']) && is_array($aParams['links_parser'])) {
            if (isset($aParams['links_parser']['highlight-new']) && !$aParams['links_parser']['highlight-new']) {
                $j->cfgSetAutoLinkMode(false);
            }
            $sText = $this->jevix()->parse($sText, $aErrors);
            $linksParser = new \bff\utils\LinksParser();
            return $linksParser->parse($sText, $aParams['links_parser']);
        } else {
            return $this->jevix()->parse($sText, $aErrors);
        }
    }

    /**
     * Парсинг wysiwyg текста при публикации с фронтенда
     * @param string $sText текст
     * @param array $aParams доп. настройки:
     *  string 'img-default-width' - ширина изображения по-умолчанию (если не указана)
     * @return string
     */
    public function parseWysiwygTextFrontend($sText, $aParams = array())
    {
        static $configured;

        if (!isset($configured)) {
            $configured = true;
            $j = $this->jevix();

            # 1. Разрешённые теги. (Все неразрешенные теги считаются запрещенными.)
            $j->cfgAllowTags(array(
                    'a',
                    'img',
                    'i',
                    'b',
                    'u',
                    'em',
                    'strong',
                    'nobr',
                    'li',
                    'ol',
                    'ul',
                    'sub',
                    'sup',
                    'abbr',
                    'pre',
                    'acronym',
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'br',
                    'hr',
                    'p',
                    'span',
                    'div',
                    'code',
                    'blockquote',
                    'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
                )
            );

            # 2. Коротие теги. (не имеющие закрывающего тега)
            $j->cfgSetTagShort(array('br', 'img', 'hr'));

            # 3. Преформатированные теги. (в них всё будет заменяться на HTML сущности)
            $j->cfgSetTagPreformatted(array('pre'));

            # 4. Теги, которые необходимо вырезать из текста вместе с контентом.
            $j->cfgSetTagCutWithContent(array('style','script','iframe'));

            # 5. Разрешённые параметры тегов. Также можно устанавливать допустимые значения этих параметров.
            $j->cfgAllowTagParams('a', array('title', 'href', 'target', 'rel'));
            $j->cfgAllowTagParams('img', array(
                    'class',
                    'src',
                    'alt'    => '#text',
                    'title',
                    'align'  => array('right', 'left', 'center'),
                    'width'  => '#int',
                    'height' => '#int'
                )
            );
            $j->cfgAllowTagParams('span', array('align', 'class'));
            $j->cfgAllowTagParams('div', array('align', 'class'));
            $j->cfgAllowTagParams('ul', array('class'));
            $j->cfgAllowTagParams('li', array('class'));
            $j->cfgAllowTagParams('table', array('style', 'class'));
            $j->cfgAllowTagParams('tr', array('class'));
            $j->cfgAllowTagParams('th', array('class'));
            $j->cfgAllowTagParams('td', array('class'));

            # 6. Параметры тегов являющиеся обязательными. Без них вырезает тег оставляя содержимое.
            $j->cfgSetTagParamsRequired('img', 'src');

            # 7. Теги которые может содержать тег контейнер
            $j->cfgSetTagChilds('ul', 'li', true, false);
            $j->cfgSetTagChilds('ol', 'li', true, false);

            # 8. Атрибуты тегов, которые будут добавляться автоматически
            $j->cfgSetTagParamDefault('a', 'rel', null, true);
            $j->cfgSetLinkProtocolAllow(array('mailto','skype'));
            if (!empty($aParams['img-default-width'])) {
                $j->cfgSetTagParamDefault('img', 'width', $aParams['img-default-width']);
            }

            # 9. Автозамена
            $j->cfgSetAutoReplace(array('+/-', '(c)', '(r)'), array('±', '©', '®'));

            # 10. Включаем режим XHTML.
            $j->cfgSetXHTMLMode(true);

            # 11. Выключаем режим замены переноса строк на тег <br/>.
            $j->cfgSetAutoBrMode(false);

            # 12. Включаем режим автоматического определения ссылок.
            $j->cfgSetAutoLinkMode(false);

            # 13. Отключаем типографирование в определенном теге
            $j->cfgSetTagNoTypography('code');
        }

        $sText = nl2br(preg_replace("/\>(\r\n|\r|\n)/u", '>', $sText));
        $sText = str_replace('&nbsp;', ' ', $sText);

        return $this->jevix()->parse($sText, $aErrors);
    }

    /**
     * Простой метод корректировки неправильной раскладки клавиатуры
     * @param string $string строка, требующая корректировки
     * @param string $from раскладка в которой предположительно набирался текст
     * @param string $to раскладка в которую необходимо конвертировать
     * @return string
     */
    public function correctKeyboardLayout($string, $from = 'en', $to = 'ru')
    {
        static $data = array(
            'en' => array(
                'q','w','e','r','t','y','u',
                'i','o','p','[',']',"\\",'a',
                's','d','f','g','h','j','k',
                'l',';',"'",'z','x','c','v',
                'b','n','m',',','.'
            ),
            'ru' => array(
                'й','ц','у','к','е','н','г',
                'ш','щ','з','х','ъ','ё','ф',
                'ы','в','а','п','р','о','л',
                'д','ж','э','я','ч','с','м',
                'и','т','ь','б','ю'
            ),
            'ua' => array(
                'й','ц','у','к','е','н','г',
                'ш','щ','з','х','ї','ґ','ф',
                'и','в','а','п','р','о','л',
                'д','ж','є','я','ч','с','м',
                'і','т','ь','б','ю'
            ),
        );
        if (!isset($data[$from]) || !isset($data[$to])) {
            return $string;
        }

        return preg_replace($data[$from], $data[$to], mb_strtolower($string));
    }

    /**
     * Антимат фильтр
     * @param string $text текст
     * @param array $customWords дополнительные слова требующие сензурирования
     * @param boolean|string $censure цензурировать true|false или строка: '*', '#'
     * @param boolean|array $highlight подсвечивать true|false или ['start'=>'<em>','stop'=>'</em>']
     * @return string
     */
    public static function antimat($text, array $customWords = array(), $censure = true, $highlight = false)
    {
        static $filter;
        if (!isset($filter))
        {
            $cache = \Cache::singleton('textparser', 'file');
            $cacheKey = 'antimat';
            if (($filter = $cache->get($cacheKey)) === false) {
//                $filter = \config::api('textparser_antimat', array());
                if (!empty($filter['regexp'])) {
                    $filter['regexp'] = base64_decode($filter['regexp']);
                    $filter['except'] = explode(';', base64_decode($filter['except']));
                }
                $cache->set($cacheKey, $filter);
            }
        }

        if (empty($filter['regexp']) || empty($filter['except'])) {
            return $text;
        }

        preg_match_all($filter['regexp'], $text, $m);

        # дополняем
        if (!empty($customWords)) {
            if (!empty($m[1])) {
                $m[1] = array_merge($m[1], $customWords);
            } else {
                $m = array(1=>$customWords);
            }
        }

        $total = sizeof($m[1]);

        if ($total > 0)
        {
            for ($i = 0; $i < $total; $i++)
            {
                # исключения:
                $word = mb_strtolower($m[1][$i]);
                foreach ($filter['except'] as $x) {
                    if (mb_strpos($word, $x) !== false) {
                        unset($m[1][$i]);
                        continue 2;
                    }
                }

                # сторонние символы:
                $m[1][$i] = str_replace(array(' ',',',';','.','!','-','?',"\t","\n"), '', $m[1][$i]);
            }

            $m[1] = array_unique($m[1]);

            # подсвечиваем
            if ($highlight) {
                $start = '<span style="color:red;">';
                $stop = '</span>';
                if (is_array($highlight)) {
                    if (!empty($highlight['start'])) $start = $highlight['start'];
                    if (!empty($highlight['stop'])) $stop = $highlight['stop'];
                }
                $highlight = array();
                foreach ($m[1] as $word) {
                    $highlight[$word] = $start.$word.$stop;
                }
                $text = strtr($text, $highlight);
            }

            # цензурируем
            if ($censure) {
                $asterisk = (is_string($censure) ? $censure : '*');
                $replace = array();
                foreach ($m[1] as $word) {
                    $replace []= str_repeat($asterisk, mb_strlen($word));
                }
                $text = str_replace($m[1], $replace, $text);
            }

        }

        return $text;
    }
}