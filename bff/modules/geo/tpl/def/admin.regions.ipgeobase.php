<?php

?>
<?= tplAdmin::blockStart('Настройки сайта / Регионы / IpGeoBase', false); ?>
<form action="" id="j-regions-ipgeobase-form">
<table class="admtbl tbledit">
    <tr>
        <td class="row1">
            <p>
                Выполнить синхронизацию базы <a href="http://ipgeobase.ru/" target="_blank">IpGeoBase</a> для городов <strong>России и Украины</strong>
            </p>
            <p>
                Предварительно необходимо скачать актуальный архив <strong>geo_files.zip</strong> или <strong>geo_files.tar.gz</strong> с <a href="http://ipgeobase.ru/cgi-bin/Archive.cgi" target="_blank">этой страницы</a>.<br />
                И разархивировать его в директорию "<?= PATH_BASE.'files'.DS.'ipgeobase'.DS ?>", обновив тем самым файлы cidr_optim.txt и cities.txt.
            </p>
        </td>
    </tr>
    <tr class="footer">
        <td class="row1">
            <input type="button" class="btn btn-success button submit" value="Синхронизировать" onclick="syncIpGeoBase($(this));" />
            <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
            <div class="progress" id="j-regions-ipgeobase-progress" style="display: none; margin-left: 10px;"></div>
        </td>
    </tr>
</table>
</form>
<script type="text/javascript">
<?php js::start(); ?>
    function syncIpGeoBase($btn)
    {
        var btnTitle = $btn.prop('disabled',true).val();
        var $progress = $('#j-regions-ipgeobase-progress');
        bff.ajax('<?= $this->adminLink(bff::$event) ?>', $('#j-regions-ipgeobase-form').serialize(), function(data, errors){
            if(data && data.success) {
                bff.success('Синхронизация базы IpGeoBase прошла успешно, синхронизировано '+data.city_mathed+' городов');
            } else {
                bff.error(errors);
            }
        }, function(process){
            $btn.val( process ? 'Подождите...' : btnTitle ).prop('disabled', process);
            $progress.toggle();
        });
    }
<?php js::stop(); ?>
</script>