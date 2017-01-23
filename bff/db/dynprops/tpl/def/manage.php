<?php 
$hasData = !empty($aData["data"]);
echo "<div class=\"actionBar\">  <ul class=\"breadcrumb\"> ";
if( $aData["owner"]["parent"] != 0 ) 
{
  echo " <li class=\"inline\"><a href=\"";
  echo $aData["url_listing"] . $aData["owner"]["parent"]["id"];
  echo "\">";
  echo $aData["owner"]["parent"]["title"];
  echo "</a><span class=\"divider\">&rarr;</span></li> ";
}

echo " <li class=\"inline\"><a href=\"";
echo $aData["url_listing"] . $aData["owner"]["id"];
echo "\" class=\"";
if( $aData["edit"] && 0 < $aData["owner_from"] ) 
{
  echo " bold text-error clr-error";
}

echo "\">";
echo $aData["owner"]["title"];
echo "</a><span class=\"divider\">&rarr;</span></li> <li class=\"active inline\">управление</li>  </ul></div><script type=\"text/javascript\">";
js::start();
echo "var bffDynpropsMain;\$(function(){  bffDynpropsMain = bffDynprops.init(false, { edit: ";
echo $aData["edit"] ? "true" : "false";
echo ", langs: ";
echo $this->langs ? func::php2js($this->langs) : "false";
echo ", lang_default: '";
echo LNG;
echo "', data: ";
echo $hasData ? func::php2js($aData["data"]) : "null";
echo ", types_allowed: [";
echo join(",", $this->typesAllowed);
echo "], types_allowed_parent: [";
echo join(",", $this->typesAllowedParent);
echo "], search_ranges: ";
echo $this->searchRanges ? "true" : "false";
echo " }, { url_action_owner: '";
echo $aData["url_action_owner"];
echo "', date: { dateFormat: \"";
echo bff\db\Dynprops::datePatternJS;
echo "\", onSelect: function() { \$(\"#\"+this.id+\"_timestamp\").val((new Date(\$(this).datepicker('getDate')).getTime())/1000); } } }  );});";
js::stop();
echo "</script><form method=\"post\" id=\"bffDynpropsMainForm\" action=\"\">  <input type=\"hidden\" name=\"dynprop[multi_deleted]\" value=\"\" class=\"multi-deleted\" />  <input type=\"hidden\" name=\"dynprop[multi_added]\" value=\"\" class=\"multi-added\" />  <table class=\"admtbl tbledit dynprop-block";
if( $this->langs && 1 < sizeof($this->langs) ) 
{
  echo " more-langs";
}

echo "\"> <tr> <td class=\"row1\" width=\"120\"><span class=\"field-title\">Тип</span>:</td> <td class=\"row2\"> <select class=\"dynprop-type-select input-xlarge\" name=\"dynprop[type]\"></select> <label class=\"checkbox inline\" style=\"display:none; margin-left: 10px;\"><input class=\"dynprop-parent\" type=\"checkbox\" name=\"dynprop[parent]\" /> с прикреплением</label> </td> </tr> ";
if( $this->langs ) 
{
  echo " <tr> <td class=\"row1\"><span class=\"field-title\">Название</span>:</td> <td class=\"row2\"> ";
  echo $this->locale->buildForm($aData, "dp-manage-title", "" . " <input class=\"dynprop-title input-xlarge lang-field\" type=\"text\" maxlength=\"150\" name=\"dynprop[title][<?= \$key ?>]\" value=\"<?= ( ! empty(\$aData['data']['title_'.\$key]) ? HTML::escape(\$aData['data']['title_'.\$key]) : '') ?>\" /> ", array( "table" => false ));
  echo " </td> </tr> <tr> <td class=\"row1\"><span class=\"field-title\">Уточнение к названию</span>:</td> <td class=\"row2\"> ";
  foreach( $this->langs as $k => $v ) 
  {
 echo " <input class=\"dynprop-description input-xlarge lang-field j-lang-form j-lang-form-";
 echo $k;
 if( $k != LNG ) 
 {
 echo " displaynone";
 }

 echo "\" type=\"text\" maxlength=\"150\" name=\"dynprop[description][";
 echo $k;
 echo "]\" value=\"";
 echo $hasData ? HTML::escape($aData["data"]["description_" . $k]) : "";
 echo "\" /> ";
  }
  echo " </td> </tr> ";
}
else
{
  echo " <tr> <td class=\"row1\"><span class=\"field-title\">Название</span>:</td> <td class=\"row2\"><input class=\"dynprop-title input-xlarge\" type=\"text\" maxlength=\"150\" name=\"dynprop[title]\" value=\"";
  echo $hasData ? HTML::escape($aData["data"]["title"]) : "";
  echo "\" /></td> </tr> <tr> <td class=\"row1\"><span class=\"field-title\">Уточнение к названию</span>:</td> <td class=\"row2\"><input class=\"dynprop-description input-xlarge\" type=\"text\" maxlength=\"150\" name=\"dynprop[description]\" value=\"";
  echo $hasData ? HTML::escape($aData["data"]["description"]) : "";
  echo "\" /></td> </tr> ";
}

