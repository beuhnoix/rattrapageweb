<?php

namespace ActivitiesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use ActivitiesBundle\Entity\ActivityIdea;
use ActivitiesBundle\Entity\ActivityUserProblem;
use ActivitiesBundle\Entity\ActivitiesVote;
use ActivitiesBundle\Entity\PhotoComment;
use ActivitiesBundle\Entity\ActivityPhoto;
use ActivitiesBundle\Entity\Activity;
use UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use ActivitiesBundle\Form\ActivityPhotoType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ActivityController extends Controller 
{
	/**
	 * @Route("all", name="activitiesShow")
	 */
	public function showAllAction(){
		$listActivities = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Activity')->findAll();
		$repositoryActivityPhoto = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto');

		$activitiesPhoto = [];
		$activitiesPast = [];
		$activitiesFutur = [];

		foreach($listActivities as $activity)
		{
			$photo = $repositoryActivityPhoto->findByActivity($activity);
			$photo = $photo[0];

			$activitiesPhoto[$activity->getId()] = $photo->getPhoto();

			$date = $activity->getDate();
			$dateToday = new \DateTime("now");

			if($dateToday > $date)
			{
				$activitiesPast[] = $activity;
			}

			else
			{
				$activitiesFutur[] = $activity;
			}

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

		return $this->render('ActivitiesBundle::listActivity.html.twig', array('activitiesFutur' => 
			$activitiesFutur, 'activitiesPhoto' => $activitiesPhoto, 'user' => $user));
	}

	/**
	 * @Route("signInActivity/{activity_id}", name="signInActivity",
	 * 			defaults={"activity_id": 1},
     *     		requirements={     *        		
     *         		"activity_id": "\d+"
     *			})
	 */
	public function signInActivityAction(Request $request, $activity_id)
	{
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

		$em = $this->getDoctrine()->getManager();
		$activity = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Activity')->find($activity_id);

		if (!$activity) {
	        throw $this->createNotFoundException('L\'activité n\'existe pas !');
	    }

	    $dateToday = new \DateTime("now");
	    $dateActivity = $activity->getDate();

	    $alreadyPast = 0;

	    if($dateToday > $dateActivity)
	    {
	    	$alreadyPast = 1;
	    }


	    $problemRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Problem');

	    $query = $problemRepository->createQueryBuilder('ap')
						    ->where('ap.name != :name')
						    ->setParameter('name', 'Aucun')
						    ->getQuery();

	    $problems = $query->getResult();

		$photos = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto')->findByActivity($activity);
		$commentsRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:PhotoComment');
		$user = $this->get('security.context')->getToken()->getUser();

		$activityUserRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityUserProblem');
		$alreadySignIn = $activityUserRepository->findBy(array(
				"activity" => $activity,
				"user" => $user,
			));

		$commentsInfo = [];

		foreach($photos as $photo)
		{
			$comments = $commentsRepository->findBy(array('photo' => $photo), array('date' => 'DESC'));

			foreach($comments as $comment)
			{
				$comment_user = $comment->getUser();
				$user_avatar = $comment_user->getAvatar();

				$commentsInfo[] = ["photo_id" => $comment->getPhoto()->getId(), "avatar" => $user_avatar, "comment" => $comment->getComment(), "date" => $comment->getDate()];
			}
		}
		
		$activityUser = new ActivityUserProblem();

		$form = $this->createFormBuilder($activityUser)->getForm();

	    $form->handleRequest($request);

	    if ($form->isSubmitted() && $form->isValid()) {
	    	if(count($alreadySignIn) == 0 && $alreadyPast == 0)
	    	{
		        $otherParticipation = $request->request->get('optradio');
		        $getProblems = $request->request->get('problems');
		        $comment = $request->request->get('comment');

		        $arrayGetProblems = [];

		        if(!is_null($getProblems))
		        {
		        	foreach($getProblems as $problem)
		        	{
		        		$arrayGetProblems[] = $problem;
		        	}
		        }

		        else
		        {
		        	$arrayGetProblems[] = 3;
		        }

		        foreach($arrayGetProblems as $problem_id)
		        {
		        	$activityUserProblem = new ActivityUserProblem();

		        	$problem = $problemRepository->find($problem_id);

		        	if($problem)
		        	{
			        	$activityUserProblem->setUser($user);
				        $activityUserProblem->setProblem($problem);
				        $activityUserProblem->setActivity($activity);
				        $activityUserProblem->setOtherParticipation($otherParticipation);
				        $activityUserProblem->setComment($comment);
				       	        
				        $em->persist($activityUserProblem);
				        $em->flush();
				    }
		        }

		        $this->addFlash(
		            'success',
		            'Vous êtes inscrit !'
		        );
		    }

		    return $this->redirectToRoute('signInActivity', array('activity_id' => $activity_id));
	    }

	    else if($form->isSubmitted() && !$form->isValid())
	    {
	    	$this->addFlash(
	            'error',
	            'Error !'
	        );
	    }

	    $activityPhotoRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto');

	    $activityPhoto = new ActivityPhoto();

	    $formPhoto = $this->createForm(ActivityPhotoType::class, $activityPhoto);

	    $formPhoto->handleRequest($request);

	    if ($formPhoto->isSubmitted() && $formPhoto->isValid()) {
		    $activityPhoto = $formPhoto->getData();

		    $lastIdActivityPhoto = $activityPhotoRepository->findOneBy(array('activity' => $activity), array('id' => 'DESC'))->getId();

		    $id = $lastIdActivityPhoto+1;

		    foreach($activityPhoto->getPhoto() as $photo)
		    {
		    	$photoActivity = new ActivityPhoto();

		    	$photoName = $activity->getName().$id.'.'.$photo->guessExtension();

		    	$id++;

		    	$photo->move(
	                $this->getParameter('imgActivities'),
	                $photoName
	            );
		    	
		    	$photoActivity->setActivity($activity);
		    	$photoActivity->setPhoto($photoName);
		    	$photoActivity->setLove(0);

		    	$em->persist($photoActivity);
		   		$em->flush();
		    }
		       	   
		    $this->addFlash(
		        'success_photo',
		        'Photo(s) uploadée(s)'
		    );

		    return $this->redirectToRoute('signInActivity', array('activity_id' => $activity_id));
	    }

	    else if($formPhoto->isSubmitted() && !$formPhoto->isValid())
	    {
	    	$this->addFlash(
	            'error_photo',
	            'Error !'
	        );
	    }

        if(strlen($user->getPrenom()) >= 8)
        {
            $words = explode(" ", $user->getPrenom());
            $initiales = '';
         
            foreach($words as $init){
                $initiales .= $init{0};
            }

            $user->setPrenom($initiales);
        }

		return $this->render('ActivitiesBundle::activity.html.twig', array(
				'activity' => $activity,
				'photos' => $photos,
				'form' => $form->createView(),
				'alreadySignIn' => count($alreadySignIn),
				'commentsInfo' => $commentsInfo,
				'formPhoto' => $formPhoto->createView(),
				'problems' => $problems,
				'user' => $user,
				'alreadyPast' => $alreadyPast
			));
	}

	/**
	* @Route("showActivitiesVote", name="showActivitiesVote")
	*/
	public function showActivitiesVoteAction(){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

		$listActivitiesIdea = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityIdea')->findAll();
		$listActivitiesVote = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivitiesVote');

		$dates = [];
		$votes = [];

		foreach($listActivitiesIdea as $activity)
		{
			$activityDates = $listActivitiesVote->findByActivity($activity);
			$activityVote = $listActivitiesVote->findOneByActivity($activity);

			foreach($activityDates as $date)
			{
				$dates[$activity->getId()][] = $date->getDate();				
			}

			$votes[$activity->getId()] = $activityVote->getVote();
		}

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

		return $this->render('ActivitiesBundle::listActivityVote.html.twig', array(
				'listActivitiesIdea' => $listActivitiesIdea,
				'dates' => $dates,
				'votes' => $votes,
				'user' => $user
			));
	}

	/**
	* @Route("summary", name="summaryActivity")
	*/
	public function showSummaryActivityAction()
	{
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.authorization_checker')->isGranted('ROLE_BDE')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $em = $this->getDoctrine()->getManager();

		$listActivities = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Activity')->findAll();
		$activitiesUsers = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityUserProblem');

		$firstActivity = $listActivities[0];
		$date = $firstActivity->getDate();

		$query = $activitiesUsers->createQueryBuilder('a')
								 ->select('DISTINCT (a.user) as user_id')
							     ->where('a.activity = :activity')
							     ->setParameter('activity', 1)
							     ->getQuery();

		$firstActivityUsers = $query->getResult();

		$nbInscrit = count($firstActivityUsers);

		$usersInfo = [];		

		foreach($firstActivityUsers as $user)
		{
			$problems = "";

			$getOneUserActivityProblem = $activitiesUsers->findOneBy(array('activity' => 1, 'user' => $user));

			$getProblems = $activitiesUsers->findBy(array('activity' => 1, 'user' => $user));

			foreach($getProblems as $problem)
			{
				$problems .= $problem->getProblem()->getName()." ";
			}

			$usersInfo[] = array($getOneUserActivityProblem->getUser()->getNom(), $getOneUserActivityProblem->getUser()->getPrenom(), $getOneUserActivityProblem->getUser()->getPromotion(), $problems, $getOneUserActivityProblem->getUser()->getEmail());
		}

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

		return $this->render('ActivitiesBundle::summaryActivity.html.twig', array(
				"activities" => $listActivities,
				"date" => $date,
				"nbInscrit" => $nbInscrit,
				"usersInfo" => $usersInfo,
				'user' => $user
			));
	}
	/**
		 * @Route("showActivityToVote/{activity_id}", name="showActivityToVote",
		 * 			defaults={"activity_id": 1},
	     *     		requirements={     *        		
	     *         		"activity_id": "\d+"
	     *			})
		 */
		public function showActivityToVoteAction(Request $request, $activity_id){
			if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
	            return $this->redirectToRoute('fos_user_security_login');
	        }

			$em = $this->getDoctrine()->getManager();
			$activity = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityIdea')->find($activity_id);
			$listActivitiesVote = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivitiesVote');

			$dates = [];
			$votes = [];

			$activityDates = $listActivitiesVote->findByActivity($activity);
			$activityVote = $listActivitiesVote->findOneByActivity($activity);

			foreach($activityDates as $date)
			{
				$dates[$activity->getId()][] = $date->getDate();				
			}

			$votes[$activity->getId()] = $activityVote->getVote();

			if (!$activity) {
		        throw $this->createNotFoundException('L\'activité n\'existe pas !');
		    }

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

			return $this->render('ActivitiesBundle::activityToVote.html.twig', array(
				'activity' => $activity,
				'dates' => $dates,
				'votes' => $votes,
				'user' => $user
			));
}

	/**
	* @Route("activityIdea", name="activityIdea")
	*/
	public function formActivityIdeaAction(Request $request)
	{
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

		$em = $this->getDoctrine()->getManager();
	    $activityIdea = new activityIdea();
	    $user = $this->get('security.context')->getToken()->getUser();

		$form = $this->createFormBuilder($activityIdea)
	        ->add('name', TextType::class)
	        ->add('description', TextareaType::class)
	        ->add('date', DateTimeType::class)
	        ->getForm();
	        
		$form->handleRequest($request);

	    if ($form->isSubmitted() && $form->isValid()) {
	        $activityIdea = $form->getData();
	        $activityIdea->setUser($user);
	       	        
	        $em->persist($activityIdea);
	        $em->flush();

	        $date = $activityIdea->getDate();
	        $dates = [$date];

	        $date2 = clone($date);
	        $date2->add(new \DateInterval('P1D'))->setTime(10, 0, 0);

	        $dates[] = $date2;

	        $date = clone($date2);
	        $date->add(new \DateInterval('P1D'))->setTime(14, 30, 0);

	        $dates[] = $date;

	        $date2 = clone($date);
	        $date2->add(new \DateInterval('P1D'))->setTime(16, 15, 0);

	        $dates[] = $date2;

	        foreach($dates as $date)
	        {
	        	$activityVote = new activitiesVote();
		        $activityVote->setActivity($activityIdea);
		        $activityVote->setVote(0);
		        $activityVote->setDate($date);

		        $em->persist($activityVote);
		        $em->flush();
	        }

	        $this->addFlash(
	            'success',
	            'Votre idée d\'activité a bien été soumise !'
	        );

	        return $this->redirectToRoute('activityIdea');
	    }

	    else if($form->isSubmitted() && !$form->isValid())
	    {
	    	$this->addFlash(
	            'error',
	            'Error !'
	        );
	    }

        if(strlen($user->getPrenom()) >= 8)
        {
            $words = explode(" ", $user->getPrenom());
            $initiales = '';
         
            foreach($words as $init){
                $initiales .= $init{0};
            }

            $user->setPrenom($initiales);
        }

		return $this->render('ActivitiesBundle::formActivityIdea.html.twig', array(
				'form' => $form->createView(),
				'user' => $user
			));
	}

	/**
	* @Route("activitiesVote", name="activitiesVote")
	*/
	public function VoteAction(Request $request){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response("Erreur");
        }

		$em = $this->getDoctrine()->getManager();
	    $repositoryActivityVote = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivitiesVote');

		$id = $request->request->get("id");
		$date = $request->request->get("horaire");

		$date2 = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

		$activityVote = $repositoryActivityVote->findOneBy(array(
				'activity' => $id,
				'date' => $date2,
			));

		$vote = $activityVote->getVote();
		$activityVote->setVote($vote + 1);

		$em->persist($activityVote);
	    $em->flush();

		return $this->redirectToRoute('showActivitiesVote');
	}

	/**
	* @Route("showActivityLike", name="showActivityLike")
	*/
	public function showActivityLikeAction()
	{
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response("Erreur");
        }

		$request = $this->container->get('request');

		if($request->isXmlHttpRequest())
    	{
    		$activity_id = $request->query->get('activity_id');
    		$date = $request->query->get('date');

			$date2 = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
		
			$repositoryActivityVote = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivitiesVote');
			$activityVote = $repositoryActivityVote->findOneBy(array(
				'activity' => $activity_id,
				'date' => $date2,
			));

			$votes = $activityVote->getVote();

			return $this->container->get('templating')->renderResponse('ActivitiesBundle::test.html.twig', array(
            	'votes' => $votes,
            ));
        }

        return new Response("Erreur");
			
	}

	/**
	* @Route("insertComment", name="insertComment")
	*/
	public function insertCommentAction()
	{
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response("Erreur");
        }

		$request = $this->container->get('request');
		$em = $this->getDoctrine()->getManager();

		if($request->isXmlHttpRequest())
    	{
    		$photo_id = $request->query->get('photo_id');
    		$text = $request->query->get('text');

			$user = $this->get('security.context')->getToken()->getUser();

			$repositoryActivityPhoto = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivityPhoto');
			$photo = $repositoryActivityPhoto->find($photo_id);
			
			$photoComment = new photoComment();

			$photoComment->setUser($user);
			$photoComment->setPhoto($photo);
			$photoComment->setComment($text);
			$photoComment->setDate(new \DateTime("now"));
			       	        
			$em->persist($photoComment);
			$em->flush();

			$comment_user = $photoComment->getUser();
			$user_avatar = $comment_user->getAvatar();

			$data = ["avatar" => $user_avatar, "comment" => $photoComment->getComment(), "date" => $photoComment->getDate()->format("d/m/Y H:i:s")];

			$response = new Response(json_encode($data));
		    $response->headers->set('Content-Type', 'application/json');

		    return $response;
        }

        else
        {
        	return new Response("Erreur");
        }
	}

	/**
	* @Route("showUsersRegistered", name="showUsersRegistered")
	*/
	public function showUsersRegisteredAction(){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response("Erreur");
        }

		$request = $this->container->get('request');

		if($request->isXmlHttpRequest())
    	{
    		$activity_id = $request->query->get('activity_id');
		
			$repositoryActivities = $this->getDoctrine()->getRepository('ActivitiesBundle:Activity');
			$activity = $repositoryActivities->find($activity_id);

			$activitiesUsers = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityUserProblem');

			$query = $activitiesUsers->createQueryBuilder('a')
								 ->select('DISTINCT (a.user) as user_id')
							     ->where('a.activity = :activity')
							     ->setParameter('activity', $activity)
							     ->getQuery();

			$activityUser = $query->getResult();

			$date = $activity->getDate();

			$usersInfo = [];

			foreach($activityUser as $user)
			{
				$problems = "";
				
				$getOneUserActivityProblem = $activitiesUsers->findOneBy(array('activity' => $activity, 'user' => $user));

				$getProblems = $activitiesUsers->findBy(array('activity' => $activity, 'user' => $user));

				foreach($getProblems as $problem)
				{
					$problems .= $problem->getProblem()->getName()." ";
				}

				$usersInfo[] = array($getOneUserActivityProblem->getUser()->getNom(), $getOneUserActivityProblem->getUser()->getPrenom(), $getOneUserActivityProblem->getUser()->getPromotion(), $problems, $getOneUserActivityProblem->getUser()->getEmail());
			}

			$table = [];

			foreach ($usersInfo as $user) {
				$table[] = 	"<tr>
								<td>".$user[0]."</td>
								<td>".$user[1]."</td>
								<td>".$user[2]."</td>
								<td>".$user[3]."</td>
								<td>".$user[4]."</td>
							</tr>";
			}

			$data = [$date, count($activityUser), $table];

			$response = new Response(json_encode($data));
		    $response->headers->set('Content-Type', 'application/json');

		    return $response;
        }

        return new Response("Erreur");
	}

	/**
	* @Route("showPhotoGallery", name="showPhotoGallery")
	*/
	public function showPhotoGalleryAction(){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

		$photos = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto')->findAll();

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

		return $this->render('ActivitiesBundle::moderationPhotos.html.twig', array(
			"photos" => $photos,
			'user' => $user
			));
	}

	/**
	* @Route("dlORrmPhoto", name="dlORrmPhoto")
	*/
	public function downloadORremovePhotoAction(Request $request){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) {
            return new Response("Erreur");
        }

		$photos = $request->request->get('photo');
		$action = $request->request->get('action');

		if(!$photos)
		{
			return $this->redirectToRoute('showPhotoGallery');
		}

		if($action == "dl")
		{
			$pathImgActivities = "http://localhost/".$this->get('request')->getBasePath()."/imgActivities/";
	        // $pathImgActivities = "http://localhost/ProjetWEB/projetWEB-BDE/web/imgActivities/";

	        $imgs = [];

	        foreach($photos as $photo)
	        {
	        	$getPhoto = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto')->find($photo);
				$filename = $getPhoto->getPhoto();

				$imgs[] = $pathImgActivities.$filename;
	        }

	        $zip = new \ZipArchive();
	        $zipName = 'Images'.date("dmY").".zip";
	        $zip->open($zipName, \ZipArchive::CREATE);

	        foreach($imgs as $img)
	        {
	        	$zip->addFromString(basename($img), file_get_contents($img));
	        }

	        $zip->close();

	        header('Content-Type', 'application/zip');
			header('Content-disposition: attachment; filename="' . $zipName . '"');
			header('Content-Length: ' . filesize($zipName));
			readfile($zipName);
		}

		else if($action == "rm")
		{
			foreach ($photos as $photo) {
				$supprPhoto = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto')->find($photo);
				$supprComments = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:PhotoComment')->findByPhoto($photo);

				foreach($supprComments as $comment)
				{
					$this->getDoctrine()->getManager()->remove($comment);
				}
				
				$this->getDoctrine()->getManager()->remove($supprPhoto);
			}

			$this->getDoctrine()->getManager()->flush();
		}

		return $this->redirectToRoute('showPhotoGallery');
	}

	/**
	* @Route("addActivity", name="addActivity")
	*/
	public function addActivityAction(Request $request){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.authorization_checker')->isGranted('ROLE_BDE')) {
             return $this->redirectToRoute('fos_user_security_login');
        }

        $em = $this->getDoctrine()->getManager();
	    $user = $this->get('security.context')->getToken()->getUser();
	    $activity = new Activity();

		$form = $this->createFormBuilder($activity)
	        ->add('name', TextType::class)
	        ->add('description', TextareaType::class)
	        ->add('date', DateTimeType::class)
	        ->add('photo', ActivityPhotoType::class, array('mapped' => false))
	        ->getForm();
	        
		$form->handleRequest($request);

	    if ($form->isSubmitted() && $form->isValid()) {
	    	$activity = $form->getData();

	    	$em->persist($activity);
	    	$em->flush();

	    	$photos = $form['photo']->getData();

	    	$activityPhotoRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:ActivityPhoto');
	    	$activityRepository = $this->getDoctrine()->getManager()->getRepository('ActivitiesBundle:Activity');

		    $photo_id = 1;
		    $last_activity = $activityRepository->findBy(array(), array('id' => 'DESC'));
		    $activity_id = $last_activity[0]->getId();

		    foreach($photos['photo'] as $photo)
		    {
		    	$photoActivity = new ActivityPhoto();

		    	$photoName = "activity".$activity_id.$photo_id.'.'.$photo->guessExtension();

		    	$photo_id++;

		    	$photo->move(
	                $this->getParameter('imgActivities'),
	                $photoName
	            );
		    	
		    	$photoActivity->setActivity($activity);
		    	$photoActivity->setPhoto($photoName);
		    	$photoActivity->setLove(0);

		    	$em->persist($photoActivity);
		   		$em->flush();
		    }

	        $this->addFlash(
	            'success',
	            'Votre activité a bien été ajouté !'
	        );

	        return $this->redirectToRoute('addActivity');
	    }

	    else if($form->isSubmitted() && !$form->isValid())
	    {
	    	$this->addFlash(
	            'error',
	            'Error !'
	        );
	    }

        if(strlen($user->getPrenom()) >= 8)
        {
            $words = explode(" ", $user->getPrenom());
            $initiales = '';
         
            foreach($words as $init){
                $initiales .= $init{0};
            }

            $user->setPrenom($initiales);
        }

		return $this->render('ActivitiesBundle::formActivity.html.twig', array(
				'form' => $form->createView(),
				'user' => $user
			));
		
	}

	/**
	 * @Route("closingVote/{activityIdea_id}", name="closingVote",
	 * 			defaults={"activityIdea_id": 1},
     *     		requirements={     *        		
     *         		"activityIdea_id": "\d+"
     *			})
	 */
	public function closingVoteAction(Request $request, $activityIdea_id){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.authorization_checker')->isGranted('ROLE_BDE')) {
             return $this->redirectToRoute('fos_user_security_login');
        }

        $em = $this->getDoctrine()->getManager();
        $repositoryActivitiesIdeas = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivityIdea');
        $repositoryActivitiesVotes = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivitiesVote');

		$activityIdea = $repositoryActivitiesIdeas->find($activityIdea_id);

		if (!$activityIdea) {
	        throw $this->createNotFoundException('L\'activité n\'existe pas !');
	    }
		
		$query = $repositoryActivitiesVotes->createQueryBuilder('av')
							->where('av.activity = :activity')
							->setParameter('activity', $activityIdea)
                            ->orderBy('av.vote', 'DESC')
                            ->getQuery();

        $bestDate = $query->setMaxResults(1)->getResult()[0]->getDate();

		$activity = new Activity();
		$activity->setName($activityIdea->getName());
		$activity->setDescription($activityIdea->getDescription());
		$activity->setDate($bestDate);

		$em->persist($activity);
		$em->flush();

		$activityVote = $repositoryActivitiesVotes->findByActivity($activityIdea_id);

		foreach($activityVote as $activity)
		{
			$em->remove($activity);
			$em->flush();
		}

		$em->remove($activityIdea);
		$em->flush();

        return $this->redirectToRoute('showActivitiesVote');
    }

    /**
    * @Route("showMentionsLegales", name="showMentionsLegales")
    */
    public function showMentionsLegalesAction()
    {
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
		
		return $this->render('ActivitiesBundle::mentionLegales.html.twig', array('user' => $user));
    }


    /**
	* @Route("likePhoto", name="likePhoto")
	*/
	public function likePhotoAction(){
		if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response("Erreur");
        }

        $em = $this->getDoctrine()->getManager();
		$request = $this->container->get('request');

		if($request->isXmlHttpRequest())
    	{
    		$photo_id = $request->query->get('photo_id');
		
			$repositoryActivitiesPhotos = $this->getDoctrine()->getRepository('ActivitiesBundle:ActivityPhoto');
			$photo = $repositoryActivitiesPhotos->find($photo_id);

			$like = $photo->getLove() + 1;

			$photo->setLove($like);

			$em->persist($photo);
			$em->flush();

			$data = ['like' => $like];

			$response = new Response(json_encode($data));
		    $response->headers->set('Content-Type', 'application/json');

		    return $response;
        }

        return new Response("Erreur");
	}

}
?>