{* menu.twig *}
{strip}
<div {$menu.attributes}>{*<p>{$menu.title}</p>*}
  <ul>
{foreach $menu.links as $link}
    {*<li><a href="{$link.path}">{$link.title}</a></li>*}
    <li>{$link}</li>
{/foreach}
  </ul>
</div>
{/strip}