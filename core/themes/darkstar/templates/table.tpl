{* table.twig *}
<table {$attributes}>
  {if isset($caption)}<caption>{$caption}</caption>{/if}
  <thead>
    <tr>
      {$count = 0}
      {foreach $header as $head}
      {$count = $count + 1}
      <th {$head.attributes}>{$head.data}</th>
      {/foreach}
    </tr>
  </thead>
  <tbody>
    {foreach $rows as $row}
    <tr>
    {foreach $row as $cell}
      <td {$cell.attributes}>{$cell.data}</td>
    {/foreach}
    </tr>
    {foreachelse}
    <tr><td colspan="{$count}">Nothing to show.</td></tr>
    {/foreach}
  </tbody>
</table>
