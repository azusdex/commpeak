<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskRunnerService;
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
        return $this->json($repo->findUploadTasks());
    }

    #[Route('/upload-task/{id}/status', name: 'cdr_upload_status')]
    public function status(int $id, TaskRepository $repo): JsonResponse
    {
        $task = $repo->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        return new JsonResponse([
            'status' => $task->getStatusMessage(),
            'is_completed' => $task->getIsCompleted(),
        ]);
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

        $runner->startBackgroundProcess(TaskRunnerService::TASK_UPLOAD_FILE, [$task->getId()]);

        return $this->json([
            'task_id' => $task->getId(),
            'status'  => $task->getStatus(),
        ]);
    }

    #[Route('/upload-task/rerun/{id}', name: 'upload_task_rerun', methods: ['POST'])]
    public function rerun(int $id, TaskRepository $repository, TaskRunnerService $runner, EntityManagerInterface $em): JsonResponse
    {
        $task = $repository->find($id);

        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        $task->setStatus(Task::STATUS_PENDING);
        $task->setResult([]);
        $task->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $runner->startBackgroundProcess(TaskRunnerService::TASK_UPLOAD_FILE, [$task->getId()]);

        return $this->json([
            'task_id' => $task->getId(),
            'status'  => $task->getStatus(),
            'rerun'   => true
        ]);
    }
}
