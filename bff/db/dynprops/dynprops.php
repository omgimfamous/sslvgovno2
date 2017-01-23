<?php 
namespace bff\db;
class Dynprops extends \Module
{
    protected $ownerFromArray = false;
    protected $ownerColumn = "cat_id";
    protected $ownerTable = NULL;
    protected $ownerTable_ID = "id";
    protected $ownerTable_PID = "pid";
    protected $ownerTable_Title = "title";
    protected $ownerTableLevels = false;
    protected $ownerTableType = "ns";
    protected $tblDynprops = NULL;
    protected $tblMulti = NULL;
    protected $tblIn = NULL;
    protected $inherit = false;
    public $datafield_prefix = "f";
    public $datafield_int_first = 1;
    public $datafield_int_last = 10;
    public $datafield_text_first = 11;
    public $datafield_text_last = 14;
    public $act_listing = "dynprops_listing";
    public $act_action = "dynprops_action";
    public $typesAllowed = array(  );
    public $typesAllowedParent = array(  );
    public $typesAllowedChild = array( self::typeSelect );
    protected $typesExtra = array( self::typeNumber, self::typeRange, self::typeCheckboxGroup, self::typeRadioGroup );
    protected $cache_method = false;
    public $searchHiddens = false;
    public $searchRanges = false;
    public $cacheKey = true;
    protected $extraSettings = array(  );
    public $langs = false;
    protected $langFields = array( "title" => TYPE_STR, "description" => TYPE_STR );
    public $langText = array( "yes" => "Да", "no" => "Нет", "all" => "Все", "select" => "Выбрать" );

    const typeInputText = 1;
    const typeTextarea = 2;
    const typeWysiwyg = 3;
    const typeRadioYesNo = 4;
    const typeCheckbox = 5;
    const typeSelect = 6;
    const typeSelectMulti = 7;
    const typeRadioGroup = 8;
    const typeCheckboxGroup = 9;
    const typeNumber = 10;
    const typeRange = 11;
    const typeCountry = 12;
    const typeState = 13;
    const typeDate = 14;
    const datePatternJS = "yy-mm-dd";
    const datePatternPHP = "Y-m-d";
    const OWNER_TABLE_TYPE_NESTEDSETS = 1;
    const OWNER_TABLE_TYPE_ADJACENCYLIST = 2;
    const OWNER_TABLE_TYPE_ARRAY = 3;

    public function __construct($ownerColumn, $pf10cc, $d92ac5637b95971a, $bee790907acda3eb, $we280cb6 = false, $Daf197cd = false)
    {

  $this->ownerColumn = $ownerColumn;
  $this->ownerTable = $pf10cc;
  $this->tblDynprops = $d92ac5637b95971a;
  $this->tblMulti = $bee790907acda3eb;
  $this->inherit = $we280cb6;
  if( $this->isInheritParticular() ) 
  {
 $this->tblIn = $Daf197cd;
  }

  if( is_array($pf10cc) && !$we280cb6 ) 
  {
 $this->ownerFromArray = true;
  }

  $this->initModuleAsComponent("Dynprops", PATH_CORE . "db" . DS . "dynprops");
    }
	
	public function _array_transparent($aData, $sByKey, $bOneInRows = false)
    {
        if (empty($aData) || !is_array($aData)) {
            return array();
        }

        $aDataResult = array();
        $cnt = count($aData);
        for ($i = 0; $i < $cnt; $i++) {
            if ($bOneInRows) {
                $aDataResult[$aData[$i][$sByKey]] = $aData[$i];
            } else {
                $aDataResult[$aData[$i][$sByKey]][] = $aData[$i];
            }
        }

        return $aDataResult;
    }

    public function extraSettings($gac0ab6d2a7207 = false)
    {

  if( is_array($gac0ab6d2a7207) ) 
  {
 static $ebed641049082f7;
 foreach( $gac0ab6d2a7207 as $A0c027a94ce180 => $A0c027a94ce181 ) 
 {
    if( empty($A0c027a94ce180) || !is_string($A0c027a94ce180) || empty($A0c027a94ce181) ) 
    {
   continue;
    }

    $this->input->clean_array($A0c027a94ce181, array( "title" => TYPE_NOTAGS, "input" => TYPE_NOTAGS ));
    if( !isset($ebed641049082f7[$A0c027a94ce181["input"]]) ) 
    {
   continue;
    }

    $A0c027a94ce181["type"] = $ebed641049082f7[$A0c027a94ce181["input"]];
    $this->extraSettings[$A0c027a94ce180] = $A0c027a94ce181;
 }
  }
  else
  {
 return $this->extraSettings;
  }

    }

