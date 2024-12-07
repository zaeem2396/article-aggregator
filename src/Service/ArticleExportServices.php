<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleExportServices
{

    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function exportCsv(array $articles): Response
    {
        $filename = "articles_" . date('YmdHis') . ".csv";

        $response = new StreamedResponse(function () use ($articles) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Author Name', 'Title', 'Summary', 'Created At']);

            foreach ($articles as $article) {
                fputcsv($handle, [
                    $article->getAuthorName(),
                    $article->getTitle(),
                    $article->getSummary(),
                    $article->getCreatedAt()->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }


    public function exportExcel(array $articles): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Heading's
        $sheet->setCellValue('A1', 'Author Name');
        $sheet->setCellValue('B1', 'Title');
        $sheet->setCellValue('C1', 'Summary');
        $sheet->setCellValue('D1', 'Created At');

        $row = 2;
        foreach ($articles as $article) {
            $sheet->setCellValue('A' . $row, $article->getAuthorName());
            $sheet->setCellValue('B' . $row, $article->getTitle());
            $sheet->setCellValue('C' . $row, $article->getSummary());
            $sheet->setCellValue('D' . $row, $article->getCreatedAt()->format('Y-m-d H:i:s'));
            $row++;
        }

        $filename = "articles_" . date('YmdHis') . ".xlsx";

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return new Response(file_get_contents($tempFile), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
