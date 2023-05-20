<?php
    require 'vendor/autoload.php';
    use Twilio\Rest\Client;

    $connect = mysqli_connect("localhost", "u195637119_vishalsproject", "ZGfgxEaPNJyx@9j", "u195637119_wheels4water");
    
    function decodeGMTOffset($gmtOffset)
    {
        if($gmtOffset < 0) {
            $decimalOffset =  $gmtOffset - ceil($gmtOffset);
            $gmtOffset = $decimalOffset < -0.5 ? floor($gmtOffset) : $gmtOffset;
            return ceil($gmtOffset) . ":" . ($gmtOffset - ceil($gmtOffset)) * 100;
        } else {
            $decimalOffset =  $gmtOffset - floor($gmtOffset);
            $gmtOffset = $decimalOffset > 0.5 ? ceil($gmtOffset) : $gmtOffset;   
            return floor($gmtOffset) . ":" . ($gmtOffset - floor($gmtOffset)) * 100;
        }
    }
    
    $current = date("Y-m-d h:i:sa");
    echo "Current timestamp is: " . $current."<br><br>";
    
    $query = "SELECT * from wishy_templates";
    $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
    
    while($row = mysqli_fetch_array($result_query)) {
        $templateName = $row['tName'];
        echo $row['tName']." ".$row['contactId']." ".$row['email']."<br>";
        
        $query1 = "SELECT * from wishy_contacts WHERE email = '$row[email]' && contactId = '$row[contactId]'";
        $result_query1 = mysqli_query($connect, $query1) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query1);
        
        $query2 = "SELECT * from wishy_users WHERE email = '$row[madeBy]'";
        $result_query2 = mysqli_query($connect, $query2) or die(mysqli_error($connect));
        $res2 = mysqli_fetch_array($result_query2);
        $senderName = $res2['name'];
        
        echo "Sender name is: ".$senderName."<br>";
        
        $sendEmail = true;
        $event = "";
        
        $lastSent = "";
        if($row['type'] == "birthday") {
            $dateToBeUsed = $res['dob'];
            $event = "birthday";
            $lastSent = "sentBirthday";
        }
        else if($row['type'] == "anniversary") {
            $dateToBeUsed = $res['weddingAnniversary'];
            $event = "anniversary";
            $lastSent = "sentAnniversary";
        }
        else if($row['type'] == "festival") {
            $lastSent = "sentFestival";
            if($row['tName'] == "Christmas") {
                $dateToBeUsed = "2023-12-25";
                $event = "christmas";
            }
            else if($row['tName'] == "Diwali") {
                $dateToBeUsed = "2023-11-12";
                $event = "diwali";
            }
            else if($row['tName'] == "Thanksgiving") {
                $dateToBeUsed = "2023-11-23";
                $event = "thanksgiving";    
            }
        } else if($row['type'] == "sms") {
            $event = "birthday";
            $sendEmail = false;
            $lastSent = "sentSMS";
            $dateToBeUsed = $res['dob'];
        }
        
        echo $res['gmtOffset']." ".$dateToBeUsed."<br>";
        
        $currentYear = date("Y");
        $dateYear = date_create($dateToBeUsed)->format("Y");
        $yearsDiff = $currentYear - $dateYear;
        $dateToBeUsed = date_modify(date_create($dateToBeUsed), "+" . $yearsDiff . " years")->format("Y-m-d h:i:sa");
        echo $dateToBeUsed;
        $decodeGMTres = decodeGMTOffset($res['gmtOffset']);
        $hours = explode(":", $decodeGMTres)[0];
        $mins = explode(":", $decodeGMTres)[1];
    
        $mins = $mins < 0 ? $mins : "+" . $mins;
        $hours = $hours < 0 ? $hours : "+" . $hours;
    
        echo " Mins is: ".$mins." & hours is: ".$hours;
    
        $timestamp = date("Y-m-d h:i:sa");
        $timestamp = date_modify(date_create($timestamp), $hours . " hours")->format("Y-m-d h:i:sa");
        $updatedUTCTimestamp = date_modify(date_create($timestamp), $mins . " minutes")->format("Y-m-d h:i:sa");
        echo " " . $updatedUTCTimestamp;
    
        echo " " . date_create($updatedUTCTimestamp)->format("d") . " " . date_create($dateToBeUsed)->format("d");
        
        if (
            (date_create($updatedUTCTimestamp)->format("d") == date_create($dateToBeUsed)->format("d")) && 
            (date_create($updatedUTCTimestamp)->format("m") == date_create($dateToBeUsed)->format("m")) &&
            ($row[$lastSent] == "" || $row[$lastSent] < $currentYear)
        ){
            echo " Same date as of today!! ";
            $update_query = "UPDATE wishy_templates SET $lastSent = '$currentYear' WHERE contactId = '$row[contactId]' && tName = '$row[tName]' && type = '$row[type]'";
            $query_result = mysqli_query($connect, $update_query) or die(mysqli_error($connect));
            
            $img = "";
            $para = "";
            if($row['tName'] == "A1") {
                $img  = "1"; 
                $para = '<p style="font-size:1.5rem; width:80%; text-align: center; margin: auto;"><br>May you get many more years of life together with your love, getting stronger and stronger with time</p>
                    <p style="padding: 3px; font-size: 1.5rem; width:80%; text-align: center; margin: auto;"><br>Happy anniversary,<br>'.$res['name'].'</p>';
            } else if($row['tName'] == "A2") {
                $img  = "2"; 
                $para = '<p style="font-size:1.5em; width:80%; text-align: center; margin: auto;"><br>Happy anniversary, <br>'.$res['name'].'<br><br>Wishing you another year of being together</p>';
            } else if($row['tName'] == "A3") {
                $img  = "3"; 
                $para = '<p style="font-size:1.5em; width:80%; text-align: center; margin: auto;"><br>May you discover more motivation to cherish each other as you grow old together.<br><br>Happy anniversary,<br>'.$res['name'].'</p>';
            } else if($row['tName'] == "B1") {
                $img  = "4"; 
                $para = '<p style="font-size:1.5rem; width:80%; text-align: center; margin: auto;"><br>May guardian angels watch over you today and always. <br><br>Happy birthday,<br>'.$res['name'].'</p>';
            } else if($row['tName'] == "B2") {
                $img  = "5"; 
                $para = '<p style="font-size:1.5rem; width:80%; text-align: center; margin: auto;"><br>I wish that whatever you want in your life, comes to you just the way you imagined or even better!<br><br>Happy birthday, <br>'.$res['name'].'</p>';
            } else if($row['tName'] == "B3") {
                $img  = "6"; 
                $para = '<p style="font-size:1.5rem; width:80%; text-align: center; margin: auto;"><br>A sweet wonderful person as you deserves extra birthday wishes<br><br>Happy birthday, <br>'.$res['name'].'</p>';
            } else if($row['tName'] == "Christmas") {
                $img  = "7"; 
                $para = '<p style="font-size:1.5rem; width:80%; text-align: center; margin: auto;"><br>May this season be full of <u>life</u> & <u>laughter</u> for you and your family</p>'; 
            } else if($row['tName'] == "Diwali") {
                $img = "9"; $para = "";
            } else if($row['tName'] == "Thanksgiving") {
                $img  = "8"; 
                $para = '<p style="font-size:2rem; width:80%; text-align: center; margin: auto;"><br>May your <br><b>thanksgiving</b> be full of love, peace and joy</p>';
            }
            
            $htmlContent = ' 
                <html>
            
                <body>
            
                    <div style="margin: auto; width:500px; text-align: center">
                        <div style="padding: 5px 0px">
                            <p style="border:3px solid #E49B0F; border-radius: 10px;">
                                <span
                                    style="font-size:35px; font-weight: 500; padding: 0px 5px;">'.$senderName.' sent you a message</span>
                            </p>
                        </div>
                    
                        <div>
                        <img src="https://www.versatilevats.tech/images/'.$img.'.jpg" style="width:500px; border-radius: 5px;" alt="" srcset="">
                        
                        <!--Here the paragraph comes for various templates-->
                        '.$para.'
                        </div>
                        <p style="text-align: center; border:3px solid #E49B0F; border-radius: 10px; background: #E49B0F; color: black">With love from Wishy 🦋</p>
                    </div>
                
                </body>
                
            </html>';
            
            $htmlContent1 = ' 
                <html>
            
                <body>
            
                    <div style="margin: auto; width:500px; text-align: center">
                        <div style="padding: 5px 0px;">
                            <p style="border:3px solid #E49B0F;background: #E49B0F; color: white; border-radius: 10px">
                                <span style="font-size:35px; font-weight: 500; padding: 0px 5px;">Wishy did its work ✅</span>
                            </p>
                        </div>
                    
                        <div>
                            <p style="font-size:1.2rem; width:90%; text-align: justify; margin: auto;">
                                Hi <b>Vishal</b>, <br><br>
                                Your contact <u>'.$res['name'].'</u> who is living in a country having an offset of <u>'.$hours.' hours '.$mins.' minutes</u> has been wished for the '.$event.'. 
                                <br><br>The scheduled email/SMS has been sent and this is a confirmation mail regarding the same. 
                                <br><br>
                                Thanks and regards,
                                <br>Wishy 🦋
                            </p>
                        </div>
                        <p style="text-align: center; border:3px solid #E49B0F; border-radius: 10px; background: #E49B0F; color: white">With
                            love from Wishy 🦋</p>
                    </div>
                
                </body>
                
            </html>';
                
            // Set content-type header for sending HTML email 
            $headers = "MIME-Version: 1.0" . "\r\n"; 
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
            
            // Additional headers 
            $headers .= 'From: vishalsproject@versatilevats.tech' . "\r\n"; 
            
            if($sendEmail) {
                echo "Mail sending status is: ". mail($row['email'], $senderName." , is wishing you 🥳 ", $htmlContent, $headers);
            } else {
                echo "Will send a SMS";
                
                $sid    = "ACf1339232a9cc6197b94117610957486f";
                $token  = "9cfede85d22959ff6c9115193e10aca0";
                $twilio = new Client($sid, $token);
            
                $message = $twilio->messages
                  ->create($res['phone'], // to
                    array(
                      "from" => "+13606142398",
                      "body" => $row['sms']
                    )
                  );
            }
            echo "Confirmation mail to the sender has been sent with status: ".mail($res2['email'], "Template delivery confirmation 📫", $htmlContent1, $headers);
        }
        echo "<br><br>";
    }
?>