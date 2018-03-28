# kushki-php

[![Estatus en Snap CI](https://img.shields.io/snap-ci/Kushki/kushki-php/master.svg?maxAge=2592000)](https://snap-ci.com/Kushki/kushki-php)
[![Packagist](https://img.shields.io/packagist/v/kushki/kushki-php.svg?maxAge=2592000)](https://packagist.org/packages/kushki/kushki-php)
[![MIT License](https://img.shields.io/packagist/l/kushki/kushki-php.svg?maxAge=2592000)](https://github.com/Kushki/kushki-php/blob/master/LICENSE.txt)

## Documentación

[Puedes acceder a la documentación aquí](https://demo.kushkipagos.com/docs/php).

## Para correr las pruebas con phpunit

- Instalar phpunit como se indica aqui: https://phpunit.de/manual/current/en/installation.html

```sh
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit
phpunit --version
```

- Correr pruebas

```sh
./runTest.sh
```

## Para generar reportes de cobertura de pruebas debes:

- Instalar xdebug con el asistente http://xdebug.org/wizard.php
- Crear una carpeta para almacenar los reportes, por ejemplo "reports"
- Agrega o quita el parametro "--coverage-html reports" a la linea de ejecución de phpunit, según se necesite
