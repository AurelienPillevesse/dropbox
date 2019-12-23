<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use AppBundle\Entity\User;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;

/**
 * Controller managing the user profile.
 */
class ProfileController extends Controller
{
    /**
     * @return Response
     *
     * Show user connected informations
     *
     */
    public function showAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        $pieChart = new PieChart();
        $usedSpace = $user ->getUsedStorage();
        $freeSpace = User::MAX_STORAGE_ALLOWED - $usedSpace;

        $freeSpaceRounded = $this->getSizeName($freeSpace);

        $pieChart->getData()->setArrayToDataTable([
            ['Info', 'Space'],
            ['Free', $freeSpace],
            ['Used', $usedSpace]
        ]);
        $pieChart->getOptions()->setIs3d(true);
        $pieChart->getOptions()->setPieSliceText('percentage');
        $pieChart->getOptions()->setHeight(200);
        $pieChart->getOptions()->setWidth(600);

        return $this->render('@FOSUser/Profile/show.html.twig', array(
            'user' => $user,
            'piechart' => $pieChart,
            'freeSpaceRounded' => $freeSpaceRounded
        ));
    }

    /**
     * Edit the user connected.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        /** @var $formFactory FactoryInterface */
        $formFactory = $this->get('fos_user.profile.form.factory');

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $userManager UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('fos_user_profile_show');
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        return $this->render('@FOSUser/Profile/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param $octet
     * @return string
     *
     * Octet converter function
     *
     */
    public function getSizeName($octet)
    {
        $unite = array('Octet','Ko','Mo','Go');

        if ($octet < 1000)
        {
            return $octet.' '.$unite[0];
        }
        elseif ($octet < 1000000)
        {
            $ko = round($octet/1024,2);
            return $ko.' '.$unite[1];
        }
        elseif ($octet < 1000000000)
        {
            $mo = round($octet/(1024*1024),2);
            return $mo.' '.$unite[2];
        }
        else
        {
            $go = round($octet/(1024*1024*1024),2);
            return $go.' '.$unite[3];
        }
    }

}
