<?php

declare(strict_types=1);

namespace Membrane\OpenAPIReader\Tests\Fixtures;

use Generator;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V31;
use Membrane\OpenAPIReader\ValueObject\Value;

final class ProvidesTrainTravelApi
{
    private const API = __DIR__ . '/train-travel-api.yaml';

    /**
     * @return \Generator<array{
     *     0: string,
     *     1: string,
     *     2: Method,
     *     3: V31\Operation,
     * }>
     */
    public static function provideOperations(): Generator
    {
        yield 'get-stations' => [
            self::API,
            '/stations',
            Method::GET,
            self::getStations(),
        ];

        yield 'get-trips' => [
            self::API,
            '/trips',
            Method::GET,
            self::getTrips(),
        ];

        yield 'get-bookings' => [
            self::API,
            '/bookings',
            Method::GET,
            self::getBookings(),
        ];

        yield 'create-booking' => [
            self::API,
            '/bookings',
            Method::POST,
            self::createBooking(),
        ];

        yield 'get-booking' => [
            self::API,
            '/bookings/{bookingId}',
            Method::GET,
            self::getBooking(),
        ];

        yield 'delete-booking' => [
            self::API,
            '/bookings/{bookingId}',
            Method::DELETE,
            self::deleteBooking(),
        ];

        yield 'create-booking-payment' => [
            self::API,
            '/bookings/{bookingId}/payment',
            Method::POST,
            self::createBookingPayment(),
        ];
    }

    private static function getStations(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/stations'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [],
            method: Method::GET,
            operation: new Partial\Operation('get-stations', [], [
                new Partial\Parameter(
                    name: 'page',
                    in: 'query',
                    schema: new Partial\Schema(type: 'integer', minimum: 1),
                ),
                new Partial\Parameter(
                    name: 'coordinates',
                    in: 'query',
                    schema: new Partial\Schema(type: 'string'),
                ),
                new Partial\Parameter(
                    name: 'search',
                    in: 'query',
                    schema: new Partial\Schema(type: 'string'),
                ),
            ])
        );
    }

    private static function getTrips(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/trips'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [],
            method: Method::GET,
            operation: new Partial\Operation('get-trips', [], [
                new Partial\Parameter(
                    name: 'origin',
                    in: 'query',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'uuid'),
                ),
                new Partial\Parameter(
                    name: 'destination',
                    in: 'query',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'uuid'),
                ),
                new Partial\Parameter(
                    name: 'date',
                    in: 'query',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'date-time'),
                ),
                new Partial\Parameter(
                    name: 'bicycles',
                    in: 'query',
                    schema: new Partial\Schema(type: 'boolean'),
                ),
                new Partial\Parameter(
                    name: 'dogs',
                    in: 'query',
                    schema: new Partial\Schema(type: 'boolean'),
                ),
                new Partial\Parameter(
                    name: 'page',
                    in: 'query',
                    schema: new Partial\Schema(type: 'number', default: new Value(1)),
                ),
            ])
        );
    }

    private static function getBookings(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [],
            method: Method::GET,
            operation: new Partial\Operation('get-bookings', [], [
                new Partial\Parameter(
                    name: 'page',
                    in: 'query',
                    schema: new Partial\Schema(
                        type: 'integer',
                        minimum: 1,
                        default: new Value(1),
                    ),
                ),
            ])
        );
    }

    private static function createBooking(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [],
            method: Method::POST,
            operation: new Partial\Operation('create-booking', [], [])
        );
    }

    private static function getBooking(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings/{bookingId}'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [new V31\Parameter(
                parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings/{bookingId}'),
                parameter:new Partial\Parameter(
                    name: 'bookingId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'uuid'),
                )
            )],
            method: Method::GET,
            operation: new Partial\Operation('get-booking', [], [])
        );
    }

    private static function deleteBooking(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings/{bookingId}'),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [new V31\Parameter(
                parentIdentifier: new Identifier('Train Travel API(1.0.0)', '/bookings/{bookingId}'),
                parameter:new Partial\Parameter(
                    name: 'bookingId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'uuid'),
                )
            )],
            method: Method::DELETE,
            operation: new Partial\Operation('delete-booking', [], [])
        );
    }

    private static function createBookingPayment(): V31\Operation
    {
        return V31\Operation::fromPartial(
            parentIdentifier: new Identifier(
                'Train Travel API(1.0.0)',
                '/bookings/{bookingId}/payment'
            ),
            pathServers: [new V31\Server(
                new Identifier('Train Travel API(1.0.0)'),
                new Partial\Server(url: 'https://api.example.com'),
            )],
            pathParameters: [new V31\Parameter(
                parentIdentifier: new Identifier(
                    'Train Travel API(1.0.0)',
                    '/bookings/{bookingId}/payment'
                ),
                parameter:new Partial\Parameter(
                    name: 'bookingId',
                    in: 'path',
                    required: true,
                    schema: new Partial\Schema(type: 'string', format: 'uuid'),
                )
            )],
            method: Method::POST,
            operation: new Partial\Operation('create-booking-payment', [], [])
        );
    }
}
