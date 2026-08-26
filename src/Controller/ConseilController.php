<?php

namespace App\Controller;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConseilRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conseil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ConseilController extends AbstractController
{
    // Route pour récupérer la liste des conseils
    #[Route('/api/conseils', name: 'app_conseil', methods: ['GET'])]
    public function getConseilList(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // Récupération de la liste des conseils depuis le repository
        $conseilList = $conseilRepository->findAll();

        // Sérialisation de la liste des conseils en JSON
        $jsonConseilList = $serializer->serialize($conseilList, 'json');

        // Retourne la liste des conseils sous forme de réponse JSON
        return new JsonResponse($jsonConseilList, Response::HTTP_OK, [], true);

    }

    // Route pour récupérer les conseils d'un mois donné
    #[Route('/api/conseil/{mois}', name: 'app_conseil_mois', methods: ['GET'])]
    public function getConseilsParMois(
        int $mois,
        ConseilRepository $conseilRepository,
        SerializerInterface $serializer
    ): JsonResponse {


        // 1. Gestion des cas d'erreur (Bad Request)
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(
                ['error' => 'Mois invalide. La valeur doit être comprise entre 1 et 12.'],
                JsonResponse::HTTP_BAD_REQUEST // Code HTTP 400 Bad Request
            );
        }

        // 2. Récupération des données via une méthode dédiée dans le repository
        $conseils = $conseilRepository->findByMois($mois);

        // 3. Sérialisation des conseils en JSON
        $jsonConseils = $serializer->serialize($conseils, 'json');

        // 4. Retour de la réponse
        // Si aucun conseil n'est trouvé, on renvoit un tableau vide avec un code 200
        return new JsonResponse($jsonConseils, JsonResponse::HTTP_OK, [], true);
    }

    // Route pour récupérer les conseils du mois courant
    #[Route('/api/conseil', name: 'app_conseil_mois_courant', methods: ['GET'])]
    public function getConseilMoisCourant(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // 1. Déterminer le mois courant
        $currentMonth = (int) date('n'); // 'n' retourne le mois au format numérique (1 à 12)

        // 2. Récupération des données via une méthode dédiée dans le repository
        $conseils = $conseilRepository->findByMois($currentMonth);

        // 3. Sérialisation des conseils en JSON
        $jsonConseils = $serializer->serialize($conseils, 'json');

        // 4. Retour de la réponse
        return new JsonResponse($jsonConseils, JsonResponse::HTTP_OK, [], true);
    }

    // Route pour récupérer les détails d'un conseil par son ID
    #[Route('/api/conseil/{id}', name: 'app_conseil_detail', methods: ['GET'])]
    public function getDetailConseil(Conseil $conseil, SerializerInterface $serializer): JsonResponse
    {
        if ($conseil) {
            $jsonConseil = $serializer->serialize($conseil, 'json');
            return new JsonResponse($jsonConseil, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    // Route pour créer un nouveau conseil
    #[Route('/api/conseil', name: 'app_conseil_create', methods: ['POST'])]
    // #[IsGranted('ROLE_ADMIN')] //
    public function createConseil(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {

        // Désérialisation des données JSON reçues dans la requête en un objet Conseil
        $conseil = $serializer->deserialize($request->getContent(), Conseil::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($conseil);

        // Si des erreurs sont trouvées, on retourne une réponse JSON avec le code HTTP 400 Bad Request
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Indique à l'EntityManager que l'on souhaite persister l'objet User dans la base de données
        $em->persist($conseil);

        // Sauvegarde de l'objet User dans la base de données
        $em->flush();

        // Sérialisation de l'objet User en JSON pour la réponse
        $jsonConseil = $serializer->serialize($conseil, 'json');

        // génération de l'URL pour accéder aux détails de l'utilisateur nouvellement créé
        $location = $urlGenerator->generate('app_conseil_detail', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        // retourne une réponse JSON avec le code HTTP 201 Created, l'URL de localisation et les données de l'utilisateur
        return new JsonResponse($jsonConseil, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    // Route pour mettre à jour un conseil existant
    #[Route('/api/conseil/{id}', name: 'app_conseil_update', methods: ['PUT'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function updateConseil(
        Request $request,
        Conseil $conseil, // Symfony va automatiquement récupérer le Conseil par son ID
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {

        // Désérialisation des données JSON sur l'objet Conseil existant
        // Cela permet de ne mettre à jour que les champs présents dans la requête
        $serializer->deserialize($request->getContent(), Conseil::class, 'json', ['object_to_populate' => $conseil]);

        // On vérifie les erreurs de validation après la mise à jour
        $errors = $validator->validate($conseil);

        // Si des erreurs sont trouvées, on retourne une réponse JSON avec le code HTTP 400 Bad Request
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Les modifications sont déjà suivies par l'EntityManager grâce à 'object_to_populate'
        // Il suffit de flusher pour sauvegarder les changements
        $em->flush();

        // Sérialisation de l'objet Conseil mis à jour en JSON pour la réponse
        $jsonConseil = $serializer->serialize($conseil, 'json');

        // Retourne une réponse JSON avec le code HTTP 200 OK et les données du conseil mis à jour
        return new JsonResponse($jsonConseil, Response::HTTP_OK, [], true);
    }
}
