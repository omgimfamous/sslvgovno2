<?php

class CCaptchaProtection
{
    public $params;
    public $gendata;

    public function __construct()
    {
        $this->params = array(
            'unique'  => '()*&%^%&^&*ASl)88ydUYSiosh0&^$%#2a+_*(D-a[BIOS]',
            'numbers' => '1-1, 2-2, 3-3, 4-4, 5-5, 6-6, 7-7, 8-8, 9-9',
            //'numbers' => '1-один, 2-два, 3-три, 4-четыре, 5-пять, 6-шесть, 7-семь, 8-восемь, 9-девять',
            //'numbers' => '1-one, 2-two, 3-three, 4-four, 5-five, 6-six, 7-seven, 8-eight, 9-nine',
        );
    }

    public function generateMath($bOnlyPlusAction = true)
    {
        $num_array = $this->numbers2array($this->params['numbers']);
        $rand_keys = array_rand($num_array, 2);
        $nSignAction = (!$bOnlyPlusAction ? (rand(0, 1) == 0) : 1);
        $this->gendata['operand1'] = $num_array[$rand_keys[0]];
        $this->gendata['operand2'] = $num_array[$rand_keys[1]];
        $this->gendata['sign'] = ($nSignAction ? '+' : 'x');
        $this->gendata['text'] = $this->gendata['operand1'] . ' ' . $this->gendata['sign'] . ' ' . $this->gendata['operand2'] . ' =';
        $this->gendata['result'] = $this->generate_hash(($nSignAction ? ($rand_keys[0] + $rand_keys[1]) : ($rand_keys[0] * $rand_keys[1])), date('j'));

        return $this->gendata['result'];
    }

    public function valid($actualResult, $userEntered)
    {
        if ($userEntered == '') {
            return false;
        }

        $userEntered = preg_replace('/[^0-9]/', '', $userEntered); // оставляем только цифры

        if ($actualResult != $this->generate_hash($userEntered, date('j'))) {
            if ((date('G') <= 1) AND ($actualResult == $this->generate_hash($userEntered, (intval(date('j')) - 1)))) {
                return true;
            }

            return false;
        }

        return true;
    }


    /**
     * конвертируем "1-один, 2-два, 3-три, ..."
     * в массив Array([1] => один, [2] => два, [3] => три, ...)
     */
    public function numbers2array($string)
    {
        $string = str_replace(' ', '', $string); //убираем пробелы
        $arr = explode(',', $string);
        $aResult = array();
        foreach ($arr as $v) {
            $aTemp = explode('-', $v);
            $aResult[$aTemp[0]] = $aTemp[1];
        }

        return $aResult;
    }

    public function generate_hash($inputstring, $day)
    {
        # Время модификации данного файла
        $inputstring .= filemtime(__FILE__);
        # Уникальную строку из params
        $inputstring .= $this->params['unique'];
        # IP адрес сервера
        $inputstring .= getenv('SERVER_ADDR');
        # Текущая дата
        $inputstring .= $day . date('ny');
        # MD5
        $enc = strrev(md5($inputstring));

        # Возвращаем несколько символов
        return (substr($enc, 28, 1) . substr($enc, 9, 1) . substr($enc, 21, 1) . substr($enc, 15, 1) . substr($enc, 7, 1));
    }

    public static function correct($actualResult, $userEntered)
    {
        $oProtection = new self();

        return (!empty($userEntered) && $oProtection->valid($actualResult, $userEntered));
    }
}