<?php
/**
 * Created by IntelliJ IDEA.
 * User: tmanson
 * Date: 06/03/2017
 * Time: 18:38
 */

use \RedCrossQuest\DBService\SpotfireAccessDBService;


$app->post('/{role-id:[4-9]}/ul/{ul-id}/graphs', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId   = (int)$decodedToken->getUlId();
    $userId = (int)$decodedToken->getUid ();

    $spotfireDBService  = new SpotfireAccessDBService($this->db, $this->logger);

    $insertDateTimeAndToken = $spotfireDBService->grantAccess($userId, $ulId, 4);

    $response->getBody()->write(json_encode($insertDateTimeAndToken));

  }
  catch(Exception $e)
  {
    $this->logger->addError($e);
  }
  return $response;
});


/*
 Documentation :



Au click sur un graph Spotfire, l’application insert une ligne dans la table spotfire_access.
Avec un token, une date d’expiration, l’UL_ID répliqué eviter des jointures inutiles, et le user_id (jointure vers table user pour checker s’il est actif et récupérer son role)

$spotfire_access_table = $this->table('spotfire_access');
$spotfire_access_table
  ->addColumn('token'           , 'string', array('limit' => 36))
  ->addColumn('token_expiration', 'datetime')
  ->addColumn('ul_id'           , 'integer')
  ->addColumn('user_id'         , 'integer')

  ->addForeignKey('ul_id'  , 'ul'   , 'id')
  ->addForeignKey('user_id', 'users', 'id')
  ->create();



Spotfire est mise à jour toutes les 2 minutes, minutes paires.
L’application RCQ attends que la prochaine minutes paires soit passée, puis ouvre le graph spotfire avec le token en parametre.
Le calcul de la colonne show=true se fait avec les critères suivant:
* Token trouvé dans la table spotfire_access
* La date courante est avant la date d’expiration
* l’utilisateur est enabled
* le role_id est supérieur parametre du dashboard.


 * */