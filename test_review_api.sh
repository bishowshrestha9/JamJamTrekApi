#!/bin/bash

# Review Controller API Testing Script
# Base URL for the API
BASE_URL="http://localhost:8000/api"

echo "=========================================="
echo "Review Controller API Testing"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to print test results
print_result() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASSED${NC}: $2"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}✗ FAILED${NC}: $2"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

# Function to make API call and display response
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo ""
    echo -e "${YELLOW}Testing:${NC} $description"
    echo "Endpoint: $method $endpoint"
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data")
    fi
    
    # Extract HTTP status code (last line)
    http_code=$(echo "$response" | tail -n1)
    # Extract response body (all but last line)
    body=$(echo "$response" | sed '$d')
    
    echo "HTTP Status: $http_code"
    echo "Response:"
    echo "$body" | jq '.' 2>/dev/null || echo "$body"
    
    # Return status based on HTTP code
    if [[ $http_code -ge 200 && $http_code -lt 300 ]]; then
        return 0
    else
        return 1
    fi
}

echo ""
echo "=========================================="
echo "1. Testing: Submit Review (POST /api/reviews)"
echo "=========================================="

test_endpoint "POST" "/reviews" '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "review": "Amazing trekking experience! The guides were professional and the scenery was breathtaking.",
    "rating": 5,
    "status": false
}' "Submit a new review"
print_result $? "Submit Review"

echo ""
echo "=========================================="
echo "2. Testing: Submit Review - Rate Limit (POST /api/reviews)"
echo "=========================================="

test_endpoint "POST" "/reviews" '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "review": "Another review from same email",
    "rating": 4,
    "status": false
}' "Submit review with same email (should fail due to rate limit)"
# This should fail with 429, so we expect non-zero
if [ $? -ne 0 ]; then
    print_result 0 "Rate Limit Check (correctly blocked)"
else
    print_result 1 "Rate Limit Check (should have been blocked)"
fi

echo ""
echo "=========================================="
echo "3. Testing: Submit Review - Validation Error (POST /api/reviews)"
echo "=========================================="

test_endpoint "POST" "/reviews" '{
    "name": "Jane Doe",
    "email": "invalid-email"
}' "Submit review with invalid data (should fail validation)"
# This should fail with 422, so we expect non-zero
if [ $? -ne 0 ]; then
    print_result 0 "Validation Error Check (correctly rejected)"
else
    print_result 1 "Validation Error Check (should have been rejected)"
fi

echo ""
echo "=========================================="
echo "4. Testing: Get All Reviews (GET /api/reviews)"
echo "=========================================="

test_endpoint "GET" "/reviews" "" "Get all reviews with default pagination"
print_result $? "Get All Reviews"

echo ""
echo "=========================================="
echo "5. Testing: Get All Reviews with Pagination (GET /api/reviews?per_page=5)"
echo "=========================================="

test_endpoint "GET" "/reviews?per_page=5" "" "Get all reviews with custom pagination"
print_result $? "Get All Reviews with Pagination"

echo ""
echo "=========================================="
echo "6. Testing: Get Publishable Reviews (GET /api/reviews/publishable)"
echo "=========================================="

test_endpoint "GET" "/reviews/publishable" "" "Get only approved/publishable reviews"
print_result $? "Get Publishable Reviews"

echo ""
echo "=========================================="
echo "7. Testing: Get Four Latest Reviews (GET /api/reviews/four)"
echo "=========================================="

test_endpoint "GET" "/reviews/four" "" "Get 4 most recent approved reviews"
print_result $? "Get Four Latest Reviews"

echo ""
echo "=========================================="
echo "8. Testing: Get Review Statistics (GET /api/reviews/stats)"
echo "=========================================="

test_endpoint "GET" "/reviews/stats" "" "Get positive and negative review counts"
print_result $? "Get Review Statistics"

echo ""
echo "=========================================="
echo "9. Testing: Approve Review (PUT /api/reviews/1/approve)"
echo "=========================================="

test_endpoint "PUT" "/reviews/1/approve" "" "Approve a review by ID"
print_result $? "Approve Review"

echo ""
echo "=========================================="
echo "10. Testing: Delete Review (DELETE /api/reviews/999)"
echo "=========================================="

test_endpoint "DELETE" "/reviews/999" "" "Delete a review by ID (using non-existent ID)"
print_result $? "Delete Review"

echo ""
echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo -e "Total Tests: $TOTAL_TESTS"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}"
echo -e "${RED}Failed: $FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}All tests completed!${NC}"
else
    echo -e "${YELLOW}Some tests failed. Please review the output above.${NC}"
fi
