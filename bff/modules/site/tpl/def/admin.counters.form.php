<?php 
$aData = HTML::escape($aData, "html", array( "title" ));
$edit = !empty($id);
echo "<form name=\"SiteCountersForm\" id=\"SiteCountersForm\" action=\"";
echo $this->adminLink(NULL);
echo "\" method=\"get\" onsubmit=\"return false;\">\n<input type=\"hidden\" name=\"act\" value=\"";
echo $edit ? "edit" : "add";
echo "\" />\n<input type=\"hidden\" name=\"save\" value=\"1\" />\n<input type=\"hidden\" name=\"id\" value=\"";
echo $id;
echo "\" />\n<table class=\"admtbl tbledit\">\n<tr class=\"required\">\n    <td class=\"row1\" width=\"100\"><span class=\"field-title\">Название<span class=\"required-mark\">*</span>:</span></td>\n    <td class=\"row2\">\n        <input class=\"stretch\" type=\"text\" id=\"counter-title\" name=\"title\" value=\"";
echo $title;
echo "\" />\n    </td>\n</tr>\n<tr class=\"required\">\n    <td class=\"row1\"><span class=\"field-title\">Код счетчика<span class=\"required-mark\">*</span>:</span></td>\n    <td class=\"row2\">\n        <textarea class=\"stretch\" rows=\"4\" id=\"counter-code\" name=\"code\">";
echo $code;
echo "</textarea>\n    </td>\n</tr>\n<tr>\n    <td class=\"row1\"><span class=\"field-title\">Включен:</span></td>\n    <td class=\"row2\">\n        <label class=\"checkbox\"><input type=\"checkbox\" id=\"counter-enabled\" name=\"enabled\"";
if( $enabled ) 
{
    echo " checked=\"checked\"";
}

echo " /></label>\n    </td>\n</tr>\n<tr class=\"footer\">\n    <td colspan=\"2\" class=\"row1\">\n        <input type=\"submit\" class=\"btn btn-success button submit\" value=\"Сохранить\" onclick=\"jSiteCountersForm.save(false);\" />\n        ";
if( $edit ) 
{
    echo "<input type=\"button\" class=\"btn btn-success button submit\" value=\"Сохранить и вернуться\" onclick=\"jSiteCountersForm.save(true);\" />";
}

echo "        ";
if( $edit ) 
{
    echo "<input type=\"button\" class=\"btn btn-danger button delete\" value=\"Удалить\" onclick=\"jSiteCountersForm.del(); return false;\" />";
}

echo "        <input type=\"button\" class=\"btn button cancel\" value=\"Отмена\" onclick=\"jSiteCountersFormManager.action('cancel');\" />\n    </td>\n</tr>\n</table>\n</form>\n\n<script type=\"text/javascript\">\nvar jSiteCountersForm =\n(function(){\n    var \$progress, \$form, formChk, id = ";
echo $id;
echo ";\n    var ajaxUrl = '";
echo $this->adminLink(bff::$event);
echo "';\n\n    \$(function(){\n        \$progress = \$('#SiteCountersFormProgress');\n        \$form = \$('#SiteCountersForm');\n        \n    });\n    return {\n        del: function()\n        {\n            if( id > 0 ) {\n                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,\n                    false, {progress: \$progress, repaint: false, onComplete:function(){\n                        bff.success('Запись успешно удалена');\n                        jSiteCountersFormManager.action('cancel');\n                        jSiteCountersList.refresh();\n                    }});\n            }\n        },\n        save: function(returnToList)\n        {\n            if( ! formChk.check(true) ) return;\n            bff.ajax(ajaxUrl, \$form.serialize(), function(data,errors){\n                if(data && data.success) {\n                    bff.success('Данные успешно сохранены');\n                    if(returnToList || ! id) {\n                        jSiteCountersFormManager.action('cancel');\n                        jSiteCountersList.refresh( ! id);\n                    }\n                }\n            }, \$progress);\n        },\n        onShow: function ()\n        {\n            formChk = new bff.formChecker( \$form );\n        }\n    };\n}());\n</script>";

