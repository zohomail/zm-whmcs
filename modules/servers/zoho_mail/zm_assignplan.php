<?php

use WHMCS\Config;
use WHMCS\Product;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

require '../../../init.php';
$extend = $_POST['extend'];
$plan = $_POST['JTP'];$userid = $_POST['zm_uid'];
if($extend == "true"){
    $accessToken = get_access_token();
    $status = extendTrialPlan($accessToken,$userid);
    
    echo "<script>alert('{$status}');</script>";
}
else if($plan == "Workplace Standard Trial" || $plan == "Workplace Professional Trial" || $plan == "Mail Premium Trial"){
    $accessToken = get_access_token();
    $status = assignTrialPlan($plan, $accessToken,$userid);
    echo "<script>alert('{$status}');</script>";
}
else{
    $userid = $_POST['zm_uid'];
    $payPeriod = $_POST['payperiod'];
    $planId = $_POST['plan'];
    $modify = $_POST['upgrade'];
    
    $addonArry = [];
    $subscription = [];
    
    $addonid ="";
    if($planId == "10730"){
        $addonid = 10779;
    }
    else if($planId == "10731"){
        $addonid = 10780;
    }
    else if($planId == "17033"){
        $addonid = 17083;
    }
    else if($planId == "17034"){
        $addonid = 17084;
    }
    else if($planId == "17035"){
        $addonid = 10792;
    }
    
    if ($planId == "17045") {
        if ($payPeriod == "MONT") {
            if($_POST['mu1'] > 0){
                $addonArry[] = array("id" => 10779, "count" => (int)$_POST['mu1']);
            }
            if($_POST['mu2']){
                $addonArry[] = array("id" => 10780, "count" => (int)$_POST['mu2']);
            }
        } else if ($payPeriod == "YAER"){
            if($_POST['yu1']){
                $addonArry[] = array("id" => 17083, "count" => (int)$_POST['yu1']);
            }
            if($_POST['yu2']){
                $addonArry[] = array("id" => 17084, "count" => (int)$_POST['yu2']);
            }
            if($_POST['yu3']){
                $addonArry[] = array("id" => 10792, "count" => (int)$_POST['yu3']);
            }
            if($_POST['yu4']){
                $addonArry[] = array("id" => 10779, "count" => (int)$_POST['yu4']);
            }
            if($_POST['yu5']){
                $addonArry[] = array("id" => 10780, "count" => (int)$_POST['yu5']);
            }
        }
    } else {
        $addonArry[] = array("id" => (int)$addonid, "count" => (int)$_POST['u0']);
    }
    
    if($_POST['Extra_5_GB']){
        $addonArry[] = array("id" => 191, "count" => (int)$_POST['Extra_5_GB']);
    }
    if($_POST['Extra_25_GB']){
        $addonArry[] = array("id" => 188, "count" => (int)$_POST['Extra_25_GB']);
    }
    if($_POST['Extra_50_GB']){
        $addonArry[] = array("id" => 187, "count" => (int)$_POST['Extra_50_GB']);
    }
    if($_POST['Extra_100_GB']){
        $addonArry[] = array("id" => 186, "count" => (int)$_POST['Extra_100_GB']);
    }
    if($_POST['Extra_200_GB']){
        $addonArry[] = array("id" => 185, "count" => (int)$_POST['Extra_200_GB']);
    }
    
    $subscription["plan"] = (int)$planId;
    $subscription["addons"] = $addonArry;
    $subscription["payperiod"] = $payPeriod;
    
    $json = json_encode($subscription);
    
    //echo "<script>alert('{$json}');</script>";
    
    $conn = Capsule::connection()->getPdo();
    if(Capsule::schema()->hasTable('tblclients')){
        $sql = "SELECT companyname,email,address1,city,state,postcode,country,phonenumber FROM tblclients WHERE id={$userid}";
        $result = $conn->query($sql);
        $clientdetails = $result->fetch();
    }
    
    $email = $clientdetails['email'];
    $countryList = array(
        "IN" => "India",
        "US" => "United States"
    );
    $country = $countryList[$clientdetails['country']];
    $customer = array(
        "companyname" => $clientdetails['companyname'],
        "street" =>$clientdetails['address1'],
        "city" => $clientdetails['city'],
        "state" => $clientdetails['state'],
        "country" => $country,
        "zipcode" => $clientdetails['postcode'],
        "phone" => $clientdetails['phonenumber']
    );
    $accessToken = get_access_token();
    
    if($modify == "false"){
        $status = addSubscriptionPlan($subscription,$accessToken,$customer,$email);
    }
    else {
        $status = modifySubscriptionPlan($subscription,$accessToken,$email);
    }
    echo "<script>alert('{$status}');</script>";
}
?><head> <meta http-equiv="refresh" content="0; url= <?php echo  '../../../admin/clientsservices.php?userid='.$userid?>"/>
</head>
<?php
function extendTrialPlan($accessToken,$userid){
    $conn = Capsule::connection()->getPdo();
    if(Capsule::schema()->hasTable('tblclients')){
        $sql = "SELECT email FROM tblclients WHERE id={$userid}";
        $result = $conn->query($sql);
        $clientdetails = $result->fetch();
    }
    $email = $clientdetails['email'];
    $cli = Capsule::table('zoho_mail')->where('superAdmin',$email)->first();
    $curl = curl_init();
    $cli1 = Capsule::table('zoho_mail_auth_table')->first();
    $urlOrg = 'https://mailadmin.zoho'.$cli1->region.'/api/organization/'.(string)$cli->zoid.'/extendTrial';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken
        ),
    ));
    
    $response = curl_exec($curl);
    $respOrgJson = json_decode($response);
    $getInfo = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $getInfo == '200'){
        if($respOrgJson->status->description == "success"){
            return "Trial Extended for another 15 days";
        }
        return $response;
    }
    else if($getInfo == '500'){
        return $respOrgJson->data->moreInfo;
    }
    else {
        return $response;}
}
function assignTrialPlan($plan, $accessToken,$userid)
{
if($plan == "Workplace Standard Trial"){$plan = "basicTrial";}
else if($plan == "Workplace Professional Trial"){$plan = "professionalTrial";}
else if($plan == "Mail Premium Trial"){$plan = "mailPremiumTrial";}
    $conn = Capsule::connection()->getPdo();
    if(Capsule::schema()->hasTable('tblclients')){
        $sql = "SELECT email FROM tblclients WHERE id={$userid}";
        $result = $conn->query($sql);
        $clientdetails = $result->fetch();
    }
    $email = $clientdetails['email'];
    $cli = Capsule::table('zoho_mail')->where('superAdmin',$email)->first();
    $cli1 = Capsule::table('zoho_mail_auth_table')->first();
    $curl = curl_init();
    $urlOrg = 'https://mailadmin.zoho'.$cli1->region.'/api/organization/'.(string)$cli->zoid.'/enableTrial?planName='.$plan;
    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken
        ),
    ));
    
    $responseOrg = curl_exec($curl);
    $respOrgJson = json_decode($responseOrg);
    $getInfo = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $getInfo == '200')
    {
        if($respOrgJson->status->description == "success"){
            return 'Trial Plan enabled successfully';}
            else{
                return $responseOrg;}
    } else if ($getInfo == '400') {
        $updatedUserCount = Capsule::table('tblproducts')
        ->where('servertype','zoho_mail')
        ->update(
            [
                'configoption5' => '',
            ]
            );
        return 'failed-->'.$responseOrg;
    }
    else {
        return 'Failed -->Description: '.$responseOrg;
    }
}
function addSubscriptionPlan(array $subscription,$accessToken,array $customer,$email)
{
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $bodyArr = array (
        "serviceid" => 1501,
        "email" => $email,
        "customer" => $customer,
        "subscription" => $subscription
    );
    
    $bodyJson = json_encode($bodyArr);
    //echo "<script>alert('{$bodyJson}');</script>";
    $curlOrg = curl_init();
    $urlOrg = 'https://store.zoho'.$cli->region.'/restapi/partner/v1/json/subscription';
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>  array('JSONString'=> $bodyJson),
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken,
            "content-type: multipart/form-data"
        ),
    ));
    $responseOrg = curl_exec($curlOrg);
    $respOrgJson = json_decode($responseOrg);
    $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ( $getInfo == '200')
    {
        if($respOrgJson->result == "success")
            return 'Plan Assigned successfully';
        else
            return $responseOrg;
                
    } else if ($getInfo == '400') {
        $updatedUserCount = Capsule::table('tblproducts')
        ->where('servertype','zoho_mail')
        ->update(
            [
                'configoption5' => '',
            ]
            );
        return 'failed-->'.$responseOrg;
    }
    else {
        return 'Failed -->Description: '.$respOrgJson->status->description;
    }
}

