<?php
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../controllers/ApplyCouponController.php';


$couponController = new CouponController();

$router->map('POST', '/coupons/create', [$couponController, 'create']);
$router->map('GET', '/coupons/getAllCoupons', [$couponController, 'getAllCoupons']);
$router->map('GET', '/coupons/getCouponById/[i:id]', [$couponController, 'getCoupon']);

$router->map('PUT', '/coupons/update/[i:id]', function($id) {
    $controller = new CouponController();
    $controller->update($id);
});

$router->map('DELETE', '/coupons/delete/[i:id]', function($id) {
    $controller = new CouponController();
    $controller->delete($id);
});


$router->map('POST', '/applicable-coupons', function() {
    $ApplyCouponController = new ApplyCouponController();
    $ApplyCouponController->getApplicableCoupons();
});

$router->map('POST', '/applicable-coupons/[i:id]', function($id) {
    $ApplyCouponController = new ApplyCouponController();
    $ApplyCouponController->applyCouponById($id);
});