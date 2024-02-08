<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataObjects\RegisterUserData;
use App\Entity\User;

interface UserProviderServiceInterface
{
    public function getById(int $userId): ?UserInterface;

    public function getByCredentials(array $credentials): ?UserInterface;

    public function createUser(RegisterUserData $data): UserInterface;

    public function verifyUser(User $user): void;

    public function updatePassword(UserInterface $user, string $password): void;
}