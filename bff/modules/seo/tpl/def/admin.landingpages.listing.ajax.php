<?php
    /**
     * @var $this SEO
     */
    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><?= HTML::escape((!empty($v['title']) ? tpl::truncate($v['title'], 45, '...', true) : $v['landing_uri'])) ?></td>
        <td class="left"><a href="<?= bff::urlBase(false).$v['landing_uri'] ?>" target="_blank"><?= HTML::escape($v['landing_uri']) ?></a></td>
        <td>
            <a class="but <?= ($v['enabled']?'un':'') ?>block landingpage-toggle" title="Включен" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
            <a class="but edit landingpage-edit" title="Редактировать" href="#" data-id="<?= $id ?>"></a>
            <a class="but del landingpage-del" title="Удалить" href="#" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<?php endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="4">
            ничего не найдено
        </td>
    </tr>
<?php endif;