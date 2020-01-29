Feature: Imbo provides a metadata endpoint
    In order to handle metadata
    As an HTTP Client
    I want to make requests against the metadata endpoint

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys

    Scenario: Get metadata when image has no metadata attached
        When I request:
            | path                               | method | access token | sign request | request body                                                                                                                                                  |
            | metadata of previously added image | GET    | yes          |              |                                                                                                                                                               |
            | metadata of previously added image | PUT    |              | yes          | {"foo": "bar"}                                                                                                                                                |
            | metadata of previously added image | GET    | yes          |              |                                                                                                                                                               |
            | metadata of previously added image | POST   |              | yes          | {"bar": "foo"}                                                                                                                                                |
            | metadata of previously added image | GET    | yes          |              |                                                                                                                                                               |
            | metadata of previously added image | PUT    |              | yes          | {"key": "value"}                                                                                                                                              |
            | metadata of previously added image | GET    | yes          |              |                                                                                                                                                               |
            | metadata of previously added image | DELETE |              | yes          |                                                                                                                                                               |
            | metadata of previously added image | GET    | yes          |              |                                                                                                                                                               |
            | metadata of previously added image | PUT    |              | yes          | {foo bar}                                                                                                                                                     |
            | metadata of previously added image | PUT    |              | yes          | {"foo.bar": "bar"}                                                                                                                                            |
            | metadata of previously added image | PUT    |              | yes          | {"foo": {"bar": "value", "exif:foo": "value2"}}                                                                                                               |
            | metadata of previously added image | PUT    |              | yes          | {"html":"<div class=\"fat-text foo\">It's cool<!-- comment --></div>","json":"{\"foo\":\"bar\"}","norwegian":"\u00c5tte karer m\u00f8ter \u00e6rlige Erlend"} |

        Then the last responses match:
            | response | status line                                                                 | body is                                                                                                                                                        | header name  | header value     |
            | 1        | 200 OK                                                                      | {}                                                                                                                                                             | Content-Type | application/json |
            | 2        | 200 OK                                                                      | {"foo":"bar"}                                                                                                                                                  | Content-Type | application/json |
            | 3        | 200 OK                                                                      | {"foo":"bar"}                                                                                                                                                  | Content-Type | application/json |
            | 4        | 200 OK                                                                      | {"foo":"bar","bar":"foo"}                                                                                                                                      | Content-Type | application/json |
            | 5        | 200 OK                                                                      | {"foo":"bar","bar":"foo"}                                                                                                                                      | Content-Type | application/json |
            | 6        | 200 OK                                                                      | {"key":"value"}                                                                                                                                                | Content-Type | application/json |
            | 7        | 200 OK                                                                      | {"key":"value"}                                                                                                                                                | Content-Type | application/json |
            | 8        | 200 OK                                                                      | {}                                                                                                                                                             | Content-Type | application/json |
            | 9        | 200 OK                                                                      | {}                                                                                                                                                             | Content-Type | application/json |
            | 10       | 400 Invalid JSON data                                                       |                                                                                                                                                                | Content-Type | application/json |
            | 11       | 400 Invalid metadata. Dot characters ('.') are not allowed in metadata keys |                                                                                                                                                                | Content-Type | application/json |
            | 12       | 200 OK                                                                      | {"foo":{"bar":"value","exif:foo":"value2"}}                                                                                                                    | Content-Type | application/json |
            | 13       | 200 OK                                                                      | {"html":"<div class=\"fat-text foo\">It's cool<!-- comment --><\/div>","json":"{\"foo\":\"bar\"}","norwegian":"\u00c5tte karer m\u00f8ter \u00e6rlige Erlend"} | Content-Type | application/json |
