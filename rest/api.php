<?php

require("../login.php");
require_once("Rest.inc.php");
require_once("../config.php");

class API extends REST {

    public $data = "";
    private $db = NULL;
    private $groups = array();

    public function __construct() {
        parent::__construct(); // Init parent contructor
        $this->dbConnect(); // Initiate Database connection
    }

//Database connection
    private function dbConnect() {
        include("../config.php");

        $this->db = mysql_connect($db_host, $db_user, $db_password);
        if ($this->db)
            mysql_select_db($db_name, $this->db);
    }

//Public method for access api.
//This method dynamically calls the method based on the query string
    public function processApi() {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404);
// If the method does not exist within this class, response would be "Page not found".
    }


    /**
     * The following function will send a GCM notification using curl.
     * 
     * @param $apiKey		[string] The Browser API key string for your GCM account
     * @param $registrationIdsArray [array]  An array of registration ids to send this notification to
     * @param $messageData		[array]	 An named array of data to send as the notification payload
     */
    private function sendNotification($apiKey, $registrationIdsArray, $messageData) {
        $headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . $apiKey);
        $data = array(
            'data' => $messageData,
            'registration_ids' => $registrationIdsArray
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function thisUserGroups() {
        include("../config.php");
        $username = $_SERVER['PHP_AUTH_USER'];
        $query = "SELECT `group` FROM $table_groups WHERE id IN (SELECT groupId FROM $table_user_group WHERE userId = (SELECT id FROM $table_users WHERE username = '$username'))";
        $sql = mysql_query($query, $this->db);
        $result = array();
        while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
            $result[] = $rlt['group'];
        }
        return $result;
    }

    private function thisUserDomain() {
        include("../config.php");
        $username = $_SERVER['PHP_AUTH_USER'];
        $query = "SELECT domain FROM $table_users WHERE username = '$username'";
        $sql = mysql_query($query, $this->db);
        while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
            $result = $rlt['domain'];
        }
        return $result;
    }

    private function setPassword($username, $password) {
        include("../config.php");
        $salt = hash('sha256', uniqid(mt_rand(), true) . 'something random' . strtolower($username));
        $hash = $salt . $password;
        for ($i = 0; $i < 100000; $i++) {
            $hash = hash('sha256', $hash);
        }
        $hash = $salt . $hash;
        $query = "UPDATE $table_users SET hash='$hash' WHERE username = '$username'";
        mysql_query($query, $this->db);
    }

    private function users() {
        include("../config.php");

        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }

        $groups = $this->thisUserGroups();
        $domain = $this->thisUserDomain();
        if (in_array("root", $groups))
            $sql = mysql_query("SELECT * FROM $table_users", $this->db);
        else
            $sql = mysql_query("SELECT * FROM $table_users WHERE domain = $domain", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $rlt['firstName'] = utf8_encode($rlt['firstName']);
                $rlt['lastName'] = utf8_encode($rlt['lastName']);
                $rlt['address'] = utf8_encode($rlt['address']);
                $rlt['city'] = utf8_encode($rlt['city']);
                if ($rlt['available'] == 0)
                    $rlt['available'] = "false";
                else
                    $rlt['available'] = "true";

                $sql2 = mysql_query("SELECT groupId FROM $table_user_group WHERE userId = {$rlt['id']}", $this->db);
                if (mysql_num_rows($sql2) > 0) {
                    $rlt['groups'] = array();
                    while ($grlt = mysql_fetch_array($sql2, MYSQL_ASSOC)) {
                        array_push($rlt['groups'], $grlt['groupId']);
                    }
                }
                $sql3 = mysql_query("SELECT skillId FROM $table_user_skill WHERE userId = {$rlt['id']}", $this->db);
                if (mysql_num_rows($sql3) > 0) {
                    $rlt['skills'] = array();
                    while ($srlt = mysql_fetch_array($sql3, MYSQL_ASSOC)) {
                        array_push($rlt['skills'], $srlt['skillId']);
                    }
                }
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function availableUsers() {
        include("../config.php");

        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $groups = $this->thisUserGroups();
        $domain = $this->thisUserDomain();
        if (in_array("root", $groups))
            $sql = mysql_query("SELECT * FROM $table_users WHERE available=1", $this->db);
        else
            $sql = mysql_query("SELECT * FROM $table_users WHERE domain=$domain AND available=1", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $rlt['firstName'] = utf8_encode($rlt['firstName']);
                $rlt['lastName'] = utf8_encode($rlt['lastName']);
                $rlt['address'] = utf8_encode($rlt['address']);
                $rlt['city'] = utf8_encode($rlt['city']);
                if ($rlt['available'] == 0)
                    $rlt['available'] = "false";
                else
                    $rlt['available'] = "true";

                $sql2 = mysql_query("SELECT groupId FROM $table_user_group WHERE userId = {$rlt['id']}", $this->db);
                if (mysql_num_rows($sql2) > 0) {
                    $rlt['groups'] = array();
                    while ($grlt = mysql_fetch_array($sql2, MYSQL_ASSOC)) {
                        array_push($rlt['groups'], $grlt['groupId']);
                    }
                }
                $sql3 = mysql_query("SELECT skillId FROM $table_user_skill WHERE userId = {$rlt['id']}", $this->db);
                if (mysql_num_rows($sql3) > 0) {
                    $rlt['skills'] = array();
                    while ($srlt = mysql_fetch_array($sql3, MYSQL_ASSOC)) {
                        array_push($rlt['skills'], $srlt['skillId']);
                    }
                }
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204);
    }

    private function userByUsername() {
        include("../config.php");

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $username = $this->_request['username'];

        $sql = mysql_query("SELECT * FROM $table_users WHERE username = '$username'", $this->db);
        if (mysql_num_rows($sql) > 0) {

            $rlt = mysql_fetch_array($sql, MYSQL_ASSOC);
            $rlt['firstName'] = utf8_encode($rlt['firstName']);
            $rlt['lastName'] = utf8_encode($rlt['lastName']);
            $rlt['address'] = utf8_encode($rlt['address']);
            $rlt['city'] = utf8_encode($rlt['city']);
            if ($rlt['available'] == 0)
                $rlt['available'] = "false";
            else
                $rlt['available'] = "true";

            //TODO
            $rlt['scenarios'] = "false";

            $sql2 = mysql_query("SELECT groupId FROM $table_user_group WHERE userId = {$rlt['id']}", $this->db);
            if (mysql_num_rows($sql2) > 0) {
                $rlt['groups'] = array();
                while ($grlt = mysql_fetch_array($sql2, MYSQL_ASSOC)) {
                    array_push($rlt['groups'], $grlt['groupId']);
                }
            }
            $sql3 = mysql_query("SELECT skillId FROM $table_user_skill WHERE userId = {$rlt['id']}", $this->db);
            if (mysql_num_rows($sql3) > 0) {
                $rlt['skills'] = array();
                while ($srlt = mysql_fetch_array($sql3, MYSQL_ASSOC)) {
                    array_push($rlt['skills'], $srlt['skillId']);
                }
            }

            $this->response($this->json($rlt), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function userById() {
        include("../config.php");

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $userId = $this->_request['userId'];

        $sql = mysql_query("SELECT * FROM $table_users WHERE id = $userId", $this->db);
        if (mysql_num_rows($sql) > 0) {

            $rlt = mysql_fetch_array($sql, MYSQL_ASSOC);
            $rlt['firstName'] = utf8_encode($rlt['firstName']);
            $rlt['lastName'] = utf8_encode($rlt['lastName']);
            $rlt['address'] = utf8_encode($rlt['address']);
            $rlt['city'] = utf8_encode($rlt['city']);
            if ($rlt['available'] == 0)
                $rlt['available'] = "false";
            else
                $rlt['available'] = "true";

            $sql2 = mysql_query("SELECT groupId FROM $table_user_group WHERE userId = {$rlt['id']}", $this->db);
            if (mysql_num_rows($sql2) > 0) {
                $rlt['groups'] = array();
                while ($grlt = mysql_fetch_array($sql2, MYSQL_ASSOC)) {
                    array_push($rlt['groups'], $grlt['groupId']);
                }
            }
            $sql3 = mysql_query("SELECT skillId FROM $table_user_skill WHERE userId = {$rlt['id']}", $this->db);
            if (mysql_num_rows($sql3) > 0) {
                $rlt['skills'] = array();
                while ($srlt = mysql_fetch_array($sql3, MYSQL_ASSOC)) {
                    array_push($rlt['skills'], $srlt['skillId']);
                }
            }

            $this->response($this->json($rlt), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function userId() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $username = $this->_request['username'];
        if (!empty($username)) {
            $sql = mysql_query("SELECT id FROM $table_users WHERE username = '$username'", $this->db);
            if (mysql_num_rows($sql) > 0) {
                $result = array();
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $result[] = $rlt;
                }
                $this->response($this->json($result), 200);
            } else {
                $this->response('', 204); // If no records "No Content" status
            }
        }
    }

    private function userGroupIds() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userId = (int) $this->_request['userId'];
        if ($userId > 0) {
            $sql = mysql_query("SELECT groupId FROM $table_user_group WHERE userId = $userId", $this->db);
            if (mysql_num_rows($sql) > 0) {
                $result = array();
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $result[] = $rlt['groupId'];
                }

                $this->response($this->json($result), 200);
            } else {
                $this->response('', 204); // If no records "No Content" status
            }
        }
    }

    private function userGroups() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userId = (int) $this->_request['userId'];
        if ($userId > 0) {
            $sql = mysql_query("SELECT `group` FROM $table_groups WHERE id IN (SELECT groupId FROM $table_user_group WHERE userId = $userId)", $this->db);
            if (mysql_num_rows($sql) > 0) {
                $result = array();
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $result[] = $rlt['group'];
                }

                $this->response($this->json($result), 200);
            } else {
                $this->response('', 204);
            }
        }
    }

    private function currentUserDomain() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $rlt = (int) $this->thisUserDomain();
        $result['domain'] = $rlt;
        //$userId = (int) $this->_request['userId'];
        if ($result > 0) {
            $this->response($this->json($result), 200);
        } else {
            $this->response('', 204);
        }
    }

    private function createUpdateUser() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userId = (int) $this->_request['id'];
        $username = $this->_request['username'];
        $domain = $this->_request['domain'];
        $password = $this->_request['password'];
        $firstName = $this->_request['firstName'];
        $lastName = $this->_request['lastName'];
        $available = (bool) $this->_request['available'];
        $address = $this->_request['address'];
        $postalCode = $this->_request['postalCode'];
        $city = $this->_request['city'];
        $country = (int) $this->_request['country'];
        $cellPhone = $this->_request['cellPhone'];
        $phone = $this->_request['phone'];
        $taxCode = $this->_request['taxCode'];

        $groups = $this->thisUserGroups();
        $hash = "No Hash";
        if ((in_array("admin", $groups) || in_array("root", $groups)) && !empty($username)) {
            if ($userId == 0) {
                $query = "INSERT INTO $table_users (username, domain, firstName, lastName, available, address, postalCode, city, country, cellPhone, phone, taxCode) 
                    VALUES ('$username', $domain, '$firstName', '$lastName', '$available', '$address', '$postalCode', '$city', $country, '$cellPhone', '$phone', '$taxCode')";
                mysql_query($query, $this->db);
            } else {
                $query = "UPDATE $table_users SET username='$username', domain=$domain, firstName='$firstName', lastName='$lastName', available='$available', address='$address', 
                    postalCode='$postalCode', city='$city', country=$country, cellPhone='$cellPhone', phone='$cellPhone', phone='$phone', taxCode='$taxCode' WHERE id = $userId";
                mysql_query($query, $this->db);
            }
            if (!empty($password)) {
                $this->setPassword($username, $password);
            }
        } else {
            $this->response('', 403);
        }
    }

    private function deleteUser() {
        include("../config.php");

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $id = (int) $this->_request['id'];
        if ($id > 0) {
            $query = "DELETE FROM $table_users WHERE id = $id";
            echo $query;
            mysql_query($query, $this->db);
            mysql_query("DELETE FROM $table_user_groups WHERE userId = $id", $this->db);
            mysql_query("DELETE FROM $table_user_skill WHERE userId = $id", $this->db);
            $success = array('status' => "Success", "msg" => "Successfully deleted.");
            $this->response($this->json($success), 200);
        } else {
            $this->response('', 204);
        }
    }

    private function groups() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sql = mysql_query("SELECT * FROM $table_groups", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function userGroup() {
        include("../config.php");
// Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sql = mysql_query("SELECT * FROM $table_user_group", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }

// If success everything is good send header as "OK" and return list of users in JSON format
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function domains() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sql = mysql_query("SELECT * FROM $table_domains", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204);
    }

    private function setUserGroups() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $username = $this->_request['username'];
        $jsonGroups = $this->_request['groups'];
        $groups = array();
        $groupIds = array();
        $groups = json_decode($jsonGroups);
        $query = "SELECT id FROM $table_users WHERE username = '$username'";
        $sql = mysql_query($query, $this->db);
        $rlt = mysql_fetch_array($sql, MYSQL_ASSOC);
        $userId = $rlt['id'];
        foreach ($groups as $group) {
            $query = "SELECT * FROM $table_groups WHERE `group` = '$group'";
            //echo $query;
            $sql = mysql_query($query, $this->db);
            $rlt = mysql_fetch_array($sql, MYSQL_ASSOC);
            $groupId = $rlt['id'];
            $groupIds[] = $groupId;
            $query = "SELECT * FROM $table_user_group WHERE groupId = $groupId AND userId = $userId";
            //echo $query;
            $sql = mysql_query($query, $this->db);
            if (mysql_num_rows($sql) == 0) {
                mysql_query("INSERT INTO $table_user_group (userId, groupId) VALUES ($userId, $groupId)", $this->db);
            }
        }
        $sql = mysql_query("SELECT * FROM $table_user_group WHERE userId = $userId", $this->db);
        while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
            if (!in_array($rlt['groupId'], $groupIds)) {
                mysql_query("DELETE FROM $table_user_group WHERE userId = $userId AND groupId = {$rlt['groupId']}");
            }
        }
        $this->response('', 200);
    }

    private function setUserSkills() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $username = $this->_request['username'];
        $jsonSkills = $this->_request['skills'];
        $skillIds = array();
        $skillIds = json_decode($jsonSkills);
        $query = "SELECT id FROM $table_users WHERE username = '$username'";
        $sql = mysql_query($query, $this->db);
        $rlt = mysql_fetch_array($sql, MYSQL_ASSOC);
        $userId = $rlt['id'];
        foreach ($skillIds as $skillId) {
            $sql = mysql_query("SELECT * FROM $table_user_skill WHERE userId = $userId AND skillId = $skillId", $this->db);
            if (mysql_num_rows($sql) == 0) {
                mysql_query("INSERT INTO $table_user_skill (userId, skillId) VALUES ($userId, $skillId)", $this->db);
            }
        }
        $sql = mysql_query("SELECT * FROM $table_user_skill WHERE userId = $userId", $this->db);
        while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
            if (!in_array($rlt['skillId'], $skillIds)) {
                mysql_query("DELETE FROM $table_user_skill WHERE userId = $userId AND skillId = {$rlt['skillId']}");
            }
        }
        $this->response('', 200);
    }

    private function createUpdateDomain() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $domainId = (int) $this->_request['id'];
        $domainName = $this->_request['name'];

        $groups = $this->thisUserGroups();
        if (in_array("admin", $groups) || in_array("root", $groups)) {
            if ($domainId == 0 && !empty($domainName)) {
                mysql_query("INSERT INTO $table_domains (name) VALUES ('$domainName')", $this->db);
            } else {
                mysql_query("UPDATE $table_domains SET name='$domainName' WHERE id = $domainId", $this->db);
            }
        } else {
            $this->response('', 403);
        }
    }

    private function countryCodes() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $sql = mysql_query("SELECT * FROM $table_country_codes", $this->db);
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $rlt['name'] = utf8_encode($rlt['name']);
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204);
    }

    private function available() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $username = $this->_request['username'];
        $available = ($this->_request['available'] == "true" ? 1 : 0);

        print_r($this->_request);

        if (!empty($username)) {
            mysql_query("UPDATE $table_users SET available=$available WHERE username = '$username'", $this->db);
        }
    }

    private function location() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $timestamp = time();
        $username = $this->_request['username'];
        $lon = (double) $this->_request['lon'];
        $lat = (double) $this->_request['lat'];
        if (!empty($username) && !empty($lon) && !empty($lat)) {
            mysql_query("UPDATE $table_users SET lon=$lon, lat=$lat, gpsTimestamp=$timestamp WHERE username = '$username'", $this->db);
        }
    }

    private function gcmRegId() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $username = $this->_request['username'];
        $gcmRegId = $this->_request['gcmRegId'];
        if (!empty($username) && !empty($gcmRegId)) {
            mysql_query("UPDATE $table_users SET gcmRegId='$gcmRegId' WHERE username = '$username'", $this->db);
        }
    }

    private function skills() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $groups = $this->thisUserGroups();
        $domain = $this->thisUserDomain();
        //if (in_array("root", $groups)) {
        //    $sql = mysql_query("SELECT * FROM $table_skills", $this->db);
        //} else {
        $sql = mysql_query("SELECT * FROM $table_skills WHERE domain = $domain", $this->db);
        //}
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function skillCategories() {
        include("../config.php");
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }
        $groups = $this->thisUserGroups();
        $domain = $this->thisUserDomain();
        //if (in_array("root", $groups)) {
        //    $sql = mysql_query("SELECT * FROM $table_skill_categories", $this->db);
        //} else {
        $sql = mysql_query("SELECT * FROM $table_skill_categories WHERE domain = $domain", $this->db);
        //}
        if (mysql_num_rows($sql) > 0) {
            $result = array();
            while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                $result[] = $rlt;
            }
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function createUpdateSkill() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $id = (int) $this->_request['id'];
        $category = (int) $this->_request['category'];
        $domain = (int) $this->_request['domain'];
        $code = $this->_request['code'];
        $description = $this->_request['description'];

        $groups = $this->thisUserGroups();
        if (in_array("admin", $groups) || in_array("root", $groups)) {
            if ($id == 0 && !empty($code) && !empty($category) && !empty($domain)) {
                mysql_query("INSERT INTO $table_skills (category, domain, code, description) VALUES ($category, $domain, '$code', '$description')", $this->db);
            } else {
                mysql_query("UPDATE $table_skills SET category=$category, domain=$domain, code='$code', description='$description' WHERE id = $id", $this->db);
            }
        } else {
            $this->response('', 403);
        }
    }

    private function deleteSkillCategory() {
        include("../config.php");

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $id = (int) $this->_request['id'];
        if ($id > 0) {
            $query = "DELETE FROM $table_skill_categories WHERE id = $id";
            //echo $query;
            mysql_query($query);
            $query = "SELECT id FROM $table_skills WHERE category = $id";
            $sql = mysql_query($query);
            if (mysql_num_rows($sql) > 0) {
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $query = "DELETE FROM $table_user_skill WHERE skillId = {$rlt['id']}";
                    mysql_query($query);
                }
            }
            $query = "DELETE FROM $table_skills WHERE category = $id";
            mysql_query($query);
            $success = array('status' => "Success", "msg" => "Successfully deleted.");
            $this->response($this->json($success), 200);
        } else {
            $this->response('', 204);
        }
    }

    private function deleteSkill() {
        include("../config.php");

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $id = (int) $this->_request['id'];
        if ($id > 0) {
            $query = "DELETE FROM $table_skills WHERE id = $id";
            echo $query;
            mysql_query($query);
            $query = "DELETE FROM $table_user_skill WHERE skillId = $id";
            echo $query;
            mysql_query($query);
            $success = array('status' => "Success", "msg" => "Successfully deleted");
            $this->response($this->json($success), 200);
        } else {
            $this->response('', 204);
        }
    }

    private function createUpdateSkillCategory() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        //print_r($_POST);
        $id = (int) $this->_request['id'];
        $domain = (int) $this->_request['domain'];
        $code = $this->_request['code'];
        $description = $this->_request['description'];

        $groups = $this->thisUserGroups();
        //print_r($groups);
        if (in_array("admin", $groups) || in_array("root", $groups)) {
            if ($id == 0 && !empty($code) && !empty($description) && $domain > 0) {
                $sql = "INSERT INTO $table_skill_categories (domain, code, description) VALUES ($domain, '$code', '$description')";
                //echo $sql;
                mysql_query($sql, $this->db);
            } else {
                mysql_query("UPDATE $table_skill_categories SET domain=$domain, code='$code', description='$description' WHERE id = $id", $this->db);
            }
        } else {
            $this->response('', 403);
        }
    }

    private function json($data) {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

    private function notify() {
        include("../config.php");
        $message = $this->_request['message'];
        //$users = array();
        $jsonUsers = $this->_request['users'];
        $users = json_decode($jsonUsers);
        //$message = "the test message";

        print_r($_POST);
        print_r($this->_request);
        print_r($jsonUsers);
        //$username = "helfrich@xs4all.nl";

        $quotedUsers = array();
        foreach ($users as $user) {
            $quotedUsers[] = "'" . $user . "'";
        }

        $query = "SELECT gcmRegId FROM $table_users WHERE username IN (" . implode(',', $quotedUsers) . ")";

        echo $query;

        $sql = mysql_query($query, $this->db);
        while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
            $registrationIds[] = $rlt['gcmRegId'];
        }

        $response = $this->sendNotification(
                $gcmApiKey, $registrationIds, array('message' => $message, 'tickerText' => $tickerText, 'contentTitle' => $contentTitle, "contentText" => $contentText));

        print_r($response);
    }

    private function message() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        // print_r($_POST);
        // print_r($this->_request);

        $jsonUserIds = $this->_request['userIds'];
        $userIds = json_decode($jsonUserIds);
        $refId = (int) $this->_request['refId'];
        $senderId = (int) $this->_request['senderId'];
        $domain = (int) $this->_request['domain'];
        $to = $this->_request['to'];
        $from = $this->_request['from'];
        $subject = $this->_request['subject'];
        $body = $this->_request['body'];
        $timestamp = time();
        mysql_query("INSERT INTO $table_messages (refId, domain, senderId, `to`, `from`, subject, body, timestamp) VALUES ($refId, $domain, $senderId, '$to', '$from', '$subject', '$body', $timestamp)", $this->db);
        $messageId = mysql_insert_id();
        foreach ($userIds as $userId) {
            mysql_query("INSERT INTO $table_message_recipient (messageId, userId) VALUES ($messageId, $userId)", $this->db);
        }
    }

    private function messagesIn() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userId = (int) $this->_request['userId'];
        if ($userId > 0) {
            $sql = mysql_query("SELECT * FROM $table_messages WHERE id IN (SELECT messageId from $table_message_recipient WHERE userId = $userId)", $this->db);
            if (mysql_num_rows($sql) > 0) {
                $result = array();
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $result[] = $rlt;
                }
                $this->response($this->json($result), 200);
            } else {
                $this->response('', 204);
            }
        }
    }

    private function messagesSent() {
        include("../config.php");
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $userId = (int) $this->_request['userId'];
        if ($userId > 0) {
            $sql = mysql_query("SELECT * FROM $table_messages WHERE senderId = $userId", $this->db);
            if (mysql_num_rows($sql) > 0) {
                $result = array();
                while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                    $result[] = $rlt;
                }
                $this->response($this->json($result), 200);
            } else {
                $this->response('', 204);
            }
        }
    }

}

// Initiate Library
$api = new API;
$api->processApi();
?>
