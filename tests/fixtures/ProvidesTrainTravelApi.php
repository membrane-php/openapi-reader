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
            operation: new Partial\Operation(
                operationId: 'get-stations',
                servers: [],
                parameters: [
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
                ],
                responses: [
                    200 => new Partial\Response(
                        description: 'OK',
                        headers: [
                            'Cache-Control' => self::refCacheControl(),
                            'RateLimit' => self::refRateLimit(),
                        ],
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(
                                    type: 'object',
                                    properties: ['data' => new Partial\Schema(
                                        type: 'array',
                                        items: self::refStation(),
                                    )]
                                )
                            ),
                            'application/xml' => new Partial\MediaType(
                                contentType: 'application/xml',
                                schema: new Partial\Schema(allOf: [
                                    self::refWrapperCollection(),
                                    new Partial\Schema(properties: [
                                        'data' => new Partial\Schema(
                                            type: 'array',
                                            items: self::refStation(),
                                        ),
                                    ]),
                                    new Partial\Schema(properties: [
                                        'links' => new Partial\Schema(allOf: [
                                            self::refLinksSelf(),
                                            self::refLinksPagination(),
                                        ]),
                                    ]),
                                ])
                            )
                        ]
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ]
            ),
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
            operation: new Partial\Operation(
                operationId: 'get-trips',
                servers: [],
                parameters: [
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
                ],
                responses: [
                    200 => new Partial\Response(
                        description: 'A list of available train trips',
                        headers: [
                            'Cache-Control' => self::refCacheControl(),
                            'RateLimit' => self::refRateLimit(),
                        ],
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(allOf: [
                                    self::refWrapperCollection(),
                                    new Partial\Schema(properties: [
                                        'data' => new Partial\Schema(
                                            type: 'array',
                                            items: self::refTrip(),
                                        ),
                                    ]),
                                    new Partial\Schema(properties: [
                                        'links' => new Partial\Schema(allOf: [
                                            self::refLinksSelf(),
                                            self::refLinksPagination(),
                                        ]),
                                    ]),
                                ])
                            ),
                            'application/xml' => new Partial\MediaType(
                                contentType: 'application/xml',
                                schema: new Partial\Schema(allOf: [
                                    self::refWrapperCollection(),
                                    new Partial\Schema(properties: [
                                        'data' => new Partial\Schema(
                                            type: 'array',
                                            items: self::refTrip(),
                                        ),
                                    ]),
                                    new Partial\Schema(properties: [
                                        'links' => new Partial\Schema(allOf: [
                                            self::refLinksSelf(),
                                            self::refLinksPagination(),
                                        ]),
                                    ]),
                                ])
                            )
                        ],
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ]
            ),
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
            operation: new Partial\Operation(
                operationId: 'get-bookings',
                servers: [],
                parameters: [
                    new Partial\Parameter(
                        name: 'page',
                        in: 'query',
                        schema: new Partial\Schema(
                            type: 'integer',
                            minimum: 1,
                            default: new Value(1),
                        ),
                    ),
                ],
                responses: [
                    200 => new Partial\Response(
                        description: 'A list of bookings',
                        headers: [
                            'Cache-Control' => self::refCacheControl(),
                            'RateLimit' => self::refRateLimit(),
                        ],
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(allOf: [
                                    self::refWrapperCollection(),
                                    new Partial\Schema(properties: [
                                        'data' => new Partial\Schema(
                                            type: 'array',
                                            items: self::refBooking(),
                                        ),
                                    ]),
                                    new Partial\Schema(properties: [
                                        'links' => new Partial\Schema(allOf: [
                                            self::refLinksSelf(),
                                            self::refLinksPagination(),
                                        ]),
                                    ]),
                                ])
                            ),
                            'application/xml' => new Partial\MediaType(
                                contentType: 'application/xml',
                                schema: new Partial\Schema(allOf: [
                                    self::refWrapperCollection(),
                                    new Partial\Schema(properties: [
                                        'data' => new Partial\Schema(
                                            type: 'array',
                                            items: self::refBooking(),
                                        ),
                                    ]),
                                    new Partial\Schema(properties: [
                                        'links' => new Partial\Schema(allOf: [
                                            self::refLinksSelf(),
                                            self::refLinksPagination(),
                                        ]),
                                    ]),
                                ])
                            )
                        ]
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ],
            ),
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
                            schema: self::refBooking(),
                        ),
                        new Partial\MediaType(
                            contentType: 'application/xml',
                            schema: self::refBooking(),
                        ),
                    ],
                    required: true,
                ),
                responses: [
                    201 => new Partial\Response(
                        description: 'Booking successful',
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(allOf: [
                                    self::refBooking(),
                                    new Partial\Schema(properties: [
                                        'links' => self::refLinksSelf(),
                                    ]),
                                ]),
                            ),
                            'application/xml' => new Partial\MediaType(
                                contentType: 'application/xml',
                                schema: new Partial\Schema(allOf: [
                                    self::refBooking(),
                                    new Partial\Schema(properties: [
                                        'links' => self::refLinksSelf(),
                                    ]),
                                ]),
                            )
                        ]
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '404' => self::refNotFound(),
                    '409' => self::refConflict(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ],
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
            operation: new Partial\Operation(
                operationId: 'get-booking',
                servers: [],
                parameters: [],
                responses: [
                    200 => new Partial\Response(
                        description: 'The booking details',
                        headers: [
                            'Cache-Control' => self::refCacheControl(),
                            'RateLimit' => self::refRateLimit(),
                        ],
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(allOf: [
                                    self::refBooking(),
                                    new Partial\Schema(properties: [
                                        'links' => self::refLinksSelf(),
                                    ]),
                                ])
                            ),
                            'application/xml' => new Partial\MediaType(
                                contentType: 'application/xml',
                                schema: new Partial\Schema(allOf: [
                                    self::refBooking(),
                                    new Partial\Schema(properties: [
                                        'links' => self::refLinksSelf(),
                                    ]),
                                ]),
                            )
                        ],
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '404' => self::refNotFound(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ],
            )
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
            operation: new Partial\Operation(
                operationId: 'delete-booking',
                servers: [],
                parameters: [],
                responses: [
                    204 => new Partial\Response(
                        description: 'Booking deleted',
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '404' => self::refNotFound(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ],
            )
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
                            schema: self::refBookingPayment(),
                        ),
                    ],
                    required: true,
                ),
                responses: [
                    200 => new Partial\Response(
                        description: 'Payment successful',
                        headers: [
                            'Cache-Control' => self::refCacheControl(),
                            'RateLimit' => self::refRateLimit(),
                        ],
                        content: [
                            'application/json' => new Partial\MediaType(
                                contentType: 'application/json',
                                schema: new Partial\Schema(allOf: [
                                    self::refBookingPayment(),
                                    new Partial\Schema(
                                        properties: [
                                            'links' => self::refLinksBooking()
                                        ],
                                    )
                                ]),
                            )
                        ]
                    ),
                    '400' => self::refBadRequest(),
                    '401' => self::refUnauthorized(),
                    '403' => self::refForbidden(),
                    '429' => self::refTooManyRequests(),
                    '500' => self::refInternalServerError(),
                ],
            )
        );
    }


    private static function refBadRequest(): Partial\Response
    {
        return new Partial\Response(
            description: 'Bad Request',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refBooking(): Partial\Schema
    {
        return new Partial\Schema(
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
        );
    }

    private static function refBookingPayment(): Partial\Schema
    {
        return new Partial\Schema(
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
            ],
        );
    }

    private static function refCacheControl(): Partial\Header
    {
        return new Partial\Header(
            schema: new Partial\Schema(
                type: 'string',
                description: 'A comma-separated list of directives as defined in [RFC 9111](https://www.rfc-editor.org/rfc/rfc9111.html).',
            ),
        );
    }

    private static function refConflict(): Partial\Response
    {
        return new Partial\Response(
            description: 'Conflict',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refForbidden(): Partial\Response
    {
        return new Partial\Response(
            description: 'Forbidden',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refInternalServerError(): Partial\Response
    {
        return new Partial\Response(
            description: 'Internal Server Error',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refLinksBooking(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'booking' => new Partial\Schema(type: 'string', format: 'uri'),
            ],
        );
    }

    private static function refLinksPagination(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'next' => new Partial\Schema(type: 'string', format: 'uri'),
                'prev' => new Partial\Schema(type: 'string', format: 'uri'),
            ],
        );
    }

    private static function refLinksSelf(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'self' => new Partial\Schema(type: 'string', format: 'uri'),
            ],
        );
    }

    private static function refNotFound(): Partial\Response
    {
        return new Partial\Response(
            description: 'Not Found',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refProblem(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'type' => new Partial\Schema(
                    type: 'string',
                    description: 'A URI reference that identifies the problem type',
                ),
                'title' => new Partial\Schema(
                    type: 'string',
                    description: 'A short, human-readable summary of the problem type',
                ),
                'detail' => new Partial\Schema(
                    type: 'string',
                    description: 'A human-readable explanation specific to this occurrence of the problem',
                ),
                'instance' => new Partial\Schema(
                    type: 'string',
                    description: 'A URI reference that identifies the specific occurrence of the problem',
                ),
                'status' => new Partial\Schema(
                    type: 'integer',
                    description: 'The HTTP status code',
                ),
            ],
        );
    }

    private static function refRateLimit(): Partial\Header
    {
        return new Partial\Header(
            description: <<<TEXT
                The RateLimit header communicates quota policies. It contains a `limit` to
                convey the expiring limit, `remaining` to convey the remaining quota units,
                and `reset` to convey the time window reset time.
                TEXT,
            schema: new Partial\Schema(type: 'string'),
        );
    }

    private static function refRetryAfter(): Partial\Header
    {
        return new Partial\Header(
            description: <<<TEXT
                The Retry-After header indicates how long the user agent should wait before making a follow-up request.
                The value is in seconds and can be an integer or a date in the future.
                If the value is an integer, it indicates the number of seconds to wait.
                If the value is a date, it indicates the time at which the user agent should make a follow-up request.
                TEXT,
            schema: new Partial\Schema(type: 'string'),
        );
    }

    private static function refStation(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            required: ['id', 'name', 'address', 'country_code'],
            properties: [
                'id' => new Partial\Schema(
                    type: 'string',
                    format: 'uuid',
                    description: 'Unique identifier for the station.',
                ),
                'name' => new Partial\Schema(
                    type: 'string',
                    description: 'The name of the station',
                ),
                'address' => new Partial\Schema(
                    type: 'string',
                    description: 'The address of the station.',
                ),
                'country_code' => new Partial\Schema(
                    type: 'string',
                    format: 'iso-country-code',
                    description: 'The country code of the station.',
                ),
                'timezone' => new Partial\Schema(
                    type: 'string',
                    description: 'The timezone of the station in the [IANA Time Zone Database format](https://www.iana.org/time-zones).',
                ),
            ],
        );
    }

    private static function refTrip(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'id' => new Partial\Schema(
                    type: 'string',
                    format: 'uuid',
                    description: 'Unique identifier for the trip',
                ),
                'origin' => new Partial\Schema(
                    type: 'string',
                    description: 'The starting station of the trip',
                ),
                'destination' => new Partial\Schema(
                    type: 'string',
                    description: 'The destination station of the trip',
                ),
                'departure_time' => new Partial\Schema(
                    type: 'string',
                    format: 'date-time',
                    description: 'The date and time when the trip departs',
                ),
                'arrival_time' => new Partial\Schema(
                    type: 'string',
                    format: 'date-time',
                    description: 'The date and time when the trip arrives',
                ),
                'operator' => new Partial\Schema(
                    type: 'string',
                    description: 'The name of the operator of the trip',
                ),
                'price' => new Partial\Schema(
                    type: 'number',
                    description: 'The cost of the trip',
                ),
                'bicycles_allowed' => new Partial\Schema(
                    type: 'boolean',
                    description: 'Indicates whether bicycles are allowed on the trip',
                ),
                'dogs_allowed' => new Partial\Schema(
                    type: 'boolean',
                    description: 'Indicates whether dogs are allowed on the trip',
                ),
            ],
        );
    }

    private static function refTooManyRequests(): Partial\Response
    {
        return new Partial\Response(
            description: 'Too Many Requests',
            headers: [
                'RateLimit' => self::refRateLimit(),
                'Retry-After' => self::refRetryAfter(),
            ],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refUnauthorized(): Partial\Response
    {
        return new Partial\Response(
            description: 'Unauthorized',
            headers: ['RateLimit' => self::refRateLimit()],
            content: [
                'application/problem+json' => new Partial\MediaType(
                    contentType: 'application/problem+json',
                    schema: self::refProblem(),
                ),
                'application/problem+xml' => new Partial\MediaType(
                    contentType: 'application/problem+xml',
                    schema: self::refProblem(),
                ),
            ]
        );
    }

    private static function refWrapperCollection(): Partial\Schema
    {
        return new Partial\Schema(
            type: 'object',
            properties: [
                'data' => new Partial\Schema(
                    type: 'array',
                    items: new Partial\Schema(type: 'object'),
                    description: 'The wrapper for a collection is an array of objects.',
                ),
                'links' => new Partial\Schema(
                    type: 'object',
                    description: 'A set of hypermedia links which serve as controls for the client.',
                ),
            ],
            description: 'This is a generic request/response wrapper which contains both data and links which serve as hypermedia controls (HATEOAS).',
        );
    }
}
