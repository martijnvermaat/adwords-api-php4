<?php


/*
    AdWords API PHP4 Client Library
    Provides access to the Google AdWords API v2009 in PHP4.

    Version <VERSION>, <DATE>

    Copyright 2010, Martijn Vermaat. All Rights Reserved.

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
  USAGE  Before instantiating the AdWords class, make sure the following are
  available and included in your code:
  - NuSOAP library (http://sourceforge.net/projects/nusoap/)
  - AuthToken.php, modified for PHP4 (comes with this library)

  PHP must have cURL support enabled.

  See the example directory for an example application.
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
define('AW_ENDPOINT_LIVE',
       'https://adwords.google.com/api/adwords/cm/v200909');
define('AW_ENDPOINT_SANDBOX',
       'https://adwords-sandbox.google.com/api/adwords/cm/v200909');


class AdWords {


/*
  All class variables are supposed to be handled as private to the class.
*/
var $email = '';
var $password = '';
var $sandbox = true;
var $client_email = '';
var $developer_token = '';
var $application_token = '';

var $auth_token = null;
var $services = array('AdGroupService'          => null,
                      'AdGroupCriterionService' => null);

var $auth_account_type = 'GOOGLE';
var $auth_service = 'adwords';

var $soap_endpoint = AW_ENDPOINT_SANDBOX;
var $soap_wsdl = 'wsdl';

var $namespace = 'https://adwords.google.com/api/adwords/cm/v200909';
var $user_agent = 'AdWords API PHP4 Client Library';

var $last_request = null;
var $last_response = null;

var $error_code = null;
var $error_message = null;
var $error_details = null;


/*
  Constructor makes no requests. Authentication and Soap client instantiation
  is only done when needed.

  @param  $email              Google account email address
  @param  $password           Google account password
  @param  $sandbox            operate in the sandbox environment
  @param  $client_email       client email address
  @param  $developer_token    developer token
  @param  $application_token  application token
  @param  $application        application name
*/
function AdWords($email, $password, $sandbox = true, $client_email = '',
                 $developer_token = '', $application_token = '',
                 $application = '') {

    $this->email = $email;
    $this->password = $password;
    $this->sandbox = $sandbox;

    if ($this->sandbox) {
        $this->client_email = 'client_1+'.$this->email;
        $this->developer_token = $this->email.'++EUR';
        $this->application_token = '';
        $this->soap_endpoint = AW_ENDPOINT_SANDBOX;
    } else {
        $this->client_email = $client_email;
        $this->developer_token = $developer_token;
        $this->application_token = $application_token;
        $this->soap_endpoint = AW_ENDPOINT_LIVE;
    }

    if ($application !== '') {
        $this->user_agent = $application.' ('.$this->user_agent.')';
    }

}


/*
  Get a page with campaigns.

  @param  $number  number of entries per page (0 for only one page)
  @param  $first   index of first entry on page
  @return          page of campaigns
*/
function get_campaigns($number = 0, $first = 0) {

    $selector = $this->__campaign_selector_ids(array(),
                                               $number,
                                               $first);

    return $this->__do_get('CampaignService', $selector);

}


/*
  Get a page with ad groups for a campaign.

  @param  $campaign_id  campaign id
  @param  $number       number of entries per page (0 for only one page)
  @param  $first        index of first entry on page
  @return               page of ad groups
*/
function get_ad_groups_by_campaign($campaign_id, $number = 0, $first = 0) {

    $selector = $this->__ad_group_selector_campaign_id($campaign_id,
                                                       $number,
                                                       $first);

    return $this->__do_get('AdGroupService', $selector);

}


/*
  Get a criterion.

  @param  $ad_group_id   ad group id
  @param  $criterion_id  criterion id
  @return                criterion
*/
function get_criterion($ad_group_id, $criterion_id) {

    $selector = $this->__criteria_selector_ids(
                    array(array('ad_group_id'  => $ad_group_id,
                                'criterion_id' => $criterion_id)));

    $result = $this->__do_get('AdGroupCriterionService', $selector);

    if (!isset($result['entries']))
        return false;

    return $result['entries'][0];

}


/*
  Get a page with criteria.

  @param  $criteria       array of criteria to be selected, with fields as the
                          the get_criterion() parameters
  @param  $user_statuses  array of user set statuses (array() for any)
  @param  $number         number of entries per page (0 for only one page)
  @param  $first          index of first entry on page
  @return                 page of criteria
*/
function get_criteria($criteria, $user_statuses = array(), $number = 0,
                      $first = 0) {

    $selector = $this->__criteria_selector_ids($criteria,
                                               $user_statuses,
                                               $number,
                                               $first);

    return $this->__do_get('AdGroupCriterionService', $selector);

}


