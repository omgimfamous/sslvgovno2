<?php 

class SEOModule extends SEOModuleBase
{
    public function templates($d125c61a4, $N58c3469, $e4a625 = true)
    {
        if( !$d125c61a4->haveAccessTo("seo") ) 
        {
            return $d125c61a4->showAccessDenied();
        }

        if( Request::isPOST() && $e4a625 ) 
        {
            $A0c027a94ce131 = array(  );
            switch( $this->input->postget("act", TYPE_NOTAGS) ) 
            {
                case "save":
                    $A0c027a94ce132 = $this->input->postget("page", TYPE_NOTAGS);
                    if( empty($A0c027a94ce132) || !isset($N58c3469["pages"][$A0c027a94ce132]) ) 
                    {
                        $this->errors->reloadPage();
                        break;
                    }

                    $A0c027a94ce133 = array( "mtitle" => TYPE_NOTAGS, "mkeywords" => TYPE_NOTAGS, "mdescription" => TYPE_NOTAGS );
                    if( !empty($N58c3469["pages"][$A0c027a94ce132]["fields"]) ) 
                    {
                        foreach( $N58c3469["pages"][$A0c027a94ce132]["fields"] as $A0c027a94ce134 => $A0c027a94ce135 ) 
                        {
                            if( isset($A0c027a94ce133[$A0c027a94ce134]) ) 
                            {
                                continue;
                            }

                            switch( $A0c027a94ce135["type"] ) 
                            {
                                case "text":
                                    $A0c027a94ce133[$A0c027a94ce134] = TYPE_NOTAGS;
                                    break;
                                case "textarea":
                                    $A0c027a94ce133[$A0c027a94ce134] = TYPE_STR;
                                    break;
                                case "wy":
                                    $A0c027a94ce133[$A0c027a94ce134] = TYPE_STR;
                            }
                        }
                    }

                    $A0c027a94ce133 = $this->input->postgetm_lang($A0c027a94ce133);
                    $this->metaTemplateSave($d125c61a4->module_name, $A0c027a94ce132, $A0c027a94ce133);
            }
            $this->ajaxResponseForm($A0c027a94ce131);
        }

        if( empty($N58c3469) ) 
        {
            $N58c3469 = array( "pages" => array(  ), "macros" => array(  ) );
        }

        $A0c027a94ce136 = false;
        foreach( $N58c3469["pages"] as $A0c027a94ce134 => &$A0c027a94ce135 ) 
        {
            $A0c027a94ce135["content"] = $this->metaTemplateLoad($d125c61a4->module_name, $A0c027a94ce134);
            if( empty($N58c3469["macros"]) ) 
            {
                $N58c3469["macros"] = array(  );
            }

            foreach( $N58c3469["macros"] as $A0c027a94ce137 => &$A0c027a94ce138 ) 
            {
                if( !empty($A0c027a94ce135["macros.ignore"]) && in_array($A0c027a94ce137, $A0c027a94ce135["macros.ignore"]) ) 
                {
                    continue;
                }

                if( !isset($A0c027a94ce135["macros"][$A0c027a94ce137]) ) 
                {
                    $A0c027a94ce135["macros"][$A0c027a94ce137] = $A0c027a94ce138;
                }

            }
            unset($A0c027a94ce138);
            if( !empty($A0c027a94ce135["list"]) && !isset($A0c027a94ce135["macros"]["page"]) ) 
            {
                $A0c027a94ce135["macros"]["page"] = array( "t" => "Страница списка" );
            }

            $A0c027a94ce135["macros"]["site.title"] = array( "t" => "Название сайта - " . config::sys("site.title") );
            if( !empty($A0c027a94ce135["inherit"]) ) 
            {
                $A0c027a94ce135["macros"] = array( "meta-base" => array( "t" => "Базовые настройки", "in" => true ) ) + $A0c027a94ce135["macros"];
            }

            $A0c027a94ce135["fields"] = $this->prepareCustomFields(isset($A0c027a94ce135["fields"]) ? $A0c027a94ce135["fields"] : array(  ), $A0c027a94ce139);
            if( $A0c027a94ce139 ) 
            {
                $A0c027a94ce136 = true;
            }

        }
        unset($A0c027a94ce135);
        if( !$e4a625 ) 
        {
            return $N58c3469;
        }

        unset($N58c3469["macros"]);
        $N58c3469["module"] =& $d125c61a4;
        $N58c3469["init_wy"] = $A0c027a94ce136;
        return $this->viewPHP($N58c3469, "admin.templates", $this->module_dir_tpl_core);
    }

