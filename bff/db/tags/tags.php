<?php namespace bff\db;

/**
 * Базовый класс для работы с тегами.
 * @abstract
 * @version 0.21
 * @modified 15.apr.2014
 *
 * Используемые таблицы:
 * {tblTags} - таблица тегов (пример структуры в install.mysql.sql)
 * {tblTagsIn} - таблица связи тегов с записями
 */

use Errors;

abstract class Tags extends \Module
{
    /** @var string таблица тегов */
    protected $tblTags;
    /** @var string таблица связи тегов с записями */
    protected $tblTagsIn;
    protected $tblTagsIn_ItemID = 'item_id';

    /**
     * @var string URL списка записей с фильтрацией по тегу
     * @Example: $this->adminLink('{items_listing}&tag=', '{module}')
     */
    protected $urlItemsListing = '';

    /**
     * @var bool включена ли постмодерация тегов
     */
    protected $postModeration = false;

    /**
     * @var array текстовки
     */
    protected $lang = array(
        'list'         => 'Список тегов',
        'add_title'    => 'Добавление тега',
        'add_text'     => 'Введите теги, каждый с новой строки',
        'edit'         => 'Редактирование тега',
        'replace_text' => 'Введите название тега для замены',
    );

    /**
     * Конструктор
     */
    public function __construct()
    {
        # Инициализируем модуль в качестве компонента (неполноценного модуля)
        $this->initModuleAsComponent('tags', PATH_CORE . 'db' . DS . 'tags');
        $this->init();
        $this->initSettings();
    }

    abstract protected function initSettings();

