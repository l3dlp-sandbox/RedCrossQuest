<?php
namespace RedCrossQuest\DBService;

use DateInterval;
use DateTime;
use Exception;
use PDO;
use PDOException;
use RedCrossQuest\Entity\DailyStatsBeforeRCQEntity;
use RedCrossQuest\Service\Logger;

class DailyStatsBeforeRCQDBService extends DBService
{

  private array $queteDates;


  public function __construct(array $queteDates, PDO $db, Logger $logger)
  {
    $this->queteDates = $queteDates;
    parent::__construct($db,$logger);
  }

  /**
   * return the date of the first day of the quete of the current year
   * @return string the date of the first day of the quete with the following format YYYY-MM-DD
   */
  public function getCurrentQueteStartDate():string
  {
    //TODO remove hardcoded value
    return "2024-05-25";// $this->queteDates[date("Y")][0];
  }


  /**
   * Get all stats for UL $ulId and a particular year
   *
   * @param int $ulId The ID of the Unite Locale
   * @param string|null $year The year for which we wants the daily stats
   * @return DailyStatsBeforeRCQEntity[]  The PointQuete
   * @throws Exception if some parsing error occurs
   */
  public function getDailyStats(int $ulId, ?string $year):array
  {

    $parameters = ["ul_id" => $ulId];
    $yearSQL   = "";

    if($year != null)
    {
      $parameters["year"] = $year."%";
      $yearSQL = "AND   d.date  LIKE :year";
    }


    $sql = "
SELECT  d.`id`,
        d.`ul_id`,
        d.`date`,
        d.`amount`,
        d.`nb_benevole`,
        d.`nb_benevole_1j`,
        d.`nb_heure`
FROM `daily_stats_before_rcq` AS d
WHERE d.ul_id = :ul_id
$yearSQL
ORDER BY d.date ASC
";

    return $this->executeQueryForArray($sql, $parameters, function($row) {
      return new DailyStatsBeforeRCQEntity($row, $this->logger);
    });
  }

  /**
   * update a daily stat (ie a particular day of a particular year)
   * @param DailyStatsBeforeRCQEntity $dailyStatsBeforeRCQEntity info about the dailyStats
   * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception
   */
  public function update(DailyStatsBeforeRCQEntity $dailyStatsBeforeRCQEntity, int $ulId):void
  {
    $sql ="
update  `daily_stats_before_rcq`
set     `amount`        = :amount,
        `nb_benevole`   = :nb_benevole,
        `nb_benevole_1j`= :nb_benevole_1j,
        `nb_heure`      = :nb_heure
where   `id`      = :id
AND     `ul_id`   = :ulId   
";
    $parameters = [
      "amount"        => $dailyStatsBeforeRCQEntity->amount,
      "nb_benevole"   => $dailyStatsBeforeRCQEntity->nb_benevole,
      "nb_benevole_1j"=> $dailyStatsBeforeRCQEntity->nb_benevole_1j,
      "nb_heure"      => $dailyStatsBeforeRCQEntity->nb_heure,
      "id"            => intVal($dailyStatsBeforeRCQEntity->id),
      "ulId"          => $ulId
    ];
    
    $this->executeQueryForUpdate($sql, $parameters);
  }

  /**
   * Create a year of daily data
   *
   * @param int    $ulId  Id of the UL for which we create the data
   * @param string $year  year to create
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception if something else fails
   */
  public function createYear(int $ulId, string $year):void
  {
    $sql = "
INSERT INTO `daily_stats_before_rcq`
(
  `ul_id`,
  `date`,
  `amount`
)
VALUES
(
  :ul_id,           
  :date,
  :amount
)
";
    $yearDefinition = $this->queteDates[$year];

    $startDate    = $yearDefinition[0];
    $numberOfDays = $yearDefinition[1];
    $oneDate      = DateTime::createFromFormat("Y-m-d", $startDate);

    $stmt         = $this->db->prepare($sql);

    for($i=0;$i<=$numberOfDays;$i++)
    {
      $stmt->execute([
        "ul_id"         => $ulId,
        "date"          => $oneDate->format("Y-m-d"),
        "amount"        => 0
      ]);

      $oneDate->add(new DateInterval('P1D'));

    }

    $stmt->closeCursor();
  }


  /**
   * Get the current number of DailyStats recorded for the Unite Local
   *
   * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return int the number of dailyStats
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception
   */
  public function getNumberOfDailyStats(int $ulId):int
  {
    //query modified in getCountForSQLQuery
    $sql="
    SELECT 1
    FROM   daily_stats_before_rcq
    WHERE  ul_id = :ul_id
    ";
    return $this->getCountForSQLQuery($sql, ["ul_id" => $ulId]);
  }

}
