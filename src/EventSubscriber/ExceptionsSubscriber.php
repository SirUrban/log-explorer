<?php


namespace App\EventSubscriber;

use App\Constant\ErrorCodeConstant;
use App\Events\UserCreatedEvent;
use App\Services\User\UserServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionsSubscriber implements EventSubscriberInterface
{
    /** @var UserServiceInterface */
    private $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $uri = $event->getRequest()->getRequestUri();
        $error = $event->getThrowable();

        if ($error instanceof AccessDeniedException) {
            if (substr($uri, 0, 5) === '/api/') {
                $event->setResponse(new JsonResponse([
                    'error' => ErrorCodeConstant::ERROR_PERMISSION_DENIED,
                    'message' => 'Access denied',
                ], 200));
                $event->allowCustomResponseCode();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 100],
        ];
    }
}
