<?php




namespace RedCrossQuest\routes\routesActions\namedDonations;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\DBService\NamedDonationDBService;
use RedCrossQuest\Entity\LoggingEntity;
use RedCrossQuest\Entity\NamedDonationEntity;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\Logger;


class CreateNamedDonation extends Action
{
  /**
   * @var NamedDonationDBService        $namedDonationDBService
   */
  private $namedDonationDBService;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param NamedDonationDBService        $namedDonationDBService
   */
  public function __construct(LoggerInterface               $logger,
                              ClientInputValidator          $clientInputValidator,
                              NamedDonationDBService        $namedDonationDBService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->namedDonationDBService = $namedDonationDBService;
  }

  /**
   * @return Response
   * @throws \Exception
   */
  protected function action(): Response
  {
    Logger::dataForLogging(new LoggingEntity($this->decodedToken));

    $ulId   = $this->decodedToken->getUlId();
    $userId = $this->decodedToken->getUid();

    $namedDonationEntity    = new NamedDonationEntity($this->parsedBody, $this->logger);
    $namedDonationId        = $this->namedDonationDBService->insert($namedDonationEntity, $ulId, $userId);
    $this->response->getBody()->write(json_encode(array("namedDonationId"=>$namedDonationId), JSON_NUMERIC_CHECK));

    return $this->response;
  }
}