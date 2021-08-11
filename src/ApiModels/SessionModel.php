<?php


namespace App\ApiModels;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class SessionModel
{
  /**
   * @var string
   * @OA\Property(description="Hash of the song.")

   */
  public $HashInfo;
    /**
     * @var string
     * @OA\Property(description="The score.")

     */
  public $Score;
    /**
     * @var string
     * @OA\Property(description="Played song level.")

     */
    public $Level;
    /**
     * @var string
     * @OA\Property(description="The ragancustoms application version.")
     */
    public $AppVersion;
}