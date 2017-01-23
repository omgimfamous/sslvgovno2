<?php 

class UsersModule extends UsersModuleBase
{
    public function group_listing()
    {
        if( !$this->haveAccessTo("groups-listing") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce156 = array( "groups" => $this->model->groups() );
        return $this->viewPHP($A0c027a94ce156, "admin.group.listing", $this->module_dir_tpl_core);
    }

    public function group_add()
    {
        if( !FORDEV && !$this->manageNonSystemGroups && $this->haveAccessTo("groups-edit") ) 
        {
            return $this->showAccessDenied();
        }

        $this->validateGroupData(0, $A0c027a94ce157);
        if( \Request::isPOST() && $this->errors->no() ) 
        {
            $this->model->groupSave(0, $A0c027a94ce157);
            $this->adminRedirect(Errors::SUCCESS, "group_listing");
        }

        $A0c027a94ce157["deletable"] = 1;
        $A0c027a94ce157["edit"] = false;
        return $this->viewPHP($A0c027a94ce157, "admin.group.form", $this->module_dir_tpl_core);
    }

    public function group_edit()
    {
        if( !FORDEV && !$this->manageNonSystemGroups && $this->haveAccessTo("groups-edit") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce158 = $this->input->get("rec", TYPE_UINT);
        if( !$A0c027a94ce158 ) 
        {
            $this->adminRedirect(Errors::IMPOSSIBLE, "group_listing");
        }

        $A0c027a94ce159 = $this->model->groupData($A0c027a94ce158);
        if( !$A0c027a94ce159 ) 
        {
            $this->adminRedirect(Errors::UNKNOWNRECORD, "group_listing");
        }

        $A0c027a94ce160 = $A0c027a94ce159["issystem"] || in_array($A0c027a94ce158, array( self::GROUPID_MEMBER, self::GROUPID_MODERATOR, self::GROUPID_SUPERADMIN ));
        if( $A0c027a94ce160 && !FORDEV ) 
        {
            return $this->showAccessDenied();
        }

        if( \Request::isPOST() ) 
        {
            $this->validateGroupData($A0c027a94ce158, $A0c027a94ce159);
            if( !FORDEV ) 
            {
                $A0c027a94ce159["issystem"] = $A0c027a94ce160;
            }

            if( $this->errors->no() ) 
            {
                $this->model->groupSave($A0c027a94ce158, $A0c027a94ce159);
                $this->adminRedirect(Errors::SUCCESS, "group_listing");
            }

        }

        $A0c027a94ce159["deletable"] = $A0c027a94ce160;
        $A0c027a94ce159["edit"] = true;
        return $this->viewPHP($A0c027a94ce159, "admin.group.form", $this->module_dir_tpl_core);
    }

    public function group_permission_listing()
    {
        if( !$this->haveAccessTo("groups-edit") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce161 = $this->input->get("rec", TYPE_UINT);
        if( !$A0c027a94ce161 ) 
        {
            $this->adminRedirect(Errors::UNKNOWNRECORD, "group_listing");
        }

        if( \Request::isPOST() ) 
        {
            $A0c027a94ce162 = $this->input->post("permission", TYPE_ARRAY_INT);
            $A0c027a94ce162 = array_unique($A0c027a94ce162);
            $A0c027a94ce162 = array_values($A0c027a94ce162);
            $this->model->groupPermissionsSave($A0c027a94ce161, $A0c027a94ce162);
            $this->adminRedirect(Errors::SUCCESS, "group_listing");
        }

        $A0c027a94ce163 = $this->model->groupData($A0c027a94ce161);
        $A0c027a94ce163["permissions"] = $this->model->groupPermissions($A0c027a94ce161);
        return $this->viewPHP($A0c027a94ce163, "admin.group.permission", $this->module_dir_tpl_core);
    }

    public function group_delete()
    {
        if( !FORDEV && (!$this->manageNonSystemGroups || !$this->haveAccessTo("groups-edit")) ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce164 = $this->input->get("rec", TYPE_UINT);
        if( !$A0c027a94ce164 ) 
        {
            $this->adminRedirect(Errors::UNKNOWNRECORD, "group_listing");
        }

        $A0c027a94ce165 = $this->model->groupData($A0c027a94ce164);
        if( empty($A0c027a94ce165) ) 
        {
            $this->adminRedirect(Errors::UNKNOWNRECORD, "group_listing");
        }
        else
        {
            $A0c027a94ce166 = $A0c027a94ce165["issystem"] || in_array($A0c027a94ce164, array( self::GROUPID_MEMBER, self::GROUPID_MODERATOR, self::GROUPID_SUPERADMIN ));
            if( $A0c027a94ce166 && !FORDEV ) 
            {
                return $this->showAccessDenied();
            }

            $this->model->groupDelete($A0c027a94ce164);
        }

        $this->adminRedirect(Errors::SUCCESS, "group_listing");
    }

    protected function validateGroupData($i12d925bd55043, &$o6b0f80d7c7e6)
    {
        $A0c027a94ce167 = array( "title" => TYPE_NOTAGS, "keyword" => TYPE_NOTAGS, "adminpanel" => TYPE_BOOL, "color" => TYPE_NOTAGS, "issystem" => TYPE_BOOL );
        $this->input->postm($A0c027a94ce167, $o6b0f80d7c7e6);
        if( \Request::isPOST() ) 
        {
            if( !$o6b0f80d7c7e6["title"] ) 
            {
                $this->errors->set("Укажите название группы");
            }

            if( empty($o6b0f80d7c7e6["keyword"]) ) 
            {
                $this->errors->set("Укажите keyword группы");
            }
            else
            {
                $o6b0f80d7c7e6["keyword"] = mb_strtolower($o6b0f80d7c7e6["keyword"]);
                if( $this->model->groupKeywordExists($o6b0f80d7c7e6["keyword"], $i12d925bd55043 ? $i12d925bd55043 : NULL) ) 
                {
                    $this->errors->set("Указанный keyword \"" . $o6b0f80d7c7e6["keyword"] . "\" уже используется");
                }

            }

            if( empty($o6b0f80d7c7e6["color"]) ) 
            {
                $o6b0f80d7c7e6["color"] = "#000";
            }

            if( !FORDEV ) 
            {
                unset($o6b0f80d7c7e6["issystem"]);
            }

            $o6b0f80d7c7e6["modified"] = $this->db->now();
            if( !$i12d925bd55043 ) 
            {
                $o6b0f80d7c7e6["created"] = $o6b0f80d7c7e6["modified"];
            }

        }

    }

    public function ban()
    {
        if( !$this->haveAccessTo("ban") ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce168 = array(  );
        if( \Request::isAJAX() ) 
        {
            switch( $this->input->postget("act", TYPE_STR) ) 
            {
                case "delete":
                    $A0c027a94ce169 = $this->input->postget("rec", TYPE_UINT);
                    if( !$A0c027a94ce169 ) 
                    {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    $this->model->banDelete($A0c027a94ce169);
                    $this->ajaxResponse(Errors::SUCCESS);
            }
            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        if( \Request::isPOST() ) 
        {
            if( $this->input->post("act", TYPE_STR) == "massdel" ) 
            {
                $A0c027a94ce170 = $this->input->post("banid", TYPE_ARRAY_UINT);
                $this->model->banDelete($A0c027a94ce170);
            }
            else
            {
                $A0c027a94ce171 = $this->input->post("banmode", TYPE_STR);
                if( empty($A0c027a94ce171) ) 
                {
                    $A0c027a94ce171 = "ip";
                }

                $A0c027a94ce172 = $this->input->post("ban_" . $A0c027a94ce171, TYPE_STR);
                $A0c027a94ce173 = $this->input->post("banlength", TYPE_UINT);
                $A0c027a94ce174 = $this->input->post("bandate", TYPE_STR);
                $A0c027a94ce175 = $this->input->post("exclude", TYPE_UINT);
                $A0c027a94ce176 = $this->input->post("description", TYPE_STR);
                $A0c027a94ce177 = $this->input->post("reason", TYPE_STR);
                if( !empty($A0c027a94ce172) ) 
                {
                    $this->model->banCreate($A0c027a94ce171, $A0c027a94ce172, $A0c027a94ce173, $A0c027a94ce174, $A0c027a94ce175, $A0c027a94ce176, $A0c027a94ce177);
                    $this->adminRedirect(Errors::SUCCESS, "ban");
                }

            }

        }

        $A0c027a94ce178 = array( "бессрочно", 30 => "30 минут", 60 => "1 час", 360 => "6 часов", 1440 => "1 день", 10080 => "7 дней", 20160 => "2 недели", 40320 => "1 месяц" );
        $A0c027a94ce168["bans"] = $this->db->select("SELECT B.* FROM " . TABLE_USERS_BANLIST . " B WHERE (B.finished >= " . time() . " OR B.finished = 0) ORDER BY B.ip, B.email");
        foreach( $A0c027a94ce168["bans"] as &$A0c027a94ce172 ) 
        {
            $A0c027a94ce179 = $A0c027a94ce172["finished"] ? ($A0c027a94ce172["finished"] - $A0c027a94ce172["started"]) / 60 : 0;
            $A0c027a94ce172["till"] = isset($A0c027a94ce178[$A0c027a94ce179]) ? $A0c027a94ce178[$A0c027a94ce179] : "";
            $A0c027a94ce172["finished_formated"] = date("Y-m-d H:i:s", $A0c027a94ce172["finished"]);
        }
        return $this->viewPHP($A0c027a94ce168, "admin.ban.listing", $this->module_dir_tpl_core);
    }

}


