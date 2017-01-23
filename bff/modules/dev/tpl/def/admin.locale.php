<?php 
tplAdmin::adminPageSettings(array( "icon" => false ));
echo "\r\n<script type=\"text/javascript\">\r\n//<![CDATA[\r\nvar jDevLocale = (function(){\r\n\r\n    var curTab = '', \$tabs;\r\n\r\n    \$(function(){\r\n        \$tabs = \$('.dev-locale-tab');\r\n        onTab('";
echo $aData["tab"];
echo "');\r\n    });\r\n\r\n    function onTab( tab )\r\n    {\r\n        if(curTab == tab) return;\r\n        \$tabs.hide();\r\n        var \$tab = \$tabs.filter('#dev-locale-tab-'+tab).show();\r\n        \$('#tabs span.tab').removeClass('tab-active');\r\n        \$('#tabs span[rel=\"'+tab+'\"]').addClass('tab-active');\r\n        curTab = document.getElementById('tab').value = tab;\r\n        if(bff.h) window.history.pushState({}, document.title, '";
echo $this->adminLink(bff::$event);
echo "&tab='+curTab);\r\n    }\r\n\r\n    return {\r\n        onTab: onTab\r\n    }\r\n}());\r\n//]]>\r\n</script>\r\n\r\n<input type=\"hidden\" name=\"tab\" id=\"tab\" value=\"\" />\r\n\r\n<div class=\"tabsBar\" id=\"tabs\">\r\n    ";
foreach( $tabs as $k => $v ) 
{
    echo "        <span class=\"tab\" onclick=\"jDevLocale.onTab('";
    echo $k;
    echo "');\" rel=\"";
    echo $k;
    echo "\">";
    echo $v["t"];
    echo "</span>\r\n    ";
}
echo "</div>\r\n\r\n<!-- translate -->\r\n<div id=\"dev-locale-tab-translate\" class=\"dev-locale-tab\" style=\"display: none;\">\r\n    <form method=\"post\" action=\"\" name=\"gettextActionsForm\" id=\"j-dev-gettext-actions-form\" enctype=\"multipart/form-data\">\r\n        <input type=\"hidden\" name=\"act\" value=\"\" />\r\n        <p class=\"text-info\">Файлы локализации <strong class=\"label\">*.PO/*.POT</strong> - текстовый формат файлов, редактируемый с помощью редактора:<p>\r\n        <table class=\"admtbl tbledit\">\r\n        <tr>\r\n            <td class=\"row1 field-title\" style=\"vertical-align:middle;\">\r\n                <input type=\"submit\" class=\"btn btn-small button submit\" onclick=\"doLangAction('tr-pot', this);\" value=\"1. Пересобрать\" /> <span class=\"desc\"> - последнее обновление ";
echo tpl::date_format2($nPotLastModify);
echo "</span>\r\n            </td>\r\n        </tr>\r\n        <tr>\r\n            <td class=\"row1 field-title\" style=\"vertical-align:middle;\">&nbsp;&nbsp;2. Скачать:\r\n            ";
$i = sizeof($languages);
foreach( $languages as $k => $v ) 
{
    echo "                  <a class=\"lng-";
    echo $k;
    echo "\" style=\"padding-left: 20px;\" href=\"";
    echo $this->adminLink("locale_data&act=tr-download&type=pot&lang=" . $k);
    echo "\">";
    echo $v["title"];
    echo "</a>";
    if( --$i ) 
    {
        echo ", ";
    }

    echo "            ";
}
echo "            </td>\r\n        </tr>\r\n        <tr>\r\n            <td class=\"row1 field-title\" style=\"vertical-align:middle;\">\r\n                <span>&nbsp;&nbsp;3. <a onclick=\"return onLangUploads();\" href=\"#\" class=\"ajax\">Загрузить дополненный вариант</a></span>\r\n                <div id=\"uploads\" style=\"padding-left:17px; padding-top:10px; display: none;\">\r\n                    ";
foreach( $languages as $k => $v ) 
{
    echo "                        <div style=\"margin-bottom: 5px;\"><span class=\"lng-";
    echo $k;
    echo "\" style=\"padding-left: 20px;\">";
    echo $v["title"];
    echo "</span>:&nbsp;<input type=\"file\" name=\"po_";
    echo $k;
    echo "\" onchange=\"onLangUploadsSelectFile(this, '";
    echo $k;
    echo ".po');\" /></div>\r\n                    ";
}
echo "                    <input type=\"submit\" class=\"btn btn-small button submit\" onclick=\"doLangAction('tr-upload', this);\" value=\"Загрузить\" />\r\n                </div>\r\n            </td>\r\n        </tr>\r\n        </table>\r\n\r\n        <hr />\r\n\r\n        <p class=\"text-info\">Файлы локализации <strong class=\"label\">*.MO</strong> - сжатый формат файлов, используемый расширением gettext:</p>\r\n        <table class=\"admtbl tbledit\">\r\n        <tr>\r\n            <td class=\"row1 field-title\" style=\"vertical-align:middle;\">\r\n                <input type=\"submit\" class=\"btn btn-small button submit\" onclick=\"doLangAction('tr-mo', this);\" value=\"1. Пересобрать\" /> <span class=\"desc\"> - последнее обновление ";
echo tpl::date_format2($nMoLastModify);
echo "</span>\r\n            </td>\r\n        </tr>\r\n        </table>\r\n    </form>\r\n\r\n    <script type=\"text/javascript\">\r\n    //<![CDATA[\r\n        function doLangAction(action, btn)\r\n        {\r\n            document.forms.gettextActionsForm.act.value = action;\r\n            btn.form.submit();\r\n            btn.disabled = true;\r\n        }\r\n\r\n        function onLangUploads()\r\n        {\r\n            \$('#uploads').toggle();\r\n            return false;\r\n        }\r\n\r\n        function checkLangUploadsExt(f) {\r\n            try\r\n            {\r\n                 if(!f) return true;\r\n                 var ext = f.split(/\\.+/).pop().toLowerCase();\r\n                 return (ext == 'po' || ext == 'pot');\r\n            } catch(e) { return true; }\r\n        }\r\n\r\n        function onLangUploadsSelectFile(input, need)\r\n        {\r\n            if( ! checkLangUploadsExt(input.value))\r\n            {\r\n                alert('Необходимо указать файл с расширением *.po');\r\n                return false;\r\n            }\r\n            return true;\r\n        }\r\n    //]]>\r\n    </script>\r\n</div>\r\n\r\n<!-- db -->\r\n<div id=\"dev-locale-tab-db\" class=\"dev-locale-tab\" style=\"display: none;\">\r\n    <form method=\"post\" action=\"\" id=\"j-dev-locale-db-form-copy\">\r\n\r\n    <p class=\"text-info\">\r\n        Дублирование данных локализации:\r\n    </p>\r\n    <p>\r\n        <select name=\"from\" class=\"input-medium j-from\">";
echo HTML::selectOptions($languages, $language_default, "", "key", "title");
echo "</select>\r\n        <i class=\"icon icon-arrow-right\"></i>\r\n        <select name=\"to\" class=\"input-medium j-to\">";
echo HTML::selectOptions($languages_to, 0, "", "key", "title");
echo "</select>\r\n    </p>\r\n    <input type=\"button\" class=\"btn btn-small\" value=\"выполнить\" onclick=\"doLocaleDataCopy(\$(this));\" />\r\n\r\n    <a href=\"#\" onclick=\"\$('#dev-locale-tab-db-tables').toggleClass('hide'); return false;\" class=\"ajax desc\" style=\"margin-left: 10px;\">показать список таблиц локализации</a>\r\n    <div id=\"dev-locale-tab-db-tables\" class=\"hide well well-small\" style=\"margin:10px; width: 96%;\">\r\n        <table class=\"table table-condensed table-hover admtbl tblhover\">\r\n            <thead>\r\n                <tr class=\"header\">\r\n                    <th class=\"left\">Таблица</th>\r\n                    <th class=\"left\" width=\"65\">Тип</th>\r\n                    <th class=\"left\" width=\"400\">Поля</th>\r\n                    <th class=\"left\">Состояние</th>\r\n                </tr>\r\n            </thead>\r\n            <tbody>\r\n            ";
foreach( $db_tables as $k => $v ) 
{
    echo "                <tr>\r\n                    <td class=\"left\">";
    echo $k;
    echo "</td>\r\n                    <td class=\"left\">";
    echo $db_tables_types[$v["type"]];
    echo "</td>\r\n                    <td class=\"left\">";
    echo join(", ", array_keys($v["fields"]));
    echo "</td>\r\n                    <td class=\"left\">";
    foreach( $v["state"] as $sk ) 
    {
        echo "<a class=\"but lng-";
        echo $sk;
        echo "\" href=\"#\" onclick=\"return false;\"></a>";
    }
    echo "</td>\r\n                </tr>\r\n            ";
}
echo "            </tbody>\r\n        </table>\r\n    </div>\r\n    </form>\r\n\r\n    <hr />\r\n\r\n    <form method=\"post\" action=\"\" id=\"j-dev-locale-db-form-remove\">\r\n        <p class=\"text-error\">\r\n            Удаление данных локализации:\r\n        </p>\r\n        <p>\r\n            <select name=\"lang\" class=\"input-medium j-from\">";
echo HTML::selectOptions($languages_remove, 0, "", "key", "title");
echo "</select>\r\n        </p>\r\n        <input type=\"button\" class=\"btn btn-small\" value=\"выполнить\" onclick=\"doLocaleDataRemove(\$(this));\" />\r\n    </form>\r\n\r\n    <script type=\"text/javascript\">\r\n    //<![CDATA[\r\n        function doLocaleDataCopy(\$btn)\r\n        {\r\n            if ( ! bff.confirm('sure')) return;\r\n            var \$form = \$('#j-dev-locale-db-form-copy');\r\n            bff.ajax('";
echo $this->adminLink(bff::$event . "&act=db-data-copy");
echo "', \$form.serialize(), function(data){\r\n                if(data && data.success) {\r\n                    bff.success('Дублирование было успешно выполнено');\r\n                    setTimeout(function(){ document.location.reload(); }, 1000);\r\n                }\r\n            }, function(p){\r\n                \$btn.val(p?'подождите...':'выполнить').prop('disabled', p);\r\n            });\r\n        }\r\n        function doLocaleDataRemove(\$btn)\r\n        {\r\n            if ( ! bff.confirm('sure')) return;\r\n            var \$form = \$('#j-dev-locale-db-form-remove');\r\n            bff.ajax('";
echo $this->adminLink(bff::$event . "&act=db-data-remove");
echo "', \$form.serialize(), function(data){\r\n                if(data && data.success) {\r\n                    bff.success('Удаление было успешно выполнено');\r\n                    setTimeout(function(){ document.location.reload(); }, 1000);\r\n                }\r\n            }, function(p){\r\n                \$btn.val(p?'подождите...':'выполнить').prop('disabled', p);\r\n            });\r\n        }\r\n    //]]>\r\n    </script>\r\n</div>";

