<?php


namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension
 * @package App\Twig
 */
class AppExtension extends AbstractExtension
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /** @var Security */
    private $security;

    /**
     * UserExtension constructor.
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(Security $security, ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        $this->security = $security;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('appVersion', [$this, 'appVersion']),
            new TwigFunction('username', [$this, 'username']),
            new TwigFunction('userImage', [$this, 'userImage']),
        ];
    }

    public function appVersion()
    {
        return $this->parameterBag->get('app.version');
    }

    public function username()
    {
        $user = $this->security->getUser();
        if (empty($user)) {
            return 'Guest';
        }
        return $user->getFirstName().' '.$user->getLastName();
    }

    public function userImage()
    {
        $user = $this->security->getUser();
        $email = (!empty($user)) ? $user->getUsername() : '';
        $default = 'mp';
        $url = 'https://www.gravatar.com/avatar';
        $email = md5(strtolower(trim($email)));
        $size = 40;

        return "{$url}/{$email}?s={$size}&d={$default}";
    }
}
