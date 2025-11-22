# Laragon Database Integration Guide

## What is Laragon?
Laragon is a portable, isolated, fast & powerful Universal Development Environment for Windows. It comes with Apache, MySQL/MariaDB, PHP, Node.js, and other useful tools for local development.

---

## Step 1: Set Up Laragon & Create Database

### 1.1 Start Laragon
- Download Laragon from [laragon.org](https://laragon.org)
- Extract and run `laragon.exe`
- Click the **Start** button to start Apache and MySQL

### 1.2 Access MySQL Database Manager
- In Laragon, click **Database** → **MySQL**
- This opens phpMyAdmin (web interface for MySQL)
- Login with default credentials (usually username: `root`, password: empty/blank)

### 1.3 Create Your Database
In phpMyAdmin:
1. Click **New** on the left sidebar
2. Enter database name: `advertising_requests`
3. Click **Create**

### 1.4 Create Tables
Run these SQL queries in the **SQL** tab of your `advertising_requests` database:

\`\`\`sql
CREATE TABLE requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  contact_number VARCHAR(20) NOT NULL,
  company_name VARCHAR(100) NOT NULL,
  service_type VARCHAR(100) NOT NULL,
  size_dimensions VARCHAR(100),
  other_service VARCHAR(255),
  project_description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  upload_files VARCHAR(255),
  status VARCHAR(50) DEFAULT 'New',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
\`\`\`

---

## Step 2: Create PHP Backend Files

### 2.1 Create Folder Structure
\`\`\`
C:\laragon\www\
  └── advertising-system/
      ├── index.html
      ├── admin.html
      ├── api/
      │   ├── submit-request.php
      │   ├── get-requests.php
      │   ├── update-status.php
      │   └── config.php
      └── db-config.php
\`\`\`

### 2.2 Create `db-config.php`
This file connects to your Laragon MySQL database. **Create a new file** called `db-config.php`:

\`\`\`php
<?php
$host = 'localhost';
$db_name = 'advertising_requests';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit();
}
?>
\`\`\`

### 2.3 Create `api/submit-request.php`
**Create new file** `api/submit-request.php` to handle form submissions:

\`\`\`php
<?php
header('Content-Type: application/json');
include '../db-config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO requests (
            client_name, email, contact_number, company_name, 
            service_type, size_dimensions, other_service, 
            project_description, location, upload_files, status
        ) VALUES (
            :client_name, :email, :contact_number, :company_name,
            :service_type, :size_dimensions, :other_service,
            :project_description, :location, :upload_files, 'New'
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':client_name' => $data['client_name'],
            ':email' => $data['email'],
            ':contact_number' => $data['contact_number'],
            ':company_name' => $data['company_name'],
            ':service_type' => $data['service_type'],
            ':size_dimensions' => $data['size_dimensions'] ?? null,
            ':other_service' => $data['other_service'] ?? null,
            ':project_description' => $data['project_description'],
            ':location' => $data['location'],
            ':upload_files' => $data['upload_files'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
\`\`\`

### 2.4 Create `api/get-requests.php`
**Create new file** `api/get-requests.php` to fetch all requests:

\`\`\`php
<?php
header('Content-Type: application/json');
include '../db-config.php';

try {
    $status = $_GET['status'] ?? null;
    
    if ($status) {
        $sql = "SELECT * FROM requests WHERE status = :status ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':status' => $status]);
    } else {
        $sql = "SELECT * FROM requests ORDER BY created_at DESC";
        $stmt = $conn->query($sql);
    }
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($requests);
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
\`\`\`

### 2.5 Create `api/update-status.php`
**Create new file** `api/update-status.php` to update request status:

\`\`\`php
<?php
header('Content-Type: application/json');
include '../db-config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "UPDATE requests SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':status' => $data['status'],
            ':id' => $data['id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
\`\`\`

---

## Step 3: What To Change In Your HTML Files

### 3.1 In `index.html` - What To REMOVE
**REMOVE** everything related to localStorage. Search for and delete:
- `localStorage.setItem('requests'...`
- `localStorage.getItem('requests'...`
- All localStorage references in the submit function

**WHAT TO KEEP:**
- Form HTML structure - NO CHANGE
- CSS styling - NO CHANGE
- Form validation logic - NO CHANGE
- Modal/Confirmation UI - NO CHANGE

### 3.2 In `index.html` - What TO ADD
Replace the form submission function with this API call:

Find this in your JavaScript:
\`\`\`javascript
// Old: saves to localStorage
requests.push(formData);
localStorage.setItem('requests', JSON.stringify(requests));
\`\`\`

Replace with:
\`\`\`javascript
// New: sends to PHP API
fetch('api/submit-request.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(formData)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Request submitted successfully!');
        form.reset();
    } else {
        alert('Error: ' + data.error);
    }
})
.catch(error => console.error('Error:', error));
\`\`\`

### 3.3 In `admin.html` - What To REMOVE
**REMOVE** all localStorage references:
- `localStorage.getItem('requests'...`
- All localStorage data fetching code

**WHAT TO KEEP:**
- Sidebar navigation - NO CHANGE
- Button styling and layout - NO CHANGE
- Modal structure - NO CHANGE
- Detail view HTML - NO CHANGE

### 3.4 In `admin.html` - What TO ADD
Replace the data loading function with this API call:

Find where requests are loaded from localStorage. Replace with:

\`\`\`javascript
// Fetch requests from database
function loadRequests() {
    fetch('api/get-requests.php')
    .then(response => response.json())
    .then(data => {
        requests = data;
        displayRequests('New');
    })
    .catch(error => console.error('Error:', error));
}

// Call this every second to auto-refresh
setInterval(loadRequests, 1000);

// Load on page start
loadRequests();
\`\`\`

Replace status update buttons with:

\`\`\`javascript
// When Accept, Reject, In Progress, or Completed is clicked
function updateStatus(requestId, newStatus) {
    fetch('api/update-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: requestId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadRequests();  // Refresh the list
            closeDetailModal();  // Close the modal
        }
    })
    .catch(error => console.error('Error:', error));
}
\`\`\`

---

## Step 4: Access Your System Locally

1. Open Laragon and start Apache + MySQL
2. Copy your `advertising-system` folder to `C:\laragon\www\`
3. Open browser and go to: `http://localhost/advertising-system/index.html`
4. Access admin dashboard at: `http://localhost/advertising-system/admin.html`

---

## IMPORTANT - What NOT To Change

❌ **DO NOT** change HTML structure or form field names
❌ **DO NOT** modify CSS styling (keep responsive design intact)
❌ **DO NOT** change modal/confirmation UI
❌ **DO NOT** modify button layout or sidebar structure
❌ **DO NOT** rename your HTML files
❌ **DO NOT** change the database table column names

✅ **ONLY** remove localStorage code
✅ **ONLY** add the API fetch calls
✅ **ONLY** create the PHP backend files

---

## Troubleshooting

**Error: "Connection failed"**
- Make sure MySQL is running in Laragon
- Check db-config.php username/password matches your setup
- Verify database name is `advertising_requests`

**Error: "404 api/submit-request.php"**
- Make sure api folder exists in `C:\laragon\www\advertising-system\api\`
- Check file names are exact (lowercase, no spaces)

**Requests not showing in admin**
- Check browser console for error messages (F12 → Console)
- Verify MySQL database has the `requests` table
- Make sure auto-refresh interval is running

**Form won't submit**
- Check browser console for errors
- Verify api/submit-request.php path is correct
- Ensure db-config.php has correct credentials

---

## Database Backup

To backup your database regularly:
1. Open phpMyAdmin
2. Click your `advertising_requests` database
3. Click **Export**
4. Save as `.sql` file

---

## Next Steps (Advanced)

Once Laragon is working, you can:
- Add file upload handling to save uploaded files
- Add email notifications when requests are submitted
- Add user authentication for admin dashboard
- Deploy to a live server instead of local Laragon

---

This guide keeps your HTML/CSS/JavaScript structure exactly the same - you're only changing the data storage from localStorage to a real database!
