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

    public function findTasksStatus(): array
    {
        $tasks = $this->findBy(['type' => TaskRunnerService::TASK_UPLOAD_FILE], ['created_at' => 'DESC']);

        $results = [];

        foreach ($tasks as $task) {
            $res = [
                'id' => $task->getId(),
                'filename' => $task->getData()['original_filename'],
                'status' => [
                    'upload' => [
                        'status'      => $task->getStatus(),
                        'total_lines' => $task->getData()['total_lines'] ?? 0,
                        'processed'   => $task->getResult()['processed'] ?? 0,
                        'added'       => $task->getResult()['added'] ?? 0,
                        'skipped'     => $task->getResult()['skipped'] ?? 0,
                    ],
                ]
            ];

            if ($task->getStatus() === Task::STATUS_FINISHED) {
                $child_task = $this->findOneBy(['parent_task' => $task]);

                if ($child_task) {
                    $res['status']['stats'] = [
                        'status'           => $child_task->getStatus(),
                        'total_lines'      => $child_task->getResult()['total_lines'] ?? 0,
                        'calculated_lines' => $child_task->getResult()['calculated_lines'] ?? 0,
                    ];

                    $res['stats_task_id'] = $child_task->getId();
                }
            }

            $results[] = $res;
        }

        return $results;
    }
}