function modifySubscriptionPlan(array $subscription,$accessToken,$email)
{
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $bodyArr = array (
        "serviceid" => 1501,
        "email" => $email,
        "subscription" => $subscription
    );
    
    $bodyJson = json_encode($bodyArr);
    //echo "<script>alert('{$bodyJson}');</script>";
    $curlOrg = curl_init();
    $urlOrg = 'https://store.zoho'.$cli->region.'/restapi/partner/v1/json/subscription';
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS =>  array('JSONString'=> $bodyJson),
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken,
            "content-type: multipart/form-data"
        ),
    ));
    $responseOrg = curl_exec($curlOrg);
    $respOrgJson = json_decode($responseOrg);
    $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ( $getInfo == '200')
    {
        if($respOrgJson->result == "success")
            return 'License upgraded';
            else
                return $responseOrg;
                
    } else if ($getInfo == '400') {
        $updatedUserCount = Capsule::table('tblproducts')
        ->where('servertype','zoho_mail')
        ->update(
            [
                'configoption5' => '',
            ]
            );
        return 'failed-->'.$responseOrg;
    }
    else {
        return 'Failed -->Description: '.$respOrgJson->status->description;
    }
}

function get_access_token() {
    $curl = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlAT = 'https://accounts.zoho'.$cli->region.'/oauth/v2/token?refresh_token='.$cli->token.'&grant_type=refresh_token&client_id='.$cli->clientId.'&client_secret='.$cli->clientSecret.'&redirect_uri='.$cli->redirectUrl.'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.UPDATE,VirtualOffice.partner.organization.READ,ZohoPayments.partnersubscription.all,ZohoPayments.fullaccess.READ,ZohoPayments.leads.READ';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlAT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST"
    ));
    
    $response = curl_exec($curl);
    $accessJson = json_decode($response);
    $getInfo = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    return $accessJson->access_token;
    
}
?>