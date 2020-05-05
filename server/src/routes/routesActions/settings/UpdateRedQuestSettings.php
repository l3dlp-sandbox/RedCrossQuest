<?php




namespace RedCrossQuest\routes\routesActions\settings;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\DBService\ULPreferencesFirestoreDBService;
use RedCrossQuest\DBService\UniteLocaleSettingsDBService;
use RedCrossQuest\Entity\LoggingEntity;
use RedCrossQuest\Entity\ULPreferencesEntity;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\ClientInputValidatorSpecs;
use RedCrossQuest\Service\Logger;


class UpdateRedQuestSettings extends Action
{

  /**
   * @var ULPreferencesFirestoreDBService  $ULPreferencesFirestoreDBService
   */
  private $ULPreferencesFirestoreDBService;



  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param ULPreferencesFirestoreDBService  $ULPreferencesFirestoreDBService
   */
  public function __construct(LoggerInterface                 $logger,
                              ClientInputValidator            $clientInputValidator,
                              ULPreferencesFirestoreDBService $ULPreferencesFirestoreDBService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->ULPreferencesFirestoreDBService = $ULPreferencesFirestoreDBService;

  }

  /**
   * @return Response
   * @throws \Exception
   */
  protected function action(): Response
  {
    $this->logger->debug("parsed body", [$this->parsedBody]);
    $this->validateSentData(
      [
        ClientInputValidatorSpecs::withBoolean("rq_autonomous_depart_and_return" , $this->parsedBody['rq_autonomous_depart_and_return']===true?1:0, true, false),
        ClientInputValidatorSpecs::withBoolean("rq_display_daily_stats"          , $this->parsedBody['rq_display_daily_stats'         ]===true?1:0, true, false),
        ClientInputValidatorSpecs::withString ("rq_display_queteur_ranking"      , $this->parsedBody['rq_display_queteur_ranking'     ], 8,      true),
      ]);





    $ulId   = $this->decodedToken->getUlId();

    //recupère les settings exitants
    $ulPreferenceEntity = $this->ULPreferencesFirestoreDBService ->getULPrefs($ulId);

    if(!$ulPreferenceEntity)
    {//does not exist in firestore
      $data = [];

      $data['rq_autonomous_depart_and_return'] = $this->validatedData["rq_autonomous_depart_and_return"];
      $data['rq_display_daily_stats'         ] = $this->validatedData["rq_display_daily_stats"];
      $data['rq_display_queteur_ranking'     ] = $this->validatedData["rq_display_queteur_ranking"];
      $data['ul_id'                          ] = $ulId;

      $ulPreferenceEntity = ULPreferencesEntity::withArray($data, $this->logger);
    }
    else
    {
      $ulPreferenceEntity->rq_autonomous_depart_and_return = $this->validatedData["rq_autonomous_depart_and_return"];
      $ulPreferenceEntity->rq_display_daily_stats          = $this->validatedData["rq_display_daily_stats"];
      $ulPreferenceEntity->rq_display_queteur_ranking      = $this->validatedData["rq_display_queteur_ranking"];
    }

    $this->ULPreferencesFirestoreDBService ->updateUlPrefs($ulId, $ulPreferenceEntity);



    return $this->response;
  }
}
