{strip}{* block.tpl *}
<div {$attributes}>
{if $title != ''}
  <h3>{$title}</h3>
{/if}
  {$content}
</div>{/strip}