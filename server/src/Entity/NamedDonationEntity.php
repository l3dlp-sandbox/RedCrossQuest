<?php /** @noinspection ALL */

namespace RedCrossQuest\Entity;

use Carbon\Carbon;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * @OA\Schema(schema="NamedDonationEntity", required={"ul_id", "first_name", "last_name", "address", "postal_code", "city", "type", "forme"})
 */
class NamedDonationEntity extends Entity
{
  /**
   * @OA\Property()
   * @var ?int $id NamedDonation ID
   */
  public ?int $id               ;

  /**
   * @OA\Property()
   * @var ?int $ul_id UL ID
   */
  public ?int $ul_id            ;
  /**
   * @OA\Property()
   * @var ?int $ref_recu_fiscal The business ID of the NamedDonation. It's the official id from the RedCross, used as reference for tax deduction
   */
  public ?int $ref_recu_fiscal  ;

  /**
   * @OA\Property()
   * @var ?string $first_name Donor first name
   */
  public ?string $first_name       ;
  /**
   * @OA\Property()
   * @var ?string $last_name Donor last name
   */
  public ?string $last_name        ;
  /**
   * @OA\Property()
   * @var ?string $donation_date Donation date
   */
  public ?string $donation_date    ;
  /**
   * @OA\Property()
   * @var ?string $address Donor address
   */
  public ?string $address          ;
  /**
   * @OA\Property()
   * @var ?string $postal_code Donor postal code
   */
  public ?string $postal_code      ;
  /**
   * @OA\Property()
   * @var ?string $city Donor city
   */
  public ?string $city             ;
  /**
   * @OA\Property()
   * @var ?string $phone Donor Phone
   */
  public ?string $phone            ;
  /**
   * @OA\Property()
   * @var ?string $email Donor email
   */
  public ?string $email            ;

  /**
   * @OA\Property()
   * @var ?int $euro500 Number of 500€ bills
   */
  public ?int $euro500          ;
  /**
   * @OA\Property()
   * @var ?int $euro200 Number of 200€ bills
   */
  public ?int $euro200          ;
  /**
   * @OA\Property()
   * @var ?int $euro100 Number of 100€ bills
   */
  public ?int $euro100          ;
  /**
   * @OA\Property()
   * @var ?int $euro50 Number of 50€ bills
   */
  public ?int $euro50           ;
  /**
   * @OA\Property()
   * @var ?int $euro20 Number of 20€ bills
   */
  public ?int $euro20           ;
  /**
   * @OA\Property()
   * @var ?int $euro10 Number of 10€ bills
   */
  public ?int $euro10           ;
  /**
   * @OA\Property()
   * @var ?int $euro5 Number of 5€ bills
   */
  public ?int $euro5            ;
  /**
   * @OA\Property()
   * @var ?int $euro2 Number of 2€ coins
   */
  public ?int $euro2            ;
  /**
   * @OA\Property()
   * @var ?int $euro1 Number of 1€ coins
   */
  public ?int $euro1            ;
  /**
   * @OA\Property()
   * @var ?int $cents50 Number of 50cts coins
   */
  public ?int $cents50          ;
  /**
   * @OA\Property()
   * @var ?int $cents20 Number of 20cts coins
   */
  public ?int $cents20          ;
  /**
   * @OA\Property()
   * @var ?int $cents10 Number of 10cts coins
   */
  public ?int $cents10          ;
  /**
   * @OA\Property()
   * @var ?int $cents5 Number of 5cts coins
   */
  public ?int $cents5           ;
  /**
   * @OA\Property()
   * @var ?int $cents2 Number of 2cts coins
   */
  public ?int $cents2           ;
  /**
   * @OA\Property()
   * @var ?int $cent1 Number of 1ct coins
   */
  public ?int $cent1            ;
  /**
   * @OA\Property()
   * @var ?float $don_cheque total amount of bank note collected
   */
  public ?float $don_cheque       ;
  /**
   * @OA\Property()
   * @var ?float $don_cheque total amount of credit card payment collected
   */
  public ?float $don_creditcard   ;

  /**
   * @OA\Property()
   * @var ?string $notes notes about the donation
   */
  public ?string $notes             ;

  /**
   * @OA\Property()
   * @var ?int $type {id:1,label:'Espèce'}, {id:2,label:'Chèque'}, {id:3,label:'Virement, Prélèvement, Carte Bancaire'}
   */
  public ?int $type              ;

  /**
   * @OA\Property()
   * @var ?int $forme {id:1,label:'Déclaration de don manuel'},{id:2,label:'Acte sous seing privé'}
   */
  public ?int $forme             ;

  /**
   * @OA\Property()
   * @var ?bool $deleted if the NamedDonation is marked as deleted or not
   */
  public ?bool $deleted           ;

