{* menu.twig *}
<div {$menu.attributes}>{*<p>{$menu.title}</p>*}
  <ul>
{foreach $menu.links as $link}
    <li><a href="{$link.path}">{$link.title}</a></li>
{/foreach}
  </ul>
</div>