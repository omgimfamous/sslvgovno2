<table class="table table-condensed table-hover admtbl tbledit">
    <tr><td class="row1" width="200">Операционная система:</td><td class="row2"><?= $os_version ?></td></tr>
    <tr><td class="row1">Версия PHP:</td><td class="row2"><?= $php_version ?></td></tr>
    <tr><td class="row1">MySQL Client Library:</td><td class="row2"><?= $mysql ?></td></tr>
    <tr><td class="row1">Версия GD:</td><td class="row2"><?= $gd_version ?></td></tr>
    <tr><td class="row1">Путь к ImageMagick:</td><td class="row2"><?= $img_imagick ?></td></tr>
    <tr><td class="row1">Расширение PDO:</td><td class="row2"><?= $extension_pdo ?></td></tr>
    <tr><td class="row1">Расширение SPL:</td><td class="row2"><?= $extension_spl ?></td></tr>
    <tr><td class="row1">Расширение mbstring:</td><td class="row2"><?= $mbstring ?></td></tr>
    <tr><td class="row1">Расширение mcrypt:</td><td class="row2"><?= $extension_mcrypt ?></td></tr>
    <tr><td class="row1">Расширение gettext:</td><td class="row2"><?= $extension_gettext ?></td></tr>
    <tr><td class="row1">Модуль mod_rewrite (apache):</td><td class="row2"><?= $mod_rewrite ?></td></tr>
    <tr><td class="row1">Безопасный режим:</td><td class="row2"><?= $safemode ?></td></tr>
    <tr><td class="row1">Выделено оперативной памяти:</td><td class="row2"><?= $maxmemory ?></td></tr>
    <tr><td class="row1">Максимальный время исполнения скипта (сек):</td><td class="row2"><?= $maxexecution ?></td></tr>
    <tr><td class="row1">Максимальный размер загружаемого файла:</td>
        <td class="row2">
            upload_max_filesize: <strong><?= $maxupload ?></strong><br />
            post_max_size: <strong><?= $maxpost ?></strong><br />
        </td>
    </tr>
    <tr><td class="row1">Отключенные функции:</td><td class="row2"><?= $disabled_functions ?></td></tr>
    <tr><td class="row1">open_basedir:</td><td class="row2"><?= $open_basedir ?></td></tr>
</table>