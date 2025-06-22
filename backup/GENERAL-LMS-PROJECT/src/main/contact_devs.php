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
        // Simulate email sending (in a real app, you would use mail() or PHPMailer)
        $to = "developers@librarysystem.com";
        $headers = "From: $email" . "\r\n" .
                   "Reply-To: $email" . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        // This would be the actual email sending code:
        // $mail_sent = mail($to, $subject, $message, $headers);
        $mail_sent = true; // Simulating success
        
        if ($mail_sent) {
            $message_sent = true;
        } else {
            $error_message = "There was a problem sending your message. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Developers - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/contact-devs-styles.css">
    <link rel="stylesheet" href="../styles/components/header.css">


    
</head>
<body>


    <?php include '../components/header.php'; ?>

    <!-- Contact Header -->
    <div class="contact-header">
        <div class="container">
            <h1 class="contact-title">Contact Our Development Team</h1>
            <p class="contact-subtitle">Have questions, suggestions, or need support? We're here to help you with any inquiries about our Library Management System.</p>
        </div>
    </div>
    
    <?php include '../components/contact-devs-main-content.php'; ?>
    
        <?php include '../components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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