    public function listing()
    {

  if( !$this->haveAccessTo("dynprops", $this->module_name) ) 
  {
 return $this->showAccessDenied();
  }

  $A0c027a94ce182 = $this->input->get("owner", TYPE_UINT);
  if( $this->ownerFromArray ) 
  {
 $A0c027a94ce183 = array( "owner_id" => $this->ownerTable["id"], "owner_title" => $this->ownerTable["title"], "owner_parent" => 0 );
  }
  else
  {
 $A0c027a94ce183 = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as owner_id, O." . $this->ownerTable_Title . " as owner_title, " . ($this->inherit ? "O." . $this->ownerTable_PID : "0") . " as owner_parent  FROM " . $this->ownerTable . " O  WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce182 ));
 if( $A0c027a94ce183["owner_parent"] != 0 ) 
 {
    $A0c027a94ce183["owner_parent"] = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title  FROM " . $this->ownerTable . " O  WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce183["owner_parent"] ));
 }

  }

  $A0c027a94ce183["dynprops"] = $this->getByOwner($A0c027a94ce182, true, false, false);
  $A0c027a94ce183["url_listing"] = $this->adminLink($this->act_listing);
  $A0c027a94ce183["url_action"] = $this->adminLink($this->act_action);
  $A0c027a94ce183["url_action_owner"] = $this->adminLink($this->act_action . "&owner=" . $A0c027a94ce182 . "&act=");
  \tpl::includeJS(array( "tablednd" ), true);
  return $this->viewPHP($A0c027a94ce183, "listing");
    }

    public function action()
    {

  $A0c027a94ce184 = $this->input->get("owner", TYPE_UINT);
  $A0c027a94ce185 = $this->act_listing . "&owner=" . $A0c027a94ce184;
  switch( $this->input->getpost("act", TYPE_STR) ) 
  {
 case "add":
    $A0c027a94ce186 = array( "data" => array(  ) );
    if(\Request::isPOST() ) 
    {
   $A0c027a94ce186["data"] = $this->input->post("dynprop", TYPE_ARRAY);
   $A0c027a94ce187 = $this->insert($A0c027a94ce186["data"], $A0c027a94ce184);
   if( $A0c027a94ce187 ) 
   {
 $this->adminRedirect(\Errors::SUCCESS, $A0c027a94ce185);
   }

    }

    if( $this->ownerFromArray ) 
    {
   $A0c027a94ce186["owner"] = $this->ownerTable;
    }
    else
    {
   $A0c027a94ce186["owner"] = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title, " . ($this->inherit ? "O." . $this->ownerTable_PID : "0") . " as parent   FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce184 ));
   if( $A0c027a94ce186["owner"]["parent"] != 0 ) 
   {
 $A0c027a94ce186["owner"]["parent"] = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title   FROM " . $this->ownerTable . " O   WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce186["owner"]["parent"] ));
   }

    }

    \tpl::includeJS(array( "dynprops", "tablednd" ), true);
    $A0c027a94ce186["edit"] = false;
    $A0c027a94ce186["url_listing"] = $this->adminLink($this->act_listing . "&owner=");
    $A0c027a94ce186["url_action_owner"] = $this->adminLink($this->act_action . "&owner=" . $A0c027a94ce184 . "&act=");
    return $this->viewPHP($A0c027a94ce186, "manage");
 case "edit":
    $A0c027a94ce188 = $this->input->get("owner_from", TYPE_UINT);
    if( $A0c027a94ce188 ) 
    {
   $A0c027a94ce185 = $this->act_listing . "&owner=" . $A0c027a94ce188;
    }

    $A0c027a94ce189 = $this->input->get("dynprop", TYPE_UINT);
    if( !$A0c027a94ce189 ) 
    {
   $this->adminRedirect(\Errors::IMPOSSIBLE, $A0c027a94ce185);
    }

    $A0c027a94ce186 = array(  );
    if(\Request::isPOST() ) 
    {
   $A0c027a94ce186["data"] = $this->input->post("dynprop", TYPE_ARRAY);
   $A0c027a94ce187 = $this->update($A0c027a94ce186["data"], $A0c027a94ce184, $A0c027a94ce189);
   if( $A0c027a94ce187 ) 
   {
 $this->adminRedirect(\Errors::SUCCESS, $A0c027a94ce185);
   }

    }
    else
    {
   $A0c027a94ce186["data"] = $this->db->one_array("SELECT D.* FROM " . $this->tblDynprops . " D  WHERE D.id = :id AND D." . $this->ownerColumn . " = :ownerid", array( ":id" => $A0c027a94ce189, ":ownerid" => $A0c027a94ce184 ));
   $A0c027a94ce190 =& $A0c027a94ce186["data"];
   if( $this->isMulti($A0c027a94ce190["type"]) ) 
   {
 if( $this->langs ) 
 {
    $A0c027a94ce190["multi"] = $this->db->select("SELECT * FROM " . $this->tblMulti . " WHERE dynprop_id = :id ORDER BY num", array( ":id" => $A0c027a94ce189 ));
    foreach( $A0c027a94ce190["multi"] as $A0c027a94ce191 => $A0c027a94ce192 ) 
    {
 foreach( $this->langs as $A0c027a94ce193 => $A0c027a94ce194 ) 
 {
     $A0c027a94ce190["multi"][$A0c027a94ce191]["name_" . $A0c027a94ce193] = htmlspecialchars($A0c027a94ce192["name_" . $A0c027a94ce193], ENT_QUOTES);
 }
    }
 }
 else
 {
    $A0c027a94ce190["multi"] = $this->db->select("SELECT value, name FROM " . $this->tblMulti . " WHERE dynprop_id = :id ORDER BY num", array( ":id" => $A0c027a94ce189 ));
    foreach( $A0c027a94ce190["multi"] as $A0c027a94ce191 => $A0c027a94ce192 ) 
    {
 $A0c027a94ce190["multi"][$A0c027a94ce191]["name"] = htmlspecialchars($A0c027a94ce192["name"], ENT_QUOTES);
    }
 }

   }

   if( $this->hasExtra($A0c027a94ce190["type"]) || $A0c027a94ce190["parent"] ) 
   {
 $A0c027a94ce195 = unserialize($A0c027a94ce190["extra"]);
 if( $A0c027a94ce195 !== false ) 
 {
    $A0c027a94ce190 = array_merge($A0c027a94ce190, $A0c027a94ce195);
 }

 unset($A0c027a94ce190["extra"]);
   }

    }

    if( empty($A0c027a94ce186["data"]) ) 
    {
   $this->adminRedirect(\Errors::IMPOSSIBLE, $A0c027a94ce185);
    }

    if( $this->ownerFromArray ) 
    {
   $A0c027a94ce186["owner"] = $this->ownerTable;
    }
    else
    {
   $A0c027a94ce186["owner"] = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title, " . ($this->inherit ? "O." . $this->ownerTable_PID : "0") . " as parent   FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce184 ));
   if( $A0c027a94ce186["owner"]["parent"] != 0 ) 
   {
 $A0c027a94ce186["owner"]["parent"] = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title   FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce186["owner"]["parent"] ));
   }

    }

    \tpl::includeJS(array( "dynprops", "tablednd" ), true);
    $A0c027a94ce186["owner_from"] = $A0c027a94ce188;
    $A0c027a94ce186["edit"] = true;
    $A0c027a94ce186["url_listing"] = $this->adminLink($this->act_listing . "&owner=");
    $A0c027a94ce186["url_action_owner"] = $this->adminLink($this->act_action . "&owner=" . $A0c027a94ce184 . "&act=");
    return $this->viewPHP($A0c027a94ce186, "manage");
 case "child":
    $this->input->postm(array( "parent_id" => TYPE_UINT, "parent_value" => TYPE_UINT, "child_act" => TYPE_STR, "id" => TYPE_UINT ), $A0c027a94ce186);
    $A0c027a94ce196 = $A0c027a94ce186["parent_id"];
    $A0c027a94ce197 = $A0c027a94ce186["parent_value"];
    if( !empty($A0c027a94ce186["child_act"]) ) 
    {
   switch( $A0c027a94ce186["child_act"] ) 
   {
 case "save":
    $A0c027a94ce198 = $this->input->post("dynprop", TYPE_ARRAY);
    if( $A0c027a94ce186["id"] ) 
    {
 $A0c027a94ce187 = $this->update($A0c027a94ce198, $A0c027a94ce184, $A0c027a94ce186["id"]);
    }
    else
    {
 $A0c027a94ce187 = $this->insert($A0c027a94ce198, $A0c027a94ce184, array( "id" => $A0c027a94ce196, "value" => $A0c027a94ce197 ));
    }

    if( $A0c027a94ce187 ) 
    {
 $this->ajaxResponse(\Errors::SUCCESS);
    }

    break;
 case "del":
    $A0c027a94ce187 = $this->del($A0c027a94ce186["id"], $A0c027a94ce184, false, true);
    if( $A0c027a94ce187 ) 
    {
 $this->ajaxResponse(\Errors::SUCCESS);
    }

   }
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }
    else
    {
   $A0c027a94ce190 = $this->db->one_array("SELECT * FROM " . $this->tblDynprops . "    WHERE parent_id = :pid AND parent_value = :pvalue", array( ":pid" => $A0c027a94ce196, ":pvalue" => $A0c027a94ce197 ));
   if( !empty($A0c027a94ce190) ) 
   {
 $A0c027a94ce186["id"] = $A0c027a94ce190["id"];
 if( $this->isMulti($A0c027a94ce190["type"]) ) 
 {
    $A0c027a94ce190["multi"] = $this->db->select("SELECT * FROM " . $this->tblMulti . " WHERE dynprop_id = :id ORDER BY num", array( ":id" => $A0c027a94ce190["id"] ));
 }

 if( $this->hasExtra($A0c027a94ce190["type"]) || $A0c027a94ce190["parent"] ) 
 {
    $A0c027a94ce195 = unserialize($A0c027a94ce190["extra"]);
    if( $A0c027a94ce195 !== false ) 
    {
 $A0c027a94ce190 = array_merge($A0c027a94ce190, $A0c027a94ce195);
    }

    unset($A0c027a94ce190["extra"]);
 }

   }

   $A0c027a94ce186["data"] = $A0c027a94ce190;
   $A0c027a94ce186["edit"] = !empty($A0c027a94ce186["id"]);
    }

    $this->typesAllowed = $this->typesAllowedChild;
    $this->ajaxResponse(array( "form" => $this->viewPHP($A0c027a94ce186, "manage.child") ));
    break;
 case "child-form":
    $A0c027a94ce199 = $this->input->postm(array( "dp_id" => TYPE_UINT, "dp_value" => TYPE_UINT, "name_prefix" => TYPE_STR, "search" => TYPE_BOOL ));
    $A0c027a94ce200["form"] = $this->formChildByParentIDValue($A0c027a94ce199["dp_id"], $A0c027a94ce199["dp_value"], array( "name" => $A0c027a94ce199["name_prefix"] ), $A0c027a94ce199["search"]);
    $this->ajaxResponseForm($A0c027a94ce200);
    break;
 case "inherit-list":
    if( !$A0c027a94ce184 || !$this->isInheritParticular() ) 
    {
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    $A0c027a94ce186 = $this->db->one_array("SELECT O." . $this->ownerTable_ID . " as owner_id, O." . $this->ownerTable_Title . " as owner_title, " . ($this->inherit ? "O." . $this->ownerTable_PID : "0") . " as parent FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $A0c027a94ce184 ));
    if( $A0c027a94ce186["parent"] == 0 ) 
    {
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    $A0c027a94ce201 = $this->getOwnerParentsID($A0c027a94ce184);
    if( empty($A0c027a94ce201) ) 
    {
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    $A0c027a94ce186["dynprops"] = $this->db->select("SELECT D.id, D.title" . ($this->langs ? "_" . LNG : "") . " as title, D." . $this->ownerColumn . ", D.type, D.enabled, D.is_search, I.data_field, I2." . $this->ownerColumn . " as inherited   FROM " . $this->tblDynprops . " D,  " . $this->tblIn . " I  LEFT JOIN " . $this->tblIn . " I2 ON I2.dynprop_id = I.dynprop_id AND I2." . $this->ownerColumn . " = :id   WHERE " . $this->db->prepareIN("I." . $this->ownerColumn, $A0c027a94ce201) . " AND I.dynprop_id = D.id AND D.parent_id = 0   GROUP BY D.id   ORDER BY I.num", array( ":id" => $A0c027a94ce184 ));
    $A0c027a94ce186["url_listing"] = $this->adminLink($this->act_listing);
    $A0c027a94ce186["url_action"] = $this->adminLink($this->act_action);
    $this->ajaxResponse($this->viewPHP($A0c027a94ce186, "inherit"));
    break;
 case "inherit":
    $A0c027a94ce189 = $this->input->get("dynprop", TYPE_UINT);
    if( !$this->isInheritParticular() || !$A0c027a94ce189 || !$A0c027a94ce184 ) 
    {
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    $A0c027a94ce187 = $this->linkIN($A0c027a94ce184, $A0c027a94ce189, false, false);
    $this->ajaxResponse($A0c027a94ce187 ? \Errors::SUCCESS : \Errors::IMPOSSIBLE);
    break;
 case "inherit-copy":
    $A0c027a94ce189 = $this->input->get("dynprop", TYPE_UINT);
    if( !$this->isInheritParticular() || !$A0c027a94ce189 || !$A0c027a94ce184 ) 
    {
   $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    $A0c027a94ce187 = $this->copy($A0c027a94ce189, $A0c027a94ce184);
    $this->ajaxResponse($A0c027a94ce187 ? \Errors::SUCCESS : \Errors::IMPOSSIBLE);
    break;
 case "copy_to":
    $A0c027a94ce200 = array( "copied" => 0 );
    $A0c027a94ce202 = $this->input->post("dynprop", TYPE_ARRAY_UNUM);
    if( empty($A0c027a94ce202) ) 
    {
   $this->errors->set(_t("dynprops", "Отметьте необходимые для копирования свойства"));
   break;
    }

    $A0c027a94ce184 = $this->input->post("owner_to", TYPE_UINT);
    if( empty($A0c027a94ce184) ) 
    {
   $this->errors->set(_t("dynprops", "Укажите куда необходимо выполнить копирование"));
   break;
    }

    foreach( array_keys($A0c027a94ce202) as $A0c027a94ce203 ) 
    {
   $A0c027a94ce204 = $this->copy($A0c027a94ce203, $A0c027a94ce184);
   if( $A0c027a94ce204 ) 
   {
 $A0c027a94ce200["copied"]++;
   }

    }
    if( !false ) 
    {
   $this->ajaxResponseForm($A0c027a94ce200);
   break;
    }

 case "rotate":
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce187 = $this->db->rotateTablednd($this->tblIn, " AND " . $this->ownerColumn . " = " . $A0c027a94ce184, "dynprop_id", "num", true, $this->ownerColumn);
    }
    else
    {
   $A0c027a94ce187 = $this->db->rotateTablednd($this->tblDynprops, " AND " . $this->ownerColumn . " = " . $A0c027a94ce184, "id", "num", true, $this->ownerColumn);
    }

    $this->ajaxResponse($A0c027a94ce187 ? Errors::SUCCESS : Errors::IMPOSSIBLE);
    break;
 case "toggle":
    $A0c027a94ce189 = $this->input->get("dynprop", TYPE_UINT);
    if( !$A0c027a94ce189 ) 
    {
   $this->adminRedirect(\Errors::IMPOSSIBLE, $A0c027a94ce185);
    }

    $A0c027a94ce205 = $this->input->get("type", TYPE_STR);
    switch( $A0c027a94ce205 ) 
    {
   case "is_search":
 $this->db->update($this->tblDynprops, array( "is_search = (1 - is_search)" ), array( "id" => $A0c027a94ce189 ));
 break;
    }
    $this->ajaxResponseForm();
    break;
 case "del":
    $A0c027a94ce189 = $this->input->get("dynprop", TYPE_UINT);
    if( !$A0c027a94ce189 ) 
    {
   $this->adminRedirect(\Errors::IMPOSSIBLE, $A0c027a94ce185);
    }

    $A0c027a94ce206 = $this->input->get("inherit", TYPE_BOOL);
    $A0c027a94ce187 = $this->del($A0c027a94ce189, $A0c027a94ce184, $A0c027a94ce206 == 1, true);
    $this->adminRedirect($A0c027a94ce187 ? \Errors::SUCCESS : \Errors::IMPOSSIBLE, $A0c027a94ce185);
    break;
 case "owners_options":
    $this->ajaxResponseForm(array( "opts" => $this->getOwnersOptions() ));
  }
    }

    public function form($a2dea604bbaa90, $d50cd2d8d3ea6d2 = false, $a0f454987c5a0 = false, $be9fc6457e4da71cd = false, $ab9c648ec8c0 = "d", $f8c167734a5e8e = "form.table", $d15f270fd0f6bdd4781 = false, $ac71e0079799a = array(  ), $Kf7d2408e3953dcc = array(  ))
    {
  $A0c027a94ce207 = array( "form" => "", "id" => array(  ) );
  if( empty($a2dea604bbaa90) ) 
  {
 return $A0c027a94ce207;
  }

  $A0c027a94ce208 = true;
  if( $d15f270fd0f6bdd4781 === false ) 
  {
 list($f8c167734a5e8e, $ac71e0079799a["blocksType"]) = explode(".", $f8c167734a5e8e);
  }
  else
  {
 $ac71e0079799a["blocksType"] = "";
  }

  if( empty($ab9c648ec8c0) ) 
  {
 $ab9c648ec8c0 = "d";
  }

  if( is_array($a2dea604bbaa90) ) 
  {
 $A0c027a94ce209 = $this->getByOwners($a2dea604bbaa90, $a0f454987c5a0, $A0c027a94ce208, $be9fc6457e4da71cd);
 if( empty($A0c027a94ce209) ) 
 {
    $A0c027a94ce209 = array(  );
 }

 if( empty($d50cd2d8d3ea6d2) ) 
 {
    $d50cd2d8d3ea6d2 = array(  );
 }

 $A0c027a94ce207["form"] = array(  );
 $A0c027a94ce207["links"] = array(  );
 foreach( $A0c027a94ce209 as $A0c027a94ce210 ) 
 {
    $A0c027a94ce207["id"][] = $A0c027a94ce210["id"];
    if( $this->inherit && $a0f454987c5a0 ) 
    {
   foreach( explode(",", $A0c027a94ce210["owners"]) as $A0c027a94ce211 ) 
   {
 $A0c027a94ce207["links"][$A0c027a94ce211]["id"][] = $A0c027a94ce210["id"];
 if( !in_array($A0c027a94ce210[$this->ownerColumn], $a2dea604bbaa90) ) 
 {
    $A0c027a94ce207["links"][$A0c027a94ce211]["i"][] = $A0c027a94ce210["id"];
 }

   }
    }

 }
 if( $this->checkChildren() ) 
 {
    $A0c027a94ce212 = $this->getChildrenByParents($A0c027a94ce209, $d50cd2d8d3ea6d2, $A0c027a94ce208, true, true);
 }

 $A0c027a94ce209 = $this->_array_transparent($A0c027a94ce209, $this->ownerColumn);
 foreach( $a0f454987c5a0 == 2 && !empty($d50cd2d8d3ea6d2) ? array_keys($A0c027a94ce209) : $a2dea604bbaa90 as $A0c027a94ce213 ) 
 {
    if( !empty($d50cd2d8d3ea6d2[$A0c027a94ce213]) ) 
    {
   $this->applyData($A0c027a94ce209[$A0c027a94ce213], $d50cd2d8d3ea6d2[$A0c027a94ce213], true);
    }
    else
    {
   $d50cd2d8d3ea6d2[$A0c027a94ce213] = array(  );
    }

    $A0c027a94ce214 = array( "dynprops" => $A0c027a94ce209[$A0c027a94ce213], "prefix" => $ab9c648ec8c0, "children" => isset($A0c027a94ce212) ? $A0c027a94ce212 : array(  ), "extra" => $ac71e0079799a );
    $A0c027a94ce207["form"][$A0c027a94ce213] = $this->viewPHP($A0c027a94ce214, $d15f270fd0f6bdd4781 === false ? "form." . $f8c167734a5e8e : $f8c167734a5e8e, $d15f270fd0f6bdd4781);
 }
 return $A0c027a94ce207;
  }

  $A0c027a94ce209 = $this->getByOwner($a2dea604bbaa90, $a0f454987c5a0, $A0c027a94ce208, $be9fc6457e4da71cd);
  if( empty($A0c027a94ce209) ) 
  {
 $A0c027a94ce209 = array(  );
  }

  if( !empty($d50cd2d8d3ea6d2) ) 
  {
 $this->applyData($A0c027a94ce209, $d50cd2d8d3ea6d2, true);
  }

  if( $this->checkChildren() ) 
  {
 $A0c027a94ce212 = $this->getChildrenByParents($A0c027a94ce209, $d50cd2d8d3ea6d2, $A0c027a94ce208, true, true);
  }

  $A0c027a94ce215 = array(  );
  if( $this->inherit && $a0f454987c5a0 ) 
  {
 foreach( $A0c027a94ce209 as $A0c027a94ce216 => $A0c027a94ce217 ) 
 {
    if( $A0c027a94ce217[$this->ownerColumn] != $a2dea604bbaa90 ) 
    {
   $A0c027a94ce215[] = $A0c027a94ce216;
    }

 }
  }

  $A0c027a94ce207["id"] = array_keys($A0c027a94ce209);
  $A0c027a94ce207["i"] = $A0c027a94ce215;
  if( !empty($Kf7d2408e3953dcc) ) 
  {
 foreach( $Kf7d2408e3953dcc as $A0c027a94ce218 ) 
 {
    if( isset($A0c027a94ce209[$A0c027a94ce218]) ) 
    {
   unset($A0c027a94ce209[$A0c027a94ce218]);
    }

 }
  }

  $A0c027a94ce214 = array( "dynprops" => $A0c027a94ce209, "prefix" => $ab9c648ec8c0, "children" => isset($A0c027a94ce212) ? $A0c027a94ce212 : array(  ), "extra" => $ac71e0079799a );
  $A0c027a94ce207["form"] = $this->viewPHP($A0c027a94ce214, $d15f270fd0f6bdd4781 === false ? "form." . $f8c167734a5e8e : $f8c167734a5e8e, $d15f270fd0f6bdd4781);
  return $A0c027a94ce207;
    }

    public function formChild($d322d4, $b728a32 = array(  ), $d26fb7d9 = false, $R17721fc7 = "form.child", $bc8244867fddbc17 = false, $Z0d7f9d924 = array(  ))
    {
  if( empty($d322d4) ) 
  {
 return "";
  }

  $A0c027a94ce219 = current($d322d4);
  if( !empty($b728a32["name"]) ) 
  {
 $b728a32["name"] = $b728a32["name"] . ($d26fb7d9 ? "[" . $A0c027a94ce219["data_field"] . "]" : "[" . $A0c027a94ce219["id"] . "]");
  }

  if( isset($A0c027a94ce219["extra"]) && $this->hasExtra($A0c027a94ce219["type"]) ) 
  {
 $A0c027a94ce220 = unserialize($A0c027a94ce219["extra"]);
 if( $A0c027a94ce220 !== false ) 
 {
    $A0c027a94ce219 = array_merge($A0c027a94ce219, $A0c027a94ce220);
 }

 unset($A0c027a94ce219["extra"]);
  }

  $A0c027a94ce219["extra"] = $Z0d7f9d924;
  $A0c027a94ce219["attr"] = $b728a32;
  if( $R17721fc7 === false ) 
  {
 return $A0c027a94ce219;
  }

  return $this->viewPHP($A0c027a94ce219, $R17721fc7, $bc8244867fddbc17);
    }

    public function formChildByParentIDValue($gc1370e5, $d8a913, $I5337ab528ab456381f = array(  ), $fe55b2ebb416 = false, $f2d4c8 = "form.child", $Z1ab55 = false, $dd2721af0 = array(  ))
    {
  if( !$gc1370e5 || !$d8a913 ) 
  {
 return "";
  }

  $A0c027a94ce221 = $this->db->one_array("SELECT C.*, P.title" . ($this->langs ? "_" . LNG : "") . " as parent_title, P.extra as parent_extra FROM " . $this->tblDynprops . " C, " . $this->tblDynprops . " P     WHERE C.parent_id = :pid AND C.parent_value = :pval  AND C.parent_id = P.id", array( ":pid" => $gc1370e5, ":pval" => $d8a913 ));
  if( empty($A0c027a94ce221) ) 
  {
 return "";
  }

  if( $this->isMulti($A0c027a94ce221["type"]) ) 
  {
 $A0c027a94ce221["multi"] = $this->getMulti($A0c027a94ce221["id"]);
  }

  $A0c027a94ce222 = unserialize($A0c027a94ce221["parent_extra"]);
  if( $A0c027a94ce222 !== false && !empty($A0c027a94ce222["child_default"]) ) 
  {
 $A0c027a94ce221["default_value"] = $this->langs !== false ? !empty($A0c027a94ce222["child_default"][LNG]) ? $A0c027a94ce222["child_default"][LNG] : false : !empty($A0c027a94ce222["child_default"]) ? $A0c027a94ce222["child_default"] : false;
  }
  else
  {
 $A0c027a94ce221["default_value"] = false;
  }

  unset($A0c027a94ce221["parent_extra"]);
  return $this->formChild(array( $A0c027a94ce221 ), $I5337ab528ab456381f, $fe55b2ebb416, $f2d4c8, $Z1ab55, $dd2721af0);
    }

    public function formCols($d8be8c06cb9, $La874aef823, $fd02e6dc8ed5, $g7e9abba = array(  ), $f626051b = 6, $ua500e9f493157 = 2)
    {
  if( empty($d8be8c06cb9) || !is_callable($fd02e6dc8ed5) ) 
  {
 return "";
  }

  $g7e9abba["style"] = (isset($g7e9abba["style"]) ? $g7e9abba["style"] : "") . "float:left;";
  $g7e9abba = HTML::attributes($g7e9abba);
  $A0c027a94ce223 = sizeof($d8be8c06cb9);
  $A0c027a94ce224 = $A0c027a94ce223 <= $f626051b ? 1 : $ua500e9f493157 <= 2 ? 2 : $ua500e9f493157;
  $A0c027a94ce225 = ceil($A0c027a94ce223 / $A0c027a94ce224);
  $A0c027a94ce226 = round(($A0c027a94ce223 / $A0c027a94ce224 - intval($A0c027a94ce223 / $A0c027a94ce224)) * $A0c027a94ce224);
  $A0c027a94ce227 = 1;
  $A0c027a94ce228 = 1;
  $A0c027a94ce229 = "<ul" . $g7e9abba . ">";
  reset($d8be8c06cb9);
  while( list($A0c027a94ce230, $A0c027a94ce231) = each($d8be8c06cb9) ) 
  {
 $A0c027a94ce229 .= $fd02e6dc8ed5($A0c027a94ce230, $A0c027a94ce231, $La874aef823);
 if( $A0c027a94ce225 <= $A0c027a94ce228++ && 0 < --$A0c027a94ce224 ) 
 {
    $A0c027a94ce229 .= "</ul><ul" . $g7e9abba . ">";
    $A0c027a94ce228 = 1;
    if( $A0c027a94ce227++ == $A0c027a94ce226 ) 
    {
   $A0c027a94ce225--;
    }

 }

  }
  $A0c027a94ce229 .= "</ul><div style=\"clear:both;\"></div>";
  return $A0c027a94ce229;
    }

    public function getByOwner($oefeb298e291, $A65bd3cdf4404f18 = false, $aa45c1aa3dae = true, $q4e1c0d1759 = false)
    {
  $A0c027a94ce232 = "";
  $A0c027a94ce233 = array(  );
  if( $q4e1c0d1759 ) 
  {
 $A0c027a94ce232 .= " AND D.is_search = 1 ";
 if( $this->searchHiddens && !bff::adminPanel() ) 
 {
    array_unshift($A0c027a94ce233, "D.search_hidden ASC");
 }

  }

  $A0c027a94ce232 .= " AND D.parent_id = 0";
  if( $this->inherit ) 
  {
 if( !$A65bd3cdf4404f18 ) 
 {
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce233[] = "DI.num";
   $A0c027a94ce234 = "SELECT D.*, DI.data_field, 0 as inherited FROM " . $this->tblDynprops . " D,     " . $this->tblIn . " DI WHERE DI." . $this->ownerColumn . " = " . $oefeb298e291 . " AND DI.dynprop_id = D.id   AND D." . $this->ownerColumn . " = DI." . $this->ownerColumn . " " . $A0c027a94ce232;
    }
    else
    {
   $A0c027a94ce233[] = "D.num";
   $A0c027a94ce234 = "SELECT D.*, 0 as inherited FROM " . $this->tblDynprops . " D WHERE D." . $this->ownerColumn . " = " . $oefeb298e291 . " " . $A0c027a94ce232;
    }

 }
 else
 {
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce233[] = "D." . $this->ownerColumn . " ASC";
   $A0c027a94ce233[] = "DI.num";
   $A0c027a94ce234 = "SELECT D.*" . ($A65bd3cdf4404f18 !== 2 ? ",DI." . $this->ownerColumn : "") . ", DI.data_field,   (D." . $this->ownerColumn . "!=DI." . $this->ownerColumn . ") as inherited   FROM " . $this->tblDynprops . " D,  " . $this->tblIn . " DI   WHERE DI." . $this->ownerColumn . " = " . $oefeb298e291 . " AND DI.dynprop_id = D.id " . $A0c027a94ce232;
    }
    else
    {
   $A0c027a94ce235 = $this->getOwnerParentsID($oefeb298e291);
   $A0c027a94ce235[] = $oefeb298e291;
   $A0c027a94ce233[] = "inherited DESC";
   $A0c027a94ce233[] = "D.num";
   $A0c027a94ce234 = "SELECT D.*, (D." . $this->ownerColumn . "!=" . $oefeb298e291 . ") as inherited" . ($A65bd3cdf4404f18 !== 2 ? ",D." . $this->ownerColumn : "") . "   FROM " . $this->tblDynprops . " D   WHERE " . $this->db->prepareIN("D." . $this->ownerColumn, $A0c027a94ce235) . " " . $A0c027a94ce232;
    }

 }

  }
  else
  {
 $A0c027a94ce233[] = "D.num";
 $A0c027a94ce234 = "SELECT D.*, 0 as inherited  FROM " . $this->tblDynprops . " D  WHERE D." . $this->ownerColumn . " = " . $oefeb298e291 . " " . $A0c027a94ce232;
  }

  $A0c027a94ce236 = $this->db->select($A0c027a94ce234 . (!empty($A0c027a94ce233) ? " ORDER BY " . join(",", $A0c027a94ce233) : ""));
  if( !empty($A0c027a94ce236) ) 
  {
 $A0c027a94ce236 = $this->_array_transparent($A0c027a94ce236, "id", true);
 $A0c027a94ce237 = array(  );
 foreach( $A0c027a94ce236 as $A0c027a94ce238 => &$A0c027a94ce239 ) 
 {
    $A0c027a94ce236[$A0c027a94ce238]["multi"] = $this->isMulti($A0c027a94ce239["type"]);
    if( $aa45c1aa3dae && $A0c027a94ce236[$A0c027a94ce238]["multi"] ) 
    {
   $A0c027a94ce237[] = $A0c027a94ce239["id"];
    }

    if( $this->langs !== false ) 
    {
   foreach( $this->langFields as $A0c027a94ce240 => $A0c027a94ce241 ) 
   {
 $A0c027a94ce239[$A0c027a94ce240] = isset($A0c027a94ce239[$A0c027a94ce240 . "_" . LNG]) ? $A0c027a94ce239[$A0c027a94ce240 . "_" . LNG] : "";
   }
    }

    if( $this->hasExtra($A0c027a94ce239["type"]) || $A0c027a94ce239["parent"] ) 
    {
   $A0c027a94ce242 = unserialize($A0c027a94ce239["extra"]);
   if( $A0c027a94ce242 !== false ) 
   {
 $A0c027a94ce239 = array_merge($A0c027a94ce239, $A0c027a94ce242);
 if( $A0c027a94ce239["parent"] && $this->langs !== false ) 
 {
    $A0c027a94ce239["child_title"] = !empty($A0c027a94ce239["child_title"][LNG]) ? $A0c027a94ce239["child_title"][LNG] : "";
    $A0c027a94ce239["child_default"] = !empty($A0c027a94ce239["child_default"][LNG]) ? $A0c027a94ce239["child_default"][LNG] : "";
 }

   }

   unset($A0c027a94ce239["extra"]);
    }

 }
 if( !empty($A0c027a94ce237) ) 
 {
    $A0c027a94ce243 = $this->getMulti($A0c027a94ce237);
    $A0c027a94ce243 = $this->_array_transparent($A0c027a94ce243, "dynprop_id");
    reset($A0c027a94ce237);
    while( list(, $A0c027a94ce244) = each($A0c027a94ce237) ) 
    {
   $A0c027a94ce236[$A0c027a94ce244]["multi"] = isset($A0c027a94ce243[$A0c027a94ce244]) ? $A0c027a94ce243[$A0c027a94ce244] : array(  );
    }
    unset($A0c027a94ce243);
 }

 return $A0c027a94ce236;
  }

  return array(  );
    }

    public function getByOwners($b6ca9587, $cd4301e97ee3e3a435 = false, $Vbb3d3fb6628c3 = true, $W08c5b220314ae1ef = false)
    {
  if( empty($b6ca9587) ) 
  {
 return false;
  }

  if( !is_array($b6ca9587) ) 
  {
 $b6ca9587 = array( $b6ca9587 );
  }

  $A0c027a94ce245 = "";
  $A0c027a94ce246 = array(  );
  if( $W08c5b220314ae1ef ) 
  {
 $A0c027a94ce245 .= " AND D.is_search = 1 ";
 if( $this->searchHiddens && !bff::adminPanel() ) 
 {
    array_unshift($A0c027a94ce246, "D.search_hidden ASC");
 }

  }

  $A0c027a94ce245 .= " AND D.parent_id = 0";
  if( $this->inherit ) 
  {
 if( !$cd4301e97ee3e3a435 ) 
 {
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce246[] = "DI.num";
   $A0c027a94ce247 = "SELECT D.*, DI.data_field, 0 as multi,   GROUP_CONCAT(DI." . $this->ownerColumn . ") as owners   FROM " . $this->tblDynprops . " D,   " . $this->tblIn . " DI   WHERE " . $this->db->prepareIN("D." . $this->ownerColumn, $b6ca9587) . "     AND DI.dynprop_id = D.id " . $A0c027a94ce245 . "   GROUP BY D.id";
    }
    else
    {
   $A0c027a94ce246[] = "D.num";
   $A0c027a94ce247 = "SELECT D.*, 0 as multi, D." . $this->ownerColumn . " as owners   FROM " . $this->tblDynprops . " D   WHERE " . $this->db->prepareIN("D." . $this->ownerColumn, $b6ca9587) . " " . $A0c027a94ce245;
    }

 }
 else
 {
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce246[] = "DI.num";
   $A0c027a94ce247 = "SELECT D.*" . ($cd4301e97ee3e3a435 !== 2 ? ",DI." . $this->ownerColumn : "") . ", DI.data_field, 0 as multi,   GROUP_CONCAT(DI." . $this->ownerColumn . ") as owners  FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DI  WHERE " . $this->db->prepareIN("DI." . $this->ownerColumn, $b6ca9587) . "  AND DI.dynprop_id = D.id " . $A0c027a94ce245 . "  GROUP BY D.id";
    }
    else
    {
   $A0c027a94ce248 = array(  );
   foreach( $b6ca9587 as $A0c027a94ce249 ) 
   {
 $A0c027a94ce250 = $this->getOwnerParentsID($A0c027a94ce249);
 if( !empty($A0c027a94ce250) ) 
 {
    $A0c027a94ce248 = array_merge($A0c027a94ce248, $A0c027a94ce250);
 }

 $A0c027a94ce248[] = $A0c027a94ce249;
   }
   $A0c027a94ce246[] = "D.num";
   $A0c027a94ce247 = "SELECT D.*, 0 as multi, D." . $this->ownerColumn . " as owners   FROM " . $this->tblDynprops . " D   WHERE " . $this->db->prepareIN("D." . $this->ownerColumn, $A0c027a94ce248) . " " . $A0c027a94ce245;
    }

 }

  }
  else
  {
 $A0c027a94ce246[] = "D.num";
 $A0c027a94ce247 = "SELECT D.*, 0 as multi, D." . $this->ownerColumn . " as owners  FROM " . $this->tblDynprops . " D  WHERE " . $this->db->prepareIN("D." . $this->ownerColumn, $b6ca9587) . " " . $A0c027a94ce245;
  }

  $A0c027a94ce251 = $this->db->select($A0c027a94ce247 . (!empty($A0c027a94ce246) ? " ORDER BY " . join(",", $A0c027a94ce246) : ""));
  if( !empty($A0c027a94ce251) ) 
  {
 $A0c027a94ce252 = array(  );
 foreach( $A0c027a94ce251 as $A0c027a94ce253 => &$A0c027a94ce254 ) 
 {
    $A0c027a94ce251[$A0c027a94ce253]["multi"] = $this->isMulti($A0c027a94ce254["type"]);
    if( $Vbb3d3fb6628c3 && $A0c027a94ce251[$A0c027a94ce253]["multi"] ) 
    {
   $A0c027a94ce252[] = $A0c027a94ce254["id"];
    }

    if( $this->langs !== false ) 
    {
   foreach( $this->langFields as $A0c027a94ce255 => $A0c027a94ce256 ) 
   {
 $A0c027a94ce254[$A0c027a94ce255] = isset($A0c027a94ce254[$A0c027a94ce255 . "_" . LNG]) ? $A0c027a94ce254[$A0c027a94ce255 . "_" . LNG] : "";
   }
    }

    if( $this->hasExtra($A0c027a94ce254["type"]) || $A0c027a94ce254["parent"] ) 
    {
   $A0c027a94ce257 = unserialize($A0c027a94ce254["extra"]);
   if( $A0c027a94ce257 !== false ) 
   {
 $A0c027a94ce254 = array_merge($A0c027a94ce254, $A0c027a94ce257);
 if( $A0c027a94ce254["parent"] && $this->langs !== false ) 
 {
    $A0c027a94ce254["child_title"] = !empty($A0c027a94ce254["child_title"][LNG]) ? $A0c027a94ce254["child_title"][LNG] : "";
    $A0c027a94ce254["child_default"] = !empty($A0c027a94ce254["child_default"][LNG]) ? $A0c027a94ce254["child_default"][LNG] : "";
 }

   }

   unset($A0c027a94ce254["extra"]);
    }

 }
 if( !empty($A0c027a94ce252) ) 
 {
    $A0c027a94ce258 = $this->getMulti($A0c027a94ce252);
    $A0c027a94ce258 = $this->_array_transparent($A0c027a94ce258, "dynprop_id");
    reset($A0c027a94ce251);
    while( list($A0c027a94ce253, $A0c027a94ce259) = each($A0c027a94ce251) ) 
    {
   if( $A0c027a94ce259["multi"] ) 
   {
 $A0c027a94ce251[$A0c027a94ce253]["multi"] = $A0c027a94ce258[$A0c027a94ce259["id"]];
   }

    }
    unset($A0c027a94ce258);
 }

 return $A0c027a94ce251;
  }

  return array(  );
    }

    public function getByID($a4ec5e9, $r786f3c6 = true, $c8f3b6edc71e6fb = false, $ce11e4a3b4ce10 = false)
    {
  if( empty($a4ec5e9) ) 
  {
 return array(  );
  }

  if( !is_array($a4ec5e9) ) 
  {
 $a4ec5e9 = array( $a4ec5e9 );
  }

  $A0c027a94ce260 = "";
  $A0c027a94ce261 = array( "D.num" );
  if( $c8f3b6edc71e6fb ) 
  {
 $A0c027a94ce260 .= " AND D.is_search = 1 ";
 if( $this->searchHiddens && !bff::adminPanel() ) 
 {
    array_unshift($A0c027a94ce261, "D.search_hidden ASC");
 }

  }

  if( $this->isInheritParticular() ) 
  {
 $A0c027a94ce262 = $this->db->select("SELECT D.*, DI.data_field, 0 as multi    FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DI    WHERE " . $this->db->prepareIN("D.id", $a4ec5e9) . " AND DI.dynprop_id = D.id " . $A0c027a94ce260 . "    ORDER BY " . join(", ", $A0c027a94ce261));
  }
  else
  {
 $A0c027a94ce262 = $this->db->select("SELECT D.*, 0 as multi    FROM " . $this->tblDynprops . " D    WHERE " . $this->db->prepareIN("D.id", $a4ec5e9) . "  " . $A0c027a94ce260 . "    ORDER BY " . join(", ", $A0c027a94ce261));
  }

  if( !empty($A0c027a94ce262) ) 
  {
 $A0c027a94ce262 = $this->_array_transparent($A0c027a94ce262, "id", true);
 $A0c027a94ce263 = array(  );
 foreach( $A0c027a94ce262 as $A0c027a94ce264 => &$A0c027a94ce265 ) 
 {
    $A0c027a94ce262[$A0c027a94ce264]["multi"] = $this->isMulti($A0c027a94ce265["type"]);
    if( $r786f3c6 && $A0c027a94ce262[$A0c027a94ce264]["multi"] ) 
    {
   $A0c027a94ce263[] = $A0c027a94ce265["id"];
    }

    if( $this->langs !== false ) 
    {
   foreach( $this->langFields as $A0c027a94ce266 => $A0c027a94ce267 ) 
   {
 $A0c027a94ce265[$A0c027a94ce266] = isset($A0c027a94ce265[$A0c027a94ce266 . "_" . LNG]) ? $A0c027a94ce265[$A0c027a94ce266 . "_" . LNG] : "";
   }
    }

    if( $this->hasExtra($A0c027a94ce265["type"]) || $A0c027a94ce265["parent"] ) 
    {
   $A0c027a94ce268 = unserialize($A0c027a94ce265["extra"]);
   if( $A0c027a94ce268 !== false ) 
   {
 $A0c027a94ce265 = array_merge($A0c027a94ce265, $A0c027a94ce268);
 if( $A0c027a94ce265["parent"] && $this->langs !== false ) 
 {
    $A0c027a94ce265["child_title"] = !empty($A0c027a94ce265["child_title"][LNG]) ? $A0c027a94ce265["child_title"][LNG] : "";
    $A0c027a94ce265["child_default"] = !empty($A0c027a94ce265["child_default"][LNG]) ? $A0c027a94ce265["child_default"][LNG] : "";
 }

   }

   unset($A0c027a94ce265["extra"]);
    }

 }
 if( !empty($A0c027a94ce263) ) 
 {
    $A0c027a94ce269 = $this->getMulti($A0c027a94ce263);
    $A0c027a94ce269 = $this->_array_transparent($A0c027a94ce269, "dynprop_id");
    reset($A0c027a94ce262);
    while( list($A0c027a94ce264, $A0c027a94ce270) = each($A0c027a94ce262) ) 
    {
   if( $A0c027a94ce270["multi"] ) 
   {
 $A0c027a94ce262[$A0c027a94ce264]["multi"] = $A0c027a94ce269[$A0c027a94ce270["id"]];
   }

    }
    unset($A0c027a94ce269);
 }

 if( $ce11e4a3b4ce10 ) 
 {
    return reset($A0c027a94ce262);
 }

 return $A0c027a94ce262;
  }

  return array(  );
    }

    public function getByParentIDValuePairs($b8659a12a2e, $d14400a2 = true)
    {
  if( empty($b8659a12a2e) ) 
  {
 return array(  );
  }

  $A0c027a94ce271 = array(  );
  $A0c027a94ce272 = 0;
  for( $A0c027a94ce273 = sizeof($b8659a12a2e); $A0c027a94ce272 <= $A0c027a94ce273; $A0c027a94ce272++ ) 
  {
 if( !empty($b8659a12a2e[$A0c027a94ce272]["parent_id"]) ) 
 {
    $A0c027a94ce271[] = "(D.parent_id = " . $b8659a12a2e[$A0c027a94ce272]["parent_id"] . (!empty($b8659a12a2e[$A0c027a94ce272]["parent_value"]) ? " AND D.parent_value = " . $b8659a12a2e[$A0c027a94ce272]["parent_value"] : "") . ")";
 }

  }
  if( empty($A0c027a94ce271) ) 
  {
 return array(  );
  }

  $A0c027a94ce271 = "(" . join(" OR ", $A0c027a94ce271) . ")";
  if( $this->isInheritParticular() ) 
  {
 $A0c027a94ce274 = $this->db->select("SELECT D.*, DI.data_field, 0 as multi    FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DI    WHERE " . $A0c027a94ce271 . " AND D.id = DI.dynprop_id    ORDER BY D.num");
  }
  else
  {
 $A0c027a94ce274 = $this->db->select("SELECT D.*, 0 as multi    FROM " . $this->tblDynprops . " D    WHERE " . $A0c027a94ce271 . "    ORDER BY D.num");
  }

  if( !empty($A0c027a94ce274) ) 
  {
 $A0c027a94ce274 = $this->_array_transparent($A0c027a94ce274, "id", true);
 $A0c027a94ce275 = array(  );
 foreach( $A0c027a94ce274 as $A0c027a94ce276 => &$A0c027a94ce277 ) 
 {
    $A0c027a94ce274[$A0c027a94ce276]["multi"] = $this->isMulti($A0c027a94ce277["type"]);
    if( $d14400a2 && $A0c027a94ce274[$A0c027a94ce276]["multi"] ) 
    {
   $A0c027a94ce275[] = $A0c027a94ce277["id"];
    }

    if( $this->hasExtra($A0c027a94ce277["type"]) || $A0c027a94ce277["parent"] ) 
    {
   $A0c027a94ce278 = unserialize($A0c027a94ce277["extra"]);
   if( $A0c027a94ce278 !== false ) 
   {
 $A0c027a94ce277 = array_merge($A0c027a94ce277, $A0c027a94ce278);
 if( $A0c027a94ce277["parent"] && $this->langs !== false ) 
 {
    $A0c027a94ce277["child_title"] = !empty($A0c027a94ce277["child_title"][LNG]) ? $A0c027a94ce277["child_title"][LNG] : "";
    $A0c027a94ce277["child_default"] = !empty($A0c027a94ce277["child_default"][LNG]) ? $A0c027a94ce277["child_default"][LNG] : "";
 }

   }

   unset($A0c027a94ce277["extra"]);
    }

 }
 if( !empty($A0c027a94ce275) ) 
 {
    $A0c027a94ce279 = $this->getMulti($A0c027a94ce275);
    $A0c027a94ce279 = $this->_array_transparent($A0c027a94ce279, "dynprop_id");
    foreach( $A0c027a94ce274 as $A0c027a94ce276 => $A0c027a94ce280 ) 
    {
   if( $A0c027a94ce280["multi"] ) 
   {
 $A0c027a94ce274[$A0c027a94ce276]["multi"] = $A0c027a94ce279[$A0c027a94ce276];
   }

    }
    unset($A0c027a94ce279);
 }

 $A0c027a94ce281 = $A0c027a94ce274;
 $A0c027a94ce274 = array(  );
 reset($A0c027a94ce281);
 while( list(, $A0c027a94ce282) = each($A0c027a94ce281) ) 
 {
    $A0c027a94ce274[$A0c027a94ce282["parent_id"]][$A0c027a94ce282["parent_value"]] = $A0c027a94ce282;
 }
 unset($A0c027a94ce281);
 return $A0c027a94ce274;
  }

  return array(  );
    }

    protected function getChildrenByParents($A9444c74, $b2903656b1113d = false, $edde167a6d = true, $l9d6c732b3ee = true, $V1f2bbb4 = true)
    {
  if( empty($A9444c74) || !is_array($A9444c74) ) 
  {
 return array(  );
  }

  $A0c027a94ce283 = array(  );
  $A0c027a94ce284 = $l9d6c732b3ee ? array( self::typeNumber, self::typeRange ) : array(  );
  reset($A9444c74);
  while( list(, $A0c027a94ce285) = each($A9444c74) ) 
  {
 $A0c027a94ce286 = ($V1f2bbb4 ? $this->datafield_prefix : "") . $A0c027a94ce285["data_field"];
 $A0c027a94ce287 = in_array($A0c027a94ce285["type"], $A0c027a94ce284);
 if( $A0c027a94ce285["parent"] && ($A0c027a94ce287 || $b2903656b1113d !== false && !empty($b2903656b1113d[$A0c027a94ce286])) ) 
 {
    if( !$A0c027a94ce287 && is_array($b2903656b1113d[$A0c027a94ce286]) ) 
    {
   foreach( $b2903656b1113d[$A0c027a94ce286] as $A0c027a94ce288 ) 
   {
 $A0c027a94ce283[] = array( "parent_id" => $A0c027a94ce285["id"], "parent_value" => $A0c027a94ce288 );
   }
    }
    else
    {
   $A0c027a94ce283[] = array( "parent_id" => $A0c027a94ce285["id"], "parent_value" => $A0c027a94ce287 ? 0 : $b2903656b1113d[$A0c027a94ce286] );
    }

 }

  }
  if( empty($A0c027a94ce283) ) 
  {
 return array(  );
  }

  $A0c027a94ce289 = $this->getByParentIDValuePairs($A0c027a94ce283, $edde167a6d);
  if( !empty($b2903656b1113d) ) 
  {
 $this->applyData($A0c027a94ce289, $b2903656b1113d, $V1f2bbb4);
  }

  reset($A9444c74);
  while( list(, $A0c027a94ce285) = each($A9444c74) ) 
  {
 if( !empty($A0c027a94ce289[$A0c027a94ce285["id"]]) ) 
 {
    foreach( $A0c027a94ce289[$A0c027a94ce285["id"]] as $A0c027a94ce290 => $A0c027a94ce291 ) 
    {
   $A0c027a94ce289[$A0c027a94ce285["id"]][$A0c027a94ce290]["default_value"] = $A0c027a94ce285["child_default"];
    }
 }

  }
  return $A0c027a94ce289;
    }

    protected function applyData(&$ac683a365ce00a1e8, $c72b9a, $xb7fc7b205d627 = true)
    {
  if( empty($ac683a365ce00a1e8) || empty($c72b9a) ) 
  {
 return NULL;
  }

  foreach( $ac683a365ce00a1e8 as &$A0c027a94ce292 ) 
  {
 if( !isset($A0c027a94ce292["type"]) && is_array($A0c027a94ce292) ) 
 {
    $this->applyData($A0c027a94ce292, $c72b9a, $xb7fc7b205d627);
    continue;
 }

 $A0c027a94ce293 = ($xb7fc7b205d627 ? $this->datafield_prefix : "") . $A0c027a94ce292["data_field"];
 $A0c027a94ce294 = isset($c72b9a[$A0c027a94ce293]) ? $c72b9a[$A0c027a94ce293] : "";
 if( $this->isStoringBits($A0c027a94ce292["type"]) ) 
 {
    if( is_array($A0c027a94ce294) ) 
    {
   $A0c027a94ce294 = array_sum($A0c027a94ce294);
    }

    $A0c027a94ce292["value"] = join(";", $this->bit2source(intval($A0c027a94ce294)));
 }
 else
 {
    if( $this->isMulti($A0c027a94ce292["type"]) ) 
    {
   $A0c027a94ce292["value"] = 0;
   if( !empty($A0c027a94ce292["multi"]) ) 
   {
 if( is_array($A0c027a94ce294) ) 
 {
    $A0c027a94ce292["value"] = array(  );
    foreach( $A0c027a94ce292["multi"] as $A0c027a94ce295 ) 
    {
 if( in_array($A0c027a94ce295["value"], $A0c027a94ce294) ) 
 {
     $A0c027a94ce292["value"][] = $A0c027a94ce295["value"];
 }

    }
 }
 else
 {
    foreach( $A0c027a94ce292["multi"] as $A0c027a94ce295 ) 
    {
 if( $A0c027a94ce295["value"] == $A0c027a94ce294 ) 
 {
     $A0c027a94ce292["value"] = $A0c027a94ce295["value"];
     break;
 }

    }
 }

   }

    }
    else
    {
   $A0c027a94ce292["value"] = $A0c027a94ce294;
    }

 }

  }
    }

    /**
     * @param $dd2721af0e8
     * @param $ca62b3ae4a2972
     * @param bool $e33d8069
     * @return bool
     */
    protected function insert(&$dd2721af0e8, $ca62b3ae4a2972, $e33d8069 = false)
    {
  $A0c027a94ce296 = array( "type" => TYPE_UINT, "title" => $this->langs ? TYPE_ARRAY_STR : TYPE_STR, "description" => $this->langs ? TYPE_ARRAY_STR : TYPE_STR, "req" => TYPE_BOOL, "is_search" => TYPE_BOOL, "cache_key" => TYPE_STR, "parent" => TYPE_BOOL, "search_hidden" => TYPE_BOOL );
  foreach( $this->extraSettings as $A0c027a94ce297 => $A0c027a94ce298 ) 
  {
 if( !isset($A0c027a94ce296[$A0c027a94ce297]) ) 
 {
    $A0c027a94ce296[$A0c027a94ce297] = $A0c027a94ce298["type"];
 }

  }
  $this->input->clean_array($dd2721af0e8, $A0c027a94ce296, $dd2721af0e8);
  $A0c027a94ce299 = $dd2721af0e8["type"];
  $A0c027a94ce300 = $this->isStoringBits($A0c027a94ce299);
  $A0c027a94ce301 = $this->isMulti($A0c027a94ce299);
  $A0c027a94ce302 = $dd2721af0e8["parent"];
  if( empty($dd2721af0e8["is_search"]) ) 
  {
 $dd2721af0e8["search_hidden"] = 0;
  }

  if( $A0c027a94ce301 ) 
  {
 $A0c027a94ce303 =& $dd2721af0e8["multi_default_value"];
 $A0c027a94ce304 = isset($A0c027a94ce303) ? array_flip(is_array($A0c027a94ce303) ? $A0c027a94ce303 : array( $A0c027a94ce303 )) : array(  );
 $A0c027a94ce305 = array(  );
 $A0c027a94ce306 = 0;
 foreach( $dd2721af0e8["multi"] as $A0c027a94ce307 => $A0c027a94ce308 ) 
 {
    $A0c027a94ce309 = !$A0c027a94ce307 ? 0 : $A0c027a94ce300 ? pow(2, $A0c027a94ce306) : $A0c027a94ce307;
    if( array_key_exists($A0c027a94ce307, $A0c027a94ce304) ) 
    {
   $A0c027a94ce305[] = $A0c027a94ce309;
    }

    $A0c027a94ce306++;
 }
 $dd2721af0e8["default_value"] = $A0c027a94ce300 ? join(";", $A0c027a94ce305) : $A0c027a94ce303;
 if( !isset($dd2721af0e8["default_value"]) ) 
 {
    $dd2721af0e8["default_value"] = "";
 }

  }
  else
  {
 if( !isset($dd2721af0e8["default_value"]) ) 
 {
    $dd2721af0e8["default_value"] = "";
 }

  }

  if( $A0c027a94ce302 && !$dd2721af0e8["title"] ) 
  {
 $this->errors->set(_t("dynprops", "Укажите название"));
  }

  $A0c027a94ce310 = $this->prepareExtra($dd2721af0e8, $A0c027a94ce299);
  if( $A0c027a94ce302 ) 
  {
 $A0c027a94ce310["child_title"] = $this->input->clean($dd2721af0e8["child_title"], $this->langs ? TYPE_ARRAY_STR : TYPE_STR);
 $A0c027a94ce310["child_default"] = $this->input->clean($dd2721af0e8["child_default"], $this->langs ? TYPE_ARRAY_STR : TYPE_STR);
  }

  while( !$this->errors->no() ) 
  {
 break;
  }
  $A0c027a94ce311 = false;
  $A0c027a94ce312 = array( $this->ownerColumn => $ca62b3ae4a2972, "type" => $A0c027a94ce299, "parent" => $A0c027a94ce302, "default_value" => $dd2721af0e8["default_value"], "req" => $dd2721af0e8["req"], "is_search" => $dd2721af0e8["is_search"], "cache_key" => $dd2721af0e8["cache_key"], "extra" => serialize($A0c027a94ce310) );
  if( $this->searchHiddens ) 
  {
 $A0c027a94ce312["search_hidden"] = $dd2721af0e8["search_hidden"];
  }

  foreach( $this->extraSettings as $A0c027a94ce297 => $A0c027a94ce298 ) 
  {
 $A0c027a94ce312[$A0c027a94ce297] = $dd2721af0e8[$A0c027a94ce297];
  }
  if( $this->langs ) 
  {
      $langs = $this->langs;
 foreach( $langs as $A0c027a94ce297 => $A0c027a94ce298 )
 {
    $A0c027a94ce312["title_" . $A0c027a94ce297] = isset($dd2721af0e8["title"][$A0c027a94ce297]) ? $dd2721af0e8["title"][$A0c027a94ce297] : "";
    $A0c027a94ce312["description_" . $A0c027a94ce297] = isset($dd2721af0e8["description"][$A0c027a94ce297]) ? $dd2721af0e8["description"][$A0c027a94ce297] : "";
 }
  }
  else
  {
 $A0c027a94ce312["title"] = $dd2721af0e8["title"];
 $A0c027a94ce312["description"] = $dd2721af0e8["description"];
  }

  if( !$this->isInheritParticular() || !empty($e33d8069) ) 
  {
 $A0c027a94ce313 = $this->getDataField($ca62b3ae4a2972, $A0c027a94ce299, $e33d8069);
 if( $A0c027a94ce313 === false )
 {

    return;
 }

 $A0c027a94ce312["data_field"] = $A0c027a94ce313;
 $A0c027a94ce312["num"] = $this->getNum($ca62b3ae4a2972);
 $A0c027a94ce311 = true;
  }

  if( !empty($e33d8069) ) 
  {
 $A0c027a94ce312["parent_id"] = $e33d8069["id"];
 $A0c027a94ce312["parent_value"] = $e33d8069["value"];
  }

  $A0c027a94ce314 = $this->db->insert($this->tblDynprops, $A0c027a94ce312, "id");
  if( 0 < $A0c027a94ce314 ) 
  {
 if( $A0c027a94ce301 ) 
 {
    $A0c027a94ce306 = 0;
    $A0c027a94ce315 = array(  );
    foreach( $dd2721af0e8["multi"] as $A0c027a94ce307 => $A0c027a94ce308 ) 
    {
   $A0c027a94ce309 = !$A0c027a94ce307 ? 0 : $A0c027a94ce300 ? pow(2, $A0c027a94ce306) : $A0c027a94ce307;
   if( $this->langs ) 
   {
 $A0c027a94ce316 = array(  );
 foreach( $A0c027a94ce308 as $A0c027a94ce317 ) 
 {
    $A0c027a94ce316[] = $this->db->str2sql(trim($A0c027a94ce317));
 }
 $A0c027a94ce316 = join(", ", $A0c027a94ce316);
   }
   else
   {
 $A0c027a94ce316 = $this->db->str2sql(trim($A0c027a94ce308));
   }

   $A0c027a94ce315[] = "(" . $A0c027a94ce314 . ", " . $A0c027a94ce316 . ", " . $A0c027a94ce309 . ", " . ++$A0c027a94ce306 . ")";
    }
    if( !empty($A0c027a94ce315) ) 
    {
   $A0c027a94ce318 = "name";
   if( $this->langs ) 
   {
 $A0c027a94ce318 = array(  );
 foreach( $this->langs as $A0c027a94ce319 => $A0c027a94ce320 ) 
 {
    $A0c027a94ce318[] = "name_" . $A0c027a94ce319;
 }
 $A0c027a94ce318 = join(", ", $A0c027a94ce318);
   }

   $this->db->exec("INSERT INTO " . $this->tblMulti . "    (dynprop_id, " . $A0c027a94ce318 . ", value, num) VALUES " . join(",", $A0c027a94ce315));
    }

 }

 $this->updateCache("insert", array( "id" => $A0c027a94ce314, "owner" => $ca62b3ae4a2972 ));
 if( $this->isInheritParticular() ) 
 {
    return $this->linkIN($ca62b3ae4a2972, $A0c027a94ce314, $A0c027a94ce299, !$A0c027a94ce311);
 }

 return true;
  }

  $this->errors->set(_t("dynprops", "Ошибка добавления дин. свойства"));
  if( !false ) 
  {
 if( $this->langs ) 
 {
    $this->db->langFieldsModify($dd2721af0e8, $this->langFields, $dd2721af0e8);
 }

 if( $A0c027a94ce301 ) 
 {
    $A0c027a94ce321 = $dd2721af0e8["multi"];
    $dd2721af0e8["multi"] = array(  );
    if( $this->langs ) 
    {
   foreach( $A0c027a94ce321 as $A0c027a94ce307 => $A0c027a94ce308 ) 
   {
 $A0c027a94ce322 = array( "value" => $A0c027a94ce307 );
 foreach( $A0c027a94ce308 as $A0c027a94ce319 => $A0c027a94ce320 ) 
 {
    $A0c027a94ce322["name_" . $A0c027a94ce319] = $A0c027a94ce320;
 }
 $dd2721af0e8["multi"][] = $A0c027a94ce322;
   }
    }
    else
    {
   foreach( $A0c027a94ce321 as $A0c027a94ce307 => $A0c027a94ce308 ) 
   {
 $dd2721af0e8["multi"][] = array( "name" => $A0c027a94ce308, "value" => $A0c027a94ce307 );
   }
    }

    unset($A0c027a94ce321);
 }

 return false;
  }

    }

    protected function update(&$b7c0f8, $e14cbe7cd0ef4b7, $Ycc333ba6)
    {
  $A0c027a94ce323 = array( "type" => TYPE_UINT, "title" => $this->langs ? TYPE_ARRAY_STR : TYPE_STR, "description" => $this->langs ? TYPE_ARRAY_STR : TYPE_STR, "req" => TYPE_BOOL, "is_search" => TYPE_BOOL, "cache_key" => TYPE_STR, "parent" => TYPE_BOOL, "search_hidden" => TYPE_BOOL );
  foreach( $this->extraSettings as $A0c027a94ce324 => $A0c027a94ce325 ) 
  {
 if( !isset($A0c027a94ce323[$A0c027a94ce324]) ) 
 {
    $A0c027a94ce323[$A0c027a94ce324] = $A0c027a94ce325["type"];
 }

  }
  $this->input->clean_array($b7c0f8, $A0c027a94ce323, $b7c0f8);
  $A0c027a94ce326 =& $b7c0f8["type"];
  $A0c027a94ce327 = $this->isStoringBits($A0c027a94ce326);
  $A0c027a94ce328 = $this->isMulti($A0c027a94ce326);
  $A0c027a94ce329 =& $b7c0f8["parent"];
  if( empty($b7c0f8["is_search"]) ) 
  {
 $b7c0f8["search_hidden"] = 0;
  }

  if( $A0c027a94ce328 ) 
  {
 $A0c027a94ce330 =& $b7c0f8["multi_default_value"];
 if( $A0c027a94ce327 ) 
 {
    $b7c0f8["default_value"] = join(";", isset($A0c027a94ce330) ? array_flip(is_array($A0c027a94ce330) ? $A0c027a94ce330 : array( $A0c027a94ce330 )) : array(  ));
 }
 else
 {
    $b7c0f8["default_value"] = isset($A0c027a94ce330) ? $A0c027a94ce330 : "";
 }

  }

  if( $A0c027a94ce329 && !$b7c0f8["title"] ) 
  {
 $this->errors->set(_t("dynprops", "Укажите название"));
  }

  $A0c027a94ce331 = $this->prepareExtra($b7c0f8, $A0c027a94ce326);
  if( $A0c027a94ce329 ) 
  {
 $A0c027a94ce331["child_title"] = $this->input->clean($b7c0f8["child_title"], $this->langs ? TYPE_ARRAY_STR : TYPE_STR);
 $A0c027a94ce331["child_default"] = $this->input->clean($b7c0f8["child_default"], $this->langs ? TYPE_ARRAY_STR : TYPE_STR);
  }

  if( $this->errors->no() ) 
  {
 $A0c027a94ce332 = array( "type" => $A0c027a94ce326, "parent" => $A0c027a94ce329, "default_value" => isset($b7c0f8["default_value"]) ? $b7c0f8["default_value"] : "", "req" => $b7c0f8["req"], "is_search" => $b7c0f8["is_search"], "cache_key" => $b7c0f8["cache_key"], "extra" => serialize($A0c027a94ce331) );
 if( $this->searchHiddens ) 
 {
    $A0c027a94ce332["search_hidden"] = $b7c0f8["search_hidden"];
 }

 foreach( $this->extraSettings as $A0c027a94ce324 => $A0c027a94ce325 ) 
 {
    $A0c027a94ce332[$A0c027a94ce324] = $b7c0f8[$A0c027a94ce324];
 }
 if( $this->langs ) 
 {
    foreach( $this->langs as $A0c027a94ce324 => $A0c027a94ce325 ) 
    {
   $A0c027a94ce332["title_" . $A0c027a94ce324] = isset($b7c0f8["title"][$A0c027a94ce324]) ? $b7c0f8["title"][$A0c027a94ce324] : "";
   $A0c027a94ce332["description_" . $A0c027a94ce324] = isset($b7c0f8["description"][$A0c027a94ce324]) ? $b7c0f8["description"][$A0c027a94ce324] : "";
    }
 }
 else
 {
    $A0c027a94ce332["title"] = $b7c0f8["title"];
    $A0c027a94ce332["description"] = $b7c0f8["description"];
 }

 $A0c027a94ce333 = $this->db->update($this->tblDynprops, $A0c027a94ce332, array( "id" => $Ycc333ba6 ));
 if( !empty($A0c027a94ce333) ) 
 {
    if( $A0c027a94ce328 ) 
    {
   $A0c027a94ce334 = !empty($b7c0f8["multi_added"]) ? explode(",", $b7c0f8["multi_added"]) : array(  );
   $A0c027a94ce334 = array_map("intval", $A0c027a94ce334);
   $A0c027a94ce335 = 1;
   foreach( $b7c0f8["multi"] as $A0c027a94ce336 => $A0c027a94ce337 ) 
   {
 if( !in_array($A0c027a94ce336, $A0c027a94ce334) ) 
 {
    $A0c027a94ce332 = array( "num" => $A0c027a94ce335++ );
    if( $this->langs ) 
    {
 foreach( $A0c027a94ce337 as $A0c027a94ce338 => $A0c027a94ce339 ) 
 {
     $A0c027a94ce332["name_" . $A0c027a94ce338] = trim($A0c027a94ce339);
 }
    }
    else
    {
 $A0c027a94ce332["name"] = trim($A0c027a94ce337);
    }

    $this->db->update($this->tblMulti, $A0c027a94ce332, "dynprop_id = :dp AND value = :val", array( ":dp" => $Ycc333ba6, ":val" => $A0c027a94ce336 ));
 }
 else
 {
    if( $this->langs || $A0c027a94ce337 != "" ) 
    {
 $A0c027a94ce340 = array( "num" => $A0c027a94ce335++ );
 if( $this->langs ) 
 {
     $A0c027a94ce340["name"] = array(  );
     foreach( $A0c027a94ce337 as $A0c027a94ce338 => $A0c027a94ce339 ) 
     {
   $A0c027a94ce340["name"][$A0c027a94ce338] = $this->db->str2sql(trim($A0c027a94ce339));
     }
 }
 else
 {
     $A0c027a94ce340["name"] = $this->db->str2sql(trim($A0c027a94ce337));
 }

 $b7c0f8["multi"][$A0c027a94ce336] = $A0c027a94ce340;
    }
    else
    {
 unset($b7c0f8["multi"][$A0c027a94ce336]);
    }

 }

   }
   $A0c027a94ce341 = $this->db->one_data("SELECT MAX(value) as value FROM " . $this->tblMulti . " WHERE dynprop_id = :id", array( ":id" => $Ycc333ba6 ));
   if( !empty($b7c0f8["multi_deleted"]) ) 
   {
 $b7c0f8["multi_deleted"] = join(",", array_map("intval", explode(",", $b7c0f8["multi_deleted"])));
 $A0c027a94ce342 = $this->db->select_one_column("SELECT id FROM " . $this->tblDynprops . "   WHERE parent_id = " . $Ycc333ba6 . " AND parent_value IN(" . $b7c0f8["multi_deleted"] . ")");
 if( !empty($A0c027a94ce342) ) 
 {
    $this->db->exec("DELETE FROM " . $this->tblMulti . "  WHERE " . $this->db->prepareIN("dynprop_id", $A0c027a94ce342));
    $this->db->exec("DELETE FROM " . $this->tblDynprops . "  WHERE " . $this->db->prepareIN("id", $A0c027a94ce342));
 }

 $this->db->exec("DELETE FROM " . $this->tblMulti . "   WHERE dynprop_id = " . $Ycc333ba6 . " AND value IN(" . $b7c0f8["multi_deleted"] . ")");
   }

   if( !empty($A0c027a94ce334) ) 
   {
 $A0c027a94ce335 = $A0c027a94ce341;
 $A0c027a94ce343 = $A0c027a94ce335;
 $A0c027a94ce344 = array(  );
 foreach( $b7c0f8["multi"] as $A0c027a94ce336 => $A0c027a94ce325 ) 
 {
    if( in_array($A0c027a94ce336, $A0c027a94ce334) ) 
    {
 $A0c027a94ce345 = $A0c027a94ce327 ? ($A0c027a94ce343 *= 2) : ++$A0c027a94ce335;
 $A0c027a94ce344[] = "(" . $Ycc333ba6 . ", " . ($this->langs ? join(",", $A0c027a94ce325["name"]) : $A0c027a94ce325["name"]) . ", " . $A0c027a94ce345 . ", " . $A0c027a94ce325["num"] . ")";
    }

 }
 if( !empty($A0c027a94ce344) ) 
 {
    $A0c027a94ce346 = "name";
    if( $this->langs ) 
    {
 $A0c027a94ce346 = array(  );
 foreach( $this->langs as $A0c027a94ce338 => $A0c027a94ce339 ) 
 {
     $A0c027a94ce346[] = "name_" . $A0c027a94ce338;
 }
 $A0c027a94ce346 = join(", ", $A0c027a94ce346);
    }

    $this->db->exec("INSERT INTO " . $this->tblMulti . "  (dynprop_id, " . $A0c027a94ce346 . ", value, num) VALUES " . join(",", $A0c027a94ce344));
 }

   }

    }

    $this->updateCache("update", array( "id" => $Ycc333ba6, "owner" => $e14cbe7cd0ef4b7 ));
    return true;
 }

 $this->errors->set(_t("dynprops", "Ошибка сохранения настроек дин. свойства"));
  }
  else
  {
 if( $this->langs ) 
 {
    $this->db->langFieldsModify($b7c0f8, $this->langFields, $b7c0f8);
 }

 if( $A0c027a94ce328 ) 
 {
    $A0c027a94ce347 = $b7c0f8["multi"];
    $b7c0f8["multi"] = array(  );
    if( $this->langs ) 
    {
   foreach( $A0c027a94ce347 as $A0c027a94ce336 => $A0c027a94ce337 ) 
   {
 $A0c027a94ce348 = array( "value" => $A0c027a94ce336 );
 foreach( $A0c027a94ce337 as $A0c027a94ce338 => $A0c027a94ce339 ) 
 {
    $A0c027a94ce348["name_" . $A0c027a94ce338] = $A0c027a94ce339;
 }
 $b7c0f8["multi"][] = $A0c027a94ce348;
   }
    }
    else
    {
   foreach( $A0c027a94ce347 as $A0c027a94ce336 => $A0c027a94ce337 ) 
   {
 $b7c0f8["multi"][] = array( "name" => $A0c027a94ce337, "value" => $A0c027a94ce336 );
   }
    }

    unset($A0c027a94ce347);
 }

 return false;
  }

    }

    protected function copy($idbfce6ddd8638c, $R8f9ddda)
    {
  if( empty($idbfce6ddd8638c) ) 
  {
 return false;
  }

  if( is_integer($idbfce6ddd8638c) ) 
  {
 $A0c027a94ce349 = $idbfce6ddd8638c;
 $A0c027a94ce350 = $this->db->one_array("SELECT * FROM " . $this->tblDynprops . " WHERE id = :id", array( ":id" => $A0c027a94ce349 ));
 if( empty($A0c027a94ce350) ) 
 {
    return false;
 }

  }
  else
  {
 if( is_array($idbfce6ddd8638c) ) 
 {
    $A0c027a94ce349 = $idbfce6ddd8638c["id"];
    $A0c027a94ce350 = $idbfce6ddd8638c;
 }
 else
 {
    return false;
 }

  }

  $A0c027a94ce351 = !empty($A0c027a94ce350["parent"]);
  $A0c027a94ce352 = false;
  if( !empty($A0c027a94ce350["parent_id"]) ) 
  {
 $A0c027a94ce352 = array( "id" => $A0c027a94ce350["parent_id"], "value" => $A0c027a94ce350["parent_value"] );
 $A0c027a94ce351 = false;
  }

  $A0c027a94ce353 = $A0c027a94ce350["type"];
  unset($A0c027a94ce350["id"]);
  $A0c027a94ce350[$this->ownerColumn] = $R8f9ddda;
  $A0c027a94ce354 = false;
  if( !$this->isInheritParticular() || !empty($A0c027a94ce352) ) 
  {
 $A0c027a94ce355 = $this->getDataField($R8f9ddda, $A0c027a94ce353, $A0c027a94ce352);
 if( $A0c027a94ce355 === false ) 
 {
    return false;
 }

 $A0c027a94ce356 = $this->getNum($R8f9ddda);
 $A0c027a94ce350["data_field"] = $A0c027a94ce355;
 $A0c027a94ce350["num"] = $A0c027a94ce356;
 $A0c027a94ce354 = true;
  }

  $A0c027a94ce357 = $this->db->insert($this->tblDynprops, $A0c027a94ce350);
  if( empty($A0c027a94ce357) ) 
  {
 return false;
  }

  if( $this->isMulti($A0c027a94ce353) ) 
  {
 $A0c027a94ce358 = $this->db->select("SELECT * FROM " . $this->tblMulti . " WHERE dynprop_id = :id", array( ":id" => $A0c027a94ce349 ));
 if( !empty($A0c027a94ce358) ) 
 {
    $A0c027a94ce359 = array(  );
    foreach( $A0c027a94ce358 as $A0c027a94ce360 ) 
    {
   $A0c027a94ce361 = array( "dynprop_id" => $A0c027a94ce357, "value" => $A0c027a94ce360["value"], "num" => $A0c027a94ce360["num"] );
   if( $this->langs ) 
   {
 foreach( $this->langs as $A0c027a94ce362 => $A0c027a94ce363 ) 
 {
    $A0c027a94ce361["name_" . $A0c027a94ce362] = $A0c027a94ce360["name_" . $A0c027a94ce362];
 }
   }
   else
   {
 $A0c027a94ce361["name"] = $A0c027a94ce360["name"];
   }

   $A0c027a94ce359[] = $A0c027a94ce361;
    }
    $this->db->multiInsert($this->tblMulti, $A0c027a94ce359);
 }

  }

  if( $A0c027a94ce351 ) 
  {
 $A0c027a94ce364 = $this->db->select("SELECT * FROM " . $this->tblDynprops . " WHERE parent_id = :id", array( ":id" => $A0c027a94ce349 ));
 if( !empty($A0c027a94ce364) ) 
 {
    foreach( $A0c027a94ce364 as $A0c027a94ce365 ) 
    {
   $A0c027a94ce365["parent_id"] = $A0c027a94ce357;
   $this->copy($A0c027a94ce365, $R8f9ddda);
    }
 }

  }

  $this->updateCache("copy", array( "id" => $A0c027a94ce357, "owner" => $R8f9ddda ));
  if( $this->isInheritParticular() ) 
  {
 return $this->linkIN($R8f9ddda, $A0c027a94ce357, $A0c027a94ce353, !$A0c027a94ce354);
  }

  return true;
    }

    protected function del($K651a6713d27b93ff01, $u936fb3a, $P35d1320ae68e = false, $jcb3f2c79e8752c2d0 = true)
    {
  if( $this->isInheritParticular() && $P35d1320ae68e ) 
  {
 $A0c027a94ce366 = $this->inherit ? $this->getOwnerChildrensID($u936fb3a) : array(  );
 $A0c027a94ce366[] = $u936fb3a;
 $A0c027a94ce367 = $this->db->delete($this->tblIn, array( "dynprop_id" => $K651a6713d27b93ff01, $this->ownerColumn => $A0c027a94ce366 ));
  }
  else
  {
 $A0c027a94ce368 = $this->db->one_array("SELECT * FROM " . $this->tblDynprops . " WHERE id = :id AND " . $this->ownerColumn . " = :owner", array( ":id" => $K651a6713d27b93ff01, ":owner" => $u936fb3a ));
 if( empty($A0c027a94ce368) ) 
 {
    return false;
 }

 $A0c027a94ce367 = $this->db->delete($this->tblDynprops, array( "id" => $K651a6713d27b93ff01, $this->ownerColumn => $u936fb3a ));
 if( !empty($A0c027a94ce367) ) 
 {
    $A0c027a94ce369 = $this->isMulti($A0c027a94ce368["type"]);
    if( !empty($A0c027a94ce368["parent"]) ) 
    {
   $A0c027a94ce370 = $this->db->select_one_column("SELECT id FROM " . $this->tblDynprops . " WHERE parent_id = :id", array( ":id" => $K651a6713d27b93ff01 ));
   if( !empty($A0c027a94ce370) ) 
   {
 $this->db->delete($this->tblMulti, array( "dynprop_id" => $A0c027a94ce370 ));
 $this->db->delete($this->tblDynprops, array( "id" => $A0c027a94ce370 ));
   }

    }

    if( $A0c027a94ce369 ) 
    {
   $this->db->delete($this->tblMulti, array( "dynprop_id" => $K651a6713d27b93ff01 ));
    }

    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce367 = $this->db->delete($this->tblIn, array( "dynprop_id" => $K651a6713d27b93ff01 ));
    }

 }

  }

  if( !empty($A0c027a94ce367) && $jcb3f2c79e8752c2d0 ) 
  {
 $this->updateCache("del", array( "id" => $K651a6713d27b93ff01, "owner" => $u936fb3a ));
 return true;
  }

  return false;
    }

    protected function delAll($sc4047c18643, $c28d465d78e8c3e01d = false)
    {
  $A0c027a94ce371 = false;
  $A0c027a94ce372 = $this->db->select_one_column("SELECT id FROM " . $this->tblDynprops . " WHERE " . $this->ownerColumn . " = :id", array( ":id" => $sc4047c18643 ));
  foreach( $A0c027a94ce372 as $A0c027a94ce373 ) 
  {
 $A0c027a94ce374 = $this->del($A0c027a94ce373, $sc4047c18643, $c28d465d78e8c3e01d, false);
 if( $A0c027a94ce374 ) 
 {
    $A0c027a94ce371 = true;
 }

  }
  if( $A0c027a94ce371 ) 
  {
 $this->updateCache("delall", array( "id" => 0, "owner" => $sc4047c18643 ));
  }

  return true;
    }

    protected function linkIN($dc8b7349b8d4, $aba403a, $ac7916d18 = false, $M27e4dbbc = true)
    {
  if( !$this->isInheritParticular() ) 
  {
 return false;
  }

  if( $M27e4dbbc ) 
  {
 if( $ac7916d18 === false ) 
 {
    $ac7916d18 = $this->db->one_data("SELECT type FROM " . $this->tblDynprops . " WHERE id = :id", array( ":id" => $aba403a ));
    if( !$ac7916d18 ) 
    {
   return false;
    }

 }

 $A0c027a94ce375 = $this->getDataField($dc8b7349b8d4, $ac7916d18);
 if( $A0c027a94ce375 === false ) 
 {
    return false;
 }

  }
  else
  {
 $A0c027a94ce375 = (int) $this->db->one_data("SELECT data_field FROM " . $this->tblIn . " WHERE dynprop_id = :id", array( ":id" => $aba403a ));
 if( empty($A0c027a94ce375) ) 
 {
    return false;
 }

  }

  return $this->db->insert($this->tblIn, array( "dynprop_id" => $aba403a, $this->ownerColumn => $dc8b7349b8d4, "data_field" => $A0c027a94ce375, "num" => $this->getNum($dc8b7349b8d4) ), false);
    }

    protected function prepareExtra(&$dd2628fc, $Abdce4a5)
    {
  $A0c027a94ce376 = array(  );
  switch( $Abdce4a5 ) 
  {
 case self::typeRange:
 case self::typeNumber:
    if( $Abdce4a5 == self::typeRange ) 
    {
   $this->input->clean_array($dd2628fc, array( "start" => TYPE_NUM, "end" => TYPE_NUM, "step" => TYPE_NUM ), $A0c027a94ce376);
   if( !$A0c027a94ce376["end"] ) 
   {
 $this->errors->set(_t("dynprops", "Укажите окончание диапазона"));
   }

   if( !$A0c027a94ce376["step"] ) 
   {
 $this->errors->set(_t("dynprops", "Укажите шаг диапазона"));
   }

    }

    $this->input->clean_array($dd2628fc, array( "search_range_user" => TYPE_BOOL, "search_ranges" => TYPE_ARRAY ), $A0c027a94ce376);
    if( !$this->searchRanges ) 
    {
   $A0c027a94ce376["search_range_user"] = true;
    }
    else
    {
   foreach( $A0c027a94ce376["search_ranges"] as $A0c027a94ce377 => $A0c027a94ce378 ) 
   {
 $A0c027a94ce378["id"] = intval($A0c027a94ce378["id"]);
 $A0c027a94ce378["from"] = floatval(strip_tags($A0c027a94ce378["from"]));
 $A0c027a94ce378["to"] = floatval(strip_tags($A0c027a94ce378["to"]));
 if( empty($A0c027a94ce378["from"]) && empty($A0c027a94ce378["to"]) ) 
 {
    unset($A0c027a94ce376["search_ranges"][$A0c027a94ce377]);
    continue;
 }

   }
    }

    break;
 case self::typeCheckboxGroup:
 case self::typeRadioGroup:
    $this->input->clean_array($dd2628fc, array( "group_one_row" => TYPE_BOOL ), $A0c027a94ce376);
    break;
  }
  return $A0c027a94ce376;
    }

    public function prepareSaveDataByOwner($e22312179bf43e615, $a28040373f54ac, $Ha860179e2280, $ba2b4c39cb12a7905 = "insert", $W1d7ed985091ce97933 = false, $bea63f5b89 = "id")
    {
  if( !empty($e22312179bf43e615) && !empty($a28040373f54ac) && !empty($Ha860179e2280) ) 
  {
 if( !is_array($e22312179bf43e615) ) 
 {
    $e22312179bf43e615 = array( $e22312179bf43e615 );
 }

 $Ha860179e2280 = $this->_array_transparent($Ha860179e2280, $this->ownerColumn);
 $A0c027a94ce379 = array(  );
 foreach( $e22312179bf43e615 as $A0c027a94ce380 ) 
 {
    if( 0 < $A0c027a94ce380 ) 
    {
   $A0c027a94ce379[$A0c027a94ce380] = $this->prepareSaveData(isset($a28040373f54ac[$A0c027a94ce380]) ? $a28040373f54ac[$A0c027a94ce380] : array(  ), isset($Ha860179e2280[$A0c027a94ce380]) ? $Ha860179e2280[$A0c027a94ce380] : array(  ), $ba2b4c39cb12a7905, $W1d7ed985091ce97933, $bea63f5b89);
    }

 }
 return $A0c027a94ce379;
  }

  return array(  );
    }

    public function prepareSaveDataByID($Ibe2e9927da42dffd5, $db9d951c, $fa0d49fbb075923433 = "insert", $D97262a506580e2b9 = false, $bc8ff0cb83 = "id")
    {
  if( !empty($Ibe2e9927da42dffd5) && !empty($db9d951c) ) 
  {
 return $this->prepareSaveData($Ibe2e9927da42dffd5, $db9d951c, $fa0d49fbb075923433, $D97262a506580e2b9, $bc8ff0cb83);
  }

  if( $D97262a506580e2b9 ) 
  {
 return array(  );
  }

  return $fa0d49fbb075923433 == "insert" ? array( "fields" => "", "values" => "" ) : "";
    }

    public function prepareSearchQuery($b772c1b, $b02f4fdb2930585a = false, $d48fd9c9, $L076b07475d2a3a1, $bf599d63e62 = "data_field")
    {
  if( empty($d48fd9c9) || empty($b772c1b) ) 
  {
 return "";
  }

  $A0c027a94ce381 = array(  );
  if( $this->checkChildren() ) 
  {
 $A0c027a94ce382 = $this->getChildrenByParents($d48fd9c9, $b772c1b, true, false, false);
  }

  foreach( $d48fd9c9 as $A0c027a94ce383 ) 
  {
 if( !isset($b772c1b[$A0c027a94ce383[$bf599d63e62]]) ) 
 {
    continue;
 }

 $A0c027a94ce384 = $b772c1b[$A0c027a94ce383[$bf599d63e62]];
 $A0c027a94ce385 = $L076b07475d2a3a1 . $this->datafield_prefix . $A0c027a94ce383[$bf599d63e62];
 switch( $A0c027a94ce383["type"] ) 
 {
    case self::typeSelect:
   $this->input->clean($A0c027a94ce384, is_array($A0c027a94ce384) ? TYPE_ARRAY_UINT : TYPE_UINT);
   if( empty($A0c027a94ce384) || empty($A0c027a94ce383["multi"]) ) 
   {
 continue;
   }

   if( !is_array($A0c027a94ce384) ) 
   {
 $A0c027a94ce384 = array( $A0c027a94ce384 );
   }

   $A0c027a94ce386 = array(  );
   if( $A0c027a94ce383["parent"] ) 
   {
 foreach( $A0c027a94ce384 as $A0c027a94ce387 ) 
 {
    if( !empty($A0c027a94ce382[$A0c027a94ce383["id"]][$A0c027a94ce387]) ) 
    {
 $A0c027a94ce388 =& $A0c027a94ce382[$A0c027a94ce383["id"]][$A0c027a94ce387];
 if( !empty($b02f4fdb2930585a[$A0c027a94ce388[$bf599d63e62]]) ) 
 {
     $A0c027a94ce389 = $b02f4fdb2930585a[$A0c027a94ce388[$bf599d63e62]];
     if( is_array($A0c027a94ce389) ) 
     {
   if( !empty($A0c027a94ce389[$A0c027a94ce388["id"]]) ) 
   {
  $A0c027a94ce389 = $A0c027a94ce389[$A0c027a94ce388["id"]];
  $this->input->clean($A0c027a94ce389, TYPE_ARRAY_UINT);
  if( !empty($A0c027a94ce389) ) 
  {
     $A0c027a94ce386[] = "(" . $A0c027a94ce385 . " = " . $A0c027a94ce387 . " AND " . $L076b07475d2a3a1 . $this->datafield_prefix . $A0c027a94ce388[$bf599d63e62] . (1 < sizeof($A0c027a94ce389) ? " IN (" . join(",", $A0c027a94ce389) . ")" : " = " . current($A0c027a94ce389)) . ")";
  }

   }
   else
   {
  $A0c027a94ce386[] = "(" . $A0c027a94ce385 . " = " . $A0c027a94ce387 . ")";
   }

     }
     else
     {
   $this->input->clean($A0c027a94ce389, TYPE_UINT);
   if( !empty($A0c027a94ce389) ) 
   {
  $A0c027a94ce386[] = "(" . $A0c027a94ce385 . " = " . $A0c027a94ce387 . " AND " . $L076b07475d2a3a1 . $this->datafield_prefix . $A0c027a94ce388[$bf599d63e62] . " = " . $A0c027a94ce389 . ")";
   }

     }

 }
 else
 {
     $A0c027a94ce386[] = "(" . $A0c027a94ce385 . " = " . $A0c027a94ce387 . ")";
 }

    }
    else
    {
 $A0c027a94ce386[] = "(" . $A0c027a94ce385 . " = " . $A0c027a94ce387 . ")";
    }

 }
   }
   else
   {
 $A0c027a94ce386[] = $A0c027a94ce385 . (1 < sizeof($A0c027a94ce384) ? " IN (" . join(",", $A0c027a94ce384) . ")" : " = " . current($A0c027a94ce384));
   }

   if( !empty($A0c027a94ce386) ) 
   {
 $A0c027a94ce381[] = sizeof($A0c027a94ce386) == 1 ? current($A0c027a94ce386) : "(" . join(" OR ", $A0c027a94ce386) . ")";
   }

   break;
    case self::typeRadioGroup:
   if( empty($A0c027a94ce384) || empty($A0c027a94ce383["multi"]) ) 
   {
 continue;
   }

   if( is_array($A0c027a94ce384) ) 
   {
 $this->input->clean($A0c027a94ce384, TYPE_ARRAY_UINT);
   }
   else
   {
 $this->input->clean($A0c027a94ce384, TYPE_UINT);
 if( empty($A0c027a94ce384) ) 
 {
    continue;
 }

 $A0c027a94ce384 = array( $A0c027a94ce384 );
   }

   $A0c027a94ce381[] = $A0c027a94ce385 . (1 < sizeof($A0c027a94ce384) ? " IN (" . join(",", $A0c027a94ce384) . ")" : " = " . current($A0c027a94ce384));
   break;
    case self::typeCheckboxGroup:
    case self::typeSelectMulti:
   if( is_array($A0c027a94ce384) ) 
   {
 $this->input->clean($A0c027a94ce384, TYPE_ARRAY_UINT);
 $A0c027a94ce384 = intval(array_sum($A0c027a94ce384));
   }
   else
   {
 $this->input->clean($A0c027a94ce384, TYPE_UINT);
   }

   if( empty($A0c027a94ce384) || empty($A0c027a94ce383["multi"]) ) 
   {
 continue;
   }

   $A0c027a94ce381[] = "(" . $A0c027a94ce385 . ($this->db->isPgSQL() ? "::integer" : "") . " & " . $A0c027a94ce384 . ")!=0";
   break;
    case self::typeNumber:
    case self::typeRange:
   $A0c027a94ce386 = array(  );
   if( !is_array($A0c027a94ce384) ) 
   {
 $A0c027a94ce384 = array( "r" => array( $A0c027a94ce384 ) );
   }

   $this->input->clean_array($A0c027a94ce384, array( "f" => TYPE_PRICE, "t" => TYPE_PRICE, "r" => TYPE_ARRAY_UINT ));
   if( $this->searchRanges && !empty($A0c027a94ce383["search_ranges"]) && !empty($A0c027a94ce384["r"]) ) 
   {
 foreach( $A0c027a94ce384["r"] as $A0c027a94ce390 ) 
 {
    if( isset($A0c027a94ce383["search_ranges"][$A0c027a94ce390]) ) 
    {
 $A0c027a94ce391 = $A0c027a94ce383["search_ranges"][$A0c027a94ce390];
 $A0c027a94ce386[] = $A0c027a94ce391["from"] ? " " . $A0c027a94ce385 . " >= " . $A0c027a94ce391["from"] . ($A0c027a94ce391["to"] ? " AND " . $A0c027a94ce385 . " <= " . $A0c027a94ce391["to"] : "") : $A0c027a94ce385 . " <= " . $A0c027a94ce391["to"];
    }

 }
   }

   $A0c027a94ce392 = $A0c027a94ce384["f"];
   $A0c027a94ce393 = $A0c027a94ce384["t"];
   if( 0 < $A0c027a94ce392 || 0 < $A0c027a94ce393 ) 
   {
 if( 0 < $A0c027a94ce392 && 0 < $A0c027a94ce393 && $A0c027a94ce393 <= $A0c027a94ce392 ) 
 {
    $A0c027a94ce392 = 0;
 }

 $A0c027a94ce386[] = 0 < $A0c027a94ce392 ? " " . $A0c027a94ce385 . " >= " . $A0c027a94ce392 . (0 < $A0c027a94ce393 ? " AND " . $A0c027a94ce385 . " <= " . $A0c027a94ce393 : "") : $A0c027a94ce385 . " <= " . $A0c027a94ce393;
   }

   if( !empty($A0c027a94ce386) ) 
   {
 $A0c027a94ce381[] = sizeof($A0c027a94ce386) == 1 ? current($A0c027a94ce386) : "((" . join(") OR (", $A0c027a94ce386) . "))";
   }

   if( $A0c027a94ce383["parent"] ) 
   {
   }

   break;
    case self::typeCheckbox:
   if( !empty($A0c027a94ce384) ) 
   {
 $A0c027a94ce381[] = $A0c027a94ce385 . " = 1";
   }

   break;
    case self::typeRadioYesNo:
   if( empty($A0c027a94ce384) ) 
   {
 break;
   }

   if( is_array($A0c027a94ce384) ) 
   {
 if( isset($A0c027a94ce384[1]) || isset($A0c027a94ce384[2]) ) 
 {
    if( sizeof($A0c027a94ce384) == 2 ) 
    {
 $A0c027a94ce381[] = $A0c027a94ce385 . " IN (1,2)";
    }
    else
    {
 $A0c027a94ce381[] = $A0c027a94ce385 . " = " . (isset($A0c027a94ce384[1]) ? 1 : 2);
    }

 }

   }
   else
   {
 $A0c027a94ce381[] = $A0c027a94ce385 . " = " . ($A0c027a94ce384 == 2 ? 2 : 1);
   }

 }
  }
  return !empty($A0c027a94ce381) ? join(" AND ", $A0c027a94ce381) : "";
    }

    public function prepareTemplateByCacheKeys($r19a6df76, $t880f5a6, $M13e00a, $s548538c = array(  ), $aa33964f7d61db = "id")
    {
  if( empty($M13e00a) ) 
  {
 return array(  );
  }

  if( $this->checkChildren() ) 
  {
 $A0c027a94ce394 = $this->getChildrenByParents($t880f5a6, false, true, false, false);
  }

  $A0c027a94ce395 = array(  );
  if( !empty($t880f5a6) ) 
  {
 foreach( $t880f5a6 as $A0c027a94ce396 ) 
 {
    $A0c027a94ce397 = $A0c027a94ce396["cache_key"];
    if( !isset($r19a6df76[$A0c027a94ce396[$aa33964f7d61db]]) ) 
    {
   $A0c027a94ce395["{" . $A0c027a94ce397 . "}"] = "-";
   continue;
    }

    $A0c027a94ce398 = $r19a6df76[$A0c027a94ce396[$aa33964f7d61db]];
    switch( $A0c027a94ce396["type"] ) 
    {
   case self::typeRadioGroup:
 foreach( $A0c027a94ce396["multi"] as $A0c027a94ce399 ) 
 {
    if( $A0c027a94ce398 == $A0c027a94ce399["value"] ) 
    {
 $A0c027a94ce398 = $A0c027a94ce399["name"];
 break;
    }

 }
 break;
   case self::typeRadioYesNo:
 $A0c027a94ce398 = $A0c027a94ce398 == 2 ? "Да" : $A0c027a94ce398 == 1 ? "Нет" : "-";
 break;
   case self::typeCheckboxGroup:
 $A0c027a94ce400 = array(  );
 foreach( $A0c027a94ce396["multi"] as $A0c027a94ce399 ) 
 {
    if( in_array($A0c027a94ce399["value"], $A0c027a94ce398) ) 
    {
 $A0c027a94ce400[] = $A0c027a94ce399["name"];
    }

 }
 $A0c027a94ce398 = join(", ", $A0c027a94ce400);
 break;
   case self::typeCheckbox:
 $A0c027a94ce398 = $A0c027a94ce398 ? "Да" : "Нет";
 break;
   case self::typeSelect:
 if( $A0c027a94ce396["parent"] ) 
 {
    $A0c027a94ce401 = "";
    foreach( $A0c027a94ce396["multi"] as $A0c027a94ce399 ) 
    {
 if( $A0c027a94ce398 == $A0c027a94ce399["value"] ) 
 {
     $A0c027a94ce401 = $A0c027a94ce399["name"];
     break;
 }

    }
    if( !empty($A0c027a94ce398) && isset($A0c027a94ce394[$A0c027a94ce396[$aa33964f7d61db]]) ) 
    {
 $A0c027a94ce402 = current($A0c027a94ce394[$A0c027a94ce396[$aa33964f7d61db]]);
 foreach( $A0c027a94ce402["multi"] as $A0c027a94ce399 ) 
 {
     if( $A0c027a94ce399["value"] == $A0c027a94ce402["value"] ) 
     {
   $A0c027a94ce401 .= ", " . $A0c027a94ce399["name"];
   break;
     }

 }
    }

    $A0c027a94ce398 = $A0c027a94ce401;
 }
 else
 {
    foreach( $A0c027a94ce396["multi"] as $A0c027a94ce399 ) 
    {
 if( $A0c027a94ce398 == $A0c027a94ce399["value"] && $A0c027a94ce398 != 0 ) 
 {
     $A0c027a94ce398 = $A0c027a94ce399["name"];
     break;
 }

    }
 }

 break;
   case self::typeInputText:
   case self::typeTextarea:
   case self::typeNumber:
   case self::typeRange:
    }
    $A0c027a94ce395["{" . $A0c027a94ce397 . "}"] = $A0c027a94ce398;
 }
  }

  $A0c027a94ce403 = array(  );
  $A0c027a94ce395 = array_merge($A0c027a94ce395, $s548538c);
  foreach( $M13e00a as $A0c027a94ce404 => $A0c027a94ce405 ) 
  {
 $A0c027a94ce403[$A0c027a94ce404] = strtr($A0c027a94ce405, $A0c027a94ce395);
  }
  return $A0c027a94ce403;
    }

    protected function prepareSaveData($b6ed34, $p0322b7886259c, $bc82a558aa2a5, $fc7d105bc93d98e = false, $z6c0e7bf6c8fe5b758 = "id")
    {
  $A0c027a94ce406 = $bc82a558aa2a5 == "update";
  $A0c027a94ce407 = "";
  $A0c027a94ce408 = "";
  $A0c027a94ce409 = array(  );
  reset($p0322b7886259c);
  while( list(, $A0c027a94ce410) = each($p0322b7886259c) ) 
  {
 $A0c027a94ce411 =& $A0c027a94ce410["type"];
 $A0c027a94ce412 = isset($b6ed34[$A0c027a94ce410[$z6c0e7bf6c8fe5b758]]) ? $b6ed34[$A0c027a94ce410[$z6c0e7bf6c8fe5b758]] : false;
 $A0c027a94ce413 = "";
 $A0c027a94ce414 = $this->datafield_prefix . $A0c027a94ce410["data_field"];
 if( $this->isStoringBits($A0c027a94ce411) ) 
 {
    $A0c027a94ce413 = !$A0c027a94ce412 ? 0 : array_sum($A0c027a94ce412);
 }
 else
 {
    if( $this->isMulti($A0c027a94ce411) ) 
    {
   if( $A0c027a94ce412 ) 
   {
 foreach( $A0c027a94ce410["multi"] as $A0c027a94ce415 ) 
 {
    if( $A0c027a94ce415["value"] == $A0c027a94ce412 ) 
    {
 $A0c027a94ce413 = $A0c027a94ce415["value"];
 break;
    }

 }
   }
   else
   {
 $A0c027a94ce413 = "0";
   }

    }
    else
    {
   if( $A0c027a94ce411 == self::typeNumber ) 
   {
 $A0c027a94ce412 = str_replace(array( ",", " " ), array( ".", "" ), strval($A0c027a94ce412));
 $A0c027a94ce413 = doubleval($A0c027a94ce412);
   }
   else
   {
 if( $A0c027a94ce411 == self::typeRange ) 
 {
    if( $A0c027a94ce412 ) 
    {
 if( !is_numeric($A0c027a94ce412) ) 
 {
     $this->errors->set(_t("dynprops", "Для ввода в поле <strong>[title]</strong> допускаются только цифры", array( "title" => $A0c027a94ce410["title"] )));
     break;
 }

 if( $A0c027a94ce412 < min($A0c027a94ce410["start"], $A0c027a94ce410["end"]) || max($A0c027a94ce410["start"], $A0c027a94ce410["end"]) < $A0c027a94ce412 ) 
 {
     $this->errors->set(_t("dynprops", "Значение поля <strong>[title]</strong> выходит за границу диапазона", array( "title" => $A0c027a94ce410["title"] )));
 }

    }

    $A0c027a94ce413 = is_array($A0c027a94ce412) ? intval(array_sum($A0c027a94ce412)) : strip_tags($A0c027a94ce412);
    if( !$fc7d105bc93d98e ) 
    {
 $A0c027a94ce413 = $this->db->str2sql($A0c027a94ce413);
    }

 }
 else
 {
    $A0c027a94ce413 = is_array($A0c027a94ce412) ? intval(array_sum($A0c027a94ce412)) : strip_tags($A0c027a94ce412);
    if( !$fc7d105bc93d98e ) 
    {
 $A0c027a94ce413 = $this->db->str2sql($A0c027a94ce413);
    }

 }

   }

    }

 }

 if( $fc7d105bc93d98e ) 
 {
    $A0c027a94ce409[$A0c027a94ce414] = $A0c027a94ce413;
 }
 else
 {
    $A0c027a94ce407 .= ", " . $A0c027a94ce414;
    $A0c027a94ce408 .= ($A0c027a94ce406 ? ", " . $A0c027a94ce414 . " = " : ", ") . $A0c027a94ce413;
 }

  }
  if( $fc7d105bc93d98e ) 
  {
 return $A0c027a94ce409;
  }

  return $A0c027a94ce406 ? $A0c027a94ce408 : array( "fields" => $A0c027a94ce407, "values" => $A0c027a94ce408 );
    }

    protected function getDataField($d596bc0e, $O14b00a, $f332fe1921be85ef = false)
    {
  $A0c027a94ce416 = false;
  $A0c027a94ce417 = $this->isStoringText($O14b00a);
  $A0c027a94ce418 = " AND D.type " . ($A0c027a94ce417 ? "" : "NOT") . " IN(" . join(",", $this->getTextTypes()) . ")";
  if( !empty($f332fe1921be85ef) ) 
  {
 if( $this->isInheritParticular() ) 
 {
    $A0c027a94ce419 = "SELECT DI.data_field   FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DI   WHERE D.parent_id = :parent AND D.id = DI.dynprop_id";
 }
 else
 {
    $A0c027a94ce419 = "SELECT D.data_field   FROM " . $this->tblDynprops . " D   WHERE D.parent_id = :parent";
 }

 $A0c027a94ce420 = $this->db->one_data($A0c027a94ce419 . $A0c027a94ce418, array( ":parent" => $f332fe1921be85ef["id"] ));
 if( !empty($A0c027a94ce420) ) 
 {
    return $A0c027a94ce420;
 }

  }

  if( $this->inherit ) 
  {
 if( $this->isOwnerTableNestedSets() ) 
 {
    $A0c027a94ce421 = $this->db->one_array("SELECT O.numleft, O.numright FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $d596bc0e ));
    if( !empty($A0c027a94ce421) ) 
    {
   $A0c027a94ce422 = $this->db->select_one_column("SELECT O." . $this->ownerTable_ID . " FROM " . $this->ownerTable . " O    WHERE (O.numleft < " . $A0c027a94ce421["numleft"] . " AND O.numright > " . $A0c027a94ce421["numright"] . ") OR    (O.numleft > " . $A0c027a94ce421["numleft"] . " AND O.numright < " . $A0c027a94ce421["numright"] . ") ");
    }

    if( empty($A0c027a94ce422) ) 
    {
   $A0c027a94ce422 = array(  );
    }

    $A0c027a94ce422[] = $d596bc0e;
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce423 = " SELECT DN.data_field  FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DN  WHERE DN." . $this->ownerColumn . " IN (" . join(",", $A0c027a94ce422) . ") AND DN.dynprop_id = D.id " . $A0c027a94ce418 . "  GROUP BY DN.data_field  ORDER BY DN.data_field ASC";
    }
    else
    {
   $A0c027a94ce423 = " SELECT D.data_field  FROM " . $this->tblDynprops . " D  WHERE D." . $this->ownerColumn . " IN (" . join(",", $A0c027a94ce422) . ") " . $A0c027a94ce418 . "  GROUP BY D.data_field  ORDER BY D.data_field ASC";
    }

 }
 else
 {
    $A0c027a94ce424 = intval($this->ownerTableType);
    $A0c027a94ce425 = $this->db->getAdjacencyListParentsID($this->ownerTable, $d596bc0e, $A0c027a94ce424, $this->ownerTable_ID, $this->ownerTable_PID);
    $A0c027a94ce426 = $this->db->getAdjacencyListChildrensID($this->ownerTable, $d596bc0e, $A0c027a94ce424, $this->ownerTable_ID, $this->ownerTable_PID);
    $A0c027a94ce422 = array_merge($A0c027a94ce425, $A0c027a94ce426);
    $A0c027a94ce422[] = $d596bc0e;
    if( $this->isInheritParticular() ) 
    {
   $A0c027a94ce423 = " SELECT DN.data_field  FROM " . $this->tblDynprops . " D, " . $this->tblIn . " DN  WHERE DN." . $this->ownerColumn . " IN (" . join(",", $A0c027a94ce422) . ") AND DN.dynprop_id = D.id " . $A0c027a94ce418 . "  GROUP BY DN.data_field  ORDER BY DN.data_field ASC";
    }
    else
    {
   $A0c027a94ce423 = " SELECT D.data_field  FROM " . $this->tblDynprops . " D  WHERE D." . $this->ownerColumn . " IN (" . join(",", $A0c027a94ce422) . ") " . $A0c027a94ce418 . "  GROUP BY D.data_field  ORDER BY D.data_field ASC";
    }

 }

  }
  else
  {
 $A0c027a94ce423 = " SELECT D.data_field FROM " . $this->tblDynprops . " D WHERE D." . $this->ownerColumn . " = " . $d596bc0e . " " . $A0c027a94ce418 . " ORDER BY D.data_field ASC";
  }

  $A0c027a94ce427 = $this->db->select_one_column($A0c027a94ce423);
  $A0c027a94ce427 = array_unique($A0c027a94ce427);
  if( $A0c027a94ce417 ) 
  {
 if( !$A0c027a94ce427 ) 
 {
    $A0c027a94ce416 = $this->datafield_text_first;
 }
 else
 {
    $A0c027a94ce428 = $this->datafield_text_first;
    foreach( $A0c027a94ce427 as $A0c027a94ce429 ) 
    {
   if( $A0c027a94ce429 == $A0c027a94ce428 ) 
   {
 $A0c027a94ce428++;
 continue;
   }

   $A0c027a94ce416 = $A0c027a94ce428;
   break;
    }
    if( !$A0c027a94ce416 ) 
    {
   $A0c027a94ce416 = intval(max($A0c027a94ce427) + 1);
    }

    $A0c027a94ce430 = $this->datafield_text_first - 1;
    $A0c027a94ce430 += sizeof($A0c027a94ce427) + 1;
    if( $this->datafield_text_last < $A0c027a94ce430 ) 
    {
   $this->errors->set(_t("dynprops", "Достигнут лимит количества текстовых полей"));
   return false;
    }

 }

  }
  else
  {
 if( !$A0c027a94ce427 ) 
 {
    $A0c027a94ce416 = $this->datafield_int_first;
 }
 else
 {
    $A0c027a94ce428 = $this->datafield_int_first;
    foreach( $A0c027a94ce427 as $A0c027a94ce429 ) 
    {
   if( $A0c027a94ce429 == $A0c027a94ce428 ) 
   {
 $A0c027a94ce428++;
 continue;
   }

   $A0c027a94ce416 = $A0c027a94ce428;
   break;
    }
    if( !$A0c027a94ce416 ) 
    {
   $A0c027a94ce416 = max($A0c027a94ce427) + 1;
    }

    $A0c027a94ce430 = sizeof($A0c027a94ce427) + 1;
    if( $this->datafield_int_last < $A0c027a94ce430 ) 
    {
   $this->errors->set(_t("dynprops", "Достигнут лимит количества числовых полей"));
   return false;
    }

 }

  }

  return $A0c027a94ce416;
    }

    protected function getOwnerParentsID($l1bbe3b62439d1, $dff0c6964c4b635 = false)
    {
  if( $this->isOwnerTableNestedSets() ) 
  {
 if( $this->ownerTableLevels == 2 && !empty($this->ownerTable_PID) ) 
 {
    $l1bbe3b62439d1 = $this->db->one_data("SELECT O." . $this->ownerTable_PID . " FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $l1bbe3b62439d1 ));
    return array( $l1bbe3b62439d1 );
 }

 if( empty($dff0c6964c4b635) ) 
 {
    $dff0c6964c4b635 = $this->db->one_array("SELECT O.numleft, O.numright FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $l1bbe3b62439d1 ));
 }

 if( !empty($dff0c6964c4b635) ) 
 {
    return $this->db->select_one_column("SELECT O." . $this->ownerTable_ID . " FROM " . $this->ownerTable . " O WHERE O.numleft < :numleft   AND O.numright > :numright", array( ":numleft" => $dff0c6964c4b635["numleft"], ":numright" => $dff0c6964c4b635["numright"] ));
 }

 return !empty($A0c027a94ce431) ? $l1bbe3b62439d1 : array(  );
  }

  $A0c027a94ce432 = intval($this->ownerTableType);
  return $this->db->getAdjacencyListParentsID($this->ownerTable, $l1bbe3b62439d1, $A0c027a94ce432, $this->ownerTable_ID, $this->ownerTable_PID);
    }

    protected function getOwnerChildrensID($f417ca50b, $f520190 = false)
    {
  if( $this->isOwnerTableNestedSets() ) 
  {
 if( empty($f520190) ) 
 {
    $f520190 = $this->db->one_array("SELECT O.numleft, O.numright FROM " . $this->ownerTable . " O  WHERE O." . $this->ownerTable_ID . " = :id", array( ":id" => $f417ca50b ));
 }

 if( !empty($f520190) ) 
 {
    return $this->db->select_one_column("SELECT O." . $this->ownerTable_ID . " FROM " . $this->ownerTable . " O WHERE O.numleft > :numleft   AND O.numright < :numright", array( ":numleft" => $f520190["numleft"], ":numright" => $f520190["numright"] ));
 }

 return !empty($A0c027a94ce433) ? $f417ca50b : array(  );
  }

  return $this->db->getAdjacencyListChildrensID($this->ownerTable, $f417ca50b, intval($this->ownerTableType), $this->ownerTable_ID, $this->ownerTable_PID);
    }

    protected function getOwnerData($a56e8419, $L8075799d8b86e225b6 = array(  ))
    {
    }

    protected function getOwnersOptions()
    {
  $A0c027a94ce434 = array(  );
  if( $this->ownerFromArray ) 
  {
 $A0c027a94ce434[] = "<option value=\"" . $this->ownerTable["id"] . "\">" . $this->ownerTable["title"] . "</option>";
  }
  else
  {
 if( $this->inherit ) 
 {
    if( $this->isOwnerTableNestedSets() ) 
    {
   $A0c027a94ce435 = $this->db->select("SELECT O." . $this->ownerTable_ID . " as id, (O.numlevel - 1) as lvl, O." . $this->ownerTable_Title . " as title    FROM " . $this->ownerTable . " O WHERE O." . $this->ownerTable_ID . " > 1    ORDER BY O.numleft");
   if( !empty($A0c027a94ce435) ) 
   {
 foreach( $A0c027a94ce435 as $A0c027a94ce436 ) 
 {
    $A0c027a94ce434[] = "<option value=\"" . $A0c027a94ce436["id"] . "\">" . str_repeat("&nbsp;", $A0c027a94ce436["lvl"] * 3) . $A0c027a94ce436["title"] . "</option>";
 }
   }

    }

 }
 else
 {
    $A0c027a94ce435 = $this->db->select("SELECT O." . $this->ownerTable_ID . " as id, O." . $this->ownerTable_Title . " as title FROM " . $this->ownerTable . " O ORDER BY O.num");
    if( !empty($A0c027a94ce435) ) 
    {
   foreach( $A0c027a94ce435 as $A0c027a94ce436 ) 
   {
 $A0c027a94ce434[] = "<option value=\"" . $A0c027a94ce436["id"] . "\">" . $A0c027a94ce436["title"] . "</option>";
   }
    }

 }

  }

  return join("", $A0c027a94ce434);
    }

    protected function getNum($e165e1bba)
    {
  if( $this->isInheritParticular() ) 
  {
 $A0c027a94ce437 = (int) $this->db->one_data("SELECT MAX(num) FROM " . $this->tblIn . " WHERE " . $this->ownerColumn . " = :id", array( ":id" => $e165e1bba ));
  }
  else
  {
 $A0c027a94ce437 = (int) $this->db->one_data("SELECT MAX(num) FROM " . $this->tblDynprops . " WHERE " . $this->ownerColumn . " = :id", array( ":id" => $e165e1bba ));
  }

  return $A0c027a94ce437 + 1;
    }

    public static function getTypeTitle($K4f701265c37)
    {
  switch( $K4f701265c37 ) 
  {
 case self::typeInputText:
    return _t("dp", "Однострочное текстовое поле");
 case self::typeTextarea:
    return _t("dp", "Многострочное текстовое поле");
 case self::typeWysiwyg:
    return _t("dp", "Текстовый редактор");
 case self::typeRadioYesNo:
    return _t("dp", "Выбор Да/Нет");
 case self::typeCheckbox:
    return _t("dp", "Флаг");
 case self::typeSelect:
    return _t("dp", "Выпадающий список");
 case self::typeSelectMulti:
    return _t("dp", "Список с мультивыбором (ctrl)");
 case self::typeRadioGroup:
    return _t("dp", "Группа св-в с единичным выбором");
 case self::typeCheckboxGroup:
    return _t("dp", "Группа св-в с множественным выбором");
 case self::typeRange:
    return _t("dp", "Диапазон");
 case self::typeNumber:
    return _t("dp", "Число");
  }
  return "?";
    }

    protected function bit2source($ea0aebcaab0)
    {
  $A0c027a94ce438 = strrev(decbin($ea0aebcaab0));
  $A0c027a94ce439 = strlen($A0c027a94ce438);
  $A0c027a94ce440 = array(  );
  for( $A0c027a94ce441 = 0; $A0c027a94ce441 < $A0c027a94ce439; $A0c027a94ce441++ ) 
  {
 if( $A0c027a94ce438[$A0c027a94ce441] ) 
 {
    $A0c027a94ce440[] = pow(2, $A0c027a94ce441);
 }

  }
  return $A0c027a94ce440;
    }

    protected function isStoringBits($V06f101)
    {
  return in_array($V06f101, array( self::typeCheckboxGroup, self::typeSelectMulti ));
    }

    protected function isStoringText($ead8dc)
    {
  return in_array($ead8dc, $this->getTextTypes());
    }

    protected function isMulti($f0e071)
    {
  return in_array($f0e071, array( self::typeCheckboxGroup, self::typeSelectMulti, self::typeRadioGroup, self::typeSelect ));
    }

    protected function getMulti($l21900d3)
    {
  if( empty($l21900d3) ) 
  {
 return array(  );
  }

  if( is_array($l21900d3) ) 
  {
 $l21900d3 = "dynprop_id IN (" . join(",", array_unique($l21900d3)) . ")";
  }
  else
  {
 $l21900d3 = "dynprop_id = " . $l21900d3;
  }

  return $this->db->select("SELECT dynprop_id, name" . ($this->langs ? "_" . LNG . " as name" : "") . ", value, num   FROM " . $this->tblMulti . " WHERE " . $l21900d3 . " ORDER BY num");
    }

    protected function isParent($T033d6493283904ef2c)
    {
  return in_array($T033d6493283904ef2c, array( self::typeCheckbox, self::typeSelect, self::typeRadioGroup, self::typeCountry, self::typeState ));
    }

    protected function checkChildren()
    {
  return !empty($this->typesAllowedParent);
    }

    protected function hasExtra($d1fe173d08e959397)
    {
  return in_array($d1fe173d08e959397, $this->typesExtra);
    }

    protected function getTextTypes()
    {
  return array( self::typeInputText, self::typeTextarea, self::typeWysiwyg );
    }

    protected function isOwnerTableNestedSets()
    {
  static $b1c8dc4089fe42649;
  $b1c8dc4089fe42649 = $this->ownerTableType == "ns";
    }

    public function isInheritParticular()
    {
  return $this->inherit === 2;
    }

    protected function updateCache($nb6a478474c19ac9582, $A7ef81)
    {
  if( empty($this->cache_method) ) 
  {
 return NULL;
  }

  //bff::i()->callModule($this->cache_method, array( $A7ef81["owner"], $A7ef81["id"], $nb6a478474c19ac9582 ));
    }

}

