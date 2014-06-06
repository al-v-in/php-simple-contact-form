<?php 
/**
 * A simple contact form. Takes a specification array for
 * the form, sends the email.
 *
 * Has ability to remember field values in cookies if required.
 * 
 * Form can have a captcha via the securimage plugin.
 *
 * You shouldn't need to edit this file.
 *
 * @author Alvin Blewitt
 */
class ContactForm
{
	/* FORM SETTINGS  
	 * client script will want to overwrite (some of) these */
	public $isSaveDataInCookies = false;
	public $cookieExpiry = 30;
	public $emailRecip = "recip@domain.tld";
	public $emailFrom = "from@domain.tld";
	public $emailSubject = "Form Submission";
	public $formMsg = "Please fill out the form below and we'll get back to you as soon as we can.";
	public $formMsgThanks = "Thank you, the form was successfully sent.";
	public $formMsgError = "Sorry, there was a problem sending the form.";
	public $captchaDirPath = 'securimage/'; //used in a private php "includes" - relative to this file
	public $captchaDirUrl = '/securimage/'; //used in public img src relative to web location (i.e. use /[path] to be relative to root)
	//example and default fields
	//copy, paste and modify in client file
	public $fieldAr = array(
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
	
	/* SCRIPT VARIBLES 
	 * accessable via getters below */
	protected $formId;
	protected $isShowForm = true;
	protected $isErr = false;
	protected $isSendingEmail = false;
	
	
	public function __construct($formId, $fieldAr = null){
		$this->formId = $formId;
		if( $fieldAr )
			$this->fieldAr = $fieldAr;
	}
	
	public function checkForm()
	{
		if( isset($_POST['form-id']) && $_POST['form-id'] == $this->formId )
		{
			session_start(); //for capctcha
			
			$this->isErr = false;
			$isAtLeastOneValid = false;
			
			foreach( $this->fieldAr as $fieldId => $field )
			{
				$reqField = false;
				if( $fieldId == 'captcha' )
				{
					$securImageScript = __DIR__ . '/' . $this->captchaDirPath . 'securimage.php';
					if( file_exists($securImageScript) ){
						include_once $securImageScript;
						if( class_exists('Securimage') ){
							$securimage = new Securimage();
							if(!isset($_POST['captcha_code']) || !trim($_POST['captcha_code']) )
							{
								$this->fieldAr[$fieldId]['error'] = 'This field is required';
								$this->isErr = true;
							}
							elseif ($securimage->check($_POST['captcha_code']) == false)
							{
								$this->fieldAr[$fieldId]['error'] = 'The security code entered was incorrect';
								$this->isErr = true;
							}
						}
						else{
							$this->fieldAr[$fieldId]['error'] = 'Sorry error occured (captcha class doesnt exist)';
							$this->isErr = true;
						}
					}
					else{
						$this->fieldAr[$fieldId]['error'] = 'Sorry error occured (captcha file doesnt exist)';
						$this->isErr = true;
					}
				}
				elseif( isset($_POST[$fieldId]) && ($val = trim($_POST[$fieldId])) )
				{
					$isValid = true;
					if( ( $val = filter_var($_POST[$fieldId], $field['sanitize']) ) === false )
						$isValid = false;
					if($field['validation'] && ( $val = filter_var($_POST[$fieldId], $field['validation']) ) === false )
						$isValid = false;
					if( $field['type'] == 'options' && isset($field['options']) && is_string($val) && !isset($field['options'][$val]) )
						$isValid = false;
					
					if( !$isValid ){
						$this->isErr = true;
						$this->fieldAr[$fieldId]['error'] = 'Your entry is invalid';
					}
					else
					{
						if( strlen($val) > $field['max-len'] ){
							$this->isErr = true;
							$this->fieldAr[$fieldId]['error'] = 'This field is must be below ' . $field['max-len'] . ' characters';
						}
						else{
							$this->fieldAr[$fieldId]['value'] = $val;
							if( $this->isSaveDataInCookies )
								setcookie( $this->formId . '_' . $fieldId, $val, time()+(64000 * $this->cookieExpiry) );
							
							if( $field['type'] == 'options' )
								$val = $field['options'][$val];
							$this->fieldAr[$fieldId]['formatted-value'] = $val;
								
							$isAtLeastOneValid = true;
						}
					}
				}
				elseif( $field['is-req'] ){
					$this->isErr = true;
					$this->fieldAr[$fieldId]['error'] = 'This field is required';
				}
			}
			
			if( !$this->isErr && $isAtLeastOneValid )
				$this->isSendingEmail = true;
		}
	}
	
	public function sendEmail(){
		if( $this->isSendingEmail )
		{
			$emailBody = "";
			foreach( $this->fieldAr as $fieldId => &$field )
			{
				if( isset($field['value']) && $fieldId != 'captcha' )
				{
					$emailBody .= $field['label'] . ":\n";
					$emailBody .= $field['formatted-value'] . "\n\n";
				}
			}
			
			$headers = 'From: ' . $this->emailFrom;
			
			if( @mail($this->emailRecip, $this->emailSubject, $emailBody, $headers) )
			{
				$this->formMsg = $this->formMsgThanks;
				$this->isShowForm = false;
			}
			else
			{
				$this->formMsg = $this->formMsgError;
				$this->isShowForm = false;
			}
		}
	}
	
	/**
	 * Separated out incase you want to do something inbetween, but if not
	 use this convenience method
	 */
	public function checkFormAndSendEmail(){
		$this->checkForm();
		$this->sendEmail();
	}
	
	public function makeform( $postForm = "" )
	{
		?>
		<div class="nww-form">
			<div class="form-msg"><?php echo $this->formMsg; ?></div>

			<?php if( $this->isShowForm ) : ?>
				<form id="<?php echo $this->formId; ?>" action="" method="post"><fieldset>
					<input type="hidden" name="form-id" value="<?php echo $this->formId; ?>" />
					<?php foreach($this->fieldAr as $fieldId => $field) : 
						$fieldUid = $this->formId . '_' . $fieldId;
						$value = "";
						//check POST first for the value
						if( isset($_POST['form-id']) && $_POST['form-id'] == $this->formId && isset($_POST[$fieldId]) ) 
							$value = htmlspecialchars($_POST[$fieldId]);
						//if not, check GET
						elseif( isset($_GET[$fieldId]) )
							$value = htmlspecialchars($_GET[$fieldId]);
						elseif( $this->isSaveDataInCookies && isset($_COOKIE[$fieldUid]) )
							$value = htmlspecialchars($_COOKIE[$fieldUid]);
						
						$classAr = array('field');
						if( isset($field['error']) )
							$classAr[] = 'field-error';
						?>
						<div class="<?php echo implode(' ', $classAr); ?>">
							<label for="<?php echo $fieldId; ?>"><?php echo $field['label']; if($field['is-req']) echo "*" ?>:</label>
							
							<?php if( $fieldId == 'captcha' ) { ?>
								<img id="captcha" src="<?php echo $this->captchaDirUrl; ?>securimage_show.php" alt="CAPTCHA Image" />
								<input type="text" name="captcha_code" size="10" maxlength="6" />
								<a href="#" onclick="document.getElementById('captcha').src = '<?php echo $this->captchaDirUrl; ?>/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>

							<?php } elseif( $field['type'] == 'text' )
								echo '<input type="text" id="' . $fieldId . '" name="' . $fieldId . '" value="' . $value . '" />';
							elseif( $field['type'] == 'textarea' )
								echo '<textarea name="' . $fieldId . '" id="' . $fieldId . '">' . $value . '</textarea>';
							elseif( $field['type'] == 'options' && isset($field['options']) && isset($field['default-option']) ){
								echo '<select id="' . $fieldId . '" name="' . $fieldId . '">';
								if( $field['default-option'] === "" || $field['default-option'] === null )
									echo '<option value="">Please select...</option>';
								foreach( $field['options'] as $optionKey => $optionValue ){
									$seled = "";
									if( $optionKey == $value )
										$seled = 'selected="selected"';
									echo '<option value="' . $optionKey . '" ' . $seled . '>' . $optionValue . '</option>';
								}
								echo '</select>';
							}
							?>
							
							<?php if( isset($field['error']) ) : ?>
								<div class="field-error-msg"><?php echo $field['error']; ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					
					<div class="req-note">* denotes a required field</div>
					<input type="submit" name="submit" value="Submit Form" />
					
					<?php echo $postForm; ?>
				</fieldset></form>
			<?php endif; 	?>
		</div>
		<?php
	}
	
	public function getFormId(){
		return $this->formId;
	}
	
	public function isShowForm(){
		return $this->isShowForm;
	}
	
	public function isError(){
		return $this->isErr;
	}
	
	public function isSendingEmail(){
		return $this->isSendingEmail;
	}
	
}