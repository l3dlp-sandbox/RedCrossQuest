<?php
namespace RedCrossQuest\routes\routesActions\unitesLocales;


/**
 * @OA\Schema(schema="ApproveULRegistrationResponse", required={"mapKey", "RGPDVideo", "RedQuestDomain","RCQVersion", "FirstDay","ul", "ul_settings", "user"})
 */
class ApproveULRegistrationResponse
{
  /**
   * @OA\Property()
   * @var int|null $user_id The created User Id
   */
  public ?int $user_id;

  /**
   * @OA\Property()
   * @var int|null $queteur_id the created queteur ID
   */
  public ?int $queteur_id;

  /**
   * @OA\Property()
   * @var int|null $ul_id the approved UL ID
   */
  public ?int $ul_id;

  /**
   * @OA\Property()
   * @var int|null $registration_id the approved UL Registration ID
   */
  public ?int $registration_id;

  /**
   * @OA\Property()
   * @var string|null $create_queteur_sql for test environment
   */
  public ?string $create_queteur_sql;

  /**
   * @OA\Property()
   * @var string|null $update_ul_sql for test environment
   */
  public ?string $update_ul_sql;

  protected array $_fieldList = ["user_id", "queteur_id", "ul_id","registration_id", "create_queteur_sql","update_ul_sql"];

  public function __construct(int $user_id, int $queteur_id, int $ul_id, int $registration_id,  string $create_queteur_sql, string $update_ul_sql)
  {
    $this->user_id            = $user_id;
    $this->queteur_id         = $queteur_id;
    $this->ul_id              = $ul_id;
    $this->registration_id    = $registration_id;
    $this->create_queteur_sql = $create_queteur_sql;
    $this->update_ul_sql      = $update_ul_sql;
  }
}
