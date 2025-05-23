<?php

namespace RedCrossQuest\BusinessService;


use Exception;
use Psr\Log\LoggerInterface;
use RedCrossQuest\DBService\DailyStatsBeforeRCQDBService;
use RedCrossQuest\DBService\PointQueteDBService;
use RedCrossQuest\DBService\QueteurDBService;
use RedCrossQuest\DBService\TroncDBService;
use RedCrossQuest\DBService\UserDBService;
use RedCrossQuest\routes\routesActions\settings\GetULSetupStatusResponse;

class SettingsBusinessService
{
  /** @var LoggerInterface */
  protected LoggerInterface $logger;
  /** @var QueteurDBService */
  protected QueteurDBService $queteurDBService;
  /** @var PointQueteDBService */
  protected PointQueteDBService $pointQueteDBService;
  /** @var UserDBService */
  protected UserDBService $userDBService;
  /** @var DailyStatsBeforeRCQDBService */
  protected DailyStatsBeforeRCQDBService $dailyStatsBeforeRCQDBService;
  /** @var TroncDBService */
  protected TroncDBService $troncDBService;

  public function __construct(LoggerInterface $logger, QueteurDBService $queteurDBService,
                              UserDBService $userDBService, PointQueteDBService $pointQueteDBService,
                              DailyStatsBeforeRCQDBService $dailyStatsBeforeRCQDBService, TroncDBService $troncDBService)
  {
    $this->logger                       = $logger                      ;
    $this->queteurDBService             = $queteurDBService            ;
    $this->userDBService                = $userDBService               ;
    $this->pointQueteDBService          = $pointQueteDBService         ;
    $this->dailyStatsBeforeRCQDBService = $dailyStatsBeforeRCQDBService;
    $this->troncDBService               = $troncDBService              ;
  }


  /**
   * Fetch data about the setup :
   *  Number of queteurs
   *  Number of users
   *  Number of PointQuete
   *  => if 0, it creates the Base one with the data contained in UL table
   * @param integer $ulId  The ID of the Unité Locale
   * @return GetULSetupStatusResponse Setup info
   * @throws Exception   if something wrong happen
   */
  public function getSetupStatus(int $ulId):GetULSetupStatusResponse
  {

    $setupStatus = new GetULSetupStatusResponse();

    $setupStatus->numberOfQueteur    = $this->queteurDBService            ->getNumberOfQueteur    ($ulId);
    $setupStatus->numberOfUser       = $this->userDBService               ->getNumberOfUser       ($ulId);
    $setupStatus->numberOfPointQuete = $this->pointQueteDBService         ->getNumberOfPointQuete ($ulId);
    $setupStatus->numberOfDailyStats = $this->dailyStatsBeforeRCQDBService->getNumberOfDailyStats ($ulId);
    $setupStatus->numberOfTroncs     = $this->troncDBService              ->getNumberOfTroncs     ($ulId);

    if($setupStatus->numberOfQueteur          <=10)
    {
      $setupStatus ->queteurIncomplete       = true;
    }
    if($setupStatus->numberOfUser             == 1)
    {
      $setupStatus ->userIncomplete          = true;
    }
    if($setupStatus->numberOfPointQuete       <=10)
    {
      $setupStatus ->pointQueteIncomplete    = true;
    }
    if($setupStatus->numberOfDailyStats       <=17)
    {
      $setupStatus ->dailyStatsIncomplete    =true;
    }
    if($setupStatus->numberOfTroncs           <=5)
    {
      $setupStatus ->troncsIncomplete        =true;
    }
    if($setupStatus->numberOfPointQuete       == 0)
    {
      $this->pointQueteDBService->initBasePointQuete($ulId);
      $setupStatus ->BasePointQueteCreated    = 1;
    }

  return $setupStatus;
  }

}
