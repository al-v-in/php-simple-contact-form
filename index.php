<?php 
//create instance
require_once 'ContactForm.php';
$contactForm = new ContactForm('con-form');

//set config
$contactForm->emailRecip = "test@test.test";
$contactForm->captchaDirUrl = 'securimage/';
$contactForm->fieldAr = array(
	'title' => array(
		'is-req' => true,
		'label' => 'Title',
		'type' => 'options',
		'options' => array(
			'mr' => 'Mr',
			'mrs' => 'Mrs',
			'miss' => 'Miss',
		),
		'default-option' => '',
		'sanitize' => FILTER_SANITIZE_STRING,
		'validation' => null,
		'max-len' => 4
	),	
	'first_name' => array(
		'is-req' => true,
		'label' => 'First Name',
		'type' => 'text',
		'sanitize' => FILTER_SANITIZE_STRING,
		'validation' => null,
		'max-len' => 50
	),
	'last_name' => array(
		'is-req' => false,
		'label' => 'Surname',
		'type' => 'text',
		'sanitize' => FILTER_SANITIZE_STRING,
		'validation' => null,
		'max-len' => 50
	),		
	'email' => array(
		'is-req' => true,
		'label' => 'Email',
		'type' => 'text',
		'sanitize' => FILTER_SANITIZE_EMAIL,
		'validation' => FILTER_VALIDATE_EMAIL,
		'max-len' => 200
	),		
	'message' => array(
		'is-req' => false,
		'label' => 'Message',
		'type' => 'textarea',
		'sanitize' => FILTER_SANITIZE_STRING,
		'validation' => null,
		'max-len' => 9000
	),
	'captcha' => array(
		'is-req' => true,
		'label' => 'Captcha'
	) 
);

//perform action
$contactForm->checkFormAndSendEmail();
?>
<!DOCTYPE html>
<html>
 <head>
  <meta charset="UTF-8">
  <title>title</title>
  <link href="formstyle.css" rel="stylesheet" type="text/css" />
 </head>
 <body>
	<?php $contactForm->makeform(); ?>
 </body>
</html>