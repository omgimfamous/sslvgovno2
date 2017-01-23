<?php

/**
 * Компонент выполняющий формирование "постраничной навигации"
 * @version 0.22
 * @created 18.dec.2013
 * @modified 25.jun.2014
 */
class Pagination extends \Component
{
    /** Макрос заменяемый в строке запроса на "page=pageID" */
    const PAGE_PARAM = '{page-param}';

    /** Макрос заменяемый в строке запроса на "pageID" */
    const PAGE_ID = '{page-id}';

    /** @var string ключ текущей страницы в GET/POST запросе */
    public $pageVar = 'page';

    /** @var int кол-во ссылок на "соседние страниц" */
    public $pageNeighbours = 2;

    /** @var bool выполнять валидацию номера текущей страницы (получаемой из запроса) */
    public $validateCurrentPage = true;

    private $_itemsTotal = 0;
    private $_pageSize = 0;
    private $_pageCurrent = null;
    private $_linkHref = '';
    private $_linkOnClick = '';

    /**
     * Конструктор
     * @param integer $nItemsTotal общее кол-во записей
     * @param integer $nPageSize кол-во записей на странице
     * @param array|string $mLinkHref URL для ссылки перехода с подставляемыми макросами self::PAGE_
     *    string - полная ссылка с макросом
     *    array - ссылка(link) + параметры(query)
     * @param string $sLinkOnClick javascript для ссылки перехода с подставляемыми макросами self::PAGE_
     */
    public function __construct($nItemsTotal, $nPageSize, $mLinkHref, $sLinkOnClick = '')
    {
        $this->init();
        $this->setItemsTotal($nItemsTotal);
        $this->setPageSize($nPageSize);

        if (is_array($mLinkHref)) {
            do {
                if (empty($mLinkHref) || empty($mLinkHref['link'])) {
                    $mLinkHref = '';
                    break;
                }
                if (!empty($mLinkHref['query'])) {
                    if (is_array($mLinkHref['query'])) {
                        foreach ($mLinkHref['query'] as $k => &$v) {
                            if (empty($v) && $k !== $this->pageVar) {
                                unset($mLinkHref['query'][$k]);
                            }
                        }
                        unset($v);
                    } else {
                        $mLinkHref['query'] = '';
                    }
                }
                if (empty($mLinkHref['query'])) {
                    $mLinkHref = $mLinkHref['link'];
                }
            } while (false);
        }
        $this->_linkHref = $mLinkHref;

        $this->_linkOnClick = $sLinkOnClick;
    }

    /**
     * @return integer кол-во записей на странице
     */
    public function getPageSize()
    {
        return $this->_pageSize;
    }

    /**
     * @param integer $value кол-во записей на странице
     */
    public function setPageSize($value)
    {
        if (($this->_pageSize = $value) <= 0) {
            $this->_pageSize = 10;
        }
    }

    /**
     * @return integer общее кол-во записей, по-умолчанию 0
     */
    public function getItemsTotal()
    {
        return $this->_itemsTotal;
    }

    /**
     * @param integer $value общее кол-во записей
     */
    public function setItemsTotal($value)
    {
        if (($this->_itemsTotal = $value) < 0) {
            $this->_itemsTotal = 0;
        }
    }

    /**
     * @return integer номер последней страницы (общее кол-во страниц)
     */
    public function getPageLast()
    {
        return ceil($this->_itemsTotal / $this->_pageSize);
    }

    /**
     * @param boolean $bRecalc выполнить пересчет текущей страницы
     * @return integer номер текущей страницы, по-умолчанию 1
     */
    public function getCurrentPage($bRecalc = false)
    {
        if ($this->_pageCurrent === null || $bRecalc) {
            if (isset($_GET[$this->pageVar]) || isset($_POST[$this->pageVar])) {
                $this->_pageCurrent = (int)(isset($_GET[$this->pageVar]) ? $_GET[$this->pageVar] : $_POST[$this->pageVar]);
                if ($this->validateCurrentPage) {
                    $pageLast = $this->getPageLast();
                    if ($this->_pageCurrent > $pageLast) {
                        $this->_pageCurrent = $pageLast;
                    }
                }
                if ($this->_pageCurrent <= 0) {
                    $this->_pageCurrent = 1;
                }
            } else {
                $this->_pageCurrent = 1;
            }
        }

        return $this->_pageCurrent;
    }

    /**
     * @param integer $value номер текущей страницы, 1+
     */
    public function setCurrentPage($value)
    {
        if (!$value || $value <= 0) {
            $value = 1;
        }
        $this->_pageCurrent = $value;
        $_GET[$this->pageVar] = $_POST[$this->pageVar] = $value;
    }

    /**
     * @return integer SQL OFFSET
     */
    public function getOffset()
    {
        return (($this->getCurrentPage() - 1) * $this->getPageSize());
    }

    /**
     * @return integer SQL LIMIT
     */
    public function getLimit()
    {
        return $this->getPageSize();
    }

    /**
     * @return string SQL LIMIT + OFFSET
     */
    public function getLimitOffset()
    {
        return $this->db->prepareLimit($this->getOffset(), $this->getLimit());
    }

