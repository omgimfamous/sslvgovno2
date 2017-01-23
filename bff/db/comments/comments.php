<?php namespace bff\db;

/**
 * Базовый класс для работы с комментариями.
 * @abstract
 * @version 0.31
 * @modified 29.jan.2014
 *
 * Используемые таблицы:
 * tblComments - таблица комментариев (пример структуры в install.sql)
 * tblItems - таблица записей (к которым оставляются комментарии)
 * TABLE_USERS - таблица пользователей
 */

use Errors, Request;

abstract class Comments extends \Module
{
    /** @var string таблица комментариев */
    protected $tblComments;
    protected $tblCommentsGroupID = false; # поле ID группы записей или FALSE (группы не используются)

    /** @var string таблица записей */
    protected $tblItems;
    protected $tblItemsID = 'id'; # поле ID в таблице записей

    /** @var bool включена премодерация комментариев */
    protected $preModeration = true;

    /** @var int минимальная длина текста комментария */
    protected $minMessageLen = 10;
    /** @var int максимальная длина текста комментария */
    protected $maxMessageLen = 2500;

    /** @var bool добавляемые из админ. панели комментарии => непромодерированные */
    protected $emulateAdmCommentsModeration = false;

    /**
     * @var mixed удалять текст комментария при удалении комментария:
     *   false - не удалять
     *   true - удалять с заменой на пустую строку ('')
     *   "текст" - удалять с заменой на "текст"
     */
    protected $eraseMessageOnDelete = false;

    # тип комментариев
    protected $commentsTree = true; # древовидные
    protected $commentsTree2Levels = false; # древовидные (не более 2 уровней вложенности)

    # счетчики комментариев
    # общий счетчик непромодерированных комментариев всех записей (config-ключ)
    protected $counterKey_UnmoderatedAll = '';
    # счетчик комментариев записи (промодерированных, если включена премодерация) (имя поля в таблице записей)
    protected $counterKey_ItemComments = '';

    # флаги, определяющие инициатора удаления комментария либо причину его скрытия
    const commentDeletedByModerator = 1; # удален модератором
    const commentDeletedByCommentOwner = 2; # удален автором комментария
    const commentDeletedByItemOwner = 3; # удален автором записи
    const commentFromBlockedUser = 4; # не отображается по причине блокировки аккаунта

    /**
     * Текстовки объясняющие удаление(скрытие) комментария
     * @var array
     */
    protected $commentHideReasons = array(
        self::commentDeletedByModerator    => 'Удален модератором',
        self::commentDeletedByCommentOwner => 'Удален автором комментария',
        self::commentDeletedByItemOwner    => 'Удален автором записи',
        self::commentFromBlockedUser       => 'Комментарий от заблокированного пользователя',
    );

    protected $urlListing = '';
    /** @Example: $this->adminLink('{edit&tab=comments}&id=', '{module}') */
    protected $urlListingAjax = ''; /** @Example: $this->adminLink('{comments_ajax}', '{module}') */

    /** @var integer ID Группы комментариев */
    protected $groupID = 0;

    /**
     * Конструктор
     */
    public function __construct()
    {
        # Инициализируем модуль в качестве компонента (неполноценного модуля)
        $this->initModuleAsComponent('comments', PATH_CORE . 'db' . DS . 'comments');
        $this->init();
        $this->initSettings();
    }

    abstract protected function initSettings();

    /**
     * Установка ID группы записей
     * @param integer $nGroupID
     */
    public function setGroupID($nGroupID = 0)
    {
        $this->groupID = intval($nGroupID);
    }

    /**
     * Используются ли группы записей
     * @return boolean
     */
    public function isGroupUsed()
    {
        return !empty($this->tblCommentsGroupID);
    }

    /**
     * Список комментариев
     * @param int $nItemID ID записи (к которой прикрепляется комментарий)
     * @param string|boolean $mUrlListingAjax ajax URL или FALSE
     * @return string HTML
     */
    public function admListing($nItemID, $mUrlListingAjax = false)
    {
        $aData['comments'] = $this->commentsData($nItemID, 0);
        $aData['tree'] = $this->commentsTree;
        $aData['tree2lvl'] = $this->commentsTree2Levels;
        $aData['url_ajax'] = (!empty($mUrlListingAjax) ? $mUrlListingAjax : $this->urlListingAjax . '&item_id=' . $nItemID . '&act=');
        $aData['deltxt'] = $this->commentHideReasons;
        $aData['group_id'] = $this->groupID;

        # TODO: Переписать на {php} шаблон
        return $this->viewTPL($aData, 'admin.comments.tpl');
    }

