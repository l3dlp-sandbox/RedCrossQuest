<?php
/**
 * Created by IntelliJ IDEA.
 * User: tmanson
 * Date: 06/03/2017
 * Time: 18:35
 */

use \RedCrossQuest\BusinessService\TroncQueteurBusinessService;
use \RedCrossQuest\DBService\TroncQueteurDBService;
use \RedCrossQuest\DBService\QueteurDBService     ;
use \RedCrossQuest\DBService\PointQueteDBService  ;
use \RedCrossQuest\DBService\TroncDBService       ;

use \RedCrossQuest\Entity\TroncQueteurEntity;

include_once("../../src/DBService/TroncQueteurDBService.php");
include_once("../../src/DBService/QueteurDBService.php");
include_once("../../src/DBService/PointQueteDBService.php");
include_once("../../src/DBService/TroncDBService.php");
include_once("../../src/BusinessService/TroncQueteurBusinessService.php");

/********************************* TRONC_QUETEUR ****************************************/

/**
 * Supprime les tronc_queteurs qui implique le tronc ({id}) et qui ont soit la colonne départ ou retour de null.
 */
$app->delete('/{role-id:[2-9]}/ul/{ul-id}/tronc_queteur/{id}', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId    = (int)$args['ul-id'];
    //c'est bien le troncId qu'on passe ici, on va supprimer tout les tronc_queteur qui ont ce tronc_id et départ ou retour à nulle
    $troncId = (int)$args['id'];
    $userId  = (int)$decodedToken->getUid ();

    $troncQueteurDBService = new TroncQueteurDBService($this->db, $this->logger);
    $troncQueteurDBService->deleteNonReturnedTroncQueteur($troncId, $ulId, $userId);
  }
  catch(\Exception $e)
  {
    $this->logger->addError("unexpected exception during deletion of tronc $troncId, ul: $ulId, user: $userId", array("Exception"=>$e));
    throw $e;
  }

});


/**
 * update troncs
 *
 */
$app->post('/{role-id:[2-9]}/ul/{ul-id}/tronc_queteur/{id}', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId         = (int)$args['ul-id'];
    $params       = $request->getQueryParams();
    $userId       = (int)$decodedToken->getUid ();

    $troncQueteurDBService = new TroncQueteurDBService($this->db, $this->logger);

    $adminMode = false;
    if(array_key_exists('adminMode', $params))
    {//in order to not overwrite the comptage date in the DB
      $adminMode = $params['adminMode'];
      $this->logger->addDebug("adminMode parameter exist", array('decodedToken'=>$decodedToken));
    }

    if(array_key_exists('action', $params))
    {
      $action = $params['action'];
      $input  = $request->getParsedBody();

      $tq = new TroncQueteurEntity($input, $this->logger);

      if ($action =="saveReturnDate")
      {
        // if the depart Date was missing, we make mandatory for the user to fill one.
        if(array_key_exists('dateDepartIsMissing', $params) && $params['dateDepartIsMissing'] == true)
        {
          $this->logger->addInfo("Setting date depart that was missing for tronc_queteur", array("id"=>$tq->id, "depart" => $tq->depart));
          $troncQueteurDBService->setDepartToCustomDate($tq, $ulId, $userId);
        }

        $troncQueteurDBService->updateRetour($tq, $ulId, $userId);
      }
      elseif ($action =="saveCoins")
      {
        //$this->logger->debug("Saving Coins", array('tronc_queteur'=>$tq, 'adminMode'=>$adminMode,'decodedToken'=>$decodedToken));
        $troncQueteurDBService->updateCoinsCount($tq, $adminMode, $ulId, $userId);
      }
      elseif ($action =="saveCreditCard")
      {
        //$this->logger->debug("Saving CreditCard", array('tronc_queteur'=>$tq, 'adminMode'=>$adminMode,'decodedToken'=>$decodedToken));
        $troncQueteurDBService->updateCreditCardCount($tq, $adminMode, $ulId, $userId);
      }
      elseif ($action =="saveAsAdmin")
      {
        //$this->logger->debug("Saving As Admin", array('tronc_queteur'=>$tq, 'adminMode'=>$adminMode,'decodedToken'=>$decodedToken));
        $troncQueteurDBService->updateTroncQueteurAsAdmin($tq, $ulId, $userId);
      }
    }
  }
  catch(\Exception $e)
  {
    $this->logger->addError("Error while updating tronc_queteur", array('decodedToken'=>$decodedToken, "Exception"=>$e, "tronc_queteur" => $tq));
    throw $e;
  }


});


/**
 * créer un départ théorique de tronc (insertion du tronc_queteur) (id_queteur, id_tronc, départ_théorique, point_quete)
 *
 * ou met à jour le tronc avec la date de départ. Si la date de départ est déjà mise à jour,
 * departAlreadyRegistered=true est initialisé dans tronc_queteur qui est retourné
 *
 * autoriser pour role >2
 *
 */
