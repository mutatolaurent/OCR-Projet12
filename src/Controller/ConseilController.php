<?php

/**
 * ConseilController.php
 *
 * Ce fichier contient le contrôleur pour gérer les requêtes liées aux conseils.
 * Il fournit des routes pour récupérer la liste des conseils, les conseils par mois,
 * les détails d'un conseil, ainsi que pour créer, mettre à jour et supprimer des conseils.
 *
 * @package App\Controller
 */

namespace App\Controller;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConseilRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conseil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Conseils', description: 'API pour gérer les conseils de jardinage')]
final class ConseilController extends AbstractController
{
    /**
     * Récupère la liste des conseils.
     *
     * @param ConseilRepository $conseilRepository Le repository pour accéder aux données des conseils.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir les objets en JSON.
     * @return JsonResponse La réponse JSON contenant la liste des conseils.
     */
    #[Route('/api/conseils', name: 'app_conseil', methods: ['GET'])]
    public function getConseilList(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // Récupération de la liste des conseils depuis le repository
        $conseilList = $conseilRepository->findAll();

        // Sérialisation de la liste des conseils en JSON
        $jsonConseilList = $serializer->serialize($conseilList, 'json');

        // Retourne la liste des conseils sous forme de réponse JSON
        return new JsonResponse($jsonConseilList, JsonResponse::HTTP_OK, [], true);

    }

    /**
     * Récupère les conseils d'un mois donné.
     *
     * @param int $mois Le mois pour lequel récupérer les conseils (1 à 12).
     * @param ConseilRepository $conseilRepository Le repository pour accéder aux données des conseils.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir les objets en JSON.
     * @return JsonResponse La réponse JSON contenant les conseils du mois spécifié ou un message d'erreur si le mois est invalide.
     */
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

    /**
     * Récupère les conseils du mois courant.
     *
     * @param ConseilRepository $conseilRepository Le repository pour accéder aux données des conseils.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir les objets en JSON.
     * @return JsonResponse La réponse JSON contenant les conseils du mois courant.
     */
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

    /**
     * Récupère les détails d'un conseil par son ID.
     *
     * @param Conseil $conseil L'entité Conseil récupérée automatiquement par Symfony grâce à l'ID passé dans l'URL.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir l'objet en JSON.
     * @return JsonResponse La réponse JSON contenant les détails du conseil ou un message d'erreur si le conseil n'est pas trouvé.
     */
    #[Route('/api/conseil/{id}', name: 'app_conseil_detail', methods: ['GET'])]
    public function getDetailConseil(Conseil $conseil, SerializerInterface $serializer): JsonResponse
    {
        if ($conseil) {
            $jsonConseil = $serializer->serialize($conseil, 'json');
            return new JsonResponse($jsonConseil, JsonResponse::HTTP_OK, [], true);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Crée un nouveau conseil.
     *
     * @param Request $request La requête HTTP contenant les données du conseil à créer.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir les objets en JSON.
     * @param EntityManagerInterface $em L'EntityManager pour gérer la persistance des entités.
     * @param UrlGeneratorInterface $urlGenerator Le générateur d'URL pour créer l'URL de localisation du nouveau conseil.
     * @param ValidatorInterface $validator Le service de validation pour vérifier les contraintes de l'entité Conseil.
     * @return JsonResponse La réponse JSON contenant le conseil créé ou les erreurs de validation.
     */
    #[OA\Response(
        response: 201,
        description: 'Crée un nouveau conseil',
        content: new Model(type: Conseil::class)
    )]
    #[OA\RequestBody(
        description: 'Données du nouveau conseil à créer',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'texte', type: 'string', example: 'Conseil de jardinage pour le mois de...'),
                new OA\Property(property: 'mois', type: 'array', items: new OA\Items(type: 'integer', format: 'int32'), example: '[1, 2, 3]'),
            ],
            type: 'object',
            required: ['texte', 'mois'] // les champs obligatoires
        )
    )]
    #[Route('/api/conseil', name: 'app_conseil_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
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
        return new JsonResponse($jsonConseil, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Met à jour un conseil existant.
     *
     * @param Request $request La requête HTTP contenant les données du conseil à mettre à jour.
     * @param Conseil $conseil L'entité Conseil récupérée automatiquement par Symfony grâce à l'ID passé dans l'URL.
     * @param SerializerInterface $serializer Le service de sérialisation pour convertir les objets en JSON.
     * @param EntityManagerInterface $em L'EntityManager pour gérer la persistance des entités.
     * @param ValidatorInterface $validator Le service de validation pour vérifier les contraintes de l'entité Conseil.
     * @return JsonResponse La réponse JSON contenant le conseil mis à jour ou les erreurs de validation.
     */
    #[OA\Response(
        response: 200,
        description: 'Met à jour un conseil existant',
        content: new Model(type: Conseil::class)
    )]
    #[OA\RequestBody(
        description: 'Données du conseil à mettre à jour',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'texte', type: 'string', example: 'Nouveau texte pour le conseil...'),
                new OA\Property(property: 'mois', type: 'array', items: new OA\Items(type: 'integer', format: 'int32'), example: '[4, 5]'),
            ],
            type: 'object',
            //required: ['texte', 'mois'] // les champs obligatoires
        )
    )]
    #[Route('/api/conseil/{id}', name: 'app_conseil_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un conseil')]
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
        return new JsonResponse($jsonConseil, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Supprime un conseil existant.
     *
     * @param Conseil $conseil L'entité Conseil récupérée automatiquement par Symfony grâce à l'ID passé dans l'URL.
     * @param EntityManagerInterface $em L'EntityManager pour gérer la persistance des entités.
     * @return JsonResponse La réponse JSON indiquant le succès de la suppression ou un message d'erreur si le conseil n'est pas trouvé.
     */
    #[Route('/api/conseil/{id}', name: 'app_conseil_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteConseil(
        Conseil $conseil, // Symfony va automatiquement récupérer le Conseil par son ID
        EntityManagerInterface $em
    ): JsonResponse {

        // Supprime l'entité Conseil de la base de données
        $em->remove($conseil);
        $em->flush();

        // Retourne une réponse vide avec le code HTTP 204 No Content pour indiquer une suppression réussie
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
