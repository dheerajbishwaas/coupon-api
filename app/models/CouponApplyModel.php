<?php
require_once __DIR__ . '/BaseModel.php';

class CouponApplyModel extends BaseModel {
    public function __construct() {
        parent::__construct('coupons');
    }

    public function getApplicableCoupons($cartData) {
        try {
            $applicableCoupons = [];
            $cartItems = $cartData['cart']['items'];
            $subtotal = $this->calculateSubtotal($cartItems);
            $productQuantities = $this->getProductQuantities($cartItems);

            // Get all active coupons within date range
            $currentDate = date('Y-m-d H:i:s');
            $coupons = $this->getAllWhere([
                ['is_active', '=', 1],
                ['start_date', '<=', $currentDate],
                ['end_date', '>=', $currentDate]
            ]);

            foreach ($coupons as $coupon) {

                $discountInfo = null;
                switch ($coupon['type']) {
                    case 'cart-wise':
                        $discountInfo = $this->checkCartWiseCoupon($coupon, $subtotal);
                        break;

                    case 'product-wise':
                        $discountInfo = $this->checkProductWiseCoupon($coupon, $cartItems);
                        break;

                    case 'bxgy':
                        $discountInfo = $this->checkBxgyCoupon($coupon, $cartItems);
                        break;
                }

                if ($discountInfo && $discountInfo['discount'] > 0) {
                    $applicableCoupons[] = array_merge([
                        'coupon_id' => $coupon['id'],
                        'code' => $coupon['code'],
                        'type' => $coupon['type'],
                        'description' => $coupon['description']
                    ], $discountInfo);
                }
            }

            return $applicableCoupons;

        } catch (Exception $e) {
            error_log("Error in getApplicableCoupons: " . $e->getMessage());
            throw $e;
        }
    }

    private function calculateSubtotal($cartItems) {
        return array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
    }

    private function getProductQuantities($cartItems) {
        $quantities = [];
        foreach ($cartItems as $item) {
            $quantities[$item['product_id']] = $item['quantity'];
        }
        return $quantities;
    }

    private function checkCartWiseCoupon($coupon, $subtotal) {
        $details = $this->getCartWiseDetails($coupon['id']);
        if (!$details || $subtotal < $details['min_amount']) {
            return null;
        }
        $discount = 0;
        if ($details['discount_type'] === 'percentage') {
            $discount = $subtotal * ($details['discount_value'] / 100);
            if ($details['max_discount'] && $discount > $details['max_discount']) {
                $discount = $details['max_discount'];
            }
        } else {
            $discount = $details['discount_value'];
        }

        return [
            'discount' => $discount,
            'message' => sprintf('%s%% off on carts over Rs. %d', 
                $details['discount_value'], 
                $details['min_amount']).' total Max discount is '.$details['max_discount']
        ];
    }

    private function checkProductWiseCoupon($coupon, $cartItems) {
        $details = $this->getProductWiseDetails($coupon['id']);
        if (!$details) return null;

        $discountedProducts = json_decode($details['products'], true);
        if (!is_array($discountedProducts)) return null;

        $discount = 0;
        $appliedProducts = [];

        foreach ($cartItems as $item) {
            if (in_array($item['product_id'], $discountedProducts)) {
                if ($details['discount_type'] === 'percentage') {
                    $productDiscount = $item['price'] * $item['quantity'] * ($details['discount_value'] / 100);
                } else {
                    $productDiscount = $details['discount_value'] * $item['quantity'];
                }
                $discount += $productDiscount;
                $appliedProducts[] = $item['product_id'];
            }
        }

        if ($discount > 0) {
            return [
                'discount' => $discount,
                'message' => sprintf('%s%% off on products: %s', 
                    $details['discount_value'], 
                    implode(', ', $appliedProducts))
            ];
        }

        return null;
    }

    // private function checkBxgyCoupon($coupon, $cartItems) {
    //     $rule = $this->getBxgyDetails($coupon['id']);
    //     $products = $this->getBxgyProducts($coupon['id']);
    //     if (!$rule || empty($products)) return null;
    
    //     // Extract product IDs
    //     $cartProductIds = array_map(fn($item) => $item['product_id'], $cartItems);
    //     $productIds = array_map(fn($item) => $item['product_id'], $products);
    
