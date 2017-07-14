<?php

/* 
 * file is use to store constant regarding MLG.
 */

//Defined Roles and their Values.


//Admin role
define('ADMIN_ROLE_ID', 1);

//parent role
define('PARENT_ROLE_ID', 2);

//teacher role
define('TEACHER_ROLE_ID', 3);

//student role
define('STUDENT_ROLE_ID', 4);

//guest teacher role
define('GUEST_TEACHER_ROLE_ID', 5);

//principal role
define('PRINCIPAL_ROLE_ID', 6);

//default image directory
define('DEFAULT_IMAGE_DIRECTORY', 'upload/');






//PAYPAL CONSTANTS

//Switch account Sandbox or Live.
define('USE_SANDBOX_ACCOUNT', TRUE);

//define('PAYPAL_SANDBOX_CREDENTIAL',  "Ae4VFzV0AGtbdhazK0lLDGwrRK9HmVuSDQCryUVwEJ5EWiRGJK-rdDWQ8oCTOv4p8x_2t8KjAlmUs-7w"
//             . ":" . "EJfblSWi6_UVJqQQ6mRB8AuX5lG3iGlgqFsyPdtWqNIPqG0KQ8CKGmbEwG9xf40DsfqL45kkuAQ1KVye");
define('PAYPAL_SANDBOX_CREDENTIAL',  "ASl1fe35bo4Lr88sKRk-55RQ_QB0pcw8soNoc_1Xs6pEG1Hb03A5fybF_RxcevwaRuYKb58eaeqt6xWm"
             . ":" . "EINlqW_H3poKIfFUdheK6ArNyQC4-Wv15CwXao__-ESEumU6iq7sCpN78r7gwmGfgJ0EkaSZGfL9JZEn");

define("PAYPAL_LIVE_CREDENTIAL", "Aft1-THtqfdBuIcmL3_rgunICfhYWpqO76okvPPrIgS66gxGN_P9QSnTcHUMc1LvVM9z8i2rSqlu2d-D"
               . ":" . "ECVdP3gZHIDUjRiDnPYJ-lR8qRvOk68YE_NRF61BHucn-DmHUPdjbIHgC_X_8n-Dqxb7BnFJ-Y8VjUyZ");

// Paypal Currency
define('PAYPAL_CURRENCY', 'USD');



//quiz_pass_score
define('QUIZ_PASS_SCORE', 80);


// Class Students classification
define('NO_ATTACK', 0); //student not present in user_quiz
define('REMEDIAL', 50); //student scored 0 to 50
define('STRUGGLING', 79);  // student scored 51 to 79
define('ON_TARGET', 85);  //student scored 80 to 85
define('OUTSTANDING',95 ); // student scored 86 to 95
define('GIFTED',100 );  // 96 to 100


// Student conquered score
define('CONQUERED', 80);


//Awards criteria
define('RED_BADGE', 85); //student have 80-85 score
define('GREEN_BADGE',90); //student have 86-90 score
define('STAR_BADGE', 95);  // student have 91-95 score
define('CROWN_BADGE', 100);  //student have 96-100 score



// Notification Category Ids
define('NOTIFICATION_CATEGORY_ANALYTICS', 1);
define('NOTIFICATION_CATEGORY_OFFERS', 2);
define('NOTIFICATION_CATEGORY_SUBSCRIPTIONS', 3);
define('NOTIFICATION_CATEGORY_COUPONS', 4);

//Notification limit
define('NOTIFICATION_LIMIT', 10);





// ALERT_BEFORE_SUBSCRIPTION_EXPIRE" contain the no. of day after child subscription will expire
define('ALERT_BEFORE_SUBSCRIPTION_EXPIRE', 7);

// Standard Type
define('STANDARD_TYPE',  json_encode( array('state','cc')));
// RANGE FOR PAGINATION
define('RANGE', 10);