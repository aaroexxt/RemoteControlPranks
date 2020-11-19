<?php
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
    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    $root = realpath($_SERVER["DOCUMENT_ROOT"]);
    if (file_exists($root.'/remote/___remote.txt')) 
    {   
        echo "<p>";
        $fil = fopen($root.'/remote/___remote.txt', r);
        echo nl2br("<center><h1>RemoteControl Console</h1>");
        echo nl2br("<h4>By Aaron Becker</h4></center>\n");

        $file = array(); //setup
        $conf = array();
        $devices = array();

        while (!feof($fil)) { //read file to array
            //$line = nl2br(htmlspecialchars(fgets($fil)));
            array_push($file,htmlspecialchars(trim(fgets($fil))));
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
            echo "Error: No configuration section found in file<br>";
        }

        $found = false;
        $foundDevice = false;
        $foundOnce = false;
        $params = array();
        for($i = 0; $i < count($file); $i++) { //find configuration section and output to array
            if ($file[$i] == "</remote>" || $file[$i] == "&lt;/remote&gt;") {
                $found = false;
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
            echo "Error: No devices configuration found in file<br>";
        }

        echo "<br>Configuration:<br>";
        foreach($conf as $result) {
            echo nl2br($result), '<br>';
        }
        echo "<br>Devices:<br>";
        foreach($devices as $ind=>$result) {
            echo "Index: ".$ind, '<br>';
            foreach($result as $tg=>$res) {
                echo $tg.": ".($res == ""?"(nothing)":$res);
                if ($tg == "Name" || $tg == "Command" || $tg == "Response" || $tg == "UpdateInterval") {
                    $ipval = getDeviceValue($devices, $ind, "IP");
                    $idval = getDeviceValue($devices, $ind, "ID");
                    $uuid = "id-".trim(gen_uuid());
                    echo "<input id='".$uuid."'></input>";
                    echo "<button onclick=\"try{var insval = document.getElementById('".$uuid."').value.replaceAll('(','&op>').replaceAll(')','&cp>').replaceAll('[','&ob>').replaceAll(']','&cb>').replaceAll('{','&oc>').replaceAll('}','&cc>'); modifyDevice('".$idval."','".$ipval."','".$tg."','\''+insval+'\'');}catch(e){console.error('Error running modifydeviceval: '+e);}\">Submit (".$tg.")</button>";
                }
                echo "<br>";
            }
            echo '<br>';
        }
        echo "<br>Raw File:<br>";
        foreach($file as $result) {
            echo nl2br($result), '<br>';
        }

        echo "</p>";

echo <<<EOF
<script type="text/javascript">
    var ajax={};ajax.x=function(){if("undefined"!=typeof XMLHttpRequest)return new XMLHttpRequest;for(var e,n=["MSXML2.XmlHttp.6.0","MSXML2.XmlHttp.5.0","MSXML2.XmlHttp.4.0","MSXML2.XmlHttp.3.0","MSXML2.XmlHttp.2.0","Microsoft.XmlHttp"],t=0;t<n.length;t++)try{e=new ActiveXObject(n[t]);break}catch(a){}return e},ajax.send=function(e,n,t,a,o){void 0===o&&(o=!0);var r=ajax.x();r.open(t,e,o),r.onreadystatechange=function(){4==r.readyState&&n(r.status,r.responseText)},"POST"==t&&r.setRequestHeader("Content-type","application/x-www-form-urlencoded"),r.send(a)},ajax.get=function(e,n,t,a){var o=[];for(var r in n)o.push(encodeURIComponent(r)+"="+encodeURIComponent(n[r]));ajax.send(e+(o.length?"?"+o.join("&"):""),t,"GET",null,a)},ajax.post=function(e,n,t,a){var o=[];for(var r in n)o.push(encodeURIComponent(r)+"="+encodeURIComponent(n[r]));ajax.send(e,t,"POST",o.join("&"),a)},ajax.cors=function(e,n,t){var a=new ajax.x;"withCredentials"in a?a.open(n,e,!0):"undefined"!=typeof XDomainRequest?(a=new XDomainRequest,a.open(n,e)):a=null,a.onreadystatechange=function(){4==a.readyState&&t(a.status,a.responseText)},a.send()};
    function modifyDevice(id,ip,tg,val){
        if (typeof ajax !== "undefined") {
            ajax.post("https://www.aaronbecker.tech/remote/modifyDevice.php",{
                ID:id,IP:ip,tag:tg,value:val
            },function(status, response){
                if (status != 200) {
                    console.error("Failed to modify device: returned status "+status);
                } else {
                    if (response != "success") {
                        console.error("Server responded that the device failed to be modified. Response: "+response);
                    } else {
                        console.log("Set device successfully. Reloading.");
                        window.location.reload();
                    }
                }
            });
        } else {
            console.error("Ajax object undefined.");
        }
    }
    String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.split(search).join(replacement);
    };
</script>
EOF;

        echo $js;

        fclose($fil);
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
?>