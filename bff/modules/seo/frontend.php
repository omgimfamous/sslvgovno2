<?php

class SEOModule extends SEOModuleBase
{
    /**
     * Устанавливаем meta-теги для страницы модуля
     * @param string|\Module $module объект модуля или его название
     * @param string $pageKey ключ страницы
     * @param array $macrosData данные для макросов
     * @param array $pageMeta @ref мета-данные страницы (в сочетании с общим шаблоном)
     * @param array $pageMetaPrepare список ключей мета-данных страницы требующих подмены макросов
     */
    public function setPageMeta($module, $pageKey, array $macrosData = array(), array &$pageMeta = array(), array $pageMetaPrepare = array())
    {
        # применяем настройки посадочной страницы
        $landing = static::landingPage();
        if ($landing!==false) {
            $landingFields = static::landingPagesFields();
            foreach ($landingFields as $k=>&$v) { # доп. поля
                if (isset($landing[$k])) $pageMeta[$k] = $landing[$k];
            } unset($v);
            foreach ($this->_metaKeys as $k) { # мета поля
                if (isset($landing[$k])) $pageMeta[$k] = $landing[$k];
            }
            # Посадочные страницы - всегда индексируемые
            $this->robotsIndex(true);
        }

        do {
            # получаем объект модуля
            if (is_object($module)) {
                if (!is_a($module, '\\Module')) {
                    break;
                }
            } else {
                if (is_string($module)) {
                    try {
                        $module = bff::module((!empty($module) ? $module : bff::$class));
                        if (!method_exists($module, 'seoTemplates')) {
                            break;
                        }
                    } catch (\Exception $e) {
                        $this->errors->set($e->getMessage(), true);
                        break;
                    }
                } else {
                    break;
                }
            }

            # проверяем наличие описание seo-страницы модуля
            $moduleTemplates = $module->seoTemplates();
            if (!isset($moduleTemplates['pages'][$pageKey])) {
                break;
            }

            $meta = array();
            $fieldsExtra = array();

            # задействуем мета-данные страницы в сочетании с общим шаблоном
            $inherit = !empty($moduleTemplates['pages'][$pageKey]['inherit']) && ($landing === false);
            $template = $this->metaTemplateLoad($module->module_name, $pageKey);
            if ($inherit) {
                foreach ($this->_metaKeys as $k) {
                    if (isset($pageMeta[$k])) {
                        $meta[$k] = $pageMeta[$k];
                    }
                }

                $useTemplate = (isset($pageMeta['mtemplate']) ? !empty($pageMeta['mtemplate']) : ($landing!==false?false:true));
                if ($useTemplate) {
                    # мета-данные страницы + общий шаблон
                    foreach ($template as $k => &$v) {
                        if (empty($v[LNG])) {
                            continue;
                        }
                        if (in_array($k, $this->_metaKeys)) {
                            $replace = (isset($meta[$k]) ? $meta[$k] : '');
                            $meta[$k] = strtr($v[LNG], array(
                                    '{meta-base}'  => $replace,
                                    ' {meta-base}' => $replace,
                                )
                            );
                        } else {
                            if (isset($pageMeta[$k])) {
                                # данные доп. полей
                                $pageMeta[$k] = strtr($v[LNG], array(
                                        '{meta-base}'  => $pageMeta[$k],
                                        ' {meta-base}' => $pageMeta[$k],
                                    )
                                );
                                $fieldsExtra[] = $k;
                            }
                        }
                    }
                    unset($v);
                } else {
                    # только мета-данные страницы + получаем список доп. полей
                    foreach ($template as $k => &$v) {
                        if (!in_array($k, $this->_metaKeys)) {
                            if (isset($pageMeta[$k])) {
                                # данные доп. полей
                                $fieldsExtra[] = $k;
                            }
                        }
                    } unset($v);
                    if (empty($meta)) {
                        break;
                    }
                }
            } else {
                # шаблон страницы
                if ($landing !== false) {
                    # подменяем на настройки посадочных страниц
                    foreach ($template as $k => &$v) {
                        if (isset($landing[$k])) {
                            if (in_array($k, $this->_metaKeys)) {
                                $meta[$k] = $landing[$k];
                            } else {
                                $fieldsExtra[] = $k;
                            }
                        }
                    } unset($v);
                } else {
                    foreach ($template as $k => &$v) {
                        if (in_array($k, $this->_metaKeys)) {
                            if (isset($v[LNG]) && $v[LNG] !== '') {
                                $replace = (isset($pageMeta[$k]) ? $pageMeta[$k] : '');
                                $meta[$k] = strtr($v[LNG], array(
                                        '{meta-base}'  => $replace,
                                        ' {meta-base}' => $replace,
                                    )
                                );
                            }
                        } else {
                            $pageMeta[$k] = ( isset($v[LNG]) ? $v[LNG] : '' );
                            $fieldsExtra[] = $k;
                        }
                    } unset($v);
                }
            }

            # устанавливаем мета теги
            if (!empty($meta)) {
                $meta = $this->metaTextPrepare($meta, $macrosData);
                foreach ($meta as $k => &$v) {
                    $this->metaSet($k, $v);
                }
                unset($v, $meta);
            }

            # подмена макросов в дополнительных мета-данных страницы
            if (!empty($pageMeta)) {
                foreach ($fieldsExtra as $k) {
                    if (!isset($pageMetaPrepare[$k]) && !in_array($k, $pageMetaPrepare)) {
                        $pageMetaPrepare[] = $k;
                    }
                }
                if (!empty($pageMetaPrepare)) {
                    foreach ($pageMetaPrepare as $k => $v) {
                        if (is_string($v)) {
                            if (isset($pageMeta[$v])) {
                                $this->metaTextPrepare($pageMeta[$v], $macrosData);
                            }
                        } else {
                            if (is_array($v) && !empty($v)) {
                                $replace = $macrosData; $do = false;
                                if (!empty($v['replace']) && is_array($v['replace'])) {
                                    foreach ($v['replace'] as $kk=>$vv) {
                                        $replace[$kk] = ( isset($replace[$vv]) ? $replace[$vv] : $vv );
                                    } $do = true;
                                }
                                if (!empty($v['ignore']) && is_array($v['ignore'])) {
                                    foreach ($v['ignore'] as $vv) {
                                        $replace[$vv] = '';
                                    } $do = true;
                                }
                                if ($do) {
                                    $this->metaTextPrepare($pageMeta[$k], $replace);
                                }
                            }
                        }
                    }
                }
            }

        } while (false);
    }

