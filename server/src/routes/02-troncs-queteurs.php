<?php
/**
 * Created by IntelliJ IDEA.
 * User: tmanson
 * Date: 06/03/2017
 * Time: 18:35
 */

require '../../vendor/autoload.php';

use Carbon\Carbon;

use \RedCrossQuest\Entity\TroncQueteurEntity;

/********************************* TRONC_QUETEUR ****************************************/

/**
 * Supprime les tronc_queteurs qui implique le tronc ({id}) et qui ont soit la colonne départ ou retour de null.
 */
$app->delete('/{role-id:[2-9]}/ul/{ul-id}/tronc_queteur/{id}', function ($request, $response, $args)
{
  $decodedToken = $request->getAttribute('decodedJWT');
  try
  {
    $ulId = $decodedToken->getUlId();
    //c'est bien le troncId qu'on passe ici, on va supprimer tout les tronc_queteur qui ont ce tronc_id et départ ou retour à nulle
    $troncId = $this->clientInputValidator->validateInteger('id', $args['id'], 1000000, true);
    $userId  = $decodedToken->getUid ();

    $this->logger->addError("user $userId of UL $ulId is deleting tronc id=$troncId");

    $this->troncQueteurDBService->deleteNonReturnedTroncQueteur($troncId, $ulId, $userId);
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
    $ulId      = $decodedToken->getUlId();
    $params    = $request->getQueryParams();
    $userId    = $decodedToken->getUid ();
    $adminMode = $this->clientInputValidator->validateBoolean("adminMode", getParam($params,'adminMode'), false, false);

    if(array_key_exists('action', $params))
    {
      $action = $this->clientInputValidator->validateString("action", $params['action'], 20 , true );
      $input  = $request->getParsedBody();

      /** @var TroncQueteurEntity */
      $tq = new TroncQueteurEntity($input, $this->logger);

      if ($action =="saveReturnDate")
      {
        // if the depart Date was missing, we make mandatory for the user to fill one.
        if($this->clientInputValidator->validateBoolean("dateDepartIsMissing", getParam($params, 'dateDepartIsMissing'), false, false))
        {
            $this->logger->addInfo("Setting date depart that was missing for tronc_queteur", array("id"=>$tq->id, "depart" => $tq->depart));
            $this->troncQueteurDBService->setDepartToCustomDate($tq, $ulId, $userId);
        }

        $this->troncQueteurDBService->updateRetour($tq, $ulId, $userId);
      }
      elseif ($action =="saveCoins")
      {
        $this->troncQueteurDBService->updateCoinsCount($tq, $adminMode, $ulId, $userId);

        /**
         * When updating BigQuery, we need to know if it's the first time the user input the coins data or not
         * So that we know it's an insert or update that we must perform
         */

        $responseMessageIds = null;
        try
        {
          //TODO remove || true, implement properties, use configuration for topic name
          if($tq->comptage == null || true)
          {
            $responseMessageIds = $this->PubSub->publish("tronc_queteur"        , $tq->prepareForPublish(), ['location' => 'Detroit'], true, true);
          }
          else
          {
            $responseMessageIds = $this->PubSub->publish("tronc_queteur_updated", $tq->prepareForPublish(), ['location' => 'Detroit'], true, true);
          }

          $this->logger->addError("Publish responses ", array("response"=>$responseMessageIds));

        }
        catch(\Exception $exception)
        {
          $this->logger->addError("error while publishing on topic='tronc_queteur'", array("exception"=>$exception));
        }

      }
      elseif ($action =="saveAsAdmin")
      {
        $this->troncQueteurDBService->updateTroncQueteurAsAdmin($tq, $ulId, $userId);
      }
      elseif ($action =="cancelDepart")
      {
        $numberOfRowUpdated = $this->troncQueteurDBService->cancelDepart($tq, $ulId, $userId);
        if($numberOfRowUpdated != 1 )
        {
          $this->logger->addError("numberOfRowUpdated=$numberOfRowUpdated, likely that retour is not null", array("tronc_queteur"=>$tq->id));

          throw new \Exception("numberOfRowUpdated=$numberOfRowUpdated, likely that retour is not null");
        }
      }
      elseif ($action =="cancelRetour")
      {

        $numberOfRowUpdated = $this->troncQueteurDBService->cancelRetour($tq, $ulId, $userId);
        if($numberOfRowUpdated != 1 )
        {
          $this->logger->addError("numberOfRowUpdated=$numberOfRowUpdated, likely that comptage is not null", array("tronc_queteur"=>$tq->id));
          throw new \Exception("numberOfRowUpdated=$numberOfRowUpdated, likely that comptage is not null");
        }

      }
      else
      {
        throw new \Exception("Unkown action '$action'");
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
    $ulId   = $decodedToken->getUlId();
    $params = $request->getQueryParams();
    $userId = $decodedToken->getUid ();

    if(array_key_exists('action', $params))
    {
      $action   = $this->clientInputValidator->validateString ("action"  , $params['action'], 40 , true );
      $tronc_id = $this->clientInputValidator->validateInteger('tronc_id', getParam($params,'tronc_id'), 1000000, true);;
      $roleId   = $decodedToken->getRoleId();

      if($action == "getTroncQueteurForTroncIdAndSetDepart")
      {// départ du tronc
        $this->logger->warn("TroncQueteur POST DEPART");
        $tq = $this->troncQueteurBusinessService->getLastTroncQueteurFromTroncId($tronc_id, $ulId, $roleId);

        if($tq->depart_theorique->year != (new Carbon())->year)
        {
          $tq->troncFromPreviousYear=true;
        }
        else
        {
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
              $departDate = $this->troncQueteurDBService->setDepartToNow($tq->id, $ulId, $userId);
              $tq->depart = $departDate;
            }
            else
            {
              $tq->departAlreadyRegistered=true;
              //$this->logger->warn("TroncQueteur with id='".$troncQueteur->id."' has already a 'depart' defined('".$troncQueteur->depart."'), don't update it", array('decodedToken'=>$decodedToken));
            }
          }
        }

        return $response->getBody()->write(json_encode($tq));
      }
      else
      {
        throw new \Exception("Unknown action '$action'" );
      }

    }
    else
    { // préparation du tronc
      $this->logger->warn("TroncQueteur POST PREPARATION");
      $input = $request->getParsedBody();
      $tq    = new TroncQueteurEntity($input, $this->logger);

      $insertResponse = $this->troncQueteurDBService->insert($tq, $ulId, $userId);

      if($insertResponse->troncInUse != true)
      {//if the tronc is not already in use, the we can save the preparation

        if($tq->preparationAndDepart == true)
        {//if the user click to Save And perform the depart, we proceed and save the depart
          $this->logger->warn("TroncQueteur POST PREPARATION DEPART NOW");
          $this->troncQueteurDBService->setDepartToNow($insertResponse->lastInsertId, $ulId, $userId);
        }
      }
      else
      {
        $this->logger->warn("TroncQueteur POST PREPARATION - TRONC IN USE");
      }

      //in any case, we return the insert response
      return $response->getBody()->write(json_encode($insertResponse));
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
    $ulId       = $decodedToken->getUlId();
    $roleId     = $decodedToken->getRoleId();
    $queteur_id = null;
    $tronc_id   = null;

    $params       = $request->getQueryParams();
    $troncQueteur = null;

    if(array_key_exists('action', $params))
    {
      $action   = $this->clientInputValidator->validateString("action", $params['action'], 40 , true );
      //$this->logger->debug("action='$action'", array('decodedToken'=>$decodedToken));

      if($action == "getLastTroncQueteurFromTroncId")
      {
        $troncId      = $this->clientInputValidator->validateInteger('tronc_id', getParam($params,'tronc_id'), 1000000, true);
        $troncQueteur = $this->troncQueteurBusinessService->getLastTroncQueteurFromTroncId($troncId, $ulId, $roleId);

        return $response->getBody()->write(json_encode($troncQueteur));
      }
      else if($action == "getTroncsQueteurForTroncId")
      {
        $troncId      = $this->clientInputValidator->validateInteger('tronc_id', getParam($params,'tronc_id'), 1000000, true);
        $troncQueteur = $this->troncQueteurDBService->getTroncsQueteurByTroncId($troncId, $ulId);

        return $response->getBody()->write(json_encode($troncQueteur));
      }
      else if($action == "getTroncsOfQueteur")
      {
        $queteurId    = $this->clientInputValidator->validateInteger('queteur_id', getParam($params,'queteur_id'), 1000000, true);
        $troncsQueteur= $this->troncQueteurDBService->getTroncsQueteur($queteurId, $ulId);

        return $response->getBody()->write(json_encode($troncsQueteur));
      }
      else if($action == "searchMoneyBagId")
      {
        $query        = $this->clientInputValidator->validateString("q"   , getParam($params,'q'   ), 30 , true );
        $type         = $this->clientInputValidator->validateString("type", getParam($params,'type'),  4 , true );

        $this->logger->debug("action='$action'", array('query'=>$query, 'type'=>$type));
        $moneyBagIds = $this->troncQueteurDBService->searchMoneyBagId($query, $type, $ulId);

        return $response->getBody()->write(json_encode($moneyBagIds));
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
    $ulId   = $decodedToken->getUlId  ();
    $roleId = $decodedToken->getRoleId();

    $troncQueteurId = $this->clientInputValidator->validateInteger('id', $args['id'], 1000000, true);
    $troncQueteur   = $this->troncQueteurBusinessService->getTroncQueteurFromTroncQueteurId($troncQueteurId, $ulId, $roleId);


    return $response->getBody()->write(json_encode($troncQueteur));
  }
  catch(\Exception $e)
  {
    $this->logger->addError("error while fetching tronc_queteur with id $troncQueteurId", array('decodedToken'=>$decodedToken, "Exception"=>$e));
    throw $e;
  }
});




