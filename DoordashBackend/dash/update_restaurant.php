<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include '../config.php';

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->restaurant_id) &&
    !empty($data->name) &&
    !empty($data->cuisine_type)
) {
    $restaurant_id = $data->restaurant_id;
    $name = $data->name;
    $cuisine_type = $data->cuisine_type;
    $rating = $data->rating;
    $delivery_time = $data->delivery_time;
    $delivery_fee = $data->delivery_fee;
    $is_active = $data->is_active;
    $featured = $data->featured;

    $query = "UPDATE restaurants SET name = ?, cuisine_type = ?, rating = ?, delivery_time = ?, delivery_fee = ?, is_active = ?, featured = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdsdiii", $name, $cuisine_type, $rating, $delivery_time, $delivery_fee, $is_active, $featured, $restaurant_id);

    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("success" => true, "message" => "Restaurant updated successfully"));
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "Unable to update restaurant"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data"));
}
?>