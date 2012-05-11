<?php

namespace Claroline\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Form\UserType;
use Claroline\CoreBundle\Library\Security\PlatformRoles;
use Claroline\CoreBundle\Library\Security\Acl\ClassIdentity;
use Claroline\CoreBundle\Entity\Resource\Repository;
//use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function newAction()
    {
        $this->checkAccess();
        
        $user = new User();
        $form = $this->get('form.factory')->create(new UserType(), $user);

        return $this->render(
            'ClarolineCoreBundle:Registration:form.html.twig', 
            array('form' => $form->createView())
        );
    }
    
    public function createAction()
    {
        $this->checkAccess();    
        $msg = null;
        $user = new User();
        $form = $this->get('form.factory')->create(new UserType(), $user);
        $form->bindRequest($this->get('request'));
        
        if ($form->isValid())
        {
            $em = $this->get('doctrine.orm.entity_manager');
            $userRole = $em->getRepository('Claroline\CoreBundle\Entity\Role')
                ->findOneByName(PlatformRoles::USER);
            $user->addRole($userRole);
            $repository = new Repository();
            $user->setRepository($repository);
            $em->persist($repository);
            $em->persist($user);
            $em->flush();
            
            $msg = $this->get('translator')->trans('account_created', array(), 'user');
            $this->getRequest()->getSession()->setFlash('notice', $msg);
        }

        return $this->render(
            'ClarolineCoreBundle:Registration:form.html.twig', 
            array('form' => $form->createView())
        );
    }
    
    private function checkAccess()
    {
        $securityContext = $this->get('security.context');
        $configHandler = $this->get('claroline.config.platform_config_handler');
        $isSelfRegistrationAllowed = $configHandler->getParameter('allow_self_registration');
        
        if (! $securityContext->getToken()->getUser() instanceof User && $isSelfRegistrationAllowed)
        {
            return;
        }
        
        if ($securityContext->isGranted('CREATE', ClassIdentity::fromDomainClass('Claroline\CoreBundle\Entity\User')))
        {
            return;
        }
        
        throw new AccessDeniedHttpException();
    }
}