  /**
   * @OA\Property()
   * @var ?string $coins_money_bag_id Identifier of the bag that contains the coins of this troncQueteur. It's used to track the total amount and weight of the bag. The amount must be exact to avoid bank penalty. The bank is also setting limits so that the bag is not teared apart with an excess of weight.
   */
  public ?string $coins_money_bag_id;
  /**
   * @OA\Property()
   * @var ?string $bills_money_bag_id Identifier of the bag that contains the bills of this troncQueteur. It's used to track the total amount and weight of the bag. The amount must be exact to avoid bank penalty. The bank is also setting limits so that the bag is not teared apart with an excess of weight.
   */
  public ?string $bills_money_bag_id;

  /**
   * @OA\Property()
   * @var ?Carbon $last_update Last time the NamedDonation row is updated
   */
  public ?Carbon $last_update      ;
  /**
   * @OA\Property()
   * @var ?int $last_update_user_id UserId of the user that performed the last update on this NamedDonation
   */
  public ?int $last_update_user_id;

  protected array $_fieldList = ['id','ul_id','ref_recu_fiscal','first_name','last_name','donation_date','address','postal_code','city','phone','email','euro500','euro200','euro100','euro50','euro20','euro10','euro5','euro2','euro1','cents50','cents20','cents10','cents5','cents2','cent1','don_cheque','don_creditcard','notes','type','forme','deleted','coins_money_bag_id','bills_money_bag_id','last_update','last_update_user_id'];


   /**
     * Accept an array of data matching properties of this class
     * and create the class
     *
     * @param array $data The data to use to create
     * @param LoggerInterface $logger
     * @throws Exception if a parse Date or JSON fails
     */
    public function __construct(array &$data, LoggerInterface $logger)
    {
      parent::__construct($logger);

      $this->getInteger('id'              , $data);
      $this->getInteger('ul_id'           , $data);

      $this->getInteger('ref_recu_fiscal'  , $data);
      $this->getString ('first_name'       , $data, 100);
      $this->getString ('last_name'        , $data, 100);
      $this->getDate   ('donation_date'    , $data);
      $this->getString ('address'          , $data, 200);
      $this->getInteger('postal_code'      , $data);
      $this->getString ('city'             , $data, 70);
      $this->getString ('phone'            , $data, 20);
      $this->getEmail  ('email'            , $data);

      $this->getInteger('euro500'          , $data);
      $this->getInteger('euro200'          , $data);
      $this->getInteger('euro100'          , $data);
      $this->getInteger('euro50'           , $data);
      $this->getInteger('euro20'           , $data);
      $this->getInteger('euro10'           , $data);
      $this->getInteger('euro5'            , $data);
      $this->getInteger('euro2'            , $data);
      $this->getInteger('euro1'            , $data);
      $this->getInteger('cents50'          , $data);
      $this->getInteger('cents20'          , $data);
      $this->getInteger('cents10'          , $data);
      $this->getInteger('cents5'           , $data);
      $this->getInteger('cents2'           , $data);
      $this->getInteger('cent1'            , $data);

      $this->getFloat  ('don_cheque'       , $data);
      $this->getFloat  ('don_creditcard'   , $data);
      

      $this->getString ('notes'            , $data, 500);
      $this->getInteger('type'             , $data);
      $this->getInteger('forme'            , $data);


      $this->getBoolean('deleted'             , $data);

      $this->getString('coins_money_bag_id'   , $data, 20);
      $this->getString('bills_money_bag_id'   , $data, 20);

      $this->getInteger('last_update_user_id' , $data);
      $this->getDate   ('last_update'         , $data);
    }

  /***
   * check if some money information has been filled
   * @return bool true if at least one bill or one coin or don_cheque or don_cb is > 0
   */
    function isMoneyFilled():bool
    {
      return
        $this->checkPositive($this->euro500       ) ||
        $this->checkPositive($this->euro200       ) ||
        $this->checkPositive($this->euro100       ) ||
        $this->checkPositive($this->euro50        ) ||
        $this->checkPositive($this->euro20        ) ||
        $this->checkPositive($this->euro10        ) ||
        $this->checkPositive($this->euro5         ) ||
        $this->checkPositive($this->euro2         ) ||
        $this->checkPositive($this->euro1         ) ||
        $this->checkPositive($this->cents50       ) ||
        $this->checkPositive($this->cents20       ) ||
        $this->checkPositive($this->cents10       ) ||
        $this->checkPositive($this->cents5        ) ||
        $this->checkPositive($this->cents2        ) ||
        $this->checkPositive($this->cent1         ) ||
        $this->checkPositive($this->don_cheque    ) ||
        $this->checkPositive($this->don_creditcard) ;
    }

  /***
   * @param $value float the value to check
   * @return bool true if the value is > 0
   */
    function checkPositive($value):bool
    {
      return $value != null && $value > 0;
    }
}
