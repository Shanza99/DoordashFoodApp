<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include '../config.php';

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->full_name) &&
    !empty($data->email) &&
    !empty($data->phone) &&
    !empty($data->password) &&
    !empty($data->user_type)
) {
    $full_name = $data->full_name;
    $email = $data->email;
    $phone = $data->phone;
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $user_type = $data->user_type;

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if($check_stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Email already exists"));
    } else {
        $query = "INSERT INTO users (full_name, email, phone, password, user_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $full_name, $email, $phone, $password, $user_type);

        if($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("success" => true, "message" => "User registered successfully"));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "Unable to register user"));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data"));
}
?>