<?php
/**
 * Created by PhpStorm.
 * User: albert
 * Date: 05/07/16
 * Time: 13:57
 */

namespace Drupal\transferhub_devicehub;

use \Drupal\Core\Entity\Exception;

class transferhub_DeviceHubRestClient
{
    var $base_url;
    var $email;
    var $password;
    var $client;
    var $db;
    var $token;

    function __construct(){

        $config = \Drupal::service('config.factory')->getEditable('transferhub_devicehub.settings');
        
        //$this->base_url = "http://jsonplaceholder.typicode.com"; //todo llegir de conf
        $this->base_url = "http://devicehub.ereuse.net"; //todo llegir de conf
        $this->email = "transferhub@ereuse.org"; //todo llegir de conf
        $this->password = "random"; //todo llegir de conf

        $this->token = "reutilitza"; //todo llegir de la configuració
        $this->db = "db1"; //todo llegir de conf

        $this->client = new \RestClient([
            'base_url' => $this->base_url, //todo llegir de configuració
            //'format' => "json",
            // https://dev.twitter.com/docs/auth/application-only-auth
            //'headers' => ['Authorization' => 'Basic '.$this->token],
        ]);
    }

    function login()
    {
        try {
            $api = new \RestClient([
                'base_url' => $this->base_url, //todo llegir de configuració
                //'format' => "json",
            ]);

            $json["email"] = $this->email;
            $json["password"] = $this->password;

            $result = $api->post("login",json_encode($json),["Content-Type"  => 'application/json']);

            if($result->info->http_code == 200 || $result->info->http_code == 201)
            {
                $values = (array) $result->decode_response();
                $this->token = $values["token"];
                $this->db = $values["defaultDatabase"];
                //TODO save token to module settings
                //todo save $DB
                return $this->token;
            }
            else {

                $values = (array) $result->decode_response();
                $error = (array) $values["_error"];

                \Drupal::logger('transferhub_devicehub')->error("REST CLIENT: login error: ".$error["@type"]." | HTTP status ".$result->info->http_code." | response: ".json_encode($result->decode_response()));
                return false;
            }
        }
        catch (\Exception $e)
        {
            \Drupal::logger('transferhub_devicehub')->error("REST CLIENT: exception on login: ". $e->getMessage());
            return false;
        }
    }

    function call($url, $method, $content = NULL, $params = NULL, $headers = NULL)
    {
        try {
            $attempts = 0;
            do {
                //token
                $headers["Authorization"] = "Basic ".$this->token;

                switch ($method) {
                    case "GET": {
                        $headers["Accept"] = "application/json";
                        $result = $this->client->get($url, $params, $headers);
                        break;
                    }
                    case "POST": {
                        $headers["Content-Type"] = 'application/json';
                        $result = $this->client->post($url, json_encode($content,JSON_UNESCAPED_SLASHES), $headers);
                        break;
                    }
                }
                $attempts++;

                if ($result->info->http_code != 200 && $result->info->http_code != 201)
                    $this->login();
            }
            while ($result->info->http_code != 200 && $result->info->http_code != 201 && $attempts <= 3);

            if ($method == "GET")
                $data = json_encode($params,JSON_UNESCAPED_SLASHES);
            else
                $data = json_encode($content,JSON_UNESCAPED_SLASHES);

            if ($result->info->http_code != 200 && $result->info->http_code != 201)
            {
                //ERROR
                //todo better error handling
                /*
                    400 Bad Request –
                    422 Unprocessable Entity – Document fails validation.
                    403 Forbidden –
                    404 Not Found –
                    405 Method Not Allowed –
                    406 Not Acceptable –
                    415 Unsupported Media Type –
                    500 Internal Server Error – Any non-documented error. Please, report if you get this code.
                    200 OK - (GET)
                    201 Created – (POST)
                 */
                \Drupal::logger("transferhub_devicehub")->error("REST CLIENT: ERROR in method call (".$url.") (".$method.") | HTTP status ".$result->info->http_code." | send: ".$data." | response: ".json_encode($result->decode_response()));
                return false;
            }
            else {
                //SUCCESSFUL CALL
                \Drupal::logger("transferhub_devicehub")->info("REST CLIENT: SUCCESSFUL method call (".$url.") (".$method.") | HTTP status ".$result->info->http_code." | send: ".$data." | response: ".json_encode($result->decode_response()));
                return (array) $result->decode_response();
            }
        }
        catch (\Exception $e)
        {
            \Drupal::logger('transferhub_devicehub')->error("REST CLIENT: exception on call ". $url ." (".$method."): ". $e->getMessage());
            return false;
        }
    }

    function createUser($url,$email)
    {
        $content["@type"] = "Account";

        $content["url"] = $url;
        $content["email"] = $email;
        $content["active"] = true;
        $content["organization"] = "ong1";
        $content["isOrganization"] = false;
        $content["databases"] = [$this->db];

        return $this->call("accounts","POST", $content);
    }

    function createProject(
       //basic info
       $title, $desc, $shortDesc, $url, $tags, $deadline, $author_url, $image,
       //social
       $link_web, $link_fb, $link_twitter, $votes,
       //required equipment
       $count_desktop, $count_desktop_peripherals, $count_laptop, $count_phone, $count_tablet, $count_monitor,
       //address
       $addr_country, $addr_locality, $addr_region, $addr_zip, $addr_street
    )
    {
        $content["@type"] = "Project";

        $content["label"] = $title;
        $content["description"] = substr($desc, 0, 499);
        $content["shortDescription"] = substr($shortDesc, 0, 149);
        $content["@type"] = "Project";
        $content["url"] = $url;
        $content["tags"] = $tags;
        if (isset($deadline) && !empty($deadline))
            $content["deadline"] = $deadline;
        $content["votes"] = (int)$votes;
        $content["byUser"] = $author_url;
        $content["image"] = $image;

        //links
        if (isset($link_web) && !empty($link_web))   $content["links"]["website"] = $link_web;
        if (isset($link_fb) && !empty($link_fb))   $content["links"]["facebook"] = $link_fb;
        if (isset($link_twitter) && !empty($link_twitter))   $content["links"]["twitter"] = $link_twitter;

        //numbers of required devices
        $content["requiredDevices"] = array(
            "Desktop" => (int)$count_desktop,
            "DesktopWithPeripherals" => (int)$count_desktop_peripherals,
            "Laptop" => (int)$count_laptop,
            "MobilePhone" => (int)$count_phone,
            "TabletComputer" => (int)$count_tablet,
            "ComputerMonitor" => (int)$count_monitor
        );

        //address
        if (isset($addr_country) && !empty($addr_country))  $content["address"]["addressCountry"] = $addr_country;
        if (isset($addr_locality) && !empty($addr_locality))  $content["address"]["addressLocality"] = $addr_locality;
        if (isset($addr_region) && !empty($addr_region))  $content["address"]["addressRegion"] = $addr_region;
        if (isset($addr_zip) && !empty($addr_zip))  $content["address"]["postalCode"] = $addr_zip;
        if (isset($addr_street) && !empty($addr_street))  $content["address"]["streetAddress"] = $addr_street;

        drupal_set_message(json_encode($content,JSON_UNESCAPED_SLASHES)); //todo treure
        return $this->call($this->db."/projects","POST", $content); 
    }
}