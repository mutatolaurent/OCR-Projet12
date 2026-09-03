<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiLogSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $apiLogger;

    // L'injection du logger spécifique se fait via le nom de la variable (canal 'api' = $apiLogger)
    public function __construct(LoggerInterface $apiLogger)
    {
        $this->apiLogger = $apiLogger;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Filtrer pour ne logger que les routes de l'API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // 1. Récupérer le timestamp de début exact (en microsecondes)
        $startTime = $request->server->get('REQUEST_TIME_FLOAT');

        // 2. Calculer le temps d'exécution en millisecondes
        $durationMs = $startTime ? round((microtime(true) - $startTime) * 1000, 2) : null;

        $logData = [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'payload' => $request->getContent() ? json_decode($request->getContent(), true) : null,
            // Décommentez pour logger les headers si besoin
            'headers' => $request->headers->all(),
        ];

        $message = sprintf('API Call: %s %s', $request->getMethod(), $request->getRequestUri());

        // On choisit le niveau de log selon le code HTTP
        if ($response->getStatusCode() >= 500) {
            $this->apiLogger->error($message, $logData);
        } elseif ($response->getStatusCode() >= 400) {
            $this->apiLogger->warning($message, $logData);
        } else {
            $this->apiLogger->info($message, $logData);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }
}
