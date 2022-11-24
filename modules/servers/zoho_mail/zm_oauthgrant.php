<?php
use WHMCS\Config;
use WHMCS\Product;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;
require '../../../init.php';

$code = $_GET['code'];
if (strlen($code) > 0)
{
	$location = '';
    if($_GET['location'] == 'us') {
	     $location = '.com';
       } else {
	     $location ='.eu';
      }
    try {
       $cli = Capsule::table('zoho_mail_auth_table')->where('region',$location)->first();
       $refurl='https://accounts.zoho'.$cli->region.'/oauth/v2/token?code='.$_GET['code'].'&client_id='.$cli->clientId.'&client_secret='.$cli->clientSecret.
       '&redirect_uri='.$cli->redirectUrl.'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.UPDATE,VirtualOffice.partner.organization.READ,ZohoPayments.partnersubscription.all,ZohoPayments.fullaccess.READ,ZohoPayments.leads.READ&state=1a8d7v6r5rw4q2cadsetw&grant_type=authorization_code';
       $curl = curl_init();
       curl_setopt_array($curl, array(
                      CURLOPT_URL => $refurl,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                   ));
       $response = curl_exec($curl);
       $jsonDecode = json_decode($response);
       $err = curl_error($curl);
       curl_close($curl);
       $tabValue="";
       if (Capsule::schema()->hasTable('zoho_mail_auth_table')) 
            {
                $keywords = preg_split('/__/', $cli->token);
              }
       $updatedUserCount = Capsule::table('zoho_mail_auth_table')
                            ->where('region',$location)
                            ->update(
                                  [
                                   'token' => $jsonDecode->refresh_token,
                                   ]
                               );
       if (strlen($jsonDecode->refresh_token)>0) {
        if(empty($keywords[2])) {
          $keywords[2] = "admin";
        }
       	 ?><head> <meta http-equiv="refresh" content="0; url= <?php echo  '../../../'.$keywords[2].'/configproducts.php?action=edit&id='.$keywords[1].$keywords[0]?>"/></head>
       	 <?php
       } 
      } catch(Exception $e) {
	     echo $e;
      }

} else {
	try {
		if (Capsule::schema()->hasTable('zoho_mail_auth_table'))
		{
      $client = Capsule::table('zoho_mail_auth_table')->first();
      if (strlen($client->clientId) > 3 ) {
        Capsule::table('zoho_mail_auth_table')->delete();
      }//do nothing
		} else 
		{
           Capsule::schema()->create(
                               'zoho_mail_auth_table',
                                function ($table) {
                                         $table->string('region');
                                         $table->string('clientId')->unique();
                                         $table->string('clientSecret');
                                         $table->string('redirectUrl');
                                         $table->string('token');
                                       }
                               );
        }
	  $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();
    $statement = $pdo->prepare('insert into zoho_mail_auth_table (region, clientId, clientSecret, redirectUrl, token) values (:region, :clientId, :clientSecret, :redirectUrl, :token)');
    $statement->execute(
                           [
                            ':region' => $_POST['zm_dn'],
                            ':clientId' => $_POST['zm_ci'],
                            ':clientSecret' => $_POST['zm_cs'],
                            ':redirectUrl' => $_POST['zm_ru'],
                            ':token' => $_POST['zm_tab_value'].'__'.$_POST['zm_pi'].'__'.$_POST['zm_ad'],
                                 ]
                              );
    $pdo->commit();
   } catch(Exception $e) {
	echo $e;
   }
	$url='https://accounts.zoho'.$_POST['zm_dn'].'/oauth/v2/auth?response_type=code&client_id='.$_POST['zm_ci'].'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.UPDATE,VirtualOffice.partner.organization.READ,ZohoPayments.partnersubscription.all,ZohoPayments.fullaccess.READ,ZohoPayments.leads.READ&redirect_uri='.$_POST['zm_ru'].'&state=1a8d7v6r5rw4q2cadsetw&prompt=consent&access_type=offline';
	?>
   <head> <meta http-equiv="refresh" content="0; url= <?php echo $url?>"/> </head>  
   <?php
    }
   ?>
