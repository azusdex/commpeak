<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\CallStatRepository;
use App\Repository\TaskRepository;
use App\Service\TaskRunnerService;
use App\Service\Tasks\StatsTask;
use App\Service\Tasks\UploadTask;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CdrController extends AbstractController
{
    #[Route('/', name: 'cdr_index')]
    public function index(): Response
    {
        return $this->render('cdr/index.html.twig');
    }

    #[Route('/cdr/list', name: 'cdr_list')]
    public function list(TaskRepository $repo): JsonResponse
    {
        return $this->json($repo->findTasksStatus());
    }

    #[Route('/cdr/records/{id}', name: 'cdr_records')]
    public function records(int $id, TaskRepository $task_repo, CallStatRepository $stat_repo): JsonResponse
    {
        $task = $task_repo->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        return $this->json(array_map(fn($stat) => [
            'customer_id'    => $stat->getCustomerId(),
            'same_calls'     => $stat->getSameCalls(),
            'same_duration'  => $stat->getSameDuration(),
            'total_calls'    => $stat->getTotalCalls(),
            'total_duration' => $stat->getTotalDuration(),
        ],
            $stat_repo->findBy(['task' => $task])));
    }

    /**
     * @throws UnavailableStream
     * @throws Exception
     */
    #[Route('/upload', name: 'cdr_upload', methods: ['POST'])]
    public function upload(Request $request, UploadTask $manager, TaskRunnerService $runner): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Invalid file'], 400);
        }

        $task = $manager->create(['file' => $file]);

        $runner->startBackgroundProcess(TaskRunnerService::TASK_RUN_COMMAND, [$task->getId()]);

        return $this->json([
            'task_id' => $task->getId(),
            'status'  => $task->getStatus(),
        ]);
    }

    #[Route('/task/rerun/{id}', name: 'task_rerun', methods: ['POST'])]
    public function rerun(int $id, TaskRepository $repo, TaskRunnerService $runner, EntityManagerInterface $em, StatsTask $stats_task): JsonResponse
    {
        $task = $repo->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        $task->setStatus(Task::STATUS_PENDING);
        $task->setResult([]);
        $task->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

//        $runner->startBackgroundProcess(TaskRunnerService::TASK_RUN_COMMAND, [$task->getId()]);

        $stats_task->process($task);

        return $this->json([
            'task_id' => $task->getId(),
            'status'  => $task->getStatus(),
            'rerun'   => true
        ]);
    }

    #[Route('/task/recalculate/{id}', name: 'task_recalculate', methods: ['POST'])]
    public function recalculate(int $id, TaskRepository $repo, TaskRunnerService $runner, EntityManagerInterface $em): JsonResponse
    {
        $parent_task = $repo->find($id);
        $child_task = $repo->findOneBy(['parent_task' => $parent_task]);

        if (!$child_task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        $child_task->setStatus(Task::STATUS_PENDING);
        $child_task->setResult([]);
        $child_task->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $runner->startBackgroundProcess(TaskRunnerService::TASK_RUN_COMMAND, [$child_task->getId()]);

        return $this->json([
            'task_id'             => $parent_task->getId(),
            'aggregation_task_id' => $child_task->getId(),
            'status'              => $child_task->getStatus(),
            'rerun'               => true
        ]);
    }
}
