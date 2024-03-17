<?php

namespace App\Form;

use App\Entity\ProductionSite;
use App\Entity\Resource;
use App\Entity\ResourceName;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EleveurBirthType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id')
            ->add('ResourceName', EntityType::class, [
            'class' => ResourceName::class,
            'choice_label' => 'name',
            'query_builder' => function (EntityRepository $er){
                return $er->createQueryBuilder('rn')
                    ->join('rn.resourceCategory', 'rc')
                    ->andWhere('rc.category = :category')
                    ->setParameter('category', 'ANIMAL');
            }
        ])
            ->add('weight')
            ->add('submit', SubmitType::class, [
                'label' => 'Confirmer la naissance'
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
