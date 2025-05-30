<?php

namespace App\Service;

class TaskRunnerService
{
    const TASK_UPLOAD_FILE = 'app:process-upload-task';
    const TASK_AGGREGATE_CALLS = 'app:task-aggregate-calls';
    const LOG_FILE_PATH = '/var/log/task-runner.log';

    public function __construct(private readonly string $app_dir)
    {
    }

    public function startBackgroundProcess(string $command, array $args = []): void
    {
        $full_command = sprintf(
            'nohup %s %s/bin/console %s %s > /dev/null 2>&1 &',
            '/usr/local/bin/php',
            $this->app_dir,
            $command,
            implode(' ', array_map('escapeshellarg', $args))
        );

        $process = proc_open($full_command, [], $pipes);

        $log = [
            'command' => $full_command,
            'args'    => $args,
            'message' => is_resource($process) ? 'Started' : 'Failed'
        ];

        file_put_contents($this->app_dir . self::TASK_UPLOAD_FILE, json_encode($log) . "\n", FILE_APPEND);

        if (is_resource($process)) {
            proc_close($process);
        }
    }
}