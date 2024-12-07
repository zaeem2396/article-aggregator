<?php

namespace App\Controller;

use Exception;
use App\Entity\Article;
use App\Service\ArticleExportServices;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

class ArticleController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em,
        private CacheInterface $cache,
        private ArticleExportServices $export
    ) {}

    #[Route('/api/articles', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        try {
            $filters = $request->query->all();
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);
            $sortField = $request->query->get('sort_field', 'created_at');
            $setOrder = $request->query->get('sort_order', 'desc');

            $allowedFields = ['author_name', 'title', 'summary', 'created_at'];

            if (!in_array($sortField, $allowedFields)) {
                $sortField = 'created_at';
            }

            $cacheKeys = md5(json_encode($filters) . $page . $limit . $sortField . $setOrder);

            $results = $this->cache->get($cacheKeys, function () use ($filters, $page, $limit, $sortField, $setOrder, $paginator) {
                $queryBuilder = $this->em->createQueryBuilder()->select('a')->from(Article::class, 'a');

                if (isset($filters['author_name'])) {
                    $queryBuilder->andWhere('a.author_name LIKE :author_name')
                        ->setParameter('author_name', '%' . $filters['author_name'] . '%');
                }

                if (isset($filters['title'])) {
                    $queryBuilder->andWhere('a.title LIKE :title')
                        ->setParameter('title', '%' . $filters['title'] . '%');
                }

                if (isset($filters['summary'])) {
                    $queryBuilder->andWhere('a.summary LIKE :summary')
                        ->setParameter('summary', '%' . $filters['summary'] . '%');
                }

                if (isset($filters['created_at'])) {
                    $queryBuilder->andWhere('a.created_at LIKE :created_at')
                        ->setParameter('created_at', '%' . $filters['created_at'] . '%');
                }

                $queryBuilder->orderBy('a.' . $sortField, $setOrder);

                return $paginator->paginate($queryBuilder, $page, $limit);
            });

            $data = array_map(fn($article) => $article->toArray(), $results->getItems());

            return $this->json([
                "status" => 200,
                "ack" => "success",
                "data" => $data,
                "metadata" => [
                    "total" => $results->getTotalItemCount(),
                    "page" => $page,
                    "limit" => $limit,
                    "sort_field" => $sortField,
                    "sort_order" => $setOrder
                ]
            ]);
        } catch (Exception $e) {
            return $this->json([
                "status" => 500,
                "ack" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/articles/export', methods: ['GET'])]
    public function export(Request $request)
    {
        try {
            $filters = $request->query->all();
            $sortField = $request->query->get('sort_field', 'created_at');
            $setOrder = $request->query->get('sort_order', 'desc');

            $allowedFields = ['author_name', 'title', 'summary', 'created_at'];

            if (!in_array($sortField, $allowedFields)) {
                $sortField = 'created_at';
            }

            $queryBuilder = $this->em->createQueryBuilder()->select('a')->from(Article::class, 'a');

            if (isset($filters['author_name'])) {
                $queryBuilder->andWhere('a.author_name LIKE :author_name')
                    ->setParameter('author_name', '%' . $filters['author_name'] . '%');
            }

            if (isset($filters['title'])) {
                $queryBuilder->andWhere('a.title LIKE :title')
                    ->setParameter('title', '%' . $filters['title'] . '%');
            }

            if (isset($filters['summary'])) {
                $queryBuilder->andWhere('a.summary LIKE :summary')
                    ->setParameter('summary', '%' . $filters['summary'] . '%');
            }

            if (isset($filters['created_at'])) {
                $queryBuilder->andWhere('a.created_at LIKE :created_at')
                    ->setParameter('created_at', '%' . $filters['created_at'] . '%');
            }

            $queryBuilder->orderBy('a.' . $sortField, $setOrder);

            $articles = $queryBuilder->getQuery()->getResult();

            $format = $request->query->get('format', 'csv');
            if ($format === 'csv') {
                return $this->export->exportCsv($articles);
            }

            if ($format === 'excel') {
                return $this->export->exportExcel($articles);
            }

            return new Response('Invalid export format', 400);
        } catch (Exception $e) {
            return $this->json([
                "status" => 500,
                "ack" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
