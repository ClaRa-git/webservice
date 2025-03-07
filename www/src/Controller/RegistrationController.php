<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\Result\ValidEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        UserAuthenticator $authenticator,
        EntityManagerInterface $entityManager
    ): Response {
        //on récupère les datas envoyées par le front
        $data = json_decode($request->getContent(), true);

        //on vérifie si l'utilisateur existe déjà
        $existingUser = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);
        
        if ($existingUser) {
            return new JsonResponse([
                'success' => false,
                'message' => 'L\'email est déjà utilisé'
            ]);
        }

        //on vérifie si le nickname est déjà utilisé
        $existingNickname = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['nickname' => $data['nickname']]);
        
        if ($existingNickname) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le nickname est déjà utilisé'
            ]);
        }

        //on vérifie que l'email est bien un email
        $validEmail = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$validEmail) {
            return new JsonResponse([
                'success' => false,
                'message' => 'L\'email n\'est pas valide'
            ]);
        }

        //on vérifie que le mot de passe fait au moins 8 caractères, contient une majuscule, une minuscule et un chiffre
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre'
            ]);
        }

        //on crée un nouvel utilisateur
        $user = new User();
        //on lui set les paramètres
        $user->setEmail(strtolower($data['email']));
        $user->setNickname($data['nickname']);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $data['password']
            )
        );
        //on lui donne le paramètre de createdAt
        $user->setCreatedAt(time());
        //on persiste l'utilisateur
        $entityManager->persist($user);
        //on flush
        $entityManager->flush();

        //on retourne une réponse json
        return $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
    }

    #[Route('/check-password', name: 'app_check-password', methods: ['GET', 'POST'])]
    public function checkPassword(
        //on récupère les données du formulaire react
        Request $request,
        //on recupère le service d'encodage de mdp
        UserPasswordHasherInterface $userPasswordHasher,
        //on récupère le repo de User
        UserRepository $userRepository
    ): Response {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $password = $data['password'];
        $user = $userRepository->find($id);
        $isPasswordValid = $userPasswordHasher->isPasswordValid($user, $password);
        if ($isPasswordValid) {
            return $this->json([
                'message' => 'password is valid',
                'response' => true
            ]);
        } else {
            return $this->json([
                'message' => 'password is not valid',
                'response' => false
            ]);
        }
    }
}
