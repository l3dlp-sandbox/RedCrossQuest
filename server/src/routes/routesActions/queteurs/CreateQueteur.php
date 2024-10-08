<?php




namespace RedCrossQuest\routes\routesActions\queteurs;


use DI\Attribute\Inject;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\BusinessService\EmailBusinessService;
use RedCrossQuest\DBService\QueteurDBService;
use RedCrossQuest\Entity\QueteurEntity;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\RedCallService;


class CreateQueteur extends Action
{
  /**
   * @var QueteurDBService          $queteurDBService
   */
  private $queteurDBService;

  /**
   * @var EmailBusinessService    $emailBusinessService
   */
  private $emailBusinessService;
  /**
   * @var array settings
   */
  #[Inject("settings")]
  protected $settings;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param QueteurDBService          $queteurDBService
   * @param EmailBusinessService    $emailBusinessService
   */
  public function __construct(LoggerInterface         $logger,
                              ClientInputValidator    $clientInputValidator,
                              QueteurDBService        $queteurDBService,
                              EmailBusinessService    $emailBusinessService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->queteurDBService     = $queteurDBService;
    $this->emailBusinessService = $emailBusinessService;
  }

  /**
   * @return Response
   * @throws Exception
   */
  protected function action(): Response
  {
    $ulId   = $this->decodedToken->getUlId  ();
    $roleId = $this->decodedToken->getRoleId();
    $userId = $this->decodedToken->getUid   ();

    $queteurEntity = new QueteurEntity($this->parsedBody, $this->logger);

    //restore the leading +
    $queteurEntity->mobile = "+".$queteurEntity->mobile;

    $this->logger->info("queteur creation", array("queteur"=>$queteurEntity));
    $queteurId  = $this->queteurDBService->insert($queteurEntity, $ulId, $roleId, $userId);
    $this->response->getBody()->write(json_encode(new CreateQueteurResponse($queteurId)));

    return $this->response;
  }
}
