<?php
require_once __DIR__ . '/../models/CouponModel.php';

class CouponController {
    private $couponModel;

    public function __construct() {
        $this->couponModel = new CouponModel();
    }

    public function create() {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input)) {
                throw new InvalidArgumentException("Invalid or empty JSON input");
            }

            $result = $this->couponModel->createCoupon($input);

            if ($result['success']) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'coupon_id' => $result['coupon_id'],
                        'message' => 'Coupon created successfully'
                    ]
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'errors' => $result['errors'] ?? ['message' => $result['message']]
                ]);
            }

        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred while creating the coupon',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function getCoupon($id) {
        header('Content-Type: application/json');
        try {
            $id = $id ?? 0;
            $coupon = $this->couponModel->getCouponById($id);
            if ($coupon) {
                echo json_encode(['status' => 'success', 'data' => $coupon]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Coupon not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve coupon & may be id does not exist']);
        }
    }

    public function getAllCoupons() {
        header('Content-Type: application/json');
        
        try {
            // Get query parameters
            $filters = [
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 10,
                'type' => $_GET['type'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Get coupons from model
            $result = $this->couponModel->getAllCoupons($filters);
            
            // Prepare response
            $response = [
                'status' => 'success',
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total_pages' => $result['total_pages']
                ]
            ];
            
            http_response_code(200);
            echo json_encode($response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to retrieve coupons',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function update($couponId) {
        header('Content-Type: application/json');
    
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input)) {
                throw new InvalidArgumentException("Invalid or empty JSON input");
            }
    
            $result = $this->couponModel->updateCoupon($couponId, $input);
    
            if ($result['success']) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'message' => 'Coupon updated successfully'
                    ]
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'errors' => $result['errors'] ?? ['message' => $result['message']]
                ]);
            }
    
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred while updating the coupon',
                'debug' => $e->getMessage()
            ]);
        }
    }
    
    public function delete($couponId) {
        // Call the deleteCoupon method from the model to delete the coupon and related data
        $result = $this->couponModel->deleteCoupon($couponId);
        // Return the response based on the result of deletion
        if ($result['success']) {
            echo json_encode(['status' => 'success', 'message' => 'Coupon deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
        }
    }
}
