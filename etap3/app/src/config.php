<?php
/**
 * Konfiguracja aplikacji PBF
 * Funkcje pomocnicze i połączenie z bazą
 */

// Połączenie z bazą danych
define('DB_HOST', $_ENV['DB_HOST'] ?? 'db');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'pbf');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'secret');

// Konfiguracja aplikacji
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Hobbit Expedition 2000');

// Stałe walidacji
define('MIN_PASSWORD_LENGTH', 8);
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 50);
define('MAX_BIO_LENGTH', 500);
define('MAX_DESCRIPTION_LENGTH', 2000);

/**
 * Połączenie PDO z bazą
 */
function db() {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Błąd połączenia z bazą danych. Spróbuj ponownie później.');
        }
    }
    return $pdo;
}

/**
 * CSRF Protection
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Pobiera aktualnie zalogowanego użytkownika
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND approved = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            session_destroy();
            return null;
        }
        
        return $user;
    } catch (Exception $e) {
        error_log('getCurrentUser error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Sprawdza czy użytkownik ma daną rolę
 */
function hasRole($role) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $roles = ['player' => 1, 'mg' => 2, 'admin' => 3];
    
    if (!isset($roles[$role]) || !isset($roles[$user['role']])) {
        return false;
    }
    
    return $roles[$user['role']] >= $roles[$role];
}

/**
 * Sprawdza czy użytkownik jest online (aktywność < 5 min)
 */
function isUserOnline($userId) {
    try {
        $stmt = db()->prepare("SELECT last_activity FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        $lastActivity = strtotime($user['last_activity']);
        $now = time();
        return ($now - $lastActivity) < 300;
    } catch (Exception $e) {
        error_log('isUserOnline error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Aktualizuje last_activity użytkownika (z optymalizacją - raz na 60s)
 */
function updateActivity() {
    if (isset($_SESSION['user_id'])) {
        if (!isset($_SESSION['last_activity_update']) || 
            time() - $_SESSION['last_activity_update'] > 60) {
            try {
                $stmt = db()->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $_SESSION['last_activity_update'] = time();
            } catch (Exception $e) {
                error_log('updateActivity error: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Oczyszcza i waliduje input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateUsername($username) {
    $len = strlen($username);
    if ($len < MIN_USERNAME_LENGTH || $len > MAX_USERNAME_LENGTH) {
        return false;
    }
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

function validatePassword($password) {
    return strlen($password) >= MIN_PASSWORD_LENGTH;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateLength($text, $min, $max) {
    $len = strlen($text);
    return $len >= $min && $len <= $max;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Dodaje powiadomienie dla użytkownika
 */
function addNotification($userId, $type, $title, $content = '', $link = '') {
    try {
        $stmt = db()->prepare("
            INSERT INTO notifications (user_id, type, title, content, link) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $type, $title, $content, $link]);
    } catch (Exception $e) {
        error_log('addNotification error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Loguje aktywność użytkownika
 */
function logActivity($actionType, $targetType = null, $targetId = null, $details = null) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = db()->prepare("
            INSERT INTO activity_logs (user_id, action_type, target_type, target_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $actionType, $targetType, $targetId, $details, $ipAddress]);
    } catch (Exception $e) {
        error_log('logActivity error: ' . $e->getMessage());
        return false;
    }
}

function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Formatuje datę
 */
function formatDate($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return "przed chwilą";
    if ($diff < 3600) return floor($diff / 60) . " min temu";
    if ($diff < 86400) return floor($diff / 3600) . " godz. temu";
    if ($diff < 2592000) return floor($diff / 86400) . " dni temu";
    
    return date('d.m.Y H:i', $timestamp);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function alert($message, $type = 'info') {
    $message = sanitize($message);
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
        $message
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

function getUnreadNotifications($userId) {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (Exception $e) {
        error_log('getUnreadNotifications error: ' . $e->getMessage());
        return 0;
    }
}

function getUnreadMessages($userId) {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) as count FROM private_messages WHERE recipient_id = ? AND read_at IS NULL");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (Exception $e) {
        error_log('getUnreadMessages error: ' . $e->getMessage());
        return 0;
    }
}

function sendAccountApproved($email, $username) {
    return true;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    updateActivity();
}
