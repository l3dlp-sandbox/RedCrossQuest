<?php
namespace RedCrossQuest\routes\routesActions\troncsQueteurs;

use Carbon\Carbon;
use DI\Attribute\Inject;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\BusinessService\TroncQueteurBusinessService;
use RedCrossQuest\DBService\TroncQueteurDBService;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\ClientInputValidatorSpecs;

class GetAndSetDepartOnTroncQueteur extends Action
{
  /**
   * @var TroncQueteurDBService          $troncQueteurDBService
   */
  private $troncQueteurDBService;

  /**
   * @var TroncQueteurBusinessService $troncQueteurBusinessService
   */
  private $troncQueteurBusinessService;


  /**
   * @var array settings
   */
  #[Inject("settings")]
  protected $settings;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param TroncQueteurDBService $troncQueteurDBService
   * @param TroncQueteurBusinessService $troncQueteurBusinessService
   */
  public function __construct(LoggerInterface             $logger,
                              ClientInputValidator        $clientInputValidator,
                              TroncQueteurDBService       $troncQueteurDBService,
                              TroncQueteurBusinessService $troncQueteurBusinessService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->troncQueteurDBService       = $troncQueteurDBService;
    $this->troncQueteurBusinessService = $troncQueteurBusinessService;

  }

  /**
   * @return Response
   * @throws Exception
   */
  protected function action(): Response
  {
    $this->validateSentData(
      [
        ClientInputValidatorSpecs::withInteger('tronc_id', $this->queryParams, 1000000, true)
      ]);

    //c'est bien le troncId qu'on passe ici, on va supprimer tout les tronc_queteur qui ont ce tronc_id et départ ou retour à nulle
    $tronc_id           = $this->validatedData["tronc_id"];

    $ulId      = $this->decodedToken->getUlId       ();
    $userId    = $this->decodedToken->getUid        ();
    $roleId    = $this->decodedToken->getRoleId     ();

    $tq = $this->troncQueteurBusinessService->getLastTroncQueteurFromTroncId($tronc_id, $ulId, $roleId);

    if($tq == null)
    {
      $response404 = $this->response->withStatus(404);
      $response404->getBody()->write(json_encode(["error" =>"Le tronc n'as pas été préparé ! Il faut faire une préparation avant d'enregistrer le départ!"]));

      return $response404;
    }

    if($tq->depart_theorique->year != (Carbon::now())->year)
    {
      $tq->troncFromPreviousYear=true;
    }
    else
    {
      // check if the tronc_queteur is in the correct state
      // Sometime the preparation is not done, so we fetch a previous tronc_queteur fully filled (return date, coins & bills data)
      if($tq->retour !== null)
      {
        $tq->troncQueteurIsInAnIncorrectState=true;
      }
      else
      {
        if($tq->depart == null)
        {
          if(!$this->troncQueteurBusinessService->hasQueteAlreadyStarted($this->settings['appSettings']['deploymentType'], null))
          {//enforce policy :  can't prepare or depart tronc before the start of the quête
            $tq->queteHasNotStartedYet=true;
          }
          else
          {
            $departDate = $this->troncQueteurDBService->setDepartToNow($tq->id, $ulId, $userId);
            $tq->depart = $departDate;
          }
        }
        else
        {
          $tq->departAlreadyRegistered=true;
          //$this->get(LoggerInterface::class)->warning("TroncQueteur with id='".$troncQueteur->id."' has already a 'depart' defined('".$troncQueteur->depart."'), don't update it", array('decodedToken'=>$decodedToken));
        }
      }
    }

    $this->response->getBody()->write(json_encode($tq));


    return $this->response;
  }
}
