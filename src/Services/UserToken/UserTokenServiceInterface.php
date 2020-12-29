<?php


namespace App\Services\UserToken;


use App\Entity\User;
use App\Entity\UserToken;
use DateTimeInterface;

interface UserTokenServiceInterface
{
    /**
     * @param User $user
     * @param bool $flush
     * @return UserToken
     */
    public function createToken(User $user, bool $flush = true): UserToken;

    /**
     * @param UserToken $token
     * @param bool $flush
     */
    public function delete(UserToken $token, bool $flush = true);

    /**
     * @param User $user
     * @return mixed
     */
    public function deleteOfUser(User $user);

    /**
     * Find UserToken by token
     * @param string $token
     * @return UserToken|null
     */
    public function findByToken(string $token): ?UserToken;

    /**
     * @param DateTimeInterface $date
     * @param $tokenExpiration
     * @return bool
     */
    public function isValidateDate(DateTimeInterface $date, $tokenExpiration): bool;
}
