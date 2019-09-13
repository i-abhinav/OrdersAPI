<?php

use App\Helpers\FakeData;

class OrderIntegrationTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->app->instance('middleware.disable', true);
    }

/**
 * Test Order Create/Insert API Request
 *
 * @param array|json|object|NULL, $requestParams, Request Body
 * @param int|string $statusCode, Desired Status Code
 */
    protected function _checkOrderCreateAssert($requestParams, $statusCode)
    {
        $response = $this->call('POST', '/orders', $requestParams);

        $data = (array) $response->getData();
        $this->assertEquals($statusCode, $this->response->status());

        switch ($statusCode) {
            case '200':
                $this->assertArrayHasKey('id', $data);
                $this->assertArrayHasKey('distance', $data);
                $this->assertArrayHasKey('status', $data);
                break;
            case '400':
                $this->assertArrayHasKey('error', $data);
                break;
            case '422':
                $this->assertArrayHasKey('error', $data);
                break;

            default:
                # code...
                break;
        }
    }

/**
 * Test Order Take/Update API Request
 *
 * @param int|string|NULL, $orderID, Order ID
 * @param array|json|object|NULL, $requestParams, Request Body
 * @param int|string $statusCode, Desired Status Code
 */
    protected function _checkOrderUpdateAssert($orderID = null, $requestParams, $statusCode)
    {
        $response = $this->call('PATCH', '/orders/' . $orderID, $requestParams);
        $data = (array) $response->getData();

        $this->assertEquals($statusCode, $this->response->status());

        switch ($statusCode) {
            case '200':
                $this->assertArrayHasKey('status', $data);
                $this->assertContains($value = 'SUCCESS', $data, "response doesn't contains SUCCESS as value");

                break;
            case '400':
                $this->assertArrayHasKey('error', $data);
                break;
            case '406':
                $this->assertArrayHasKey('error', $data);
            case '404':
                $this->assertArrayHasKey('error', $data);
                $this->assertContains($value = 'ORDER_ID_NOT_EXIST', $data);
                break;
            case '405':
                $this->assertArrayHasKey('error', $data);
                $this->assertContains($value = 'METHOD_NOT_ALLOWED', $data);
                break;
            case '422':
                $this->assertArrayHasKey('error', $data);
                break;
            case '500':
                $this->assertArrayHasKey('error', $data, 'Internal Server error');

            default:
                # code...
                break;
        }
    }

/**
 * Test Order List API Request
 *
 * @param string|NULL, $query, Page and Limit parameters
 * @param int|string $statusCode, Desired Status Code
 */
    protected function _checkOrderListingAssert($query = null, $statusCode)
    {
        $response = $this->call('GET', "/orders?$query", []);
        $data = (array) $response->getData();
        $this->assertEquals($statusCode, $this->response->status());

        switch ($statusCode) {
            case '200':
                foreach ($data as $array) {
                    $this->assertArrayHasKey('id', (array) $array);
                    $this->assertArrayHasKey('distance', (array) $array);
                    $this->assertArrayHasKey('status', (array) $array);
                }
                $this->assertCount(
                    $expectededCount = 5,
                    $data, "Response array contains 5 elements"
                );
                break;
            case '400':
                $this->assertArrayHasKey('error', $data);
                break;
            case '422':
                $this->assertArrayHasKey('error', $data);
                break;

            default:
                # code...
                break;
        }
    }

/** @test */
    public function OrderCreateWithValidScenario()
    {
        echo "\n <<<<<< Starting Integration Test Cases >>>>>> \n";
        echo "\n \t - ### Test case for INSERTING ORDER with Valid Scenario ###";
        $validData = FakeData::validCoordinates();
        $this->_checkOrderCreateAssert($validData, $desiredCode = 200); //success
    }

/** @test */
    public function OrderCreateWithInvalidScenario()
    {
        echo "\n \t - ### Test case for INSERTING ORDER ----> Negative Test Cases ###";

        echo "\n ** Scenario(1). Empty Request Body ---> Expected Response Code(400) **";
        $this->_checkOrderCreateAssert($invalidData = [], $desiredCode = 400); //fail

        $invalidData = FakeData::invalidLongitude();
        echo "\n ** Scenario(2). Invalid Longitude ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::invalidLatitude();
        echo "\n ** Scenario(3). Invalid Latitude ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::emptyLatitude();
        echo "\n ** Scenario(4). Empty Latitude ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::emptyLongitude();
        echo "\n ** Scenario(5). Empty Longitude ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::invalidFormatLatitude();
        echo "\n ** Scenario(6). Invalid Format - not string ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::invalidNumberOfParams();
        echo "\n ** Scenario(7). Invalid Number of Parameters ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $invalidData = FakeData::invalidKeys();
        echo "\n ** Scenario(8). Spelling mistake ---> Expected Response Code(422) **";
        $this->_checkOrderCreateAssert($invalidData, $desiredCode = 422); //validation error

        $sameCoordiates = FakeData::sameCoordiates();
        echo "\n ** Scenario(9). If Origin and Destination Co-ordinates are same **";
        echo "\n ---> Expected Response Code(422)";
        $this->_checkOrderCreateAssert($sameCoordiates, $desiredCode = 422); //validation error

    }

