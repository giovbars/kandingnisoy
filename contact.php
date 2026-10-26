<?php
require_once 'config/config.php';
require_once 'config/functions.php';

$messageSent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = "Palihog kompletoha ang tanang fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // 👉 ILISI KINI SA ACTUAL NGA EMAIL SA INYONG TEAM
        $to = "support@kandingnisoy.com"; // Example: kandingnisoy@gmail.com
        $subject = "Kandingnisoy Support: Message from $name";
        
        $body = "You have received a new message from the Kandingnisoy Support Form.\n\n";
        $body .= "Name: $name\n";
        $body .= "Email: $email\n\n";
        $body .= "Message:\n$message\n";

        $headers = "From: noreply@kandingnisoy.com\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // I-send ang email
   
if (@mail($to, $subject, $body, $headers)) {
            $messageSent = true;
        } else {
            // Fallback kung walay mail server sa localhost (XAMPP)
            // I-save lang sa log file para makita ninyo
            $logFile = __DIR__ . '/support_messages.log';
            $logData = "[" . date('Y-m-d H:i:s') . "] Name: $name | Email: $email | Message: $message\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            
            // Ipakita gihapon ang success message sa user
            $messageSent = true; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Kandingnisoy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 py-4">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/index.php" class="flex items-center gap-2 text-xl font-bold text-green-700">
                <i class="fas fa-leaf"></i> Kandingnisoy
            </a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="text-sm font-semibold text-gray-600 hover:text-green-600 transition">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </nav>

    <!-- Content -->
    <main class="flex-grow max-w-2xl mx-auto px-4 py-12 w-full">
        <div class="bg-white p-8 md:p-10 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-headset"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900">Contact Support</h1>
                <p class="text-gray-500 mt-2">Naay problema sa login o sa system? Pasagdi lang imong mensahe dinhi ug among tubagon dayon.</p>
            </div>

            <?php if ($messageSent): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
                    <p class="font-bold"><i class="fas fa-check-circle mr-2"></i> Mensahe Napadala!</p>
                    <p class="text-sm mt-1">Salamat sa pag-contact. Among i-review ang imong mensahe ug mo-reply mi pinaagi sa imong email.</p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
                    <p class="font-bold"><i class="fas fa-exclamation-circle mr-2"></i> Error</p>
                    <p class="text-sm mt-1"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$messageSent): ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Imong Pangalan</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Imong Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Mensahe</label>
                    <textarea name="message" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" placeholder="Isulat dinhi ang imong pangutana o problema..."></textarea>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition shadow-md">
                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                </button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-sm text-gray-500">
        <p>&copy; <?php echo date('Y'); ?> Kandingnisoy. All rights reserved.</p>
    </footer>
</body>
</html>