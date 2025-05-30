<?php

namespace App\Entity;

use App\Repository\CallStatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallStatRepository::class)]
class CallStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $customer_id = null;

    #[ORM\Column]
    private ?int $same_calls = null;

    #[ORM\Column]
    private ?int $same_duration = null;

    #[ORM\Column]
    private ?int $total_calls = null;

    #[ORM\Column]
    private ?int $total_duration = null;

    #[ORM\ManyToOne(targetEntity: Task::class)]
    private ?Task $task = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSameCalls(): ?int
    {
        return $this->same_calls;
    }

    public function setSameCalls(int $same_calls): static
    {
        $this->same_calls = $same_calls;

        return $this;
    }

    public function getSameDuration(): ?int
    {
        return $this->same_duration;
    }

    public function setSameDuration(int $same_duration): static
    {
        $this->same_duration = $same_duration;

        return $this;
    }

    public function getTotalCalls(): ?int
    {
        return $this->total_calls;
    }

    public function setTotalCalls(int $total_calls): static
    {
        $this->total_calls = $total_calls;

        return $this;
    }

    public function getTotalDuration(): ?int
    {
        return $this->total_duration;
    }

    public function setTotalDuration(int $total_duration): static
    {
        $this->total_duration = $total_duration;

        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }
}
