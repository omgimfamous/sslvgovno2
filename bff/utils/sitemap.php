<?php

/**
 * Компонент формирования sitemap.xml файлов
 * @version 0.2
 * @modified 29.августа.2013
 */
class CSitemapXML extends Component
{
    public $limit = 50000;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Формируем sitemap.xml файл(ы) на основе списка ссылок($data)
     * @param array $data @ref список ссылок вида: array( array('l'=>string, 'm'=>string), ... )
     * @param string $filename имя sitemap.xml файла, без расширения '.xml'
     * @param string $path путь к sitemap.xml файлу
     * @param string $url URL путь к sitemap.xml файлу
     * @param boolean $gzip
     */
    public function build(&$data, $filename, $path, $url, $gzip = true)
    {
        if (sizeof($data) > $this->limit) {
            $dataChunks = array_chunk($data, $this->limit, true);
            unset($data);
            $i = 1;
            $indexData = array();
            foreach ($dataChunks as $k => $data) {
                $file = $filename . ($i++) . '.xml';
                $indexData[] = $this->_buildSitemap($data, $path, $file, $gzip);
                unset($dataChunks[$k]);
            }

            return $this->_buildSitemapIndex($indexData, $path . $filename . '_index.xml', $url);
        } else {
            return $this->_buildSitemap($data, $path, $filename . '.xml', $gzip);
        }
    }

    /**
     * Формируем sitemap.index.xml файл на основе списка sitemap.xml файлов
     * @param array $files список sitemap.xml файлов, формат: array(array(path=>полный путь к файлу, url=>доп. URL), ...)
     * @param string $filepath полный путь к sitemap.index.xml файлу
     * @param string $url базовый URL путь к sitemap.xml файлам или "пустая строка"
     */
    public function buildIndex($files, $filepath, $url = '')
    {
        if (empty($filepath) || !file_exists($filepath)) {
            return false;
        }
        $filesData = array();
        foreach ($files as $v) {
            if (!empty($v['path']) && file_exists($v['path'])) {
                $filesData[] = array(
                    'file' => $v['url'] . (!empty($v['file']) ? $v['file'] : basename($v['path'])),
                    'm'    => (!empty($v['m']) ? $v['m'] : filemtime($v['path'])),
                );
            }
        }

        return $this->_buildSitemapIndex($filesData, $filepath, $url);
    }

    private function _buildSitemap($data, $path, $filename, $gzip = true)
    {
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $f = $this->_fopen($path . $filename);
        if ($f === false) {
            return false;
        }

        fwrite($f, $header);

        foreach ($data as $v) {
            /*
            <url>
              <loc>http://www.example.com/</loc>
              <lastmod>2005-01-01</lastmod>
            </url>
            */
            fwrite($f, "<url>\n<loc>" . $v['l'] . "</loc>\n<lastmod>" . $v['m'] . "</lastmod>\n</url>\n");
        }

        fwrite($f, '</urlset>');
        fclose($f);

        if ($gzip) {
            $this->_gzip($path . $filename);
            $filename = $filename . '.gz';
        }

        return array('file' => $filename, 'path' => $path . $filename, 'm' => filemtime($path . $filename));
    }

    private function _buildSitemapIndex($files, $filepath, $url)
    {
        $f = $this->_fopen($filepath);
        if ($f === false) {
            return '';
        }

        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        fwrite($f, $header);

        foreach ($files as $v) {
            /*
            <sitemap>
              <loc>http://www.example.com/sitemap1.xml.gz</loc>
              <lastmod>2004-10-01T18:23:17+00:00</lastmod>
            </sitemap>
            */
            if ($v !== false) {
                fwrite($f, "<sitemap>\n<loc>" . $url . '/' . $v['file'] . "</loc>\n<lastmod>" . date('Y-m-d', $v['m']) . "</lastmod>\n</sitemap>\n");
            }
        }

        fwrite($f, '</sitemapindex>');

        return array('file' => basename($filepath), 'path' => $filepath, 'm' => filemtime($filepath));
    }

    private function _gzip($filename)
    {
        $fp = $this->_fopen($filename . '.gz');
        if ($fp === false) {
            return false;
        }

        fwrite($fp, gzencode(implode('', file($filename)), 9));
        fclose($fp);
        unlink($filename);

        return true;
    }

    private function _fopen($sFilepath)
    {
        $fp = @fopen($sFilepath, 'w');
        if ($fp === false) {
            $this->errors->set('Не удалось открыть файл "' . $sFilepath . '" на запись');
        }

        return $fp;
    }
}