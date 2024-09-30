{if (isset($status) == true) && ($status == 'ok')}
    <h3>{l s='Your order is confirmed.' mod='deltaplan'}</h3>
    <p>
        <br />{l s='Amount' mod='deltaplan'}: <span class="price"><strong>{$total}</strong></span>
        <br /><br />{l s='An email has been sent with this information.' mod='deltaplan'}
        <br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='deltaplan'} <a href="{$link->getPageLink('contact', true)}">{l s='expert customer support team.' mod='deltaplan'}</a>
    </p>
{else}
    <h3>{l s='Your order has not been accepted.' mod='deltaplan'}</h3>
    <p>
        <br /><br />{l s='Please, try to order again.' mod='deltaplan'}
        <br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='deltaplan'} <a href="{$link->getPageLink('contact', true)}">{l s='expert customer support team.' mod='deltaplan'}</a>
    </p>
{/if}
<hr />
