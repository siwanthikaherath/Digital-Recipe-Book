<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message']));
    $terms = isset($_POST['terms']) ? true : false;

    // Validate the form data
    if (empty($name) || empty($email) || empty($message)) {
        echo "All fields are required!";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address!";
        exit;
    }

    if (!$terms) {
        echo "You must accept the terms and conditions.";
        exit;
    }

    // Prepare the email
    $to = "sanduniherath0516@gmail.com";  
    $subject = "New Contact Us Form Submission";
    $body = "You have received a new message from the contact form:\n\n" .
            "Name: $name\n" .
            "Email: $email\n" .
            "Message: $message";

    // Set headers
    $headers = "From: sanduniherath0516@gmail.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Send the email and check for errors
    if (mail($to, $subject, $body, $headers)) {
        echo "Thank you for contacting us! We'll get back to you shortly.";
    } else {
        error_log("Mail failed to send to $to");
        echo "Sorry, something went wrong. Please try again later.";
    }
}
?>
