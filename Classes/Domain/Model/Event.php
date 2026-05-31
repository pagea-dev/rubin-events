<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
namespace PageaDev\RubinEvents\Domain\Model;

use DateTime;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use PageaDev\RubinEvents\Domain\Model\FrontendUser;

final class Event extends AbstractEntity
{
    protected ?FrontendUser $creator = null;

    /**
     * @var ObjectStorage<FrontendUser>
     */
    protected ObjectStorage $contacts;

    protected string $description = '';

    protected ?DateTime $eventEnd = null;

    protected ?DateTime $eventStart = null;

    protected string $location = '';

    protected string $teaser = '';

    protected string $title = '';
    
    protected string $mapLocation = '';




    /**
     * Returns the creator of this event
     */
    public function getCreator(): ?FrontendUser
    {
        return $this->creator;
    }

    /**
     * Sets the creator of this event
     */
    public function setCreator(?FrontendUser $creator): void
    {
        $this->creator = $creator;
    }

    
    /**
     * Returns the full description of this event
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the full description of this event
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Returns the end date and time of this event
     *
     * @return DateTime|null
     */
    public function getEventEnd(): ?DateTime
    {
        return $this->eventEnd;
    }

    /**
     * Sets the end date and time of this event
     *
     * @param DateTime|null $eventEnd
     */
    public function setEventEnd(?DateTime $eventEnd): void
    {
        $this->eventEnd = $eventEnd;
    }

    /**
     * Returns the start date and time of this event
     *
     * @return DateTime|null
     */
    public function getEventStart(): ?DateTime
    {
        return $this->eventStart;
    }

    /**
     * Sets the start date and time of this event
     *
     * @param DateTime|null $eventStart
     */
    public function setEventStart(?DateTime $eventStart): void
    {
        $this->eventStart = $eventStart;
    }

    /**
     * Returns the name of the event location
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Sets the name of the event location
     *
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * Returns the short teaser text of this event
     *
     * @return string
     */
    public function getTeaser(): string
    {
        return $this->teaser;
    }

    /**
     * Sets the short teaser text of this event
     *
     * @param string $teaser
     */
    public function setTeaser(string $teaser): void
    {
        $this->teaser = $teaser;
    }

    /**
     * Returns the title of this event
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of this event
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMapLocation(): string
    {
        return $this->mapLocation;
    }

    public function setMapLocation(string $mapLocation): void
    {
        $this->mapLocation = $mapLocation;
    }

    /**
     * Returns latitude parsed from map_location
     */
    public function getLat(): ?float
    {
        if (empty($this->mapLocation)) {
            return null;
        }
        $parts = explode(',', $this->mapLocation);
        return isset($parts[0]) ? (float)$parts[0] : null;
    }

    /**
     * Returns longitude parsed from map_location
     */
    public function getLon(): ?float
    {
        if (empty($this->mapLocation)) {
            return null;
        }
        $parts = explode(',', $this->mapLocation);
        return isset($parts[1]) ? (float)$parts[1] : null;
    }


    /**
     * Returns all contacts
     */
    public function getContacts(): ObjectStorage
    {
        return $this->contacts;
    }

    /**
     * Sets all contacts
     */
    public function setContacts(ObjectStorage $contacts): void
    {
        $this->contacts = $contacts;
    }

    /**
     * Adds a single contact
     */
    public function addContact(FrontendUser $contact): void
    {
        $this->contacts->attach($contact);
    }

    /**
     * Removes a single contact
     */
    public function removeContact(FrontendUser $contact): void
    {
        $this->contacts->detach($contact);
    }



    public function __construct()
    {
        $this->contacts  = new ObjectStorage();
        $this->eventEnd  = new DateTime();
        $this->eventStart = new DateTime();
    }

    public function initializeObject(): void
    {
        $this->contacts  ??= new ObjectStorage();
        $this->eventEnd  ??= new DateTime();
        $this->eventStart ??= new DateTime();
    }
}