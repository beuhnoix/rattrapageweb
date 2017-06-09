<?php
// src/ActivitiesBundle/Form/TaskType.php
namespace ActivitiesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ActivityPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('photo', FileType::class, array('label' => false, 'required' => true, 'multiple' => true));
    }
}
?>