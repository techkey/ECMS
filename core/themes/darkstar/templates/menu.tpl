{* menu.tpl *}
{strip}
<div {$menu.attributes}>{*<p>{$menu.title}</p>*}
  <ul>
{foreach $menu.links as $link}
    <li>{$link}</li>
{/foreach}
  </ul>
</div>
{/strip}