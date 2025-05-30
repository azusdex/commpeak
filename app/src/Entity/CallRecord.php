<?php

namespace App\Entity;

use App\Repository\CallRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallRecordRepository::class)]
class CallRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'callRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Task $task = null;

    #[ORM\Column]
    private ?int $customer_id = null;

    #[ORM\Column]
    private ?\DateTime $call_date = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(length: 20)]
    private ?string $dialed_number = null;

    #[ORM\Column(length: 20)]
    private ?string $source_ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip_continent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone_continent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): static
    {
        $this->task = $task;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customer_id;
    }

    public function setCustomerId(int $customer_id): static
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    public function getCallDate(): ?\DateTime
    {
        return $this->call_date;
    }

    public function setCallDate(\DateTime $call_date): static
    {
        $this->call_date = $call_date;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDialedNumber(): ?string
    {
        return $this->dialed_number;
    }

    public function setDialedNumber(string $dialed_number): static
    {
        $this->dialed_number = $dialed_number;

        return $this;
    }

    public function getSourceIp(): ?string
    {
        return $this->source_ip;
    }

    public function setSourceIp(string $source_ip): static
    {
        $this->source_ip = $source_ip;

        return $this;
    }

    public function getIpContinent(): ?string
    {
        return $this->ip_continent;
    }

    public function setIpContinent(string $ip_continent): static
    {
        $this->ip_continent = $ip_continent;

        return $this;
    }

    public function getPhoneContinent(): ?string
    {
        return $this->phone_continent;
    }

    public function setPhoneContinent(?string $phone_continent): static
    {
        $this->phone_continent = $phone_continent;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }
}
