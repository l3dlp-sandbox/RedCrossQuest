<?php
namespace RedCrossQuest\Entity;

use RedCrossQuest\Service\Logger;

class MailingInfoEntity extends Entity
{
  public $id;
  public $email                       ;
  public $first_name                  ;
  public $last_name                   ;
  /**
  "1">Action Sociale
  "2">Secours
  "3">Non Bénévole
  "4">Ancien Bénévole, Inactif ou Adhérent
  "5">Commerçant
  "6">Special
   */
  public $secteur                     ;
  public $man                         ;
  public $spotfire_access_token       ;

  // not retrieved from DB

  public $status                      ;

  protected $_fieldList = ['id', 'email', 'first_name', 'last_name', 'secteur', 'man', 'spotfire_access_token', 'status'];

  /**
   * Accept an array of data matching properties of this class
   * and create the class
   *
   * @param array $data The data to use to create
   * @param Logger $logger
   * @throws \Exception if a parse Date or JSON fails
   */
  public function __construct(array $data, Logger $logger)
  {
    parent::__construct($logger);
    $this->getInteger('id'                          , $data);
    $this->getEmail  ('email'                       , $data);
    $this->getString ('first_name'                  , $data, 100);
    $this->getString ('last_name'                   , $data, 100);
    $this->getInteger('secteur'                     , $data);
    $this->getBoolean('man'                         , $data);
    $this->getString ('spotfire_access_token'       , $data, 36);
  }
}
