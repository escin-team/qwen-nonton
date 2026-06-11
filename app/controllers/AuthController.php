<?php
/**
 * ==========================================
 * AUTH CONTROLLER (FINAL FIX)
 * ==========================================
 * Menangani Login, Register, dan Logout.
 * Compatible: PHP 5.6 - 8.3
 */
class AuthController extends Controller {

    /**
     * 1. Menampilkan Form Login
     * Dipanggil saat user akses: /auth/login (GET)
     */
    public function showLoginForm() {
        // Jika user sudah login, langsung redirect ke Home
        if (isset($_SESSION['user_id'])) {
            redirect('');
            return;
        }

        // Ambil pesan error dari session (jika ada)
        $error_msg = '';
        if (isset($_SESSION['flash_error'])) {
            $error_msg = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']); // Hapus setelah dibaca
        }

        // Tampilkan view login
        $this->view('auth/login', array(
            'title' => 'Login - Nontonin',
            'error' => $error_msg
        ));
    }

    /**
     * 2. Memproses Data Login
     * Dipanggil saat user submit form: /auth/login (POST)
     */
    public function login() {
        // Pastikan request adalah POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/login');
            return;
        }

        // Ambil input (Anti Error PHP 8.3 dengan isset)
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Validasi dasar
        if (empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'Username dan Password wajib diisi!';
            redirect('auth/login');
            return;
        }

        // Cek ke Database
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
            $stmt->execute(array($username));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi Password (BCRYPT)
            if ($user && password_verify($password, $user['password'])) {
                // LOGIN SUKSES
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect ke Homepage
                redirect(''); 
            } else {
                // LOGIN GAGAL
                $_SESSION['flash_error'] = 'Username atau Password salah!';
                redirect('auth/login');
            }
        } catch (Exception $e) {
            // Jika database error
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem. Coba lagi nanti.';
            redirect('auth/login');
        }
    }

    /**
     * 3. Proses Logout
     * Dipanggil saat user akses: /auth/logout
     */
    public function logout() {
        // Hapus semua data session
        session_unset();
        session_destroy();
        
        // Mulai session baru untuk flash message (opsional)
        session_start();
        $_SESSION['flash_success'] = 'Anda telah berhasil logout.';
        
        // Redirect ke halaman login
        redirect('auth/login');
    }

    /**
     * (Opsional) Menampilkan Form Register
     */
    public function showRegisterForm() {
        $this->view('auth/register', array(
            'title' => 'Register - Nontonin'
        ));
    }

    /**
     * FIX BUG #4: Method register() yang hilang - Menangani registrasi user baru
     * Dipanggil saat user submit form: /auth/register (POST)
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/register');
            return;
        }

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
        $password = isset($_POST['password']) ? $_POST['password']       : '';

        if (empty($username) || empty($password) || empty($email)) {
            $_SESSION['flash_error'] = 'Semua field wajib diisi!';
            redirect('auth/register');
            return;
        }

        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Password minimal 6 karakter!';
            redirect('auth/register');
            return;
        }

        try {
            // FIX BUG #7: Gunakan Database::getInstance() langsung (lazy connection)
            $db = Database::getInstance()->getConnection();

            // Cek apakah username sudah ada
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute(array($username));
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = 'Username sudah digunakan!';
                redirect('auth/register');
                return;
            }

            // Hash password dengan bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user baru
            $stmt = $db->prepare(
                "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute(array($username, $email, $hashedPassword));

            $_SESSION['flash_success'] = 'Registrasi berhasil! Silakan login.';
            redirect('auth/login');

        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem. Coba lagi nanti.';
            redirect('auth/register');
        }
    }
}