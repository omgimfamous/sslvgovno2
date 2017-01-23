<div class="pagination pagination-centered">
    {if $pagenation|@count>1}
        <ul>
            {if $pgsPrev|default:''}<li><a href="#" onclick="{$pgsPrev}; return false;">&larr;</a></li>{/if}
            {foreach from=$pagenation name=pagenation item=v key=k}
                <li {if $v.active}class="active"{/if}>
                    <a href="#" onclick="{$v.link}; return false;">{$v.page}</a>
                </li>
            {/foreach}
            {if $pgsNext|default:''}<li><a href="#" onclick="{$pgsNext}; return false;">&rarr;</a></li>{/if}
        </ul>
    {/if}
</div>
