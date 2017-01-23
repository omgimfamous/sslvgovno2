<?php 

class DevModule extends DevModuleBase
{
    public function sys()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce1 = array(  );
        $A0c027a94ce2 = strtoupper(substr(PHP_OS, 0, 3) == "WIN");
        $A0c027a94ce3 = ini_get("open_basedir");
        $A0c027a94ce1["open_basedir"] = !empty($A0c027a94ce3) ? $A0c027a94ce3 : "Неопределено";
        $A0c027a94ce3 = !empty($A0c027a94ce3) ? explode(PATH_SEPARATOR, $A0c027a94ce3) : false;
        $A0c027a94ce1["maxmemory"] = ini_get("memory_limit") != "" ? ini_get("memory_limit") : "Неопределено";
        $A0c027a94ce4 = get_loaded_extensions();
        $A0c027a94ce1["extension_pdo"] = in_array("PDO", $A0c027a94ce4) ? "Включено (" . implode(", ", PDO::getAvailableDrivers()) . ")" : "<span class=\"clr-error\">Выключено</span>";
        $A0c027a94ce1["extension_spl"] = in_array("SPL", $A0c027a94ce4) ? "Включено (autoload, iterators...)" : "<span class=\"clr-error\">Выключено</span>";
        $A0c027a94ce1["mbstring"] = in_array("mbstring", $A0c027a94ce4) ? "Включено" : "<span class=\"clr-error\">Выключено</span>";
        $A0c027a94ce1["extension_mcrypt"] = in_array("mcrypt", $A0c027a94ce4) ? "Включено" : "Выключено";
        $A0c027a94ce1["extension_gettext"] = in_array("gettext", $A0c027a94ce4) ? "Включено" : "<span class=\"clr-error\">Выключено</span>";
        if( in_array("apache2handler", $A0c027a94ce4) && function_exists("apache_get_modules") ) 
        {
            if( array_search("mod_rewrite", apache_get_modules()) ) 
            {
                $A0c027a94ce1["mod_rewrite"] = "Включен";
            }
            else
            {
                $A0c027a94ce1["mod_rewrite"] = "<span class=\"clr-error\">Выключен</span>";
            }

        }
        else
        {
            $A0c027a94ce1["mod_rewrite"] = "Неопределено";
        }

        $A0c027a94ce1["safemode"] = ini_get("safe_mode") == 1 ? "Включен" : "Выключен";
        $A0c027a94ce1["os_version"] = php_uname("s") . " " . php_uname("r");
        $A0c027a94ce1["php_version"] = phpversion();
        if( in_array("mysql", $A0c027a94ce4) ) 
        {
            if( function_exists("mysql_get_client_info") ) 
            {
                $A0c027a94ce5 = mysql_get_client_info();
                preg_match("/[0-9.]+/", $A0c027a94ce5, $A0c027a94ce6);
                $A0c027a94ce1["mysql"] = $A0c027a94ce6[0];
            }
            else
            {
                $A0c027a94ce1["mysql"] = "Включено";
            }

        }
        else
        {
            $A0c027a94ce1["mysql"] = "<span class=\"clr-error\">Выключено</span>";
        }

        static $K0a9326;
        if( $K0a9326 === NULL ) 
        {
            ob_start();
            phpinfo(INFO_MODULES);
            $A0c027a94ce7 = ob_get_contents();
            ob_end_clean();
            if( preg_match("/\\bgd\\s+version\\b[^\\d\n\r]+?([\\d\\.]+)/i", $A0c027a94ce7, $A0c027a94ce8) ) 
            {
                $A0c027a94ce9 = $A0c027a94ce8[1];
            }
            else
            {
                $A0c027a94ce9 = 0;
            }

        }

        $A0c027a94ce1["gd_version"] = $A0c027a94ce9;
        $A0c027a94ce10 = $A0c027a94ce2 ? ".exe" : "";
        $A0c027a94ce11 = getenv("MAGICK_HOME");
        $A0c027a94ce1["img_imagick"] = "Неопределено";
        if( empty($A0c027a94ce11) ) 
        {
            $A0c027a94ce12 = $A0c027a94ce2 ? array( "C:/WINDOWS/", "C:/WINNT/", "C:/WINDOWS/SYSTEM/", "C:/WINNT/SYSTEM/", "C:/WINDOWS/SYSTEM32/", "C:/WINNT/SYSTEM32/" ) : array( "/usr/bin/", "/usr/sbin/", "/usr/local/bin/", "/usr/local/sbin/", "/opt/", "/usr/imagemagick/", "/usr/bin/imagemagick/" );
            $A0c027a94ce13 = getenv("PATH");
            if( !empty($A0c027a94ce13) ) 
            {
                $A0c027a94ce13 = str_replace("\\", "/", explode(PATH_SEPARATOR, $A0c027a94ce13));
                $A0c027a94ce12 = array_merge($A0c027a94ce12, $A0c027a94ce13);
            }

            foreach( $A0c027a94ce12 as $A0c027a94ce14 ) 
            {
                if( substr($A0c027a94ce14, -1, 1) !== "/" ) 
                {
                    $A0c027a94ce14 .= "/";
                }

                if( (empty($A0c027a94ce3) || in_array($A0c027a94ce14, $A0c027a94ce3)) && @file_exists($A0c027a94ce14) && @is_readable($A0c027a94ce14 . "mogrify" . $A0c027a94ce10) && 3000 < @filesize($A0c027a94ce14 . "mogrify" . $A0c027a94ce10) ) 
                {
                    $A0c027a94ce1["img_imagick"] = str_replace("\\", "/", $A0c027a94ce14);
                    continue;
                }

            }
            if( empty($A0c027a94ce1["img_imagick"]) && in_array("imagick", $A0c027a94ce4) ) 
            {
                $A0c027a94ce1["img_imagick"] = "расширение imagick";
                try
                {
                    $A0c027a94ce15 = new Imagick();
                    $A0c027a94ce1["img_imagick"] .= ", версия: " . $A0c027a94ce15->getVersion();
                }
                catch( Exception $A0c027a94ce16 ) 
                {
                }
            }

        }
        else
        {
            $A0c027a94ce1["img_imagick"] = str_replace("\\", "/", $A0c027a94ce11);
        }

