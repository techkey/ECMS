{* comment.tpl *}
<div class="comment">
  <div class="comment-info">
    <span>Comment by <em>{$name}</em> on <em>{$created|date_format:"%A, %B %e, %Y"}</em> at <em>{$created|date_format:"%T"}</em></span>
    <span>
      {if isset($edit_link)}{$edit_link}{/if}
      {if isset($delete_link)}{$delete_link}{/if}
    </span>
  </div>
  <p>{$comment}</p>
</div>