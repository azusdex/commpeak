<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskRunnerService;
use App\Service\Tasks\UploadTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: TaskRunnerService::TASK_UPLOAD_FILE,
    description: 'Upload file processing task',
)]
class ProcessUploadCommand extends Command
{
    public function __construct(
        private UploadTask             $manager,
        private EntityManagerInterface $em,
        private readonly string        $app_dir
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task_id = $input->getArgument('arg1');

        file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Process started']) . "\n", FILE_APPEND);

        if (!$task_id || !is_numeric($task_id)) {
            file_put_contents($this->app_dir  . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Missing task ID']) . "\n", FILE_APPEND);

            return Command::FAILURE;
        }

        $task = $this->em->getRepository(Task::class)->find((int)$task_id);
        if (!$task) {
            file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Task not found in db']) . "\n", FILE_APPEND);

            return Command::FAILURE;
        }

        file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Task started']) . "\n", FILE_APPEND);
        $this->manager->process($task);
        file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Task completed']) . "\n", FILE_APPEND);
        return Command::SUCCESS;
    }
}
