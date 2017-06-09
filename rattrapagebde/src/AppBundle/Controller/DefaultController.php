<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $activityRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Activity');
        $activityPhotoRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto');

        $query = $activityRepository->createQueryBuilder('a')                            
                            ->orderBy('a.id', 'DESC')
                            ->getQuery();

        $lastActivities = $query->setMaxResults(3)->getResult();

        $lastActivitiesInfo = [];
        $lastActivitiesPhoto = [];

        foreach($lastActivities as $activity)
        {
            $lastActivitiesInfo[] = ["id" => $activity->getId(), "date" => $activity->getDate(), "name" => $activity->getName()];

            $query2 = $activityPhotoRepository->createQueryBuilder('ap')
                                                ->orderBy('ap.id', 'ASC')
                                                ->where('ap.activity = :activity_id')
                                                ->setParameter('activity_id', $activity->getId())
                                                ->getQuery();

            $activitiesPhoto = $query2->setMaxResults(1)->getResult();                                 
            // $lastActivitiesPhoto[] = $activitiesPhoto[0]->getPhoto();
        }

        $user['prenom'] = "";

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $user = $this->get('security.context')->getToken()->getUser();

            if(strlen($user->getPrenom()) >= 8)
            {
                $words = explode(" ", $user->getPrenom());
                $initiales = '';
             
                foreach($words as $init){
                    $initiales .= $init{0};
                }

                $user->setPrenom($initiales);
            }
        }

        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
            'lastActivitiesInfo' => $lastActivitiesInfo,
            'lastActivitiesPhoto' => $lastActivitiesPhoto,
            'user' => $user
        ));
    }
}
