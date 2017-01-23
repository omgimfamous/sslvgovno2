<?php

/**
 * Поиск объявлений при помощи Sphinx
 */
class BBSItemsSearchSphinx extends Component
{
    /** @var SphinxClient */
    public $sphinx = null;
    /** @var array результаты поиска */
    private $searchResult = array();
    /** @var boolean есть ли результаты поиска? */
    private $searchResultEmpty = true;
    /** @var array слова, которые необходимо подсветить */
    public $highlightWords = array();

    public function __construct()
    {
        parent::init();

        require_once(PATH_CORE . 'external/sphinx/sphinxapi.php');

        $this->sphinx = new SphinxClient();
        $this->sphinx->SetServer(config::sys('sphinx.host'), intval(config::sys('sphinx.port')));
    }

    /**
     * Поиск объявлений
     * @param string $sQuery строка поиска
     * @param boolean $bCount только подсчет кол-ва
     * @param integer $nOffset id страницы
     * @param boolean $bOffsetIsPage true - $nOffset это id страницы, false -  $nOffset это кол-во пропускаемых записей
     * @param integer $nLimit лимит результатов на страницу
     * @param array $aFilters дополнительные фильтры [array['key'=>1, 'value'=>2, 'exclude'=>TRUE], ...]
     * @param string $sOrderBy порядок сортировки
     * @return array|boolean
     */
    public function searchItems($sQuery = '', $bCount = false, $nOffset = 1, $bOffsetIsPage = true, $nLimit = 20, $aFilters = array(), $sOrderBy = '@weight DESC, @id DESC')
    {
        if (!empty($sQuery)) {
            $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE, $sOrderBy);
        } else {
            $this->sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'created');
        }
        $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->sphinx->SetRankingMode(SPH_RANK_MATCHANY);
        $this->sphinx->SetFieldWeights(array('title' => 60, 'descr' => 40));

        $sQuery = $this->prepareSearchQuery($sQuery);

        $aRes = $this->search(array(
                'q'            => $sQuery,
                'indexNames'   => 'itemsIndexMain, itemsIndexDelta',
                'offset'       => $nOffset,
                'offsetIsPage' => $bOffsetIsPage
            ),
            $bCount,
            $nLimit,
            $aFilters
        );

        if ($aRes === false) {
            $this->errors->set(_t('bbs', 'Неудалось выполнить запрос к Sphinx'));

            return false;
        }

        # Если поиск дал результаты
        if (!$this->searchResultEmpty) {
            return $this->searchResult;
        } else {
            return array();
        }
    }

    /**
     * Подготовка строки поиска
     * @param string $sQuery строка поиска
     * @return string
     */
    private function prepareSearchQuery($sQuery)
    {
        $sQuery = str_replace(array('*', '-'), '', $sQuery);

        if (strpos($sQuery, '"') === 0) { # поиск точной фразы
            $this->sphinx->SetMatchMode(SPH_MATCH_PHRASE);
            $sQuery = str_replace('"', '', $sQuery);
            $this->highlightWords[] = $sQuery;
        } else {
            # фомируем запрос поиска каждого слова отдельно
            $words = explode(' ', $sQuery);
            $tmp = array();
            foreach ($words as $word) {
                if (strlen($word) >= 3) {
                    $tmp[] = "($word | $word*)";
                    $this->highlightWords[] = $word;
                }
            }
            $sQuery = join(' & ', $tmp);
        }

        return $sQuery;
    }

    /**
     * Поиск и формирование результата
     * @param array $aRequest параметры запроса
     * @param boolean $bCount только подсчет кол-ва
     * @param integer $nLimit лимит результата
     * @return array $aFilters фильтры (атрибуты запроса)
     */
    private function search($aRequest, $bCount, $nLimit, $aFilters = array())
    {
        # считаем количество
        $res['count'] = $this->_search_count($aRequest['q'], $aRequest['indexNames'], $aFilters);
        if ($bCount) {
            $this->searchResultEmpty = false;

            return ($this->searchResult = $res['count']);
        }

        $nOffset = ($aRequest['offsetIsPage'] ? ($aRequest['offset'] - 1) * $nLimit : $aRequest['offset']);

        if ($res['count'] == 0) {
            # ничего не найдено
            return 0;
        } elseif ($nOffset <= $res['count']) {
            # ищем
            $this->searchResult = $this->_search($aRequest['q'], $aRequest['indexNames'], $nOffset, $nLimit, $aFilters);

            # возможно Sphinx-демон не доступен
            if ($this->searchResult === false) {
                return false;
            }

            $this->searchResult = array(
                'id'    => array_keys($this->searchResult['matches']),
                'count' => $res['count'],
            );

            $this->searchResultEmpty = false;

            # заполняем слова, для подсветки
            if (!empty($this->searchResult['words'])) {
                foreach ($this->searchResult['words'] as $word => $founds) {
                    $this->highlightWords[] = str_replace('*', '', $word);
                }
                $this->highlightWords = array_values(array_unique($this->highlightWords));
            }
        }

        return $res;
    }

    private function _search_count($sTerms, $sIndexNames, $aFilters = null)
    {
        $aResults = $this->_search($sTerms, $sIndexNames, 1, 1, $aFilters);
        return (int)$aResults['total_found'];
    }

    /**
     * Выполняем поиск
     * @param string $sTerms условия поиска
     * @param string $sIndexNames названия индексов, используемых при поиске
     * @param integer $nOffset
     * @param integer $iLimit
     * @param array|null $aFilters
     * @return mixed
     */
    private function _search($sTerms, $sIndexNames, $nOffset, $nLimit, $aFilters = null)
    {
        # Параметры поиска
        $this->sphinx->SetLimits($nOffset, $nLimit);

        # Устанавливаем атрибуты поиска
        if (!is_null($aFilters)) {
            foreach ($aFilters as $f) {
                $this->sphinx->SetFilter(
                    $f['key'],
                    (is_array($f['value'])) ? $f['value'] : array($f['value']),
                    (isset($f['exclude']) ? $f['exclude'] : false)
                );
            }
        }

        # Выполняем поиск
        $data = $this->sphinx->Query($sTerms, $sIndexNames);

        if (!is_array($data)) {
            return false; # Скорее всего недоступен демон searchd
        }

        return $data;
    }

    /**
     * Получаем ошибку при последнем обращении к поиску
     * @return string
     */
    public function sphinxLastError()
    {
        return $this->sphinx->GetLastError();
    }

}