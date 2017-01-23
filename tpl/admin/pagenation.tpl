<div class="pagination pagination-centered">
    {if $pagenation|@count>1}
        <ul>
            {if $pgsPrev|default:''}<li><a href="{$pgsPrev}">&larr;</a></li>{/if}
            {foreach from=$pagenation name=pagenation item=v key=k}
                <li {if $v.active}class="active"{/if}>
                    <a href="{$v.link}">{$v.page}</a>
                </li>
            {/foreach}
            {if $pgsNext|default:''}<li><a href="{$pgsNext}">&rarr;</a></li>{/if}
        </ul>
    {/if}
</div>