    //     // Check if all required products exist in the cart
    //     $missing = array_diff($productIds, $cartProductIds);
    //     if (!empty($missing)) return null;
    
    //     // Group buy/get
    //     $buyProducts = array_filter($products, fn($p) => $p['type'] === 'buy');
    //     $getProducts = array_filter($products, fn($p) => $p['type'] === 'get');
    
    //     // Prepare product quantity & price map
    //     $productQuantities = [];
    //     $productPrices = [];
    //     foreach ($cartItems as $item) {
    //         $productQuantities[$item['product_id']] = $item['quantity'];
    //         $productPrices[$item['product_id']] = $item['price'];
    //     }
    
    //     // Find how many times buy group can repeat
    //     $minTimes = PHP_INT_MAX;
    //     foreach ($buyProducts as $buy) {
    //         $pid = $buy['product_id'];
    //         $qty = $productQuantities[$pid] ?? 0;
    //         $minTimes = min($minTimes, $qty);
    //     }
    
    //     // Also apply same rule for get products (availability matters)
    //     foreach ($getProducts as $get) {
    //         $pid = $get['product_id'];
    //         $qty = $productQuantities[$pid] ?? 0;
    //         $minTimes = min($minTimes, $qty);
    //     }
    
    //     // Cap it by repetition limit
    //     $applicableTimes = min($minTimes, $rule['repetition_limit']);
    //     if ($applicableTimes <= 0) return null;
    
    //     // Calculate discount
    //     $discount = 0;
    //     $freeProducts = [];
    //     foreach ($getProducts as $get) {
    //         $pid = $get['product_id'];
    //         $price = $productPrices[$pid] ?? 0;
    //         $discount += $price * $applicableTimes;
    //         $freeProducts[] = $pid;
    //     }
    
    //     return [
    //         'discount' => $discount,
    //         'message' => sprintf("Buy %s & get %s free (%d time%s)", 
    //             implode(', ', array_column($buyProducts, 'product_id')),
    //             implode(', ', $freeProducts),
    //             $applicableTimes,
    //             $applicableTimes > 1 ? 's' : ''
    //         )
    //     ];
    // }

    private function checkBxgyCoupon($coupon, $cartItems) {
        $rule = $this->getBxgyDetails($coupon['id']);
        $products = $this->getBxgyProducts($coupon['id']);
        if (!$rule || empty($products)) return null;
    
        $cartProductIds = array_map(fn($item) => $item['product_id'], $cartItems);
        $productIds = array_map(fn($item) => $item['product_id'], $products);
    
        $missing = array_diff($productIds, $cartProductIds);
        if (!empty($missing)) return null;
    
        $buyProducts = array_filter($products, fn($p) => $p['type'] === 'buy');
        $getProducts = array_filter($products, fn($p) => $p['type'] === 'get');
    
        $productQuantities = [];
        $productPrices = [];
        foreach ($cartItems as $item) {
            $productQuantities[$item['product_id']] = $item['quantity'];
            $productPrices[$item['product_id']] = $item['price'];
        }
    
        $minTimes = PHP_INT_MAX;
        foreach ($buyProducts as $buy) {
            $pid = $buy['product_id'];
            $qty = $productQuantities[$pid] ?? 0;
            $minTimes = min($minTimes, floor($qty));
        }
    
        foreach ($getProducts as $get) {
            $pid = $get['product_id'];
            $qty = $productQuantities[$pid] ?? 0;
            $minTimes = min($minTimes, floor($qty));
        }
    
        $applicableTimes = min($minTimes, $rule['repetition_limit']);
        if ($applicableTimes <= 0) return null;
    
        $discount = 0;
        $getProductIds = [];
    
        foreach ($getProducts as $get) {
            $pid = $get['product_id'];
            $price = $productPrices[$pid] ?? 0;
            $discount += $price * $applicableTimes;
            $getProductIds[] = $pid;
        }
    
        return [
            'discount' => $discount,
            'times' => $applicableTimes,
            'get_product_ids' => $getProductIds
        ];
    }
    
    
    private function getCartWiseDetails($couponId) {
        $model = new BaseModel('cart_wise_coupons');
        return $model->getWhere([['coupon_id', '=', $couponId]]);
    }

