<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    // méthode appelée lorsqu'une exception est levée dans l'application
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // Récupération de l'exception levée
        $exception = $event->getThrowable();

        // Vérification si l'exception est une instance de HttpException
        if ($exception instanceof HttpException) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];

            // Création d'une réponse JSON avec les données de l'exception
            $event->setResponse(new JsonResponse($data));
        } else {
            $data = [
                'status' => 500, // Le status n'existe pas car ce n'est pas une exception HTTP, donc on met 500 par défaut.
                'message' => $exception->getMessage()
            ];

            // Création d'une réponse JSON avec les données de l'exception
            $event->setResponse(new JsonResponse($data));
        }
    }


    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent',
        ];
    }
}
