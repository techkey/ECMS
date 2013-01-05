{* pager.tpl *}
{if $page != 0}
<div class="pager">
{if $page > 1}
  <a href="?page={$page - 1}">previous</a>
{/if}
  <a href="?page={$page + 1}">next</a>
</div>
{/if}