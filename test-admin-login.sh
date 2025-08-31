#!/bin/bash

echo "Testing admin login with proper CSRF token..."

# Get the login page and extract CSRF token
echo "1. Getting login page and extracting CSRF token..."
csrf_token=$(curl -s -c /tmp/admin_cookies.txt https://dalthaus.net/admin/login.php | grep 'csrf_token' | grep -o 'value="[^"]*"' | cut -d'"' -f2)

if [ -n "$csrf_token" ]; then
    echo "   CSRF token extracted: ${csrf_token:0:20}..."
else
    echo "   ERROR: Could not extract CSRF token"
    exit 1
fi

# Attempt login with CSRF token
echo "2. Attempting login with credentials and CSRF token..."
login_response=$(curl -s -w "\nHTTP_CODE:%{http_code}\nREDIRECT_URL:%{redirect_url}\n" \
    -b /tmp/admin_cookies.txt \
    -c /tmp/admin_cookies.txt \
    -X POST \
    -d "username=admin&password=130Bpm&csrf_token=${csrf_token}" \
    https://dalthaus.net/admin/login.php)

echo "$login_response" | grep -v "HTTP_CODE\|REDIRECT_URL" > /tmp/login_content.html
http_code=$(echo "$login_response" | grep "HTTP_CODE" | cut -d':' -f2)
redirect_url=$(echo "$login_response" | grep "REDIRECT_URL" | cut -d':' -f2-)

echo "   HTTP Code: $http_code"
echo "   Redirect URL: $redirect_url"

# Check login content for success/failure indicators
if grep -q "Invalid" /tmp/login_content.html; then
    echo "   LOGIN RESULT: FAILED - Invalid credentials or token"
    grep -o "Invalid[^<]*" /tmp/login_content.html
elif grep -q "dashboard\|admin" /tmp/login_content.html && [ ! -s /tmp/login_content.html ] || [ $(wc -c < /tmp/login_content.html) -lt 100 ]; then
    echo "   LOGIN RESULT: SUCCESS - Redirected or minimal response"
else
    echo "   LOGIN RESULT: UNKNOWN - Checking content..."
    head -n 5 /tmp/login_content.html
fi

# Test if we can access admin dashboard
echo "3. Testing access to admin dashboard..."
dashboard_response=$(curl -s -w "HTTP_CODE:%{http_code}\n" \
    -b /tmp/admin_cookies.txt \
    https://dalthaus.net/admin/dashboard.php)

dashboard_code=$(echo "$dashboard_response" | grep "HTTP_CODE" | cut -d':' -f2)
echo "$dashboard_response" | grep -v "HTTP_CODE" > /tmp/dashboard_content.html

echo "   Dashboard HTTP Code: $dashboard_code"

if [ "$dashboard_code" = "200" ] && ! grep -q "login" /tmp/dashboard_content.html; then
    echo "   DASHBOARD ACCESS: SUCCESS - Can access admin dashboard"
elif grep -q "login" /tmp/dashboard_content.html; then
    echo "   DASHBOARD ACCESS: FAILED - Redirected to login (session not established)"
else
    echo "   DASHBOARD ACCESS: UNKNOWN - HTTP $dashboard_code"
fi

# Cleanup
rm -f /tmp/admin_cookies.txt /tmp/login_content.html /tmp/dashboard_content.html

echo "Admin login test complete."