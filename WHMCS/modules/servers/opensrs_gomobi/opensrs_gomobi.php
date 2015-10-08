<?php
/**********************************************************************
 *  OpenSRS - GoMobi WHMCS module
 * *
 *
 *
 *  CREATED BY Tucows Co       ->    http://www.opensrs.com
 *  CONTACT                    ->	 help@tucows.com
 *  Version                    -> 	 2.0.2
 *  Release Date               -> 	 03/10/15
 *
 *
 * Copyright (C) 2014 by Tucows Co/OpenSRS.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 **********************************************************************/

defined('DS') ? null : define('DS',DIRECTORY_SEPARATOR);
if(!defined('PS')) define('PS', PATH_SEPARATOR);
if(!defined('CRLF')) define('CRLF', "\r\n");
require_once dirname(__FILE__).DS.'core'.DS.'openSRS.php';

//GLOBAL
$opensrs_gomobi_language = array();

//little mysql helper
if(function_exists('mysql_safequery') == false) {
    function mysql_safequery($query,$params=false) {
        if ($params) {
            foreach ($params as &$v) { $v = mysql_real_escape_string($v); }
            $sql_query = vsprintf( str_replace("?","'%s'",$query), $params );
            $sql_query = mysql_query($sql_query);
        } else {
            $sql_query = mysql_query($query);
        }
        return ($sql_query);
    }
}


/**
 * Default configuration for module
 * @return type 
 */
