<?php

class Site extends SiteBase
{
    /**
     * Главная страница
     */
    public function index()
    {
        $aData = array('titleh1' => '', 'seotext' => '');
        $region = Geo::filterUrl(); # seo
        if (!empty($region['id'])) {
            # seo: Главная страница (регион)
            $this->seo()->canonicalUrl(static::url('index-geo', $region, true));
            $this->setMeta('index-region', array(
                    'region' => ($region['city'] ? $region['city']['title_' . LNG] :
                            ($region['region'] ? $region['region']['title_' . LNG] :
                          ($region['country'] ? $region['country']['title_' . LNG] : '')))
                ), $aData
            );
        } else {
            # seo: Главная страница
            $this->seo()->canonicalUrl(static::url('index', array(), true));
            $this->setMeta('index', array(), $aData);
        }
        $aData['last'] = BBS::i()->indexLastBlock();
        return $this->viewPHP($aData, 'index');
    }

    /**
     * Блок фильтра в шапке
     */
    public function filterForm()
    {
        if (bff::$class == 'shops' && bff::shopsEnabled()) {
            return Shops::i()->searchForm();
        } else {
            if (bff::$class == 'help') {
                return Help::i()->searchForm();
            } else {
                return BBS::i()->searchForm();
            }
        }
    }

    /**
     * Страница "Услуги"
     */
    public function services()
    {
        $aData = array();

        if (!bff::servicesEnabled()) {
            $this->errors->error404();
        }

        # SEO:
        $this->urlCorrection(static::url('services'));
        $this->seo()->canonicalUrl(static::url('services', array(), true));
        $this->setMeta('services', array(), $aData);

        $aData['svc_bbs'] = BBS::model()->svcData();
        if (bff::shopsEnabled()) {
            $aData['svc_shops'] = Shops::model()->svcData();
            $aData['shop_opened'] = User::shopID() > 0;
            if ($aData['shop_opened']) {
                $aData['shop_promote_url'] = Shops::url('shop.promote', array('id'   => User::shopID(),
                                                                              'from' => 'services'
                    )
                );
            } else {
                $aData['shop_open_url'] = Shops::url('my.open');
            }
        }
        $aData['user_logined'] = (User::id() > 0);

        bff::setActiveMenu('//services');

        return $this->viewPHP($aData, 'services');
    }

    /**
     * Статические страницы
     */
    public function pageView()
    {
        $sFilename = $this->input->get('page', TYPE_NOTAGS);
        $aData = $this->model->pageDataView($sFilename);
        if (empty($aData)) {
            $this->errors->error404();
        }

        # SEO: Статические страницы
        $this->urlCorrection(static::url('page', array('filename' => $sFilename)));
        $this->seo()->canonicalUrl(static::url('page', array('filename' => $sFilename), true));
        $this->setMeta('page-view', array('title' => $aData['title']), $aData);

        return $this->viewPHP($aData, 'page.view');
    }

    /**
     * Страница "Карта сайта"
     */
    public function sitemap()
    {
        $aData = array('seotext' => '');

        # SEO: Карта сайта
        $this->urlCorrection(static::url('sitemap'));
        $this->seo()->canonicalUrl(static::url('sitemap', array(), true));
        $this->setMeta('sitemap', array(), $aData);

        $aData['cats'] = BBS::i()->catsListSitemap();
        if (!empty($aData['cats'])) {
            $aData['cats'] = array_chunk($aData['cats'], sizeof($aData['cats']) / 3);
        }

        return $this->viewPHP($aData, 'sitemap');
    }

    /**
     * Обработчик перехода по внешним ссылкам
     */
    public function away()
    {
        $sURL = $this->input->get('url', TYPE_STR);
        if (empty($sURL)) {
            $sURL = SITEURL;
        } else {
            $sURL = 'http://' . $sURL;
        }
        $this->redirect($sURL);
    }

    public function ajax()
    {
        $aResponse = array();

        switch ($this->input->getpost('act', TYPE_STR)) {
            default:
                $this->errors->impossible();
        }

        $aResponse['res'] = $this->errors->no();

        $this->ajaxResponse($aResponse);
    }

    /**
     * Cron: Формирование файла Sitemap.xml
     * Рекомендуемый период: раз в сутки
     */
    public function cronSitemapXML()
    {
        if (!bff::cron()) {
            return;
        }

        $data = array();

        # Посадочные страницы
        if (SEO::landingPagesEnabled()) {
            $data[] = SEO::model()->landingpagesSitemapXmlData();
        }

        # Объявления
        $data[] = BBS::model()->itemsSitemapXmlData();

        $sitemap = new CSitemapXML();
        $sitemap->buildIterator($data, 'sitemap', bff::path(''), bff::url(''), false, '');
    }
}