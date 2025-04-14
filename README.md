Coupon Management API Documentation
Overview
This API provides comprehensive coupon management functionality including creation, retrieval, updating, and deletion of coupons. It also includes endpoints to check applicable coupons for a shopping cart and apply specific coupons to a cart.

Note: This API currently does not implement authentication/authorization. 


PHP (MVC pattern)

Composer for dependencies
JSON input/output
RESTful structure



Folder structure

coupon-api/
│
├── app/
│   ├── config/           # Configuration files (app & database)
│   ├── controllers/      # Handles API request logic
│   ├── models/           # Contains business logic & DB interactions
│   └── routes/           # Routes definition, mapping endpoints to controllers
│
├── db/                   # Database structure or SQL scripts (if any)
├── public/               # Entry point for HTTP requests (index.php)
├── vendor/               # Composer dependencies
├── composer.json         # Composer configuration file
├── composer.lock         # Exact versions of dependencies installed
└── README.md             # Project documentation


Method	Endpoint        	            Description
POST	/coupons/create	                Create a new coupon
GET	    /coupons/getAllCoupons	        List all coupons
GET	    /coupons/getCouponById/{id}	    Fetch details of a specific coupon
PUT	    /coupons/update/{id}	        Update a coupon
DELETE	/coupons/delete/{id}	        Delete a coupon
POST	/applicable-coupons/	        Validate a coupon with cart data
POST	//applicable-coupons/{id}       Check if coupon id with cart data

Base URL
http://localhost/coupon-api/public

Endpoints
1. Coupon Management
Create Coupon
Endpoint: POST /coupons/create

Description: Creates a new coupon with the specified parameters

Request Body: (Content-Type: application/json)

{
  "code": "SUMMER20",
  "type": "cart-wise", // "cart-wise", "product-wise", or "bxgy"
  "description": "Summer discount",
  "is_active": true,
  "start_date": "2023-06-01 00:00:00",
  "end_date": "2023-08-31 23:59:59",
  "max_uses": 1000,
  
  // Type-specific parameters:
  // For cart-wise:
  "min_amount": 1000,
  "discount_type": "percentage", // or "fixed"
  "discount_value": 20,
  "max_discount": 500,
  
  // For product-wise:
  "products": [1, 2, 3],
  "max_uses_per_product": 1,
  
  // For bxgy:
  "repetition_limit": 1,
  "products": [
    {"product_id": 1, "type": "buy"},
    {"product_id": 2, "type": "get"}
  ]
}
Success Response:

{
  "status": "success",
  "data": {
    "coupon_id": 123,
    "message": "Coupon created successfully"
  }
}
Get All Coupons
Endpoint: GET /coupons/getAllCoupons

Query Parameters:

page (optional): Page number (default: 1)

limit (optional): Items per page (default: 10)

type (optional): Filter by coupon type

search (optional): Search in code or description

Success Response:

