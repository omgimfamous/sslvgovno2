<?php 

class SendmailModule extends SendmailModuleBase
{
    public function init()
    {
        parent::init();
        $A0c027a94ce113 = \bff::i()->getModulesList("all");
        foreach( array( $this->module_name, "test" ) as $A0c027a94ce114 ) 
        {
            if( isset($A0c027a94ce113[$A0c027a94ce114]) ) 
            {
                unset($A0c027a94ce113[$A0c027a94ce114]);
            }

        }
        $A0c027a94ce115 = "sendmailTemplates";
        $A0c027a94ce116 = false;
        foreach( $A0c027a94ce113 as $A0c027a94ce114 ) 
        {
            $A0c027a94ce117 = \bff::i()->getModule($A0c027a94ce114);
            if( method_exists($A0c027a94ce117, $A0c027a94ce115) ) 
            {
                $A0c027a94ce116 = true;
                $A0c027a94ce118 = $A0c027a94ce117->$A0c027a94ce115();
                if( empty($A0c027a94ce118) ) 
                {
                    continue;
                }

                foreach( $A0c027a94ce118 as $A0c027a94ce119 => $A0c027a94ce120 ) 
                {
                    if( !isset($this->aTemplates[$A0c027a94ce119]) ) 
                    {
                        $this->aTemplates[$A0c027a94ce119] = $A0c027a94ce120;
                    }

                }
            }

        }
        if( $A0c027a94ce116 ) 
        {
            $A0c027a94ce121 = array(  );
            $A0c027a94ce122 = 1;
            foreach( $this->aTemplates as $A0c027a94ce123 => $A0c027a94ce114 ) 
            {
                $A0c027a94ce121[$A0c027a94ce123] = isset($A0c027a94ce114["priority"]) ? $A0c027a94ce114["priority"] : $A0c027a94ce122++;
            }
            array_multisort($A0c027a94ce121, SORT_ASC, $this->aTemplates);
        }

    }

    public function template_listing()
    {
        if( !$this->haveAccessTo("templates-listing") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce124 = array( "templates" => $this->aTemplates );
        return $this->viewPHP($A0c027a94ce124, "admin.template.listing", $this->module_dir_tpl_core);
    }

    public function template_edit()
    {
        if( !$this->haveAccessTo("templates-edit") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce125 = $this->input->postget("tpl", TYPE_STR);
        if( empty($A0c027a94ce125) || !isset($this->aTemplates[$A0c027a94ce125]) ) 
        {
            $this->adminRedirect(Errors::IMPOSSIBLE, "template_listing");
        }

        if( Request::isPOST() ) 
        {
            $this->input->postm_lang(array( "subject" => TYPE_STR, "body" => TYPE_STR ), $A0c027a94ce126);
            $this->saveMailTemplateToFile($A0c027a94ce125, $A0c027a94ce126);
            $this->adminRedirect(\Errors::SUCCESS, "template_listing");
        }
        else
        {
            $A0c027a94ce126 = $this->getMailTemplateFromFile($A0c027a94ce125);
        }

        $A0c027a94ce127 = array( "keyword" => $A0c027a94ce125, "description" => $this->aTemplates[$A0c027a94ce125]["description"], "vars" => $this->aTemplates[$A0c027a94ce125]["vars"], "title" => $this->aTemplates[$A0c027a94ce125]["title"], "tpl" => $A0c027a94ce126, "clientside" => 0 );
        foreach( array( "{site.title}" => "Название сайта", "{site.host}" => "Домен сайта" ) as $A0c027a94ce128 => $A0c027a94ce129 ) 
        {
            if( !isset($A0c027a94ce127["vars"][$A0c027a94ce128]) ) 
            {
                $A0c027a94ce127["vars"][$A0c027a94ce128] = $A0c027a94ce129;
            }

        }
        return $this->viewPHP($A0c027a94ce127, "admin.template.form", $this->module_dir_tpl_core);
    }

    public function template_restore()
    {
        if( !$this->haveAccessTo("templates-edit") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce130 = $this->input->postget("tpl", TYPE_STR);
        if( empty($A0c027a94ce130) || !isset($this->aTemplates[$A0c027a94ce130]) ) 
        {
            $this->adminRedirect(\Errors::IMPOSSIBLE, "template_listing");
        }

        $this->restoreMailTemplateFile($A0c027a94ce130);
        $this->adminRedirect(Errors::SUCCESS, "template_edit&tpl=" . $A0c027a94ce130);
    }

}