    /**
     * Формирование страницы списка тегов для управления (просмотр/добавление/редактирование/удаление)
     */
    public function manage()
    {
        if (!$this->haveAccessTo('tags-listing')) {
            return $this->showAccessDenied();
        }

        $postModeration = $this->postModerationEnabled();

        if (\Request::isAJAX()) {
            if (!$this->haveAccessTo('tags-manage'))
                return $this->showAccessDenied();

            switch ($this->input->get('act', TYPE_STR)) {
                /**
                 * Редактирование тега - инициализация
                 * @param UINT ::tag_id ID тега
                 */
                case 'edit':
                {
                    $nTagID = $this->input->get('tag_id', TYPE_UINT);
                    if (!$nTagID) $this->ajaxResponse(Errors::UNKNOWNRECORD);

                    $aData = $this->tagData($nTagID);
                    if (empty($aData)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                    $aData['add'] = 0;
                    $aData['form'] = $this->viewPHP($aData, 'admin.tags.form');
                    $this->ajaxResponse($aData);
                }
                break;
                /**
                 * Модерация тега
                 * @param UINT ::tag_id ID тега
                 */
                case 'moderate':
                {
                    $nTagID = $this->input->get('id', TYPE_UINT);
                    $aResponse = array();
                    do {
                        if (!$nTagID) {
                            $this->errors->unknownRecord();
                            break;
                        }
                        if (!$postModeration) {
                            $this->errors->impossible();
                            break;
                        }
                        $this->tagSave($nTagID, array('moderated' => 1));
                    } while (false);

                    $this->ajaxResponseForm($aResponse);
                }
                break;
                /**
                 * Autocomplete тега для замены
                 * @param NOTAGS ::q строка поиска тега
                 */
                case 'replace-autocomplete':
                {
                    $sQuery = $this->input->post('q', TYPE_NOTAGS);
                    $this->tagsAutocomplete($sQuery, false);
                }
                break;
                /**
                 * Удаление тега
                 * @param UINT ::tag_id ID тега
                 */
                case 'delete':
                {
                    $nTagID = $this->input->get('tag_id', TYPE_UINT);
                    if (!$nTagID) $this->ajaxResponse(Errors::UNKNOWNRECORD);

                    $res = $this->tagDelete($nTagID);

                    $this->ajaxResponse((!empty($res) ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;
            }
        } else if (\Request::isPOST()) {
            if (!$this->haveAccessTo('tags-manage'))
                return $this->showAccessDenied();

            switch ($this->input->postget('act', TYPE_STR)) {
                /**
                 * Добавление тега (нескольких тегов)
                 * @param STR ::tags список тегов
                 */
                case 'add-finish':
                {
                    $this->tagsSave(0, TYPE_STR);
                }
                break;
                /**
                 * Редактирование тега - сохранение
                 * @param UINT ::tag_id ID тега
                 * @param STR ::tag название тега
                 * @param UINT ::replace_tag_id ID тега, на который необходимо заменить редактируемый
                 */
                case 'edit-finish':
                {
                    $p = $this->input->postm(array(
                            'tag_id'         => TYPE_UINT,
                            'tag'            => TYPE_STR,
                            'replace_tag_id' => TYPE_UINT,
                        )
                    );
                    extract($p, EXTR_REFS);
                    $tag = mb_strtolower(strip_tags($tag));

                    if (!$tag_id) $this->errors->unknownRecord();
                    if ($tag == '') {
                        $this->errors->set(_t('tags', 'Название указано некорректно'));
                    }
                    if ($replace_tag_id) {
                        if ($replace_tag_id == $tag_id) {
                            $this->errors->impossible();
                        } else {
                            $new_data = $this->tagData($replace_tag_id);
                            if (empty($new_data)) {
                                $this->errors->unknownRecord();
                            }
                        }
                    }

                    if ($this->errors->no()) {
                        if ($replace_tag_id) {
                            $this->tagReplace($tag_id, $replace_tag_id);
                            $this->tagDelete($tag_id);
                        } else {
                            $this->tagSave($tag_id, array('tag' => $tag));
                        }
                    }
                }
                break;
            }

            $sFilter = $this->input->post('filter', TYPE_STR);
            $this->adminRedirect(Errors::SUCCESS, \bff::$event . (!empty($sFilter) ? '&' . $sFilter : ''), \bff::$class);
        }

        $aData = array('add' => 1);

        # filter
        $nPageID = $this->input->getpost('page', TYPE_UINT);
        if (!$nPageID) $nPageID = 1;
        $aFilter = array();

        $nCount = $this->tagsListing($aFilter, true);

        $aData['pgn'] = $this->generatePagenation($nCount, 10, 'jTags.page({pageId})', $sqlLimit, 'pagenation.ajax.tpl', 'page', true);

        $aData['list'] = $this->tagsListing($aFilter, false, ($postModeration ? 'moderated ASC, tag ASC' : 'tag ASC'), $sqlLimit);
        $aData['list'] = $this->viewPHP($aData, 'admin.tags.listing.ajax');

        $aData['filter'] = http_build_query(array('page' => $nPageID));

        if (\Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list'   => $aData['list'],
                    'pgn'    => $aData['pgn'],
                    'filter' => $aData['filter'],
                )
            );
        }

        $aData['page'] = $nPageID;
        $aData['form'] = $this->viewPHP($aData, 'admin.tags.form');

        return $this->viewPHP($aData, 'admin.tags.listing');
    }

    /**
     * Форма редактирования связи записи с тегами
     * @param int $nItemID ID записи
     * @param string $sSuggestURL URL для автокомплита
     * @param int $nInputWidth ширина поля ввода тегов
     * @return string HTML
     */
    public function tagsForm($nItemID, $sSuggestURL = '', $nInputWidth = 600)
    {
        $aData = array(
            'tags'       => ($nItemID > 0 ? $this->tagsGet($nItemID) : array()),
            'suggestUrl' => $sSuggestURL,
            'inputWidth' => $nInputWidth,
        );

        return $this->viewPHP($aData, 'admin.tags.item.form');
    }

    # --------------------------------------------------------------------

    /**
     * Формируем запрос на получение списка тегов
     * @param bool $bCount только подсчет кол-ва
     * @param array $aFilter фильтр списка
     * @param string $sqlOrder SQL сортировка
     * @param string $sqlLimit SQL лимит выборки
     * @return array|int|mixed
     */
    protected function tagsListing(array $aFilter = array(), $bCount = false, $sqlOrder = 'items DESC', $sqlLimit = '')
    {
        $aFilter = \Model::filter($aFilter, 'T');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(*) FROM ' . $this->tblTags . ' T ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT T.*, COUNT(TT.' . $this->tblTagsIn_ItemID . ') as items
                    FROM ' . $this->tblTags . ' T
                        LEFT JOIN ' . $this->tblTagsIn . " TT ON TT.tag_id = T.id
                    " . $aFilter['where'] . "
                    GROUP BY T.id
                    ORDER BY $sqlOrder $sqlLimit", $aFilter['bind']
        );
    }

    /**
     * Получаем данные о теге по его ID
     * @param int $nTagID ID тега
     * @return array|mixed
     */
    public function tagData($nTagID)
    {
        return $this->db->one_array('SELECT * FROM ' . $this->tblTags . ' WHERE id = :id', array(':id' => $nTagID));
    }

    /**
     * Получаем данные о теге по его названию
     * @param string $sTagName название тега
     * @return array|mixed
     */
    public function tagDataByName($sTagName)
    {
        if (empty($sTagName)) return false;

        return $this->db->one_array('SELECT * FROM ' . $this->tblTags . ' WHERE tag = :name', array(':name' => $sTagName));
    }

    /**
     * Сохранение тега
     * @param int $nTagID ID тега
     * @param array $aData данные
     * @return int
     */
    protected function tagSave($nTagID, array $aData = array())
    {
        if ($nTagID > 0) {
            $this->db->update($this->tblTags, $aData, array('id' => $nTagID));
        } else {
            return $this->db->insert($this->tblTags, $aData, 'id');
        }
    }

    /**
     * Замена одного тега другим
     * @param integer $nTagID ID тега, который необходимо заменить
     * @param integer $nTagNewID ID тега, на который необходимо заменить
     * @return boolean
     */
    protected function tagReplace($nTagID, $nTagNewID)
    {
        if (!$nTagID || !$nTagNewID || $nTagID == $nTagNewID) return false;

        $aItemsID = $this->db->select_one_column('SELECT ' . $this->tblTagsIn_ItemID . '
                        FROM ' . $this->tblTagsIn . '
                        WHERE tag_id = :id', array(':id' => $nTagNewID)
        );

        # связываем записи с новым тегом
        $this->db->update($this->tblTagsIn,
            array('tag_id' => $nTagNewID),
            array('tag_id' => $nTagID, $this->db->prepareIN($this->tblTagsIn_ItemID, $aItemsID, true))
        );

        return true;
    }

    /**
     * Удаление тега
     * @param integer $nTagID ID тега
     * @return bool
     */
    protected function tagDelete($nTagID)
    {
        # удаляем тег
        $res = $this->db->delete($this->tblTags, array('id' => $nTagID));
        if (!empty($res)) {
            # удаляем связь тега с записями
            $this->db->delete($this->tblTagsIn, array('tag_id' => $nTagID));

            return true;
        }

        return false;
    }

    /**
     * Обрабатываем удаление записи
     * @param int $nItemID ID записи
     * @return bool
     */
    public function onItemDelete($nItemID)
    {
        # удаляем связь записи($nItemID) с тегами
        $this->db->delete($this->tblTagsIn, array($this->tblTagsIn_ItemID => $nItemID));
    }

    /**
     * Сохранение тегов (при работе с формой записи)
     * @param integer $nItemID ID записи
     * @param mixed $mData данные
     * @return boolean
     */
    public function tagsSave($nItemID, $mData = null)
    {
        if (is_null($mData)) {
            $mData = TYPE_ARRAY_STR;
        }
        if (is_integer($mData)) {
            $mData = $this->input->post('tags', $mData);
        }

        if (empty($mData) && !$nItemID)
            return false;

        if (is_string($mData)) {
            # разбиваем строку, каждый новый тег с новой строки
            $aTags = explode("\r\n", strtr(mb_strtolower(strip_tags($mData)), array('"' => '', '\'' => '')));
            $this->input->clean($aTags, TYPE_ARRAY_STR);
            foreach ($aTags as $key => $v) {
                if ($v == '') {
                    unset($aTags[$key]);
                    continue;
                }
                //$v = mb_strtoupper(mb_substr($v{0})).mb_substr($v,1); // тег => Тег
                $aTags[$key] = trim($v);
            }
        } elseif (is_array($mData) && $nItemID) {
            $newPrefix = '__##';
            $newPrefixOffset = strlen($newPrefix);
            $aItemTagsID = array();
            $aTags = array();
            foreach ($mData as $v) {
                if (strpos($v, $newPrefix) === 0) {
                    $v = substr($v, $newPrefixOffset);
                    if ($v != '') $aTags[] = trim($v);
                } else {
                    $v = intval($v);
                    if ($v > 0) $aItemTagsID[] = $v;
                }
            }
        }

        $aTags = array_unique($aTags);

        if (!empty($aTags)) {
            $sqlTags = array();
            $i = 0;
            foreach ($aTags as $v) {
                $sqlTags[':tag' . $i++] = $v;
            }

            # исключаем существующие теги
            $aExistingTags = $this->db->select_one_column('SELECT tag FROM ' . $this->tblTags . '
                                        WHERE tag IN (' . join(',', array_keys($sqlTags)) . ')', $sqlTags
            );
            $sqlTagsNew = array();
            foreach ($aTags as $v) {
                if (!in_array($v, $aExistingTags)) {
                    $tag = array('tag' => $v);
                    if ($this->postModerationEnabled() && !\bff::adminPanel()) {
                        $tag['moderated'] = 0;
                    }
                    $sqlTagsNew[] = $tag;
                }
            }

            # добавляем новые
            if (!empty($sqlTagsNew)) {
                $this->db->multiInsert($this->tblTags, $sqlTagsNew);
            }
        }

        # обновляем связь записи с тегами
        if ($nItemID) {
            $sqlSearchBind = array();
            $sqlSearch = array();
            if (!empty($aItemTagsID)) {
                $sqlSearch[] = 'id IN(' . (join(',', $aItemTagsID)) . ')';
            }
            if (!empty($sqlTags)) {
                $sqlSearch[] = 'tag IN(' . (join(',', array_keys($sqlTags))) . ')';
                $sqlSearchBind = $sqlTags;
            } else {
                # если новые теги не добавляли
                # проверяем действительно ли менялась связь записи с тегами
                $sTagsCurrent = $this->input->post('tags_current', TYPE_STR);
                ksort($aItemTagsID);
                if ($sTagsCurrent === join(',', $aItemTagsID)) {
                    return true;
                }
            }

            # удаляем текущую связь с тегами
            $this->db->delete($this->tblTagsIn, array($this->tblTagsIn_ItemID => $nItemID));
            if (!empty($sqlSearch)) {
                $aItemTagsID = $this->db->select_one_column('SELECT id FROM ' . $this->tblTags . '
                                WHERE (' . join(' OR ', $sqlSearch) . ')', $sqlSearchBind
                );
                $sqlItemTagsNew = array();
                if (!empty($aItemTagsID)) {
                    $aItemTagsID = array_unique($aItemTagsID);
                    foreach ($aItemTagsID as $v) {
                        $sqlItemTagsNew[] = array(
                            $this->tblTagsIn_ItemID => $nItemID,
                            'tag_id'                => $v,
                        );
                    }
                    if (!empty($sqlItemTagsNew)) {
                        # связываем запись с тегами
                        $this->db->multiInsert($this->tblTagsIn, $sqlItemTagsNew);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Получаем список тегов записи
     * @param int $nItemID ID записи
     * @param boolean $bModeratedOnly только промодерированные
     * @return array
     */
    public function tagsGet($nItemID, $bModeratedOnly = true)
    {
        if ($nItemID > 0) {
            $aTags = $this->db->select('SELECT T.id, T.tag
                        FROM ' . $this->tblTags . ' T,
                             ' . $this->tblTagsIn . ' TI
                        WHERE TI.' . $this->tblTagsIn_ItemID . ' = :item_id
                          AND TI.tag_id = T.id' . ($bModeratedOnly && $this->postModerationEnabled() ? ' AND T.moderated = 1 ' : ''),
                array(':item_id' => $nItemID)
            );

            return (!empty($aTags) ? \func::array_transparent($aTags, 'id', true) : array());
        } else {
            return array();
        }
    }

    /**
     * Данные для формирования облака тегов
     * @param int $nMinSize минимальный размер тега
     * @param boolean $bModeratedOnly только промодерированные
     * @return array
     */
    public function tagsCloud($nMinSize = 12, $bModeratedOnly = true)
    {
        $aFilter = array();
        if ($bModeratedOnly && $this->postModerationEnabled()) {
            $aFilter['moderated'] = 1;
        }
        $aTags = $this->tagsListing($aFilter);
        if (!empty($aTags)) {
            $nMaximum = 0;
            foreach ($aTags as $v) {
                if ($v['items'] > $nMaximum) $nMaximum = $v['items'];
            }
            foreach ($aTags as $k => $v) {
                $aTags[$k]['size'] = $nMinSize + ($v['items'] > 0 ? round(($nMinSize * ($v['items'] / $nMaximum))) : 0);
            }
        } else {
            $aTags = array();
        }

        return $aTags;
    }

    /**
     * Подсказка тега по начальным символам
     * @param string $sQuery начальные символы тега
     * @param boolean $bModeratedOnly только промодерированные
     * @param string|bool $mType тип autocomplete-контрола
     * @return mixed
     */
    public function tagsAutocomplete($sQuery, $bModeratedOnly = true, $mType = false)
    {
        if (!empty($sQuery)) {
            $aData = $this->db->select('SELECT id, tag FROM ' . $this->tblTags . '
                WHERE tag LIKE :q
                    ' . ($bModeratedOnly && $this->postModerationEnabled() ? ' AND moderated = 1 ' : '') . '
                ORDER BY tag
            ', array(':q' => $sQuery . '%')
            );
        } else {
            $aData = array();
        }
        $this->autocompleteResponse($aData, 'id', 'tag', $mType);
    }

    /**
     * Включена ли постмодерация тегов
     * @return bool
     */
    public function postModerationEnabled()
    {
        return $this->postModeration;
    }
}