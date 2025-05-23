<?php
namespace RedCrossQuest\DBService;

use Exception;
use InvalidArgumentException;
use PDOException;
use RedCrossQuest\Entity\PageableRequestEntity;
use RedCrossQuest\Entity\PageableResponseEntity;
use RedCrossQuest\Entity\TroncEntity;

class TroncDBService extends DBService
{

  /**
   * search all tronc, that are enabled, if query is specified, search on the ID
   * @param PageableRequestEntity  $pageableRequestEntity
   *     string  $query    search query
   *     boolean $active   search active or incative troncs, if null, all troncs are returned
   *     int $type  Id of the type of tronc, if null, search all types.
   * @param  int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return PageableResponseEntity The response with the count of rows
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception in other situations, possibly : parsing error in the entity
   *
   */
  //?string $query, ?bool $active, ?int $type
  public function getTroncs(PageableRequestEntity $pageableRequestEntity, int $ulId):PageableResponseEntity
  {
    /** @var string $query */
    $query  = $pageableRequestEntity->filterMap['q'];
    /** @var bool $active */
    $active = $pageableRequestEntity->filterMap['active'];
    /** @var int $type */
    $type   = $pageableRequestEntity->filterMap['type'];

    $parameters = ["ul_id"  => $ulId];
    $sql = "
SELECT `id`,
     `ul_id`,
     `created`,
     `enabled`,
     `notes`,
     `type`
FROM   `tronc` as t
WHERE  t.ul_id = :ul_id
";

    if($active !==   null)
    {
      $parameters[ "enabled"] = $active===true?"1":"0";
      $sql .="
      AND    enabled = :enabled
";
    }

    if($query != null)
    {
      $parameters[ "query"] =$query;
      $sql .="
      AND CONVERT(id, CHAR) like concat(:query,'%')
";
    }

    if( $type != null)
    {
      $parameters[ "type"] =$type;
      $sql .="
      AND `type` = :type
";
    }

    $sql .="
    ORDER BY id ASC
";
    $count   = $this->getCountForSQLQuery ($sql, $parameters);
    $results = $this->executeQueryForArray($sql, $parameters, function($row) {
      return new TroncEntity($row, $this->logger);
    }, $pageableRequestEntity->pageNumber, $pageableRequestEntity->rowsPerPage);

    return new PageableResponseEntity($count, $results, $pageableRequestEntity->pageNumber, $pageableRequestEntity->rowsPerPage);
  }

    
  /**
   * Get one tronc by its ID
   *
   * @param int $tronc_id The ID of the tronc
   * @param int $ulId the ID of the UniteLocal
   * @return TroncEntity|null  The tronc
   * @throws Exception if the tronc is not found
   * @throws PDOException if the query fails to execute on the server
   */
  public function getTroncById(int $tronc_id, int $ulId, int $roleId):?TroncEntity
  {
    $sql = "
SELECT `id`,
     `ul_id`,
     `created`,
     `enabled`,
     `notes`,
     `type`
FROM  `tronc` as t
WHERE  t.id    = :tronc_id

";
    $parameters = ["tronc_id" => $tronc_id];

    if($roleId != 9)
    {
      $sql .= "
AND   `ul_id`           = :ul_id      
";
      $parameters["ul_id"] = $ulId;
    }
    $sql .= "
LIMIT 1
";


    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $this->executeQueryForObject($sql, $parameters, function($row) {
      return new TroncEntity($row, $this->logger);
    }, false);
  }


/**
 * Update one tronc
 *
 * @param TroncEntity $tronc The tronc to update
 * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
 * @throws PDOException if the query fails to execute on the server
 * @throws Exception
 */
  public function update(TroncEntity $tronc, int $ulId):void
  {
    $sql = "
UPDATE `tronc`
SET
    `notes`       = :notes,
    `enabled`     = :enabled,
    `type`        = :type
WHERE `id`          = :id
AND   `ul_id`       = :ul_id
";
    $parameters = [
      "notes"      => $tronc->notes,
      "enabled"    => $tronc->enabled===true?"1":"0",
      "id"         => $tronc->id,
      "type"       => $tronc->type,
      "ul_id"      => $ulId
    ];

    $this->executeQueryForUpdate($sql, $parameters);
  }

  /**
   * Insert one Tronc
   *
   * @param TroncEntity $tronc The tronc to update
   * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception
   */
  public function insert(TroncEntity $tronc, int $ulId):void
  {
    /** @noinspection SyntaxError */
    $sql = "
INSERT INTO `tronc`
(
   `ul_id`,
   `created`,
   `enabled`,
   `notes`,
   `type`
)
VALUES
";
    if($tronc->nombreTronc <= 0  || $tronc->nombreTronc > 50  )
    {
      throw new InvalidArgumentException("Invalid number of tronc to be created ".$tronc->nombreTronc);
    }
    for($i=0 ; $i<$tronc->nombreTronc ; $i++)
    {
      $sql .="(:ul_id, NOW(), :enabled, :notes, :type)".($i<$tronc->nombreTronc-1?",":"");
    }

    $parameters = [
      "ul_id"    => $ulId,
      "enabled"  => $tronc->enabled===true?"1":"0",
      "notes"    => $tronc->notes,
      "type"     => $tronc->type
    ];

    $this->executeQueryForInsert($sql, $parameters, false);
  }

