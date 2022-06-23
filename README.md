<h1 align="center" style="border:none !important">
    <code>M2 PHP version compatibility check</code>
    <br>
    <br>
</h1>

## How To use
> **Requires [PHP 8.0+](https://php.net/releases/)**
> 
- Clone this repository to a location where it can access a Magento 2 installation
- Run composer install
- This app will run PHP_CodeSniffer with phpcompatibility/php-compatibility on the M2 doc root
```
Description:
  Check all module in an m2 application for PHP8 compatibility

Usage:
  check:all [options] [--] <root-dir>

Arguments:
  root-dir

Options:
      --php-version[=PHP-VERSION]   [default: "8.1"]
      --threads[=THREADS]           [default: "4"]
```

Sample: `php application check:all /var/projects/magento2 --threads=16`

```
+---------------------------------+----------------------------------------------------------+---------------+
| Module Name                     | Module Dir                                               | Compatiblity  |
+---------------------------------+----------------------------------------------------------+---------------+
| 'Justuno_Core'                  | vendor/justuno.com/core                                  | OK            |
| 'MSP_TwoFactorAuth'             | vendor/msp/twofactorauth                                 | OK            |
| 'MagePal_Reindex'               | vendor/magepal/magento2-reindex                          | OK            |
| 'Vertex_AddressValidationApi'   | vendor/vertexinc/module-address-validation-api           | OK            |
...
| vendor/snowdog/theme-blank-sass | vendor/snowdog/theme-blank-sass                          | OK            |
| 'Justuno_M2'                    | vendor/justuno.com/m2                                    | General error |
+---------------------------------+----------------------------------------------------------+---------------+
```
