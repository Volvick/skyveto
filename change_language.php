<?php
// File: change_language.php (FUNCTIONAL VERSION)
// Location: /
include_once 'common/config.php';
check_login(); // FIX: Moved this line up to execute before any HTML is sent.

// The header include, which outputs HTML, now comes after the login check.
include_once 'common/header.php'; 

// Based on research of popular Indian e-commerce apps
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

$current_lang = $_SESSION['lang'] ?? 'en';
?>

<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i><?php echo __t('lang_back_to_account'); ?></a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('lang_choose_language'); ?></h1>
    
    <div id="form-message" class="hidden mb-4 p-3 rounded-md text-center"></div>

    <div class="bg-white p-4 rounded-lg shadow-sm">
        <form id="language-form">
            <input type="hidden" name="action" value="update_language">
            <div class="space-y-1">
                <?php foreach ($languages as $code => $lang): ?>
                <label class="flex items-center p-3 rounded-md has-[:checked]:bg-blue-50">
                    <input type="radio" name="lang_code" value="<?php echo $code; ?>" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300" <?php echo ($code === $current_lang) ? 'checked' : ''; ?>>
                    <span class="ml-3 font-medium text-gray-700"><?php echo htmlspecialchars($lang['name']); ?></span>
                    <span class="ml-auto text-gray-500"><?php echo htmlspecialchars($lang['native']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700">
                    <?php echo __t('lang_save_changes'); ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const langForm = document.getElementById('language-form');
    const msgEl = document.getElementById('form-message');

    if (langForm) {
        langForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(langForm);
            
            msgEl.classList.remove('hidden');
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-yellow-100 text-yellow-800';
            msgEl.textContent = 'Saving...';
            
            try {
                const response = await fetch('language_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    msgEl.className = 'mb-4 p-3 rounded-md text-center bg-green-100 text-green-800';
                    msgEl.textContent = result.message;
                    // Reload the page to apply the new language everywhere
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                    msgEl.textContent = 'Error: ' + result.message;
                }
            } catch (error) {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                msgEl.textContent = 'An unexpected error occurred.';
            }
        });
    }
});
</script>

<?php
include_once 'common/bottom.php';
?>