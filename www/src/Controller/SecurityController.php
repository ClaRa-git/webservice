<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(AuthenticationUtils $authenticationUtils): JsonResponse
    {
        //on vérifie si l'utilisateur est déjà connecté
        if($this->getUser()){
            return new JsonResponse([
                'success' => true,
                'id' => $this->getUser()->getId(),
                'email' => $this->getUser()->getEmail(),
                'nickname' => $this->getUser()->getNickname(),
                'message' => 'Utilisateur déja en session'
            ]);
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        //si on a $error, on renvoie une erreur au front
        if($error){
            return new JsonResponse([
                'success' => false,
                'message' => 'Email et/ou mot de passe incorrect'
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $this->getUser()->getId(),
            'email' => $this->getUser()->getEmail(),
            'nickname' => $this->getUser()->getNickname(),
            'last_username' => $lastUsername,
            'error' => $error?->getMessage(),
            'message' => 'Connexion réussie'
        ]);
    }
}