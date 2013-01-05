{* messages.tpl *}
<div class="region messages container_15">
  {if isset($messages.error)}
    <div class="grid_15">
      <ul class="error-messages">
        {foreach $messages.error as $message}
          <li>{$message}</li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if isset($messages.warning)}
    <div class="grid_15">
      <ul class="warning-messages">
        {foreach $messages.warning as $message}
          <li>{$message}</li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if isset($messages.status)}
    <div class="grid_15">
      <ul class="status-messages">
        {foreach $messages.status as $message}
          <li>{$message}</li>
        {/foreach}
      </ul>
    </div>
  {/if}
</div>
