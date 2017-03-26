<?php
/**
 * Created by IntelliJ IDEA.
 * User: tmanson
 * Date: 06/03/2017
 * Time: 18:35
 */




/**
 * créer un départ de tronc (id_queteur, id_tronc, départ_théorique, point_quete)
 *
 */
$app->post('/ul/{ul-id}/tronc_queteur', function ($request, $response, $args)
{
  try
  {
    $ulId = (int)$args['ul-id'];
    $this->logger->addInfo("Request UL ID '".$ulId."''");


    $params = $request->getQueryParams();
    $troncQueteurMapper = new RedCrossQuest\TroncQueteurMapper($this->db, $this->logger);

    if(array_key_exists('action', $params))
    {
      $action   = $params['action'  ];
      $tronc_id = $params['tronc_id'];

      $troncQueteurAction = new RedCrossQuest\TroncQueteurAction( $this->logger,
        $troncQueteurMapper,
        new RedCrossQuest\QueteurMapper   ($this->db, $this->logger),
        new RedCrossQuest\PointQueteMapper($this->db, $this->logger),
        new RedCrossQuest\TroncMapper     ($this->db, $this->logger)
      );


      if($action == "getTroncQueteurForTroncIdAndSetDepart")
      {
        $troncQueteur = $troncQueteurAction->getLastTroncQueteurFromTroncId($tronc_id);

        if($troncQueteur->depart == null)
        {
          $departDate = $troncQueteurMapper->setDepartToNow($troncQueteur->id);
          $troncQueteur->depart = $departDate;
        }
        else
        {
          $troncQueteur->departAlreadyRegistered=true;
          $this->logger->warn("TroncQueteur with id='".$troncQueteur->id."' has already a 'depart' defined('".$troncQueteur->depart."'), don't update it");
        }

        $response->getBody()->write(json_encode($troncQueteur));
        return $response;
      }

    }
    else
    {

      $input  = $request->getParsedBody();

      $tq = new \RedCrossQuest\TroncQueteurEntity($input, $this->logger);
      $this->logger->debug("tronc_queteur", [$tq]);
      try
      {
        $troncQueteurMapper->insert($tq);
      }
      catch(Exception $e)
      {
        $this->logger->addError($e);
        throw $e;
      }
      return $response;
    }

  }
  catch(Exception $e)
  {
    $this->logger->addError($e);
    throw $e;
  }

});

/**
 * Recherche un tronc_queteur
 */
$app->get('/ul/{ul-id}/tronc_queteur', function ($request, $response, $args)
{
  try
  {
    $ulId = (int)$args['ul-id'];
    $this->logger->addInfo("Request UL ID '".$ulId."''");

    $params = $request->getQueryParams();

    if(array_key_exists('action', $params))
    {
      $action   = $params['action'  ];
      $troncQueteurAction = new RedCrossQuest\TroncQueteurAction($this->logger,
        new RedCrossQuest\TroncQueteurMapper($this->db, $this->logger),
        new RedCrossQuest\QueteurMapper     ($this->db, $this->logger),
        new RedCrossQuest\PointQueteMapper  ($this->db, $this->logger),
        new RedCrossQuest\TroncMapper       ($this->db, $this->logger));

      if($action == "getTroncQueteurForTroncId")
      {
        $this->logger->debug("action='getTroncQueteurForTroncId'");
        $tronc_id     = $params['tronc_id'];
        $troncQueteur = $troncQueteurAction->getLastTroncQueteurFromTroncId($tronc_id);
        $response->getBody()->write(json_encode($troncQueteur, JSON_NUMERIC_CHECK));
        return $response;
      }
      else if($action == "getTroncsOfQueteur")
      {
        $this->logger->debug("action='getTroncsOfQueteur'");
        $queteur_id         = $params['queteur_id'];
        $troncQueteurMapper = new RedCrossQuest\TroncQueteurMapper($this->db, $this->logger);
        $troncsQueteur      = $troncQueteurMapper->getTroncsQueteur($queteur_id);
        $response->getBody()->write(json_encode($troncsQueteur, JSON_NUMERIC_CHECK));
        return $response;
      }
    }

  }
  catch(Exception $e)
  {
    $this->logger->addError($e);
    throw $e;
  }


  return $response;
});


/**
 * récupère un tronc_queteur par son id
 *
 */
$app->get('/ul/{ul-id}/tronc_queteur/{id}', function ($request, $response, $args)
{
  try
  {
    $ulId = (int)$args['ul-id'];
    $this->logger->addInfo("Request UL ID '".$ulId."''");

    $troncQueteurId = (int)$args['id'];
    $this->logger->debug("Getting tronc_queteur with id '".$troncQueteurId."'");

    $params = $request->getQueryParams();
    $troncQueteurAction = new RedCrossQuest\TroncQueteurAction($this->logger,
      new RedCrossQuest\TroncQueteurMapper($this->db, $this->logger),
      new RedCrossQuest\QueteurMapper     ($this->db, $this->logger),
      new RedCrossQuest\PointQueteMapper  ($this->db, $this->logger),
      new RedCrossQuest\TroncMapper       ($this->db, $this->logger));

    $troncQueteur = $troncQueteurAction->getTroncQueteurFromTroncQueteurId($troncQueteurId);

    $response->getBody()->write(json_encode($troncQueteur, JSON_NUMERIC_CHECK));

    return $response;
  }
  catch(Exception $e)
  {
    $this->logger->addError($e);
    throw $e;
  }


});