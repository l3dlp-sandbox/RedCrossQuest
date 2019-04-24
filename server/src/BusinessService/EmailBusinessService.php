<?php

namespace RedCrossQuest\BusinessService;


use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\Logging\PsrLogger;
use Ramsey\Uuid\Uuid;
use RedCrossQuest\DBService\MailingDBService;
use RedCrossQuest\DBService\UniteLocaleDBService;
use RedCrossQuest\Entity\MailingInfoEntity;
use RedCrossQuest\Entity\QueteurEntity;
use RedCrossQuest\Entity\UniteLocaleEntity;

use RedCrossQuest\Service\MailService;

use Carbon\Carbon;


class EmailBusinessService
{
  /**
   * @var PsrLogger
   */
  protected $logger;

  protected $appSettings;
  /**
   * @var MailingDBService
   * */
  protected $mailingDBService;

  /**
   * @var UniteLocaleDBService
   * */
  protected $uniteLocaleDBService;

  /**
   * @var MailService
   * */
  protected $mailService;


  public function __construct(PsrLogger             $logger,
                              MailService           $mailService,
                              MailingDBService      $mailingDBService,
                              UniteLocaleDBService  $uniteLocaleDBService,
                              $appSettings)
  {

    $this->logger               = $logger;
    $this->appSettings          = $appSettings;
    $this->mailService          = $mailService;
    $this->mailingDBService     = $mailingDBService;
    $this->uniteLocaleDBService = $uniteLocaleDBService;
  }