/*
  Get a page with criteria for an ad group.

  @param  $ad_group_id    ad group id
  @param  $user_statuses  array of user set statuses (array() for any)
  @param  $number         number of entries per page (0 for only one page)
  @param  $first          index of first entry on page
  @return                 page of criteria
*/
function get_criteria_by_ad_group($ad_group_id, $user_statuses = array(),
                                  $number = 0, $first = 0) {

    $selector = $this->__criteria_selector_ad_group_id($ad_group_id,
                                                       $user_statuses,
                                                       $number,
                                                       $first);

    return $this->__do_get('AdGroupCriterionService', $selector);

}


/*
  Add a keyword criterion for an ad group.

  @param  $ad_group_id      ad group id
  @param  $text             keyword text
  @param  $match_type       keyword match type
  @param  $user_status      user set status
  @param  $destination_url  destination url override
  @return                   added keyword criterion
*/
function add_keyword($ad_group_id, $text, $match_type = AW_MATCH_TYPE_BROAD,
                     $user_status = null, $destination_url = null) {

    $result = $this->add_keywords(
                  array(array('ad_group_id'     => $ad_group_id,
                              'text'            => $text,
                              'match_type'      => $match_type,
                              'user_status'     => $user_status,
                              'destination_url' => $destination_url)));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


/*
  Add keyword criteria for ad groups.

  @param  $keywords         array of keywords to be added, with fields as
                            the add_keyword() parameters
  @return                   list of added keyword criteria
*/
function add_keywords($keywords) {

    $operations = array();

    for ($i = 0; $i < count($keywords); $i++) {

        $k = $keywords[$i];

        if (!isset($k['match_type']))
            $k['match_type'] = AW_MATCH_TYPE_BROAD;

        $keyword = '<criterion xsi:type="Keyword">
                      <text>'.$this->__xml($k['text']).'</text>
                      <matchType>'.
                        $this->__xml($k['match_type'])
                      .'</matchType>
                    </criterion>';

        $operations[] =
            $this->__make_criterion_operation('ADD',
                                              $k['ad_group_id'],
                                              $keyword,
                                              $k['user_status'],
                                              $k['destination_url']);

    }

    return $this->__do_mutate('AdGroupCriterionService', $operations);

}


/*
  Add a placement criterion for an ad group.

  @param  $ad_group_id      ad group id
  @param  $url              placement url
  @param  $user_status      user set status
  @param  $destination_url  destination url override
  @return                   added placement criterion
*/
function add_placement($ad_group_id, $url, $user_status = null,
                       $destination_url = null) {

    $result = $this->add_placements(
                  array(array('ad_group_id'     => $ad_group_id,
                              'url'             => $url,
                              'user_status'     => $user_status,
                              'destination_url' => $destination_url)));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


/*
  Add placement criteria for ad groups.

  @param  $placements       array of placements to be added, with fields as
                            the add_keyword() parameters
  @return                   list of added keyword criteria
*/
function add_placements($placements) {

    $operations = array();

    for ($i = 0; $i < count($placements); $i++) {

        $p = $placements[$i];

        $placement = '<criterion xsi:type="Placement">
                        <url>'.$this->__xml($p['url']).'</url>
                      </criterion>';

        $operations[] =
            $this->__make_criterion_operation('ADD',
                                              $p['ad_group_id'],
                                              $placement,
                                              $p['user_status'],
                                              $p['destination_url']);

    }

    return $this->__do_mutate('AdGroupCriterionService', $operations);

}


/*
  Delete a criterion.

  @param  $ad_group_id   ad group id
  @param  $criterion_id  criterion id
  @return                deleted criterion
*/
function delete_criterion($ad_group_id, $criterion_id) {

    $criterion = '<criterion>
                    <id>'.$this->__xml($criterion_id).'</id>
                  </criterion>';

    $operation = $this->__make_criterion_operation('REMOVE',
                                                   $ad_group_id,
                                                   $criterion);

    $result = $this->__do_mutate('AdGroupCriterionService',
                                 array($operation));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


/*
  Set user status for a criterion.

  @param  $ad_group_id   ad group id
  @param  $criterion_id  criterion id
  @param  $user_status   user set status
  @return                updated criterion
*/
function set_criterion_user_status($ad_group_id, $criterion_id,
                                   $user_status = AW_USER_STATUS_ACTIVE) {

    $criterion = '<criterion>
                    <id>'.$this->__xml($criterion_id).'</id>
                  </criterion>';

    $operation = $this->__make_criterion_operation('SET',
                                                   $ad_group_id,
                                                   $criterion,
                                                   $user_status);

    $result = $this->__do_mutate('AdGroupCriterionService',
                                 array($operation));

    if (!isset($result['value']))
        return false;

    return $result['value'][0];

}


/*
  Set user statuses for criteria.

  @param  $criteria  array of criteria to be updated, with fields as the
                     set_criterion_user_status() parameters
  @return            list of updated criteria
*/
function set_criteria_user_statuses($criteria) {

    $operations = array();

    for ($i = 0; $i < count($criteria); $i++) {

        $c = $criteria[$i];

        $criterion = '<criterion>
                        <id>'.$this->__xml($c['criterion_id']).'</id>
                      </criterion>';

        $operations[] = $this->__make_criterion_operation('SET',
                                                          $c['ad_group_id'],
                                                          $criterion,
                                                          $c['user_status']);

    }

    return $this->__do_mutate('AdGroupCriterionService', $operations);

}


/*
  Get raw HTTP request message for last API call.

  @return  raw HTTP request message
*/
function get_http_request() {

    return $this->last_request;

}


/*
  Get raw HTTP response message for last API call.

  @return  raw HTTP response message
*/
function get_http_response() {

    return $this->last_response;

}


/*
  Check if there was an error. Get error information with get_error().

  @return  true if an error occured, false otherwise
*/
function error_occurred() {

    return ($this->error_code !== null);

}


/*
  Get information for last error occurence.

  @return  array with fields:
           error_code     short string describing where the error occured
           error_message  descriptive error message
           error_details  possible detailed information on the error, not in
                          a predescribed format (use print_r() on it)
*/
function get_error() {

    if ($this->error_code === null)
        return false;

    return array('code' => $this->error_code,
                 'message' => $this->error_message,
                 'details' => $this->error_details);

}


/*
  Check if we are operating in the sandbox environment.

  @return  true if we use the sandbox, false otherwise
*/
function using_sandbox() {

    return $this->sandbox;

}


/*
  Private: construct an operation on a criterion.
*/
function __make_criterion_operation($operation_type, $ad_group_id, $criterion,
                                    $user_status = null,
                                    $destination_url = null) {

    $operand = '<adGroupId>'.$this->__xml($ad_group_id).'</adGroupId>
               '.$criterion;

    if (isset($user_status))
        $operand .= '<userStatus>'.
                      $this->__xml($user_status)
                    .'</userStatus>';
    if (isset($destination_url))
        $operand .= '<destinationUrl>'.
                      $this->__xml($destination_url)
                    .'</destinationUrl>';

    $operation = '<operations>
                    <operator>'.
                      $this->__xml($operation_type)
                    .'</operator>
                    <operand xsi:type="BiddableAdGroupCriterion">
                      '.$operand.'
                    </operand>
                  </operations>';

    return $operation;

}


/*
  Private: construct a campaign selector using campaign ids.
*/
function __campaign_selector_ids($campaign_ids, $number, $first) {

    $paging = $this->__paging($number, $first);

    return '<selector>
              <ids>'.$this->__xml(implode(' ', $campaign_ids)).'</ids>
              '.$paging.'
            </selector>';

}


/*
  Private: construct an ad group selector using campaign id.
*/
function __ad_group_selector_campaign_id($campaign_id, $number, $first) {

    $paging = $this->__paging($number, $first);

    return '<selector>
              <campaignId>'.$this->__xml($campaign_id).'</campaignId>
              '.$paging.'
            </selector>';

}


/*
  Private: construct a criterion selector using pairs of ad group id and
  criterion id.
*/
function __criteria_selector_ids($criteria, $statuses, $number = 0,
                                 $first = 0) {

    $paging = $this->__paging($number, $first);

    $statuses = $this->__statuses($statuses);

    $filters = array();

    for ($i = 0; $i < count($criteria); $i++) {

        $ad_group_id = $criteria[$i]['ad_group_id'];
        $criterion_id = $criteria[$i]['criterion_id'];

        $filters[] = '<idFilters>
                        <adGroupId>'.$this->__xml($ad_group_id).'</adGroupId>
                        <criterionId>'.
                          $this->__xml($criterion_id)
                        .'</criterionId>
                      </idFilters>';

    }

    return '<selector>'.
             implode(' ', $filters).$paging.$statuses
           .'</selector>';

}


/*
  Private: construct a criterion selector using ad group id.
*/
function __criteria_selector_ad_group_id($ad_group_id, $statuses, $number,
                                         $first) {

    $paging = $this->__paging($number, $first);

    $statuses = $this->__statuses($statuses);

    return '<selector>
              <idFilters>
                <adGroupId>'.$this->__xml($ad_group_id).'</adGroupId>
              </idFilters>
              '.$paging.$statuses.'
            </selector>';

}


/*
  Private: construct a paging selector.
*/
function __paging($number, $first) {

    if ($number == 0) {
        return '';
    } else {
        return '<paging>
                  <startIndex>'.$this->__xml($first).'</startIndex>
                  <numberResults>'.
                    $this->__xml($number)
                  .'</numberResults>
                </paging>';
    }

}


/*
  Private: construct status selectors.
*/
function __statuses($statuses) {

    $selectors = '';

    for ($i = 0; $i < count($statuses); $i++) {
        $selectors .= '<userStatuses>'.
                        $this->__xml($statuses[$i])
                      .'</userStatuses>';
    }

    return $selectors;

}


/*
  Private: do a get operation on service using selector.
*/
function __do_get($service, $selector) {

    $request = '<get xmlns="'.$this->__xml($this->namespace).'">'.
                 $selector
               .'</get>';

    $result = $this->__call_service($service, $request, 'get');

    /* Make sure we always return a list of result entries */
    if (isset($result['entries']) && !$result['entries'][0]) {
        $result['entries'] = array($result['entries']);
    }

    return $result;

}


/*
  Private: do mutate operations on service.
*/
function __do_mutate($service, $operations) {

    $request = '<mutate xmlns="'.$this->__xml($this->namespace).'">
                  '.implode(' ', $operations).'
                </mutate>';

    $result = $this->__call_service($service, $request, 'mutate');

    /* Make sure we always return a list of result values */
    if (isset($result['value']) && !$result['value'][0]) {
        $result['value'] = array($result['value']);
    }

    return $result;

}


/*
  Private: make request on service.
*/
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

    $headers = '<RequestHeader xmlns="'.
                $this->__xml($this->namespace).'">
                  <authToken>'.$this->__xml($auth_token).'</authToken>
                  <clientEmail>'.
                    $this->__xml($this->client_email)
                  .'</clientEmail>
                  <userAgent>'.
                    $this->__xml($this->user_agent)
                  .'</userAgent>
                  <developerToken>'.
                    $this->__xml($this->developer_token)
                  .'</developerToken>
                  <applicationToken>'.
                    $this->__xml($this->application_token)
                  .'</applicationToken>
                </RequestHeader>';

    $service->setHeaders($headers);
    $service->soap_defencoding = 'UTF-8';

    $response = $service->call($request_type, $request);

    $this->last_request = $service->request;
    $this->last_response = $service->response;

    if ($service->fault) {
        $this->__set_error($service->faultcode,
                           $service->faultstring,
                           $response);
        return false;
    }

    if (!isset($response['rval'])) {
        $this->__set_error('AdWords:Client', 'No return value', $response);
        return false;
    }

    return $response['rval'];

}


/*
  Private: get authentication token for Adwords and create it if it does not
  yet exist.
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
  Private: get Soap client for an Adwords service and create it if it does not
  yet exist.
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
  Private: create new authentication token.
*/
function __create_auth_token($email, $password, $account_type, $service) {

    return new AuthToken($email, $password, $account_type, $service);

}


/*
  Private: create new Soap client using the NuSOAP library.
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


/*
  Private: escape XML reserved characters in string.
*/
function __xml($s) {

    return str_replace(array('&',     '<',    '>',    '"'),
                       array('&amp;', '&lt;', '&gt;', '&quot;'),
                       $s);

}


/*
  Private: reset error variables.
*/
function __reset_error() {

    $this->__set_error();

}


/*
  Private: set error variables.
*/
function __set_error($error_code = null, $error_message = null,
                     $error_details = null) {

    $this->error_code = $error_code;
    $this->error_message = $error_message;
    $this->error_details = $error_details;

}


}


?>