{
  "status": "success",
  "data": [
    {
      "id": 123,
      "code": "SUMMER20",
      "type": "cart-wise",
      "description": "Summer discount",
      "is_active": true,
      "start_date": "2023-06-01 00:00:00",
      "end_date": "2023-08-31 23:59:59",
      "max_uses": 1000,
      "uses_count": 0,
      "details": {
        //coupon Details will show 
      }
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 10,
    "total_pages": 2
  }
}
Get Coupon by ID
Endpoint: GET /coupons/getCouponById/{id}

Success Response:

{
  "status": "success",
  "data": {
    "id": 123,
    "code": "SUMMER20",
    "type": "cart-wise",
    // ... other coupon fields ...,
    "details": {
      // Type-specific details
    }
  }
}
Update Coupon
Endpoint: PUT /coupons/update/{id}

Request Body: Same structure as create coupon, but only fields to update need to be included

Success Response:
{
  "status": "success",
  "data": {
    "message": "Coupon updated successfully"
  }
}


Delete Coupon
Endpoint: DELETE /coupons/delete/{id}

Success Response:

{
  "status": "success",
  "message": "Coupon deleted successfully"
}

2. Coupon Application
Get Applicable Coupons
Endpoint: POST /coupons/applicable-coupons

Description: Returns all coupons that can be applied to the provided cart

Request Body:

{
  "cart": {
    "items": [
      {
        "product_id": 1,
        "price": 500,
        "quantity": 2
      },
      {
        "product_id": 2,
        "price": 300,
        "quantity": 1
      }
    ]
  }
}
Success Response:

{
  "status": "success",
  "data": {
    "applicable_coupons": [
      {
        "coupon_id": 123,
        "code": "SUMMER20",
        "type": "cart-wise",
        "description": "Summer discount",
        "discount": 100,
        "message": "20% off on carts over Rs. 1000 total Max discount is 500"
      }
    ]
  }
}
Apply Coupon by ID
Endpoint: POST /coupons/applicable-coupons/{id}

Description: Applies a specific coupon to the cart and returns the updated cart with discounts

Request Body:

{
  "cart": {
    "items": [
      {
        "product_id": 1,
        "price": 500,
        "quantity": 2
      },
      {
        "product_id": 2,
        "price": 300,
        "quantity": 1
      }
    ]
  }
}
Success Response:

{
  "status": "success",
  "data": {
    "updated_cart": {
      "items": [
        {
          "product_id": 1,
          "price": 500,
          "quantity": 2,
          "total_discount": 0
        },
        {
          "product_id": 2,
          "price": 300,
          "quantity": 1,
          "total_discount": 300
        }
      ],
      "total_price": 1300,
      "total_discount": 300,
      "final_price": 1000
    }
  }
}

Coupon Types
1. Cart-wise Coupons
Applies to the entire cart when minimum amount is met
Can be percentage or fixed amount discount
May have a maximum discount cap for percentage discounts

2. Product-wise Coupons
Applies to specific products in the cart
Can be percentage or fixed amount discount per product
Discount is applied to each qualifying product in the cart
For this, we accept an array of product IDs. If any of these product IDs exist in the cart, the discount will be applied as follows:

For percentage discounts: The discount will only apply to those specific products in the cart that match the IDs in the array.

For fixed amount discounts: The discount will be applied to each qualifying product in the cart (all products that match the IDs in the array), with the fixed amount applied individually to each matching product.

Important: The discount will only be applied if the product ID exists in both the cart AND the specified product ID array.

3. BxGy (Buy X Get Y) Coupons

Buy certain products to get other products free

Can specify repetition limits (how many times the offer can be applied)

Free products must be in cart to apply the discount

Error Responses
Common error responses include:

Copy
{
  "status": "error",
  "message": "Error description"
}
Specific error codes:

400: Bad request (invalid input data)

404: Coupon not found

500: Internal server error

Security Note
This API currently does not implement authentication or authorization. In a production environment, we can use:
Implement JWT or OAuth2 authentication

Add role-based access control
Validate all inputs thoroughly
Implement rate limiting
Use HTTPS for all communications

Database Schema
The system uses several tables:
coupons: Main coupon information
cart_wise_coupons: Cart-wise coupon details
product_wise_coupons: Product-wise coupon details
bxgy_rules: BxGy rule definitions
bxgy_products: BxGy product mappings

Implementation Notes
The system uses PHP with PDO for database access
Models follow a base model pattern with common CRUD operations
Controllers handle request/response logic
Coupon validation is performed before creation/updates
The system supports transactions for data integrity




1. Controller Structure
A. CouponController
Handles all CRUD operations for coupons:

create() - Creates new coupons

getCoupon() - Gets single coupon by ID

getAllCoupons() - Lists all coupons with pagination

update() - Modifies existing coupons

delete() - Removes coupons

B. ApplyCouponController
Handles coupon application logic:

getApplicableCoupons() - Returns all valid coupons for a given cart

applyCouponById() - Applies specific coupon and returns discounted totals

2. Model Structure
A. CouponModel

Modular design with separate handlers for each coupon type

Clear validation methods (validateCartWise(), validateProductWise(), etc.)

Type-specific operations grouped logically

B. CouponApplyModel

Dedicated to application logic

Contains discount calculation methods for each coupon type

Returns structured discount information

API Endpoints
All routes are defined in routes/api.php:

// Coupon Management
POST /coupons/create
GET /coupons/getAllCoupons
GET /coupons/getCouponById/{id}
PUT /coupons/update/{id} 
DELETE /coupons/delete/{id}

// Coupon Application
POST /coupons/applicable-coupons
POST /coupons/applicable-coupons/{id}
Current Implementation Notes
Public URLs: Temporary public access due to PHP version constraints (will be secured in production)

Product Validation:

Currently accepts product ID arrays

Only applies discounts if products exist in both:

The coupon's product list

The submitted cart

Discount Types:

// Stored in separate tables:
- percentage (with optional max cap)
- fixed amount (applies per qualifying item)

Additional Scenarios to Consider
1. Product Quantity Limits
Current Limitation:
No quantity validation - coupon applies to all matching products regardless of quantity.

Suggested Enhancement:

// In product_wise_coupons table:
add columns:
- max_quantity_per_order
- min_quantity_to_activate

2. Stackable Coupons
Current Behavior:
Only one coupon can be applied at a time.

Potential Improvement:


// New table: coupon_combinations
- primary_coupon_id
- secondary_coupon_id
- is_allowed (boolean)
3. User-Specific Restrictions
Missing Feature:
No user-level restrictions (all users can use all coupons).

Recommended Addition:

// New table: coupon_user_restrictions
- coupon_id
- user_id
- usage_count
- max_usage
4. Category-Based Coupons
Extension Idea:
Instead of product IDs, allow category-based discounts.


// New table: category_coupons
- coupon_id
- category_id
- discount_type
- discount_value

5. Time-Based Activation
Possible Enhancement:
Add "time windows" for coupon activation.

// In coupons table:
- active_days: ['mon','tue','wed'] 
- active_hours: '09:00-17:00'

Also, I have intentionally committed the vendor folder so that you don't need to run composer install on your end.