    private function getProductWiseDetails($couponId) {
        $model = new BaseModel('product_wise_coupons');
        return $model->getWhere([['coupon_id', '=', $couponId]]);
    }

    private function getBxgyDetails($couponId) {
        $model = new BaseModel('bxgy_rules');
        return $model->getWhere([['coupon_id', '=', $couponId]]);
    }

    private function getBxgyProducts($couponId) {
        $model = new BaseModel('bxgy_products');
        return $model->getAllWhere([['coupon_id', '=', $couponId]]);
    }


    public function applyCouponById($couponId, $cartItems) {
        // Get coupon data
        $coupon = $this->getCouponById($couponId);
        if (!$coupon) {
            throw new InvalidArgumentException("Coupon not found");
        }
    
        $updatedItems = $cartItems;
      
        $totalPrice = 0;
        $totalDiscount = 0;
        $subtotal = $this->calculateSubtotal($updatedItems);
    
        // Apply based on coupon type
        switch ($coupon['type']) {
            case 'cart-wise':
                $result = $this->checkCartWiseCoupon($coupon, $subtotal);
                if ($result && isset($result['discount'])) {
                    $totalDiscount = $result['discount'];
                }
                break;
    
                case 'product-wise':
                    $result = $this->checkProductWiseCoupon($coupon, $cartItems);
                    if ($result && isset($result['discount'])) {
                        $totalDiscount = $result['discount'];
                
                        // Fetch discount type from product_wise_coupons table
                        $discountType = $this->getProductWiseDetails($couponId)['discount_type']; 
                
                        // Initialize updated cart values
                        $updatedCart = [
                            'total_price' => 0, // Initialize total price
                            'total_discount' => 0, // Initialize total discount
                            'final_price' => 0 // Initialize final price
                        ];
                
                        // mark product-wise discount
                        foreach ($updatedItems as $item) {
                            // Calculate total price for the cart
                            $updatedCart['total_price'] += $item['price'] * $item['quantity'];
                            // Check if the product is in the discounted products list
                            if (in_array($item['product_id'], json_decode($this->getProductWiseDetails($couponId)['products'], true))) {
                                // Apply percentage discount
                                if ($discountType === 'percentage') {
                                    $item['total_discount'] = round($item['price'] * $item['quantity'] * ($totalDiscount / 100), 2);
                                } else { 
                                    // Apply fixed amount discount
                                    $item['total_discount'] = round($totalDiscount * $item['quantity'], 2);
                                }
                            } else {
                                $item['total_discount'] = 0;
                            }
                        }
                
                        // Calculate total discount (rounded)
                        $totalDiscountAmount = array_sum(array_column($updatedItems, 'total_discount'));
                        $totalDiscountAmount = round($totalDiscountAmount, 2);
                
                        // Update the total discount in the updated cart
                        $updatedCart['total_discount'] = $totalDiscountAmount;
                
                        // Update final price (total price - total discount)
                        $updatedCart['final_price'] = round($updatedCart['total_price'] - $updatedCart['total_discount'], 2);
                    }
                    break;
                
    
            case 'bxgy':
                $result = $this->checkBxgyCoupon($coupon, $cartItems);
                if ($result && isset($result['discount'])) {
                    $totalDiscount = $result['discount'];
    
                    // Apply discount to "get" products
                    foreach ($updatedItems as &$item) {
                        if (in_array($item['product_id'], $result['get_product_ids'])) {
                            $item['total_discount'] = ($item['price'] * $result['times']);
                        } else {
                            $item['total_discount'] = 0;
                        }
                    }
                }
                break;
    
            default:
                throw new InvalidArgumentException("Unsupported coupon type");
        }
    
        // Calculate total price
        foreach ($updatedItems as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }
    
        $finalPrice = $totalPrice - $totalDiscount;
    
        return [
            'items' => $updatedItems,
            'total_price' => $totalPrice,
            'total_discount' => $totalDiscount,
            'final_price' => $finalPrice
        ];
    }
    
    private function getCouponById($id) {
        $query = "SELECT * FROM coupons WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

}