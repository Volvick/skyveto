<?php
// File: help_center.php (FULLY TRANSLATED)
// Location: /
include_once 'common/config.php';
include_once 'common/header.php';

// Fetch contact details from the database
$contact_details = $conn->query("SELECT * FROM contact_details WHERE id = 1")->fetch_assoc();

// Define FAQs using translation keys
$faqs = [
    __t('help_faq_orders') => [
        __t('help_faq_q_place_order') => __t('help_faq_a_place_order'),
        __t('help_faq_q_track_order') => __t('help_faq_a_track_order'),
    ],
    __t('help_faq_shipping') => [
        __t('help_faq_q_shipping_address') => __t('help_faq_a_shipping_address'),
    ],
    __t('help_faq_returns') => [
        __t('help_faq_q_return_policy') => __t('help_faq_a_return_policy'),
        __t('help_faq_q_get_refund') => __t('help_faq_a_get_refund'),
    ]
];
?>
<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i><?php echo __t('help_back_to_account'); ?></a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('help_title'); ?></h1>

    <div class="bg-white p-4 rounded-lg shadow-sm text-center mb-6">
        <p class="font-semibold"><?php echo __t('help_quick_help'); ?></p>
        <img src="https://img.freepik.com/free-vector/delivery-service-with-masks-concept_23-2148505104.jpg" class="w-48 mx-auto my-4">
        <a href="order.php" class="text-blue-600 font-semibold"><?php echo __t('help_get_help_link'); ?></a>
    </div>

    <!-- Contact Section -->
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-700 mb-3"><?php echo __t('help_contact_title'); ?></h2>
        <div class="bg-white rounded-lg shadow-sm divide-y">
            
            <?php if (!empty($contact_details['instagram_chat_link'])): ?>
            <a href="<?php echo htmlspecialchars($contact_details['instagram_chat_link']); ?>" target="_blank" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fab fa-instagram w-6 text-pink-500"></i>
                <span class="ml-3 font-medium"><?php echo htmlspecialchars($contact_details['instagram_chat_name']); ?></span>
                <i class="fas fa-external-link-alt text-gray-400 ml-auto text-xs"></i>
            </a>
            <?php endif; ?>
            
            <?php if (!empty($contact_details['instagram_helper_link'])): ?>
            <a href="<?php echo htmlspecialchars($contact_details['instagram_helper_link']); ?>" target="_blank" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fab fa-instagram w-6 text-purple-500"></i>
                <span class="ml-3 font-medium"><?php echo htmlspecialchars($contact_details['instagram_helper_name']); ?></span>
                <i class="fas fa-external-link-alt text-gray-400 ml-auto text-xs"></i>
            </a>
            <?php endif; ?>
            
            <?php if (!empty($contact_details['facebook_link'])): ?>
            <a href="<?php echo htmlspecialchars($contact_details['facebook_link']); ?>" target="_blank" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fab fa-facebook-f w-6 text-blue-600"></i>
                <span class="ml-3 font-medium"><?php echo htmlspecialchars($contact_details['facebook_name']); ?></span>
                <i class="fas fa-external-link-alt text-gray-400 ml-auto text-xs"></i>
            </a>
            <?php endif; ?>

            <?php if (!empty($contact_details['whatsapp_number'])): ?>
            <a href="https://wa.me/<?php echo htmlspecialchars($contact_details['whatsapp_number']); ?>" target="_blank" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fab fa-whatsapp w-6 text-green-500"></i>
                <span class="ml-3 font-medium"><?php echo __t('help_contact_whatsapp'); ?></span>
                <i class="fas fa-external-link-alt text-gray-400 ml-auto text-xs"></i>
            </a>
            <?php endif; ?>

            <?php if (!empty($contact_details['email_address'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($contact_details['email_address']); ?>" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fas fa-envelope w-6 text-gray-500"></i>
                <span class="ml-3 font-medium"><?php echo __t('help_contact_email'); ?></span>
            </a>
            <?php endif; ?>

            <?php if (!empty($contact_details['phone_number']) && $contact_details['phone_number'] !== 'Enter Number Here'): ?>
            <a href="tel:<?php echo htmlspecialchars($contact_details['phone_number']); ?>" class="flex items-center p-4 hover:bg-gray-50">
                <i class="fas fa-phone-alt w-6 text-gray-500"></i>
                <span class="ml-3 font-medium"><?php echo __t('help_contact_phone'); ?>: <?php echo htmlspecialchars($contact_details['phone_number']); ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <?php foreach ($faqs as $category => $questions): ?>
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-700 mb-3"><?php echo $category; ?></h2>
        <div class="bg-white rounded-lg shadow-sm divide-y">
            <?php foreach ($questions as $question => $answer): ?>
            <details class="p-4 group">
                <summary class="flex justify-between items-center cursor-pointer font-semibold">
                    <?php echo $question; ?>
                    <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                </summary>
                <p class="mt-2 text-gray-600 text-sm">
                    <?php echo $answer; ?>
                </p>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</main>
<?php
include_once 'common/bottom.php';
?>