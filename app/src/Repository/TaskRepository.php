<?php

namespace App\Repository;

use App\Entity\Task;
use App\Service\TaskRunnerService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findUploadTasks(): array
    {
        $tasks = $this->findBy(['type' => TaskRunnerService::TASK_UPLOAD_FILE], ['created_at' => 'DESC']);

        return array_map(fn($task) => [
            'id'          => $task->getId(),
            'filename'     => $task->getData()['original_filename'],
            'status'      => $task->getStatus(),
            'result'      => $task->getResult() ?? [],
            'total_lines' => $task->getData()['total_lines'] ?? 0
        ], $tasks);
    }



}