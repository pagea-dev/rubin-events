<?php

declare(strict_types=1);

namespace PageaDev\RubinEvents\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Minimal FrontendUser model mapped to fe_users table
 */
class FrontendUser extends AbstractEntity
{
    // Map this model to the fe_users table
    protected string $username = '';
    protected string $name = '';
    protected string $firstName = '';
    protected string $lastName = '';
    protected string $email = '';

    /**
     * Returns the login username of this frontend user
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the full display name of this frontend user
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the first name of this frontend user
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Returns the last name of this frontend user
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Returns the email address of this frontend user
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}