    /**
     * Общий список комментариев (к разным записям) на модерацию
     * @param integer $nLimit кол-во на страницу
     * @return mixed
     */
    public function admListingModerate($nLimit = 15)
    {
        $aData['offset'] = $this->input->getpost('offset', TYPE_UINT);
        if ($aData['offset'] <= 0) {
            $aData['offset'] = 0;
        }
        $nOffset = $aData['offset'];

        $aData['comments'] = $this->db->select('SELECT C.*, U.name as uname, U.blocked as ublocked
                FROM ' . $this->tblComments . ' C
                    , ' . TABLE_USERS . ' U
                WHERE C.item_id > 0 AND C.user_id = U.user_id AND C.moderated = 0 AND C.deleted = 0
                    ' . ($this->isGroupUsed() ? ' AND C.' . $this->tblCommentsGroupID . ' = ' . $this->groupID : '') . '
                ORDER BY C.id DESC
                ' . $this->db->prepareLimit($nOffset, $nLimit + 1)
        );

        # generate pagenation: prev, next
        # TODO: Pagination
        $this->generatePagenationPrevNext(null, $aData, 'comments', $nLimit, 'jCommentsPgn');

        $this->admListingIncludes();

        $aData['list'] = $this->viewPHP($aData, 'admin.comments.mod.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        } else {
            return $this->viewPHP($aData, 'admin.comments.mod');
        }
    }

