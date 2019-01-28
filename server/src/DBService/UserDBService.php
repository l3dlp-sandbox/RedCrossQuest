<?php
namespace RedCrossQuest\DBService;

require '../../vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use RedCrossQuest\Entity\UserEntity;
use PDOException;

class UserDBService extends DBService
{


  /**
   * Insert one user for a queteur.
   *
   * @param string $nivol : Nivol of the user
   * @param int    $queteurId : queteurId of the user
   * @return int the primary key of the new user
   * @throws PDOException if the query fails to execute on the server
   */
  public function insert(string $nivol, int $queteurId)
  {
    $sql = "
INSERT INTO `users`
(
`nivol`,
`queteur_id`,
`password`,
`role`,
`created`,
`updated`,
`active`,
`last_failure_login_date`,
`nb_of_failure`,
`last_successful_login_date`,
`init_passwd_uuid`,
`init_passwd_date`)
VALUES
(
:nivol,
:queteur_id,
'',
1,
NOW(),
NOW(),
1,
NULL,
0,
NULL,
NULL,
NULL
)
";

    $stmt = $this->db->prepare($sql);

    $this->db->beginTransaction();
    $result = $stmt->execute([
      "nivol"       => ltrim($nivol, '0'),
      "queteur_id"  => $queteurId
    ]);

    $stmt->closeCursor();

    $stmt = $this->db->query("select last_insert_id()");
    $row = $stmt->fetch();

    $lastInsertId = $row['last_insert_id()'];
    //$this->logger->info('$lastInsertId:', [$lastInsertId]);

    $stmt->closeCursor();
    $this->db->commit();
    return $lastInsertId;
  }







