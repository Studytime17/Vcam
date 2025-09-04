<?php
/**
 * Core Functions File
 * POS System Version 7.0
 * Created: 2025-09-04
 */

// User, Profile, and Store Setup Functions
function createUser($userData) {
    global $conn;
    // Sanitize and validate input
    $username = sanitizeInput($userData['username']);
    $password = password_hash($userData['password'], PASSWORD_DEFAULT);
    $role = sanitizeInput($userData['role']);
    
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$username, $password, $role]);
}

function updateUserProfile($userId, $profileData) {
    global $conn;
    // Update user profile information
    $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$profileData['name'], $profileData['email'], $profileData['phone'], $userId]);
}

function changePassword($userId, $newPassword) {
    global $conn;
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$hashedPassword, $userId]);
}

function setUserRole($userId, $role, $permissions) {
    global $conn;
    // Set user role and permissions
    $sql = "UPDATE users SET role = ?, permissions = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$role, json_encode($permissions), $userId]);
}

// Receipt and Invoice Functions
function generateReceipt($saleId) {
    global $conn;
    // Get sale details
    $sql = "SELECT * FROM sales WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    
    // Generate PDF receipt
    // Implementation for PDF generation
    return $receiptPdf;
}

function sendWhatsAppBill($saleId, $phoneNumber) {
    // Generate QR code for payment
    $qrCode = generatePaymentQR($saleId);
    
    // Send WhatsApp message with PDF and QR code
    // Implementation for WhatsApp API integration
    return $messageSent;
}

// Location and Device Management
function addStoreLocation($locationData) {
    global $conn;
    $sql = "INSERT INTO locations (name, address, timezone) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$locationData['name'], $locationData['address'], $locationData['timezone']]);
}

function registerDevice($deviceData) {
    global $conn;
    $sql = "INSERT INTO devices (location_id, type, name) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$deviceData['location_id'], $deviceData['type'], $deviceData['name']]);
}

// Product and Inventory Management
function addProduct($productData) {
    global $conn;
    $sql = "INSERT INTO products (name, description, price, tax_rate, stock_level) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $productData['name'],
        $productData['description'],
        $productData['price'],
        $productData['tax_rate'],
        $productData['stock_level']
    ]);
}

function updateStock($productId, $quantity, $type = 'sale') {
    global $conn;
    // Update stock levels based on sales, returns, or stock takes
    $sql = "UPDATE products SET stock_level = stock_level + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$quantity, $productId]);
}

// Customer Management
function addCustomer($customerData) {
    global $conn;
    $sql = "INSERT INTO customers (name, email, phone, type, credit_limit) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $customerData['name'],
        $customerData['email'],
        $customerData['phone'],
        $customerData['type'],
        $customerData['credit_limit']
    ]);
}

function updateLoyaltyPoints($customerId, $points) {
    global $conn;
    $sql = "UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$points, $customerId]);
}

// Sales and Transaction Processing
function processSale($saleData) {
    global $conn;
    try {
        $conn->begin_transaction();
        
        // Create sale record
        $sql = "INSERT INTO sales (customer_id, total_amount, payment_method) 
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $saleData['customer_id'],
            $saleData['total_amount'],
            $saleData['payment_method']
        ]);
        $saleId = $conn->insert_id;
        
        // Process each item in the sale
        foreach ($saleData['items'] as $item) {
            // Add sale items
            $sql = "INSERT INTO sale_items (sale_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $saleId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
            
            // Update stock levels
            updateStock($item['product_id'], -$item['quantity']);
        }
        
        // Update customer loyalty points if applicable
        if (!empty($saleData['customer_id'])) {
            updateLoyaltyPoints($saleData['customer_id'], 
                              calculateLoyaltyPoints($saleData['total_amount']));
        }
        
        $conn->commit();
        return $saleId;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Utility Functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}

function calculateLoyaltyPoints($amount) {
    // Example: 1 point per $10 spent
    return floor($amount / 10);
}

function logError($error, $context = []) {
    error_log(date('Y-m-d H:i:s') . " - Error: " . $error . 
              " Context: " . json_encode($context));
}

// Database Table Creation
function createTables() {
    global $conn;
    
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            username VARCHAR(50) UNIQUE NOT NULL,\n            password VARCHAR(255) NOT NULL,\n            role VARCHAR(20) NOT NULL,\n            permissions JSON,\n            name VARCHAR(100),\n            email VARCHAR(100),\n            phone VARCHAR(20),\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        )",
        
        "products" => "CREATE TABLE IF NOT EXISTS products (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            name VARCHAR(100) NOT NULL,\n            description TEXT,\n            price DECIMAL(10,2) NOT NULL,\n            tax_rate DECIMAL(5,2),\n            stock_level INT DEFAULT 0,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        )",
        
        "customers" => "CREATE TABLE IF NOT EXISTS customers (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            name VARCHAR(100) NOT NULL,\n            email VARCHAR(100),\n            phone VARCHAR(20),\n            type VARCHAR(20),\n            credit_limit DECIMAL(10,2),\n            loyalty_points INT DEFAULT 0,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        )",
        
        "sales" => "CREATE TABLE IF NOT EXISTS sales (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            customer_id INT,\n            total_amount DECIMAL(10,2) NOT NULL,\n            payment_method VARCHAR(50),\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (customer_id) REFERENCES customers(id)\n        )",
        
        "sale_items" => "CREATE TABLE IF NOT EXISTS sale_items (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            sale_id INT NOT NULL,\n            product_id INT NOT NULL,\n            quantity INT NOT NULL,\n            price DECIMAL(10,2) NOT NULL,\n            FOREIGN KEY (sale_id) REFERENCES sales(id),\n            FOREIGN KEY (product_id) REFERENCES products(id)\n        )",
        
        "locations" => "CREATE TABLE IF NOT EXISTS locations (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            name VARCHAR(100) NOT NULL,\n            address TEXT,\n            timezone VARCHAR(50),\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        )",
        
        "devices" => "CREATE TABLE IF NOT EXISTS devices (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            location_id INT NOT NULL,\n            type VARCHAR(50),\n            name VARCHAR(100),\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (location_id) REFERENCES locations(id)\n        )"
    ];
    
    foreach ($tables as $name => $sql) {
        if (!$conn->query($sql)) {
            throw new Exception("Error creating table $name: " . $conn->error);
        }
    }
}