<?php
/**
 * Message Reminder Cronjob
 * 
 * Runs every Friday at 9 AM to send reminder emails to route creators
 * about new unread messages they have received.
 * 
 * Setup:
 * Add to server crontab: 0 9 * * 5 /usr/bin/php /path/to/HavuGamification/cron/send-message-reminders.php
 */

require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/user.class.php');
require_once(__DIR__ . '/../classes/messagecenter.class.php');

try {
    // Get all users with unread messages
    $users_with_unread = MessageCenter::getUsersWithUnreadMessages();
    
    if (empty($users_with_unread)) {
        echo "[" . date('Y-m-d H:i:s') . "] No users with unread messages. Exiting.\n";
        exit(0);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Processing " . count($users_with_unread) . " user(s) with unread messages.\n";
    
    $emails_sent = 0;
    
    foreach ($users_with_unread as $user_data) {
        $recipient_user_id = $user_data['recipient_user_id'];
        $unread_count = $user_data['unread_count'];
        
        try {
            // Load user
            $user = new User($recipient_user_id);
            
            // Check if we already sent a reminder recently (prevent duplicates)
            $db = Tools::getDb();
            $stmt = $db->prepare("SELECT messages_last_reminder_sent FROM users WHERE id = ?");
            if (!$stmt) {
                $db->close();
                throw new Exception('Database error');
            }
            
            $stmt->bind_param('i', $recipient_user_id);
            if (!$stmt->execute()) {
                $stmt->close();
                $db->close();
                throw new Exception('Failed to fetch last reminder time');
            }
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $last_reminder = $row['messages_last_reminder_sent'];
            $stmt->close();
            $db->close();
            
            // Only send if no reminder sent in the last 6 days (to allow for Friday weekly)
            if ($last_reminder !== null) {
                $last_reminder_time = strtotime($last_reminder);
                $now = time();
                $days_since = ($now - $last_reminder_time) / (60 * 60 * 24);
                
                if ($days_since < 6) {
                    echo "[" . date('Y-m-d H:i:s') . "] Skipping " . $user->getEmail() . " - reminder already sent " . round($days_since, 1) . " days ago.\n";
                    continue;
                }
            }
            
            // Fetch the unread messages for details
            $messages = MessageCenter::getForRecipient($recipient_user_id, ['is_read' => 0]);
            
            // Build email
            $recipient_email = $user->getEmail();
            $recipient_name = $user->getFullName();
            
            $message_list = '';
            foreach ($messages as $msg) {
                $sender_name = 'Anonymous User';
                if ($msg['sender_user_id'] !== null) {
                    try {
                        $sender = new User($msg['sender_user_id']);
                        $sender_name = $sender->getFullName();
                    } catch (Exception $e) {
                        $sender_name = 'Unknown User';
                    }
                }
                
                $route_info = '';
                if ($msg['route_id'] !== null) {
                    try {
                        $db = Tools::getDb();
                        $stmt = $db->prepare("SELECT title FROM routes WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param('i', $msg['route_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $route_row = $result->fetch_assoc();
                                $route_info = " (Route: {$route_row['title']})";
                            }
                            $stmt->close();
                        }
                        $db->close();
                    } catch (Exception $e) {
                        // Non-fatal
                    }
                }
                
                $message_list .= "  - {$sender_name}{$route_info}: \"{$msg['title']}\"\n";
            }
            
            $subject = "HAVU: You have " . $unread_count . " unread message(s)";
            
            $body = "Hi " . htmlspecialchars($recipient_name) . ",\n\n"
                  . "You have " . $unread_count . " unread message(s) in your HAVU Messaging Center.\n\n"
                  . "Recent messages:\n"
                  . $message_list . "\n"
                  . "To review and respond to these messages, visit your admin dashboard:\n"
                  . "https://havupeli.jansoftworks.fi" . ROOT_DIR . "pages/admin/messages.php\n\n"
                  . "---\n"
                  . "HAVU Platform\n"
                  . "This is an automated reminder email. Do not reply to this address.";
            
            $safe_recipient = str_replace(["\r", "\n"], '', $recipient_email);
            $headers = "From: noreply@havupeli.jansoftworks.fi\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";
            
            if (mail($safe_recipient, $subject, $body, $headers)) {
                echo "[" . date('Y-m-d H:i:s') . "] ✓ Email sent to " . $recipient_email . " (" . $unread_count . " unread messages)\n";
                $emails_sent++;
                
                // Update last_reminder_sent timestamp
                $db = Tools::getDb();
                $stmt = $db->prepare("UPDATE users SET messages_last_reminder_sent = NOW() WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $recipient_user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                $db->close();
            } else {
                echo "[" . date('Y-m-d H:i:s') . "] ✗ Failed to send email to " . $recipient_email . "\n";
            }
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ✗ Error processing user ID " . $recipient_user_id . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Cronjob completed. " . $emails_sent . " email(s) sent.\n";
    exit(0);
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ✗ Cronjob failed: " . $e->getMessage() . "\n";
    exit(1);
}
