<?php
require_once __DIR__ . '/../models/CouponApplyModel.php';

class ApplyCouponController {
    private $couponApplyModel;

    public function __construct() {
        $this->couponApplyModel = new CouponApplyModel();
    }

    public function getApplicableCoupons() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input) || !isset($input['cart']['items'])) {
                throw new InvalidArgumentException("Invalid cart data");
            }

            $applicableCoupons = $this->couponApplyModel->getApplicableCoupons($input);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'applicable_coupons' => $applicableCoupons
                ]
            ]);

        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to check applicable coupons',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function applyCouponById($id) {
        header('Content-Type: application/json');
    
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $input['coupon_id'] = $id;
            if (empty($input) || !isset($input['coupon_id']) || !isset($input['cart']['items'])) {
                throw new InvalidArgumentException("Coupon ID and cart data are required");
            }
    
            $updatedCart = $this->couponApplyModel->applyCouponById($input['coupon_id'], $input['cart']['items']);
    
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'updated_cart' => $updatedCart
                ]
            ]);
    
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to apply coupon',
                'debug' => $e->getMessage()
            ]);
        }
    }
    
}