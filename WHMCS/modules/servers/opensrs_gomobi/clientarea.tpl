{php}##################DO NOT EDIT BELOW THIS LINE#############################
$_template_dir = $template->joined_template_dir;
$_maindir = substr($_template_dir, 0 , strpos($_template_dir, DIRECTORY_SEPARATOR.'templates')).DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'opensrs_gomobi'.DIRECTORY_SEPARATOR;

if(!file_exists($_maindir.'clientarea_controller.php'))
{
    $_template_dir = str_replace("/", "\\", $_template_dir);
    $_maindir = substr($_template_dir, 0 , strpos($_template_dir, DIRECTORY_SEPARATOR.'templates')).DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'opensrs_gomobi'.DIRECTORY_SEPARATOR;
    
    if(!file_exists($_maindir.'clientarea_controller.php'))
    {
        echo '<br />File name: clientarea_controller.php';
        echo '<br />File dir: '.'modules'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'opensrs_gomobi'.DIRECTORY_SEPARATOR;
        echo '<br />WHMCS template dir: '.$_template_dir;
        echo '<br />Full path: '.$_maindir.'clientarea_controller.php';
        die("<br />Please report this message to tech support");
    }
}

require_once($_maindir.'clientarea_controller.php');
##################YOU CAN EDIT ABOVE THIS LINE#############################
{/php}
{literal}
<style>
    .moduleoutput
    {
        border: 0 !important;
        text-align: left !important;
        padding: 0!important;
    }
 
    .mg-form ul
    {
        list-style-type: none;
        margin: 0;
    }

    .mg-form li
    {
        overflow: hidden;
        padding: 4px 0 4px 0;
    }

    .mg-form label
    {
        width: 150px;
        float: left;
        text-align: left;
    }

    .mg-form input[type="text"]
    {
        float: left;
    }

    .input-domain
    {
        width: 300px;
    }

    .mg-form .btn-remove
    {
        color: #F00!important;
    }

    .mg-table
    {
        width: 100%;
    }
    button.btn {width:30%!important;}
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

<div style="text-align: center; margin-top: 20px;">
    <button class="btn" onclick="window.open('clientarea.php?action=productdetails&id={$serviceid}&modop=custom&a=login', '_blank'); return false;"><img class="manage_img" src="modules/servers/opensrs_gomobi/img/keys.png"/>{$lang.login}</button> 
</div>

<h4 style="margin-top: 20px">{$lang.settings}</h4>
<form class="mg-form" action="clientarea.php?action=productdetails&id={$serviceid}" method="post">
    <ul class="mg-form">
        <li>
            <label>{$lang.domain}</label>
            <input type="text" name="domain" value="{$domain}" class="input-domain"/>
        </li>
        <li>
            <label>{$lang.source_domain}</label>
            <input type="text" name="source_domain" value="{$source_domain}" class="input-domain" />
        </li>
        <li style="text-align: center">
            <input type="hidden" name="modaction" value="update" />
            <input class="btn" type="submit" value="{$lang.update}" />
        </li>
    </ul>
</form>

{if $alias_management}
    <h4 style="margin-top: 20px">{$lang.aliases}</h4>
    {if $aliases}
        <table class="mg-table">
            {foreach from=$aliases item=alias}
                <tr>
                    <td>{$alias}</td>
                    <td>
                        <form action="clientarea.php?action=productdetails&id={$serviceid}" method="post" style="float: right" class="mg-form">
                            <input type="hidden" name="alias" value="{$alias}" />
                            <input type="hidden" name="modaction" value="alias_delete" />
                            <input type="submit" value="{$lang.delete}" class="btn-remove btn" />
                        </form>
                    </td>
                </tr>
            {/foreach}
        </table>
    {else}
        <p style="text-align: center; font-weight:bold">{$lang.no_aliases}</p>
    {/if}

    <h4>{$lang.add_new_alias}</h4>
    <form class="mg-form" action="clientarea.php?action=productdetails&id={$serviceid}" method="post">
        <ul class="mg-form">
            <li>
                <input type="hidden" name="modaction" value="alias_add" /> 
                <label style="width: 250px">{$lang.add_new}</label>
                <input type="text" name="alias" value="" class="input-domain" style="width: 230px">
                <input class="btn" type="submit" value="{$lang.add}" style="margin-left: 10px; float: right"/>
            </li>
        </ul>
    </form>
{/if}

<h4 style="margin-top: 20px">{$lang.redirection_code}</h4>
<form class="mg-form" action="clientarea.php?action=productdetails&id={$serviceid}" method="post">
    <ul class="mg-form">
        <li>
            <input type="hidden" name="modaction" value="get_redirection_code" /> 
            <label style="width: 250px">{$lang.programing_language}</label>
            <select name="programming_language">
                <option value="asp">ASP</option>
                <option value="htaccess">HTACCESS</option>
                <option value="javascript">JavaScript</option>
                <option value="jsp">JSP</option>
                <option value="php">PHP</option>
            </select>
            <input class="btn" type="submit" value="{$lang.get_redirection_code}" style="margin-left: 10px; float: right"/>
        </li>
    </ul>
</form>

{if $generated_code}
<textarea style="width: 100%; height: 300px; margin-top: 20px;">{$generated_code}</textarea>
{/if}