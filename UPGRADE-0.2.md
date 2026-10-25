# Upgrade from 0.1.x to 0.2.0

0.2.0 moves every class from the `Api\` namespace to `Jschaedl\ApiClient\`. Nothing else
changes: class names, sub-namespaces and behaviour stay the same.

| 0.1.x | 0.2.0 |
| --- | --- |
| `Api\Client` | `Jschaedl\ApiClient\Client` |
| `Api\Exception\ClientException` | `Jschaedl\ApiClient\Exception\ClientException` |
| `Api\Retry\GenericRetryStrategy` | `Jschaedl\ApiClient\Retry\GenericRetryStrategy` |
| ... | ... |

`Api\` was too generic for a library: any application or package could claim it as well.

## Migrating

Replace the namespace prefix in your code and config. This rewrites `use` statements (group
`use` included), fully qualified names (`\Api\...`), class names in strings and service IDs in
YAML, and leaves names like `MyApi\` or `App\ApiResource\` alone:

```bash
grep -rlF 'Api\' src tests config \
  | xargs perl -pi -e 's/(?<![A-Za-z0-9_])Api\\(?=[A-Z{])/Jschaedl\\ApiClient\\/g'
```

Then check the diff, and search for escaped forms the command does not touch, such as
`Api\\Client` in JSON or double-quoted YAML strings.

Rector is no help here: `RenameNamespaceRector` no longer exists in Rector 2, and
`RenameClassRector` leaves `use` statements unchanged for classes that no longer exist.
