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
                    schema: new Partial\Schema(type: 'integer', minimum: 1, default: new Value(1)),
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
                    schema: new Partial\Schema(type: 'boolean', default: new Value(false)),
                ),
                new Partial\Parameter(
                    name: 'dogs',
                    in: 'query',
                    schema: new Partial\Schema(type: 'boolean', default: new Value(false)),
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
            operation: new Partial\Operation(
                operationId: 'create-booking',
                servers: [],
                parameters: [],
                requestBody: new Partial\RequestBody(
                    content: [
                        new Partial\MediaType(
                            contentType: 'application/json',
                            schema: new Partial\Schema(
                                type: 'object',
                                properties: [
                                     'id' => new Partial\Schema(
                                         type: 'string',
                                         format: 'uuid',
                                         description: 'Unique identifier for the booking',
                                     ),
                                     'trip_id' => new Partial\Schema(
                                         type: 'string',
                                         format: 'uuid',
                                         description: 'Identifier of the booked trip',
                                     ),
                                     'passenger_name' => new Partial\Schema(
                                         type: 'string',
                                         description: 'Name of the passenger',
                                     ),
                                     'has_bicycle' => new Partial\Schema(
                                         type: 'boolean',
                                         description: 'Indicates whether the passenger has a bicycle.',
                                     ),
                                     'has_dog' => new Partial\Schema(
                                         type: 'boolean',
                                         description: 'Indicates whether the passenger has a dog.',
                                     ),
                                 ],
                            ),
                        ),
                        new Partial\MediaType(
                            contentType: 'application/xml',
                            schema: new Partial\Schema(
                                type: 'object',
                                properties: [
                                     'id' => new Partial\Schema(
                                         type: 'string',
                                         format: 'uuid',
                                         description: 'Unique identifier for the booking',
                                     ),
                                     'trip_id' => new Partial\Schema(
                                         type: 'string',
                                         format: 'uuid',
                                         description: 'Identifier of the booked trip',
                                     ),
                                     'passenger_name' => new Partial\Schema(
                                         type: 'string',
                                         description: 'Name of the passenger',
                                     ),
                                     'has_bicycle' => new Partial\Schema(
                                         type: 'boolean',
                                         description: 'Indicates whether the passenger has a bicycle.',
                                     ),
                                     'has_dog' => new Partial\Schema(
                                         type: 'boolean',
                                         description: 'Indicates whether the passenger has a dog.',
                                     ),
                                 ],
                            ),
                        ),
                    ],
                    required: true,
                )
            )
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
            operation: new Partial\Operation(
                operationId: 'create-booking-payment',
                servers: [],
                parameters: [],
                requestBody: new Partial\RequestBody(
                    content: [
                        new Partial\MediaType(
                            contentType: 'application/json',
                            schema: new Partial\Schema(
                                type: 'object',
                                properties: [
                                     'id' => new Partial\Schema(
                                         type: 'string',
                                         format: 'uuid',
                                         description: 'Unique identifier for the payment. This will be a unique identifier for the payment, and is used to reference the payment in other objects.',
                                     ),
                                     'amount' => new Partial\Schema(
                                         type: 'number',
                                         exclusiveMinimum: 0,
                                         description: 'Amount intended to be collected by this payment. A positive decimal figure describing the amount to be collected.',
                                     ),
                                     'source' => new Partial\Schema(
                                         oneOf: [
                                             new Partial\Schema(
                                                 type: 'object',
                                                 required: [
                                                     'name',
                                                     'number',
                                                     'cvc',
                                                     'exp_month',
                                                     'exp_year',
                                                     'address_country',
                                                 ],
                                                 properties:[
                                                     'object' => new Partial\Schema(
                                                         type: 'string',
                                                     ),
                                                     'name' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'Cardholder\'s full name as it appears on the card.',
                                                     ),
                                                     'number' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'The card number, as a string without any separators. On read all but the last four digits will be masked for security.',
                                                     ),
                                                     'cvc' => new Partial\Schema(
                                                         type: 'integer',
                                                         maxLength: 4,
                                                         minLength: 3,
                                                         description: 'Card security code, 3 or 4 digits usually found on the back of the card.',
                                                     ),
                                                     'exp_month' => new Partial\Schema(
                                                         type: 'integer',
                                                         format: 'int64',
                                                         description: 'Two-digit number representing the card\'s expiration month.',
                                                     ),
                                                     'exp_year' => new Partial\Schema(
                                                         type: 'integer',
                                                         format: 'int64',
                                                         description: 'Four-digit number representing the card\'s expiration year.'
                                                     ),
                                                     'address_line1' => new Partial\Schema(type: 'string'),
                                                     'address_line2' => new Partial\Schema(type: 'string'),
                                                     'address_city' => new Partial\Schema(type: 'string'),
                                                     'address_country' => new Partial\Schema(type: 'string'),
                                                     'address_post_code' => new Partial\Schema(type: 'string'),
                                                 ],
                                                 title: 'Card',
                                                 description: 'A card (debit or credit) to take payment from.',
                                             ),
                                             new Partial\Schema(
                                                 type: ['object'],
                                                 required: [
                                                     'name',
                                                     'number',
                                                     'account_type',
                                                     'bank_name',
                                                     'country',
                                                 ],
                                                 properties: [
                                                     'object' => new Partial\Schema(type: 'string'),
                                                     'name' => new Partial\Schema(type: 'string'),
                                                     'number' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'The account number for the bank account, in string form. Must be a current account.',
                                                     ),
                                                     'sort_code' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'The sort code for the bank account, in string form. Must be a six-digit number.',
                                                     ),
                                                     'account_type' => new Partial\Schema(
                                                         type: 'string',
                                                         enum: [new Value('individual'), new Value('company')],
                                                         description: 'The type of entity that holds the account. This can be either `individual` or `company`.',
                                                     ),
                                                     'bank_name' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'The name of the bank associated with the routing number.',
                                                     ),
                                                     'country' => new Partial\Schema(
                                                         type: 'string',
                                                         description: 'Two-letter country code (ISO 3166-1 alpha-2).',
                                                     ),
                                                 ],
                                                 title: 'Bank Account',
                                                 description: 'A bank account to take payment from. '
                                                 . 'Must be able to make payments in the currency specified in the payment.',
                                             ),
                                         ],
                                         description: 'The payment source to take the payment from. '
                                             . 'This can be a card or a bank account. '
                                             . 'Some of these properties will be hidden on read to protect PII leaking.',
                                     ),
                                     'status' => new Partial\Schema(
                                         type: 'string',
                                         enum: [
                                             new Value('pending'),
                                             new Value('succeeded'),
                                             new Value('failed'),
                                         ],
                                         description: 'The status of the payment, '
                                             . 'one of `pending`, `succeeded`, or `failed`.',
                                     ),
                                     'currency' => new Partial\Schema(
                                         type: 'string',
                                         enum: [
                                             new Value('bam'),
                                             new Value('bgn'),
                                             new Value('chf'),
                                             new Value('eur'),
                                             new Value('gbp'),
                                             new Value('nok'),
                                             new Value('sek'),
                                             new Value('try'),
                                         ],
                                         description: 'Three-letter [ISO currency code](https://www.iso.org/iso-4217-currency-codes.html), in lowercase.',
                                     )
                                 ],
                            ),
                        ),
                    ],
                    required: true,
                ),
            )
        );
    }
}
