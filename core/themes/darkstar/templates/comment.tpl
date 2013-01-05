{* comment.tpl *}
<div class="comment">
  <div class="comment-info">
    <em>{$name}</em> commented on <em>{$created|date_format:"%A, %B %e, %Y"}</em> at <em>{$created|date_format:"%T"}</em>
    {if isset($edit_link)}, {$edit_link}{/if}:
    {if isset($delete_link)}, {$delete_link}{/if}:
  </div>
  <p>{$comment}</p>
</div>