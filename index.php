<?php 
require_once 'ContactForm.php';
$contactForm = new ContactForm('con-form');

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
