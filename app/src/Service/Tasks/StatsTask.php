<?php

namespace App\Service\Tasks;

use App\Entity\CallRecord;
use App\Entity\CallStat;
use App\Entity\Task;
use App\Service\Resolver\IpContinentResolver;
use App\Service\Resolver\PhoneContinentResolver;
use App\Service\TaskRunnerService;
use Doctrine\ORM\EntityManagerInterface;

class StatsTask implements TaskProcessorInterface
{
    const BATCH_SIZE = 1000;

    public function __construct(
        private EntityManagerInterface $em,
        private IpContinentResolver    $ip_resolver,
        private PhoneContinentResolver $phone_resolver,
        private readonly string        $app_dir,
    ) {}

    public function supports(string $type): bool
    {
        return $type === TaskRunnerService::TASK_STATS;
    }

    public function create(array $data): Task
    {
        if (!isset($data['parent_task'])) {
            throw new \InvalidArgumentException('Aggregate task expects parent_task_id.');
        }

        $task = new Task();
        $task->setType(TaskRunnerService::TASK_STATS);
        $task->setStatus(Task::STATUS_PENDING);
        $task->setParentTask($data['parent_task']);
        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    public function process(Task $task): void
    {
        $parent_task = $task->getParentTask();
        if (!$parent_task) {
            throw new \RuntimeException('Missing parent_task');
        }

        $results = ['total_lines' => $parent_task->getResult()['added'] ?? 0, 'calculated_lines' => 0];
        $offset = 0;

        $repo = $this->em->getRepository(CallRecord::class);
//        $records = $repo->findBy(['task' => $parent_task], null, self::BATCH_SIZE);

        do {
            $records = $repo->findBy(['task' => $parent_task], null, self::BATCH_SIZE, $offset);

            $count = count($records);
            if ($count === 0) break;

            $stats = [];

            foreach ($records as $record) {
                $customer_id = $record->getCustomerId();
                $ip_continent = $this->ip_resolver->resolve($record->getSourceIp());
                $phone_continent = $this->phone_resolver->resolve($record->getDialedNumber());

                $same = ($ip_continent === $phone_continent);

                if (!isset($stats[$customer_id])) {
                    $stats[$customer_id] = [
                        'same_continent_calls'    => 0,
                        'same_continent_duration' => 0,
                        'total_calls'             => 0,
                        'total_duration'          => 0,
                    ];
                }

                $stats[$customer_id]['total_calls']++;
                $stats[$customer_id]['total_duration'] += $record->getDuration();

                if ($same) {
                    $stats[$customer_id]['same_continent_calls']++;
                    $stats[$customer_id]['same_continent_duration'] += $record->getDuration();
                }

                $results['calculated_lines'] += 1;
            }

            $this->saveStats($parent_task, $stats);

            $task->setResult($results);
            $this->em->persist($task);
            $this->em->flush();

            $this->em->clear(CallRecord::class);
            $this->em->clear(CallStat::class);

            $task = $this->em->getRepository(Task::class)->find($task->getId());

            $offset += $count;
        } while ($count > 0);

        $task->setStatus(Task::STATUS_FINISHED);
        $this->em->persist($task);
        $this->em->flush();
    }

    private function saveStats(Task $parent_task, array $stats): void
    {
        foreach ($stats as $customer_id => $stat) {
            $existing = $this->em->getRepository(CallStat::class)
                ->findOneBy(['customer_id' => $customer_id, 'task' => $parent_task]);

            if ($existing) {
                $record = $existing;
            } else {
                $record = new CallStat();
                $record->setCustomerId($customer_id);
                $record->setTask($parent_task);
            }

            $record->setSameCalls(
                ($record->getSameCalls() ?? 0) + $stat['same_continent_calls']
            );
            $record->setSameDuration(
                ($record->getSameDuration() ?? 0) + $stat['same_continent_duration']
            );
            $record->setTotalCalls(
                ($record->getTotalCalls() ?? 0) + $stat['total_calls']
            );
            $record->setTotalDuration(
                ($record->getTotalDuration() ?? 0) + $stat['total_duration']
            );

            $this->em->persist($record);
        }
    }
}