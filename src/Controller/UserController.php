<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserController extends AbstractController
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    // Route pour créer un nouvel utilisateur
    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {

        // Désérialisation des données JSON reçues dans la requête en un objet User
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($user);

        // Si des erreurs sont trouvées, on retourne une réponse JSON avec le code HTTP 400 Bad Request
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Hashage du mot de passe avant de le stocker dans la base de données
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));

        // Indique à l'EntityManager que l'on souhaite persister l'objet User dans la base de données
        $em->persist($user);

        // Sauvegarde de l'objet User dans la base de données
        $em->flush();

        // Sérialisation de l'objet User en JSON pour la réponse
        $jsonUser = $serializer->serialize($user, 'json');

        // génération de l'URL pour accéder aux détails de l'utilisateur nouvellement créé
        $location = $urlGenerator->generate('app_user_detail', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        // retourne une réponse JSON avec le code HTTP 201 Created, l'URL de localisation et les données de l'utilisateur
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);

    }

    // Route pour récupérer les détails d'un utilisateur par son ID
    #[Route('/api/user/{id}', name: 'app_user_detail', methods: ['GET'])]
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        //
        if ($user) {
            //$context = SerializationContext::create()->setGroups(['getBooks']);
            $jsonUser = $serializer->serialize($user, 'json');
            // $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

}
