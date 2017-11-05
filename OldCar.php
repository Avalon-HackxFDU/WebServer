<?php

class OldCar {
    function SQLConnect() {
        if ($this -> con) return 1;
        $this -> con = mysqli_connect("localhost", $this -> dbname, $this -> dbpass, $this -> dbname);
        if (!$this -> con) return 0;
        return 1;
    }
        
    function SQLDisconnect() {
        mysqli_close($this -> con);
        $this -> con = null;
        return 1;
    }
    
    function loadJSON() {
        $this -> codeStr = json_decode(file_get_contents('codeStr.json'), true);
    }
    
    function parseURI() {
        $uri = $_SERVER["REQUEST_URI"];
        $loc = strpos($uri, "/?");
        if ($loc === false) $loc = strrpos($uri, "?");
        if ($loc !== false) {
            $uri = substr($uri, 0, $loc);
            $loc = strrpos($uri, "/");
            if ($loc === false) {
                return $uri;
            } else {
                $uri = substr($uri, $loc + 1);
                return $uri;
            }
        } else {
            $this -> dumpInfo(false, "Invalid Request.");
            return null;
        }
    }
    
    function returnAllUsers() {
        $ans = [];
        $this -> SQLConnect();
        $task = "SELECT uid FROM user;";
        $result = mysqli_query($this -> con, $task);
        while($row = mysqli_fetch_array($result)){
		    $ans[] = $row['uid'];
        }
        $this -> SQLDisconnect();
        $this -> dumpInfo(true, $ans);
    }
    
    function returnUserInfo() {
        $uid = -1;
        if (isset($_GET["id"])) $uid = $_GET["id"];
        $this -> SQLConnect();
        $task = "SELECT * FROM user WHERE uid=$uid;";
        $row = mysqli_fetch_array(mysqli_query($this -> con, $task));
        $this -> SQLDisconnect();
        if ($row <= 1) {
            $this -> dumpInfo(false, "Invalid UserID.");
        } else {
            $res = array();
            $res["id"] = $uid;
            $res["mobile"] = $row["phone"];
            $res["user"] = array(
                "name" => $row["name"],
                "licenseinfo" => "您的驾照已经扣去 ".$row["license"]." 分。",
                "carinfo" => "暂时没有您的车辆保养信息哦。",
            );
            $res["tags"] = [];
            $tags = explode(",", $row["tags"]);
            foreach ($tags as $v) {
                $thetag = explode(":", $v);
                $res["tags"][$thetag[0]] = $thetag[1];
            }
            $res["radar"] = [];
            $radar = explode(",", $row["sentiments"]);
            foreach ($radar as $v) {
                $theradar = explode(":", $v);
                $res["radar"][$theradar[0]] = $theradar[1];
            }
            $res["events"] = [];
            $events = explode(",", $row["records"]);
            foreach ($events as $v) {
                $therecord = explode(":", $v);
                $therecordname = $therecord[0];
                if ($this -> codeStr) {
                    if (isset($this -> codeStr[$therecordname])) {
                        $therecordname = $this -> codeStr[$therecordname];
                    }
                }
                $res["events"][$therecord[0]] = array(
                    "describe" => $therecordname,
                    "times" => $therecord[1]
                );
            }
            $res["devices"] = [];
            $devices = explode(",", $row["devices"]);
            foreach ($devices as $v) {
                $res["devices"][] = $v;
            }
            $this -> dumpInfo(true, $res);
        }
        
    }
    
    function returnUser() {
        $uid = -1;
        if (isset($_GET["id"])) $uid = $_GET["id"];
        $this -> SQLConnect();
        $task = "SELECT * FROM user WHERE uid=$uid;";
        $row = mysqli_fetch_array(mysqli_query($this -> con, $task));
        $this -> SQLDisconnect();
        if ($row <= 1) {
            $this -> dumpInfo(false, "Invalid UserID.");
        } else {
            $res = array();
            $res["id"] = $uid;
            $res["mobile"] = $row["phone"];
            $res["cids"] = [];
            $devices = explode(",", $row["cars"]);
            foreach ($devices as $v) {
                if ($v === "") continue;
                $this -> SQLConnect();
                $task2 = "SELECT * FROM car WHERE cid=$v;";
                $row2 = mysqli_fetch_array(mysqli_query($this -> con, $task2));
                $this -> SQLDisconnect();
                if ($row2 > 1) {
                    $res["cids"][] = $v;
                    $res["carinfo"][] = array(
                        "vin" => $row2["vin"],
                        "engine" => $row2["engine"],
                        "license" => $row2["license"]
                    );
                }
            }
            $this -> dumpInfo(true, $res);
        }
        
    }
    
    function setUser() {
        $uid = -1;
        if (isset($_GET["id"])) $uid = $_GET["id"];
        $this -> SQLConnect();
        $task = "SELECT * FROM user WHERE uid=$uid;";
        $row = mysqli_fetch_array(mysqli_query($this -> con, $task));
        $this -> SQLDisconnect();
        if ($row <= 1) {
            $this -> dumpInfo(false, "Invalid UserID.");
        } else {
            $data = null;
            if (isset($_POST["data"])) $data = json_decode($_POST["data"], true);
            if ($data) {
                $targets = array("records", "tags", "sentiments");
                $sets = array();
                foreach ($targets as $t) {
                    if (isset($data[$t])) {
                        $d = "";
                        $tempdata = $data[$t];
                        if ($t != "records") arsort($tempdata);
                        foreach ($tempdata as $k=>$v) {
                            if ($t == "records") {
                                $d .= "$k:" . $v["times"] . ",";
                            } else {
                                $d .= "$k:$v,";
                            }
                        }
                        $sets[$t] = substr($d, 0, -1);
                    }
                }
                
                $setsstr = "";
                foreach ($sets as $k=>$v) {
                    $setsstr .= "$k=\"$v\",";
                }
                
                $setsstr = substr($setsstr, 0, -1);
                if ($setsstr != "") {
                    $this -> SQLConnect();
                    $task = "UPDATE user SET $setsstr WHERE uid=$uid;";
                    mysqli_query($this -> con, $task);
                    $this -> SQLDisconnect();
                    $this -> dumpInfo(true, "Success.");
                } else {
                    $this -> dumpInfo(false, "Invalid Data.");
                }
            } else {
                $this -> dumpInfo(false, "Unknown Data.");
            }
        }
    }
    
    function parseRequest() {
        $requestKey = $this -> parseURI();
        
        if ($requestKey) {
            switch ($requestKey) {
                case 'getAllUsers':
                    $this -> returnAllUsers();
                    break;
                case 'getUser':
                    $this -> returnUser();
                    break;
                case 'setUser':
                    $this -> setUser();
                    break;
                case 'getUserInfo':
                    $this -> returnUserInfo();
                    break;
                default:
                    $this -> dumpInfo(false, "No Such Method!");
                    break;
            }
        }
    }
    
    function dumpInfo($status=true, $data) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST");
        header("content-type: application/json; charset=utf-8");
        
        echo json_encode(array(
            "status" => $status,
            "data"   => $data
        ));
    }
    
    function __construct($dbname, $dbpass) {
        $this -> dbname = $dbname;
        $this -> dbpass = $dbpass;
        $this -> con    = null;
        $this -> loadJSON();
    }
    
    function handle() {
        $this -> parseRequest();
    }
    
}

/*
 * USER:
 * uid
 * name
 * phone
 * license
 * cars
 * devices
 * tags
 * sentiments
 * records
 */

/*
 * CAR:
 * cid
 * license
 * ven
 * engine
 */