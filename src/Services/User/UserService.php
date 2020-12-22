<?php


namespace App\Services\User;


use App\Entity\User;
use App\Entity\UserToken;
use App\Events\UserCreatedEvent;
use App\Events\UserForgotPasswordEvent;
use App\Repository\UserRepository;
use App\Services\Mailer\MailerServiceInterface;
use App\Services\UserToken\UserTokenServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


class UserService implements UserServiceInterface
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var UserTokenServiceInterface */
    private $userTokenService;
    /** @var EventDispatcherInterface */
    private $dispatcher;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var MailerServiceInterface */
    private $mailerService;
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $em,
        UserTokenServiceInterface $userTokenService,
        UserPasswordEncoderInterface $passwordEncoder,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $urlGenerator,
        MailerServiceInterface $mailerService
    ) {
        $this->em = $em;
        $this->userTokenService = $userTokenService;
        $this->passwordEncoder = $passwordEncoder;
        $this->dispatcher = $dispatcher;
        $this->urlGenerator = $urlGenerator;
        $this->mailerService = $mailerService;
    }

    /**
     * @return UserRepository
     */
    private function getRepository(): UserRepository
    {
        return $this->em->getRepository(User::class);
    }

    /**
     * @inheritDoc
     */
    public function getAllUser(array $options = []): array
    {
        return $this->getRepository()->getAllUser($options);
    }

    private function save(User $user): bool
    {
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function createUser(User $user): User
    {
        $user->setIsActive(true);
        $user->setIsConfirmed(false);

        $token = $this->userTokenService->createToken($user, false);

        $this->save($user);

        $event = new UserCreatedEvent($token);
        $this->dispatcher->dispatch($event, UserCreatedEvent::USER_CREATED);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function updateUser(User $user): bool
    {
        return $this->save($user);
    }

    /**
     * @inheritDoc
     */
    public function sendInvitationEmail(UserToken $token)
    {
        $activeUrl = $this->urlGenerator->generate('user_confirmation', ['token' => $token->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL);

        $data = [
            'username' => $token->getUser()->getFirstname().' '.$token->getUser()->getLastName(),
            'url' => $activeUrl,
        ];
        return $this->mailerService->sendEmailConfirmation($token->getUser()->getEmail(), $data);
    }

    /**
     * @inheritDoc
     */
    public function setConfirmation(User $user, string $password)
    {
        $this->setUserPassword($user, $password);
        $user->setIsConfirmed(true);
        $this->save($user);
    }

    /**
     * Set password for user
     *
     * @param User $user
     * @param string $password
     */
    public function setUserPassword(User $user, string $password)
    {
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $password
            )
        );
    }

    /**
     * @param string $email
     * @return User
     */
    public function findByEmail(string $email): User
    {
        return $this->getRepository()->findOneBy(['email' => $email]);
    }

    /**
     * @param User $user
     */
    public function forgotPassword(User $user)
    {
        $token = $this->userTokenService->create($user);
        $user->addUserToken($token);
        $this->em->persist($token);
        $this->save($user);

        $event = new UserForgotPasswordEvent($token);
        $this->dispatcher->dispatch($event, UserForgotPasswordEvent::NAME);
    }

    /**
     * @param UserToken $token
     */
    public function sendForgotPasswordEmail(UserToken $token)
    {
        $user = $token->getUser();
        $resetUrl = $this->urlGenerator->generate('reset_password', ['token' => $token->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL);
        $data = [
            'firstName' => $user->getFirstname(),
            'resetUrl' => $resetUrl,
        ];
        $this->mailerService->sendResetPasswordEmail($user->getEmail(), $data);
    }

    /**
     * @param UserToken $token
     * @param string $password
     */
    public function resetPassword(UserToken $token, string $password)
    {
        $user = $token->getUser();
        $this->setUserPassword($user, $password);
        $user->removeUserToken($token);
        $this->em->remove($token);
        $this->save($user);
    }

    /**
     * @param User $user
     * @param string|null $password
     */
    public function updateProfile(User $user, ?string $password = null)
    {
        if ($password) {
            $this->setUserPassword($user, $password);
        }
        $this->save($user);
    }

    /**
     * @param User $user
     * @param string $password
     */
    public function setPassword(User $user, string $password)
    {
        $this->setUserPassword($user, $password);
        $this->save($user);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(User $user, $isActive)
    {
        $user->setIsActive(!empty($isActive));
        $this->save($user);
    }

    /**
     * @inheritDoc
     */
    public function delete(User $user)
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
