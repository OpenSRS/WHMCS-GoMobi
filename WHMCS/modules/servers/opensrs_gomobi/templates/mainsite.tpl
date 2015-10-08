{literal} 
    <style type="text/css">
        form.mg-form
        {
        }

        ul.mg-form
        {
            list-style-type: none;
        }

        .leftcontainer
        {
            float: left;
            width: 20%;
        }
        .centercontainer
        {
            float: right;
            width: 80%;
        }
    </style>
{/literal}
{if $error}
    <div class="alert-message error">
        <p class="bold textcenter">{$error}</p>
    </div>
{/if}
 
{if $info}
    <div class="alert-message info">
        <p class="bold textcenter">{$info}</p>
    </div>
{/if}

<input type="hidden" id="product_id" value="{$productid}" /> 

{literal} 
        <script type="text/javascript">
            var lang = new Object();{/literal}
            {foreach from=$lang item=l key=k}
                {literal}lang['{/literal}{$k|replace:"'":""}{literal}'] = '{/literal}{$l|replace:"'":""}{literal}';{/literal}
            {/foreach}
         {literal} 
        </script>
{/literal} 
{if $cpe_js}<script type="text/javascript">{$cpe_js}</script>{/if}


<div>

{if $mainsiteerror == ''}    
    
<div class="leftcontainer">
<h3>{$main_lang.main_header}</h3>
<ul class="mainmenu">
                    <li><img class="manage_img" src="modules/servers/cpanel_extended/img/back.png"/> <a href="clientarea.php?action=productdetails&id={$serviceid}">{$main_lang.menu_back_link}</a>
                    
                    <li><img class="manage_img" src="modules/servers/cpanel_extended/img/ftp.png"/> <a {if $currpage=="aliases"}onclick="return false" class="selected"{/if} href="clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=management&page=aliases">{$lang.aliases}Aliases</a></li>

                    <li><img class="manage_img" src="modules/servers/cpanel_extended/img/emails.png"/> <a {if $currpage=="settings"}onclick="return false" class="selected"{/if} href="clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=management&page=settings">{$lang.settings}Settings</a></li>
                    
    </ul>
</div>

<div class="centercontainer">

{$pagefile}
{if $pageloaderror == ''}

    {if $page == 'aliases' || $page == 'settings'}
        
        {include file=$pagefile}
    {/if}

{else}
    <strong>{$pageloaderror}</strong>
{/if}
</div>


{else}
    <h5 style="text-align: center">{$mainsiteerror}</h5>
{/if} 

</div>

<div style="clear: both"></div>

 