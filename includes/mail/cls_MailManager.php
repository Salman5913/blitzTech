<?
if (is_file("vendor/autoload.php"))
    include("vendor/autoload.php");
elseif (is_file("../vendor/autoload.php"))
    include("../vendor/autoload.php");
elseif (is_file("../../vendor/autoload.php"))
    include("../../vendor/autoload.php");


use PHPMailer\PHPMailer\PHPMailer;

class MailManager
{
	/**
	 * @var ?PHPMailer
	 */
	private ?PHPMailer $oPHPMailer = null;

	function __construct()
	{
		$FromEmailAddress = Settings::getFromEmailAddress();
		$CompanyName = Settings::getCompanyName();

		$this->oPHPMailer = new PHPMailer();
        $this->oPHPMailer->isSMTP();

        try {
            $this->oPHPMailer->setFrom($FromEmailAddress, $CompanyName);
            //$this->oPHPMailer->addReplyTo($FromEmailAddress, $CompanyName);

            $this->oPHPMailer->CharSet = 'UTF-8';

            $this->oPHPMailer->Host = 'email-smtp.us-west-2.amazonaws.com';
            $this->oPHPMailer->SMTPAuth = true;
            $this->oPHPMailer->Username = 'AKIATZRABMGAR6EKCZRX';
            $this->oPHPMailer->Password = 'BOkzQKoQancyGMrwYUUn2H4CBZw79BBT3/ZYDNDTgw4B';
            $this->oPHPMailer->Port = 587;
            $this->oPHPMailer->SMTPSecure = 'tls';

            $this->oPHPMailer->isHTML(true);
        } catch (\PHPMailer\PHPMailer\Exception|Exception $e) {
            ErrorHandler::setError($e->getMessage());
        }
    }

    function addCC($Email)
    {
        try {
            $this->oPHPMailer->addCC($Email);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            ErrorHandler::setError("CC cannot be added: ".$e->getMessage());
        }
    }

	/**
	 * @param array|string $To
	 * @param $Subject
	 * @param $Body
	 * @param array $Attachments relative path only, no absolute path allowed
	 * @param bool $ShowErrorNSuccess
	 * @return bool
	 */
	function sendMail(array|string $To, $Subject, $Body, array $Attachments=array(), bool $ShowErrorNSuccess=true)
	{
		$isToOk = is_array($To) ? implode(", ", $To) != '' : !empty($To);

		if ($isToOk) // if not empty
		{
			$Status = false;

			try {
				$this->oPHPMailer->Subject = $Subject;
				$this->oPHPMailer->Body = $Body;

				if (!is_array($To))
					$To = [$To];

				foreach ($To as $EmailTo)
					$this->oPHPMailer->addAddress($EmailTo);

				foreach ($Attachments as $Files)
					$this->oPHPMailer->addAttachment($Files);

				$Status = $this->oPHPMailer->send();

			} catch (\PHPMailer\PHPMailer\Exception $e) {
				ErrorHandler::setError("Mailer Exception: " . $e->errorMessage());
			} catch (Exception $e) {
				ErrorHandler::setError("Mailer Exception: " . $e->getMessage());
			}

			if ($ShowErrorNSuccess)
				if ($Status)
					SuccessHandler::setMessage("Mail Sent Successfully to " . implode(", ", $To) . ", Subject: $Subject");
				else
					ErrorHandler::setError("Unable to Send Mail to " . implode(", ", $To) . ", Subject: $Subject");

			return $Status;
		}
		else
			return false;
	}

	/**
	 * Returns Array if 2 emails found else string
	 * @param $AccountId
	 * @return array|string
	 */
	function getRecipients($AccountId)
	{
		$To = DBQM::query("SELECT email1 FROM acc_bio_data WHERE account_id = ".escape($AccountId));

		if (DBQM::result($To, 0, 1) == '')
			return DBQM::result($To, 0, 0);
		else
			return [DBQM::result($To, 0, 0), DBQM::result($To, 0, 1)];
	}

    function addReplyTo($Email)
    {
        try {
            $this->oPHPMailer->addReplyTo($Email);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            ErrorHandler::setError("Reply To: ".$e->getMessage());
        }
    }

    function setFrom($FromEmail)
    {
        try {
            $this->oPHPMailer->setFrom($FromEmail, Settings::getCompanyName());
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            ErrorHandler::setError("Company Name in Mail From: ".$e->getMessage());
        }
    }
}