    public function form($B65d453742b61, &$h6500b1f, $U95b3532a1d47c84214 = "", $ca7a0b5c = array(  ))
    {
        $A0c027a94ce140 = array( "module" => $B65d453742b61, "data" => $h6500b1f, "macros" => array(  ), "fields" => array(  ), "template" => false, "width" => 100, "init_wy" => false );
        if( !empty($ca7a0b5c["fields"]) ) 
        {
            $A0c027a94ce140["fields"] = $this->prepareCustomFields($ca7a0b5c["fields"], $A0c027a94ce140["init_wy"]);
        }

        if( !empty($U95b3532a1d47c84214) && is_string($U95b3532a1d47c84214) ) 
        {
            $A0c027a94ce141 = $B65d453742b61->seo_templates_edit(false);
            if( !isset($A0c027a94ce141["pages"][$U95b3532a1d47c84214]) ) 
            {
                $A0c027a94ce140["template"] = false;
            }
            else
            {
                $A0c027a94ce140["template"] = $A0c027a94ce141["pages"][$U95b3532a1d47c84214]["content"];
                $A0c027a94ce140["template_title"] = $A0c027a94ce141["pages"][$U95b3532a1d47c84214]["t"];
                $A0c027a94ce142 = $this->locale->getLanguages();
                foreach( $A0c027a94ce140["template"] as &$A0c027a94ce143 ) 
                {
                    foreach( $A0c027a94ce142 as $A0c027a94ce144 ) 
                    {
                        if( isset($A0c027a94ce143[$A0c027a94ce144]) ) 
                        {
                            $A0c027a94ce143[$A0c027a94ce144] = strtr($A0c027a94ce143[$A0c027a94ce144], array( "{meta-base}" => "<span class=\"bold\">{meta-base}</span>" ));
                        }

                    }
                }
                unset($A0c027a94ce143);
                $A0c027a94ce140["template_use"] = isset($h6500b1f["mtemplate"]) ? !empty($h6500b1f["mtemplate"]) : true;
                $A0c027a94ce140["macros"] = $A0c027a94ce141["pages"][$U95b3532a1d47c84214]["macros"];
                if( isset($A0c027a94ce140["macros"]["meta-base"]) ) 
                {
                    unset($A0c027a94ce140["macros"]["meta-base"]);
                }

                if( isset($A0c027a94ce141["pages"][$U95b3532a1d47c84214]["fields"]) ) 
                {
                    $A0c027a94ce140["fields"] = array_merge($A0c027a94ce140["fields"], $A0c027a94ce141["pages"][$U95b3532a1d47c84214]["fields"]);
                    foreach( $A0c027a94ce140["fields"] as &$A0c027a94ce143 ) 
                    {
                        if( $A0c027a94ce143["type"] == "wy" ) 
                        {
                            $A0c027a94ce140["init_wy"] = true;
                        }

                    }
                }

            }

        }

        if( !empty($ca7a0b5c["macros"]) && is_array($ca7a0b5c["macros"]) ) 
        {
            $A0c027a94ce140["macros"] = array_merge($A0c027a94ce140["macros"], $ca7a0b5c["macros"]);
        }

        $A0c027a94ce140["macros"]["site.title"] = array( "t" => "Название сайта - " . config::sys("site.title") );
        if( !empty($ca7a0b5c["width"]) ) 
        {
            $A0c027a94ce140["width"] = strval($ca7a0b5c["width"]);
        }

        return $this->viewPHP($A0c027a94ce140, "admin.form", $this->module_dir_tpl_core);
    }

    protected function prepareCustomFields($xff3f2a9f0a3cb1, &$Z5611fabffb1 = false)
    {
        $Z5611fabffb1 = false;
        if( empty($xff3f2a9f0a3cb1) || !is_array($xff3f2a9f0a3cb1) ) 
        {
            return array(  );
        }

        foreach( $xff3f2a9f0a3cb1 as &$A0c027a94ce145 ) 
        {
            if( empty($A0c027a94ce145["type"]) || !in_array($A0c027a94ce145["type"], array( "text", "textarea", "wy" )) ) 
            {
                $A0c027a94ce145["type"] = "textarea";
            }

            $A0c027a94ce145["after"] = !isset($A0c027a94ce145["before"]) && !isset($A0c027a94ce145["after"]) || empty($A0c027a94ce145["before"]);
            $A0c027a94ce145["before"] = !$A0c027a94ce145["after"];
            if( !isset($A0c027a94ce145["attr"]) ) 
            {
                $A0c027a94ce145["attr"] = array(  );
            }

            if( !isset($A0c027a94ce145["attr"]["class"]) ) 
            {
                $A0c027a94ce145["attr"]["class"] = "";
            }

            $A0c027a94ce145["attr"]["class"] .= " stretch lang-field j-input";
            if( $A0c027a94ce145["type"] == "wy" ) 
            {
                $A0c027a94ce145["attr"]["class"] .= " j-wy";
                tpl::includeJS("wysiwyg", true);
                $Z5611fabffb1 = true;
            }

            if( !isset($A0c027a94ce145["attr"]["style"]) ) 
            {
                switch( $A0c027a94ce145["type"] ) 
                {
                    case "text":
                        $A0c027a94ce145["attr"]["style"] = "";
                        break;
                    case "textarea":
                        $A0c027a94ce145["attr"]["style"] = "min-height:85px;";
                        break;
                    case "wy":
                        $A0c027a94ce145["attr"]["style"] = "height:100px;";
                }
            }

            $A0c027a94ce145["attr"] = HTML::attributes($A0c027a94ce145["attr"]);
        }
        unset($A0c027a94ce145);
        return $xff3f2a9f0a3cb1;
    }

}


