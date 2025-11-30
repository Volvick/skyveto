<?php
// File: header.php
// Location: /common/
// FINAL VERSION - Header updated as per screenshot, camera icon removed.
include_once __DIR__ . '/config.php';

// Fetch the logo from the database
$logo_stmt = $conn->query("SELECT logo_image FROM settings WHERE id = 1");
$logo_filename = $logo_stmt ? ($logo_stmt->fetch_assoc()['logo_image'] ?? 'logo.png') : 'logo.png';
$logo_path = ($logo_filename === 'logo.png') ? BASE_URL . '/assets/logo.png' : BASE_URL . '/uploads/' . $logo_filename;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="google-site-verification" content="X93Ju9YUY-PeDYcjiQH39xdZ4IvwoW1q0UQpY9sT2C0" />
    
    <?php
    $page_title = "Skyveto"; 
    $page_description = "Skyveto is your ultimate online shopping destination in India, offering a wide range of the latest fashion for men, women, and kids | Shop for kurtis, dresses, sarees, western wear, and more at the lowest prices | Enjoy a seamless shopping experience with multi-language support and an upcoming AI assistant | The CEO of Skyveto company is Abhishek Kushwaha.";
    
    if (basename($_SERVER['PHP_SELF']) == 'product_detail.php' && isset($product)) {
        $page_title = htmlspecialchars($product['name']) . " | Skyveto";
        $page_description = "Shop for " . htmlspecialchars($product['name']) . " online at the best price on Skyveto. " . htmlspecialchars(substr(strip_tags($product['description']), 0, 120)) . "...";
    }
    ?>
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="skyveto, online shopping, skyveto shopping, fashion, kurti & dress, bangles, men fashion, western wear, best online shopping app, cheap online shopping India, online shopping app with cash on delivery, top e-commerce apps in India, trusted online shopping platform, affordable online shopping, shopping app with free delivery, secure shopping app India, buy products online India, discount shopping apps, alternative to Meesho, apps like Flipkart, Amazon alternative in India, shopping apps like Meesho, cheaper than Flipkart, trusted Amazon alternative, Flipkart style shopping app, low price shopping app India, Meesho competitor shopping app, best app instead of Amazon, online clothing shopping India, men’s t-shirts online shopping, buy women’s dresses online, ethnic wear shopping app, kids fashion shopping app, affordable fashion online, trendy outfits online shopping, budget-friendly clothing app, online kurti shopping India, western wear shopping app, buy jeans online India, women’s sarees online, kids clothes online store, fashion deals online, low price clothing shopping app, best fashion app India, buy shirts online, Skyveto clothing deals, women’s tops online shopping, winter wear online shopping, buy shoes online India, affordable footwear shopping app, men’s sandals online, women’s heels shopping app, sneakers online shopping India, flip flops online shopping, Skyveto footwear deals, budget footwear online, trending shoes online shopping, kids footwear app, buy mobile phones online, best shopping app for gadgets, cheap headphones online India, Bluetooth speakers online shopping, laptop deals online, tablets shopping app, smartwatches online India, buy chargers online, Skyveto electronics offers, budget mobile phones online, buy refrigerators online, home appliances online shopping, washing machines shopping app, buy AC online India, affordable electronics shopping, Skyveto mobile deals, best electronics app India, headphones sale online, TVs online shopping India, gaming consoles shopping app, home appliances online, cheap kitchenware shopping app, buy mixer grinder online, Skyveto home products, budget cookware online, home decor shopping app, furniture online shopping India, affordable bedsheets online, trending kitchen products, online curtains shopping app, Skyveto home essentials, home lighting online, buy storage boxes online, cheap dinner sets online, bathroom accessories shopping app, low price home decor app, kitchen gadgets shopping app, affordable furniture online, trending home decor ideas, online bedsheet shopping, grocery shopping app India, buy groceries online cheap, Skyveto grocery deals, online supermarket app, snacks and drinks online shopping, household products shopping app, cheapest grocery app India, buy pulses and rice online, personal care products online, Skyveto daily essentials, affordable grocery delivery app, FMCG products online shopping, best grocery shopping app India, Skyveto online kirana store, health drinks online shopping, beverages shopping app, groceries home delivery India, best daily needs shopping app, low price groceries online, baby food online shopping, buy cosmetics online India, Skyveto beauty products, skincare online shopping app, affordable makeup products, hair care shopping app, perfume shopping app India, trending beauty products online, lipstick online shopping, budget skincare India, buy soap and shampoo online, Skyveto personal care store, best beauty products app, eyeliner and kajal online shopping, buy facewash online India, nail polish shopping app, sunscreen shopping app, men’s grooming products online, deodorant online shopping, buy shaving kit online, Skyveto beauty sale, baby products online shopping, kids toys shopping app, Skyveto baby care products, diapers online shopping app, baby food shopping app, kids wear online shopping India, buy school bags online, baby skincare shopping app, Skyveto kids toys, affordable baby products online, buy baby bottles online, kids shoes shopping app, toys under 500 India, buy strollers online, trending kids toys shopping, baby wipes shopping app, Skyveto kids fashion, cartoon character toys online, school supplies shopping app, best baby products app, best deals shopping app, Skyveto sale offers, 50% off online shopping India, buy 1 get 1 free shopping app, festival sale online shopping, Skyveto coupons and vouchers, daily deals online India, cashback shopping app, free delivery shopping app, flash sale shopping app, trending offers on fashion, Skyveto festive sale, clearance sale online shopping, cheap deals online app, Skyveto mega sale, holiday shopping deals, shopping app with coupons, discount shopping app, Skyveto new user offers, online festival discounts, buy watches online India, men’s wallets shopping app, women’s handbags online shopping, affordable sunglasses online, Skyveto accessories deals, jewelry online shopping app, budget jewelry shopping India, buy belts online India, men’s caps and hats shopping app, travel bags online shopping, Skyveto lifestyle products, watches under 1000 India, cheap jewelry online India, online handbags shopping app, trending accessories online, buy purses online India, Skyveto fashion accessories, hair accessories online shopping, affordable jewelry app, watches for women shopping app, best online shopping app for clothes in India, new shopping apps like Flipkart and Amazon, cheapest grocery delivery app in India, affordable online shopping app for fashion, trusted shopping app with cash on delivery, Skyveto trending online shopping app 2025, low price online shopping app in India, shopping app for electronics and mobiles India, best app for fashion and lifestyle shopping, Skyveto offers and discounts on shopping, Diwali shopping sale app, Holi shopping discounts, Skyveto festive offers, Raksha Bandhan gifts shopping app, Navratri clothes shopping online, Christmas deals shopping app, New Year offers shopping app, Skyveto Independence Day sale, best wedding shopping app India, Valentine’s Day gift shopping online, online shopping app India, sasta online shopping app, shopping app free delivery India, accha shopping app India, Skyveto shopping India, kapde online shopping app, sabse sasta shopping app, shopping app mobile se order karo, bharat ka best shopping app, Skyveto India deals, sports products online shopping, fitness equipment shopping app, health supplements online, Skyveto fitness deals, books and stationery shopping app, budget sportswear online, office supplies shopping app, Skyveto health products, protein powders online shopping, best fitness shopping app, mobile covers shopping app, Skyveto mobile accessories, buy power banks online, phone screen protectors app, affordable earphones online, Skyveto gadget store, trending tech accessories, cheap smart gadgets online, Skyveto smartwatch deals, buy gaming mouse online, buy chocolates online India, Skyveto snacks store, coffee and tea shopping app, biscuits and cookies online, soft drinks shopping app, Skyveto food products, budget snacks shopping app, healthy food online shopping, Skyveto instant noodles deals, online beverages store, camping products shopping app, Skyveto travel essentials, buy backpacks online India, affordable luggage bags, Skyveto outdoor products, tents and sleeping bags online, budget travel gear app, sunglasses online shopping India, Skyveto travel bags sale, buy travel pillows online, Skyveto gift store, birthday gifts shopping app, anniversary gifts online shopping, festive hampers online India, Skyveto gift deals, corporate gifting shopping app, personalized gifts online India, wedding gifts shopping app, Skyveto rakhi gift store, budget gifts shopping app, buy notebooks online, Skyveto stationery products, pens and pencils shopping app, office supplies online store, Skyveto books shopping app, affordable stationery online, calculators shopping app, desk organizers online India, Skyveto school products, budget office supplies, online shopping app Tamil, shopping app Telugu, shopping app Malayalam, Skyveto Kannada shopping, Hindi shopping app Skyveto, Bengali shopping app, Gujarati shopping app online, Marathi shopping app, Punjabi shopping app online, Skyveto South India shopping, premium fashion shopping app, luxury watches online shopping, Skyveto premium products, designer bags shopping app, branded shoes online shopping, Skyveto luxury deals, gold jewelry shopping app, high-end gadgets online, branded clothes online shopping, Skyveto exclusive offers, dog food online shopping, Skyveto pet products, cat food online shopping, pet toys shopping app, Skyveto pet care store, fish tank accessories online, pet grooming products app, Skyveto dog supplies, pet beds shopping app, affordable pet products, Pongal shopping app, Onam shopping deals, Eid shopping app India, Ganesh Chaturthi online shopping, Durga Puja shopping offers, Baisakhi online shopping, Karwa Chauth gift app, Makar Sankranti shopping app, Skyveto festive store, Lohri shopping online, cheapest online shopping app in India with cash on delivery, best shopping app for clothes electronics and groceries, online shopping app like Flipkart and Amazon 2025, Skyveto app trusted online shopping India, budget friendly shopping app for daily use, top rated shopping app with best reviews India, online shopping app with free shipping offers, new shopping app in India for all products, Skyveto trending shopping app with discounts, best mobile app for affordable shopping India, #Skyveto, #SkyvetoShopping, #SkyvetoApp, #SkyvetoDeals, #SkyvetoSale, #ShopWithSkyveto, #SkyvetoIndia, #SkyvetoOffers, #SkyvetoOnlineShopping, #SkyvetoStyle, #SkyvetoFashion, #SkyvetoDiscounts, #SkyvetoShoppingApp, #SkyvetoLife, #SkyvetoMegaSale, #SkyvetoStore, #SkyvetoTrend, #SkyvetoSavings, #SkyvetoFestiveSale, #SkyvetoLove, best budget shopping tips India, how to save money with Skyveto shopping, Skyveto online shopping guide, affordable fashion shopping in India, trending electronics deals online, why Skyveto is best online shopping app, Skyveto festive shopping hacks, best grocery delivery with Skyveto, Skyveto vs Flipkart vs Amazon, online shopping made easy with Skyveto, Skyveto shopping Delhi, Skyveto shopping Mumbai, Skyveto shopping Bangalore, Skyveto shopping Chennai, Skyveto shopping Hyderabad, Skyveto shopping Kolkata, Skyveto shopping Pune, Skyveto shopping Ahmedabad, Skyveto shopping Jaipur, Skyveto shopping Lucknow, India’s trending shopping app, Skyveto app best prices, shopping app for all categories, Skyveto 24x7 shopping, India’s trusted shopping platform, Skyveto low price deals, best cashback shopping app India, Skyveto mobile shopping app, Skyveto product reviews, shop online at Skyveto, new shopping app 2025 India, Skyveto best online store, shop anytime anywhere Skyveto, buy anything online India Skyveto, online shopping app Skyveto verified, best Skyveto deals today, shopping Skyveto discount store, Skyveto trending offers, Skyveto app download link, Skyveto India’s best shopping app">

    <!-- Favicon Code -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom_fonts.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; -webkit-user-select: none; user-select: none; overflow-x: hidden; }
        
        /* --- NEW: SKELETON LOADER STYLES --- */
        .skeleton {
            background-color: #e2e8f0;
            border-radius: 0.5rem;
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .skeleton-image { width: 100%; padding-top: 100%; } /* for aspect-square */
        .skeleton-text { height: 1rem; margin-top: 0.75rem; border-radius: 0.25rem; }
        .skeleton-text-sm { height: 0.75rem; width: 75%; margin-top: 0.5rem; border-radius: 0.25rem; }
        .skeleton-text-lg { height: 1.25rem; width: 50%; margin-top: 0.75rem; border-radius: 0.25rem; }
        /* --- END NEW --- */
    </style>
</head>
<body class="bg-gray-50">
    <!-- NEW HEADER SECTION -->
    <header class="bg-white shadow-sm sticky top-0 z-30 px-3 py-2 flex items-center gap-x-2">
        <!-- Logo -->
        <a href="<?php echo BASE_URL; ?>/index.php" class="flex-shrink-0">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Skyveto Logo" class="h-8 w-auto">
        </a>
        
        <!-- Search Bar (now a link to search page) -->
        <a href="<?php echo BASE_URL; ?>/search.php" class="flex-grow flex items-center bg-gray-100 border border-gray-200 rounded-lg px-3 text-sm cursor-pointer">
            <i class="fas fa-search text-gray-400"></i>
            <span class="text-gray-500 p-2">Search...</span>
            <span class="text-gray-500 p-1 ml-auto"><i class="fas fa-microphone"></i></span>
            <span class="text-gray-500 p-1"><i class="fas fa-camera"></i></span>
        </a>
        
        <!-- Icon Group -->
        <div class="flex items-center space-x-3 flex-shrink-0">
            <a href="wishlist.php" class="text-gray-600 text-xl"><i class="far fa-heart"></i></a>
            <a href="change_language.php" class="text-gray-600 text-xl"><i class="fas fa-language"></i></a>
            <!-- FIX: Added link to notifications.php -->
            <a href="notifications.php" class="text-gray-600 text-xl"><i class="far fa-bell"></i></a>
        </div>
    </header>
    <!-- END NEW HEADER SECTION -->

    <?php include_once __DIR__ . '/sidebar.php'; ?>
    <main class="pb-24">