<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Form\RegistrationType;
use App\Entity\User;

class SecurityController extends AbstractController
{

    /*---------------------------
    |   PORTAIL D'INSCRIPTION   |
    ---------------------------*/

    /**
     * @Route("/inscription", name="security_registration")
     */
    public function registration(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder)
    {
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Cryptage du mot de passe avant persistence ->
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash);

            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('security_login');
        }

        return $this->render('security/registration.html.twig', [
            'form' => $form->createView()
        ]);
    } // EO registration

    /*----------------------------
    |    PORTAIL DE CONNEXION    |
    ----------------------------*/

    /**
     * @Route("/connexion", name="security_login")
     */
    public function login()
    {
        return $this->render('security/login.html.twig');
    } // EO login

    /*----------------------------
    |        DECONNEXION         |
    ----------------------------*/

    /**
     * @Route("/deconnexion", name="security_logout")
     */
    public function logout()
    {
    } // EO logout

}// EO SecurityController
