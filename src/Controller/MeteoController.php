<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;

final class MeteoController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient, private SluggerInterface $slugger, private Security $security)
    {
    }

    #[Route('/api/meteo', name: 'app_meteo_by_user_town', methods: ['GET'])]
    public function getWeatherByUserTown(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Assurez-vous que l'entité User a une méthode getVille()
        $ville = $user->getVille();

        if (empty($ville)) {
            return new JsonResponse(['error' => 'Absence de ville dans le compte de cet utilisateur'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Supprimer les accents et les caractères spéciaux de la ville
        $cleanedVille = $this->slugger->slug($ville)->lower()->toString();

        // Récupérer la clé API et l'URL de base depuis les paramètres de l'application
        $apiKey = $this->getParameter('app.weatherapi_key');
        $apiUrlBase = $this->getParameter('app.weatherapi_base_url');

        $apiUrl = $apiUrlBase . "?key=" . $apiKey . "&q=" . $cleanedVille . "&lang=fr";

        $response = $this->httpClient->request(
            'GET',
            $apiUrl,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        $content = $response->toArray();

        // Retourner la réponse JSON avec le contenu de l'API météo et le statut de la réponse de l'API externe
        return new JsonResponse($content, $response->getStatusCode());
    }

    #[Route('/api/meteo/{ville}', name: 'app_meteo_by_town', methods: ['GET'])]
    public function getWeatherByTown(string $ville): JsonResponse
    {
        // Supprimer les accents et les caractères spéciaux de la ville pour éviter les problèmes d'encodage dans l'URL
        $cleanedVille = $this->slugger->slug($ville)->lower()->toString();

        // Récupérer la clé API depuis les paramètres de l'application
        $apiKey = $this->getParameter('app.weatherapi_key');

        // Construire l'URL de l'API avec la ville et la clé API
        $apiUrl = $this->getParameter('app.weatherapi_base_url')."?key=".$apiKey."&q=".$cleanedVille."&lang=fr";

        // Effectuer la requête HTTP vers l'API météo
        $response = $this->httpClient->request(
            'GET',
            $apiUrl,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        //$statusCode = $response->getStatusCode();

        // toArray() pour convertir la réponse JSON en tableau PHP
        $content = $response->toArray();

        // Retourner la réponse JSON avec le contenu de l'API météo
        return new JsonResponse($content, $response->getStatusCode());

    }
}
