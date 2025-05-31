<?php

namespace App\Service\Tasks;

use App\Entity\CallRecord;
use App\Entity\Task;
use App\Service\TaskRunnerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadTask implements TaskProcessorInterface
{
    const BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StatsTask $stats_task,
        private readonly TaskRunnerService $runner,
        private readonly string                 $app_dir
    ) {}

    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    public function create(array $data): Task
    {
        if (!isset($data['file']) || !$data['file'] instanceof UploadedFile) {
            throw new \InvalidArgumentException('UploadTask expects UploadedFile under key "file".');
        }

        $file = $data['file'];
        $filename = uniqid('cdr_', true) . '.csv';
        $upload_dir = $this->app_dir . '/var/uploads';
        $file_path = $upload_dir . '/' . $filename;
        $file->move($upload_dir, $filename);

        $csv = Reader::createFromPath($file_path, 'r');
        $total_lines = iterator_count($csv->getRecords());

        $task = new Task();
        $task->setType(TaskRunnerService::TASK_UPLOAD_FILE);
        $task->setStatus(Task::STATUS_PENDING);
        $task->setData([
            'original_filename' => $file->getClientOriginalName(),
            'filename'          => $filename,
            'file_path'         => $file_path,
            'total_lines'      => $total_lines,
        ]);
        $task->setResult([
            'skipped'   => 0,
            'processed' => 0,
            'added'     => 0,
            'errors'    => [],
        ]);

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    public function process(Task $task): void
    {
        $task->setStatus(Task::STATUS_PROCESSING);
        $this->em->flush();
        $data = $task->getData();
        $result = $task->getResult() ?? [];

        $line_number = 0;
        $added = 0;
        $skipped = 0;

        try {
            $csv = Reader::createFromPath($data['file_path'], 'r');
            $records = $csv->getRecords();

            foreach ($records as $row) {
                $line_number++;

                if ($line_number <= ($result['processed'] ?? 0)) {
                    continue;
                }

                if ($this->isValidRow($row)) {
                    $this->createCallRecord($row, $task);
                    $added++;
                } else {
                    $skipped++;
                }

                if ($added % self::BATCH_SIZE === 0) {
                    $this->updateProgress($task, $line_number, $added, $skipped, true);

                    $task = $this->em->getRepository(Task::class)->find($task->getId());
                }
            }

            if ($added % self::BATCH_SIZE !== 0) {
                $this->updateProgress($task, $line_number, $added, $skipped, false);
            }

            $this->updateDoneStatus($task, $line_number, $added, $skipped);

            $aggregate_task = $this->stats_task->create(['parent_task' => $task]);
            $this->runner->startBackgroundProcess(TaskRunnerService::TASK_RUN_COMMAND, [$aggregate_task->getId()]);
        } catch (\Throwable $e) {

            $this->updateFailStatus($task, $e->getMessage());
        }
    }

    private function isValidRow(array $row): bool {
        return count($row) === 5;
    }

    /**
     * @throws \Exception
     */
    private function createCallRecord(array $row, Task $task): void {
        $call = new CallRecord();
        $call->setTask($task);
        $call->setCustomerId((int)$row[0]);
        $call->setCallDate(new DateTime($row[1]));
        $call->setDuration((int)$row[2]);
        $call->setDialedNumber($row[3]);
        $call->setSourceIp($row[4]);

        $this->em->persist($call);
    }

    private function updateDoneStatus(Task $task, int $line_number, int $added, int $skipped): void {
        $task->setStatus(Task::STATUS_FINISHED);
        $task->setResult($this->updateResult($task, $line_number, $added, $skipped));
        $this->em->flush();
    }

    private function updateFailStatus(Task $task, string $message): void {
        $result = $task->getResult() ?? [];
        $result['error'] = $message;
        $task->setStatus(Task::STATUS_ERROR);
        $task->setResult($result);
        $this->em->flush();
    }

    public function updateProgress(Task $task, int $line_number, int $added, int $skipped, bool $clear): void {
        $task->setResult($this->updateResult($task, $line_number, $added, $skipped));
        $this->em->flush();

        if ($clear) {
            $this->em->clear();
        }
    }

    private function updateResult(Task $task, int $line_number, int $added, int $skipped): array {
        $result = $task->getResult() ?? [];
        $result['processed'] = $line_number;
        $result['added'] = $added;
        $result['skipped'] = $skipped;

        return $result;
    }

    public function supports(string $type): bool
    {
        return $type === TaskRunnerService::TASK_UPLOAD_FILE;
    }
}