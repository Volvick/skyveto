<?php
// File: welcome.php
// Location: /
// FINAL, SIMPLIFIED, and RELIABLE VERSION

include_once 'common/config.php';

if (isset($_SESSION['user_id']) || isset($_SESSION['language_selected'])) {
    header("Location: index.php");
    exit();
}

$languages = [
    'en' => ['name' => 'English', 'native' => 'English'],
    'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी'],
    'mr' => ['name' => 'Marathi', 'native' => 'मराठी'],
    'bn' => ['name' => 'Bengali', 'native' => 'বাংলা'],
    'te' => ['name' => 'Telugu', 'native' => 'తెలుగు'],
    'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்'],
    'gu' => ['name' => 'Gujarati', 'native' => 'ગુજરાતી'],
    'kn' => ['name' => 'Kannada', 'native' => 'ಕನ್ನಡ'],
    'ml' => ['name' => 'Malayalam', 'native' => 'മലയാളം'],
    'or' => ['name' => 'Odia', 'native' => 'ଓଡ଼ିଆ'],
    'pa' => ['name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Welcome - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style> 
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; } 
    </style>
</head>
<body class="bg-white">
    <div class="max-w-md mx-auto min-h-screen flex flex-col p-4">
        <div class="text-center my-8">
             <h1 class="text-3xl font-bold text-blue-600">Skyveto</h1>
             <p class="text-gray-500 mt-1">Your Shopping Destination</p>
        </div>

        <h2 class="text-xl font-bold text-gray-800">Choose your language</h2>
        <p class="text-gray-500 mb-6">You can change this later in settings.</p>

        <!-- Language List -->
        <div class="flex-grow space-y-3 overflow-y-auto">
            <?php foreach ($languages as $code => $lang): ?>
            <div class="language-option border rounded-lg p-4 flex justify-between items-center cursor-pointer hover:bg-blue-50" data-lang-code="<?php echo $code; ?>">
                <div>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($lang['name']); ?></span>
                    <span class="text-gray-500 ml-3"><?php echo htmlspecialchars($lang['native']); ?></span>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- NEW: "Continue in English" button -->
        <div class="mt-auto pt-4">
             <button id="continue-english-btn" class="w-full text-center bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700">
                Continue in English
             </button>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const setLanguageAndRedirect = async (langCode, redirectUrl) => {
        const formData = new FormData();
        formData.append('action', 'set_language');
        formData.append('lang_code', langCode);
        try {
            const response = await fetch('welcome_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                window.location.href = redirectUrl;
            } else {
                alert('Could not set language. Please try again.');
            }
        } catch (error) {
            alert('An error occurred.');
        }
    };

    // Handle clicking on any language option
    document.querySelectorAll('.language-option').forEach(option => {
        option.addEventListener('click', () => {
            setLanguageAndRedirect(option.dataset.langCode, 'index.php');
        });
    });

    // Handle clicking the "Continue in English" button
    document.getElementById('continue-english-btn').addEventListener('click', () => {
        setLanguageAndRedirect('en', 'index.php');
    });

    // --- NEW, SIMPLIFIED BACK BUTTON LOGIC ---
    // This logic handles the case where the user presses the physical back button on their phone.
    // It pushes a new state to the browser's history.
    history.pushState(null, document.title, location.href);
    window.addEventListener('popstate', function (event) {
        // When the user presses back, they go to the "null" state we just pushed.
        // We catch that and redirect them to the homepage.
        setLanguageAndRedirect('en', 'index.php');
    });
    // --- END OF NEW LOGIC ---
});
</script>
</body>
</html>