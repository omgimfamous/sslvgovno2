<?php

?>
<div id="popupContactView" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Сообщение №<?= $id ?></div>
        <div class="ipopup-content" style="width:643px;">

                <div style="min-height:150px;">

                    <table class="admtbl tbledit">
                        <tr>
                            <th width="115" style="height: 1px;"></th>
                            <th width="20" style="height: 1px;"></th>
                            <th style="height: 1px;"></th>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">Имя:</td>
                            <td></td>
                            <td><?= $name ?></td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">E-mail:</td>
                            <td></td>
                            <td><a href="mailto:<?= $email ?>"><?= $email ?></a></td>
                        </tr>
                        <? if($user_id > 0) { ?>
                        <tr>
                            <td class="row1 field-title right">Пользователь:</td>
                            <td></td>
                            <td>
                                <a href="#" onclick="return bff.userinfo(<?= $user_id ?>);" class="userlink desc ajax"><?= $user_email ?></a>
                            </td>
                        </tr>
                        <? } ?>
                        <tr>
                            <td class="row1 field-title right">Тема:</td>
                            <td></td>
                            <td>
                                <span class="bold"><?= $ctype['title'] ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3"><hr class="cut" /></td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right" style="vertical-align: top;">Сообщение:</td>
                            <td></td>
                            <td style="word-wrap: break-word; max-width: 500px;"><?= nl2br($message) ?></td>
                        </tr>
                    </table>

                </div>

                <div class="ipopup-content-bottom">
                    <ul class="right">
                        <li><a href="<?= $this->adminLink('ban', 'users') ?>" class="desc"><?= $user_ip ?></a></li>
                        <li><span class="post-date" title="дата создания"><?= tpl::date_format2($created, true) ?></span></li>
                    </ul>
                </div>

        </div>
    </div>
</div>