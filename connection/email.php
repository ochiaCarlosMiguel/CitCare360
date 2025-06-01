<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendIncidentNotification($incidentData, $adminEmails) {
    error_log("Starting sendIncidentNotification function");
    
    if (empty($adminEmails)) {
        error_log("No admin emails provided");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        
        // Gmail account settings
        $mail->Username = 'ivanevan2codewell@gmail.com';
        $mail->Password = 'cenh frpi denv fuuf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email settings for better deliverability
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Priority = 1;
        
        // Anti-spam headers
        $mail->XMailer = 'CIT CARE 360 Mailer';
        $mail->addCustomHeader('Feedback-ID', 'CIT-CARE-360:' . time());
        $mail->addCustomHeader('X-Entity-Ref-ID', 'CIT-' . time() . '-' . rand(1000, 9999));
        $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . $mail->Username . '?subject=unsubscribe>');
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        
        // DKIM-Signature header (if available)
        $mail->DKIM_domain = 'gmail.com';
        $mail->DKIM_selector = 'default';
        $mail->DKIM_identity = $mail->Username;
        
        // From and Reply-To with proper formatting
        $mail->setFrom($mail->Username, 'CIT CARE 360 Incident Reporting System', false);
        $mail->addReplyTo($mail->Username, 'CIT CARE 360 Support Team');
        
        // Add admin recipients with names
        foreach ($adminEmails as $email) {
            // Extract name from email (before @)
            $name = explode('@', $email)[0];
            $name = ucwords(str_replace('.', ' ', $name));
            $mail->addAddress($email, $name);
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'CIT CARE 360: New Incident Report - Immediate Action Required';
        
        // Create plain text version with proper formatting
        $plainText = "CIT CARE 360 - New Incident Report\n\n" .
                    "IMPORTANT: A new incident report requires your immediate attention.\n\n" .
                    "Report Details:\n" .
                    "-------------\n" .
                    "Student: {$incidentData['full_name']}\n" .
                    "ID: {$incidentData['student_number']}\n" .
                    "Department: {$incidentData['department']}\n" .
                    "Subject: {$incidentData['subject_report']}\n\n" .
                    "Description:\n" .
                    "{$incidentData['description']}\n\n" .
                    "Action Required:\n" .
                    "Please log in to the CIT CARE 360 admin dashboard to review this report.\n\n" .
                    "-------------------\n" .
                    "This is an official notification from CIT CARE 360.\n" .
                    "Please do not reply to this email.\n" .
                    "For support: Contact system administrator\n\n" .
                    "© " . date('Y') . " CIT CARE 360 - BULSU CIT MALOLOS";
        
        $mail->AltBody = $plainText;
        
        // HTML email body with improved design and trust signals
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .header { background-color: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .logo { margin-bottom: 15px; }
                    .content { padding: 30px; background-color: #ffffff; }
                    .details { margin: 20px 0; background-color: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #4F46E5; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
                    .urgent { color: #dc3545; font-weight: bold; font-size: 16px; }
                    .action-needed { background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
                    .trust-badge { text-align: center; margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <div class='logo'>
                            <img src='C:\xampp\htdocs\Project\image' alt='CIT CARE 360' style='max-width: 150px;'>
                        </div>
                        <h2>⚠️ New Incident Report</h2>
                    </div>
                    <div class='content'>
                        <p class='urgent'>IMPORTANT: A new incident report requires your immediate attention.</p>
                        <div class='details'>
                            <h3>Report Details:</h3>
                            <p><strong>Student Name:</strong> {$incidentData['full_name']}</p>
                            <p><strong>Student Number:</strong> {$incidentData['student_number']}</p>
                            <p><strong>Department:</strong> {$incidentData['department']}</p>
                            <p><strong>Subject:</strong> {$incidentData['subject_report']}</p>
                            <p><strong>Description:</strong></p>
                            <p style='white-space: pre-wrap; background-color: #ffffff; padding: 15px; border-radius: 5px;'>{$incidentData['description']}</p>
                        </div>
                        <div class='action-needed'>
                            <h3 style='margin-top: 0;'>Action Required:</h3>
                            <p>Please log in to the admin dashboard to review and take immediate action on this report.</p>
                            <a href='..Project/lightAdmin/index.php' style='display: inline-block; padding: 10px 20px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Access Admin Dashboard</a>
                        </div>
                        <div class='trust-badge'>
                            <p style='margin: 0;'><strong>CIT CARE 360</strong></p>
                            <p style='margin: 5px 0; font-size: 12px;'>Official Incident Reporting System</p>
                            <p style='margin: 5px 0; font-size: 12px;'>BULSU CIT - MALOLOS</p>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>This is an official communication from CIT CARE 360 System.</p>
                        <p>Please do not reply to this email. For support, please contact the system administrator.</p>
                        <p style='margin-top: 15px; font-size: 11px;'>© " . date('Y') . " CIT CARE 360 - All rights reserved</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Send the email
        if (!$mail->send()) {
            error_log("Email sending failed. Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
        
        error_log("Email sent successfully to: " . implode(", ", $adminEmails));
        return true;
    } catch (Exception $e) {
        error_log("Exception occurred while sending email: " . $e->getMessage());
        if (isset($mail)) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
        return false;
    }
}
?> 