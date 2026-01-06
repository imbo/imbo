# Imbo adapters SDK

SDK for storage and database adapters used with Imbo. This namespace contains some abstract integration test cases that **must** be used by all adapters for Imbo, making sure they all pass at least the common tests. Adapters should also add specific tests when needed.

The following table shows which test case you will need to extend to test your implementation of Imbos interfaces:

| Imbo interface                                                  | SDK test case                                                  |
| --------------------------------------------------------------- | -------------------------------------------------------------- |
| `Imbo\Database\DatabaseInterface`                               | `ImboSDK\Database\DatabaseTests`                               |
| `Imbo\Storage\StorageInterface`                                 | `ImboSDK\Storage\StorageTests`                                 |
| `Imbo\EventListener\ImageVariations\Database\DatabaseInterface` | `ImboSDK\EventListener\ImageVariations\Database\DatabaseTests` |
| `Imbo\EventListener\ImageVariations\Storage\StorageInterface`   | `ImboSDK\EventListener\ImageVariations\Storage\StorageTests`   |
| `Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface`       | `ImboSDK\Auth\AccessControl\Adapter\MutableAdapterTests`       |
