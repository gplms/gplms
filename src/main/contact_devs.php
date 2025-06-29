<?php
// Handle form submission
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // MailerSend API Configuration
        $apiKey = 'YOUR API KEY';
        
        // Use a verified test domain format
        $senderEmail = 'YOU SENDER DOMAIN';
        $senderName = 'Library System';
        $recipientEmail = 'RECIPIENT EMAIL';
        $recipientName = 'System Administrator';
        
        // Prepare email data
        $emailData = [
            'from' => [
                'email' => $senderEmail,
                'name' => $senderName
            ],
            'to' => [
                [
                    'email' => $recipientEmail,
                    'name' => $recipientName
                ]
            ],
            'subject' => "Library Support: $subject",
            'text' => "Name: $name\nEmail: $email\n\nMessage:\n$message",
            'html' => "
                <h2>Library System Support Request</h2>
                <p><strong>From:</strong> $name ($email)</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
                <hr>
                <p><small>This message was sent from the Library System contact form</small></p>
            "
        ];
        
        // Send email via MailerSend API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailersend.com/v1/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check response
        if ($httpCode >= 200 && $httpCode < 300) {
            $message_sent = true;
        } else {
            // Improved error handling
            $error_detail = 'Unknown error';
            
            if (!empty($curlError)) {
                $error_detail = "cURL Error: $curlError";
            } elseif (!empty($response)) {
                $responseData = json_decode($response, true);
                
                // Handle MailerSend's error format
                if (isset($responseData['errors'][0]['message'])) {
                    $error_detail = $responseData['errors'][0]['message'];
                } elseif (isset($responseData['message'])) {
                    $error_detail = $responseData['message'];
                } else {
                    $error_detail = "API Error: " . substr($response, 0, 100);
                }
            }
            
            $error_message = "Failed to send message. Error: $error_detail";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPLMS - Free & Open Source Project | Contact Administrator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/png" href="../../assets/logo-l.png">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light: #f8f9fa;
            --border: #e0e0e0;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            background-color: var(--primary);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .logo-text {
            font-weight: 700;
            color: white;
            font-size: 1.5rem;
        }
        
        .contact-container {
            max-width: 1200px;
            margin: 0 auto 40px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .contact-header {
            background-color: var(--primary);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .contact-title {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .contact-subtitle {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .contact-content {
            padding: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .contact-form {
            flex: 1;
            min-width: 300px;
            background-color: white;
            padding: 25px;
            border: 1px solid var(--border);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-send {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-send:hover {
            background-color: #2980b9;
        }
        
        .info-section {
            flex: 1;
            min-width: 300px;
        }
        
        .info-card {
            background: white;
            border: 1px solid var(--border);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .info-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary);
        }
        
        .contact-method {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .contact-details {
            font-size: 1rem;
        }
        
        .contact-details strong {
            display: block;
            margin-bottom: 4px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: flex-start;
        }
        
        .feature-list li:before {
            content: '✓';
            color: var(--secondary);
            font-weight: bold;
            margin-right: 10px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer {
            background-color: var(--primary);
            color: white;
            padding: 30px 0;
            text-align: center;
        }
        
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .social-links {
            margin: 20px 0;
        }
        
        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            margin: 0 8px;
            line-height: 40px;
            text-align: center;
            font-size: 18px;
            text-decoration: none;
        }
        
        .copyright {
            margin-top: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .contact-content {
                padding: 20px;
            }
            
            .contact-header {
                padding: 20px;
            }
            
            .contact-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

    
    <div class="container">
        <div class="contact-container">
            <div class="contact-header">
                <h1 class="contact-title">Contact System Administrators</h1>
                <p class="contact-subtitle">Need assistance with the library system? Our administrators are here to help.</p>
            </div>
            
            <div class="contact-content">
                <!-- Contact Form -->
                <div class="contact-form">
                    <h2 class="info-title">Send a Message</h2>
                    
                    <?php if ($message_sent): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Thank you for your message!</strong> 
                                <p>We'll get back to you as soon as possible.</p>
                            </div>
                        </div>
                    <?php elseif ($error_message): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <strong>Error:</strong> 
                                <p><?= $error_message ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="name">Your Name</label>
                            <input type="text" name="name" class="form-control" id="name" placeholder="John Smith" required value="<?= $_POST['name'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" name="email" class="form-control" id="email" placeholder="john@example.com" required value="<?= $_POST['email'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="subject">Subject</label>
                            <input type="text" name="subject" class="form-control" id="subject" placeholder="How can we help?" required value="<?= $_POST['subject'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="message">Message</label>
                            <textarea name="message" class="form-control" id="message" placeholder="Describe your issue or question..." required><?= $_POST['message'] ?? '' ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Contact Information -->
                <div class="info-section">
                    <div class="info-card">
                        <h3 class="info-title">Administration Contact</h3>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <strong>System Support</strong>
                                admin@librarysystem.com
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <strong>Support Line</strong>
                                (123) 456-7890
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <strong>Office Hours</strong>
                                Mon-Fri: 8:00 AM - 6:00 PM
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3 class="info-title">Common Support Requests</h3>
                        
                        <ul class="feature-list">
                            <li>Account access issues</li>
                            <li>Password reset requests</li>
                            <li>System error reports</li>
                            <li>Feature requests</li>
                            <li>Permission changes</li>
                            <li>Training inquiries</li>
                        </ul>
                    </div>
                    
          
                </div>
            </div>
        </div>
    </div>
    

    
    <script>
        // Simple animation for form elements
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>