echo " <tbody class=\"dynprop-parent-block\" style=\"display:none;\"> ";
if( $this->langs ) 
{
  echo " <tr> <td class=\"row1 field-title\">Название:<br /><span class=\"desc\">(для прикрепления)</span></td> <td class=\"row2\"> ";
  foreach( $this->langs as $k => $v ) 
  {
 echo " <input class=\"dynprop-child-title input-xlarge lang-field j-lang-form j-lang-form-";
 echo $k;
 if( $k != LNG ) 
 {
 echo " displaynone";
 }

 echo "\" type=\"text\" maxlength=\"150\" name=\"dynprop[child_title][";
 echo $k;
 echo "]\" value=\"";
 echo $hasData && !empty($aData["data"]["child_title"]) ? HTML::escape($aData["data"]["child_title"][$k]) : "";
 echo "\" /> ";
  }
  echo " </td> </tr> ";
}
else
{
  echo " <tr> <td class=\"row1 field-title\">Название:<br /><span class=\"desc\">(для прикрепления)</span></td> <td class=\"row2\"><input class=\"dynprop-child-title input-xlarge\" type=\"text\" maxlength=\"150\" name=\"dynprop[child_title]\" value=\"";
  echo $hasData && !empty($aData["data"]["child_title"]) ? HTML::escape($aData["data"]["child_title"]) : "";
  echo "\" /></td> </tr> ";
}

echo " ";
if( $this->langs ) 
{
  echo " <tr> <td class=\"row1 field-title\">Значение<br />по-умолчанию:<br/><span class=\"desc\">(для прикрепления)</span></td> <td class=\"row2\"> ";
  foreach( $this->langs as $k => $v ) 
  {
 echo " <input class=\"dynprop-child-default input-xlarge lang-field j-lang-form j-lang-form-";
 echo $k;
 if( $k != LNG ) 
 {
 echo " displaynone";
 }

 echo "\" type=\"text\" maxlength=\"150\" name=\"dynprop[child_default][";
 echo $k;
 echo "]\" value=\"";
 echo $hasData && !empty($aData["data"]["child_default"]) ? HTML::escape($aData["data"]["child_default"][$k]) : "";
 echo "\" /> ";
  }
  echo " </td> </tr> ";
}
else
{
  echo " <tr> <td class=\"row1 field-title\">Значение<br/>по-умолчанию:<br/><span class=\"desc\">для прикрепления</span></td> <td class=\"row2\"><input class=\"dynprop-child-default input-xlarge\" type=\"text\" maxlength=\"150\" name=\"dynprop[child_default]\" value=\"";
  echo $hasData && !empty($aData["data"]["child_default"]) ? HTML::escape($aData["data"]["child_default"]) : "";
  echo "\" /></td> </tr> ";
}

echo " </tbody> <tr> <td class=\"row1\"><span class=\"field-title\">Значение<br/>по-умолчанию</span>:<br/><a href=\"#\" class=\"ajax cancel multi-default-clear\">сбросить</a></td> <td class=\"row2\"> <div class=\"dynprop-params\"></div> </td> </tr> <tr> <td colspan=\"2\"> <hr class=\"cut\" /> </td> </tr> <tr";
if( !$this->cacheKey ) 
{
  echo " style=\"display: none;\"";
}

echo "> <td class=\"row1\"><span class=\"field-title\">Кеш ключ</span>:</td> <td class=\"row2\"> <input type=\"text\" name=\"dynprop[cache_key]\" class=\"dynprop-cache-key input-xlarge\" value=\"";
echo $hasData ? HTML::escape($aData["data"]["cache_key"]) : "";
echo "\" maxlength=\"150\" /> </td> </tr> <tr> <td class=\"row1\"></td> <td class=\"row2\"> <label class=\"checkbox\"><input type=\"checkbox\" name=\"dynprop[req]\" class=\"dynprop-req\" />обязательное <span class=\"desc\">(для ввода)</span></label> <div class=\"dynprop-search-block hidden\"> <label class=\"checkbox\"><input type=\"checkbox\" name=\"dynprop[is_search]\" class=\"dynprop-search\" />поле поиска</label> ";
if( $this->searchHiddens ) 
{
  echo "<label class=\"checkbox\" style=\"margin-left: 10px;\"><input type=\"checkbox\" name=\"dynprop[search_hidden]\" class=\"dynprop-search-hidden\" />скрытое по-умолчанию</label>";
}

echo " </div> ";
foreach( $this->extraSettings() as $k => $v ) 
{
  switch( $v["input"] ) 
  {
 case "checkbox":
 echo "<label class=\"checkbox\"><input type=\"checkbox\" name=\"dynprop[";
 echo $k;
 echo "]\" ";
 if( !empty($aData["data"][$k]) ) 
 {
 echo " checked=\"checked\"";
 }

 echo " />";
 echo $v["title"];
 echo "</label>";
  }
}
echo " </td> </tr> <tr class=\"footer\"> <td class=\"row1\" colspan=\"2\"> <input type=\"submit\" class=\"btn btn-success button submit\" value=\"Сохранить\" /> <input type=\"button\" class=\"btn button cancel \" value=\"Отмена\" onclick=\"bff.redirect('";
echo $aData["url_listing"] . ($aData["edit"] && 0 < $aData["owner_from"] ? $aData["owner_from"] : $aData["owner"]["id"]);
echo "');\" /> </td> </tr>  </table></form>";

