<?php
require_once "../includes/db_connect.php";

class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAllUsers() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
 // Kiểm tra email đã tồn tại chưa
public function Kiemtra($email) {
    if (!empty($email)) {
        // Kiểm tra số lượng email trong CSDL
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $count = $stmt->fetchColumn(); // Lấy giá trị COUNT(*) trực tiếp
        
        return ($count > 0) ? 1 : 0; // 1 nếu email đã tồn tại, 0 nếu không tồn tại
    }
    return -1; // Trả về -1 nếu email rỗng
}

// Tạo tài khoản mới
public function More_user($data) {
    // Kiểm tra dữ liệu JSON
    if (!$data) {
        echo json_encode(["error" => "Dữ liệu không hợp lệ!"]);
        exit;
    }

    // Lấy thông tin
    $name = $data["name"] ?? "";
    $email = $data["email"] ?? "";
    $rawPassword = $data["password"] ?? "";
    $phone = $data["phone"] ?? "";

    // Kiểm tra nếu thiếu thông tin
    if (empty($name) || empty($email) || empty($rawPassword) || empty($phone)) {
        echo json_encode(["error" => "Vui lòng nhập đầy đủ thông tin!"]);
        exit;
    }

    // Kiểm tra email đã tồn tại chưa
    $email_check = $this->Kiemtra($email); // Kiểm tra email trước
    error_log("Kiểm tra email: " . $email_check); // Debug thêm
    if ($email_check === 1) {
        error_log("Email đã tồn tại: " . $email); // Debug nếu email đã tồn tại
        echo json_encode(["error" => "Email đã tồn tại!"]);
        exit; // Dừng tại đây, không tiếp tục thực hiện đăng ký
    }

    // Mã hóa mật khẩu sau khi kiểm tra hợp lệ
    $password = password_hash($rawPassword, PASSWORD_DEFAULT);

    try {
        // Chèn vào CSDL chỉ khi email chưa tồn tại
        $query = "INSERT INTO users (username, email, phonenumber, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$name, $email, $phone, $password]);

        if ($result) {
            echo json_encode(["success" => "Đăng ký thành công!"]);
        } else {
            echo json_encode(["error" => "Đăng ký thất bại!"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Lỗi: " . $e->getMessage()]);
    }
    exit;
}

    // Đăng nhập tài khoản
    public function Kiemtra_dangnhap($email, $password) {
    if (!empty($email) && !empty($password)) {
        $stmt = $this->conn->prepare("SELECT user_id, password FROM users WHERE email = :email");
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Lấy cả user_id và password
        // Kiểm tra mật khẩu
        if ($user && password_verify($password, $user['password'])) {
            return $user['user_id']; // Trả về ID nếu đúng mật khẩu
        }
    }
        return false;
    }
    
    public function More_user_dangnhap($data) {
        if (!$data) {
            echo json_encode(["error" => "Dữ liệu không hợp lệ!"]);
            exit;
        }
    
        $email = $data["email"];
        $password = $data["password"];
    
        if (empty($email) || empty($password)) {
            echo json_encode([
                "error" => "Vui lòng nhập đầy đủ thông tin!",
                "debug" => [
                    "email" => $email,
                    "password" => $password
                ]
            ]);
            exit;
        }
    
        $user_id = $this->Kiemtra_dangnhap($email, $password);
    
        if ($user_id) {
            // 🔥 Truy vấn toàn bộ thông tin user từ DB
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = :id");
            $stmt->bindValue(":id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user) {
                // 🧠 Lưu toàn bộ thông tin vào session
                $_SESSION["users_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["phonenumber"] = $user["phonenumber"];
                // Nếu có fullname thì thêm:
                // $_SESSION["fullname"] = $user["fullname"];
    
                echo json_encode([
                    "success" => "Đăng nhập thành công",
                    "user" => $user // gửi luôn về frontend
                ]);
            } else {
                echo json_encode(["error" => "Không tìm thấy thông tin người dùng!"]);
            }
        } else {
            echo json_encode(["error" => "Email hoặc mật khẩu không đúng!"]);
        }
    }
    
}    
?>
