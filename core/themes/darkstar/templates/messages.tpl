{* messages.tpl *}
{if isset($messages.error)}
<ul class="error-messages">
  {foreach $messages.error as $message}
    <li>{$message}</li>
  {/foreach}
</ul>
{/if}
{if isset($messages.warning)}
<ul class="warning-messages">
  {foreach $messages.warning as $message}
    <li>{$message}</li>
  {/foreach}
</ul>
{/if}
{if isset($messages.status)}
<ul class="status-messages">
  {foreach $messages.status as $message}
    <li>{$message}</li>
  {/foreach}
</ul>
{/if}
