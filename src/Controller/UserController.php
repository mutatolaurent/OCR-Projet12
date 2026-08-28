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
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            //$context = SerializationContext::create()->setGroups(['getUsers']);
            $jsonUser = $serializer->serialize($user, 'json');
            // $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    // Route pour mettre à jour un utilisateur existant
    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un utilisateur')]
    public function updateUser(
        Request $request,
        User $user, // Symfony va automatiquement récupérer le User par son ID
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher // Injecter le service de hachage de mot de passe
    ): JsonResponse {

        // Stocker le mot de passe actuel s'il existe et si la requête n'en fournit pas un nouveau
        // Ceci est important car la désérialisation pourrait écraser un mot de passe haché par null si non fourni
        $currentPassword = $user->getPassword();

        // Désérialisation des données JSON sur l'objet User existant
        // Cela permet de ne mettre à jour que les champs présents dans la requête
        $serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $user]);

        // Si un nouveau mot de passe est fourni dans la requête, il est haché et mis à jour.
        // Sinon, l'ancien mot de passe est conservé.
        $plainPassword = $request->toArray()['password'] ?? null;
        if ($plainPassword) {
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
        } else {
            $user->setPassword($currentPassword);
        }

        // On vérifie les erreurs de validation après la mise à jour
        $errors = $validator->validate($user);

        // Si des erreurs sont trouvées, on retourne une réponse JSON avec le code HTTP 400 Bad Request
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Les modifications sont déjà suivies par l'EntityManager grâce à 'object_to_populate'
        // Il suffit de flusher pour sauvegarder les changements
        $em->flush();

        // Sérialisation de l'objet User mis à jour en JSON pour la réponse
        $jsonUser = $serializer->serialize($user, 'json');

        // Retourne une réponse JSON avec le code HTTP 200 OK et les données de l'utilisateur mis à jour
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    // Route pour supprimer un User par son ID
    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    public function deleteUser(
        User $user, // Symfony va automatiquement récupérer le Conseil par son ID
        EntityManagerInterface $em
    ): JsonResponse {

        // Supprime l'entité Conseil de la base de données
        $em->remove($user);
        $em->flush();

        // Retourne une réponse vide avec le code HTTP 204 No Content pour indiquer une suppression réussie
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
