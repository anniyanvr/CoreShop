<?php

namespace CoreShop\Bundle\AddressBundle\Form\Type;

use CoreShop\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ZoneChoiceType extends AbstractType
{
    /**
     * @var RepositoryInterface
     */
    private $zoneRepository;

    /**
     * @param RepositoryInterface $zoneRepository
     */
    public function __construct(RepositoryInterface $zoneRepository)
    {
        $this->zoneRepository = $zoneRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'choices' => function (Options $options) {
                    if (null === $options['active']) {
                        $zones = $this->zoneRepository->findAll();
                    } else {
                        $zones = $this->zoneRepository->findBy(['active' => $options['active']]);
                    }

                    /*
                     * PHP 5.* bug, fixed in PHP 7: https://bugs.php.net/bug.php?id=50688
                     * "usort(): Array was modified by the user comparison function"
                     */
                    @usort($zones, function($a, $b) {
                        return $a->getName() < $b->getName() ? -1 : 1;
                    });

                    return $zones;
                },
                'choice_value' => 'id',
                'choice_label' => 'name',
                'choice_translation_domain' => false,
                'active' => true,
                'label' => 'coreshop.form.address.zone',
                'placeholder' => 'coreshop.form.zone.select',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'coreshop_zone_choice';
    }
}