    /**
     * Обработка ajax-запросов от js компонента работы с комментариями
     */
    public function admAjax()
    {
        if (Request::isAJAX()) {
            $nGroupID = $this->input->postget('group_id', TYPE_UINT);
            $this->setGroupID($nGroupID);

            $nItemID = $this->input->postget('item_id', TYPE_UINT);
            $bMass = $this->input->get('mass', TYPE_BOOL);
            if ((!$bMass && !$nItemID)) {
                $this->errors->impossible();
                $this->ajaxResponse(false);
            }

            switch ($this->input->getpost('act', TYPE_STR)) {
                /**
                 * Добавление комментария
                 * @param string 'message' текст сообщения
                 * @param integer 'reply' ID комментария, на который выполняется ответ
                 */
                case 'comment-add':
                {
                    $sMessage = $this->input->post('message', TYPE_STR);
                    $sMessage = $this->input->cleanTextPlain($sMessage, $this->maxMessageLen, true);
                    if (!empty($this->minMessageLen) && (mb_strlen($sMessage) < $this->minMessageLen)) {
                        $this->errors->set(sprintf('Минимальная длина текста ответа: %s', \tpl::declension($this->minMessageLen, 'символ;символа;символов')));
                        $this->ajaxResponse(false);
                    }

                    $nParentCommentID = $this->input->post('reply', TYPE_UINT);
                    $nCommentID = $this->commentInsert($nItemID, array(
                            'message' => $sMessage,
                        ), $nParentCommentID
                    );

                    if (!$nCommentID) {
                        $this->ajaxResponse(false);
                    }

                    $this->ajaxResponse(array('comment_id' => $nCommentID));
                }
                break;

                /**
                 * Удаление комментария
                 * @param integer 'comment_id' ID комментария
                 */
                case 'comment-delete':
                {
                    $nCommentID = $this->input->post('comment_id', TYPE_UINT);
                    if (!$nCommentID) $this->ajaxResponse(Errors::IMPOSSIBLE);

                    $bResult = $this->commentDelete($nItemID, $nCommentID, self::commentDeletedByModerator);

                    $this->ajaxResponse(($bResult ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;

                /**
                 * Удаление комментариев (пакетно)
                 * @param integers array 'c' ID комментариев
                 */
                case 'comment-delete-mass': # удаляем только непромодерированные
                {
                    $aCommentsID = $this->input->post('c', TYPE_ARRAY_UINT);
                    if (empty($aCommentsID)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                    if ($this->preModeration) # премодерация
                    {
                        $aCommentsID = $this->db->select_one_column('SELECT id FROM ' . $this->tblComments . '
                                        WHERE id IN(' . join(',', $aCommentsID) . ') AND moderated = 0'
                        );
                        if (empty($aCommentsID)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                        # УДАЛЯЕМ ПОЛНОСТЬЮ
                        $this->db->delete($this->tblComments, array('id' => $aCommentsID));

                        # счетчик комментариев записей не обновляем(updateItemCommentsCounter), т.к. включен режим ПРЕмодерации

                        # обновляем только общий счетчик
                        $this->updateUnmoderatedAllCounter(-sizeof($aCommentsID));
                    } else # пост-модерация
                    {
                        $aComments = $this->db->select('SELECT id, pid, item_id
                                        FROM ' . $this->tblComments . '
                                        WHERE id IN(' . join(',', $aCommentsID) . ') AND moderated = 0'
                        );
                        if (empty($aComments)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                        $aMarkDeletedID = array();
                        $aDeleteID = array();
                        $aDecrementItemID = array();

                        foreach ($aComments as $c) {
                            if ($c['pid']) {
                                # есть parent, значить только помечаем как "удален модератором"
                                $aMarkDeletedID[] = $c['id'];
                            } else {
                                # УДАЛЯЕМ ПОЛНОСТЬЮ
                                $aDeleteID[] = $c['id'];
                                if (!isset($aDecrementItemID[$c['item_id']])) {
                                    $aDecrementItemID[$c['item_id']] = -1;
                                } else {
                                    $aDecrementItemID[$c['item_id']] -= 1;
                                }
                            }
                        }

                        if (!empty($aMarkDeletedID)) {
                            $aUpdate = array(
                                'deleted'   => self::commentDeletedByModerator,
                                'moderated' => 1,
                            );
                            if ($this->eraseMessageOnDelete !== false) {
                                $aUpdate['message'] = (is_string($this->eraseMessageOnDelete) ? $this->eraseMessageOnDelete : '');
                            }
                            $this->db->update($this->tblComments, $aUpdate, array('id' => $aMarkDeletedID));
                        }

                        if (!empty($aDeleteID)) {
                            $this->db->delete($this->tblComments, array('id' => $aDeleteID));
                            $this->updateItemCommentsCounter($aDecrementItemID, false);
                        }

                        # обновляем общий счетчик
                        $this->updateUnmoderatedAllCounter(-sizeof($aComments));
                    }

                    $this->ajaxResponse(Errors::SUCCESS);

                }
                break;

                /**
                 * Модерация комментария
                 * @param integer 'comment_id' ID комментария
                 */
                case 'comment-moderate':
                {
                    $nCommentID = $this->input->post('comment_id', TYPE_UINT);
                    if (!$nCommentID) $this->ajaxResponse(Errors::IMPOSSIBLE);

                    $res = $this->db->update($this->tblComments, array(
                            'moderated' => 1,
                        ), array('id' => $nCommentID, 'item_id' => $nItemID, 'deleted' => 0, 'moderated' => 0)
                    );

                    if ($res) {
                        if ($this->preModeration) {
                            $this->updateItemCommentsCounter($nItemID, 1);
                        }
                        # обновляем общий счетчик
                        $this->updateUnmoderatedAllCounter(-1);
                    }

                    $this->ajaxResponse(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;

                /**
                 * Модерация комментариев (пакетно)
                 * @param integers array 'c' ID комментариев
                 */
                case 'comment-moderate-mass':
                {
                    $aCommentsID = $this->input->post('c', TYPE_ARRAY_UINT);
                    if (empty($aCommentsID)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                    if ($this->preModeration) # премодерация
                    {
                        $aIncrement = $this->db->select('SELECT item_id, COUNT(id) as total
                                        FROM ' . $this->tblComments . '
                                        WHERE id IN(' . join(',', $aCommentsID) . ') AND deleted = 0 AND moderated = 0
                                        GROUP BY item_id'
                        );
                        if (empty($aIncrement)) $this->ajaxResponse(Errors::IMPOSSIBLE);

                        $aIncrementResult = array();
                        $nModeratedTotal = 0;
                        foreach ($aIncrement as $v) {
                            $aIncrementResult[$v['item_id']] = $v['total'];
                            $nModeratedTotal += $v['total'];
                        }
                        if (empty($aIncrementResult)) {
                            $this->ajaxResponse(Errors::IMPOSSIBLE);
                        }
                    }

                    $res = $this->db->update($this->tblComments, array(
                            'moderated' => 1,
                        ), 'id IN(' . join(',', $aCommentsID) . ') AND deleted = 0 AND moderated = 0'
                    );
                    if ($res) {
                        if ($this->preModeration && !empty($aIncrementResult)) {
                            $this->updateItemCommentsCounter($aIncrementResult, false);
                        }
                        # обновляем общий счетчик
                        $this->updateUnmoderatedAllCounter(-$res);
                    }

                    $this->ajaxResponse(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;

                /**
                 * Обновление списка комментариев, после добавления комментария
                 * @param integer 'comment_id_last' ID добавленного комментария
                 */
                case 'comment-response':
                {
                    $nCommentLastID = $this->input->post('comment_id_last', TYPE_UINT);
                    $aResponse = $this->commentsData($nItemID, $nCommentLastID);

                    $aCmt = array();
                    foreach ($aResponse['aComments'] as $aComment) {
                        $aCmt[] = array(
                            'id'   => $aComment['id'],
                            'pid'  => $aComment['pid'],
                            'html' => $this->viewPHP($aComment, 'admin.comments.ajax')
                        );
                    }
                    $aResponse['aComments'] = $aCmt;
                    $this->ajaxResponse($aResponse);
                }
                break;
            }
        }
    }

    /**
     * Подключаем js файлы, необходимые для работы со списком комментариев в админ. панели
     */
    public function admListingIncludes()
    {
        \tpl::includeJS('comments', true);
    }

    /**
     * Обновляем счетчик комментариев записи
     * @param integer|array $mItemID ID записи или array(itemID=>increment,...)
     * @param integer $nIncrement инкремент (1, -1)
     * @return mixed
     */
    protected function updateItemCommentsCounter($mItemID, $nIncrement)
    {
        if (empty($mItemID)) return false;

        $sCounter = $this->counterKey_ItemComments;
        $sID = $this->tblItemsID;

        if (is_array($mItemID)) {
            $aUpdateData = array();
            foreach ($mItemID as $itemID => $increment) {
                $aUpdateData[] = "WHEN $itemID THEN " . (int)$increment;
            }

            if (!empty($aUpdateData)) {
                $this->db->exec('UPDATE ' . $this->tblItems . '
                                    SET ' . $sCounter . ' = ' . $sCounter . ' +
                                            ( CASE ' . $sID . ' ' . join(' ', $aUpdateData) . ' ELSE 0 END )
                                    WHERE ' . $sID . ' IN(' . join(',', array_keys($mItemID)) . ')'
                );

                return true;
            }

            return false;
        } else {
            return $this->db->exec('UPDATE ' . $this->tblItems . '
                SET ' . $sCounter . ' = ' . $sCounter . ' + ' . ((int)$nIncrement) . '
                WHERE ' . $sID . ' = :id', array(':id' => $mItemID)
            );
        }
    }

    /**
     * Обновляем общий счетчик непромодерированных комментариев
     * @param integer|bool|null $mIncrement инкремент:
     *  > 1, -1 - +/- к текущему значению
     *  > false - получаем текущее значение счетчика
     *  > NULL - пересчитываем(актуализируем) текущее значение
     */
    public function updateUnmoderatedAllCounter($mIncrement = false)
    {
        $sCounterKey = $this->counterKey_UnmoderatedAll;
        if ($mIncrement === false) {
            # получаем кол-во непромодерированных комментариев
            return (int)\config::get($sCounterKey, 0);
        } elseif (is_null($mIncrement)) {
            # пересчитываем счетчик
            $nCount = (int)$this->db->one_data('SELECT COUNT(id)
               FROM ' . $this->tblComments . ' WHERE moderated = 0'
            );
            \config::save($sCounterKey, $nCount, true);
        } else {
            # обновляем счетчик
            \config::saveCount($sCounterKey, $mIncrement, true);
        }
    }

    protected function buildCommentsRecursive($aComments, $bBegin = true)
    {
        static $aResultComments, $iLevel, $iMaxIdComment, $nTotal;
        if ($bBegin) {
            $nTotal = sizeof($aComments);
            $aComments = $this->db->transformRowsToTree($aComments, 'id', 'pid', 'sub');
            $aResultComments = array();
            $iLevel = 0;
            $iMaxIdComment = 0;
        }
        foreach ($aComments as $aComment) {
            $aTemp =& $aComment;
            if ($aComment['id'] > $iMaxIdComment) {
                $iMaxIdComment = $aComment['id'];
            }
            $aTemp['level'] = $iLevel;
            $aResultComments[] = $aTemp;
            if (!empty($aComment['sub'])) {
                $iLevel++;
                $this->buildCommentsRecursive($aComment['sub'], false);
            }
            unset($aTemp['sub']);
        }
        $iLevel--;

        return array('aComments' => $aResultComments, 'nMaxIdComment' => $iMaxIdComment, 'total' => $nTotal);
    }

    /**
     * Получение данных обо всех комментариях к записи
     * - в случае с frontend'ом и включенной preModeration: исключаем непромодерированные
     * @param integer $nItemID ID записи
     * @param integer $nCommentLastID ID комментария, с которого следует начать выборку
     * @param boolean $bSimpleTree простое дерево (выполняем только transformRowsToTree)
     * @return array
     */
    public function commentsData($nItemID, $nCommentLastID = 0, $bSimpleTree = false)
    {
        $sql = array();
        $bind = array(':itemID' => $nItemID);
        if ($nCommentLastID > 0) {
            $sql[] = 'C.id > :lastID';
            $bind[':lastID'] = $nCommentLastID;
        }
        # в случае с frontend'ом и включенной preModeration: исключаем непромодерированные
        if (!\bff::adminPanel()) {
            if ($this->preModeration) {
                $sql[] = 'C.moderated = 1';
            }
        }
        # учитываем ID группы
        if ($this->isGroupUsed()) {
            $sql[] = 'C.' . $this->tblCommentsGroupID . ' = :groupID';
            $bind[':groupID'] = $this->groupID;
        }
        $aData = $this->db->select('SELECT C.*, U.name as uname, U.blocked as ublocked
                FROM ' . $this->tblComments . ' C
                    LEFT JOIN ' . TABLE_USERS . ' U ON C.user_id = U.user_id
                WHERE C.item_id = :itemID' . (!empty($sql) ? ' AND ' . join(' AND ', $sql) : ' ') . '
                ORDER BY C.id ASC', $bind
        );

        if (!empty($aData)) {
            foreach ($aData as $k => $v) {
                if ($v['ublocked'] && !$v['deleted']) {
                    $aData[$k]['deleted'] = self::commentFromBlockedUser;
                }
            }
            if ($bSimpleTree) {
                return array(
                    'total'     => sizeof($aData),
                    'aComments' => $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub'),
                );
            }

            return $this->buildCommentsRecursive($aData);
        }

        return array('aComments' => array(), 'nMaxIdComment' => 0, 'total' => 0);
    }

    /**
     * Получение данных комментария
     * @param integer $nItemID ID записи
     * @param integer $nCommentID ID комментария
     * @return array
     */
    public function commentData($nItemID, $nCommentID)
    {
        $aBind = array(':commentID' => $nCommentID, ':itemID' => $nItemID);
        $bGroupUsed = $this->isGroupUsed();
        if ($bGroupUsed) $aBind[':groupID'] = $this->groupID;

        return $this->db->one_array('SELECT * FROM ' . $this->tblComments . '
                WHERE id = :commentID AND item_id = :itemID' .
            ($bGroupUsed ? ' AND ' . $this->tblCommentsGroupID . ' = :groupID' : ''),
            $aBind
        );
    }

    /**
     * Сохранение комментария в базу
     * @param integer $nItemID ID записи
     * @param array $aData данные
     * @param integer $nParentCommentID ID parent-комментария
     * @return mixed ID добавленного комментария или FALSE
     */
    public function commentInsert($nItemID, $aData, $nParentCommentID = 0)
    {
        $bGroupUsed = $this->isGroupUsed();
        if ($nParentCommentID > 0 && $this->commentsTree) # древовидные
        {
            /* Проверяем:
             * - существует ли parent
             * - не удален ли parent
             * - относится ли новый комментарий и parent к одной и той же записи
             * - промодерирован ли parent (при включеной премодерации)
             * - максимальный уровень вложенности (при включеной настройке "два уровня вложенности")
             */
            $aParentBind = array(':commentID' => $nParentCommentID);
            if ($bGroupUsed) $aParentBind[':groupID'] = $this->groupID;
            $aParentComment = $this->db->one_array('SELECT id, pid, numlevel, item_id, deleted, moderated
                                FROM ' . $this->tblComments . '
                                WHERE id = :commentID' .
                ($bGroupUsed ? ' AND ' . $this->tblCommentsGroupID . ' = :groupID' : ''),
                $aParentBind
            );

            if (!$aParentComment ||
                $aParentComment['deleted'] > 0 ||
                $aParentComment['item_id'] != $nItemID ||
                ($this->preModeration && !$aParentComment['moderated']) ||
                ($this->commentsTree2Levels && $aParentComment['pid'] > 0)
            ) {
                $this->errors->set(_t('comments', 'Ошибка добавления комментария'));

                return false;
            }
            $nNumlevel = $aParentComment['numlevel'] + 1;
        } else {
            $nParentCommentID = 0;
            $nNumlevel = 1;
        }

        if (!\bff::adminPanel()) {
            $nModerated = 0;
        } else {
            $nModerated = ($this->emulateAdmCommentsModeration ? 0 : 1);
        }

        $aData['pid'] = ($nParentCommentID ? $nParentCommentID : null);
        $aData['numlevel'] = $nNumlevel;
        $aData['item_id'] = $nItemID;
        $aData['user_id'] = $this->security->getUserID();
        $aData['user_ip'] = Request::remoteAddress();
        $aData['created'] = $this->db->now();
        $aData['moderated'] = $nModerated;
        $aData['message'] = nl2br($aData['message']);
        if ($bGroupUsed) {
            $aData[$this->tblCommentsGroupID] = $this->groupID;
        }

        $nCommentID = $this->db->insert($this->tblComments, $aData, 'id');

        if (!$nCommentID) {
            $this->errors->set(_t('comments', 'Ошибка добавления комментария'));

            return false;
        }

        if (!$nModerated) {
            $this->updateUnmoderatedAllCounter(1);
        }

        if (!$this->preModeration) {
            $this->updateItemCommentsCounter($nItemID, 1);
        }

        return $nCommentID;
    }

    /**
     * Удаление комментария
     * @param integer $nItemID ID записи
     * @param integer $nCommentID ID комментария
     * @param integer $nDeletedBy инициатор удаления (владелец, модератор...)
     * @return boolean
     */
    public function commentDelete($nItemID, $nCommentID, $nDeletedBy)
    {
        $bGroupUsed = $this->isGroupUsed();
        $aComment = $this->db->one_array('SELECT id, pid, moderated, deleted
                    ' . ($bGroupUsed ? ', ' . $this->tblCommentsGroupID : '') . '
               FROM ' . $this->tblComments . ' WHERE id = :id AND item_id = :itemid',
            array(':id' => $nCommentID, ':itemid' => $nItemID)
        );

        if (!$aComment || $aComment['deleted'] > 0) {
            return false;
        }
        if ($bGroupUsed && $aComment[$this->tblCommentsGroupID] != $this->groupID) {
            return false;
        }

        $aUpdate = array(
            'deleted'   => $nDeletedBy,
            'moderated' => 1,
        );
        if ($this->eraseMessageOnDelete !== false) {
            $aUpdate['message'] = (is_string($this->eraseMessageOnDelete) ? $this->eraseMessageOnDelete : '');
        }

        if ($this->commentsTree) # древовидные
        {
            if ($this->preModeration && !$aComment['moderated']) {
                # премодерация включена; непромодерирован, значит child-комментариев не имеет;
                # УДАЛЯЕМ ПОЛНОСТЬЮ
                $this->db->delete($this->tblComments, array('id' => $nCommentID));
                # счетчик комментариев записи не обновляем(updateItemCommentsCounter), поскольку режим ПРЕмодерации
            } else {
                # помечаем как "удален $nDeletedBy" и "промодерирован"
                $this->db->update($this->tblComments, $aUpdate, array('id' => $nCommentID));
            }
        } else # недревовидные
        {
            if ($aComment['pid']) {
                # есть parent, значит остался при переключении с "древовидных"
                # значить только помечаем как "удален $nDeletedBy"
                $this->db->update($this->tblComments, $aUpdate, array('id' => $nCommentID));
            } else {
                # УДАЛЯЕМ
                $this->db->delete($this->tblComments, array('id' => $nCommentID));
                $this->updateItemCommentsCounter($nItemID, -1);
            }
        }

        if (!$aComment['moderated']) {
            # обновляем общий счетчик
            $this->updateUnmoderatedAllCounter(-1);
        }

        return true;
    }

    /**
     * Возвращаем список текстовых описаний возможных причин объясняющих скрытие комментария
     * @return array
     */
    public function getHideReasons()
    {
        return $this->commentHideReasons;
    }
}