<?php
session_start();
ob_start(); // Start output buffering

// Check if connections.php exists
if (!file_exists('../connection/connection.php')) {
    error_log("connections.php not found.");
    header("Location: existingStudents.php?error=connections_not_found");
    exit();
}

require_once '../connection/connection.php'; // Ensure this path is correct
require_once '../vendor/autoload.php'; // Include Composer autoload file

// Debugging statement
if (!isset($conn)) {
    die("Database connection not established.");
}

if (!isset($_SESSION['user'])) {
    header("Location: existingStudents.php");
    exit();
}

try {
    // Fetch student data with all details
    $query = "
        SELECT 
            cs.student_number,
            cs.first_name,
            cs.last_name,
            cs.middle_name,
            cs.email,
            cs.year_level,
            d.name as department_name,
            CASE 
                WHEN u.id IS NOT NULL THEN 'Registered'
                ELSE 'Unregistered'
            END as status
        FROM cit_students cs
        LEFT JOIN departments d ON cs.department_id = d.id
        LEFT JOIN users u ON cs.student_number = u.student_number
        ORDER BY cs.last_name, cs.first_name";
    
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $students = $result->fetch_all(MYSQLI_ASSOC);

    // Create PDF with printer-friendly settings
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Bulacan State University');
    $pdf->SetTitle('Existing CIT Students Report');
    
    // Remove default header
    $pdf->setPrintHeader(false);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage('P', 'A4');
    
    // Add logo
    $logoPath = '../image/logo.png'; // Updated logo path
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30, 0, 'PNG');
    }
    
    // Company/Institution Information
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'College of Industrial Technology', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'City of Malolos, Bulacan', 0, 1, 'C');
    $pdf->Ln(10); // Add space before the report title
    
    // Report Title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(248, 184, 60); // Theme color
    $pdf->Cell(0, 15, 'Existing CIT Students Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(0, 0, 0); // Black color for date
    date_default_timezone_set('Asia/Manila'); // Set timezone to Philippines
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y g:i A'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add school name
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Bulacan State University', 0, 1, 'C');
    $pdf->Ln(5); // Add space after school name
    
    // Calculate column widths based on page width
    $pageWidth = $pdf->getPageWidth() - 30; // Total width minus margins
    $colWidths = array(
        $pageWidth * 0.08,  // # (8%)
        $pageWidth * 0.15,  // Student Number (15%)
        $pageWidth * 0.25,  // Name (25%)
        $pageWidth * 0.15,  // Year Level (15%)
        $pageWidth * 0.22,  // Department (22%)
        $pageWidth * 0.15   // Status (15%)
    );
    
    // Summary Table
    $pdf->SetFont('helvetica', 'B', 11);
    
    // Custom Header Style
    $pdf->SetFillColor(248, 184, 60);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetLineWidth(0.3);
    
    // Table header
    $header = array('#', 'Student No.', 'Name', 'Year Level', 'Department', 'Status');
    $pdf->Cell($colWidths[0], 12, $header[0], 1, 0, 'C', true);
    $pdf->Cell($colWidths[1], 12, $header[1], 1, 0, 'C', true);
    $pdf->Cell($colWidths[2], 12, $header[2], 1, 0, 'C', true);
    $pdf->Cell($colWidths[3], 12, $header[3], 1, 0, 'C', true);
    $pdf->Cell($colWidths[4], 12, $header[4], 1, 0, 'C', true);
    $pdf->Cell($colWidths[5], 12, $header[5], 1, 1, 'C', true);
    
    // Table body with alternating colors
    $pdf->SetTextColor(68, 68, 68);
    $pdf->SetFont('helvetica', '', 9); // Slightly smaller font for content
    $fill = false;
    
    foreach ($students as $i => $student) {
        // Check if we need a new page
        if($pdf->GetY() > $pdf->GetPageHeight() - 30) {
            $pdf->AddPage();
            // Repeat the header
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(248, 184, 60);
            $pdf->SetTextColor(255, 255, 255);
            foreach($header as $k => $h) {
                $pdf->Cell($colWidths[$k], 12, $h, 1, ($k == 5 ? 1 : 0), 'C', true);
            }
            $pdf->SetTextColor(68, 68, 68);
            $pdf->SetFont('helvetica', '', 9);
        }
        
        // Alternate row colors
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
        
        // Format name with proper spacing
        $fullName = trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']);
        
        // Ensure text fits in cells
        $rowHeight = max(
            $pdf->getStringHeight($colWidths[2], $fullName),
            $pdf->getStringHeight($colWidths[4], $student['department_name']),
            10
        );
        
        // Draw cells with multi-line support
        $pdf->Cell($colWidths[0], $rowHeight, $i + 1, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], $rowHeight, $student['student_number'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[2], $rowHeight, $fullName, 1, 0, 'L', $fill);
        $pdf->Cell($colWidths[3], $rowHeight, $student['year_level'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[4], $rowHeight, $student['department_name'], 1, 0, 'C', $fill);
        
        // Status cell with color
        $pdf->SetTextColor($student['status'] === 'Registered' ? 76 : 244, $student['status'] === 'Registered' ? 175 : 67, $student['status'] === 'Registered' ? 80 : 54);
        $pdf->Cell($colWidths[5], $rowHeight, $student['status'], 1, 1, 'C', $fill);
        $pdf->SetTextColor(68, 68, 68);
        
        $fill = !$fill;
    }
    
    // Add total count after the table
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 10, 'Total Students: ' . count($students), 0, 1, 'R');
    
    // Footer Information with page number only on the summary page
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(128, 128, 128); // Gray color for footer
    $pdf->Cell(0, 10, 'This report is confidential and intended for internal use only. Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Output the PDF
    ob_end_clean(); // Clean the output buffer before generating the PDF
    
    // Set printer-friendly options
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Output the PDF with printer-friendly settings
    $pdf->Output('Existing_CIT_Students_Report.pdf', 'I'); // 'I' for inline display, 'D' for download
    
    // Log success message
    error_log("PDF generated successfully.");
    exit; // Ensure no further output is sent
} catch (Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    header("Location: existingStudents.php?error=export_failed");
    exit();
}
?>