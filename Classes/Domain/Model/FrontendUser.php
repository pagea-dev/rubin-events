<?php

declare(strict_types=1);

namespace PageaDev\RubinEvents\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
     * The element type is what makes Extbase map fe_users.image (TCA type "file") as a relation.
     *
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $image;

    public function __construct()
    {
        $this->image = new ObjectStorage();
    }

    public function initializeObject(): void
    {
        $this->image ??= new ObjectStorage();
    }

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

    /**
     * All images of this frontend user
     *
     * @return ObjectStorage<FileReference>
     */
    public function getImage(): ObjectStorage
    {
        return $this->image;
    }

    /**
     * First image, for templates that show a single portrait. ObjectStorage cannot be
     * accessed by index in Fluid, so the pick happens here.
     */
    public function getFirstImage(): ?FileReference
    {
        foreach ($this->image as $reference) {
            return $reference;
        }

        return null;
    }
}