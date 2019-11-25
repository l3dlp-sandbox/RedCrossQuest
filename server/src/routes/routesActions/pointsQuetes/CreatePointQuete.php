<?php




namespace RedCrossQuest\routes\routesActions\pointsQuetes;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\DBService\PointQueteDBService;
use RedCrossQuest\Entity\LoggingEntity;
use RedCrossQuest\Entity\PointQueteEntity;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\Logger;


class CreatePointQuete extends Action
{
  /**
   * @var PointQueteDBService     $pointQueteDBService
   */
  private $pointQueteDBService;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param PointQueteDBService     $pointQueteDBService
   */
  public function __construct(LoggerInterface         $logger,
                              ClientInputValidator    $clientInputValidator,
                              PointQueteDBService     $pointQueteDBService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->pointQueteDBService = $pointQueteDBService;

  }

  /**
   * @return Response
   * @throws \Exception
   */
  protected function action(): Response
  {
    Logger::dataForLogging(new LoggingEntity($this->decodedToken));

    $ulId   = $this->decodedToken->getUlId  ();

    $pointQueteEntity    = new PointQueteEntity($this->parsedBody, $this->logger);

    $pointQueteId = $this->pointQueteDBService->insert            ($pointQueteEntity, $ulId);
    $this->response->getBody()->write(json_encode(array('pointQueteId' =>$pointQueteId), JSON_NUMERIC_CHECK));

    return $this->response;
  }
}
