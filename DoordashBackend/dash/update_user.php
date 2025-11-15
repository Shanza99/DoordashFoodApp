<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include '../config.php';

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->user_id) &&
    !empty($data->full_name) &&
    !empty($data->email) &&
    !empty($data->user_type)
) {
    $user_id = $data->user_id;
    $full_name = $data->full_name;
    $email = $data->email;
    $user_type = $data->user_type;
    $is_active = $data->is_active;

    // Check if email already exists for other users
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if($check_stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Email already exists"));
    } else {
        $query = "UPDATE users SET full_name = ?, email = ?, user_type = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssii", $full_name, $email, $user_type, $is_active, $user_id);

        if($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("success" => true, "message" => "User updated successfully"));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "Unable to update user"));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data"));
}
?>