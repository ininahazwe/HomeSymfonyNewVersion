<?php

namespace App\Entity;

use App\Repository\AuthLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AuthLogRepository::class)
 * @ORM\Table(name="auth_logs")
 */
class AuthLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $authAttemptAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userIP;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $emailEntered;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSuccessfulAuth;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $startOfBlackListing;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $endOfBlackListing;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isRememberMeAuth;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deAuthenticatedAt;

    public function __construct(string $emailEntered, ?string $userIP)
    {
        $this->authAttemptAt = new \DateTimeImmutable('now');
        $this->emailEntered = $emailEntered;
        $this->isRememberMeAuth = false;
        $this->userIP = $userIP;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthAttemptAt(): ?\DateTimeImmutable
    {
        return $this->authAttemptAt;
    }

    public function setAuthAttemptAt(\DateTimeImmutable $authAttemptAt): self
    {
        $this->authAttemptAt = $authAttemptAt;

        return $this;
    }

    public function getUserIP(): ?string
    {
        return $this->userIP;
    }

    public function setUserIP(?string $userIP): self
    {
        $this->userIP = $userIP;

        return $this;
    }

    public function getEmailEntered(): ?string
    {
        return $this->emailEntered;
    }

    public function setEmailEntered(string $emailEntered): self
    {
        $this->emailEntered = $emailEntered;

        return $this;
    }

    public function getIsSuccessfulAuth(): ?bool
    {
        return $this->isSuccessfulAuth;
    }

    public function setIsSuccessfulAuth(bool $isSuccessfulAuth): self
    {
        $this->isSuccessfulAuth = $isSuccessfulAuth;

        return $this;
    }

    public function getStartOfBlackListing(): ?\DateTimeImmutable
    {
        return $this->startOfBlackListing;
    }

    public function setStartOfBlackListing(?\DateTimeImmutable $startOfBlackListing): self
    {
        $this->startOfBlackListing = $startOfBlackListing;

        return $this;
    }

    public function getEndOfBlackListing(): ?\DateTimeImmutable
    {
        return $this->endOfBlackListing;
    }

    public function setEndOfBlackListing(?\DateTimeImmutable $endOfBlackListing): self
    {
        $this->endOfBlackListing = $endOfBlackListing;

        return $this;
    }

    public function getIsRememberMeAuth(): ?bool
    {
        return $this->isRememberMeAuth;
    }

    public function setIsRememberMeAuth(bool $isRememberMeAuth): self
    {
        $this->isRememberMeAuth = $isRememberMeAuth;

        return $this;
    }

    public function getDeAuthenticatedAt(): ?\DateTimeImmutable
    {
        return $this->deAuthenticatedAt;
    }

    public function setDeAuthenticatedAt(?\DateTimeImmutable $deAuthenticatedAt): self
    {
        $this->deAuthenticatedAt = $deAuthenticatedAt;

        return $this;
    }
}
