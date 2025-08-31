#!/bin/bash

echo "Testing admin login with correct credentials..."

# Clean up any existing cookies
rm -f /tmp/test_cookies.txt

# Step 1: Get login page and extract CSRF token
echo "1. Getting CSRF token..."
csrf_token=$(curl -s -c /tmp/test_cookies.txt https://dalthaus.net/admin/login.php | grep 'csrf_token' | sed -n 's/.*value="\([^"]*\)".*/\1/p')

if [ -n "$csrf_token" ]; then
    echo "   CSRF token: ${csrf_token:0:20}..."
else
    echo "   ERROR: Could not extract CSRF token"
    exit 1
fi

# Step 2: Try login with kevin/(130Bpm)
echo "2. Testing login with kevin/(130Bpm)..."
login_result=$(curl -s -w "HTTP_CODE:%{http_code}\n" \
    -b /tmp/test_cookies.txt \
    -c /tmp/test_cookies.txt \
    -X POST \
    -d "username=kevin&password=(130Bpm)&csrf_token=${csrf_token}" \
    https://dalthaus.net/admin/login.php)

http_code=$(echo "$login_result" | grep "HTTP_CODE" | cut -d':' -f2)
echo "$login_result" | grep -v "HTTP_CODE" > /tmp/kevin_login.html

echo "   HTTP Code: $http_code"

# Check for success indicators
if [ "$http_code" = "302" ] || [ "$http_code" = "301" ]; then
    echo "   LOGIN RESULT: SUCCESS (redirected)"
elif grep -q "Invalid" /tmp/kevin_login.html; then
    echo "   LOGIN RESULT: FAILED - Invalid credentials"
    grep -o "Invalid[^<]*" /tmp/kevin_login.html
elif [ "$http_code" = "500" ]; then
    echo "   LOGIN RESULT: FAILED - Server error (500)"
elif [ "$http_code" = "200" ] && [ $(wc -c < /tmp/kevin_login.html) -lt 100 ]; then
    echo "   LOGIN RESULT: SUCCESS (minimal response)"
else
    echo "   LOGIN RESULT: UNKNOWN - Need to check content"
    head -n 3 /tmp/kevin_login.html
fi

# Step 3: Test dashboard access
echo "3. Testing dashboard access..."
dashboard_result=$(curl -s -w "HTTP_CODE:%{http_code}\n" \
    -b /tmp/test_cookies.txt \
    https://dalthaus.net/admin/dashboard.php)

dashboard_code=$(echo "$dashboard_result" | grep "HTTP_CODE" | cut -d':' -f2)
echo "$dashboard_result" | grep -v "HTTP_CODE" > /tmp/dashboard.html

echo "   Dashboard HTTP Code: $dashboard_code"

if [ "$dashboard_code" = "200" ] && ! grep -q "login" /tmp/dashboard.html; then
    echo "   DASHBOARD ACCESS: SUCCESS"
    echo "   Dashboard title: $(grep -o '<title>[^<]*</title>' /tmp/dashboard.html | sed 's/<[^>]*>//g')"
else
    echo "   DASHBOARD ACCESS: FAILED"
fi

# Step 4: Try with admin/130Bpm as fallback
echo "4. Testing fallback with admin/130Bpm..."
csrf_token2=$(curl -s -c /tmp/test_cookies2.txt https://dalthaus.net/admin/login.php | grep 'csrf_token' | sed -n 's/.*value="\([^"]*\)".*/\1/p')

fallback_result=$(curl -s -w "HTTP_CODE:%{http_code}\n" \
    -b /tmp/test_cookies2.txt \
    -c /tmp/test_cookies2.txt \
    -X POST \
    -d "username=admin&password=130Bpm&csrf_token=${csrf_token2}" \
    https://dalthaus.net/admin/login.php)

fallback_code=$(echo "$fallback_result" | grep "HTTP_CODE" | cut -d':' -f2)
echo "   Fallback HTTP Code: $fallback_code"

if [ "$fallback_code" = "302" ] || [ "$fallback_code" = "301" ]; then
    echo "   FALLBACK LOGIN: SUCCESS"
elif [ "$fallback_code" = "500" ]; then
    echo "   FALLBACK LOGIN: FAILED - Server error"
else
    echo "   FALLBACK LOGIN: HTTP $fallback_code"
fi

# Clean up
rm -f /tmp/test_cookies.txt /tmp/test_cookies2.txt /tmp/kevin_login.html /tmp/dashboard.html

echo "Login test complete."