    /**
     * Формирование атрибутов ссылки постраничной навигации
     * @param integer $nPageId номер страницы
     * @param array $aAttributes доп. атрибуты
     * @return string
     */
    public function linkAttributes($nPageId, array $aAttributes = array())
    {
        if (is_string($this->_linkHref)) {
            $aAttributes['href'] = $this->_linkHref;
        } else {
            if (is_array($this->_linkHref)) {
                $link = $this->_linkHref['link'];
                if (isset($this->_linkHref['query'])) {
                    $linkQuery = $this->_linkHref['query'];
                    if ($nPageId < 2 && isset($linkQuery[$this->pageVar])) {
                        unset($linkQuery[$this->pageVar]);
                    } else {
                        $linkQuery[$this->pageVar] = $nPageId;
                    }
                    if (sizeof($linkQuery)) {
                        $link .= (strrpos($link, '?') === false ? '?' : '&');
                        $link .= http_build_query($linkQuery);
                    }
                }
                $aAttributes['href'] = $link;
            } else {
                $aAttributes['href'] = '#';
            }
        }
        if (!empty($this->_linkOnClick)) {
            $aAttributes['onclick'] = $this->_linkOnClick;
        }
        foreach ($aAttributes as $k => $v) {
            $aAttributes[$k] = strtr($v, array(
                    self::PAGE_PARAM => $this->pageVar . '=' . $nPageId,
                    self::PAGE_ID    => $nPageId,
                )
            );
        }

        return \HTML::attributes($aAttributes);
    }

    /**
     * Формирование шаблона постраничной навигации
     * @param array $aSettings доп. настройки элементов навигации
     * @param string $sTemplate шаблон
     * @param string|bool $mTemplatePath путь к шаблону, false - TPL_PATH
     * @return string
     */
    public function view(array $aSettings = array(), $sTemplate = 'pagination.standart', $mTemplatePath = false)
    {
        # настройки элементов навигации
        $aSettings = array_merge(array(
                # отображение ссылок постраничной навигации (1, 2, 3...)
                'pages'       => true,
                'pages.attr'  => array(), # дополнительные атрибуты ссылок
                # отображение стрелок <- назад, вперед ->
                'arrows'      => true,
                'arrows.attr' => array(), # дополнительные атрибуты стрелок
                # отображение поля ввода перехода на указанную страницу
                'pageto'      => true,
            ), $aSettings
        );

        # javascript атрибуты:
        if ($aSettings['pages'] && empty($aSettings['pages.attr'])) {
            $aSettings['pages.attr'] = array('data-page' => self::PAGE_ID, 'class' => 'j-pgn-page');
        }
        if ($aSettings['arrows'] && empty($aSettings['arrows.attr'])) {
            $aSettings['arrows.attr'] = array('data-page' => self::PAGE_ID, 'class' => 'j-pgn-page');
        }

        $last = $this->getPageLast();
        $current = $this->getCurrentPage();

        $aData = array(
            'paginator' => $this,
            'pages'     => array(),
            'total'     => $last,
            'first'     => false,
            'last'      => false,
            'current'   => $current,
            'prev'      => false,
            'next'      => false,
            'settings'  => $aSettings,
        );

        if ($last > 0) {
            # arrows
            if ($aSettings['arrows']) {
                if ($current > 1) {
                    $aData['prev'] = $this->linkAttributes($current - 1, $aSettings['arrows.attr']);
                }
                if ($current < $last) {
                    $aData['next'] = $this->linkAttributes($current + 1, $aSettings['arrows.attr']);
                }
            }

            # pages
            if ($aSettings['pages']) {
                if ($this->pageNeighbours < 1) {
                    $this->pageNeighbours = 1;
                }

                # [2]3[current]
                $from = $current - $this->pageNeighbours;
                if ($from < 1) {
                    $from = 1;
                }
                # [first]...
                if ($from > 1) {
                    if (($from - 1) > 1) {
                        $aData['first'] = array(
                            'page'   => 1,
                            'attr'   => $this->linkAttributes(1, $aSettings['pages.attr']),
                            'active' => ($current == 1),
                            'dots'   => $this->linkAttributes(2, $aSettings['pages.attr']),
                        );
                    } else {
                        $from--;
                    }
                }

                #[current]4[5]
                $to = $current + $this->pageNeighbours;
                if ($to > $last) {
                    $to = $last;
                }
                #...[last]
                if ($to < $last) {
                    if (($last - $to) > 1) {
                        $aData['last'] = array(
                            'page'   => $last,
                            'attr'   => $this->linkAttributes($last, $aSettings['pages.attr']),
                            'active' => ($current == $last),
                            'dots'   => $this->linkAttributes($to + 1, $aSettings['pages.attr']),
                        );
                    } else {
                        $to++;
                    }
                }

                # pages data
                for ($i = $from; $i <= $to; $i++) {
                    $aData['pages'][] = array(
                        'page'   => $i,
                        'attr'   => $this->linkAttributes($i, $aSettings['pages.attr']),
                        'active' => ($current == $i),
                    );
                }
            }
        }

        return \View::renderTemplate($aData, $sTemplate, $mTemplatePath);
    }

    /**
     * Формирование шаблона постраничной навигации вида "< назад - вперед >".
     * Без известного общего кол-ва записей.
     * TODO
     */
    function viewPrevNext()
    {
    }
}