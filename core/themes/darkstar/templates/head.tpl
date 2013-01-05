{* head.tpl *}
{block 'head_css'}{foreach $head.css as $style}
    <link type="text/css" rel="stylesheet" href="{$style}">
{/foreach}{/block}
{block 'head_inline_css'}{if isset($head.inline_css)}
    <style type="text/css">
      {$head.inline_css}
    </style>
{/if}{/block}
{block 'head_js'}{foreach $head.js as $script}
    <script type="text/javascript" src="{$script}"></script>
{/foreach}{/block}
{block 'head_inline_js'}{if isset($head.inline_js)}
  <script type="text/javascript">
    <!--//--><![CDATA[//><!--
    {$head.inline_js}
    //--><!]]>
  </script>
{/if}{/block}