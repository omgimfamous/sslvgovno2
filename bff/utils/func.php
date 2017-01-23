<?php

/**
 * Вспомогательные функции
 * @version 0.43
 * @modified 13.jan.2015
 */
abstract class func
{
    /**
     * Перестраивание массива по ключу
     * @param array $aData массив
     * @param string $sByKey ключ
     * @param boolean $bOneInRows
     * @return array
     */
    public static function array_transparent($aData, $sByKey, $bOneInRows = false)
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

    /**
     * Multi-dimentions array sort, with ability to sort by two and more dimensions
     * $array = array_subsort($array [, 'col1' [, SORT_FLAG [, SORT_FLAG]]]...);
     * @return mixed
     */
    public static function array_subsort()
    {
        $args = func_get_args();
        $marray = array_shift($args);

        $i = 0;
        $msortline = "return(array_multisort(";
        foreach ($args as $arg) {
            $i++;
            if (is_string($arg)) {
                foreach ($marray as $row) {
                    $sortarr[$i][] = $row[$arg];
                }
            } else {
                $sortarr[$i] = $arg;
            }
            $msortline .= "\$sortarr[" . $i . "],";
        }
        $msortline .= "\$marray));";

        eval($msortline);

        return $marray;
    }

    /**
     * Получаем значение из массива SESSION
     * @param string $sKey ключ
     * @param mixed $mDefault значение по-умолчанию
     * @return mixed
     */
    public static function SESSION($sKey, $mDefault = false)
    {
        return (isset($_SESSION['SESSION'][$sKey]) ? $_SESSION['SESSION'][$sKey] : $mDefault);
    }

    /**
     * Сохраняем значение в массив SESSION
     * @param string $sKey ключ
     * @param mixed $mValue значение
     */
    public static function setSESSION($sKey, $mValue)
    {
        $_SESSION['SESSION'][$sKey] = $mValue;
    }

    /**
     * Парсинг даты/времени
     * @param string $sDatetime дата/время
     * @return array
     */
    public static function parse_datetime($sDatetime = '2006-04-05 01:50:00')
    {
        $arr = explode(' ', $sDatetime, 2);
        $arr_res = array('year' => '', 'month' => '', 'day' => '', 'hour' => '', 'min' => '', 'sec' => '');
        if (isset($arr[0])) {
            $arr_date = explode('-', $arr[0], 3);
            if (count($arr_date) == 3) {
                $arr_res['year'] = $arr_date[0];
                $arr_res['month'] = $arr_date[1];
                $arr_res['day'] = $arr_date[2];
            }
        }
        if (isset($arr[1])) {
            $arr_time = explode(':', $arr[1], 3);
            if (count($arr_time) == 3) {
                $arr_res['hour'] = $arr_time[0];
                $arr_res['min'] = $arr_time[1];
                $arr_res['sec'] = $arr_time[2];
            }
        }

        return $arr_res;
    }

    /**
     * Генератор случайной последовательности символов
     * @param integer $nLength кол-во символов (1-32)
     * @param boolean $bNumbersOnly только числа
     * @return string
     */
    public static function generator($nLength = 10, $bNumbersOnly = false)
    {
        if ($nLength > 32) {
            $nLength = 32;
        }
        if ($bNumbersOnly) {
            return mt_rand(($nLength > 1 ? pow(10, $nLength - 1) : 1), ($nLength > 1 ? pow(10, $nLength) - 1 : 9));
        }

        return substr(md5(uniqid(mt_rand(), true)), 0, $nLength);
    }

