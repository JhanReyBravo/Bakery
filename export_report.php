<?php
require 'vendor/autoload.php';
include 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['report_id'])) {
    $reportID = $_GET['report_id'];

    // Fetch report data
    $sql = "SELECT * FROM end_of_day_reports WHERE ReportID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reportID);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();

    if ($report) {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('The A Bakery BCD')
            ->setTitle('End of Day Report')
            ->setSubject('End of Day Report')
            ->setDescription('End of Day Report exported from the system.');

        // Add some data
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('End of Day Report');

        // Set header
        $sheet->setCellValue('A1', 'Report ID')
            ->setCellValue('B1', 'Report Date')
            ->setCellValue('C1', 'Beginning Balance')
            ->setCellValue('D1', 'Total Sales')
            ->setCellValue('E1', 'Total Payments')
            ->setCellValue('F1', 'Cash')
            ->setCellValue('G1', 'Gcash')
            ->setCellValue('H1', 'Customer Outstanding')
            ->setCellValue('I1', 'Total Expenses');

        // Set report data
        $sheet->setCellValue('A2', $report['ReportID'])
            ->setCellValue('B2', $report['Report_Date'])
            ->setCellValue('C2', $report['BeginningBalance'])
            ->setCellValue('D2', $report['Total_Sales'])
            ->setCellValue('E2', $report['Total_Payments'])
            ->setCellValue('F2', $report['Cash'])
            ->setCellValue('G2', $report['Gcash'])
            ->setCellValue('H2', $report['Customer_Outstanding'])
            ->setCellValue('I2', $report['Total_Expenses']);

        // Write to Excel file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'A_BAKERY_end_of_day_report_' . $reportID . '.xlsx';

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        // Write the file to the output buffer
        $writer->save('php://output');
        exit;
    } else {
        echo "Report not found.";
    }
} else {
    echo "No report ID provided.";
}

$conn->close();
?>