        $A0c027a94ce1["maxexecution"] = ini_get("max_execution_time");
        $A0c027a94ce1["maxupload"] = str_replace(array( "M", "m" ), "", ini_get("upload_max_filesize"));
        $A0c027a94ce1["maxupload"] = tpl::filesize($A0c027a94ce1["maxupload"] * 1024 * 1024, true);
        $A0c027a94ce1["maxpost"] = str_replace(array( "M", "m" ), "", ini_get("post_max_size"));
        $A0c027a94ce1["maxpost"] = tpl::filesize($A0c027a94ce1["maxpost"] * 1024 * 1024, true);
        $A0c027a94ce1["disabled_functions"] = 1 < strlen(ini_get("disable_functions")) ? ini_get("disable_functions") : "Неопределено";
        $A0c027a94ce1["disabled_functions"] = str_replace(",", ", ", $A0c027a94ce1["disabled_functions"]);
        return $this->viewPHP($A0c027a94ce1, "admin.system", $this->module_dir_tpl_core);
    }

    public function module_create()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce17 = bff::i()->getModulesList();
        $A0c027a94ce18 = array( "modules" => $A0c027a94ce17, "title" => "", "name" => "" );
        if( Request::isPOST() ) 
        {
            $A0c027a94ce18["title"] = mb_strtolower($this->input->post("title", TYPE_NOTAGS));
            $A0c027a94ce18["name"] = $this->input->post("name", TYPE_NOTAGS);
            $A0c027a94ce19 = $this->createModule($A0c027a94ce18["title"], $A0c027a94ce18["name"]);
            if( $A0c027a94ce19 ) 
            {
                $this->adminRedirect(Errors::SUCCESS, "module_create");
            }

        }

        return $this->viewPHP($A0c027a94ce18, "admin.module.create", $this->module_dir_tpl_core);
    }

    public function dirs_listing()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce20 = DIRECTORY_SEPARATOR;
        $A0c027a94ce21 = array( PATH_BASE . "config", PATH_BASE . "files" . $A0c027a94ce20 . "cache", PATH_BASE . "files" . $A0c027a94ce20 . "logs", PATH_BASE . "files" . $A0c027a94ce20 . "mail", PATH_BASE . "files" . $A0c027a94ce20 . "smarty", PATH_PUBLIC . "files" . $A0c027a94ce20 . "bnnrs", PATH_PUBLIC . "files" . $A0c027a94ce20 . "images" . $A0c027a94ce20 . "avatars", PATH_PUBLIC . "files" . $A0c027a94ce20 . "pages" );
        $A0c027a94ce22 = array(  );
        if( !empty($A0c027a94ce22) ) 
        {
            $A0c027a94ce21 = $A0c027a94ce22;
        }

        clearstatcache();
        foreach( $A0c027a94ce21 as &$A0c027a94ce23 ) 
        {
            $A0c027a94ce24 = $A0c027a94ce23;
            $A0c027a94ce23 = array( "path" => str_replace(PATH_BASE, "", $A0c027a94ce24), "access" => 0 );
            if( !file_exists($A0c027a94ce24) ) 
            {
                $A0c027a94ce23["access"] = 1;
            }
            else
            {
                if( !is_writable($A0c027a94ce24) ) 
                {
                    $A0c027a94ce23["access"] = 2;
                }

            }

        }
        unset($A0c027a94ce23);
        $A0c027a94ce25 = array( "dirs" => $A0c027a94ce21, "accessTypes" => array( array( "t" => "OK", "class" => "clr-success" ), array( "t" => "Не существует", "class" => "clr-error" ), array( "t" => "Нет прав на запись", "class" => "clr-error" ) ) );
        return $this->viewPHP($A0c027a94ce25, "admin.dirs", $this->module_dir_tpl_core);
    }

    public function phpinfo1()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        return "<iframe width=\"730\" height=\"25000\" style=\"overflow-x:hidden;\" src=\"" . $this->adminLink("phpinfo2", "dev") . "\" frameborder=\"0\"></iframe>";
    }

    public function phpinfo2()
    {
        if( !FORDEV || bff::demo() ) 
        {
            echo "";
            exit();
        }

        phpinfo();
        exit();
    }

    public function locale_data()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        require(PATH_CORE . "gettext" . DIRECTORY_SEPARATOR . "CGettextHelper.php");
        $A0c027a94ce26 = $this->locale->getLanguages(false);
        $A0c027a94ce27 = $this->input->postget("act", TYPE_STR);
        if( !empty($A0c027a94ce27) ) 
        {
            $A0c027a94ce28 = new CGettextHelper();
            switch( $A0c027a94ce27 ) 
            {
                case "tr-pot":
                    $A0c027a94ce28->generatePotFiles(PATH_BASE, $A0c027a94ce26, array( ".git", ".svn", ".idea", "vagrant", "zend", "/files", "/install", "/lang", "/docs", "/app/external", "/bff/external", "/public_html/css", "/public_html/coding", "/public_html/doc", "/public_html/files", "/public_html/img", "/public_html/js", "/public_html/styles" ));
                    break;
                case "tr-mo":
                    $A0c027a94ce29 = $this->locale->gt_Domain("next");
                    $A0c027a94ce28->updateMoFiles($A0c027a94ce26, $this->locale->gt_Domain(), $A0c027a94ce29);
                    file_put_contents($this->locale->gt_Domain("path"), "<?php\n return '" . $A0c027a94ce29 . "';");
                    break;
                case "tr-download":
                    $A0c027a94ce30 = $this->input->getm(array( "lang" => TYPE_STR, "type" => TYPE_STR ));
                    $A0c027a94ce31 = $A0c027a94ce30["lang"];
                    $A0c027a94ce32 = $A0c027a94ce30["type"];
                    if( array_key_exists($A0c027a94ce31, $A0c027a94ce26) && $A0c027a94ce32 == "pot" ) 
                    {
                        $A0c027a94ce33 = CGettextHelper::getPOFilename($this->locale->gt_Path($A0c027a94ce31));
                        if( is_file($A0c027a94ce33) ) 
                        {
                            header("Content-Disposition: attachment;filename=\"" . $A0c027a94ce31 . ".po" . "\"");
                            header("Cache-Control: max-age=0");
                            header("Content-Type: text/plain");
                            header("Content-Length: " . filesize($A0c027a94ce33));
                            readfile($A0c027a94ce33);
                            exit( 0 );
                        }

                    }

                    break;
                case "tr-upload":
                    $A0c027a94ce34 = array(  );
                    foreach( $_FILES as $A0c027a94ce35 => $A0c027a94ce36 ) 
                    {
                        $A0c027a94ce33 = $A0c027a94ce36["tmp_name"];
                        if( strpos($A0c027a94ce35, "po_") !== 0 ) 
                        {
                            continue;
                        }

                        $A0c027a94ce31 = str_replace("po_", "", $A0c027a94ce35);
                        if( !array_key_exists($A0c027a94ce31, $A0c027a94ce26) || $A0c027a94ce36["error"] != 0 || !is_uploaded_file($A0c027a94ce33) || @filesize($A0c027a94ce33) <= 0 ) 
                        {
                            continue;
                        }

                        $A0c027a94ce34[$A0c027a94ce31] = $A0c027a94ce33;
                    }
                    if( !sizeof($A0c027a94ce34) ) 
                    {
                        $this->adminRedirect(Errors::IMPOSSIBLE, "locale_data");
                    }

                    $A0c027a94ce28->updatePoFiles($A0c027a94ce34);
                    break;
                case "db-data-copy":
                    $A0c027a94ce37 = array(  );
                    $A0c027a94ce38 = $this->input->postget("from", TYPE_STR);
                    $A0c027a94ce39 = $this->input->postget("to", TYPE_STR);
                    while( !$A0c027a94ce38 || !$A0c027a94ce39 || !isset($A0c027a94ce26[$A0c027a94ce38]) || !isset($A0c027a94ce26[$A0c027a94ce39]) ) 
                    {
                        $this->errors->set("Укажите корректные языки локализации");
                        break;
                    }
                    if( $A0c027a94ce38 == $A0c027a94ce39 ) 
                    {
                        $this->errors->set("Язык локализации не должен совпадать");
                        break;
                    }

                    ignore_user_abort(true);
                    foreach( bff::i()->getModulesList("all") as $A0c027a94ce40 ) 
                    {
                        $A0c027a94ce41 = bff::model($A0c027a94ce40);
                        if( !$A0c027a94ce41 || !method_exists($A0c027a94ce41, "getLocaleTables") ) 
                        {
                            continue;
                        }

                        $A0c027a94ce42 = $A0c027a94ce41->getLocaleTables();
                        foreach( $A0c027a94ce42 as $A0c027a94ce43 => $A0c027a94ce44 ) 
                        {
                            $A0c027a94ce45 = $this->db->schema($A0c027a94ce43);
                            if( $A0c027a94ce45 === false ) 
                            {
                                break;
                            }

                            $A0c027a94ce46 = $A0c027a94ce45["result"];
                            if( $A0c027a94ce44["type"] == "table" ) 
                            {
                                $A0c027a94ce47 = isset($A0c027a94ce44["lang_table"]) ? $A0c027a94ce44["lang_table"] : $A0c027a94ce43 . "_lang";
                                if( !$this->db->isTable($A0c027a94ce47) ) 
                                {
                                    $this->errors->set("Таблица локализации \"" . $A0c027a94ce47 . "\" указана некорректно");
                                    break;
                                }

                                $A0c027a94ce48 = $A0c027a94ce46[0][$A0c027a94ce45["field"]];
                                foreach( $A0c027a94ce46 as $A0c027a94ce49 ) 
                                {
                                    if( $A0c027a94ce49[$A0c027a94ce45["pkname"]] == $A0c027a94ce45["pkval"] ) 
                                    {
                                        $A0c027a94ce48 = $A0c027a94ce49[$A0c027a94ce45["field"]];
                                        break;
                                    }

                                }
                                $A0c027a94ce50 = $this->db->select_one_column("SELECT I." . $A0c027a94ce48 . " FROM " . $A0c027a94ce43 . " I LEFT JOIN " . $A0c027a94ce47 . " LT ON I." . $A0c027a94ce48 . " = LT." . $A0c027a94ce48 . " AND LT.lang = :langTo WHERE LT.lang IS NULL", array( ":langTo" => $A0c027a94ce39 ));
                                if( !empty($A0c027a94ce50) ) 
                                {
                                    foreach( array_chunk($A0c027a94ce50, 100) as $A0c027a94ce51 ) 
                                    {
                                        $A0c027a94ce52 = array(  );
                                        foreach( $A0c027a94ce51 as &$A0c027a94ce53 ) 
                                        {
                                            $A0c027a94ce52[] = "(" . $A0c027a94ce53 . ",:langTo)";
                                        }
                                        unset($A0c027a94ce53);
                                        $this->db->exec("INSERT INTO " . $A0c027a94ce47 . " (" . $A0c027a94ce48 . ", lang) VALUES " . join(", ", $A0c027a94ce52), array( ":langTo" => $A0c027a94ce39 ));
                                    }
                                }

                                $A0c027a94ce54 = array(  );
                                foreach( $A0c027a94ce44["fields"] as $A0c027a94ce55 => $A0c027a94ce49 ) 
                                {
                                    $A0c027a94ce54[] = "LT." . $A0c027a94ce55 . " = LF." . $A0c027a94ce55;
                                }
                                $this->db->exec("UPDATE " . $A0c027a94ce47 . " LT, (SELECT L.* FROM " . $A0c027a94ce47 . " L WHERE L.lang = :langFrom) as LF SET " . join(", ", $A0c027a94ce54) . " WHERE LF." . $A0c027a94ce48 . " = LT." . $A0c027a94ce48 . " AND LT.lang = :langTo ", array( ":langFrom" => $A0c027a94ce38, ":langTo" => $A0c027a94ce39 ));
                            }
                            else
                            {
                                if( $A0c027a94ce44["type"] == "fields" && !empty($A0c027a94ce44["fields"]) ) 
                                {
                                    foreach( $A0c027a94ce44["fields"] as $A0c027a94ce56 => $A0c027a94ce49 ) 
                                    {
                                        $A0c027a94ce57 = false;
                                        $A0c027a94ce58 = $A0c027a94ce56 . "_" . $A0c027a94ce39;
                                        $A0c027a94ce59 = false;
                                        foreach( $A0c027a94ce46 as &$A0c027a94ce60 ) 
                                        {
                                            if( $A0c027a94ce60[$A0c027a94ce45["field"]] === $A0c027a94ce56 . "_" . $A0c027a94ce38 ) 
                                            {
                                                $A0c027a94ce57 = $A0c027a94ce60;
                                            }
                                            else
                                            {
                                                if( $A0c027a94ce60[$A0c027a94ce45["field"]] === $A0c027a94ce58 ) 
                                                {
                                                    $A0c027a94ce59 = true;
                                                }

                                            }

                                        }
                                        unset($A0c027a94ce60);
                                        if( $A0c027a94ce57 === false ) 
                                        {
                                            $this->errors->set("Таблица \"" . $A0c027a94ce43 . "\" не содержит необходимых для дублирования полей");
                                            break 2;
                                        }

                                        if( $A0c027a94ce59 === false ) 
                                        {
                                            if( $this->db->isMySQL() ) 
                                            {
                                                $this->db->exec("ALTER TABLE " . $A0c027a94ce43 . " ADD " . $A0c027a94ce58 . " " . $A0c027a94ce57[$A0c027a94ce45["type"]] . ($A0c027a94ce57["Null"] == "NO" ? " NOT NULL " : "") . " AFTER " . $A0c027a94ce56 . "_" . $A0c027a94ce38);
                                            }
                                            else
                                            {
                                                if( $this->db->isPgSQL() ) 
                                                {
                                                    $this->db->exec("ALTER TABLE " . $A0c027a94ce43 . " ADD " . $A0c027a94ce58 . " " . $A0c027a94ce57[$A0c027a94ce45["type"]]);
                                                }

                                            }

                                        }

                                    }
                                    $A0c027a94ce61 = array(  );
                                    foreach( $A0c027a94ce44["fields"] as $A0c027a94ce56 => $A0c027a94ce49 ) 
                                    {
                                        $A0c027a94ce61[] = $A0c027a94ce56 . "_" . $A0c027a94ce39 . " = " . $A0c027a94ce56 . "_" . $A0c027a94ce38;
                                    }
                                    $this->db->exec("UPDATE " . $A0c027a94ce43 . " SET " . join(", ", $A0c027a94ce61));
                                }

                            }

                        }
                    }
                    bff::i()->callModules("onLocaleDataCopy", array( $A0c027a94ce38, $A0c027a94ce39 ));
                    if( !false ) 
                    {
                        $this->ajaxResponseForm($A0c027a94ce37);
                        break;
                    }

                case "db-data-remove":
                    $A0c027a94ce37 = array(  );
                    $A0c027a94ce31 = $this->input->postget("lang", TYPE_STR);
                    while( !$A0c027a94ce31 || !isset($A0c027a94ce26[$A0c027a94ce31]) ) 
                    {
                        $this->errors->set("Удаляемый язык локализации указан некорректно");
                        break;
                    }
                    if( $A0c027a94ce31 == config::sys("locale.default") ) 
                    {
                        $this->errors->set("Невозможно удалить базовый язык локализации");
                        break;
                    }

                    ignore_user_abort(true);
                    foreach( bff::i()->getModulesList("all") as $A0c027a94ce40 ) 
                    {
                        $A0c027a94ce41 = bff::model($A0c027a94ce40);
                        if( !$A0c027a94ce41 || !method_exists($A0c027a94ce41, "getLocaleTables") ) 
                        {
                            continue;
                        }

                        $A0c027a94ce42 = $A0c027a94ce41->getLocaleTables();
                        foreach( $A0c027a94ce42 as $A0c027a94ce43 => $A0c027a94ce44 ) 
                        {
                            if( $A0c027a94ce44["type"] == "table" ) 
                            {
                                $A0c027a94ce47 = isset($A0c027a94ce44["lang_table"]) ? $A0c027a94ce44["lang_table"] : $A0c027a94ce43 . "_lang";
                                if( !$this->db->isTable($A0c027a94ce47) ) 
                                {
                                    $this->errors->set("Таблица локализации \"" . $A0c027a94ce47 . "\" указана некорректно");
                                    break;
                                }

                                $this->db->delete($A0c027a94ce47, array( "lang" => $A0c027a94ce31 ));
                            }
                            else
                            {
                                if( $A0c027a94ce44["type"] == "fields" && !empty($A0c027a94ce44["fields"]) ) 
                                {
                                    $A0c027a94ce45 = $this->db->schema($A0c027a94ce43);
                                    if( $A0c027a94ce45 === false ) 
                                    {
                                        break;
                                    }

                                    $A0c027a94ce46 = $A0c027a94ce45["result"];
                                    $A0c027a94ce62 = array(  );
                                    foreach( $A0c027a94ce44["fields"] as $A0c027a94ce56 => $A0c027a94ce49 ) 
                                    {
                                        foreach( $A0c027a94ce46 as &$A0c027a94ce60 ) 
                                        {
                                            if( $A0c027a94ce60[$A0c027a94ce45["field"]] === $A0c027a94ce56 . "_" . $A0c027a94ce31 ) 
                                            {
                                                $A0c027a94ce62[] = $A0c027a94ce56 . "_" . $A0c027a94ce31;
                                            }

                                        }
                                        unset($A0c027a94ce60);
                                    }
                                    if( !empty($A0c027a94ce62) ) 
                                    {
                                        foreach( $A0c027a94ce62 as $A0c027a94ce63 ) 
                                        {
                                            $this->db->exec("ALTER TABLE " . $A0c027a94ce43 . " DROP COLUMN " . $A0c027a94ce63);
                                        }
                                    }

                                }

                            }

                        }
                    }
                    bff::i()->callModules("onLocaleDataRemove", array( $A0c027a94ce31 ));
                    if( !false ) 
                    {
                        $this->ajaxResponseForm($A0c027a94ce37);
                        break;
                    }

            }
            $this->adminRedirect(Errors::SUCCESS, "locale_data");
        }

        $A0c027a94ce64["tabs"] = array( "translate" => array( "t" => "Интерфейс" ), "db" => array( "t" => "Данные" ) );
        $A0c027a94ce64["tab"] = $this->input->getpost("tab", TYPE_STR);
        if( !isset($A0c027a94ce64["tabs"][$A0c027a94ce64["tab"]]) ) 
        {
            $A0c027a94ce64["tab"] = key($A0c027a94ce64["tabs"]);
        }

        $A0c027a94ce64["nPotLastModify"] = @filemtime(@CGettextHelper::getPOFilename(@$this->locale->gt_Path(@$this->locale->getDefaultLanguage())));
        $A0c027a94ce64["nMoLastModify"] = $this->locale->gt_Domain("lastmodify");
        $A0c027a94ce65 = array(  );
        $A0c027a94ce66 = bff::i()->getModulesList("all");
        foreach( $A0c027a94ce66 as $A0c027a94ce40 ) 
        {
            $A0c027a94ce41 = bff::model($A0c027a94ce40);
            if( $A0c027a94ce41 && method_exists($A0c027a94ce41, "getLocaleTables") ) 
            {
                $A0c027a94ce67 = array_merge($A0c027a94ce65, $A0c027a94ce41->getLocaleTables());
                foreach( $A0c027a94ce67 as $A0c027a94ce68 => $A0c027a94ce69 ) 
                {
                    if( $A0c027a94ce69["type"] == "fields" ) 
                    {
                        $A0c027a94ce70 = array(  );
                        $A0c027a94ce71 = array(  );
                        $A0c027a94ce72 = $this->db->schema($A0c027a94ce68);
                        foreach( $A0c027a94ce72["result"] as $A0c027a94ce73 ) 
                        {
                            $A0c027a94ce71[] = $A0c027a94ce73[0];
                        }
                        $A0c027a94ce74 = preg_quote(key($A0c027a94ce69["fields"]));
                        foreach( $A0c027a94ce71 as $A0c027a94ce75 ) 
                        {
                            if( preg_match("/^" . $A0c027a94ce74 . "_([a-z]{2})/iu", $A0c027a94ce75, $A0c027a94ce76) ) 
                            {
                                $A0c027a94ce70[] = $A0c027a94ce76[1];
                            }

                        }
                        $A0c027a94ce67[$A0c027a94ce68]["state"] = $A0c027a94ce70;
                    }
                    else
                    {
                        $A0c027a94ce67[$A0c027a94ce68]["state"] = array_keys($A0c027a94ce26);
                    }

                }
                $A0c027a94ce65 = array_merge($A0c027a94ce65, $A0c027a94ce67);
            }

        }
        ksort($A0c027a94ce65);
        $A0c027a94ce64["db_tables"] = $A0c027a94ce65;
        $A0c027a94ce64["db_tables_types"] = array( "table" => "таблица", "fields" => "поля" );
        foreach( $A0c027a94ce26 as $A0c027a94ce77 => &$A0c027a94ce44 ) 
        {
            $A0c027a94ce44["key"] = $A0c027a94ce77;
        }
        unset($A0c027a94ce44);
        $A0c027a94ce64["languages"] = $A0c027a94ce26;
        $A0c027a94ce64["language_default"] = config::sys("locale.default");
        $A0c027a94ce64["languages_to"] = $A0c027a94ce26;
        unset($A0c027a94ce64["languages_to"][$A0c027a94ce64["language_default"]]);
        $A0c027a94ce64["languages_remove"] = $A0c027a94ce26;
        unset($A0c027a94ce64["languages_remove"][$A0c027a94ce64["language_default"]]);
        return $this->viewPHP($A0c027a94ce64, "admin.locale", $this->module_dir_tpl_core);
    }

    public function utils()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        if( Request::isAJAX() ) 
        {
            $A0c027a94ce78 = array(  );
            switch( $this->input->getpost("act", TYPE_STR) ) 
            {
                case "rstpass1":
                    $A0c027a94ce79 = $this->input->post("salt", TYPE_BOOL);
                    $A0c027a94ce80 = $this->db->select("SELECT user_id as id, password_salt as salt FROM " . TABLE_USERS . " ORDER BY user_id");
                    $A0c027a94ce81 = 0;
                    $A0c027a94ce82 = $this->security->getUserPasswordMD5("test");
                    foreach( $A0c027a94ce80 as $A0c027a94ce83 ) 
                    {
                        if( $A0c027a94ce79 ) 
                        {
                            $A0c027a94ce84 = $this->security->getUserPasswordMD5("test", $A0c027a94ce83["salt"]);
                        }
                        else
                        {
                            $A0c027a94ce84 = $A0c027a94ce82;
                        }

                        $A0c027a94ce85 = $this->db->exec("UPDATE " . TABLE_USERS . " SET password = :pass WHERE user_id = :uid", array( ":pass" => $A0c027a94ce84, ":uid" => $A0c027a94ce83["id"] ));
                        if( !empty($A0c027a94ce85) ) 
                        {
                            $A0c027a94ce81++;
                        }

                    }
                    $A0c027a94ce78["result"] = "Пароли успешно обнулены (" . $A0c027a94ce81 . " из " . sizeof($A0c027a94ce80) . ")";
                    break;
                case "install-sql":
                    $this->install_sql();
            }
            $this->ajaxResponseForm($A0c027a94ce78);
        }

        $A0c027a94ce86["tabs"] = array( "sysword" => array( "t" => "Системное слово" ), "resetpass" => array( "t" => "Обнуление паролей" ) );
        if( BFF_LOCALHOST ) 
        {
            $A0c027a94ce86["tabs"]["install-sql"] = array( "t" => "Сброс базы" );
        }

        $A0c027a94ce86["tab"] = $this->input->getpost("tab", TYPE_STR);
        if( !isset($A0c027a94ce86["tabs"][$A0c027a94ce86["tab"]]) ) 
        {
            $A0c027a94ce86["tab"] = key($A0c027a94ce86["tabs"]);
        }

        return $this->viewPHP($A0c027a94ce86, "admin.utils", $this->module_dir_tpl_core);
    }

    private function install_sql()
    {
        if( !FORDEV || !BFF_LOCALHOST ) 
        {
            $this->errors->accessDenied();
        }
        else
        {
            $A0c027a94ce87 = config::sys("db.type");
            $A0c027a94ce88 = PATH_CORE . "modules" . DS . "site" . DS . "install." . $A0c027a94ce87 . ".sql";
            if( !file_exists($A0c027a94ce88) ) 
            {
                echo "Отсутствует SQL файл [" . $A0c027a94ce88 . "]";
                exit();
            }

            $A0c027a94ce88 = bff\utils\Files::getFileContent($A0c027a94ce88);
            if( !empty($A0c027a94ce88) ) 
            {
                $this->db->exec($A0c027a94ce88);
            }

            $A0c027a94ce89 = bff::i()->getModulesList(true);
            foreach( $A0c027a94ce89 as $A0c027a94ce90 ) 
            {
                if( $A0c027a94ce90 == "site" ) 
                {
                    continue;
                }

                $A0c027a94ce91 = PATH_CORE . "modules" . DS . $A0c027a94ce90 . DS . "install." . $A0c027a94ce87 . ".sql";
                if( file_exists($A0c027a94ce91) ) 
                {
                    $A0c027a94ce91 = bff\utils\Files::getFileContent($A0c027a94ce91);
                    if( !empty($A0c027a94ce91) ) 
                    {
                        $this->db->exec($A0c027a94ce91);
                    }

                }

            }
            $A0c027a94ce92 = PATH_MODULES . "users" . DS . "install.sql";
            if( !file_exists($A0c027a94ce92) ) 
            {
                echo "Отсутствует SQL файл [" . $A0c027a94ce92 . "]";
                exit();
            }

            $A0c027a94ce92 = bff\utils\Files::getFileContent($A0c027a94ce92);
            if( !empty($A0c027a94ce92) ) 
            {
                $this->db->exec($A0c027a94ce92);
            }

            $A0c027a94ce89 = bff::i()->getModulesList(false);
            foreach( $A0c027a94ce89 as $A0c027a94ce90 ) 
            {
                if( $A0c027a94ce90 == "users" ) 
                {
                    continue;
                }

                $A0c027a94ce93 = array( "install.sql" );
                if( $A0c027a94ce90 == "publications" ) 
                {
                    $A0c027a94ce93[] = "install.types.sql";
                }
                else
                {
                    if( $A0c027a94ce90 == "afisha" ) 
                    {
                        $A0c027a94ce93[] = "install.types.sql";
                    }

                }

                $A0c027a94ce93[] = "install.data.sql";
                foreach( $A0c027a94ce93 as $A0c027a94ce94 ) 
                {
                    $A0c027a94ce95 = PATH_MODULES . $A0c027a94ce90 . DS . $A0c027a94ce94;
                    if( file_exists($A0c027a94ce95) ) 
                    {
                        $A0c027a94ce95 = bff\utils\Files::getFileContent($A0c027a94ce95);
                        if( !empty($A0c027a94ce95) ) 
                        {
                            $this->db->exec($A0c027a94ce95);
                        }

                    }

                }
            }
        }

    }

    public function mm_listing()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        if( Request::isAJAX() ) 
        {
            switch( $this->input->get("act", TYPE_STR) ) 
            {
                case "rotate":
                    $A0c027a94ce96 = $this->db->rotateTablednd(TABLE_MODULE_METHODS, "", "id", "number");
                    $this->ajaxResponse($A0c027a94ce96 ? Errors::SUCCESS : Errors::IMPOSSIBLE);
                    break;
                case "delete":
                    $A0c027a94ce97 = $this->input->post("rec", TYPE_UINT);
                    if( !$A0c027a94ce97 ) 
                    {
                        break;
                    }

                    $A0c027a94ce98 = $this->db->one_array("SELECT * FROM " . TABLE_MODULE_METHODS . " WHERE id = :id", array( ":id" => $A0c027a94ce97 ));
                    if( empty($A0c027a94ce98) ) 
                    {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    if( $A0c027a94ce98["module"] == $A0c027a94ce98["method"] ) 
                    {
                        $A0c027a94ce99 = $this->db->select_one_column("SELECT id FROM " . TABLE_MODULE_METHODS . " WHERE module=:module AND module!=method ORDER BY number, id", array( ":module" => $A0c027a94ce98["module"] ));
                        $this->db->delete(TABLE_MODULE_METHODS, array( "id" => $A0c027a94ce99 ));
                        $this->db->delete(TABLE_USERS_GROUPS_PERMISSIONS, array( "item_type" => "module", "item_id" => $A0c027a94ce99 ));
                    }

                    $this->db->delete(TABLE_MODULE_METHODS, $A0c027a94ce97);
                    $this->db->delete(TABLE_USERS_GROUPS_PERMISSIONS, array( "unit_type" => "group", "item_type" => "module", "item_id=" => $A0c027a94ce97 ));
                    $this->ajaxResponse(Errors::SUCCESS);
            }
            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $A0c027a94ce100 = $this->db->select(" SELECT M.*, 1 as numlevel FROM " . TABLE_MODULE_METHODS . " M WHERE M.module = M.method ORDER BY M.number, M.id");
        $A0c027a94ce101 = $this->db->select("SELECT M.*, 2 as numlevel FROM " . TABLE_MODULE_METHODS . " M WHERE M.module != M.method ORDER BY M.number, M.id");
        $A0c027a94ce101 = func::array_transparent($A0c027a94ce101, "module");
        for( $A0c027a94ce102 = 0; $A0c027a94ce102 < count($A0c027a94ce100); $A0c027a94ce102++ ) 
        {
            $A0c027a94ce100[$A0c027a94ce102]["subitems"] = array(  );
            if( isset($A0c027a94ce101[$A0c027a94ce100[$A0c027a94ce102]["module"]]) ) 
            {
                $A0c027a94ce100[$A0c027a94ce102]["subitems"] = $A0c027a94ce101[$A0c027a94ce100[$A0c027a94ce102]["module"]];
            }

        }
        $A0c027a94ce100 = array( "mm" => $A0c027a94ce100 );
        tpl::includeJS(array( "tablednd" ), true);
        return $this->viewPHP($A0c027a94ce100, "admin.mm.listing", $this->module_dir_tpl_core);
    }

    public function mm_add()
    {
        if( !FORDEV || bff::demo() ) 
        {
            return $this->showAccessDenied();
        }

        $A0c027a94ce103 = $this->input->postm(array( "module" => TYPE_NOTAGS, "method" => TYPE_NOTAGS, "title" => TYPE_NOTAGS ));
        if( Request::isPOST() ) 
        {
            $A0c027a94ce104 = $A0c027a94ce103["module"];
            $A0c027a94ce105 = $A0c027a94ce103["method"];
            $A0c027a94ce106 = $A0c027a94ce103["title"];
            func::setSESSION("mm_module_last", $A0c027a94ce104);
            if( empty($A0c027a94ce105) ) 
            {
                $A0c027a94ce105 = $A0c027a94ce104;
            }

            if( empty($A0c027a94ce106) ) 
            {
                $A0c027a94ce106 = mb_convert_case($A0c027a94ce104 . " " . $A0c027a94ce105, MB_CASE_TITLE);
            }

            if( $A0c027a94ce104 === $A0c027a94ce105 ) 
            {
                $A0c027a94ce107 = (int) $this->db->one_data("SELECT MAX(number) FROM " . TABLE_MODULE_METHODS . " WHERE module = method");
            }
            else
            {
                $A0c027a94ce107 = (int) $this->db->one_data("SELECT MAX(number) FROM " . TABLE_MODULE_METHODS . " WHERE module = :module AND method != :method", array( ":module" => $A0c027a94ce104, ":method" => $A0c027a94ce104 ));
            }

            $A0c027a94ce107++;
            $this->db->insert(TABLE_MODULE_METHODS, array( "module" => $A0c027a94ce104, "method" => $A0c027a94ce105, "title" => $A0c027a94ce106, "number" => $A0c027a94ce107 ));
            if( $this->errors->no() ) 
            {
                $this->adminRedirect(Errors::SUCCESS, "mm_listing");
            }

        }

        if( empty($A0c027a94ce103["module"]) ) 
        {
            $A0c027a94ce103["module"] = func::SESSION("mm_module_last");
        }

        $A0c027a94ce108 = bff::i()->getModulesList();
        foreach( $A0c027a94ce108 as $A0c027a94ce109 ) 
        {
            $A0c027a94ce108[$A0c027a94ce109] = $A0c027a94ce109;
            if( $A0c027a94ce109 == "publications" ) 
            {
                $A0c027a94ce110 = bff::module("Publications")->getTypes();
                if( !empty($A0c027a94ce110) ) 
                {
                    foreach( $A0c027a94ce110 as $A0c027a94ce111 => $A0c027a94ce112 ) 
                    {
                        $A0c027a94ce108[$A0c027a94ce111] = "publications-" . $A0c027a94ce111;
                    }
                }

            }

            if( $A0c027a94ce109 == "afisha" ) 
            {
                $A0c027a94ce110 = bff::module("Afisha")->getTypes();
                if( !empty($A0c027a94ce110) ) 
                {
                    foreach( $A0c027a94ce110 as $A0c027a94ce111 => $A0c027a94ce112 ) 
                    {
                        $A0c027a94ce108[$A0c027a94ce111] = "afisha-" . $A0c027a94ce111;
                    }
                }

            }

        }
        asort($A0c027a94ce108);
        $A0c027a94ce103["modules_options"] = HTML::selectOptions($A0c027a94ce108, $A0c027a94ce103["module"]);
        return $this->viewPHP($A0c027a94ce103, "admin.mm.form", $this->module_dir_tpl_core);
    }

}


