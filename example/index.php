<?php

/*
    AdWords API PHP4 Client Library example code
    Example accessing the Google AdWords API v2009 in PHP4.

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

require_once('config.php');

$adwords = new AdWords($email, $password, $sandbox, $client_email, $developer_token, $application_token, $application);

function error($adwords) {
    echo '<p><a href="javascript:toggle(\'error\');">An error occured</a></p>';
    echo '<pre id="error" style="display:none">'.htmlentities(print_r($adwords->get_error(), true)).'</pre>';
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>AdWords API PHP4 Client Library example code</title>
    <script type="text/javascript">
    function toggle(id) {
        var e = document.getElementById(id);
        e.style.display = (e.style.display == 'none') ? '' : 'none';
    }
    </script>
</head>

<body>

<h1>AdWords API PHP4 Client Library example code</h1>

<?php

if (!isset($_GET['action']) && !isset($_POST['action'])) {

    echo '<h2>Campaigns</h2>';

    if ($campaigns = $adwords->get_campaigns()) {

        if (isset($campaigns['entries'])) {

            echo '<ol>';
            for ($i = 0; $i < count($campaigns['entries']); $i++) {
                $c = $campaigns['entries'][$i];
                echo '<li><p>'.$c['name'].'</p>';
                echo '<p><a href="example.php?action=get_ad_groups_by_campaign&campaign='.$c['id'].'">Show ad groups</a></p>';
                echo '</li>';
            }
            echo '</ol';

        } else {

            echo '<p>No campaigns found</p>';

        }

    } else {

        error($adwords);

    }

} else if ($_GET['action'] == 'get_ad_groups_by_campaign' &&
           preg_match('/^[0-9]+$/', $_GET['campaign'])) {

    echo '<h2>Ad groups for campaign '.$_GET['campaign'].'</h2>';

    if ($groups = $adwords->get_ad_groups_by_campaign($_GET['campaign'])) {

        if (isset($groups['entries'])) {

            echo '<ol>';
            for ($i = 0; $i < count($groups['entries']); $i++) {
                $g = $groups['entries'][$i];
                echo '<li><p>'.$g['name'].'</p>';
                echo '<p><a href="example.php?action=get_criteria_by_ad_group&ad_group='.$g['id'].'">Show criteria</a></p>';
                echo '<form method="POST" action="example.php"><input type="hidden" name="action" value="add_keyword">';
                echo '<input type="hidden" name="ad_group" value="'.$g['id'].'">';
                echo '<input type="text" name="text"><input type="submit" value="Add keyword"></form>';
                echo '<form method="POST" action="example.php"><input type="hidden" name="action" value="add_placement">';
                echo '<input type="hidden" name="ad_group" value="'.$g['id'].'">';
                echo '<input type="text" name="url"><input type="submit" value="Add placement"></form>';
                echo '</li>';
            }
            echo '</ol';

        } else {

            echo '<p>No ad groups found for campaign '.$_GET['campaign'].'</p>';

        }

    } else {

        error($adwords);

    }

} else if ($_GET['action'] == 'get_criteria_by_ad_group' &&
           preg_match('/^[0-9]+$/', $_GET['ad_group'])) {

    echo '<h2>Criteria for ad group '.$_GET['ad_group'].'</h2>';

    if ($criteria = $adwords->get_criteria_by_ad_group($_GET['ad_group'])) {

        if (isset($criteria['entries'])) {

            echo '<ol>';
            for ($i = 0; $i < count($criteria['entries']); $i++) {
                $c = $criteria['entries'][$i]['criterion'];
                echo '<li>';
                if ($c['Criterion.Type'] == 'Keyword') {
                    echo '<p>Keyword: '.$c['text'].'</p>';
                } else {
                    echo '<p>Placement: '.$c['url'].'</p>';
                }
                echo '<p>User status: '.$criteria['entries'][$i]['userStatus'].'</p>';
                echo '<p><a href="example.php?action=get_criterion&ad_group='.$_GET['ad_group'].'&criterion='.$c['id'].'">Show criterion</a></p>';
                echo '<form method="POST" action="example.php"><input type="hidden" name="action" value="set_criterion_user_status">';
                echo '<input type="hidden" name="ad_group" value="'.$_GET['ad_group'].'">';
                echo '<input type="hidden" name="criterion" value="'.$c['id'].'">';
                echo '<select name="user_status"><option value="'.AW_USER_STATUS_ACTIVE.'">Active</option>';
                echo '<option value="'.AW_USER_STATUS_DELETED.'">Deleted</option>';
                echo '<option value="'.AW_USER_STATUS_PAUSED.'">Paused</option></select>';
                echo '<input type="submit" value="Update user status"></form>';
                echo '<form method="POST" action="example.php"><input type="hidden" name="action" value="delete_criterion">';
                echo '<input type="hidden" name="ad_group" value="'.$_GET['ad_group'].'">';
                echo '<input type="hidden" name="criterion" value="'.$c['id'].'">';
                echo '<input type="submit" value="Delete criterion"></form>';
                echo '</li>';
            }
            echo '</ol';

        } else {

            echo '<p>No criteria found for ad group '.$_GET['ad_group'].'</p>';

        }

    } else {

        error($adwords);

    }

} else if ($_GET['action'] == 'get_criterion' &&
           preg_match('/^[0-9]+$/', $_GET['ad_group']) &&
           preg_match('/^[0-9]+$/', $_GET['criterion'])) {

    echo '<h2>Criterion '.$_GET['criterion'].' for ad group '.$_GET['ad_group'].'</h2>';

    if ($criterion = $adwords->get_criterion($_GET['ad_group'], $_GET['criterion'])) {

        if ($criterion['criterion']['Criterion.Type'] == 'Keyword') {
            echo '<p>Keyword: '.$criterion['criterion']['text'].'</p>';
        } else {
            echo '<p>Placement: '.$criterion['criterion']['url'].'</p>';
        }
        echo '<p>User status: '.$criterion['userStatus'].'</p>';

    } else {

        if ($adwords->error_occurred()) {
            error($adwords);
        } else {
            echo '<p>Criterion not found.</p>';
        }

    }

} else if ($_POST['action'] == 'add_keyword' &&
           preg_match('/^[0-9]+$/', $_POST['ad_group']) &&
           isset($_POST['text'])) {

    echo '<h2>Adding keyword for ad group '.$_POST['ad_group'].'...</h2>';

    if ($criterion = $adwords->add_keyword($_POST['ad_group'], $_POST['text'])) {

        echo '<p>Added keyword: '.$criterion['criterion']['text'].' (id '.$criterion['criterion']['id'].')</p>';

    } else {

        if ($adwords->error_occurred()) {
            error($adwords);
        } else {
            echo '<p>No keyword added</p>';
        }

    }

} else if ($_POST['action'] == 'add_placement' &&
           preg_match('/^[0-9]+$/', $_POST['ad_group']) &&
           isset($_POST['url'])) {

    echo '<h2>Adding placement for ad group '.$_POST['ad_group'].'...</h2>';

    if ($criterion = $adwords->add_placement($_POST['ad_group'], $_POST['url'])) {

        echo '<p>Added placement: '.$criterion['criterion']['url'].' (id '.$criterion['criterion']['id'].')</p>';

    } else {

        if ($adwords->error_occurred()) {
            error($adwords);
        } else {
            echo '<p>No placement added</p>';
        }

    }

} else if ($_POST['action'] == 'delete_criterion' &&
           preg_match('/^[0-9]+$/', $_POST['ad_group']) &&
           preg_match('/^[0-9]+$/', $_POST['criterion'])) {

    echo '<h2>Deleting criterion '.$_POST['criterion'].'...</h2>';

    if ($criterion = $adwords->delete_criterion($_POST['ad_group'], $_POST['criterion'])) {

        echo '<p>Deleted criterion '.$criterion['criterion']['id'].'</p>';

    } else {

        if ($adwords->error_occurred()) {
            error($adwords);
        } else {
            echo '<p>No criterion deleted</p>';
        }

    }

} else if ($_POST['action'] == 'set_criterion_user_status' &&
           preg_match('/^[0-9]+$/', $_POST['ad_group']) &&
           preg_match('/^[0-9]+$/', $_POST['criterion']) &&
           isset($_POST['user_status'])) {

    echo '<h2>Updating user status for criterion '.$_POST['criterion'].'...</h2>';

    if ($criterion = $adwords->set_criterion_user_status($_POST['ad_group'], $_POST['criterion'], $_POST['user_status'])) {

        echo '<p>Set user status: '.$criterion['userStatus'].' (id '.$criterion['criterion']['id'].')</p>';

    } else {

        if ($adwords->error_occurred()) {
            error($adwords);
        } else {
            echo '<p>No criterion user status updated</p>';
        }

    }

}

?>

<h2>Debugging Information</h2>

<p>Using sandbox: <?php echo ($adwords->using_sandbox()) ? 'yes' : 'no'; ?></p>

<p><a href="javascript:toggle('request');">HTTP request</a></p>

<pre id="request" style="display:none"><?php echo htmlentities($adwords->get_http_request()); ?></pre>

<p><a href="javascript:toggle('response');">HTTP response</a></p>

<pre id="response" style="display:none"><?php echo htmlentities($adwords->get_http_response()); ?></pre>

</body>

</html>
