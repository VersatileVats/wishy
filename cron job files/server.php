<?php
    require 'vendor/autoload.php';
    use Twilio\Rest\Client;

    $connect = mysqli_connect("localhost", $USERNAME, $DB_PWD, $DB_NAME);
    session_start();
    
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    header("Access-Control-Allow-Origin: *");
    header('content-type: application/json; charset=utf-8');
    
    function sendSMS($to, $otp) {

        $sid    = $TWILIO_SID;
        $token  = $TWILIO_TOKEN;
        $twilio = new Client($sid, $token);

        $message = $twilio->messages
          ->create($to,
            array(
              "from" => "+13606142398",
              "body" => 'Hi user. Welcome to WISHY. The telephone OTP for verification is: ' . $otp
            )
          );
    }
    
    function sendOTP($email, $otp) {
            
        $htmlContent = ' 
            <html>
        
            <body>
    
                <div style="margin: auto; border:5px solid #E49B0F; border-radius: 10px; width:500px; text-align: center; font-size: 15px">
                    Verfication OTP for Wishy is: '.$otp.'
                </div>
            
            </body>
            
        </html>';
            
        // Set content-type header for sending HTML email 
        $headers = "MIME-Version: 1.0" . "\r\n"; 
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
        
        // Additional headers 
        $headers .= 'From: vishalsproject@versatilevats.tech' . "\r\n"; 
        $response->result = mail($email, '🦋 Wishy: Email OTP', $htmlContent, $headers);
        return json_encode($response);
    }
    
    function createDBRecord($email, $name, $phone, $pwd, $connect){
        
        if($email == "" || $name == "" || $pwd == "" || $phone == "") {
            $response->error = "Insufficient values provided";
            return json_encode($response);
        }
        
        $email = mysqli_real_escape_string($connect, $email);
        $name = mysqli_real_escape_string($connect, $name);
        $phone = mysqli_real_escape_string($connect, $phone);
        $pwd = mysqli_real_escape_string($connect, $pwd);
        
        $pwd = md5($pwd);
        
        $query1 = "SELECT * from wishy_users WHERE email = '$email' || phone = '$phone'";
        $result_query1 = mysqli_query($connect, $query1) or die(mysqli_error($connect));
        
        if(mysqli_num_rows($result_query1) > 0) {
            $response->error = "Provided email/phone no is already registered";
            return json_encode($response);
        }
        
        $createdAt = date("Y-m-d h:i:sa");
        $insert_query = "INSERT into wishy_users (email,name,pwd,phone,createdAt) VALUES ('$email','$name','$pwd','$phone','$createdAt')";
        $query_result = mysqli_query($connect, $insert_query) or die(mysqli_error($connect));
        $id = mysqli_insert_id($connect);
        
        $response->result = $name.":".$email;
        return json_encode($response);
        // return "Made the record#".$id;
    }
    
    function login($email, $pwd, $connect) {
        $email = mysqli_real_escape_string($connect, $email);
        $pwd = mysqli_real_escape_string($connect, $pwd);
        
        $pwd = md5($pwd);
        
        $query = "SELECT * FROM wishy_users WHERE email = '$email' && pwd = '$pwd'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query);
        
        if(mysqli_num_rows($result_query) == 0){
            $response->error = "No matching response found";
            $result = json_encode($response);
            return $result;
        } else {
            $response->result = $res['name'].":".$res['plan'];
            return json_encode($response);
        }
        
    }
    
    function getContacts($email, $connect) {
        $query = "SELECT * from wishy_contacts WHERE madeBy = '$email'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        
        if(mysqli_num_rows($result_query) == 0){
            $response->error ="NA";
            return json_encode($response);
        }
        
        $response;
        $itr = 0;
        while($row = mysqli_fetch_array($result_query)) {
            $wa = $row['weddingAnniversary'] == null ? "0" : $row['weddingAnniversary'];
            
            // "%" is the delimeter for the each row result & "||" is the seperator between two rows
            // $response.= $row['email']."%".$row['dob']."%".$row['name']."%".$wa."%".$row['festivals']."%".$row['contactId']."%".$row['phone']."%".$row['description']."||";
            
            $response->$itr->email = $row["email"];
            $response->$itr->dob = $row["dob"];
            $response->$itr->name = $row["name"];
            $response->$itr->wa = $row["wa"];
            $response->$itr->festivals = $row["festivals"];
            $response->$itr->contactID = $row["contactId"];
            $response->$itr->phone = $row["phone"];
            $response->$itr->description = $row["description"];
            
            $info = $row["name"] . "'s date of birth is " . $row["dob"] . " &";
            
            if($wa == null || $wa == "" || $wa == 0) {
                $info.= " is currently unmarried. "; 
            } else {
                $info.= " wedding anniversary is on " . $wa . ". ";
            }
            
            $info.="Notifications for " .  str_split($row["festivals"], strlen($row["festivals"]) - 1)[0] . " can be sent";
            
            $response->$itr->information = $info;
            $itr++;
        }
        
        return json_encode($response);
    }
    
    function makeContact($email, $dob, $name, $weddingAnniversary, $festivals, $madeBy, $gmtOffset, $phone, $description, $connect) {
        // weddingAnniversary would be 0 if user has told that the contact is single
        
        if($email == "" || $name == "" || $dob == "" || $madeBy == "" || $gmtOffset == "" || $phone == "") {
            $response->error = "Provide all the details";
            return json_encode($response);
        }
        
        // festivals would be seperated by ","
        $numFestivals = sizeof(explode(",",$festivals));
        
        $query = "SELECT * from wishy_contacts WHERE (madeBy = '$madeBy' && email = '$email') || (madeBy = '$madeBy' && phone = '$phone')";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        
        if(mysqli_num_rows($result_query) >0) {
            $response->error = "Contact with similar phone/email has already been attached";
            return json_encode($response);
        }
        
        $query = "SELECT * from wishy_users WHERE email = '$madeBy'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query);
        
        $plan = $res['plan'];
        
        $query = "SELECT * from wishy_contacts WHERE madeBy = '$madeBy'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $noOfContacts = mysqli_num_rows($result_query);
        
        if($plan == "free" && $noOfContacts == 5) {
            $response->error = "Free plan only allow 5 contacts. Upgrade!!";
            return json_encode($response);
        } else if($plan == "standard" && $noOfContacts == 8) {
            $response->error = "Standard plan only allow 8 contacts. Upgrade!!";
            return json_encode($response);
        } else if($plan == "premium" && $noOfContacts == 10) {
            $response->error = "Maximum number of contacts have been made";
            return json_encode($response);
        }
        
        
        // inserting the db record
        if($weddingAnniversary == "0") {
            $insert_query = "INSERT into wishy_contacts (email,dob,name,weddingAnniversary,festivals,madeBy,gmtOffset,phone,description) VALUES ('$email','$dob','$name',null,'$festivals','$madeBy','$gmtOffset','$phone','$description')";
            $query_result = mysqli_query($connect, $insert_query) or die(mysqli_error($connect));
        } else {
            $insert_query = "INSERT into wishy_contacts (email,dob,name,weddingAnniversary,festivals,madeBy,gmtOffset,married,phone,description) VALUES ('$email','$dob','$name','$weddingAnniversary','$festivals','$madeBy','$gmtOffset','true','$phone','$description')";
            $query_result = mysqli_query($connect, $insert_query) or die(mysqli_error($connect));    
        }
        
        $id = mysqli_insert_id($connect);
        
        $contactId = str_replace(" ","",$name).$numFestivals.$id;
        
        $update_query = "UPDATE wishy_contacts SET contactId = '$contactId' WHERE email = '$email' && madeBy = '$madeBy'";
        $query_result = mysqli_query($connect, $update_query) or die(mysqli_error($connect));
        
        
        $response->result = "Your contact has been created. Reload to see the changes";
        return json_encode($response);
    }
    
    function changePlan($email, $plan, $connect) {
        $query = "SELECT * from wishy_users WHERE email = '$email'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query);
        
        $currentPlan = $res['plan']; 
        
        if($currentPlan == $plan) {
            $response->error = "You can't purchase the same plan again";
            return json_encode($response);
        }
        
        if($currentPlan == "free") {
            if($plan == "standard" || $plan =="premium") {
                $update_query = "UPDATE wishy_users SET plan = '$plan' WHERE email = '$email'";
                $query_result = mysqli_query($connect, $update_query) or die(mysqli_error($connect));
                
                $response->result = "Congrats 🎉. Upgraded to ".$plan." tier. Refresh to see the changes";
                return json_encode($response);
                
            } else {
                $response->error = "You have already subscribed this tier.";
                return json_encode($response);
            }
        } else if($currentPlan == "standard") {
            if($plan == "premium") {
                $update_query = "UPDATE wishy_users SET plan = '$plan' WHERE email = '$email'";
                $query_result = mysqli_query($connect, $update_query) or die(mysqli_error($connect));
                
                $response->result = "Congrats 🎉. Upgraded to ".$plan." tier. Refresh to see the changes";
                return json_encode($response);
            } else {
                $response->error = "Downgrading plans is not supported. Enjoy your STANDARD plan";
                return json_encode($response);
            }
        } else if($currentPlan == "premium") {
            $response->error = "Downgrading plans is not supported. Enjoy your PREMIUM plan";
            return json_encode($response);
        }
    }
    
    function makeTemplate($cid, $email, $type, $template, $connect, $sms = "") {
        // check whether the given CID is correct & is affiliated to one of the customers  
        $query = "SELECT * from wishy_contacts WHERE contactId = '$cid' && madeBy = '$email'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query);
        
        if($type == "festival") {
            if(mysqli_num_rows($result_query) == 0) {
                $response->error = "The given Contact Id is not valid! Try Again";
                return json_encode($response);
            }
            
            $flag = false;
            $sepFestivals = explode(",",$res["festivals"]);
            for($a=0; $a < count($sepFestivals); $a++) {
                if($sepFestivals[$a] == $template) {
                    $flag = true;
                }
            }
            if(!($flag)) {
                $response->error = $template." was not included in this contact's record";
                return json_encode($response);
            }
        } else if($type == "anniversary") {
            if($res['weddingAnniversary'] == null) {
                $response->error = "Can't add the template as wedding anniversary for this contact has not been added!";
                return json_encode($response);
            }
        }
        
        if(mysqli_num_rows($result_query) == 0) {
            $response->error = "The given Contact Id is not valid! Try Again";
            return json_encode($response);
        } else {
            // as the cid is correct, look out for a previous template record for the same cid in the wishy_templates table
            if($type == "festival") {
                $query = "SELECT * from wishy_templates WHERE contactId = '$cid' && madeBy = '$email' && tName = '$template'";
                $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
            } else {
                $query = "SELECT * from wishy_templates WHERE contactId = '$cid' && madeBy = '$email' && type = '$type'";
                $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));   
            }
            
            if(mysqli_num_rows($result_query) > 0) {
                $response->error = "Previous template for the same contact has been made! Can't duplicate things";
                return json_encode($response);
            }
            
            $insert_query = "INSERT into wishy_templates (contactId,email,name,phone,madeBy,tName,type,sms) VALUES ('$cid','$res[email]','$res[name]','$res[phone]','$email','$template','$type','$sms')";
            $query_result = mysqli_query($connect, $insert_query) or die(mysqli_error($connect));
            
            $response->result = "Your request has been updated. Cheers!";
            return json_encode($response);
        }
        
    }
    
    function deleteTemplate($cid, $email, $type, $template, $connect) {
        $query = "SELECT * from wishy_contacts WHERE contactId = '$cid' && madeBy = '$email'";
        $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
        $res = mysqli_fetch_array($result_query);
        
        if(mysqli_num_rows($result_query) == 0) {
            $response->error = "The given Contact Id is not valid! Try Again";
            return json_encode($response);
        } else {
            $query = "SELECT * from wishy_templates WHERE contactId = '$cid' && madeBy = '$email' && type = '$type' && tName = '$template'";
            $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
            
            if(mysqli_num_rows($result_query) == 0) {
                $response->error = "No such template is found for the given Contact Id";
                return json_encode($response);
            } else {
                $query = "DELETE from wishy_templates WHERE contactId = '$cid' && madeBy = '$email' && type = '$type' && tName = '$template'";
                $result_query = mysqli_query($connect, $query) or die(mysqli_error($connect));
                
                $response->result = "Template deleted";
                return json_encode($response);
            }
        }
    }
    
    switch($_GET['todo']) {
        case 'insert':
            $data = json_decode(file_get_contents('php://input'), true);
            echo createDBRecord($data['email'], $data['name'], $data['phone'], $data['pwd'], $connect);
            break;
            
        case 'login':
            $data = json_decode(file_get_contents('php://input'), true);
            echo login($data['email'], $data['pwd'], $connect);
            break;
            
        case 'sendSMS':
            $data = json_decode(file_get_contents('php://input'), true);
            echo sendSMS($data['to'], $data['otp']);
            break;
            
        case 'sendOTP':
            $data = json_decode(file_get_contents('php://input'), true);
            echo sendOTP($data['email'], $data['otp']);
            break;
        
        case 'getContacts':
            $data = json_decode(file_get_contents('php://input'), true);
            echo getContacts($data['email'], $connect);
            break;
        
        case 'makeContact':
            $data = json_decode(file_get_contents('php://input'), true);
            echo makeContact($data['email'], $data['dob'], $data['name'], $data['weddingAnniversary'], $data['festivals'], $data['madeBy'], $data['gmtOffset'], $data['phone'], $data['description'], $connect);
            break;
            
        case 'changePlan':
            $data = json_decode(file_get_contents('php://input'), true);
            echo changePlan($data['email'], $data['plan'], $connect);
            break;
            
        case 'makeTemplate':
            $data = json_decode(file_get_contents('php://input'), true);
            echo makeTemplate($data['cid'], $data['email'], $data['type'], $data['template'], $connect, $data['sms']);
            break;
        
        case 'deleteTemplate':
            $data = json_decode(file_get_contents('php://input'), true);
            echo deleteTemplate($data['cid'], $data['email'], $data['type'], $data['template'], $connect);
            break;
        
        default:
            $response->error = "Invalid endpoint!! Try with a correct one";
            return json_encode($response);
            break;
    }
?>  