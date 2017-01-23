<?php 

class SvcModule extends SvcModuleBase
{
    public function manage()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce146 = $this->getSvcTypes();
        $A0c027a94ce147 = $this->input->postget("act", TYPE_STR);
        if( !empty($A0c027a94ce147) || Request::isPOST() ) 
        {
            $A0c027a94ce148 = array(  );
            switch( $A0c027a94ce147 ) 
            {
                case "add":
                    $A0c027a94ce149 = $this->input->post("save", TYPE_BOOL);
                    $A0c027a94ce150 = $this->validateSvcData(0, $A0c027a94ce149);
                    if( $A0c027a94ce149 && $this->errors->no() ) 
                    {
                        $A0c027a94ce151 = $this->model->svcSave(0, $A0c027a94ce150);
                        if( $A0c027a94ce151 <= 0 ) 
                        {
                            $this->errors->impossible();
                        }

                    }

                    $A0c027a94ce150["id"] = 0;
                    $A0c027a94ce150["types"] = $A0c027a94ce146;
                    $A0c027a94ce150["types_options"] = HTML::selectOptions($A0c027a94ce146, $A0c027a94ce150["type"], false, "id", "title_select");
                    $A0c027a94ce150["modules"] = HTML::selectOptions(bff::i()->getModulesList(), $A0c027a94ce150["module"]);
                    $A0c027a94ce148["form"] = $this->viewPHP($A0c027a94ce150, "admin.form", $this->module_dir_tpl_core);
                    break;
                case "edit":
                    $A0c027a94ce149 = $this->input->post("save", TYPE_BOOL);
                    $A0c027a94ce151 = $this->input->postget("id", TYPE_UINT);
                    if( !$A0c027a94ce151 ) 
                    {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if( $A0c027a94ce149 ) 
                    {
                        $A0c027a94ce150 = $this->validateSvcData($A0c027a94ce151, $A0c027a94ce149);
                        if( $this->errors->no() ) 
                        {
                            $this->model->svcSave($A0c027a94ce151, $A0c027a94ce150);
                        }

                        $A0c027a94ce150["id"] = $A0c027a94ce151;
                    }
                    else
                    {
                        $A0c027a94ce150 = $this->model->svcData($A0c027a94ce151);
                        if( empty($A0c027a94ce150) ) 
                        {
                            $this->errors->unknownRecord();
                            break;
                        }

                    }

                    $A0c027a94ce150["types"] = $A0c027a94ce146;
                    $A0c027a94ce150["types_options"] = HTML::selectOptions($A0c027a94ce146, $A0c027a94ce150["type"], false, "id", "title_select");
                    $A0c027a94ce150["modules"] = HTML::selectOptions(bff::i()->getModulesList(), $A0c027a94ce150["module"]);
                    $A0c027a94ce148["form"] = $this->viewPHP($A0c027a94ce150, "admin.form", $this->module_dir_tpl_core);
                    break;
                case "rotate":
                    $A0c027a94ce152 = $this->input->post("tab", TYPE_UINT);
                    $this->model->svcRotate("num", "type = " . $A0c027a94ce152);
                    break;
                case "delete":
                    $A0c027a94ce151 = $this->input->postget("id", TYPE_UINT);
                    if( !$A0c027a94ce151 ) 
                    {
                        $this->errors->impossible();
                        break;
                    }

                    $A0c027a94ce150 = $this->model->svcData($A0c027a94ce151, array( "id" ));
                    if( empty($A0c027a94ce150) ) 
                    {
                        $this->errors->impossible();
                        break;
                    }

                    $A0c027a94ce153 = $this->model->svcDelete($A0c027a94ce151);
                    if( !$A0c027a94ce153 ) 
                    {
                        $this->errors->impossible();
                        break;
                    }

                    break;
                default:
                    $A0c027a94ce148 = false;
            }
            if( $A0c027a94ce148 !== false && Request::isAJAX() ) 
            {
                $this->ajaxResponseForm($A0c027a94ce148);
            }

        }

        $A0c027a94ce154 = $this->input->postgetm(array( "tab" => TYPE_UINT ));
        if( empty($A0c027a94ce154["tab"]) ) 
        {
            $A0c027a94ce154["tab"] = self::TYPE_SERVICE;
        }

        $A0c027a94ce150["list"] = $this->model->svcListing($A0c027a94ce154["tab"]);
        $A0c027a94ce150["list"] = $this->viewPHP($A0c027a94ce150, "admin.listing.ajax", $this->module_dir_tpl_core);
        if( Request::isAJAX() ) 
        {
            $this->ajaxResponseForm(array( "list" => $A0c027a94ce150["list"] ));
        }

        $A0c027a94ce150["f"] = $A0c027a94ce154;
        $A0c027a94ce150["id"] = $this->input->get("id", TYPE_UINT);
        $A0c027a94ce150["act"] = $A0c027a94ce147;
        $A0c027a94ce150["types"] = $A0c027a94ce146;
        tpl::includeJS(array( "tablednd" ), true);
        return $this->viewPHP($A0c027a94ce150, "admin.listing", $this->module_dir_tpl_core);
    }

    public function validateSvcData($j712710c2, $bb89fe1f20)
    {
        $A0c027a94ce155 = $this->input->postgetm(array( "type" => TYPE_UINT, "title" => TYPE_STR, "keyword" => TYPE_STR, "module" => TYPE_STR, "module_title" => TYPE_STR ));
        if( $bb89fe1f20 ) 
        {
            if( !array_key_exists($A0c027a94ce155["type"], $this->getSvcTypes()) ) 
            {
                $this->errors->set("Тип указан некорректно", "type");
            }

            if( empty($A0c027a94ce155["title"]) ) 
            {
                $this->errors->set("Название указано некорректно", "title");
            }

            if( empty($A0c027a94ce155["keyword"]) ) 
            {
                $this->errors->set("Keyword указан некорректно", "keyword");
            }

            if( empty($A0c027a94ce155["module"]) ) 
            {
                $this->errors->set("Модуль указан некорректно", "module");
            }

            if( $this->model->svcKeywordExists($A0c027a94ce155["keyword"], $A0c027a94ce155["module"], $j712710c2) ) 
            {
                $this->errors->set("Указанный keyword уже используется", "keyword");
            }

        }

        return $A0c027a94ce155;
    }

}