    /**
     * Транслитерация cyr->lat
     * @param string $text текст для транслитерации
     * @param boolean $isURL адаптировать для URL
     * @param string $encIn кодировка входящий строки
     * @param string $encOut кодировка выходящий строки
     * @return string
     */
    public static function translit($text, $isURL = true, $encIn = false, $encOut = false)
    {
        if (empty($encIn)) {
            $encIn = 'utf-8';
        }
        if (empty($encOut)) {
            $encOut = 'utf-8';
        }

        $text = iconv($encIn, 'utf-8', $text);
        $cyr = array(
            "Щ","Ш","Ч","Ц","Ю","Я","Ж","А","Б","В",
            "Г","Д","Е","Ё","З","И","І","Й","К","Л",
            "М","Н","О","П","Р","С","Т","У","Ф","Х",
            "Ь","Ы","Ъ","Э","Є","Ї","щ","ш","ч","ц",
            "ю","я","ж","а","б","в","г","д","е","ё",
            "з","и","і","й","к","л","м","н","о","п",
            "р","с","т","у","ф","х","ь","ы","ъ","э",
            "є","ї"
        );
        $lat = array(
            "Shh","Sh","Ch","C","Ju","Ja","Zh","A",
            "B","V","G","D","Je","Jo","Z","I","I",
            "J","K","L","M","N","O","P","R","S","T",
            "U","F","Kh","'","Y","`","E","Je","Ji",
            "shh","sh","ch","c","ju","ja","zh","a",
            "b","v","g","d","je","jo","z","i","i",
            "j","k","l","m","n","o","p","r","s",
            "t","u","f","kh","'","y","`","e","je","ji"
        );

        for ($i = 0; $i < count($cyr); $i++) {
            $c_cyr = $cyr[$i];
            $c_lat = $lat[$i];
            $text = str_replace($c_cyr, $c_lat, $text);
        }

        $text = preg_replace("/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]e/", "\${1}e", $text);
        $text = preg_replace("/([qwrtpsdfghklzxcvbnmQWRTPSDFGHKLZXCVBNM]+)[jJ]/", "\${1}'", $text);
        $text = preg_replace("/([eyuioaEYUIOA]+)[Kk]h/", "\${1}h", $text);
        $text = preg_replace("/^kh/", "h", $text);
        $text = preg_replace("/^Kh/", "H", $text);
        $text = preg_replace('/[\?&\']+/', '', $text);
        $text = preg_replace('/[\s,\?&]+/', '-', $text);
        if ($isURL) {
            $text = preg_replace('/[\/\'\"\(\)\=\\\]+/', '', $text);
            $text = preg_replace('/[^a-zA-Z0-9_\-]/', '', $text);
            $text = preg_replace("/\-+/", "-", $text); //сжимаем двойные "-"
        }

        return iconv('utf-8', $encOut, $text);
    }

    /**
     * Формирование JSON
     * @param mixed $a данные
     * @param boolean $noNumQuotes не обворачивать число в кавычки
     * @return string
     */
    public static function php2js($a = false, $noNumQuotes = false)
    {
        if (is_null($a)) {
            return 'null';
        }
        if ($a === false) {
            return 'false';
        }
        if ($a === true) {
            return 'true';
        }
        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                $a = str_replace(",", ".", strval($a));
            }

            // All scalars are converted to strings to avoid indeterminism.
            // PHP's "1" and 1 are equal for all PHP operators, but
            // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
            // we should get the same result in the JS frontend (string).
            // Character replacements for JSON.
            static $jsonReplaces = array(
                array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
                array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
            );
            if ($noNumQuotes && is_int($a)) {
                return $a;
            } else {
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v) {
                $result[] = self::php2js($v, $noNumQuotes);
            }

            return '[' . join(',', $result) . ']';
        } else {
            foreach ($a as $k => $v) {
                $result[] = self::php2js($k, $noNumQuotes) . ': ' . self::php2js($v, $noNumQuotes);
            }

            return '{' . join(',', $result) . '}';
        }
    }

    /**
     * Безопасный unserialize массива
     * @param mixed $data данные в сериализованном виде
     * @param mixed $default значение по-умолчанию
     * @return mixed
     */
    public static function unserialize($data, $default = array())
    {
        $data = strval($data);
        if (is_array($default)) {
            if (empty($data)) {
                return $default;
            }
            if (strpos($data, 'a:') !== 0) {
                if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
                    $data = base64_decode($data, true);
                    if (empty($data) || strpos($data, 'a:') !== 0) return $default;
                } else {
                    return $default;
                }
            }
            $data = unserialize($data);
            return ( ! empty($data) ? $data : $default );
        }
        return ( ! empty($data) ? unserialize($data) : $default );
    }

    /**
     * Проверка является ли строка сериализованной строкой
     * @param mixed $data
     * @return bool
     */
    public static function is_serialized($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if ($data[1] !== ':' || mb_strlen($data) < 4) {
            return false;
        }
        if (!preg_match('/^([adObis]):/', $data, $matches)) {
            return false;
        }
        switch ($matches[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$matches[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$matches[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}