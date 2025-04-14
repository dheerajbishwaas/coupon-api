<?php
require_once __DIR__ . '/BaseModel.php';

class CouponModel extends BaseModel {
    public function __construct() {
        parent::__construct('coupons');
    }

    public function createCoupon($data) {
        $validationErrors = $this->validateCouponData($data);
        if (!empty($validationErrors)) {
            return ['success' => false, 'errors' => $validationErrors];
        }

        $couponData = [
            'code' => $data['code'],
            'type' => $data['type'],
            'description' => $data['description'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'max_uses' => $data['max_uses'] ?? null,
            'uses_count' => 0
        ];

        try {
            $this->conn->beginTransaction();
            $couponId = $this->insert($couponData);
            if (!$couponId) {
                throw new Exception('Failed to create base coupon');
            }

            $handlerMethod = 'handle' . str_replace('-', '', ucwords($data['type'], '-'));
            if (method_exists($this, $handlerMethod)) {
                $this->$handlerMethod($couponId, $data);
            }

            $this->conn->commit();
            return ['success' => true, 'coupon_id' => $couponId];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateCouponData($data) {
        $errors = [];

        $requiredFields = ['code', 'type', 'start_date', 'end_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!in_array($data['type'], ['cart-wise', 'product-wise', 'bxgy'])) {
            $errors[] = "Invalid coupon type.";
        }

        if (isset($data['type'])) {
            $validator = 'validate' . str_replace('-', '', ucwords($data['type'], '-'));
            if (method_exists($this, $validator)) {
                $errors = array_merge($errors, $this->$validator($data));
            }
        }

        return $errors;
    }

    private function validateCartWise($data) {
        $required = ['min_amount', 'discount_type', 'discount_value'];
        return $this->validateFields($data, $required, 'cart-wise coupon');
    }

    private function validateProductWise($data) {
        $required = ['discount_type', 'discount_value','products'];
        return $this->validateFields($data, $required, 'product-wise coupon');
    }

    private function validateBxgy($data) {
        $required = ['repetition_limit'];
        return $this->validateFields($data, $required, 'BxGy coupon');
    }

    private function validateFields($data, $requiredFields, $typeName) {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " for $typeName is required.";
            }
        }
        return $errors;
    }

    private function handleCartWise($couponId, $data) {
        $model = new BaseModel('cart_wise_coupons', $this->conn);
        $insertData = [
            'coupon_id' => $couponId,
            'min_amount' => $data['min_amount'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount' => $data['max_discount'] ?? null
        ];
        if (!$model->insert($insertData)) {
            throw new Exception('Failed to insert cart-wise rules');
        }
    }

    private function handleProductWise($couponId, $data) {
        $model = new BaseModel('product_wise_coupons', $this->conn);
        $res = $model->insert([
            'coupon_id' => $couponId,
            'products' => json_encode($data['products']),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_uses_per_product' => $data['max_uses_per_product'] ?? null
        ]);
        if (!$res) {
            throw new Exception('Failed to insert product-wise rules');
        }
    }

    private function handleBxgy($couponId, $data) {
        $ruleModel = new BaseModel('bxgy_rules', $this->conn);
        $ruleRes = $ruleModel->insert([
            'coupon_id' => $couponId,
            'repetition_limit' => $data['repetition_limit'],
        ]);

        if (!$ruleRes) {
            throw new Exception('Failed to insert BxGy rule');
        }

        // Optional: BxGy products (if applicable)
        if (!empty($data['products'])) {
            $productModel = new BaseModel('bxgy_products', $this->conn);
            foreach ($data['products'] as $product) {
                $productModel->insert([
                    'coupon_id' => $couponId,
                    'product_id' => $product['product_id'],
                    'type' => $product['type']
                ]);
            }
        }
    }

    public function updateCoupon($couponId, $data) {
        $validationErrors = $this->validateCouponData($data);
        if (!empty($validationErrors)) {
            return ['success' => false, 'errors' => $validationErrors];
        }
    
        $couponData = [
            'code' => $data['code'],
            'type' => $data['type'],
            'description' => $data['description'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'max_uses' => $data['max_uses'] ?? null,
        ];
    
        try {
            $this->conn->beginTransaction();
    
            $updated = $this->update(['id'=>$couponId], $couponData); // assuming `update` is defined in your BaseModel
    
            if (!$updated) {
                throw new Exception('Failed to update base coupon');
            }
    
            $handlerMethod = 'update' . str_replace('-', '', ucwords($data['type'], '-'));
            if (method_exists($this, $handlerMethod)) {
                $this->$handlerMethod($couponId, $data);
            }
    
            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function updateCartWise($couponId, $data) {
        $model = new BaseModel('cart_wise_coupons', $this->conn);
        $updateData = [
            'coupon_id' => $couponId,
            'min_amount' => $data['min_amount'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount' => $data['max_discount'] ?? null
        ];
    
        if ($model->exists(['coupon_id' => $couponId])) {
            if (!$model->update(['coupon_id' => $couponId], $updateData)) {
                throw new Exception('Failed to update cart-wise rules');
            }
        } else {
            if (!$model->insert($updateData)) {
                throw new Exception('Failed to insert cart-wise rules');
            }
        }
    }
    

    private function updateProductWise($couponId, $data) {
        $model = new BaseModel('product_wise_coupons', $this->conn);
        $updateData = [
            'coupon_id' => $couponId,
            'products' => json_encode($data['products']),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_uses_per_product' => $data['max_uses_per_product'] ?? null
        ];
    
        if ($model->exists(['coupon_id' => $couponId])) {
            if (!$model->update(['coupon_id' => $couponId], $updateData)) {
                throw new Exception('Failed to update product-wise rules');
            }
        } else {
            if (!$model->insert($updateData)) {
                throw new Exception('Failed to insert product-wise rules');
            }
        }
    }
    
    private function updateBxgy($couponId, $data) {
        $ruleModel = new BaseModel('bxgy_rules', $this->conn);
        $ruleData = ['coupon_id' => $couponId, 'repetition_limit' => $data['repetition_limit']];
    
        if ($ruleModel->exists(['coupon_id' => $couponId])) {
            if (!$ruleModel->update(['coupon_id' => $couponId], ['repetition_limit' => $data['repetition_limit']])) {
                throw new Exception('Failed to update BxGy rule');
            }
        } else {
            if (!$ruleModel->insert($ruleData)) {
                throw new Exception('Failed to insert BxGy rule');
            }
        }
    
        if (!empty($data['products'])) {
            $productModel = new BaseModel('bxgy_products', $this->conn);
            // $productModel->deleteBy(['coupon_id' => $couponId]); // Clear old entries
            foreach ($data['products'] as $product) {
                $productModel->insert([
                    'coupon_id' => $couponId,
                    'product_id' => $product['product_id'],
                    'type' => $product['type']
                ]);
            }
        }
    }

                    
    public function getCouponById($id) {
        try {
            // Fetch base coupon data
            $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$coupon) {
                throw new Exception("Coupon not found with ID: $id");
            }
    
            // Fetch and attach detailed information based on type
            switch ($coupon['type']) {
                case 'cart-wise':
                    $coupon['details'] = $this->getCartWiseDetails($coupon['id']);
                    break;
    
                case 'product-wise':
                    $coupon['details'] = $this->getProductWiseDetails($coupon['id']);
                    break;
    
                case 'bxgy':
                    $coupon['details'] = $this->getBxgyDetails($coupon['id']);
    
                    // Attach buy and get product IDs
                    list($buy_product_ids, $free_product_ids) = $this->getGroupedProductIds($coupon['id']);
                    $coupon['buy_product_ids'] = array_merge($buy_product_ids,$free_product_ids);
                    $coupon['free_product_ids'] = $free_product_ids;
                    break;
            }
    
            return $coupon;
    
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Failed to retrieve coupon. DB Error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("App Error: " . $e->getMessage());
            throw new Exception("Failed to retrieve coupon. Error: " . $e->getMessage());
        }
    }

    public function getAllCoupons($filters = []) {
        // Default values
        $page = max(1, $filters['page'] ?? 1);
        $limit = min(50, max(1, $filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        
        // Base query
        $query = "SELECT c.* FROM coupons c WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM coupons c WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (isset($filters['is_active'])) {
            $query .= " AND c.is_active = :is_active";
            $countQuery .= " AND c.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }
        
        if (!empty($filters['type'])) {
            $query .= " AND c.type = :type";
            $countQuery .= " AND c.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (c.code LIKE :search OR c.description LIKE :search)";
            $countQuery .= " AND (c.code LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Add sorting and pagination
        $query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        try {
            // Get total count
            $stmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            // Get paginated results
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $paramType);
            }
            $stmt->execute();
            $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if query fetched any coupons
            if (empty($coupons)) {
                throw new Exception("No coupons found with the provided filters.");
            }
            
            // Get additional details for each coupon type
            foreach ($coupons as &$coupon) {
                switch ($coupon['type']) {
                    case 'cart-wise':
                        $details = $this->getCartWiseDetails($coupon['id']);
                        $coupon['details'] = $details;
                        break;
                        
                    case 'product-wise':
                        $details = $this->getProductWiseDetails($coupon['id']);
                        $coupon['details'] = $details;
                       
                        break;
                        
                    case 'bxgy':
                        $details = $this->getBxgyDetails($coupon['id']);
                        $coupon['details'] = $details;
                         
                        // Fetch buy and free product IDs
                        list($buy_product_ids, $free_product_ids) = $this->getGroupedProductIds($coupon['id']);
                        $coupon['buy_product_ids'] = array_merge($buy_product_ids,$free_product_ids);
                        $coupon['free_product_ids'] = $free_product_ids;
                        break;
                }
            }
            
            return [
                'data' => $coupons,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to retrieve coupons. Debug: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General error: " . $e->getMessage());
            throw new Exception("Failed to retrieve coupons. Error: " . $e->getMessage());
        }
    }
    
    // Returns both buy_product_ids and free_product_ids
    private function getGroupedProductIds($couponId) {
        $stmt = $this->conn->prepare(
            "SELECT product_id, type FROM bxgy_products WHERE coupon_id = ?"
        );
        $stmt->execute([$couponId]);

        // Separate the product IDs into buy and free categories
        $buyProductIds = [];
        $freeProductIds = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['type'] == 'buy') {
                $buyProductIds[] = $row['product_id'];
            } elseif ($row['type'] == 'get') {
                $freeProductIds[] = $row['product_id'];
            }
        }

        // Return both arrays correctly
        return [$buyProductIds, $freeProductIds]; // No comma or error here
    }

    private function getCartWiseDetails($couponId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM cart_wise_coupons WHERE coupon_id = ?"
        );
        $stmt->execute([$couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getProductWiseDetails($couponId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM product_wise_coupons WHERE coupon_id = ?"
        );
        $stmt->execute([$couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getBxgyDetails($couponId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM bxgy_rules WHERE coupon_id = ?"
        );
        $stmt->execute([$couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteCoupon($couponId) {
        // Get the coupon details to find its type
        $coupon = $this->getOneBy(['id' => $couponId]);

        if (empty($coupon)) {
            return ['success' => false, 'message' => 'Coupon not found'];
        }

        try {
            // Start a database transaction
            $this->conn->beginTransaction();

            // Delete related data based on the coupon type
            switch ($coupon['type']) {
                case 'cart-wise':
                    $this->deleteCartWiseData($couponId);
                    break;

                case 'product-wise':
                    $this->deleteProductWiseData($couponId);
                    break;

                case 'bxgy':
                    $this->deleteBxgyData($couponId);
                    break;

                default:
                    throw new Exception('Unknown coupon type');
            }

            // Now delete the main coupon record
            if (!$this->deleteBy(['id' => $couponId])) {
                throw new Exception('Failed to delete coupon');
            }

            // Commit the transaction
            $this->conn->commit();

            return ['success' => true];

        } catch (Exception $e) {
            // Rollback in case of error
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Delete cart-wise related data
    private function deleteCartWiseData($couponId) {
        $model = new BaseModel('cart_wise_coupons', $this->conn);
        if (!$model->deleteBy(['coupon_id' => $couponId])) {
            throw new Exception('Failed to delete cart-wise data');
        }
    }

    // Delete product-wise related data
    private function deleteProductWiseData($couponId) {
        $model = new BaseModel('product_wise_coupons', $this->conn);
        if (!$model->deleteBy(['coupon_id' => $couponId])) {
            throw new Exception('Failed to delete product-wise data');
        }
    }

    // Delete BxGy related data
    private function deleteBxgyData($couponId) {
        $ruleModel = new BaseModel('bxgy_rules', $this->conn);
        if (!$ruleModel->deleteBy(['coupon_id' => $couponId])) {
            throw new Exception('Failed to delete BxGy rule');
        }

        // Deleting the associated products from bxgy_products table
        $productModel = new BaseModel('bxgy_products', $this->conn);
        if (!$productModel->deleteBy(['coupon_id' => $couponId])) {
            throw new Exception('Failed to delete BxGy products');
        }
    }

}
