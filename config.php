<?php

    // BlackBerry Server Details
    define('BB_SERVER', '192.168.91.53');
    define('BB_SNMP_COMMUNITY_STRING', 'BESMON');
    define('BB_BASE_SNMP_OID', '1.3.6.1.4.1.3530.5');
    define('BB_TEST_USER_ID', '"BESTEST"');
    define('BB_TEST_USER_EMAIL', 'bestest@fire.nsw.gov.au');

    // Email Environment Details
    define('SMTP_SERVER', '192.168.5.8');
    define('IMAP_SERVER', '192.168.5.7');
    define('IMAP_SERVER_PORT', '143');
    define('IMAP_MAILBOX_USER', 'POBox_BBTEST');
    define('IMAP_MAILBOX_PASS', 'postman01');
    define('IMAP_CHECK_FOLDER', 'INBOX');
    define('IMAP_RECHECK_INTERVAL', 5);
    
    // Program Settings
    define('BB_CHECK_PHONE_DELIVERY_INTERVAL', 5);
    define('LOG_LEVEL', 1); // Log Level 1 - 5 where 5 is the most verbose
    define('FULL_CHECK_INTERVAL', (60*5)); // Check interval in min
    
    // Prowl Settings
    define('PROWL_API_KEYS', '80e4a01635a7fa087bff899339dc931da7bfc45b'); // Barry Reid = 1st in list
    define('PROWL_APP_NAME', 'BES Alerter');
    
    // Alerts
    define('IMAP_RECHECK_ALERT_TIME', (60*10)); // Alert after time in min
    //define('IMAP_RECHECK_ALERT_TIME', (15)); // TESTING ^
    define('BB_CHECK_PHONE_ALERT_TIME', (60*5)); // Alert if message has not arived on BB Phone after x min
    //define('BB_CHECK_PHONE_ALERT_TIME', (30)); //TESTING ^

?>
