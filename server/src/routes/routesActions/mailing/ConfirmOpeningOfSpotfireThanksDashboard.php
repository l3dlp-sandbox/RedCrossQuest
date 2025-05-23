<?php




namespace RedCrossQuest\routes\routesActions\mailing;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\DBService\MailingDBService;
use RedCrossQuest\Entity\LoggingEntity;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\ClientInputValidatorSpecs;
use RedCrossQuest\Service\Logger;


class ConfirmOpeningOfSpotfireThanksDashboard extends Action
{
  /**
   * @var MailingDBService               $mailingDBService
   */
  private MailingDBService $mailingDBService;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param MailingDBService $mailingDBService
   */
  public function __construct(LoggerInterface               $logger,
                              ClientInputValidator          $clientInputValidator,
                              MailingDBService               $mailingDBService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->mailingDBService = $mailingDBService;
  }

  /**
   * @return Response
   * @throws Exception
   */
  protected function action(): Response
  {

    $this->validateSentData([
      ClientInputValidatorSpecs::withString("guid"    , $this->args, 36  , true, ClientInputValidator::$UUID_VALIDATION)
    ]);

    $guid  = $this->validatedData["guid"];
    Logger::dataForLogging(new LoggingEntity(null, ["guid"=>$guid]));

    $this->mailingDBService->confirmRead($guid);
    
    return $this->response;
  }
}
