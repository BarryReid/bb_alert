<?php

    // Includes
    include 'config.php';
    include 'include/prowl_class.php';
    include 'include/snmp.php';
    include 'include/common.php';

    // Triger main program
    main();

    function main() {
        // Unset some used vars
        if (isset($msgFound)) {unset($msgFound);}
        if (isset($msgDeliveredToPhone)) {unset($msgDeliveredToPhone);}
        if (isset($alertCount)) {unset($alertCount);}
        if (isset($$bbTestUserID)) {unset($bbTestUserID);}
        
        // Setup some varables
        $initUserMsgTotalProc = null;
        $newUserMsgTotalProc = null;

        $initUserMsgPending = null;
        $newUserMsgPending = null;
        
        $bbTestUserID = getBlackBerryUserID();
        logThat('BlackBerry test user ID : ' . $bbTestUserID, 1);
        
        // Setup new instance of alerting
        $prowl = new Prowl();
        $prowl->setApiKey(PROWL_API_KEYS);
        
        // Start main program
        logThat('Starting new check', 1);
        
        // Do snmp call to BES and return count of User Messages Processed
        $initUserMsgTotalProc = snmpget(BB_SERVER, BB_SNMP_COMMUNITY_STRING, BB_BASE_SNMP_OID . '.30.1.40.1.' . $bbTestUserID);
        $initUserMsgTotalProc = cleanInt($initUserMsgTotalProc);
        logThat('Initial count of User Messages Processed = ' . $initUserMsgTotalProc, 1);
        
        // Do snmp call to BES and return count of User Messages pending
        $initUserMsgPending = snmpget(BB_SERVER, BB_SNMP_COMMUNITY_STRING, BB_BASE_SNMP_OID . '.30.1.44.1.' . $bbTestUserID);
        $initUserMsgPending = cleanInt($initUserMsgPending);
        logThat('Initial count of User Messages Pending = ' . $initUserMsgPending, 1);
        
        // Send ping email
        logThat('Sending ping email', 1);
        $sendStatus = mail(BB_TEST_USER_EMAIL, 'ID:' . ($initUserMsgTotalProc + 1), '');
        //$sendStatus = TRUE; // For Testing!
        if ($sendStatus == TRUE) {
            // Succsesfuly sent ping email. Move forward
            logThat('Ping email send succsesfuly!', 1);
            
            // Check if ping email arived in 'BES Test' mailbox
            if ($mailBox = imap_open('{' . IMAP_SERVER . '}' . IMAP_CHECK_FOLDER, IMAP_MAILBOX_USER, IMAP_MAILBOX_PASS)) {
                logThat('Connection to IMAP server has been established', 1);
                
                $checkCount = 0;
                do {
                    if (imap_ping($mailBox)) {
                        logThat('Connection to IMAP server is still up', 1);
                    }
                    else {
                        logThat('Connection to IMAP server has gone DOWN!', 1);
                        logThat('Restarting connection to IMAP server', 1);
                        if ($mailBox = imap_open('{' . IMAP_SERVER . '}' . IMAP_CHECK_FOLDER, IMAP_MAILBOX_USER, IMAP_MAILBOX_PASS)) {
                            logThat('Connection to IMAP server has been restarted', 1);
                        }
                    }
                    if (imap_num_msg($mailBox) == 0) {
                        logThat('Number of Messages Found : ' . imap_num_msg($mailBox), 1);
                        logThat('Waiting for ' . IMAP_RECHECK_INTERVAL . ' seconds before recheck', 1);
                        logThat('Adding 1 to check counter', 1);
                        $checkCount = ($checkCount + 1);
                        sleep(IMAP_RECHECK_INTERVAL);
                        logThat('Rechecking IMAP server', 1);
                    }
                    else if (imap_num_msg($mailBox) != 0) {
                        logThat('Number of Messages Found : ' . imap_num_msg($mailBox), 1);
                        // We have found messages in the INBOX and will now work through them
                        $mc = imap_check($mailBox);
                        $result = imap_fetch_overview($mailBox, "1:{$mc->Nmsgs}",0);
                        logThat('Looking for the correct subject string', 1);
                        foreach ($result as $overview) {
                            $subject = $overview->subject;
                            if ($subject == 'ID:' . ($initUserMsgTotalProc + 1)) {
                                logThat('Found message with correct subject string', 1);
                                $msgFound = 1;
                                $msgFoundUID = $overview->uid; // This will be used to delete message later
                            }
                            else {
                                logThat('ID string not found in email with UID : ' . $overview->uid, 1);
                                logThat('Deleting email with UID ' . $overview->uid . '. This email is not what we are looking for', 1);
                                imap_delete($mailBox, $overview->uid, 1);
                                imap_expunge($mailBox);
                            }
                        }
                    }
                    if (($checkCount * IMAP_RECHECK_INTERVAL) > IMAP_RECHECK_ALERT_TIME) {
                        logThat('Sending alert - Mail has not arived in GW mailbox after ' . round(($checkCount * IMAP_RECHECK_INTERVAL) / 60, 2) . ' min', 1);
                        $alertMessage = $prowl->add(PROWL_APP_NAME, 'SMTP -> MailBox | ' . date("Y-m-d H:i:s"), 2, 'Email has not arived in mailbox after ' . round(($checkCount * IMAP_RECHECK_INTERVAL) / 60, 2) . ' min.', NULL);
                        $msgFound = 0;
                        
                    }
                } while(!isset($msgFound));
                
                // We now have the message we are looking for. lets wait to see if it gets to the BlackBerry
                if ($msgFound == 1) {
                    $phoneCheckCount = 0;
                    do {
                        // Get total number of messages delivered to BlackBerry Phone
                        logThat('Checking if email arived on BlackBerry Phone', 1);
                        $newUserMsgTotalProc = snmpget(BB_SERVER, BB_SNMP_COMMUNITY_STRING, BB_BASE_SNMP_OID . '.30.1.40.1.' . $bbTestUserID);
                        $newUserMsgTotalProc = cleanInt($newUserMsgTotalProc);
                        if ($initUserMsgTotalProc == $newUserMsgTotalProc) {
                            logThat('Email has not yet arived on BlackBerry phone', 1);
                            logThat('Checking again in ' . BB_CHECK_PHONE_DELIVERY_INTERVAL . ' seconds', 1);
                            $phoneCheckCount = ($phoneCheckCount + 1);
                            sleep(BB_CHECK_PHONE_DELIVERY_INTERVAL);
                        }
                        else if (($initUserMsgTotalProc + 1) == $newUserMsgTotalProc) {
                            logThat('Email has arived on BlackBerry phone', 1);
                            imap_delete($mailBox, $msgFoundUID, 1);
                            imap_expunge($mailBox);
                            imap_close($mailBox);
                            $msgDeliveredToPhone = 1;
                        }
                        if (($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) > BB_CHECK_PHONE_ALERT_TIME and !isset($alertCount)) {
                            logThat('Sending alert - Mail with UID ' . $msgFoundUID . ' has not arived on BlackBerry phone after ' . round(($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) / 60, 2) . ' min', 1);
                            $alertMessage = $prowl->add(PROWL_APP_NAME, 'MailBox -> BlackBerry | ' . date("Y-m-d H:i:s"), 2, 'Email with UID ' . $msgFoundUID . ' has not arived on BlackBerry phone after ' . round(($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) / 60, 2) . ' min.', NULL);
                            $alertCount = 1;
                        }
                        else if (($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) > BB_CHECK_PHONE_ALERT_TIME and isset($alertCount)) {
                            if (($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) > (BB_CHECK_PHONE_ALERT_TIME * ($alertCount + 1))) {
                                logThat('Sending alert ' . ($alertCount + 1) . ' - Mail with UID ' . $msgFoundUID . ' has still not arived on BlackBerry phone after ' . round(($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) / 60, 2) . ' min', 1);
                                $alertMessage = $prowl->add(PROWL_APP_NAME, 'MailBox -> BlackBerry | ' . date("Y-m-d H:i:s"), 2, 'Email with UID ' . $msgFoundUID . ' has still not arived on BlackBerry phone after ' . round(($phoneCheckCount * BB_CHECK_PHONE_DELIVERY_INTERVAL) / 60, 2) . ' min. Alert Count : ' . ($alertCount + 1), NULL);
                                $alertCount = ($alertCount + 1);
                            }
                            else {}
                        }
                    } while (!isset($msgDeliveredToPhone));
                }
                else if ($msgFound == 0) {
                    logThat('Giving up on trying to find email' ,1);
                }
            }
            else {
                logThat('Connection to IMAP server could not be established', 1);
            }
        }
        else if ($sendStatus == FALSE) {
            // Send ALERT reporting problem with SMTP server!
            logThat('Ping email FAILED to send!', 1);
        }
        logThat('Waiting ' . round(FULL_CHECK_INTERVAL / 60, 2) . ' min before we run the check again', 1);
        sleep(FULL_CHECK_INTERVAL);
        main();
    }
?>
