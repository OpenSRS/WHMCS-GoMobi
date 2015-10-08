<?php
/**********************************************************************
 *  OpenSRS - GoMobi WHMCS module
 * *
 *
 *  CREATED BY Tucows Co       ->    http://www.opensrs.com
 *  CONTACT                    ->	 help@tucows.com
 *  Version                    -> 	 2.0.1
 *  Release Date               -> 	 07/10/14
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

//a little helper for mysql
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

//load language
$_LANG = opensrs_gomobi_loadLanguage();
$this->assign('lang',  $_LANG);

//prepare connecting data
$template_vars = $this->get_template_vars();
$params = array
(
    'accountid'      =>  $template_vars['accountid'],
    'serviceid'      =>  $template_vars['serviceid'],
    'domain'         =>  $template_vars['domain'],
    'configoption1'  =>  $template_vars['configoption1'],
    'configoption2'  =>  $template_vars['configoption2'],
    'configoption3'  =>  $template_vars['configoption3'],
    'configoption4'  =>  $template_vars['configoption4'],
    'customfields'   =>  $template_vars['customfields'],
    
);

//managing aliases is enabled?
$this->assign('alias_management', $params['configoption4']);

//do something with user request
$ret = null;
switch($_REQUEST['modaction'])
{
    case 'alias_delete':
        if(isset($_REQUEST['alias']) && $params['configoption4'])
        {
            $params['hostname'] = $_REQUEST['alias'];
            $ret = opensrs_gomobi_DeleteAlias($params);
        }
    break;
    
    case 'alias_add':
        if(isset($_REQUEST['alias']) && $params['configoption4'])
        {
            $params['hostname'] = $_REQUEST['alias'];
            $ret = opensrs_gomobi_CreateAlias($params);
        }
    break;
    
    case 'update':
        $params['domain'] = $params['customfields']['Domain'] = $_REQUEST['domain'];
        $params['source_domain'] = $_REQUEST['source_domain'];
        
        $ret = opensrs_gomobi_UpdatePublishing($params);
        
        if($ret['status'] == 1)
        {
            $params['domain'] = $_REQUEST['domain'];
            $params['customfields']['Domain'] = $_REQUEST['domain'];
            $params['customfields']['Source domain'] = $_REQUEST['source_domain'];
        }
    break;
    
    case 'get_redirection_code':
        $params['language'] = $_REQUEST['programming_language'];
        $ret = opensrs_gomobi_getRedirectionCode($params);
        if($ret['status'] == 1)
        {
            $vars['generated_code'] = $ret['code'];
        }
        else
        {
            $vars['error'] = opensrs_gomobi_translate($ret['message']);
        }
    break;
}
 

$attr = opensrs_gomobi_ServiceInfo($params);

$vars['aliases'] = $attr['attributes']['aliases'];
$vars['source_domain'] = $attr['attributes']['gomobi']['source_domain'];
$vars['domain'] =  $attr['attributes']['domain'];


if(isset($ret['message']) && $ret['message'])// Are we have any info?
{
    $m  = '';
    $message = str_replace(array($_REQUEST['alias'], $vars['domain'], $_REQUEST['source_domain'], $_REQUEST['new_domain']), array('%alias%', '%domain%', '%source%', '%new%'), $ret['message']);
    if(isset($_LANG[$message]))
    {
        $message = str_replace(array('%alias%', '%domain%', '%source%', '%new%'), array($_REQUEST['alias'], $vars['domain'], $_REQUEST['source_domain'], $_REQUEST['new_domain']), $_LANG[$message]);
        $m = $message;
    }
    else
        $m = $ret['message'];
    
    if($ret['status'] == 1)
        $vars['info'] = $m;
    else
        $vars['error'] = $m;
}

foreach($vars as $key => $var)
{
    $this->assign($key, $var);
}
?>