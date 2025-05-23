<?php /** @noinspection SpellCheckingInspection */


namespace RedCrossQuest\routes\routesActions\queteurs;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use RedCrossQuest\BusinessService\EmailBusinessService;
use RedCrossQuest\BusinessService\ExportDataBusinessService;
use RedCrossQuest\DBService\QueteurDBService;
use RedCrossQuest\routes\routesActions\Action;
use RedCrossQuest\Service\ClientInputValidator;
use RedCrossQuest\Service\ClientInputValidatorSpecs;


class ExportQueteurData extends Action
{
  /**
   * @var QueteurDBService              $queteurDBService
   */
  private QueteurDBService $queteurDBService;

  /**
   * @var ExportDataBusinessService     $exportDataBusinessService
   */
  private ExportDataBusinessService $exportDataBusinessService;

  /**
   * @var EmailBusinessService          $emailBusinessService
   */
  private EmailBusinessService $emailBusinessService;

  /**
   * @param LoggerInterface $logger
   * @param ClientInputValidator $clientInputValidator
   * @param QueteurDBService $queteurDBService
   * @param ExportDataBusinessService $exportDataBusinessService
   * @param EmailBusinessService $emailBusinessService
   */
  public function __construct(LoggerInterface               $logger,
                              ClientInputValidator          $clientInputValidator,
                              QueteurDBService              $queteurDBService,
                              ExportDataBusinessService     $exportDataBusinessService,
                              EmailBusinessService          $emailBusinessService)
  {
    parent::__construct($logger, $clientInputValidator);
    $this->queteurDBService           = $queteurDBService;
    $this->exportDataBusinessService  = $exportDataBusinessService;
    $this->emailBusinessService       = $emailBusinessService;
  }

  /**
   * @return Response
   * @throws Exception
   */
  protected function action(): Response
  {
    $ulId   = $this->decodedToken->getUlId  ();
    $roleId = $this->decodedToken->getRoleId();

    $this->validateSentData([
      ClientInputValidatorSpecs::withInteger("id", $this->args, 1000000 , false, 0)
    ]);

    $queteurId  = $this->validatedData["id"];

    $queteurEntity = $this->queteurDBService         ->getQueteurById   ($queteurId, $roleId == 9? null : $ulId);
    $exportReport  = $this->exportDataBusinessService->exportDataQueteur($queteurId,  $roleId == 9? $queteurEntity->ul_id : $ulId);

    $status = $this->emailBusinessService->sendExportDataQueteur($queteurEntity, $exportReport['fileName']);

    $this->response->getBody()->write(json_encode(new ExportDataQueteurResponse($status, $queteurEntity->email, $exportReport['fileName'],$exportReport['numberOfRows'])));

    /*  envoie bien le fichier comme il faut, mais ne fonctionne pas en rest

    $fh = fopen("/tmp/".$zipFileName, 'r  ');
    $stream = new \Slim\Http\Stream($fh);
    return $response->withHeader('Content-Type', 'application/force-download')
      ->withHeader('Content-Type', 'application/octet-stream')
      ->withHeader('Content-Type', 'application/download')
      ->withHeader('Content-Description', 'File Transfer')
      ->withHeader('Content-Transfer-Encoding', 'binary')
      ->withHeader('Content-Disposition', 'attachment; filename="' .$zipFileName . '"')
      ->withHeader('Expires', '0')
      ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
      ->withHeader('Pragma', 'public')
      ->withBody($stream);
      */



    return $this->response;
  }
}
