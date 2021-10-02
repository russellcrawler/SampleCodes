<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use auth\models\UserInterface;

abstract class User_abstract extends My_Model implements UserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_CLASSIFIEDS_SELLER = 'ROLE_CLASSIFIEDS_SELLER';
    const ROLE_SELLER = 'ROLE_SELLER';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    protected $roles;
    protected $email;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        if (empty($this->roles)) {
            return [];
        }

        return unserialize($this->roles, ['allowed_classes' => false]);
    }

    public function setRoles(array $roles)
    {
        $this->roles = serialize($roles);

        return $this;
    }

    public function addRole(string $role)
    {
        $roles = $this->getRoles();

        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
        }

        return $this->setRoles($roles);
    }

    public function deleteRole(string $role)
    {
        $roles = $this->getRoles();

        if (($key = array_search($role, $roles, true)) !== false) {
            unset($roles[$key]);
            $roles = array_values($roles);
        }

        return $this->setRoles($roles);
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->getRoles();

        if (!is_array($roles)) {
            $roles = array($roles);
        }

        return in_array($role, $roles, true);
    }

    public function isUser(): bool
    {
        return $this->hasRole(self::ROLE_USER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN) || $this->hasRole(self::ROLE_SUPER_ADMIN);
    }
}