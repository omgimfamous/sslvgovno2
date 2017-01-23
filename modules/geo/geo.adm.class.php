<?php

class Geo extends GeoBase
{
    public function ajax()
    {
        $aResponse = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            /**
             * Список станций метро города
             * @param int $nCityID ID города
             */
            case 'city-metro':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $aData = static::cityMetro($nCityID, 0, 'select');
                $aResponse['html'] = $aData['html'];
            } break;
            case 'country-presuggest':
            {
                $nCountryID = $this->input->postget('country', TYPE_UINT);
                $mResult = false;
                if ($nCountryID) {
                    $aData = static::regionPreSuggest($nCountryID, true);
                    $mResult = array();
                    foreach ($aData as $v) {
                        $mResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid']);
                    }
                }
                $this->ajaxResponse($mResult);
            } break;
            case 'district-options':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $mEmpty = $this->input->postget('empty', TYPE_NOTAGS);
                if (!$mEmpty) {
                    $mEmpty = false;
                }
                $aResponse['html'] = static::districtOptions($nCityID, 0, $mEmpty);
            } break;
        }

        $this->ajaxResponseForm($aResponse);
    }


}