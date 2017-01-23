<?php 
if( empty($pages) ) 
{
    echo "<div class=\"text-center muted\" style=\"margin: 20px;\">нет шаблонов для редактирования</div>";
}
else
{
    reset($pages);
    $pages[key($pages)]["active"] = true;
    $fieldsCode = "" . "<tr><td><label class=\"field-title\"><?= \$v['t'] ?>:<?php switch(\$v['type']) {case 'text': { ?><input type=\"text\" name=\"<?= \$k ?>[<?= \$key ?>]\" <?= \$v['attr'] ?> value=\"<?= ( isset(\$content[\$k][\$key]) ? HTML::escape(\$content[\$k][\$key]) : '' ) ?>\" /><?php } break;case 'textarea': { ?><textarea name=\"<?= \$k ?>[<?= \$key ?>]\" <?= \$v['attr'] ?>><?= ( isset(\$content[\$k][\$key]) ? \$content[\$k][\$key] : '' ) ?></textarea><?php } break;case 'wy': { ?><textarea name=\"<?= \$k ?>[<?= \$key ?>]\" id=\"<?= \$aData['page_key'].'_'.\$k.'_'.\$key ?>\" <?= \$v['attr'] ?>><?= ( isset(\$content[\$k][\$key]) ? \$content[\$k][\$key] : '' ) ?></textarea><?php } break;} ?></label></td></tr>";
    echo "<div class=\"tabbable tabs-left\" id=\"j-seo-templates-block\"><ul class=\"nav nav-tabs\"><li class=\"nav-header\">Страницы:</li>";
    foreach( $pages as $key => &$v ) 
    {
        echo "<li";
        if( !empty($v["active"]) ) 
        {
            echo " class=\"active\"";
        }

        echo "><a href=\"#j-seo-templates-page-";
        echo $key;
        echo "\"";
        if( empty($v["i"]) ) 
        {
            echo " class=\"bold\"";
        }

        echo " data-toggle=\"tab\">";
        echo $v["t"];
        echo "</a></li>";
    }
    unset($v);
    echo "</ul><div class=\"tab-content\" id=\"j-seo-templates-pages-content\">";
    foreach( $pages as $key => &$v ) 
    {
        $v["page_key"] = $key;
        echo "        <div class=\"tab-pane ";
        if( !empty($v["active"]) ) 
        {
            echo " active";
        }

        echo "\" id=\"j-seo-templates-page-";
        echo $key;
        echo "\"><form action=\"\" id=\"j-seo-templates-block\"><input type=\"hidden\" name=\"act\" value=\"save\" /><input type=\"hidden\" name=\"page\" value=\"";
        echo $key;
        echo "\" />";
        if( !empty($v["inherit"]) ) 
        {
            echo "<div class=\"badge absolute\" style=\"top: -10px; left:50%;\">общий шаблон</div>";
        }

        echo "<table class=\"admtbl tbledit\">";
        echo $this->locale->buildForm($v, "page-" . $key, "" . "<?php \$content = & \$aData['content'];?><?php foreach(\$aData['fields'] as \$k=>&\$v) { if(\$v['after']) continue; ?>" . $fieldsCode . "<?php } unset(\$v); ?><tr><td><label class=\"field-title\">Заголовок:<input class=\"stretch lang-field j-input\" type=\"text\" name=\"mtitle[<?= \$key ?>]\" value=\"<?= ( isset(\$content['mtitle'][\$key]) ? HTML::escape(\$content['mtitle'][\$key]) : '') ?>\" /></label><label class=\"field-title\">Ключевые слова:<textarea name=\"mkeywords[<?= \$key ?>]\" class=\"lang-field j-input j-expanding\" style=\"min-height:85px;\"><?= ( isset(\$content['mkeywords'][\$key]) ? HTML::escape(\$content['mkeywords'][\$key]) : '') ?></textarea></label><label class=\"field-title\">Описание:<textarea name=\"mdescription[<?= \$key ?>]\" class=\"lang-field j-input j-expanding\" style=\"min-height:85px;\"><?= ( isset(\$content['mdescription'][\$key]) ? HTML::escape(\$content['mdescription'][\$key]) : '') ?></textarea></label></td></tr><?php foreach(\$aData['fields'] as \$k=>&\$v) { if(\$v['before']) continue; ?>" . $fieldsCode . "<?php } unset(\$v); ?>", array( "cols" => 1 ));
        echo "</table><div class=\"field-title\">Макросы:</div><div style=\"padding: 8px 8px 8px 0;\">";
        foreach( $v["macros"] as $kk => $vv ) 
        {
            echo "<a href=\"#\" class=\"j-macros";
            if( !empty($vv["in"]) ) 
            {
                echo " bold";
            }

            echo "\" style=\"display:inline-block; padding: 2px 7px; margin-bottom: 2px; border-radius: 3px; border: 1px solid #ccc; color:#666;\" data-toggle=\"tooltip\" title=\"";
            echo HTML::escape($vv["t"]);
            echo "\">";
            echo $kk;
            echo "</a>";
        }
        echo "</div><div style=\"margin-top: 10px;\"><input type=\"button\" class=\"btn btn-success btn-small j-submit\" value=\"Сохранить настройки\" /></div></form></div>";
    }
    unset($v);
    echo "</div></div><script type=\"text/javascript\">\$(function(){ var \$block = \$('#j-seo-templates-block'), formProcessing = false; var inputActive = null; \$block.find('textarea.j-expanding').autogrow({minHeight:85, lineHeight:16}); if( bff.bootstrapJS() ) { \$block.find('.j-macros').tooltip({placement:'top',html:true}); } \$block.find('.j-macros').on('click', function(e){ nothing(e); bff.textInsert(inputActive, '{'+\$(this).text()+'}'); }); \$block.on('focus', '.j-input', function(e){ inputActive = this; }); \$block.on('click', '.j-submit', function(e){ nothing(e); if (formProcessing) return; var \$button = \$(this); var \$form = \$button.closest('form'); bff.ajax('";
    echo $this->adminLink(bff::$event, bff::$class);
    echo "', \$form.serialize(), function(data){ if (data && data.success) { bff.success('Настройки страницы были успешно сохранены') } }, function(p){formProcessing = p; \$form.toggleClass('disabled', p); \$button.prop('disabled', p);}); });";
    if( $init_wy ) 
    {
        echo "\$block.find('.j-wy').bffWysiwyg({autogrow: false});";
    }

    echo "});\n</script>";
}


