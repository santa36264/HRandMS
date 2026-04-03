<?php

return [
    // Welcome messages
    'welcome' => 'ወደ :hotel_name እንኳን ደህና መጡ! 🏨',
    'welcome_message' => 'ወደ ሞላ ደህና መጡ :name! እኛ ከእርስዎ ጋር ደስተኛ ነን።',
    'welcome_registered' => 'እንደገና ወደ ሞላ ደህና መጡ! 👋',
    'welcome_not_registered' => 'ወደ ሞላ ደህና መጡ! እባክዎ ለመጀመር ይመዝገቡ።',

    // Commands
    'start' => '/start - ሮቦትን ጀምር',
    'help' => '/help - ረዳታ ያግኙ',
    'rooms' => '/rooms - ተገኝ ሆኖ ያሉ ክፍሎችን ይመልከቱ',
    'search' => '/search - ክፍሎችን ይፈልጉ',
    'bookings' => '/bookings - ሕجوزations ዎን ይመልከቱ',
    'mybookings' => '/mybookings - የእኔ ሕዋሳት',
    'service' => '/service - ክፍል አገልግሎት ምናሌ',
    'concierge' => '/concierge - ኮንሲየርጅ አገልግሎቶች',
    'airport' => '/airport - የአየር ማረፊያ አገልግሎቶች',
    'taxi' => '/taxi - ታክሲ አገልግሎት',
    'tour' => '/tour - ጉብኝት ሕዋሳት',
    'food' => '/food - ምግብ ምክር',
    'spa' => '/spa - ስፓ ስብሰባ',
    'language' => '/language - ቋንቋ ይቀይሩ',
    'analytics' => '/analytics - ትንታኔ ይመልከቱ',

    // Room details
    'room_name' => 'ክፍል',
    'room_type' => 'ዓይነት',
    'room_capacity' => 'አቅም',
    'room_price' => 'ዋጋ',
    'room_amenities' => 'ምቹ ሁኔታዎች',
    'room_available' => 'ተገኝ',
    'room_booked' => 'ተያዘ',
    'room_maintenance' => 'ጥገና ስር',

    // Booking flow
    'booking_start' => 'የሕዋስ ሂደት ይጀምራል...',
    'booking_select_dates' => 'እባክዎ ቀናትዎን ይምረጡ',
    'booking_check_in' => 'ወደ ውስጥ ማስገባት ቀን',
    'booking_check_out' => 'ወደ ውጪ ማስወጣት ቀን',
    'booking_guests' => 'የእንግዶች ቁጥር',
    'booking_guest_names' => 'የእንግዶች ስሞች',
    'booking_special_requests' => 'ልዩ ጥያቄዎች (አማራጭ)',
    'booking_confirm' => 'ሕዋስ ያረጋግጡ',
    'booking_confirmed' => '✅ ሕዋስ ተረጋግጧል!',
    'booking_reference' => 'ሕዋስ ማመሳከሪያ',
    'booking_total' => 'ጠቅላላ መጠን',

    // Notifications
    'notification_checkin' => '🔔 ወደ ውስጥ ማስገባት ማስታወሻ',
    'notification_checkout' => '🛎️ ወደ ውጪ ማስወጣት ማስታወሻ',
    'notification_review' => '🌟 እባክዎ ቆይታዎን ደረጃ ይስጡ',
    'notification_payment' => '💳 ክፍያ ማስታወሻ',

    // Errors
    'error_general' => '❌ ስህተት ተከስቷል። እባክዎ እንደገና ይሞክሩ።',
    'error_booking_not_found' => '❌ ሕዋስ አልተገኘም።',
    'error_room_not_available' => '❌ ክፍል አይገኝም።',
    'error_invalid_input' => '❌ ልክ ያልሆነ ግቤት። እባክዎ እንደገና ይሞክሩ።',
    'error_unauthorized' => '❌ መዳረሻ ተከልክሏል። ይህ ትዕዛዝ ለአስተዳዳሪዎች ብቻ ነው።',

    // Success messages
    'success_booking' => '✅ ሕዋስ ተሳክቷል!',
    'success_checkin' => '✅ ወደ ውስጥ ማስገባት ተሳክቷል!',
    'success_checkout' => '✅ ወደ ውጪ ማስወጣት ተሳክቷል!',
    'success_payment' => '✅ ክፍያ ተቀበልን!',
    'success_review_submitted' => '✅ ለእርስዎ ግምገማ 감사합니다!',

    // Room service
    'room_service_menu' => '🍽️ ክፍል አገልግሎት ምናሌ',
    'room_service_breakfast' => '🍳 ቁርስ',
    'room_service_main_course' => '🍝 ዋና ምግብ',
    'room_service_drinks' => '🥤 መጠጦች',
    'room_service_dessert' => '🍰 ጣፋጭ',
    'room_service_add_to_cart' => '➕ ወደ ቅጠሎ ጨምር',
    'room_service_view_cart' => '🛒 ቅጠሎ ይመልከቱ',
    'room_service_confirm_order' => '✅ ትዕዛዝ ያረጋግጡ',
    'room_service_order_confirmed' => '✅ ትዕዛዝ ተረጋግጧል!',
    'room_service_estimated_delivery' => 'ግምታዊ ማድረሻ',

    // Concierge services
    'concierge_airport' => '✈️ የአየር ማረፊያ 픽업',
    'concierge_taxi' => '🚕 ታክሲ አገልግሎት',
    'concierge_tour' => '🎫 ጉብኝት ሕዋስ',
    'concierge_food' => '🍽️ ምግብ ቤት',
    'concierge_spa' => '💆 ስፓ ስብሰባ',
    'concierge_booking_confirmed' => '✅ አገልግሎት ተያዘ!',
    'concierge_confirmation_code' => 'ማረጋገጫ ኮድ',

    // Reviews
    'review_rating' => '🌟 ቆይታዎ እንዴት ነበር?',
    'review_rate_us' => 'እባክዎ ቆይታዎን ደረጃ ይስጡ',
    'review_what_liked' => '👍 በጣም ምን ወደደህ?',
    'review_improvement' => '💡 ምን ሊሻሻል ይችላል?',
    'review_recommend' => '🤝 ሌሎችን ታክመውን ታሰብ?',
    'review_permission' => 'ግምገማዎን በሕዝብ ማሳየት እንችላለን?',
    'review_thank_you' => '🙏 ለእርስዎ ግምገማ 감사합니다!',
    'review_submitted' => '✅ ግምገማ በተሳካ ሁኔታ ተላልፏል!',

    // Analytics
    'analytics_dashboard' => '📊 ትንታኔ ዳሽቦርድ',
    'analytics_average_rating' => 'አማካይ ደረጃ',
    'analytics_total_reviews' => 'ጠቅላላ ግምገማዎች',
    'analytics_completion_rate' => 'ማጠናቀቅ ደረጃ',
    'analytics_response_time' => 'ምላሽ ጊዜ',
    'analytics_keywords' => 'ከፍተኛ ቁልፍ ቃላት',
    'analytics_negative_alerts' => 'አሉታዊ ግምገማዎች',

    // Admin commands
    'admin_broadcast' => '📢 ስርጭት መልእክት',
    'admin_stats' => '📊 ፈጣን ሜትሪክስ',
    'admin_checkin' => '✅ ማንዋል ወደ ውስጥ ማስገባት',
    'admin_checkout' => '🚪 ማንዋል ወደ ውጪ ማስወጣት',
    'admin_assign' => '🏠 ክፍል ይመድቡ',
    'admin_maintenance' => '🔧 ክፍል ጥገና',

    // Language
    'language_select' => 'ቋንቋ ይምረጡ / Select your language',
    'language_english' => '🇬🇧 English',
    'language_amharic' => '🇪🇹 አማርኛ',
    'language_changed' => '✅ ቋንቋ ወደ አማርኛ ተቀይሯል',

    // Buttons
    'button_yes' => 'አዎ',
    'button_no' => 'አይ',
    'button_back' => 'ተመለስ',
    'button_next' => 'ቀጣይ',
    'button_confirm' => 'ያረጋግጡ',
    'button_cancel' => 'ይሰርዙ',
    'button_submit' => 'ያስገቡ',
    'button_skip' => 'ዝለል',
    'button_more' => 'ተጨማሪ',
    'button_details' => 'ዝርዝር',
    'button_view' => 'ይመልከቱ',
    'button_edit' => 'ያርትዑ',
    'button_delete' => 'ይሰርዙ',
];