function opensrs_gomobi_ConfigOptions() 
{
    
    mysql_safequery('CREATE TABLE IF NOT EXISTS `opensrs_gomobi_orders`
    (
        `account_id` INT(11) NOT NULL,
        `data` TEXT,
        UNIQUE KEY(`account_id`)
    ) DEFAULT CHARACTER SET UTF8 ENGINE = MyISAM');
    
    //WELCOME EMAIL
    $q = mysql_safequery('SELECT COUNT(*) as `count` FROM tblemailtemplates WHERE name = "OpenSRS - GoMobi Welcome Email"');
    $row = mysql_fetch_assoc($q);
    if(!mysql_num_rows($q) || !$row['count'])
    {
        mysql_safequery("INSERT INTO `tblemailtemplates` (`type` ,`name` ,`subject` ,`message` ,`fromname` ,`fromemail` ,`disabled` ,`custom` ,`language` ,`copyto` ,`plaintext` )VALUES ('product', 'OpenSRS - GoMobi Welcome Email', 'GoMobi', '<p>Dear {\$client_name},</p>
        <p>Your order for {\$service_product_name} has now been activated. Please keep this message for your records.</p>
        <p>Product/Service: {\$service_product_name}<br /> Payment Method: {\$service_payment_method}<br /> Amount: {\$service_recurring_amount}<br /> Billing Cycle: {\$service_billing_cycle}<br /> Next Due Date: {\$service_next_due_date}</p>
        <p>Thank you for choosing us.</p>
        <p>{\$signature}</p>', '', '', '', '1', '', '', '0')");
    }
    else
    {
        mysql_safequery("UPDATE tblemailtemplates SET custom = 1 WHERE name = 'OpenSRS - GoMobi Welcome Email'");
    }
    
    return array
    (
        'username'          =>  array
        (
            'FriendlyName'  =>  'Username',
            'Type'          =>  'text',
            'Size'          =>  '25'
        ),
        'apikey'            =>  array
        (
            'FriendlyName'  =>  'API Key',
            'Type'          =>  'text',
            'Size'          =>  '25'
        ),
        'test'              =>  array
        (
            'FriendlyName'  =>  'Test Mode',
            'Type'          =>  'yesno',
        ),
        'management'        =>  array
        (
            'FriendlyName'  =>  'Alias Management',
            'Type'          =>  'yesno',
            'Description'   =>  'Enable Alias Management in client area'
        )
    );
} 

/**
 * Create new GoMobi service
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_CreateAccount($params) 
{
    $domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    $source_domain = $params['customfields']['Source Domain'];
    
    //GET SUFFIX FOR USERNAME
    $q = mysql_query("SELECT COUNT(id) as `count` FROM tblhosting WHERE username = ?", array(substr($domain, 0 ,8)));
    $row = mysql_fetch_assoc($q);
    $sufix = $row['count'] ? $row['count'] : 0;
    $username = $params['username'] ? $params['username'] : substr($domain, 0 ,8).$sufix;
    //update username
    if(!$params['username'])
    {
        $q = mysql_safequery("UPDATE tblhosting SET username = ? WHERE id = ?", array($username, $params['serviceid']));
    }
    //get password
    $password = $params['password'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'create',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
            'source_domain'         =>  $source_domain,
            'end_user_auth_info'    =>  array
            (
                'email_address'     =>  $params['clientsdetails']['email'],
                'password'          =>  $password,
                'username'          =>  $username,
            )
        )
    );
    
    $openSRS->send($send);
    if($openSRS->isSuccess())
    {
        $data = array
        (
            'domain'    =>  $domain,
        );
        mysql_safequery('REPLACE INTO opensrs_gomobi_orders SET account_id = ?, data = ?', array($params['serviceid'], serialize($data)));
        
        mysql_safequery("UPDATE tblhosting SET username = '', password = '', domain = ? WHERE id = ?", array(
            $domain,
            $params['serviceid']
        ));
        return 'success';   
    }
    
   return opensrs_gomobi_translate($openSRS->getError());
}

/**
 * Terminate GoMobi service
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_TerminateAccount($params) 
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'delete',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
        )
    );
    
    $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        return opensrs_gomobi_translate($openSRS->getError());
    }
    
    return 'success';
}

/**
 * suspend account. Account will be disabled for client
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_SuspendAccount($params) 
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'disable',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
        )
    );
    
    $ret = $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        return opensrs_gomobi_translate($openSRS->getError());
    }
    
    return 'success';
}

/**
 * Unsuspend account
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_UnsuspendAccount($params) 
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'enable',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
        )
    );
    
    $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        return $openSRS->getError();
    }
    
    return 'success';
}

/**
 * create alias for gomobi service
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_CreateAlias($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $hostname = $params['hostname'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'create_alias',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
            'hostname'              =>  $hostname
        )
    );
    
    $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        return array
        (
            'status'    =>  0,
            'message'   =>  $openSRS->getError(),
        );
    }
    
    return array
    (
        'status'    =>  1,
        'message'   =>  $openSRS->getInfo(),
    );
}

/**
 * delete existing alias from gomobi service
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_DeleteAlias($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $hostname = $params['hostname'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'delete_alias',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
            'hostname'              =>  $hostname
        )
    );
    
    $ret = $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        return array
        (
            'status'    =>  0,
            'message'   =>  $openSRS->getError(),
        );
    }
    
    return array
    (
        'status'    =>  1,
        'message'   =>  $openSRS->getInfo(),
    );
}

/**
 * get all aliases
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_GetAliasList($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $hostname = $params['hostname'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'get_aliast_list',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
        )
    );
    
    $r = $openSRS->send($send);
    if($openSRS->hasError())
    {
        return opensrs_gomobi_translate($openSRS->getError());
    }
    
    if(isset($r['attributes']['aliases']))
    {
        $aliases = array();
        foreach($r['attributes']['aloases'] as $a)
        {
            $aliases[] = $a;
        }
        
        return $aliases;
    }
    
    return array();
}


/**
 * get some info about our service
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_ServiceInfo($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $hostname = $params['hostname'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'get_service_info',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
        )
    );
    
    return $openSRS->send($send);
}

/**
 * update source domain for
 * @param type $params
 * @return type 
 */ 
function opensrs_gomobi_UpdatePublishing($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $new_domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    $source_domain = $params['source_domain'] ? $params['source_domain'] : $params['customfields']['Source Domain'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'update',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
            'new_domain'            =>  $new_domain,
            'source_domain'         =>  $source_domain
        )
    );
    
    $openSRS->send($send);
    if(!$openSRS->isSuccess())
    {
        mysql_query("UPDATE tblcustomfieldsvalues
            LEFT JOIN tblcustomfields ON (tblcustomfields.id = tblcustomfieldsvalues.fieldid)
            SET value = '".$domain."'
            WHERE tblcustomfields.fieldname LIKE 'Domain%' AND tblcustomfields.type = 'product' AND tblcustomfieldsvalues.relid=".$params['serviceid']);
        mysql_query("UPDATE tblhosting SET domain = '".$domain."' WHERE id = ".$params['serviceid']) or die(mysql_error());
        
        return array
        (
            'status'    =>  0,
            'message'   =>  $openSRS->getError(),
        );
    }
    
    mysql_query("UPDATE tblcustomfieldsvalues
        LEFT JOIN tblcustomfields ON (tblcustomfields.id = tblcustomfieldsvalues.fieldid)
        SET value = '".$new_domain."'
        WHERE tblcustomfields.fieldname LIKE 'Domain%' AND tblcustomfields.type = 'product' AND tblcustomfieldsvalues.relid=".$params['serviceid']) or die(mysql_error());
    mysql_query("UPDATE tblcustomfieldsvalues
        LEFT JOIN tblcustomfields ON (tblcustomfields.id = tblcustomfieldsvalues.fieldid)
        SET value = '".$source_domain."'
        WHERE tblcustomfields.fieldname LIKE 'Source Domain%' AND tblcustomfields.type = 'product' AND tblcustomfieldsvalues.relid=".$params['serviceid']) or die(mysql_error());
    mysql_query("UPDATE tblhosting SET domain = '".$new_domain."' WHERE id = ".$params['serviceid']) or die(mysql_error());
    opensrs_gomobi_updateDomain($params['serviceid'], $new_domain);
    
    return array
    (
        'status'    =>  1,
        'message'   =>  $openSRS->getInfo(),
    );
}

/**
 * Display login button in admin area
 * @return string 
 */
function opensrs_gomobi_ClientAreaCustomButtonArray() 
{
    $_LANG = opensrs_gomobi_loadLanguage();
    $buttonarray = array(
        opensrs_gomobi_translate('login') => "login",
    );
    return $buttonarray;
}

/**
 * redirect client to gomobi service
 * @param type $params 
 */
function opensrs_gomobi_login($params)
{
    $url = opensrs_gomobi_loginButton($params);
    if($url !== false)
    {
        header('Location: '.$url);
        die();
    }

    ob_clean();
    header('location: clientarea.php?action=productdetails&id='.$params['serviceid']);
    die();
}

/**
 * Display some useful info in admin panel
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_AdminServicesTabFields($params) 
{
    $attr = opensrs_gomobi_ServiceInfo($params);
    if($attr['is_success'] == 1)
    {
        $attr = $attr['attributes'];
        $status = $attr['status'];
        $aliases = $attr['aliases'];
        $billing_date = $attr['billing_date'];

        $fieldsarray = array
        (
            '<b>Service details</b>'    =>  '<div style="background-color: #fff">
                                              <table>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_gomobi_translate('status').'</b></td>
                                                    <td>'.($status ? $status : '-').'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_gomobi_translate('aliases').'</b></td>
                                                    <td>'.($aliases ? implode($aliases, '<br />') : ' - ').'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_gomobi_translate('billing_date').'</b></td>
                                                    <td>'.($billing_date ? $billing_date : '-').'</td>
                                                </tr>
                                              </table>
                                          </div>',
        );
        return $fieldsarray;
    }
}

/**
 * update publishing details
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_AdminServicesTabFieldsSave($params) 
{
    $domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    mysql_safequery("UPDATE tblhosting SET domain = ? WHERE id = ?", array(
        $domain,
        $params['serviceid']
    ));
            
            
    $q = mysql_safequery('SELECT domainstatus FROM tblhosting WHERE id = ?', $params['serviceid']);
    $row = mysql_fetch_assoc($q);
    
    if($row['domainstatus'] == 'Active' && opensrs_gomobi_getDomain($params['accountid']))
    {
        $s = opensrs_gomobi_UpdatePublishing($params);
        
        if($s['status'] == 1)
        {
            return "success";
        }

        return $s['message'];
    }
    
    opensrs_gomobi_updateDomain($params['accountid'], $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain']);
}

/**
 * Generate link to login page
 * @param type $params
 * @return type 
 */
function opensrs_gomobi_loginButton($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'get_control_panel_url',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'          =>  'gomobi',
            'domain'                =>  $domain,
            'language'              =>  'en'
        )
    );
    
    $ret = $openSRS->send($send);
    if(!$openSRS->isSuccess())
        return false;
    
    return $ret['attributes']['control_panel_url'];
}


function opensrs_gomobi_getRedirectionCode($params)
{
    $domain = opensrs_gomobi_getDomain($params['serviceid']);
    $language = $params['language'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'generate_redirection_code',
        'object'        =>  'publishing',
        'attributes'    =>  array
        (
            'service_type'              =>  'gomobi',
            'domain'                    =>  $domain,
            'programming_language'      =>  $language
        )
    );
    
    $ret = $openSRS->send($send);
    if($openSRS->isSuccess())
    {
        return array
        (
            'status'    =>  1,
            'code'      =>  $ret['attributes']['redirection_code']
        );
    }
    
    return array
    (
        'status'    =>  0,
        'message'   =>  $openSRS->getError()
    );
}

/**
 * load language!
 * @global array $opensrs_gomobi_language
 * @return array 
 */
function opensrs_gomobi_loadLanguage()
{
    GLOBAL $opensrs_gomobi_language;
    if($opensrs_gomobi_language)
        return $opensrs_gomobi_language;
    
    $language = null;
    if(isset($_SESSION['Language'])) // GET LANG FROM SESSION
    { 
        $language = strtolower($_SESSION['Language']);
    }
    else
    {
        $q = mysql_safequery("SELECT language FROM tblclients WHERE id = ?", array($_SESSION['uid']));
        $row = mysql_fetch_assoc($q); 
        if($row['language'])
            $language = $row['language'];
    }
    
    if(!$language) //Ouuuh?
    {
        $q = mysql_safequery("SELECT value FROM tblconfiguration WHERE setting = 'Language' LIMIT 1");
        $row = mysql_fetch_assoc($q);
        $language = $row['language'];
    }
    $langfilename = dirname(__FILE__).DS.'lang'.DS.$language.'.php';
    $deflangfilename = dirname(__FILE__).DS.'lang'.DS.'english.php';
    if(file_exists($langfilename)) 
        include($langfilename);
    else
        include($deflangfilename);
    
    $opensrs_gomobi_language = $_LANG;
    
    return $_LANG;
}

function opensrs_gomobi_translate($key)
{
    $_LANG = opensrs_gomobi_loadLanguage();
    if(isset($_LANG[$key]))
        return $_LANG[$key];
    
    return $key;
}

function opensrs_gomobi_getDomain($account_id)
{
    $q = mysql_safequery("SELECT * FROM opensrs_gomobi_orders WHERE account_id = ?", array($account_id));
    $row = mysql_fetch_assoc($q);
    $domain = unserialize($row['data']);
    
    $domain = $domain['domain'];
    return $domain;
}

function opensrs_gomobi_updateDomain($account_id, $domain)
{
    $q = mysql_safequery("SELECT * FROM opensrs_gomobi_orders WHERE account_id = ?", array($account_id));
    $row = mysql_fetch_assoc($q);
    $row = unserialize($row['data']);
    
    $arr = array
    (
        'domain'    =>  $domain,
    );
    
    if(mysql_num_rows($q))
    {
        $q = mysql_safequery('UPDATE opensrs_gomobi_orders SET data = ? WHERE account_id = ?', array(serialize($arr), $account_id));
    }
    else
    {
        $q = mysql_safequery('INSERT INTO opensrs_gomobi_orders (`data`, `account_id`) VALUE(?, ?)', array(serialize($arr), $account_id));
    }
    
    //Update WHMCS Domain
    mysql_safequery("UPDATE tblhosting SET domain = ? WHERE id = ?", array(
        $domain,
        $account_id
    )); 
}