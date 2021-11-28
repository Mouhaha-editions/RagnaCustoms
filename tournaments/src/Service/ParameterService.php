<?php


namespace App\Service;


use App\Repository\ParameterRepository;
use Doctrine\ORM\EntityManagerInterface;

class ParameterService
{
    /** @var EntityManagerInterface */
    public $entityManager;
    /** @var ParameterRepository */
    public $repository;

    public function __construct(ParameterRepository $repository)
    {
        $this->repository = $repository;
    }


    public function getValue($slug, $default = null)
    {

        $parameter = $this->repository->findOneBy(['slug' => $slug]);
        if ($parameter != null) {
            if($parameter->getValueFile() == null){
                return $parameter->getValueText();
            }else{
                return "/files/".$parameter->getValueFile();
            }
        }

        return $default;
    }

}