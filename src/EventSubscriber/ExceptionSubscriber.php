<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as KernelHttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface as HttpClientExceptionInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private string $appEnv;

    // On injecte l'environnement pour savoir si on est en dev ou en prod
    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
    }

    // méthode appelée lorsqu'une exception est levée dans l'application
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // Récupération de l'exception levée
        $exception = $event->getThrowable();

        // 1. Gestion des exceptions HTTP natives de Symfony (404, 403, 400...)
        if ($exception instanceof KernelHttpExceptionInterface) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];

            // On crée une réponse JSON avec le code de statut et les headers de l'exception
            $event->setResponse(new JsonResponse(
                $data,
                $exception->getStatusCode(),
                $exception->getHeaders() // Important : on transmet les headers de l'exception
            ));
            return;
        }

        // 2. Gestion des exceptions liées aux appels API externes (HttpClient 4xx et 5xx)
        if ($exception instanceof HttpClientExceptionInterface) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();

            // On s'assure d'avoir un tableau, même si le format reçu de l'API externe n'est pas du JSON
            try {
                $content = $response->toArray(false);
            } catch (\Exception $e) {
                $content = ['message' => 'Erreur inattendue de l\'API externe.'];
            }

            // On crée une réponse JSON avec le code de statut et le contenu de l'API externe
            $event->setResponse(new JsonResponse($content, $statusCode));
            return;
        }

        // 3. Fallback (Erreurs 500 : Base de données, erreurs fatales de code, etc.)
        // Sécurité : On ne révèle le vrai message de l'exception qu'en mode "dev"
        $message = $this->appEnv === 'dev'
            ? $exception->getMessage()
            : 'Une erreur interne est survenue.';

        $data = [
            'status' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $message
        ];

        $event->setResponse(new JsonResponse($data, JsonResponse::HTTP_INTERNAL_SERVER_ERROR));

    }


    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent',
        ];
    }
}
