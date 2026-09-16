<?php

/**
 * MeteoController.php
 *
 * Ce fichier contient le contrôleur pour gérer les requêtes liées à la météo.
 * Il fournit des routes pour récupérer la météo en fonction de la ville de l'utilisateur connecté,
 * ainsi que pour récupérer la météo d'une ville spécifique et pour supprimer le cache météo.
 *
 * @package App\Controller
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[Route('/api/meteo', name: 'api_meteo_')] // Préfixe de route au niveau de la classe
#[OA\Tag(name: 'Météo', description: 'API pour gérer les données météorologiques')]
final class MeteoController extends AbstractController
{
    /**
     * MeteoController constructor.
     *
     * @param HttpClientInterface $httpClient Le client HTTP pour effectuer les requêtes vers l'API météo.
     * @param SluggerInterface $slugger Le service de slugging pour nettoyer les noms de villes.
     * @param Security $security Le service de sécurité pour récupérer l'utilisateur connecté.
     * @param string $weatherApiKey La clé API pour accéder à l'API météo.
     * @param string $weatherApiBaseUrl L'URL de base de l'API météo.
     * @param int $weatherApiCacheTtl La durée de vie du cache pour les réponses de l'API météo (en secondes).
     * @param string $weatherApiCacheTag Le tag pour le cache des réponses de l'API météo.
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private SluggerInterface $slugger,
        private Security $security,
        private string $weatherApiKey,
        private string $weatherApiBaseUrl,
        private int $weatherApiCacheTtl,
        private string $weatherApiCacheTag
    ) {
    }

    /**
     * Récupère les données météo pour une ville donnée ou pour la ville de l'utilisateur connecté.
     *
     * @param TagAwareCacheInterface $cachePool Le pool de cache pour stocker les réponses de l'API.
     * @param string|null $ville La ville pour laquelle récupérer les données météo (optionnel).
     * @return JsonResponse La réponse JSON contenant les données météo et le statut de la réponse de l'API externe.
     */
    #[Route('/{ville}', name: 'by_town', methods: ['GET'])]
    #[Route('', name: 'by_user_town', methods: ['GET'])]
    public function getWeatherByTown(?string $ville, TagAwareCacheInterface $cachePool): JsonResponse
    {
        // Si la ville n'est pas spécifiée dans l'URL, la récupérer depuis l'utilisateur connecté
        if (null === $ville) {
            $user = $this->security->getUser();

            if (!$user instanceof User) {
                return new JsonResponse(['error' => 'Utilisateur non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $ville = $user->getVille();
            if (empty($ville)) {
                return new JsonResponse(['error' => 'Absence de ville dans le compte de cet utilisateur'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Supprimer les accents et les caractères spéciaux de la ville
        $cleanedVille = $this->slugger->slug($ville)->lower()->toString();

        // Générer une clé de cache unique pour la ville
        $idCache = 'meteo_' . $cleanedVille;

        // Construire l'URL complète de l'API avec la ville et la clé API
        $apiUrl = $this->weatherApiBaseUrl . "?key=" . $this->weatherApiKey . "&q=" . $cleanedVille . "&lang=fr";

        // Utiliser le cache pour stocker la réponse de l'API météo
        $isHit = true;
        $content = $cachePool->get($idCache, function (ItemInterface $item) use ($apiUrl, &$isHit) {
            $isHit = false;

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

            // Stocker le statut de la réponse et le contenu de l'API dans le cache
            $rep = [];
            $rep['status'] = $response->getStatusCode();
            $rep['content'] = $response->toArray();

            // Ajouter un tag pour le cache afin de pouvoir l'invalider facilement si nécessaire
            $item->tag($this->weatherApiCacheTag);

            // Définir la durée de vie du cache
            $item->expiresAfter($this->weatherApiCacheTtl);

            // Retourner le tableau contenant le statut et le contenu de l'API
            return $rep;
        });

        // Créer une réponse JSON avec le contenu de l'API et le statut HTTP approprié
        $response = new JsonResponse($content['content'], $content['status']);

        // Ajoute un en-tête personnalisé pour indiquer si la réponse provient du cache ou non
        $response->headers->set('X-Cache', $isHit ? 'HIT' : 'MISS');

        // Directives Cache-Control pour LE CLIENT
        $response->setCache([
            'max_age'       => $this->weatherApiCacheTtl, // Le client peut cacher la réponse pendant $weatherApiCacheTtl secondes
            's_maxage'      => $this->weatherApiCacheTtl, // Les proxys/CDN peuvent aussi cacher la réponse pendant $weatherApiCacheTtl secondes
            'public'        => true, // Permet la mise en cache par tout le monde
        ]);

        return $response;
    }

    /**
     * Supprime le cache météo pour toutes les villes en utilisant le tag de cache.
     *
     * @param TagAwareCacheInterface $cachePool Le pool de cache pour invalider le cache.
     * @return JsonResponse Une réponse JSON indiquant le succès de l'opération.
     */
    // #[Route('/api/meteo', name: 'app_meteo_delete_cache', methods: ['DELETE'])]
    #[Route('/admin', name: 'delete_cache', methods: ['DELETE'])] // La route finale sera /api/meteo
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour vider le cache météo')]
    public function deleteTownCache(TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags([$this->weatherApiCacheTag]);

        return new JsonResponse(['message' => 'Cache météo vidé avec succès'], JsonResponse::HTTP_NO_CONTENT);
    }


}
