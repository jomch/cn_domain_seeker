<?php

define('_ROOT', __DIR__);

require_once _ROOT.'/libs/PHPMailer.php';
require_once _ROOT.'/libs/SMTP.php';
require_once _ROOT.'/config.inc.php';

function curl_get($url) {
	$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_TIMEOUT, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($curl, CURLOPT_URL,$url);
	$res = curl_exec($curl);
	if($res){
		curl_close($curl);
		return $res;
	}else {
		$error = curl_errno($curl);
		curl_close($curl);
		return $error;
	}
}

function send_mail($msg) {
	global $config;
	$mail = new PHPMailer();
	$mail->isSMTP();
	//$mail->SMTPDebug = 2;
	$mail->Host = 'smtp.qq.com';
	$mail->Port = 465;
	$mail->SMTPSecure = "ssl";
	$mail->SMTPAuth = true;
	$mail->Username = $config['smtp_username'];
	$mail->Password = $config['smtp_password'];
	$mail->setFrom($config['smtp_username'], 'Postman');
	//$mail->addReplyTo('replyto@example.com', 'First Last');
	$mail->addAddress($config['inbox_addr'], 'inbox');
	$mail->IsHTML(true);
	$mail->Subject = 'Domain Seeker report';
	$mail->Body = $msg;
	//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
	//$mail->AltBody = 'This is a plain-text message body';
	//$mail->addAttachment('images/phpmailer_mini.png');
	if (!$mail->send()) {
		echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo 'Message sent!';
	}
}

$dict_array = array();

$file = file(_ROOT."/pinyin_raw.txt");
foreach($file as $item) {
	foreach($file as $item2) {
		$dict_array[] = trim($item).trim($item2);
	}
}

$file2 = file(_ROOT."/words2000_raw.txt");
foreach($file2 as $item) {
	$dict_array[] = trim($item);
}

$future2todayDelTxt = curl_get("http://www.cnnic.cn/download/registar_list/future2todayDel.txt");

$future2todayDelTxtArray = explode("\n", $future2todayDelTxt);

if(!empty($future2todayDelTxtArray)) {

	$domains = "";
	foreach( $future2todayDelTxtArray as $item ) {
		$domain = trim($item);
		if( preg_match("/\[[0-9]{1,4}\.cn\]/i", $domain) ) {
			$domain_body =  substr($domain, 1, strpos($domain, '.')-1 );
			$domains .= $domain_body."<br>";
		} else {
			if( preg_match("/\[[a-z]+\.cn\]/i", $domain) ) {
				$domain_body =  substr($domain, 1, strpos($domain, '.')-1 );
				if( in_array( $domain_body, $dict_array ) ) {
					$domains .= $domain_body."<br>";
				}
			}
		}
	}

	if( !empty($domains) ) {
		$msg = str_replace("{msg}", $domains, file_get_contents(_ROOT."/mail_html_tpl.html"));
		send_mail($msg);
	}

}