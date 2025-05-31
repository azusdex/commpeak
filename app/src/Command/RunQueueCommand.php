<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskRunnerService;
use App\Service\Tasks\TaskProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AsCommand(
    name: 'task:run',
    description: 'Fake queue processing task',
)]
class RunQueueCommand extends Command
{
    /**
     * @param EntityManagerInterface $em
     * @param string $app_dir
     * @param iterable<TaskProcessorInterface> $handlers
     */
    public function __construct(
        private EntityManagerInterface $em,
        private readonly string        $app_dir,
        private readonly iterable      $handlers
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

        $type = $task->getType();

        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Task started']) . "\n", FILE_APPEND);

                try {
                    $handler->process($task);
                    file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Task completed']) . "\n", FILE_APPEND);

                } catch (\Exception $e) {
                    file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => $e->getMessage()]) . "\n", FILE_APPEND);

                }


                return Command::SUCCESS;
            }
        }

        file_put_contents($this->app_dir . TaskRunnerService::LOG_FILE_PATH, json_encode(['task_id' => $task_id, 'message' => 'Handler not found']) . "\n", FILE_APPEND);
        return Command::FAILURE;
    }
}
