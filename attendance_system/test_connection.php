<?php
require_once 'config/db.php';

if ($conn->ping()) {
    echo "✅ Database connected successfully!";
} else {
    echo "❌ Connection failed: " . $conn->error;
}
?>