$app->post('/{role-id:[2-9]}/ul/{ul-id}/tronc_queteur', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId         = (int)$args['ul-id'];
    $params       = $request->getQueryParams();
    $userId       = (int)$decodedToken->getUid ();

    $troncQueteurDBService = new TroncQueteurDBService($this->db, $this->logger);

    if(array_key_exists('action', $params))
    {
      $action   = $params['action'  ];
      $tronc_id = $params['tronc_id'];

      $troncQueteurBusinessService = new TroncQueteurBusinessService( $this->logger,
        $troncQueteurDBService,
        new QueteurDBService   ($this->db, $this->logger),
        new PointQueteDBService($this->db, $this->logger),
        new TroncDBService     ($this->db, $this->logger)
      );

      if($action == "getTroncQueteurForTroncIdAndSetDepart")
      {
        $tq = $troncQueteurBusinessService->getLastTroncQueteurFromTroncId($tronc_id, $ulId);

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
            $departDate = $troncQueteurDBService->setDepartToNow($tq->id, $ulId, $userId);
            $tq->depart = $departDate;
          }
          else
          {
            $tq->departAlreadyRegistered=true;
            //$this->logger->warn("TroncQueteur with id='".$troncQueteur->id."' has already a 'depart' defined('".$troncQueteur->depart."'), don't update it", array('decodedToken'=>$decodedToken));
          }
        }
        $response->getBody()->write(json_encode($tq));
        return $response;
      }
      else
      {
        throw new \Exception("Unknown action" );
      }

    }
    else
    { // préparation du tronc

      $input  = $request->getParsedBody();
      $tq = new TroncQueteurEntity($input, $this->logger);

      $troncQueteurDBService->insert($tq, $ulId, $userId);

      return $response;
    }

  }
  catch(\Exception $e)
  {
    $this->logger->addError("Error while creating tronc_queteur or updating departure date", array('decodedToken'=>$decodedToken, "Exception"=>$e, "tronc_queteur" => $tq));
    throw $e;
  }

});

/**
 * Recherche un tronc_queteur
 */
$app->get('/{role-id:[1-9]}/ul/{ul-id}/tronc_queteur', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId = (int)$args['ul-id'];

    $params = $request->getQueryParams();

    if(array_key_exists('action', $params))
    {
      $action   = $params['action'  ];
      $troncQueteurDBService = new TroncQueteurDBService($this->db, $this->logger);
      $troncQueteurBusinessService = new TroncQueteurBusinessService(
        $this->logger                                      ,
        $troncQueteurDBService                             ,
        new QueteurDBService     ($this->db, $this->logger),
        new PointQueteDBService  ($this->db, $this->logger),
        new TroncDBService       ($this->db, $this->logger)
      );

      if($action == "getLastTroncQueteurFromTroncId")
      {
        //$this->logger->debug("action='getLastTroncQueteurFromTroncId'", array('decodedToken'=>$decodedToken));
        $tronc_id     = $params['tronc_id'];
        $troncQueteur = $troncQueteurBusinessService->getLastTroncQueteurFromTroncId($tronc_id, $ulId);
        $response->getBody()->write(json_encode($troncQueteur, JSON_NUMERIC_CHECK));
        return $response;
      }
      else if($action == "getTroncsQueteurForTroncId")
      {
        //$this->logger->debug("action='getTroncsQueteurForTroncId'", array('decodedToken'=>$decodedToken));
        $tronc_id     = $params['tronc_id'];
        $troncQueteur = $troncQueteurDBService->getTroncsQueteurByTroncId($tronc_id, $ulId);
        $response->getBody()->write(json_encode($troncQueteur, JSON_NUMERIC_CHECK));
        return $response;
      }
      else if($action == "getTroncsOfQueteur")
      {
        //$this->logger->debug("action='getTroncsOfQueteur'", array('decodedToken'=>$decodedToken));
        $queteur_id             = $params['queteur_id'];
        $troncQueteurDBService  = new TroncQueteurDBService($this->db, $this->logger);
        $troncsQueteur          = $troncQueteurDBService->getTroncsQueteur($queteur_id, $ulId);

        $response->getBody()->write(json_encode($troncsQueteur, JSON_NUMERIC_CHECK));

        return $response;
      }
    }

  }
  catch(\Exception $e)
  {
    $this->logger->addError("Error while searching for tronc_queteur of tronc_id=$tronc_id or queteur_id=$queteur_id", array('decodedToken'=>$decodedToken, "Exception"=>$e, "tronc_queteur" => $troncQueteur));
    throw $e;
  }


  return $response;
});


/**
 * récupère un tronc_queteur par son id
 *
 */
$app->get('/{role-id:[1-9]}/ul/{ul-id}/tronc_queteur/{id}', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId           = (int)$args['ul-id'];
    $troncQueteurId = (int)$args['id'];
    //$this->logger->debug("Getting tronc_queteur with id '".$troncQueteurId."'", array('decodedToken'=>$decodedToken));

    $troncQueteurAction = new TroncQueteurBusinessService($this->logger,
      new TroncQueteurDBService($this->db, $this->logger),
      new QueteurDBService     ($this->db, $this->logger),
      new PointQueteDBService  ($this->db, $this->logger),
      new TroncDBService       ($this->db, $this->logger));

    $troncQueteur = $troncQueteurAction->getTroncQueteurFromTroncQueteurId($troncQueteurId, $ulId);

    $response->getBody()->write(json_encode($troncQueteur, JSON_NUMERIC_CHECK));

    return $response;
  }
  catch(\Exception $e)
  {
    $this->logger->addError("error while fetching tronc_queteur with id $troncQueteurId", array('decodedToken'=>$decodedToken, "Exception"=>$e));
    throw $e;
  }
});





