<?php
/**
 * User: axc
 * Date: 24/09/18
 * Time: 14.04
 */
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload
namespace axc\ZMManager;


use Unirest\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;



class ZoneMinderManager
{

    private $host = "http://localhost/zm/"; //WEBSERVER URL

    private $cookies = "";

    public $authHash = "";

    private $secretKey = ""; // SECRET KEY OF ZM SERVER

    /**
     * Valid for the API version below of 1.32.0
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username, $password){
        $cookie ="";
        $cookies = "";

        

        $session = new Session();

        $query = array('username'=>$username, 'password'=>$password, 'action'=>'login', 'view'=>'console');

        $req = new Request();

        $headers = array('Accept' => 'text/html');

        $response = $req->post($this->host."index.php",$headers,$query);



        $headers = array('Accept' => 'application/json');

        $this->cookies = $response->headers['Set-Cookie'];

        $session->set('cookies',$response->headers['Set-Cookie']);
        //$this->get('session')->set('cookies',$response->headers['Set-Cookie']);
        foreach ($response->headers['Set-Cookie'] as $cook){

            $req->cookie($cook);

        }

        $res_v = $req->get($this->host."api/host/getVersion.json", $headers);

        $json_resp = json_decode($res_v->raw_body);

        
        $session->set('username',$username);
        $session->set('password',$password);


        if(isset($json_resp->success)){
            return false;
        }
        $this->authHash = $this->getAuthHash($password);

        return true;


    }

    /**
     * Valid for the API version below 1.32.0
     * @return bool
     */
    public function logout(){

        $query = array('action'=>'logout', 'view'=>'console');
        $req = new Request();
        $headers = array('Accept' => 'text/html');
        $response = $req->post($this->host.'index.php',$headers, $query);

        $res_v = $req->get($this->host."api/host/getVersion.json", $headers);

        $json_resp = json_decode($res_v->raw_body);

        if(isset($json_resp->success)){
            return true;
        }else{
            return false;
        }


    }

    private function sqlPassword($input) {
            $pass = strtoupper(
                sha1(
                    sha1($input, true)
                )
            );
            $pass = '*' . $pass;
            return $pass;
        }


    public function getAuthHash($password){
        $time = localtime();

        $pass = $this->sqlPassword($password);
        echo hash('md5','admin');
        $authKey = $this->secretKey . 'admin' . $pass . $time[2] . $time[3] . $time[4] . $time[5];

        $authHash = md5($authKey);
        $this->authHash = $authHash;
        return $authHash;
}


    public function getCookies($res){
        foreach ($this->cookies as $cook){
            $arr_cookie = explode(";",$cook);
            foreach ($arr_cookie as $item){
                $part = explode("=",$item);
                if(count($part)>1)
                    $res->headers->setCookie(new Cookie(trim($part[0]),trim($part[1])));
            }


        }
        return $res;
    }


    public function getListMonitors(){

        //Get Monitor List
        //curl http://server/zm/api/monitors.json
        $session = new Session();

        $username = $session->get('username');
        $password = $session->get('password');

        $headers = array('Accept' => 'application/json');

        $req = new Request();

        $cookies = $session->get('cookies');

        foreach ($cookies as $cook){

            $req->cookie($cook);

        }

        $res_v = $req->get($this->host."api/monitors.json", $headers);

        $json_resp = json_decode($res_v->raw_body);
        $arr = array();
        foreach ($json_resp->monitors as $monitor) {
            array_push($arr, $monitor->Monitor);

        }

        return $arr;

    }


    public function getEventMonitor($id){
        //curl -XGET http://server/zm/api/events/index/MonitorId:5.json
        $session = new Session();

        $headers = array('Accept' => 'application/json');

        $req = new Request();

        $cookies = $session->get('cookies');

        foreach ($cookies as $cook){

            $req->cookie($cook);

        }

        $res_v = $req->get($this->host."api/events/index/monitorId:".$id.".json", $headers);
        return $res_v;

    }


}
