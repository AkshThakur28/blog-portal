<?php
// Generate bcrypt hashes for seed users
$admin = password_hash('Admin@123', PASSWORD_BCRYPT);
$user1 = password_hash('User@123', PASSWORD_BCRYPT);
$user2 = password_hash('User@123', PASSWORD_BCRYPT);

echo "ADMIN:$admin\n";
echo "USER1:$user1\n";
echo "USER2:$user2\n";
