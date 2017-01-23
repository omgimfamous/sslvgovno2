<?php 
$fieldsCode = "" . " <tr><td class=\"row1 field-title\"><?= \$v['t'] ?>:</td><td class=\"row2\"><?php switch(\$v['type']) {case 'text': { ?><input type=\"text\" name=\"<?= \$k ?>[<?= \$key ?>]\" <?= \$v['attr'] ?> value=\"<?= ( isset(\$data[\$k][\$key]) ? HTML::escape(\$data[\$k][\$key]) : '' ) ?>\" /><?php } break;case 'textarea': { ?><textarea name=\"<?= \$k ?>[<?= \$key ?>]\" <?= \$v['attr'] ?>><?= ( isset(\$data[\$k][\$key]) ? \$data[\$k][\$key] : '' ) ?></textarea><?php } break;case 'wy': { ?><textarea name=\"<?= \$k ?>[<?= \$key ?>]\" id=\"seo_wy_<?= \$k.'_'.\$key ?>\" <?= \$v['attr'] ?>><?= ( isset(\$data[\$k][\$key]) ? \$data[\$k][\$key] : '' ) ?></textarea><?php } break;} ?>" . ($template ? "<?php if (isset(\$tpl[\$k])){ ?><div class=\"well well-small desc j-template-preview" . (!$template_use ? " displaynone" : "") . "\" style=\"border:none;\"><?= ( ! empty(\$tpl[\$k][\$key]) ? \$tpl[\$k][\$key] : '<i>общий шаблон не заполнен</i>' ) ?></div><?php } ?>" : "") . "</td> </tr>\n";
echo "<div id=\"j-seo-form-";
echo bff::$class . "-" . bff::$event;
echo "-block\" style=\"padding: 2px;\"> <table class=\"admtbl tbledit\"> ";
echo $this->locale->buildForm($aData, join("-", array( bff::$class, bff::$event, "item" )), "" . " <?php     \$fields = &\$aData['fields'];     \$data = &\$aData['data'];     \$tpl = &\$aData['template']; ?> <?php foreach(\$fields as \$k=>\$v) { if(\$v['after']) continue; ?>" . $fieldsCode . "<?php } ?> <tr>     <td class=\"row1 field-title\" width=\"" . $width . "\">Заголовок:<br /><span class=\"desc small\">(title)</span></td>     <td class=\"row2\">         <input class=\"stretch lang-field j-input\" type=\"text\" name=\"mtitle[<?= \$key ?>]\" value=\"<?= ( isset(\$data['mtitle'][\$key]) ? HTML::escape(\$data['mtitle'][\$key]) : ''); ?>\" />         " . ($template ? "<div class=\"well well-small desc j-template-preview" . (!$template_use ? " displaynone" : "") . "\" style=\"border:none;\"><?= ( ! empty(\$tpl['mtitle'][\$key]) ? \$tpl['mtitle'][\$key] : '<i>общий шаблон не заполнен</i>' ) ?></div>" : "") . "     </td> </tr> <tr>     <td class=\"row1 field-title\">Ключевые слова:<br /><span class=\"desc small\">(meta keywords)</span></td>     <td class=\"row2\">        <textarea name=\"mkeywords[<?= \$key ?>]\" class=\"lang-field j-input j-expanding\" style=\"min-height:85px;\"><?= ( isset(\$data['mkeywords'][\$key]) ? HTML::escape(\$data['mkeywords'][\$key]) : ''); ?></textarea>        " . ($template ? "<div class=\"well well-small desc j-template-preview" . (!$template_use ? " displaynone" : "") . "\" style=\"border:none;\"><?= ( ! empty(\$tpl['mkeywords'][\$key]) ? \$tpl['mkeywords'][\$key] : '<i>общий шаблон не заполнен</i>' ) ?></div>" : "") . "     </td> </tr> <tr>     <td class=\"row1 field-title\">Описание:<br /><span class=\"desc small\">(meta description)</span></td>     <td class=\"row2\">         <textarea name=\"mdescription[<?= \$key ?>]\" class=\"lang-field j-input j-expanding\" style=\"min-height:85px;\"><?= ( isset(\$data['mdescription'][\$key]) ? HTML::escape(\$data['mdescription'][\$key]) : ''); ?></textarea>         " . ($template ? "<div class=\"well well-small desc j-template-preview" . (!$template_use ? " displaynone" : "") . "\" style=\"border:none;\"><?= ( ! empty(\$tpl['mdescription'][\$key]) ? \$tpl['mdescription'][\$key] : '<i>общий шаблон не заполнен</i>' ) ?></div>" : "") . "     </td> </tr> <?php foreach(\$fields as \$k=>\$v) { if(\$v['before']) continue; ?>" . $fieldsCode . "<?php } ?> <?php     unset(\$fields, \$data); ?>");
echo "    <tr>     <td class=\"row1 field-title\">Макросы:</td>     <td class=\"row2\">         <div class=\"left\" style=\"max-width:530px;\">         ";
foreach( $macros as $k => $v ) 
{
    echo "                <a href=\"#\" class=\"j-macros\" style=\"display:inline-block; padding: 2px 7px; margin-bottom: 2px; border-radius: 3px; border: 1px solid #ccc; color:#666;\" data-toggle=\"tooltip\" title=\"";
    echo HTML::escape($v["t"]);
    echo "\">";
    echo $k;
    echo "</a>         ";
}
echo "</div><div class=\"right\" style=\"padding: 2px 4px;\">";
if( $template ) 
{
    echo "                    <label class=\"checkbox inline j-template-toggler-label\" data-toggle=\"tooltip\" title=\"";
    echo HTML::escape("Шаблон страницы<br />\"" . $template_title . "\"");
    echo "\"><input type=\"checkbox\" class=\"j-template-toggler\" name=\"mtemplate\"";
    if( $template_use ) 
    {
        echo " checked=\"checked\"";
    }

    echo " />использовать общий шаблон</label>             ";
}

echo "</div><div class=\"clearfix\"></div></td> </tr> </table> <script type=\"text/javascript\">\$(function(){var \$block = \$('#j-seo-form-";
echo bff::$class . "-" . bff::$event;
echo "-block');var inputActive = null;\$block.find('textarea.j-expanding').autogrow({minHeight:85, lineHeight:16});         if( bff.bootstrapJS() ) {             \$block.find('.j-macros').tooltip({placement:'top',html:true});         }         \$block.find('.j-macros').on('click', function(e){ nothing(e);             bff.textInsert(inputActive, '{'+\$(this).text()+'}');});\$block.on('focus', '.j-input', function(e){ inputActive = this; });";
if( $init_wy ) 
{
    echo "\$block.find('.j-wy').bffWysiwyg({autogrow: false});         ";
}

echo " var \$templateTogglerLabel = \$block.find('.j-template-toggler-label');\$templateTogglerLabel.on('click', '.j-template-toggler', function(e){ \$block.find('.j-template-preview').toggleClass('displaynone', !\$(this).is(':checked')); if( bff.bootstrapJS() ) { \$templateTogglerLabel.tooltip('hide'); } }); if( bff.bootstrapJS() ) { \$templateTogglerLabel.tooltip({placement:'top',html:true}); } }); </script>\n</div>";