  /**
   * Get the current number of Troncs for the Unite Local
   *
   * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return int the number of troncs
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception
   */
  public function getNumberOfTroncs(int $ulId):int
  {
    $sql="
    SELECT 1
    FROM   tronc
    WHERE  ul_id = :ul_id
    ";
    $parameters = ["ul_id" => $ulId];
    return $this->getCountForSQLQuery($sql, $parameters);
  }


  /**
   * Mark All Troncs as printed
   * @param int $ulId Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception
   */
  public function markAllAsPrinted(int $ulId):void
  {
    $sql = "
UPDATE `tronc`
SET    `qr_code_printed` = 1
WHERE  `ul_id`           = :ul_id
";
    $parameters["ul_id"] = $ulId;

    $this->executeQueryForUpdate($sql, $parameters);
  }


  /**
   * search all tronc, that are available for depart, ie: associated with a troncQueteur for the current year,
   * active (tronc, troncQueteur) with a dateTheorique and no departure date.
   * @param  int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return PageableResponseEntity The response with the count of rows
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception in other situations, possibly : parsing error in the entity
   *
   */
//?string $query, ?bool $active, ?int $type
  public function getTroncsForDepart(int $ulId):PageableResponseEntity
  {
    $parameters = ["ul_id"  => $ulId];
    $sql = "
SELECT t.`id`, tq.`id` as `tronc_queteur_id`, q.`first_name`, q.`last_name`, tq.`depart_theorique`,
       t.`ul_id`, t.`created`, t.`enabled`, t.`notes`, t.`type`
FROM   tronc         as t, 
       tronc_queteur as tq, 
       queteur       as q
where tq.`tronc_id`   = t.`id`
AND   tq.`queteur_id` = q.`id`
AND   t.`enabled`     = true
AND   tq.`deleted`    = false
AND   YEAR(tq.`depart_theorique`) = YEAR(CURRENT_DATE())
and   tq.`depart`     is null
AND   t.`ul_id`       = :ul_id
ORDER BY tq.`depart_theorique` DESC, tq.`id` desc
";

    $count   = $this->getCountForSQLQuery ($sql, $parameters);
    $results = $this->executeQueryForArray($sql, $parameters, function($row) {
      return new TroncEntity($row, $this->logger);
    }, 1, 0);

    return new PageableResponseEntity($count, $results, 1, 0);
  }


  /**
   * search all tronc, that are available for Return, ie: associated with a troncQueteur for the current year,
   * active (tronc, troncQueteur) with depart != null && return == null
   * @param  int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return PageableResponseEntity The response with the count of rows
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception in other situations, possibly : parsing error in the entity
   *
   */
//?string $query, ?bool $active, ?int $type
  public function getTroncsForReturn(int $ulId):PageableResponseEntity
  {
    $parameters = ["ul_id"  => $ulId];
    $sql = "
SELECT t.`id`, tq.`id` as `tronc_queteur_id`, q.`first_name`, q.`last_name`, tq.`depart`,
       t.`ul_id`, t.`created`, t.`enabled`, t.`notes`, t.`type`
FROM   tronc         as t, 
       tronc_queteur as tq, 
       queteur       as q
where tq.`tronc_id`   = t.`id`
AND   tq.`queteur_id` = q.`id`
AND   t.`enabled`     = true
AND   tq.`deleted`    = false
AND   YEAR(tq.`depart_theorique`) = YEAR(CURRENT_DATE())
AND   tq.`depart`     is not null
AND   tq.`retour`     is null
AND   t.`ul_id`       = :ul_id
ORDER BY tq.`depart` DESC, tq.`id` desc
";

    $count   = $this->getCountForSQLQuery ($sql, $parameters);
    $results = $this->executeQueryForArray($sql, $parameters, function($row) {
      return new TroncEntity($row, $this->logger);
    }, 1, 0);

    return new PageableResponseEntity($count, $results, 1, 0);
  }


  /**
   * search all tronc, that are available for depart, ie: associated with a troncQueteur for the current year,
   * active (tronc, troncQueteur) with depart != null && return != null && comptage == null
   * @param  int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return PageableResponseEntity The response with the count of rows
   * @throws PDOException if the query fails to execute on the server
   * @throws Exception in other situations, possibly : parsing error in the entity
   *
   */
//?string $query, ?bool $active, ?int $type
  public function getTroncsForComptage(int $ulId):PageableResponseEntity
  {
    $parameters = ["ul_id"  => $ulId];
    $sql = "
SELECT t.`id`, tq.`id` as `tronc_queteur_id`, q.`first_name`, q.`last_name`, tq.`depart`, tq.`retour`,
       t.`ul_id`, t.`created`, t.`enabled`, t.`notes`, t.`type`
FROM   tronc         as t, 
       tronc_queteur as tq, 
       queteur       as q
where tq.`tronc_id`   = t.`id`
AND   tq.`queteur_id` = q.`id`
AND   t.`enabled`     = true
AND   tq.`deleted`    = false
AND   YEAR(tq.`depart_theorique`) = YEAR(CURRENT_DATE())
AND   tq.`depart`     is not null
AND   tq.`retour`     is not null
AND   tq.`comptage`   is null  
AND   t.`ul_id`       = :ul_id
ORDER BY tq.`retour` DESC, tq.`id` desc
";

    $count   = $this->getCountForSQLQuery ($sql, $parameters);
    $results = $this->executeQueryForArray($sql, $parameters, function($row) {
      return new TroncEntity($row, $this->logger);
    }, 1, 0);

    return new PageableResponseEntity($count, $results, 1, 0);
  }

}
