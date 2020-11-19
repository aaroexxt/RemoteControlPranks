<?php
    $ID = $_REQUEST['ID'];
    $IP = $_REQUEST['IP'];
    $success = $_REQUEST['success'];
    $insertDeviceAt = 0;
    date_default_timezone_set("America/Los_Angeles");
    function warn($message) {
        global $IP;
        global $ID;
        $root = realpath($_SERVER["DOCUMENT_ROOT"]);
        if (file_exists($root.'/remote/___warnings.txt')) {
            $warn = fopen($root.'/remote/___warnings.txt', r);
            $warndat = fread($warn, filesize($root.'/remote/___warnings.txt'));
            fclose($warn);
            $date = (date ("F j, Y"));
            $time = (date ("h:i:s"));
            $warn = fopen($root.'/remote/___warnings.txt', w);
            fwrite($warn, $warndat."\n\nRemoteControl Warning:\nDate: ".$date."\nTime: ".$time."\nIP: ".$IP."\nID: ".$ID."\nWarning: ".$message);
            fclose($warn);
        } else {
            $f = fopen($root.'/remote/___warnings.txt', w);
            fwrite($f, "RemoteControl Warning Log");
            fclose($f);
            warn($message);
        }
    }
    function newdevice($message) {
        global $IP;
        global $ID;
        $root = realpath($_SERVER["DOCUMENT_ROOT"]);
        if (file_exists($root.'/remote/___newdevices.txt')) {
            $warn = fopen($root.'/remote/___newdevices.txt', r);
            $warndat = fread($warn, filesize($root.'/remote/___newdevices.txt'));
            fclose($warn);
            $date = (date ("F j, Y"));
            $time = (date ("h:i:s"));
            $warn = fopen($root.'/remote/___newdevices.txt', w);
            fwrite($warn, $warndat."\n\nNew Device:\nDate: ".$date."\nTime: ".$time."\nIP: ".$IP."\nID: ".$ID."\nInfo: ".$message);
            fclose($warn);
        } else {
            warn("New devices file created; check file permissions");
            $f = fopen($root.'/remote/___newdevices.txt', w);
            fwrite($f, "RemoteControl Warning Log");
            fclose($f);
            newdevice($message);
        }
    }
    function updateDeviceValue($deviceArr, $fileArr, $deviceNum, $param, $value) {
        $deviceId = 0;
        $deviceIp = 0;
        foreach($deviceArr as $ind=>$result) {
            if ($ind == $deviceNum) {
                foreach($result as $tag=>$val) {
                    if ($tag == "IP") {
                        $deviceIp = $val;
                    } else if ($tag == "ID") {
                        $deviceId = $val;
                    }
                    if ($tag == $param) {
                        $deviceArr[$deviceNum][$tag] = $value;
                    }
                }
            }
        }
        $found = false;
        $foundDevice = false;
        $ipMatch = false;
        $idMatch = false;
        $targetLn = null;
        for($i = 0; $i < count($fileArr); $i++) { //find device section and output to array
            if (trim($fileArr[$i]) == "</remote>" || trim($fileArr[$i]) == "&lt;/remote&gt;") {
                $found = false;
            }
            if ($found == true) {
                //redef params after end
                if (trim($fileArr[$i]) == "</device>" || trim($fileArr[$i]) == "&lt;/device&gt;") {
                    $foundDevice = false;
                    if ($ipMatch == true && $idMatch == true) {
                        break;
                    } else {
                        $ipMatch = false;
                        $idMatch = false;
                    }
                }
                if ($foundDevice == true) {
                    $line = trim($fileArr[$i]);
                    $posColon = strpos($line, ":");
                    $tag = substr($line, 0, $posColon);
                    $body = trim(substr($line, $posColon+1));
                    if ($tag == "ID" && $body == $deviceId) {
                        $idMatch = true;
                    }
                    if ($tag == "IP" && $body == $deviceIp) {
                        $ipMatch = true;
                    }
                    if ($tag == $param) {
                        $targetLn = $i;
                    }
                }
                if (trim($fileArr[$i]) == "<device>"  || trim($fileArr[$i]) == "&lt;device&gt;") {
                    $foundDevice = true;
                }
            }
            if (trim($fileArr[$i]) == "<remote>" || trim($fileArr[$i]) == "&lt;remote&gt;") {
                $found = true;
                $foundOnce = true;
            }
        }
        if ($targetLn == null) {
            return null;
        } else {
            /*var_dump($fileArr);
            echo("\nGolden line: ".$targetLn."\n");*/
            $fileArr[$targetLn] = "        ".$param.": ".$value."\n";
            writeChanges($fileArr);
            //var_dump($fileArr);
            return $targetLn;
        }
    }
    function getDeviceValue($deviceArr, $deviceNum, $param) {
       foreach($deviceArr as $ind=>$result) {
            if ($ind == $deviceNum) {
                foreach($result as $tag=>$val) {
                    if ($tag == $param) {
                        return $val;
                    }
                }
            }
        }
        return null;
    }
    function addDevice($fileArr, $internalIP, $internalID) {
        global $insertDeviceAt;
        array_splice($fileArr, $insertDeviceAt, 0, "    <device>\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        Name: Anonymous\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        Command: \n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        Response: \n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        UpdateInterval: 60000\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        IP: ".$internalIP."\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        ID: ".$internalID."\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        Added: ".date("F jS, Y", strtotime("now"))." at ".date("h:i:sa", strtotime("now"))."\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "        LastAccessed: ".date("F jS, Y", strtotime("now"))." at ".date("h:i:sa", strtotime("now"))."\n");
        $insertDeviceAt++;
        array_splice($fileArr, $insertDeviceAt, 0, "    </device>\n");
        $insertDeviceAt++;
        writeChanges($fileArr);
    }
    function writeChanges($fileArr) {
        $writestring = "";
        foreach($fileArr as $ln) {
            $writestring = $writestring.$ln;
        }
        /*echo "finalstr ";
        print_r($writestring);*/
        $root = realpath($_SERVER["DOCUMENT_ROOT"]);
        if (file_exists($root.'/remote/___remote.txt')) { //fixed path so no filesystem modification :)
            $writefile = fopen($root.'/remote/___remote.txt', w);
            fwrite($writefile, $writestring);
            fclose($writefile);
        }
    }
    if ($ID == "" || $IP == "" || $success == "") {
        echo "ID_IP_SUCCESS_BLANK";
    } else {
        //echo "SUCCESS";
        $root = realpath($_SERVER["DOCUMENT_ROOT"]);
        if (file_exists($root.'/remote/___remote.txt')) 
        {
            $fil = fopen($root.'/remote/___remote.txt', r);

            $file = array(); //setup
            $untrim_file = array();
            $conf = array();
            $devices = array();

            while (!feof($fil)) { //read file to array
                //$line = nl2br(htmlspecialchars(fgets($fil)));
                array_push($untrim_file,fgets($fil));
                array_push($file,trim($untrim_file[count($untrim_file)-1]));
            }

            $found = false;
            $foundOnce = false;
            for($i = 0; $i < count($file); $i++) { //find configuration section and output to array
                if ($file[$i] == "</conf>" || $file[$i] == "&lt;/conf&gt;") {
                    $found = false;
                }
                if ($found == true) {
                    array_push($conf,trim($file[$i]));
                }
                if ($file[$i] == "<conf>" || $file[$i] == "&lt;conf&gt;") {
                    $found = true;
                    $foundOnce = true;
                }
                //echo trim($file[$i]).$file[$i];
            }
            if ($foundOnce == false) {
                echo "ERROR_NO_CONFIG";
            }

            $found = false;
            $foundDevice = false;
            $foundOnce = false;
            $params = array();
            for($i = 0; $i < count($file); $i++) { //find device section and output to array
                if ($file[$i] == "</remote>" || $file[$i] == "&lt;/remote&gt;") {
                    $found = false;
                    $insertDeviceAt = $i+1;
                }
                if ($found == true) {
                    //redef params after end
                    if ($file[$i] == "</device>" || $file[$i] == "&lt;/device&gt;") {
                        $foundDevice = false;
                        if ($foundOnce == true) {
                            array_push($devices,$params);
                            $params = array();
                        }
                    }
                    if ($foundDevice == true) {
                        $line = trim($file[$i]);
                        $posColon = strpos($line, ":");
                        $tag = substr($line, 0, $posColon);
                        $body = substr($line, $posColon+1);
                        $params[$tag] = trim($body);
                    }
                    if ($file[$i] == "<device>"  || $file[$i] == "&lt;device&gt;") {
                        $foundDevice = true;

                    }
                }
                if ($file[$i] == "<remote>" || $file[$i] == "&lt;remote&gt;") {
                    $found = true;
                    $foundOnce = true;
                }
            }
            if ($foundOnce == false) {
                echo "ERROR_NO_DEVICE_CONFIG";
            }

            //now match id and ip to device
            $deviceNum = null;
            foreach($devices as $ind=>$result) {
                $matchesIP = false;
                $matchesID = false;
                foreach($result as $tag=>$val) {
                    if ($tag == "IP" || $tag == "ip") {
                        if ($val == $IP) {
                            $matchesIP = true;
                        }
                    } else if ($tag == "ID" || $tag == "id") {
                        if ($val == $ID) {
                            $matchesID = true;
                        }
                    }
                }
                if ($matchesIP == true && $matchesID == true) {
                    $deviceNum = $ind;
                }
            }
            if ($deviceNum == null) {
                warn("Response: No device found");
                echo "ERROR_NO_DEVICE_FOUND";
            } else {
                if (updateDeviceValue($devices, $untrim_file, $deviceNum, "Response", " ".$success) !== null) {
                    echo "success";
                } else {
                    echo "fail";
                }
            }
            fclose($fil);
        } else {
            warn("No tracking file from response.php");
        }
            /*$fil = fopen($root.'/___messages.txt', w);
            $date = (date ("F j, Y")); ## Current date
            $time = (date ("h:i:s")); ##  Current time
            $IPnumber = getenv("REMOTE_ADDR"); ## IP Number assigned to your DUN
            fwrite($fil, $dat."\n\nNew message:\nDate: ".$date."\nTime: ".$time."\nIP: ".$IPnumber."\nMessage: ".$message);
            fclose($fil);
        }

        else
        {
            $fil = fopen($root.'/remote/___remote.txt', w);
            fwrite($fil, "Begin private messages file\n");
            fclose($fil);
        }*/
    }
?>