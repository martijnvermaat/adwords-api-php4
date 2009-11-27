<?php


/*
    AdWords API PHP4 client library
    Provides access to the Google AdWords API v2009 in PHP4.

    Version 0.1a

    Copyright 2009, Martijn Vermaat. All Rights Reserved.

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

        http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/


/*
  Constants to be used with the library.
*/
define('AW_MATCH_TYPE_EXACT', 'EXACT');
define('AW_MATCH_TYPE_PHRASE', 'PHRASE');
define('AW_MATCH_TYPE_BROAD', 'BROAD');
define('AW_USER_STATUS_ACTIVE', 'ACTIVE');
define('AW_USER_STATUS_DELETED', 'DELETED');
define('AW_USER_STATUS_PAUSED', 'PAUSED');


class AdWords {


/*
  All class variables are supposed to be handled as private to the class.
*/
var $email = '';
var $password = '';
var $client_email = '';
var $developer_token = '';
var $application_token = '';

var $auth_token = null;
var $services = array('AdGroupService'          => null,
                      'AdGroupCriterionService' => null);

var $auth_account_type = 'GOOGLE';
var $auth_service = 'adwords';

var $soap_endpoint = 'https://adwords-sandbox.google.com/api/adwords/cm/v200909';
var $soap_wsdl = 'wsdl';

var $namespace = 'https://adwords.google.com/api/adwords/cm/v200909';
var $user_agent = 'PHP4 Adwords API v2009 class';

var $last_request = null;
var $last_response = null;

var $error_code = null;
var $error_message = null;
var $error_details = null;


/*
  Constructor makes no requests. Authentication and Soap client instantiation
  is only done when needed.
*/
function Adwords($email, $password, $client_email, $developer_token,
                 $application_token, $application = '') {

    $this->email = $email;
    $this->password = $password;
    $this->client_email = $client_email;
    $this->developer_token = $developer_token;
    $this->application_token = $application_token;

    if ($application !== '') {
        $this->user_agent = $application.' ('.$this->user_agent.')';
    }

}


function get_campaigns($number = 0, $first = 0) {

    $selector = $this->__campaign_selector_ids(array(),
                                               $number,
                                               $first);

    return $this->__get('CampaignService', $selector);

}


function get_ad_groups_by_campaign($campaign_id, $number = 0, $first = 0) {

    $selector = $this->__ad_group_selector_campaign_id($campaign_id,
                                                       $number,
                                                       $first);

    return $this->__get('AdGroupService', $selector);

}


function get_criterion($ad_group_id, $criterion_id) {

    $selector = $this->__criteria_selector_id($ad_group_id,
                                              $criterion_id);

    $result = $this->__get('AdGroupCriterionService', $selector);

    if (!isset($result['entries']))
        return false;

    return $result['entries'][0];

}


function get_criteria_by_ad_group($ad_group_id, $number = 0, $first = 0) {

    $selector = $this->__criteria_selector_ad_group_id($ad_group_id,
                                                       $number,
                                                       $first);

    return $this->__get('AdGroupCriterionService', $selector);

}


function add_keyword($ad_group_id, $text, $match_type = AW_MATCH_TYPE_BROAD,
                     $user_status = null, $destination_url = null) {

    $result = $this->add_keywords($ad_group_id,
                  array(array('text'            => $text,
                              'match_type'      => $match_type,
                              'user_status'     => $user_status,
                              'destination_url' => $destination_url)));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


function add_keywords($ad_group_id, $keywords) {

    $operations = array();

    for ($i = 0; $i < count($keywords); $i++) {

        $k = $keywords[$i];

        if (!isset($k['match_type']))
            $k['match_type'] = AW_MATCH_TYPE_BROAD;

        $keyword = '<criterion xsi:type="Keyword">
                      <text>'.$k['text'].'</text>
                      <matchType>'.$k['match_type'].'</matchType>
                    </criterion>';

        $operations[] =
            $this->__make_criterion_operation('ADD',
                                              $ad_group_id,
                                              $keyword,
                                              $k['user_status'],
                                              $k['destination_url']);

    }

    return $this->__mutate('AdGroupCriterionService', $operations);

}


function add_placement($ad_group_id, $url, $user_status = null,
                       $destination_url = null) {

    $result = $this->add_placements($ad_group_id,
                  array(array('url'             => $url,
                              'user_status'     => $user_status,
                              'destination_url' => $destination_url)));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


function add_placements($ad_group_id, $placements) {

    $operations = array();

    for ($i = 0; $i < count($placements); $i++) {

        $p = $placements[$i];

        $placement = '<criterion xsi:type="Placement">
                        <url>'.$p['url'].'</url>
                      </criterion>';

        $operations[] =
            $this->__make_criterion_operation('ADD',
                                              $ad_group_id,
                                              $placement,
                                              $p['user_status'],
                                              $p['destination_url']);

    }

    return $this->__mutate('AdGroupCriterionService', $operations);

}


function delete_criterion($ad_group_id, $criterion_id) {

    $criterion = '<criterion>
                    <id>'.$criterion_id.'</id>
                  </criterion>';

    $operation = $this->__make_criterion_operation('REMOVE',
                                                   $ad_group_id,
                                                   $criterion);

    $result = $this->__mutate('AdGroupCriterionService', array($operation));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


function set_criterion_user_status($ad_group_id, $criterion_id,
                                   $user_status = AW_USER_STATUS_ACTIVE) {

    $criterion = '<criterion>
                    <id>77'.$criterion_id.'</id>
                  </criterion>';

    $operation = $this->__make_criterion_operation('SET',
                                                   $ad_group_id,
                                                   $criterion,
                                                   $user_status);

    $result = $this->__mutate('AdGroupCriterionService', array($operation));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


function __make_criterion_operation($operation_type, $ad_group_id, $criterion,
                                    $user_status = null,
                                    $destination_url = null) {

    $operand = '<adGroupId>'.$ad_group_id.'</adGroupId>'.$criterion;

    if (isset($user_status))
        $operand .= '<userStatus>'.$user_status.'</userStatus>';
    if (isset($destination_url))
        $operand .= '<destinationUrl>'.$destination_url.'</destinationUrl>';

    $operation = '<operator>'.$operation_type.'</operator>
                  <operand xsi:type="BiddableAdGroupCriterion">
                    '.$operand.'
                  </operand>';

    return $operation;

}


function __campaign_selector_ids($campaign_ids, $number, $first) {

    $paging = $this->__paging($number, $first);

    return '<selector>
              <ids>'.implode(' ', $campaign_ids).'</ids>
              '.$paging.'
            </selector>';

}


function __ad_group_selector_campaign_id($campaign_id, $number, $first) {

    $paging = $this->__paging($number, $first);

    return '<selector>
              <campaignId>'.$campaign_id.'</campaignId>
              '.$paging.'
            </selector>';

}


function __criteria_selector_id($ad_group_id, $criterion_id) {

    return '<selector>
              <idFilters>
                <adGroupId>'.$ad_group_id.'</adGroupId>
                <criterionId>'.$criterion_id.'</criterionId>
              </idFilters>
            </selector>';

}


function __criteria_selector_ad_group_id($ad_group_id, $number, $first) {

    $paging = $this->__paging($number, $first);

    return '<selector>
              <idFilters>
                <adGroupId>'.$ad_group_id.'</adGroupId>
              </idFilters>
              '.$paging.'
            </selector>';

}


function __paging($number, $first) {

    if ($number == 0) {
        return '';
    } else {
        return '<paging>
                  <startIndex>'.$first.'</startIndex>
                  <numberResults>'.$number.'</numberResults>
                </paging>';
    }

}


function __get($service, $selector) {

    $request = '<get xmlns="'.$this->namespace.'">'.$selector.'</get>';

    $result = $this->__call_service($service, $request, 'get');

    /* Make sure we always return a list of result entries */
    if (isset($result['entries']) && !$result['entries'][0]) {
        $result['entries'] = array($result['entries']);
    }

    return $result;

}


function __mutate($service, $operations) {

    $request = '<mutate xmlns="'.$this->namespace.'">
                  <operations>
                    '.implode(' ', $operations).'
                  </operations>
                </mutate>';

    $result = $this->__call_service($service, $request, 'mutate');

    /* Make sure we always return a list of result values */
    if (isset($result['value']) && !$result['value'][0]) {
        $result['value'] = array($result['value']);
    }

    return $result;

}


function __call_service($name, $request, $request_type = 'get') {

    $this->__reset_error();

    $token = $this->__get_auth_token();
    $auth_token = $token->get_auth_token();
    $service = $this->__get_service($name);

    if (!$auth_token) {
        $this->__set_error('AdWords:AuthToken',
                           'No authentication token received',
                           $token->res);
        return false;
    }

    $headers = '<RequestHeader xmlns="'.$this->namespace.'">
                  <authToken>'.$auth_token.'</authToken>
                  <clientEmail>'.$this->client_email.'</clientEmai>
                  <userAgent>'.$this->user_agent.'</userAgent>
                  <developerToken>'.$this->developer_token.'</developerToken>
                  <applicationToken>
                    '.$this->application_token.'
                  </applicationToken>
                </RequestHeader>';

    $service->setHeaders($headers);
    $service->soap_defencoding = 'UTF-8';

    $response = $service->call($request_type, $request);

    $this->last_request = $service->request;
    $this->last_response = $service->response;

    if ($service->fault) {
        $this->__set_error($service->faultcode, $service->faultstring, $response);
        return false;
    }

    if (!isset($response['rval'])) {
        $this->__set_error('AdWords:Client', 'No return value', $response);
        return false;
    }

    return $response['rval'];

}


/*
  Get authentication token for Adwords and create it if it does not yet exist.
*/
function __get_auth_token() {

    if ($this->auth_token === null) {

        $this->auth_token =
            $this->__create_auth_token($this->email,
                                       $this->password,
                                       $this->auth_account_type,
                                       $this->auth_service);

    }

    return $this->auth_token;

}


/*
  Get Soap client for an Adwords service and create it if it does not yet
  exist.
*/
function __get_service($service) {

    if ($this->services[$service] === null) {

        $url = $this->soap_endpoint.'/'.$service.'?wsdl';

        $this->services[$service] =
            $this->__create_soap_client($url, $this->soap_wsdl);

    }

    return $this->services[$service];

}


/*
  Create new authentication token.
*/
function __create_auth_token($email, $password, $account_type, $service) {

    return new AuthToken($email, $password, $account_type, $service);

}


/*
  Create new Soap client using the NuSOAP library.
*/
function __create_soap_client($endpoint, $wsdl = false, $proxyhost = false,
                           $proxyport = false, $proxyusername = false,
                           $proxypassword = false, $timeout = 0,
                           $response_timeout = 30) {

    if (!extension_loaded('soap')) {
        return new soapclient($endpoint, $wsdl, $proxyhost, $proxyport,
                              $proxyusername, $proxypassword, $timeout,
                              $response_timeout);
    } else {
        return new nusoap_client($endpoint, $wsdl, $proxyhost, $proxyport,
                                 $proxyusername, $proxypassword, $timeout,
                                 $response_timeout);
    }

}


function get_http_request() {

    return $this->last_request;

}


function get_http_response() {

    return $this->last_response;

}


function get_error() {

    if ($this->error_code === null)
        return false;

    return array('code' => $this->error_code,
                 'message' => $this->error_message,
                 'details' => $this->error_details);

}


function __reset_error() {

    $this->__set_error();

}


function __set_error($error_code = null, $error_message = null,
                     $error_details = null) {

    $this->error_code = $error_code;
    $this->error_message = $error_message;
    $this->error_details = $error_details;

}


}


?>