    /**
     * Устанавливаем социальные meta-теги Open Graph
     * @url https://developers.facebook.com/docs/opengraph/howtos/maximizing-distribution-media-content
     * @param string $title заголовок
     * @param string $description описание
     * @param string|array $image изображение (или несколько)
     * @param string $url каноническая ссылка на страницу
     * @param string $site_name название сайта
     * @param string $fb_app_id ID facebook приложения
     */
    public function setSocialMetaOG($title, $description, $image, $url, $site_name, $fb_app_id = '')
    {
        # title, description, url, site_name
        if (empty($site_name)) {
            $site_name = config::sys('site.title');
        }
        if (strpos($url, '{site') !== false) {
            $url = static::urlDynamic($url, array(), LNG);
        }
        foreach (array('title'=>$title, 'description'=>$description, 'url'=>$url, 'site_name'=>$site_name) as $k=>$v) {
            $data = htmlspecialchars(trim(strval($v)), ENT_QUOTES, 'UTF-8', false);
            if (!empty($data)) {
                $this->_metaData['og:' . $k] = '<meta property="og:' . $k . '" content="' . $data . '" />';
            }
        }
        # image
        if (!empty($image)) {
            $requestScheme = Request::scheme() . ':';
            if (is_string($image)) {
                if ($image{0} == '/') {
                    $image = $requestScheme . $image;
                }
                $image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
                $this->_metaData['og:image'] = '<meta property="og:image" content="' . $image . '" />';
            } else {
                if (is_array($image)) {
                    $i = 1;
                    foreach ($image as &$v) {
                        if (!empty($v)) {
                            if ($v{0} == '/') {
                                $v = $requestScheme . $v;
                            }
                            $v = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                            $this->_metaData['og:image' . ($i++)] = '<meta property="og:image" content="' . $v . '" />';
                        }
                    }
                    unset($v);
                }
            }
        }
        # locale
        $locale = $this->locale->getLanguageSettings(LNG, 'locale');
        if (!empty($locale)) {
            $this->_metaData['og:locale'] = '<meta property="og:locale" content="' . $locale . '" />';
        }
        # fb_app_id
        if (!empty($fb_app_id)) {
            $this->_metaData['fb:app_id'] = '<meta property="fb:app_id" content="' . $fb_app_id . '" />';
        }
    }
}