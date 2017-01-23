<?php 
$blocksType = $extra["blocksType"];
$prefix = $aData["prefix"];
if( empty($aData["dynprops"]) ) 
{
    return NULL;
}

$sChildUrl = $this->adminLink($this->act_action);
$sChildUrlAct = "child-form";
$sChildInputIdPrefix = "bffdp";
$sChildTemplate = "form.child";
$sChildTemplatePath = $this->module_dir_tpl;
$dpDescription = function($description = "")
{
    if( !empty($description) ) 
    {
 echo "&nbsp;<span>" . $description . "</span>";
    }

}

;
foreach( $aData["dynprops"] as $d ) 
{
    $name = $prefix . "[" . $d[$this->ownerColumn] . "][" . $d["id"] . "]";
    $name_child = $prefix . "[" . $d[$this->ownerColumn] . "]";
    $extra = "dyntype=\"" . $d["type"] . "\"";
    $title = $d["title"];
    $blockClass = "dynprop-owner-" . $d[$this->ownerColumn] . " dynprop-df-" . $d["data_field"] . " dynprop-id-" . $d["id"];
    if( $d["req"] ) 
    {
 $blockClass .= " required";
 $title .= "<span class=\"required-mark\">*</span>";
 switch( $d["type"] ) 
 {
 case bff\db\Dynprops::typeSelect:
     $blockClass .= " check-select";
 }
    }

    foreach( $this->extraSettings() as $k => $v ) 
    {
 if( $v["input"] == "checkbox" && !empty($d[$k]) ) 
 {
 $blockClass .= " extra-sett-" . $k;
 }

    }
    switch( $blocksType ) 
    {
 case "div":
 echo "<div class=\"" . $blockClass . "\"><span class=\"field-title\">" . $title . ":</span>";
 break;
 case "inline":
 echo "<div class=\"left " . $blockClass . "\"><span class=\"field-title\">" . $title . ":</span>";
 break;
 case "table":
 echo "<tr class=\"" . $blockClass . "\"><td class=\"row1 field-title\">" . $title . ":</td><td>";
 break;
    }
    switch( $d["type"] ) 
    {
 case bff\db\Dynprops::typeRadioGroup:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 foreach( $d["multi"] as $dm ) 
 {
     if( 0 < $dm["value"] ) 
     {
  echo "<label class=\"radio" . (!empty($d["group_one_row"]) ? " inline" : "") . "\"><input type=\"radio\" name=\"" . $name . "\" " . $extra . " value=\"" . $dm["value"] . "\" " . ($value == $dm["value"] ? "checked=\"checked\"" : "") . " />" . $dm["name"] . "</label>";
     }

 }
 break;
 case bff\db\Dynprops::typeRadioYesNo:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 echo "<label class=\"radio inline\"><input type=\"radio\" name=\"" . $name . "\" " . $extra . " value=\"2\" " . ($value == 2 ? "checked=\"checked\"" : "") . " />" . $this->langText["yes"] . "</label>&nbsp;       <label class=\"radio inline\"><input type=\"radio\" name=\"" . $name . "\" " . $extra . " value=\"1\" " . ($value == 1 ? "checked=\"checked\"" : "") . " />" . $this->langText["no"] . "</label>";
 break;
 case bff\db\Dynprops::typeCheckboxGroup:
 $value = isset($d["value"]) && $d["value"] ? explode(";", $d["value"]) : explode(";", $d["default_value"]);
 foreach( $d["multi"] as $dm ) 
 {
     echo "<label class=\"checkbox" . (!empty($d["group_one_row"]) ? " inline" : "") . "\"><input type=\"checkbox\" name=\"" . $name . "[]\" " . $extra . " " . (in_array($dm["value"], $value) ? "checked=\"checked\"" : "") . " value=\"" . $dm["value"] . "\" />" . $dm["name"] . "</label>";
 }
 break;
 case bff\db\Dynprops::typeCheckbox:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 echo "<label class=\"checkbox\"><input type=\"hidden\" name=\"" . $name . "\" value=\"0\" /><input type=\"checkbox\" name=\"" . $name . "\" " . $extra . " value=\"1\" " . ($value ? "checked=\"checked\"" : "") . " />" . $this->langText["yes"] . "</label>";
 break;
 case bff\db\Dynprops::typeSelect:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 if( $d["parent"] ) 
 {
     echo "   <div class=\"left\" style=\"margin-right: 10px;\">       <select";
     echo " name=\"" . $name . "\" " . $extra . " onchange=\"bffDynpropsParents.select(" . $d["id"] . ", this.value, '" . $name_child . "');\"";
     echo ">       ";
     foreach( $d["multi"] as $dm ) 
     {
  echo "<option value=\"" . $dm["value"] . "\"" . ($value == $dm["value"] ? " selected=\"selected\"" : "") . ">" . $dm["name"] . "</option>";
     }
     echo "       </select>   </div>   <div class=\"left field-title\" style=\"";
     if( empty($value) ) 
     {
  echo "display: none;";
     }

     echo "\">     ";
     echo $d["child_title"];
     echo ":     <span id=\"";
     echo $sChildInputIdPrefix . $d["id"];
     echo "_child\">     ";
     if( !empty($value) && isset($aData["children"][$d["id"]]) ) 
     {
  echo $this->formChild($aData["children"][$d["id"]], array( "name" => $name_child, "class" => $d["req"] ? " req" : "" ), false, $sChildTemplate, $sChildTemplatePath);
     }

     echo "     </span>   </div>   <div class=\"clear\"></div>";
 }
 else
 {
     echo "     <select name=\"";
     echo $name;
     echo "\" ";
     echo $extra;
     echo ">     ";
     foreach( $d["multi"] as $dm ) 
     {
  echo "<option value=\"" . $dm["value"] . "\"" . ($value == $dm["value"] ? " selected=\"selected\"" : "") . ">" . $dm["name"] . "</option>";
     }
     echo "     </select> ";
     $dpDescription($d["description"]);
 }

 break;
 case bff\db\Dynprops::typeInputText:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 echo "<input type=\"text\" name=\"" . $name . "\" " . $extra . " class=\"stretch\" value=\"" . $value . "\" />";
 break;
 case bff\db\Dynprops::typeTextarea:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 echo "<textarea name=\"" . $name . "\" " . $extra . " class=\"stretch\">" . $value . "</textarea>";
 break;
 case bff\db\Dynprops::typeNumber:
 $value = isset($d["value"]) ? $d["value"] : $d["default_value"];
 echo "<input type=\"text\" name=\"" . $name . "\" " . $extra . " value=\"" . $value . "\" />";
 if( $d["parent"] && isset($aData["children"][$d["id"]]) ) 
 {
     echo "<span style=\"margin-left: 10px;\">";
     echo $this->formChild($aData["children"][$d["id"]], array( "name" => $name_child ), false, $sChildTemplate, $sChildTemplatePath);
     echo "</span>";
 }
 else
 {
     $dpDescription($d["description"]);
 }

 break;
 case bff\db\Dynprops::typeRange:
 $value = isset($d["value"]) && $d["value"] ? $d["value"] : $d["default_value"];
 echo "<select name=\"" . $name . "\" " . $extra . ">";
 if( !empty($value) && !intval($value) ) 
 {
     echo "<option value=\"0\">" . $value . "</option>";
 }

 if( $d["start"] <= $d["end"] ) 
 {
     $i = $d["start"];
     while( $i <= $d["end"] ) 
     {
  echo "<option value=\"" . $i . "\"" . ($value == $i ? " selected=\"selected\"" : "") . ">" . $i . "</option>";
  $i += $d["step"];
     }
 }
 else
 {
     $i = $d["start"];
     while( $d["end"] <= $i ) 
     {
  echo "<option value=\"" . $i . "\"" . ($value == $i ? " selected=\"selected\"" : "") . ">" . $i . "</option>";
  $i -= $d["step"];
     }
 }

 echo "</select>";
 if( $d["parent"] && isset($aData["children"][$d["id"]]) ) 
 {
     echo "<span style=\"margin-left: 10px;\">";
     echo $this->formChild($aData["children"][$d["id"]], array( "name" => $name_child ), false, $sChildTemplate, $sChildTemplatePath);
     echo "</span>";
 }
 else
 {
     $dpDescription($d["description"]);
 }

 switch( $blocksType ) 
 {
     case "div":
  echo "</div>";
  break;
     case "inline":
  echo "</div>";
  break;
     case "table":
  echo "</tr>";
  break;
 }
    }
}
echo "<script type=\"text/javascript\">var bffDynpropsParents = (function(){var cache = {}, prefixID = '";
echo $sChildInputIdPrefix;
echo "';function view(data, id){var \$inp = \$('#'+prefixID+id+'_child').html( ( data.form ? data.form : '')).parent(); if(data.form!='') { \$inp.show(); } else { \$inp.hide(); }    }    return { select: function(id, val, prefix) { var key = id+'-'+val; if(!intval(val)) cache[key] = {form:''}; if(cache.hasOwnProperty(key)) {view( cache[key], id ); } else {     bff.ajax('";
echo $sChildUrl;
echo "', {act:'";
echo $sChildUrlAct;
echo "', dp_id: id, dp_value:val, name_prefix: prefix, search:false}, function(data){  if(data) {  view( (cache[key] = data), id );  }     }); } }    }}());</script>";

