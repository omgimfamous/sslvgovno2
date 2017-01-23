<?php namespace bff\base;

/**
 * Базовый класс работы с отображением шаблонов
 * @version 0.1
 * @modified 10.feb.2014
 */

class View
{
    /** @var string $layout текущий layout */
    protected static $layout = 'main';

    /**
     * Рендеринг шаблона (php)
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string $templateName_ название шаблона (без расширения ".php")
     * @param string|boolean $templateDir_ путь к шаблону или false - используем TPL_PATH
     * @param boolean $display_ отображать(true), возвращать результат(false)
     * @return mixed
     */
    public static function renderTemplate(array &$aData, $templateName_, $templateDir_ = false, $display_ = false)
    {
        $_tpl = ($templateDir_ === false ? TPL_PATH : rtrim($templateDir_, DIRECTORY_SEPARATOR . ' ')) . DIRECTORY_SEPARATOR . $templateName_ . '.php';

        extract($aData, EXTR_REFS);

        if (!$display_) {
            ob_start();
            ob_implicit_flush(false);
            require($_tpl);
            return ltrim(ob_get_clean());
        } else {
            require($_tpl);
        }
    }

    /**
     * Рендеринг layout шаблона (php)
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string|boolean $layoutName название layout'a (без расширения ".php")
     * @param string|boolean $templateDir путь к шаблону или false - используем TPL_PATH
     * @param boolean $display отображать(true), возвращать результат(false)
     * @return mixed
     */
    public static function renderLayout(array &$aData, $layoutName = false, $templateDir = false, $display = false)
    {
        return static::renderTemplate($aData, 'layout.' . (empty($layoutName) ? static::getLayout() : $layoutName), $templateDir, $display);
    }

    /**
     * Устанавливаем layout
     * @param string $layoutName название
     * @return string
     */
    public static function setLayout($layoutName = '')
    {
        static::$layout = $layoutName;
    }

    /**
     * Получаем текущий layout
     * @return string
     */
    public static function getLayout()
    {
        return static::$layout;
    }
}