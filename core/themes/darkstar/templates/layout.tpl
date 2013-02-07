{* layout.tpl *}
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
{if isset($page_title) and $page_title != ''}
    <title>{$page_title} | {$site.name}</title>
{else}
    <title>{$site.name}</title>
{/if}
{include 'head.tpl'}
  </head>
  <body>
    <header>
      <div class="container_15">
        <div class="logo">
{if $system.install}
          <span>{$site.name}</span>
{else}
          <a href="{$base_path}">{$site.name}</a>
{/if}
        </div>{*<img src="{$theme_path}/images/logo.png" height="78" alt="logo">*}
{if isset($header)}
        <div class="region header">
          {$header|strip}
        </div>
{/if}
      </div>
    </header>
    <div class="page container_15">
{if isset($messages.error) or isset($messages.warning) or isset($messages.status)}
      <div class="region messages grid_15">
        {include 'messages.tpl'}
      </div>
{/if}
{if isset($page_top)}
      <div class="container_15">
        <div class="region page-top grid_15">
          {$page_top}
        </div>
      </div>
{/if}

      <div class="container_15">
        {if isset($sidebar_first) or isset($sidebar_second)}
          {$content_grid = 'grid_12'}
        {else}
          {$content_grid = 'grid_15'}
        {/if}

        {if isset($region_left)}
          <div class="region content {$content_grid}">
            {if isset($content_title) and $content_title != ''}
              <h1 class="content-title">{$content_title}</h1>
            {/if}
            {if isset($content)}
              {$content}
            {/if}
          </div>
        {/if}

        {if isset($sidebar_first) or isset($sidebar_second)}
          <div class="grid_3">
            {if isset($sidebar_first)}
              <div class="region sidebar-first">
                {$sidebar_first}
              </div>
            {/if}
            {if isset($sidebar_second)}
              <div class="region sidebar-second">
                {$sidebar_second|strip}
              </div>
            {/if}
          </div>
        {/if}

        {if !isset($region_left)}
          <div class="region content {$content_grid}">
            {if isset($content_title) and $content_title != ''}
              <h1 class="content-title">{$content_title}</h1>
            {/if}
            {if isset($content)}
              {$content}
            {/if}
          </div>
        {/if}

      </div>

{if isset($triptych_first) or isset($triptych_middle) or isset($triptych_last)}
      <div class="container_15">
{if isset($triptych_first)}
        <div class="region triptych-first grid_5">{$triptych_first}</div>
{/if}
{if isset($triptych_middle)}
        <div class="region triptych-middle grid_5">{$triptych_middle}</div>
{/if}
{if isset($triptych_last)}
        <div class="region triptych-last grid_5">{$triptych_last}</div>
{/if}
      </div>
{/if}
{if isset($page_bottom)}
      <div class="container_15">
        <div class="region page-bottom grid_15">
          {$page_bottom}
        </div>
      </div>
{/if}
    </div> <!-- page -->
    <footer class="container_15">
{if isset($footer_top)}
      <div class="region footer-top grid_15">{block 'footer_top'}{$footer_top}{/block}</div>
{/if}

{if isset($footer_firstcolumn) or isset($footer_secondcolumn) or isset($footer_thirdcolumn) or isset($footer_fourthcolumn) or isset($footer_fifthcolumn)}
      <div class="region footer-firstcolumn grid_3">
      {if isset($footer_firstcolumn)}
        {$footer_firstcolumn|strip}
      {/if}
      </div>
      <div class="region footer-secondcolumn grid_3">
      {if isset($footer_secondcolumn)}
        {$footer_secondcolumn|strip}
      {/if}
      </div>
      <div class="region footer-thirdcolumn grid_3">
      {if isset($footer_thirdcolumn)}
        {$footer_thirdcolumn|strip}
      {/if}
      </div>
      <div class="region footer-fourthcolumn grid_3">
      {if isset($footer_fourthcolumn)}
        {$footer_fourthcolumn|strip}
      {/if}
      </div>
      <div class="region footer-fifthcolumn grid_3">
      {if isset($footer_fifthcolumn)}
        {$footer_fifthcolumn|strip}
      {/if}
      </div>
{/if}
{if isset($footer_bottom)}
      <div class="region footer-bottom grid_15">{block 'footer_bottom'}{$footer_bottom}{/block}</div>
{/if}
    </footer>
  </body>
</html>
