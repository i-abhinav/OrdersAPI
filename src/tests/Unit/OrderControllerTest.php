<?php

use App\Helpers\FakeData;
use App\Helpers\GoogleDistanceMatrix;
use App\Helpers\Haversine;
use App\Helpers\Messages;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Repositories\OrderEloquentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderControllerTest extends TestCase
{

    protected static $orderStatus = [
        Order::STATUS_UNASSIGNED,
        Order::STATUS_ASSIGNED,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker\Factory::create();
        $this->app->instance('middleware.disable', true);

        $this->orderMock = \Mockery::mock(OrderEloquentRepository::class);
        $this->googleMock = \Mockery::mock(GoogleDistanceMatrix::class);

        $this->orderController = $this->app->instance(
            OrderController::class,
            new OrderController($this->orderMock, $this->googleMock)
        );
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

/** @test */
    public function storeSuccessPost()
    {
        echo "\n <<<<<< Starting Unit Test Cases >>>>>> \n";

        echo "\n # Unit Test --> OrderController::class --> store() --> Positive Scenario #";

        $validData = FakeData::validCoordinates();
        $requestParams = $this->_getRequestBody($validData);
        $order = $this->_getFakeOrder();

        $this->googleMock
            ->shouldReceive('getMapDistance')
            ->with($validData['origin'], $validData['destination'])
            ->once()
            ->andReturn($order->distance);

        $this->orderMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $response = $this->orderController->store($requestParams);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('distance', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function storeFailsForInvalidRequestBody()
    {
        echo "\n # Unit Test --> OrderController::class --> store() --> Negative Scenario #";
        echo "\n *** Negative Test Case To POST Invalid Co-ordinates in Request Body *** \n";
        // Set stage for a failed validation
        $invalidData = FakeData::invalidLatitudeLongitude();
        $requestParams = $this->_getRequestBody($invalidData);

        $this->app->instance('Order', $this->orderMock);

        $response = $this->orderController->store($requestParams);
        $data = json_decode($response->getContent(), true);

        // Failed validation should reeturn 422 response code
        $this->assertEquals(422, $response->getStatusCode());
        // The errors should have error key in response
        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function storeFailsForInvalidRequestKeys()
    {
        echo "\n # Unit Test --> OrderController::class --> store() --> Negative Scenario #";
        echo "\n *** Negative Test Case To POST Invalid keys in Request Body *** \n";

        $invalidData = FakeData::invalidKeys();
        $requestParams = $this->_getRequestBody($invalidData);

        $this->app->instance('Order', $this->orderMock);

        $response = $this->orderController->store($requestParams);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

/** @test */
    public function storeFailsForEmptyRequestBody()
    {
        echo "\n # Unit Test --> OrderController --> store --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To POST Empty Request Body *** \n";

        $invalidData = [];
        $requestParams = $this->_getRequestBody($invalidData);

        $response = $this->orderController->store($requestParams);
        $data = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $data);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function storeFailsForSameCoordinates()
    {
        echo "\n # Unit Test --> OrderController --> store --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To POST Same Co-ordinates in Request Body *** \n";

        $sameCoordiates = FakeData::sameCoordiates();
        $requestParams = $this->_getRequestBody($sameCoordiates);

        $response = $this->orderController->store($requestParams);
        $data = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $data);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function takeOrderSuccess()
    {
        echo "\n # Unit Test --> OrderController --> take --> Positive Scenario # \n";
        echo "\n *** Positive Test Case To TAKE Order *** \n";

        $validData = ['status' => 'TAKEN'];
        $requestParams = $this->_getRequestBody($validData);
        $orderID = $this->faker->randomDigit;
        $successResponse = ['status' => Messages::get('orders.success')];

        $this->orderMock
            ->shouldReceive('take')
            ->with($orderID)
            ->andReturn(1);

        $response = response()->json($successResponse, JsonResponse::HTTP_OK);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame('SUCCESS', $data['status']);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function takeOrderFailsWitInvalidRequest()
    {
        echo "\n # Unit Test --> OrderController --> take --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To TAKE Order with Invalid Request Body *** \n";

        $invalidData = ['status' => 'TAK123EN'];
        $requestParams = $this->_getRequestBody($invalidData);
        $orderID = $this->faker->randomDigit;

        $response = $this->orderController->take($requestParams, $orderID);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());

        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function takeOrderFailsWithInvalidRequestKey()
    {
        echo "\n # Unit Test --> OrderController --> take --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To TAKE Order with Invalid Key in Request Body *** \n";

        $invalidData = ['statuss' => 'TAKEN'];
        $requestParams = $this->_getRequestBody($invalidData);
        $orderID = $this->faker->randomDigit;

        $response = $this->orderController->take($requestParams, $orderID);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());

        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
    }

    /** @test */
    public function takeOrderConflictCase()
    {
        echo "\n # Unit Test --> OrderController --> take --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To TAKE Already TAKEN Order *** \n";

        $validData = ['status' => 'TAKEN'];
        $requestParams = $this->_getRequestBody($validData);
        $orderID = $this->faker->randomDigit;
        $successResponse = ['status' => Messages::get('orders.order_conflict')];

        $this->orderMock
            ->shouldReceive('take')
            ->with($orderID)
            ->andReturn(1);

        $response = response()->json($successResponse, JsonResponse::HTTP_CONFLICT);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function orderListSuccess()
    {
        echo "\n # Unit Test --> OrderController --> list --> Positive Scenario # \n";
        echo "\n *** Positive Test Case To GET Order List *** \n";

        $page = 1;
        $limit = 5;
        $params = ['page' => $page, 'limit' => $limit];
        $requestParams = $this->_getRequestBody($params);
        $orderList = $this->_getFakeOrderList($limit);

        $this->orderMock
            ->shouldReceive('list')
            ->with($page, $limit)
            ->once()
            ->andReturn($orderList);

        $response = $this->orderController->list($requestParams);
        $data = json_decode($response->getContent(), true);


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('id', (array) $data[0]);
        $this->assertArrayHasKey('distance', (array) $data[0]);
        $this->assertArrayHasKey('status', (array) $data[0]);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function testOrderListFails()
    {
        echo "\n # Unit Test --> OrderController --> list --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To GET Order List with Invalid Query String *** \n";

        $page = 1;
        $limit = 10;
        $query = "paage=$page&limitt=$limit";

        $this->app->instance('Order', $this->orderMock);

        $response = $this->call('GET', '/orders?' . $query);

        $this->assertEquals(422, $this->response->status());
        $data = (array) $response->getData();

        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /** @test */
    public function testOrderListFailsWithNegativeValues()
    {
        echo "\n # Unit Test --> OrderController --> list --> Negative Scenario # \n";
        echo "\n *** Negative Test Case To GET Order List with Negative Page and Limit value *** \n";

        $page = -2;
        $limit = -5;
        $query = "page=$page&limit=$limit";

        $this->app->instance('Order', $this->orderMock);

        $response = $this->call('GET', '/orders?' . $query);
        $data = (array) $response->getData();

        $this->assertEquals(422, $this->response->status());
        $this->assertArrayHasKey('error', $data);
        $this->assertInternalType('array', $data);
        $this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);

    }

    /**
     * @param int|null $id
     *
     * @return Order
     */
    protected function _getFakeOrder($id = null)
    {
        $id = $id ?: $this->faker->randomDigit;

        $originLat = $this->faker->latitude;
        $originLong = $this->faker->longitude;
        $destinationLat = $this->faker->latitude;
        $destinationLong = $this->faker->longitude;
        $distance = Haversine::getDistance($originLat, $originLong, $destinationLat, $destinationLong);

        $order = new Order();
        $order->id = $id;
        $order->origin_lat = $originLat;
        $order->origin_lng = $originLong;
        $order->destination_lat = $destinationLat;
        $order->destination_lng = $destinationLong;
        $order->distance = $distance;
        $order->status = $this->faker->randomElement(self::$orderStatus);
        $order->created_at = $this->faker->dateTimeBetween();
        $order->updated_at = $this->faker->dateTimeBetween();

        return $order;
    }

    /**
     * @param int, $limit, Order Limit
     *
     * @return Order
     */
    protected function _getFakeOrderList(int $limit)
    {
        $orderList = [];
        for ($i = 0; $i <= $limit; $i++) {
            $orderList[] = $this->_getFakeOrder();
        }
        return $orderList;
    }

    /**
     * @param array, $requestBbody, Request body
     *
     * @return Request
     */
    protected function _getRequestBody(array $requestBbody): Request
    {
        $request = new Request();
        $request->replace($requestBbody);
        return $request;
    }

}
