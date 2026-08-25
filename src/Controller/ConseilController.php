<?php

namespace App\Controller;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConseilRepository;
use Symfony\Component\Serializer\SerializerInterface;

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
}
