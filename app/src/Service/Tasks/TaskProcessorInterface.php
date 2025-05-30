<?php

namespace App\Service\Tasks;

use App\Entity\Task;

interface TaskProcessorInterface
{
    public function supports(string $type): bool;
    public function create(array $data): Task;
    public function process(Task $task): void;
}