  /**
   * Send an email to allow the user to reset its password (or create the password for the first connexion)
   * @param QueteurEntity $queteur    The information of the user
   * @param string        $uuid       The uuid to be inserted in the email
   * @param bool          $firstInit  If it's the first init, the TTL of the link is 48h, otherwise 4h
   * @throws \Exception   if the email fails to be sent
   */
  public function sendInitEmail(QueteurEntity $queteur, string $uuid, bool $firstInit = false)
  {
    $this->logger->info("sendInitEmail:'".$queteur->email."'");

    $url        = $this->appSettings['appUrl'].$this->appSettings['resetPwdPath'].$uuid;

    $startValidityDateCarbon = new Carbon();
    $startValidityDateString = $startValidityDateCarbon->setTimezone("Europe/Paris")->format('d/m/Y à H:i:s');

    $uniteLocaleEntity = $this->uniteLocaleDBService->getUniteLocaleById($queteur->ul_id);

    if($firstInit)
      $mailTTL = "48 heures";
    else
      $mailTTL = "4  heures";

    $title = "Réinitialisation de votre mot de passe";

    $this->mailService->sendMail(
      "RedCrossQuest",
      "sendInitEmail",
      "[".$queteur->nivol."] $title",
      $queteur->email,
      $queteur->first_name,
      $queteur->last_name,
      $this->getMailHeader($title, $queteur->first_name).
      "
<br/>
 Cet email fait suite à votre demande de réinitialisation de mot de passe pour l'application RedCrossQuest.<br/>
 Votre login est votre NIVOL : <b>'".$queteur->nivol."'</b><br/>
 Si vous n'êtes pas à l'origine de cette demande, ignorer cet email.<br/>
<br/> 
 Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :<br/>
<br/> 
 <a href='".$url."' target='_blank'>".$url."</a><br/>
 Ce lien est valide $mailTTL à compter du : ".$startValidityDateString."
<br/> 
<br/>".$this->getMailFooter($uniteLocaleEntity, false, $queteur));

  }


  /**
   *
   * Send a confirmation email to the user after password changed successfully
   *
   * @param QueteurEntity $queteur information about the user
   *
   * @throws \Exception if the mail fails to be sent
   *
   */
  public function sendResetPasswordEmailConfirmation(QueteurEntity $queteur)
  {
    $this->logger->info("sendResetPasswordEmailConfirmation:'".$queteur->email."'");

    $url=$this->appSettings['appUrl'];

    $uniteLocaleEntity = $this->uniteLocaleDBService->getUniteLocaleById($queteur->ul_id);

    $changePasswordDate = new Carbon();
    $changePasswordDateString = $changePasswordDate->setTimezone("Europe/Paris")->format('d/m/Y à H:i:s');

    $title="Votre mot de passe a été changé";

    $this->mailService->sendMail(
      "RedCrossQuest",
      "sendResetPasswordEmailConfirmation",
      "[".$queteur->nivol."] $title",
      $queteur->email,
      $queteur->first_name,
      $queteur->last_name,
      $this->getMailHeader($title, $queteur->first_name).
      "
<br/>
 Cet email confirme le changement de votre mot de passe pour l'application RedCrossQuest le $changePasswordDateString.<br/>
 Votre login est votre NIVOL : '".$queteur->nivol."'
 Si vous n'êtes pas à l'origine de cette demande, contactez votre cadre local ou départementale.<br/>
<br/> 
 Vous pouvez maintenant vous connecter à RedCrossQuest avec votre nouveau mot de passe :<br/>
<br/> 
 <a href='".$url."' target='_blank'>".$url."</a><br/>
<br/> 
".$this->getMailFooter($uniteLocaleEntity, false, $queteur));

  }


  /**
   * Send an email that inform the queteur its data has been anonymised
   * @param QueteurEntity $queteur  The information of the user
   * @param string        $token     The uuid to be inserted in the email
   * @throws \Exception   if the email fails to be sent
   */
  public function sendAnonymizationEmail(QueteurEntity $queteur, string $token)
  {
    $this->logger->info("sendAnonymizationEmail:'".$queteur->email."'");

    $uniteLocaleEntity = $this->uniteLocaleDBService->getUniteLocaleById($queteur->ul_id);

    $anonymiseDateCarbon = new Carbon();
    $anonymiseDateString = $anonymiseDateCarbon->setTimezone("Europe/Paris")->format('d/m/Y à H:i:s');

    $title = "Suite à votre demande, vos données viennent d'être anonymisées";

    $this->mailService->sendMail(
      "RedCrossQuest",
      "sendAnonymizationEmail",
      $title,
      $queteur->email,
      $queteur->first_name,
      $queteur->last_name,
      $this->getMailHeader($title, $queteur->first_name)."

<p>
 Cet email fait suite à votre demande d'anonymisation de vos données personnelles de l'application RedCrossQuest, 
 l'outil de gestion opérationel de la quête de la Croix Rouge française.
</p>

<p>Tout d'abord, la Croix Rouge française tient à vous remercier pour votre contribution à la quête de la Croix Rouge.<br/>
Vous avez participé au financement des activités de premiers secours et d'actions sociales de l'unité locale de '".$queteur->ul_name."'<br/>
Nous espèrons vous revoir bientôt à la quête ou en tant que bénévole!
</p>

<p>Conformément à votre demande, vos données personnelles ont été remplacées par les valeurs indiquées ci-après :
  <ul>
   <li>Nom: 'Quêteur' </li>
   <li>Prénom: 'Anonimisé'</li>
   <li>Email: ''</li>
   <li>Secteur: 0</li>
   <li>NIVOL: ''</li>
   <li>Mobile: ''</li>
   <li>Date de Naissance: 22/12/1922</li>
   <li>Homme: 0</li> 
   <li>Active: 0</li>
  </ul>
 </p>

<p> 
La date d'anonymisation est le ".$anonymiseDateString." et ce token sont conservé dans notre base de données :
</p>
</p>TOKEN : '$token'</p>
 
<p>
  Si vous revenez preter main forte à l'unité locale de '".$queteur->ul_name."', vous pouvez communiquer ce Token à l'unité locale de '".$queteur->ul_name."'
  Il permettra de retrouver votre fiche anonymisée et de revaloriser votre fiche avec vos données pour une nouvelle participation à la quête!
  Vous retrouver ainsi vos statistiques des années passées.
  (ce token n'est valable que pour l'unité locale de '".$queteur->ul_name."', un nouveau compte sera créé si vous quêter avec une autre unité locale)
</p>
<p>
 Si vous n'êtes pas à l'origine de cette demande, contactez l'unité locale de '".$queteur->ul_name."' et donner leur ce token ainsi que les informations listées plus haut dans cet email pour revaloriser votre fiche.
</p>
".$this->getMailFooter($uniteLocaleEntity, false, $queteur));

  }



  /**
   * Send a batch of X emails to thanks Queteur for their participation
   * @param int $ul_id id of the UL
   * @param UniteLocaleEntity $uniteLocaleEntity s
   * @return MailingInfoEntity[] Mailing information with status
   * @throws \Exception when things goes wrong
   */
  public function sendThanksEmailBatch(int $ul_id, UniteLocaleEntity $uniteLocaleEntity)
  {
    $mailInfoEntity = $this->mailingDBService->getMailingInfo($ul_id, $this->appSettings['email']['thanksMailBatchSize']);

    if($mailInfoEntity != null)
    {
      $count = count($mailInfoEntity);
      for($i=0;$i<$count; $i++)
      {
        $mailInfoEntity[$i] = $this->sendThanksEmail($mailInfoEntity[$i], $uniteLocaleEntity);
      }
    }

    return $mailInfoEntity;
  }
  
  /**
   * Send an email to allow the user to reset its password (or create the password for the first connexion)
   * @param MailingInfoEntity $mailingInfoEntity  Info for the mailing
   * @param UniteLocaleEntity $uniteLocaleEntity  Info about the UL
   * @return MailingInfoEntity updated with token and status
   * @throws \Exception if mailing has an issue
   */
  public function sendThanksEmail(MailingInfoEntity $mailingInfoEntity, UniteLocaleEntity $uniteLocaleEntity)
  {
    //if spotfire_access_token not generated, generate it and store it
    if($mailingInfoEntity->spotfire_access_token == null || strlen($mailingInfoEntity->spotfire_access_token) != 36)
    {
      $mailingInfoEntity->spotfire_access_token = Uuid::uuid4()->toString();
      $this->mailingDBService->updateQueteurWithSpotfireAccessToken($mailingInfoEntity->spotfire_access_token, $mailingInfoEntity->id, $uniteLocaleEntity->id);
    }

    $url        = $this->appSettings['appUrl'].$this->appSettings['graphPath']."?i=".$mailingInfoEntity->spotfire_access_token."&g=".$this->appSettings['queteurDashboard'];

    try
    {

      $title = $mailingInfoEntity->first_name.", Merci pour votre Participation aux Journées Nationales de la Croix Rouge";
      $statusCode = $this->mailService->sendMail(
        "RedCrossQuest",
        "sendAnonymizationEmail",
        $title,
        $mailingInfoEntity->email,
        $mailingInfoEntity->first_name,
        $mailingInfoEntity->last_name,
        $this->getMailHeader($title, $mailingInfoEntity->first_name)."
<br/>
Encore une fois nous tenions à te remercier pour ta participation aux journées nationales ".(new Carbon())->year()." de la Croix-Rouge française !<br/>
<br/>
Nous t'avons préparé un petit résumé de ce que ta participation représente pour l'unité locale de ".$uniteLocaleEntity->name.". <br/>
Tu y trouveras également un message de remerciement de son Président. <br/>
<br/>
Pour cela, il suffit de cliquer sur l'image ci-dessous:<br/>
<a href='$url' target='_blank'>
<img src='https://www.redcrossquest.com/assets/images/RedCrossQuest-Merci.jpg' alt='Cliquez ICI'>
</a><br/>
<small style='color:silver;'>ou recopie l'addresse suivante dans ton navigateur:<br/>
<a href='$url' style='color:grey;'>$url</a>
</small>
<br/>
<br/>
". $this->getMailFooter($uniteLocaleEntity, true, $mailingInfoEntity));


      $mailingInfoEntity->status = $statusCode;
      $this->mailingDBService->insertQueteurMailingStatus($mailingInfoEntity->id, $mailingInfoEntity->status);
    }
    catch(\Exception $e)
    {
      $mailingInfoEntity->status = substr($e->getMessage()."", 0,200);
      $this->mailingDBService->insertQueteurMailingStatus($mailingInfoEntity->id, $mailingInfoEntity->status);

      //Do not rethrow, continue
    }

    return $mailingInfoEntity;
  }

  /**
   * @param string $title the title of the email
   * @param string $bonjour the text that will be displayed after the "Bonjour word
   * @return string return the html of the mail header
   */
  public function getMailHeader(string $title, string $bonjour)
  {
    return "
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"/>
  <title>[RedCrossQuest] $title</title>
  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>
<body>




  <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"500\">
  <tr>
    <td style=\"background-color:#FFFFFF;\">
      <table style=\"width:100%;padding:0; margin:0;\" >
        <tr>
          <td style=\"font-family: Helvetica; font-size: 24px;font-weight: bolder;padding:8px;\">
            <div style='background-color: #222222;'><img src=\"https://".$this->getDeploymentInfo()."redcrossquest.com/assets/images/RedCrossQuestLogo.png\" style=\"height: 50px;\"/></div>
          </td>
          <!-- 
          //TODO 
          https://".$this->getDeploymentInfo()."redcrossquest.com/assets/images/logoCRF.png
          http://mansonthomas.com/CRF/Paris1erEt2eme/logoCRF.png
          -->
          <td style=\"text-align: right;\"><img src=\"http://mansonthomas.com/CRF/Paris1erEt2eme/logoCRF.png\" alt=\"Croix Rouge Française\" style=\"height: 90px;\"/></td>
        </tr>
      </table>
    </td>
  </tr>

  <td style=\"background-color:#e3001b;padding-top:4px;padding-bottom:4px;text-align: center;vertical-align: top;\">
    &nbsp;
  </td>
  <tr>
    <td style=\"padding-top:20px;padding-bottom: 20px;text-align: center;\"><strong style=\"color:#054752;font-family: Arial, sans-serif;text-decoration:none;font-size:20px;line-height:18px;\"
    > $title </strong></td>
  </tr>
  <tr>
    <td style=\"padding:5px;font-family:Arial,sans-serif;color:#202020;font-size:16px;text-align:left;background-color: #ffffff;\">

      <strong>Bonjour $bonjour,</strong>
      <br/>

    
    ";
  }

  /**
   * @param UniteLocaleEntity $uniteLocaleEntity UL info
   * @param bool $isNewsletter : if true, the wording of the footer is slightly different
   * @param mixed $queteurInfo : QueteurEntity or MailingInfoEntity : an object with the info of the queteur.
   * @return string return the html of the mail header
   */
  public function getMailFooter(UniteLocaleEntity $uniteLocaleEntity, bool $isNewsletter, $queteurInfo)
  {
    $startValidityDateCarbon = new Carbon();
    $startValidityDateString = $startValidityDateCarbon->setTimezone("Europe/Paris")->format('d/m/Y à H:i:s');

    $text1 = $isNewsletter ? "ne plus recevoir d'email de la platforme ou à" : "" ;
    $text2 = $isNewsletter ? "Newsletter ou données personnelles" : "Données personnelles" ;
    $text3 = $isNewsletter ? "la newsletter ou " : "" ;

    $emailContact = urlencode($this->getDeploymentType()."
Bonjour la Croix Rouge de ".$uniteLocaleEntity->name.",

J'ai une demande en relation avec $text3 mes données personnelles et l'application RedCrossQuest:
Note: cet email est à transférer au responsable de la quête, au trésorier ou au président de l'UL

------------------
Votre demande ici
------------------

https://".$this->getDeploymentInfo()."redcrossquest.com/#!/queteurs/edit/\".$queteurInfo->id

En vous remerciant,
".$queteurInfo->first_name." ".$queteurInfo->last_name.", 
".$queteurInfo->email.".");



    return "
     <p>
        <span style=\"font-size: 15px;color:grey\">
        Amicalement,<br>
L'Unité Locale de ".$uniteLocaleEntity->name.",<br/>
".$uniteLocaleEntity->phone."<br/>
".$uniteLocaleEntity->email."<br/>
".$uniteLocaleEntity->address.", ".$uniteLocaleEntity->postal_code.", ".$uniteLocaleEntity->city."<br/>
Via l'application RedCrossQuest.
        </span>
      </p>
    </td>
  </tr>
  <tr>
    <td style=\"background-color:azure; color:silver;text-align: justify;\">
Cet email est envoyé depuis la plateforme RedCrossQuest qui permet aux unités locales de gérer les Journées Nationales.<br/>
Vos données ne sont utilisées que pour la gestion des Journées Nationales et ne sont pas partagées avec un tiers.<br/>
Notre politique de protection des données conforme à la RGPD est <a href=\"".$this->appSettings['RGPD']."\" target='_blank' style='color:grey;'>disponible ici</a>.<br/>
Vous pouvez demander à $text1 corriger / anonymiser vos données par email<br/>
<a href=\"mailto:".$uniteLocaleEntity->email."?subject=".$this->getDeploymentType()."[RedCrossQuest]$text2&body=$emailContact\" style='color:grey;'>Contactez votre unité locale ici</a><br/>
<br/>
email envoyé le $startValidityDateString<br/>
    </td>
  </tr>
</table>

</body>
</head>
</html>
";
  }


  /**
   * Return the subdomain for links to RCQ depending on the current environment
   * @return string 'www.' for production, 'dev.' for D, 'test.' for T
   */
  private function getDeploymentInfo()
  {
    $deployment='www.';
    if($this->appSettings['deploymentType'] == 'D')
    {
      $deployment='dev.';
    }
    else if($this->appSettings['deploymentType'] == 'T')
    {
      $deployment='test.';
    }
    return $deployment;
  }

  /**
   * Return a string to be put in the email subjects
   * @return string nothing for production, [Site de DEV] for D, [Site de TEST] for T
   */
  private function getDeploymentType()
  {
    $deployment='';
    if($this->appSettings['deploymentType'] == 'D')
    {
      $deployment='[Site de DEV]';
    }
    else if($this->appSettings['deploymentType'] == 'T')
    {
      $deployment='[Site de TEST]';
    }
    return $deployment;
  }

}