/** @test */
    public function OrderUpdateWithValidScenario()
    {
        echo "\n \t - ### Test case for UPDATING ORDER with Valid Parameters ###";
        $requestBody = FakeData::validCoordinates();
        $response = $this->call('POST', '/orders', $requestBody);
        $data = $response->getData();
        $orderID = $data->id;

        $validData = ['status' => 'TAKEN'];
        $this->_checkOrderUpdateAssert($orderID, $validData, $desiredCode = 200); //success
    }

/** @test */
    public function OrderUpdateWithInvalidScenario()
    {
        echo "\n \t - ### Test case for UPDATING ORDER ----> Negative Test Cases ###";
        echo "\n ** Scenario(1). Invalid Request ---> Expected Response Code(422) **";

        $validRequestBody = ['status' => 'TAKEN'];
        $this->_checkOrderUpdateAssert($orderID = 4, $requestBody = [], $desiredCode = 422); //fail

        echo "\n ** Scenario(2). Empty Order ID ---> Expected Response Code(422) **";
        $this->_checkOrderUpdateAssert($orderID = null, $validRequestBody, $desiredCode = 405); //fail

        echo "\n ** Scenario(3). Invalid format - Order Id ---> Expected Response Code(422) **";
        $this->_checkOrderUpdateAssert($orderID = '12dsd', $validRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(4). Negative Order ID ---> Expected Response Code(422) **";
        $this->_checkOrderUpdateAssert($orderID = -4, $validRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(5). Spelling Mistake - param value ---> Expected Response Code(422) **";
        $invalidRequestBody = ['status' => 'TAKENN'];
        $this->_checkOrderUpdateAssert($orderID, $invalidRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(6). Invalide request param key ---> Expected Response Code(422) **";
        $invalidRequestBody = ['statuss' => 'TAKEN'];
        $this->_checkOrderUpdateAssert($orderID, $invalidRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(7). Empty Param value ---> Expected Response Code(422) **";
        $invalidRequestBody = ['status' => ''];
        $this->_checkOrderUpdateAssert($orderID, $invalidRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(8). Invalid format ---> Expected Response Code(422) **";
        $invalidRequestBody = ['status' => 1234897239];
        $this->_checkOrderUpdateAssert($orderID, $invalidRequestBody, $desiredCode = 422); //fail

        echo "\n ** Scenario(9). Update already Taken Order - Conflict ---> Expected Response Code(409) **";
        $this->_checkOrderUpdateAssert($orderID = 3, $validRequestBody, $desiredCode = 409); // conflict

        echo "\n ** Scenario(10). Order ID not exist in Database ---> Expected Response Code(404) **";
        $this->_checkOrderUpdateAssert($orderID = 12321423143, $validRequestBody, $desiredCode = 404); // not found

    }

    /** @test */
    public function OrderListWithValidScenario()
    {
        echo "\n \t - ### Test case for LISTING ORDER with Valid scenario ###";
        
        $query = 'page=1&limit=5';
        $this->_checkOrderListingAssert($query, $desiredCode = 200);
        
    }
    
    /** @test */
    public function OrderListWithInvalidScenario()
    {
        echo "\n \t - ### Test case for LISTING ORDER ----> Negative Test Cases ###";
        
        echo "\n ** Scenario(1). Invalid Request Param (Page) ---> Expected Response Code(422) **";
        $query = 'page44=1&limit=5';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(2). Spelling Mistake (Page) ---> Expected Response Code(422)  **";
        $query = 'paage=1&limit=5';
        // it will gives result set beacuse page and limit are optional
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(3). Zero value (Page) ---> Expected Response Code(422)  **";
        $query = 'page=0&limit=5';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(4). Negative value (Page) ---> Expected Response Code(422)  **";
        $query = 'page=-2&limit=5';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(5). Spelling Mistake (Limit) ---> Expected Response Code(422)  **";
        $query = 'page=1&limmit=5';
        // it will gives result set beacuse page and limit are optional
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(6). Zero value (Limit) ---> Expected Response Code(422)  **";
        $query = 'page=1&limit=0';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(7). Negative value (Limit) ---> Expected Response Code(422)  **";
        $query = 'page=1&limit=-5';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(8). Invalid Request Param - Limit ---> Expected Response Code(422)  **";
        $query = 'page=1&limit231=5';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
        echo "\n ** Scenario(9). Exceeding - Limit, greater than 1000 ---> Expected Response Code(422)  **";
        $query = 'page=1&limit=1001';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);

        echo "\n ** Scenario(10). Empty query string ---> Expected Response Code(422) **";
        $query = '';
        $this->_checkOrderListingAssert($query, $desiredCode = 422);
        
    }
    
}
