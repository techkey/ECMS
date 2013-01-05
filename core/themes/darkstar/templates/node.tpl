{* node.tpl *}
{if ($node.show_author) or ($node.show_create_date) or ($node.show_editor) or ($node.show_edit_date) or isset($node.edit_link)}
  <div class="node-info">
  {if ($node.show_author) and ($node.show_create_date)}
    Created by <span>{$node.author}</span> on <span>{$node.create_date|date_format:"%A, %B %e, %Y"}</span>.
  {elseif ($node.show_author)}
    Created by <span>{$node.author}</span>.
  {elseif ($node.show_create_date)}
    Created on <span>{$node.create_date|date_format:"%A, %B %e, %Y"}</span>.
  {/if}
  {if ($node.show_editor) and ($node.show_edit_date)}
    Edited by <span>{$node.editor}</span> on <span>{$node.edit_date|date_format:"%A, %B %e, %Y"}</span>.
  {elseif ($node.show_editor)}
    Edited by <span>{$node.editor}</span>.
  {elseif ($node.show_edit_date)}
    Edited on <span>{$node.edit_date|date_format:"%A, %B %e, %Y"}</span>.
  {/if}
  {if isset($node.edit_link)}
    {$node.edit_link}
  {/if}
  </div>
{/if}
<div {$node.attributes}>{$node.content}</div>
{if isset($comments)}
  <div class="comments">{$comments}</div>
{/if}
{if isset($comment_form)}
<div class="comment-form">
  <h3>Leave a comment</h3>
  {$comment_form}
</div>
{/if}