  /***
   * This function is used by the authenticate method, to get the user info from its nivol.
   * Can't be restricted by ULID since the UL is not known.
   *
   * @param string $nivol string The Nivol passed at login
   * @return UserEntity An instance of UserEntity, null if nothing is found
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function getUserInfoWithNivol(string $nivol)
  {
    $sql = "
SELECT id, queteur_id, password, role, nb_of_failure, last_failure_login_date, last_successful_login_date 
FROM   users
WHERE  upper(nivol) = upper(?)
AND    active = 1
LIMIT 1
";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$nivol]);

    $count = $stmt->rowCount();

    if($count == 1)
    {
      $result = new UserEntity($stmt->fetch(), $this->logger);
      $stmt->closeCursor();
      return $result;
    }
    else
    {
      $stmt->closeCursor();
      throw new \Exception ("Update didn't update the correct number of rows($count) for nivol: $nivol");
    }
  }



  /***
   * This function is used by dataExport
   *
   * @param int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return UserEntity[] array of users of  the UnitéLocale
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function getULUsers(int $ulId)
  {

    $sql = "
SELECT u.id, u.queteur_id, LENGTH(u.password) >1 as password_defined, u.role, 
       u.nb_of_failure, u.last_failure_login_date, u.last_successful_login_date,
       u.init_passwd_date, u.active, u.created, u.updated, q.first_name, q.last_name
FROM   users u, queteur q
WHERE  u.queteur_id = q.id
AND    q.ul_id      = :ul_id

LIMIT 1
";

    $parameters = ["ul_id"=>$ulId];

    $stmt = $this->db->prepare($sql);
    $stmt->execute($parameters);

    $results = [];
    $i = 0;
    while ($row = $stmt->fetch())
    {
      $results[$i++] = new UserEntity($row, $this->logger);
    }

    $stmt->closeCursor();

    return $results;

  }


  /***
   * This function is used by queteurEditForm, where the info from the user is retrieved from the queteurId
   *
   * @param int $queteurId the Id of the queteur from which we want to retrieve the user
   * @param int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @param int $roleId the roleId of the connected user, to override UL Limitation for superadmin
   * @return UserEntity an instance of UserEntity, null if nothing is found
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function getUserInfoWithQueteurId(int $queteurId, int $ulId, int $roleId)
  {
    $limitQueryToUl="";
    if($roleId!=9)
    {
      $limitQueryToUl="AND    q.ul_id      = :ul_id";
    }

    $sql = "
SELECT u.id, u.queteur_id, LENGTH(u.password) >1 as password_defined, u.role, 
       u.nb_of_failure, u.last_failure_login_date, u.last_successful_login_date,
       u.init_passwd_date, u.active, u.created, u.updated, q.first_name, q.last_name
FROM   users u, queteur q
WHERE  u.queteur_id = :queteur_id
AND    q.id         = u.queteur_id
$limitQueryToUl
LIMIT 1
";

    $parameters = ["queteur_id"=>$queteurId];

    if($roleId!=9)
    {
      $parameters["ul_id"]=$ulId;
    }


    $stmt = $this->db->prepare($sql);
    $stmt->execute($parameters);

    $count = $stmt->rowCount();

    if($count == 1)
    {
      $result = new UserEntity($stmt->fetch(), $this->logger);
      $stmt->closeCursor();
      return $result;
    }
    return null;

  }


  /***
   * This function is used by queteurEditForm, where the info from the user is retrieved from the queteurId
   * Can't fetch data from superuser
   *
   * @param int $userId the Id of the user
   * @param int $ulId  Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @param int $roleId the roleId of the connected user, to override UL Limitation for superadmin
   * @return UserEntity an instance of UserEntity, null if nothing is found
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function getUserInfoWithUserId(int $userId, int $ulId, int $roleId)
  {
    $limitQueryToUl="";
    if($roleId!=9)
    {
      $limitQueryToUl="AND    q.ul_id      = :ul_id";
    }

    $sql = "
SELECT u.id, u.queteur_id, LENGTH(u.password) >1 as password_defined, u.role, 
       u.nb_of_failure, u.last_failure_login_date, u.last_successful_login_date,
       u.init_passwd_date, u.active, u.created, u.updated, q.first_name, q.last_name
FROM   users u, queteur q
WHERE  u.id = :id
AND    q.id = u.queteur_id
$limitQueryToUl
LIMIT 1
";

    $parameters = ["id"    => $userId];

    if($roleId!=9)
    {
      $parameters["ul_id"]= $ulId;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($parameters);

    //$this->logger->info( "queryResult=$queryResult, queteurId=$userId, ulId=$ulId, roleId=$roleId, count=".$stmt->rowCount());

    $count = $stmt->rowCount();

    if($count == 1)
    {
      $result = new UserEntity($stmt->fetch(), $this->logger);
      $stmt->closeCursor();
      return $result;
    }

    return null;

  }


  /**
   * used in Password reset process
   * get user info for UUID if the init_passwd_date is after current time.
   * @param string $uuid the UUID to retrieve the user info
   * @return USerEntity the info of the user
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function getUserInfoWithUUID(string $uuid)
  {
    $sql = "
SELECT id, queteur_id, password, role, nb_of_failure, last_failure_login_date, last_successful_login_date 
FROM   users
WHERE  upper(init_passwd_uuid) = upper(:uuid)
AND    init_passwd_date > NOW()
AND    active = 1
AND    role  != 9
LIMIT 1
";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(["uuid" => $uuid]);

    //$this->logger->info( "queryResult=$queryResult, $uuid, ".$stmt->rowCount());

    $count = $stmt->rowCount();

    if($count == 1)
    {
      $result = new UserEntity($stmt->fetch(), $this->logger);
      $stmt->closeCursor();
      return $result;
    }
    else
    {
      $stmt->closeCursor();
      throw new \Exception ("Update didn't update the correct number of rows($count) for $uuid");
    }

  }


  /**
   * update the user with the init uuid (generated buy this method) and the time until the uuid is valid (now+one hour)
   *
   * @param string $username the nivol of the user who want to init its password
   * @return string the generated uuid
   * @throws \Exception in case of incorrect number of rows updated
   * @throws PDOException if the query fails to execute on the server
   */
  public function sendInit(string $username)
  {
    $uuid = Uuid::uuid4();

    $sql = "
UPDATE  `users`
SET     init_passwd_uuid  = :uuid,
        init_passwd_date  = DATE_ADD(NOW(), INTERVAL 1 HOUR)
WHERE   nivol             = :nivol
AND     active            = 1
AND     role             != 9
";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(
      [
        "nivol" => $username,
        "uuid"  => $uuid
      ]
    );

    $count = $stmt->rowCount();
    $stmt->closeCursor();

    if($count == 1)
    {
      return $uuid;
    }
    else
    {
      throw new \Exception ("Update didn't update the correct number of rows($count) for $username");
    }

  }

  /**
   * Save the new password for a user, from the UUID
   * @param string $uuid the uuid that identifiy the user that update his password
   * @param string $password the new password( clear text), stores it as a hash
   * @return boolean true if the query is successfull, false otherwise
   * @throws PDOException if the query fails to execute on the server
   */
  public function resetPassword(string $uuid, string $password)
  {
    $sql = "
UPDATE  `users`
SET     init_passwd_uuid  =  null,
        init_passwd_date  =  null,
        password          = :password
WHERE   upper(init_passwd_uuid) = upper(:uuid)
AND     init_passwd_date > NOW()
AND     active = 1
AND     role  != 9
";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(
      [
        "uuid"     => $uuid,
        "password" => password_hash($password, PASSWORD_DEFAULT)
      ]
    );

    if($stmt->rowCount() == 1)
    {
      $stmt->closeCursor();
      return true;
    }
    $stmt->closeCursor();
    return false;
  }


  /**
   * update last successful login date and reset the count of failed login
   * @param int $userId the id of the user that is connecting
   * @return boolean true if query successful, false otherwise
   * @throws PDOException if the query fails to execute on the server
   */
  public function registerSuccessfulLogin(int $userId)
  {
    $sql = "
UPDATE  `users`
SET     last_successful_login_date  = NOW(),
        nb_of_failure               = 0
WHERE   id                          = :id
";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(
      [
        "id"     => $userId
      ]
    );

    if($stmt->rowCount() == 1)
    {
      return true;
    }
    return false;
  }

  /**
   * increment the failed login counter and update the last failed login date
   * @param int $userId the id of the user that is connecting
   * @return boolean true if query successful, false otherwise
   * @throws PDOException if the query fails to execute on the server
   */
  public function registerFailedLogin(int $userId)
  {
    $sql = "
UPDATE  `users`
SET     last_failure_login_date  = NOW(),
        nb_of_failure            = nb_of_failure + 1
WHERE   id                       = :id
";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(
      [
        "id"     => $userId
      ]
    );

    if($stmt->rowCount() == 1)
    {
      return true;
    }
    return false;
  }


  /**
   * this method update the 'active' and 'role' column of the user (for non super user)
   * @param UserEntity $userEntity the user info
   * @param int         $ulId   the UL ID  of the person performing the action
   * @param int         $roleId the RoleID of the person performing the action
   * @return boolean true if query is successful, false otherwise
   * @throws PDOException if the query fails to execute on the server
   */
  public function updateActiveAndRole(UserEntity $userEntity, int $ulId, int $roleId)
  {
    $sql = "
UPDATE        `users`    u
  INNER JOIN  `queteur`  q
  ON         u.queteur_id = q.id
SET          u.active     = :active,
             u.role       = :role
WHERE        u.id         = :id
AND          u.role      != 9
";

    $parameters = [
      "id"     => $userEntity->id,
      "active" => $userEntity->active===true?"1":"0",
      "role"   => $userEntity->role
    ];


    if($roleId != 9)
    {//allow super admin to update users from any UL
      $sql .= "
AND     q.ul_id = :ul_id      
";
      $parameters["ul_id"] = $ulId;
    }


    $stmt = $this->db->prepare($sql);
    $stmt->execute($parameters);

    if($stmt->rowCount() == 1)
    {
      return true;
    }
    return false;
  }



  /**
   * Get the current number of Users for the Unite Local
   *
   * @param int           $ulId     Id of the UL of the user (from JWT Token, to be sure not to update other UL data)
   * @return int the number of users
   * @throws PDOException if the query fails to execute on the server
   */
  public function getNumberOfUser(int $ulId)
  {
    $sql="
    SELECT count(1) as cnt
    FROM   users u, queteur q
    WHERE  q.ul_id = :ul_id
    AND    q.id    = u.queteur_id 
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(["ul_id" => $ulId]);
    $row = $stmt->fetch();
    return $row['cnt'];
  }

}
