# Modulo de PQR para el sistema SAIA

El proyecto esta enfocado al proceso de PQR sobre el sistema SAIA, el cliente puede generar su propio formulario y publicarlo en un webservice, los usuarios pueden ingresar a dicho webservice y realizar todas las peticiones, quejas y reclamos llenando previamente el formulario realizado

## Comenzando 🚀

Estas instrucciones te permitirán obtener una copia del proyecto en funcionamiento en tu máquina local.

### Pre-requisitos 📋

1. _Servidor Web con PHP 7.4 o superior_
2. _El servidor debe tener instalado [nodejs](https://nodejs.org/es/download/), [composer](https://getcomposer.org/download/)_
3. _El servidor debe tener instalado el sistema SAIA_ ([https://laboratorio.netsaia.com:82/cerok/saia_2019.git](https://laboratorio.netsaia.com:82/cerok/saia_2019.git)).

### Instalación 🔧

El modulo esta compuesto por dos repositorios, uno con todo el codigo del frontEnd y otra con todo el codigo del backEnd

Para empezar se debe ingresar a la raiz del sistema SAIA y correr los siguientes comandos.

_Instalación frontEnd:_

```
#Se clona el repositorio
git submodule add --force https://github.com/k0n3r/front-pqr.git public/views/modules/pqr --name "front-pqr"

#Ingresamos al directorio del submodulo
cd public/views/modules/pqr/

#Instalamos las librerias
npm install

#Compilamos el codigo y generamos el front
npm run build

```

_Instalación backEnd:_

Desde la raiz del proyecto se corre nuevamente los siguientes comandos.

```
#Se clona el repositorio
git submodule add --force https://github.com/k0n3r/back-pqr.git src/Bundles/pqr --name "back-pqr"

#Se actualizan las clases y el autoload
composer dump-autoload -o

#Ejecutamos las migraciones
php bin/console doctrine:migrations:migrate

#Actualizamos permisos
chmod -R 770 *

```

## Despliegue 📦

Una vez realizado la instalación ingresamos al sistema SAIA y nos dirigimos al modulo de PQR, generamos el formulario segun las necesidades del cliente y publicamos.
Al publicar el sistema genera automaticamente los archivos necesarios para creaer el formulario y el webservice.

El webservice queda registrado en la siguiente URL: [https://DOMINIO/ws/pqr](https://DOMINIO/ws/pqr)

_Cambiar **DOMINIO** por el dominio del cliente_

## Construido con 🛠️

- [PHP](https://www.php.net/) - Para el BackEnd
- [Vue](https://vuejs.org/) - Para el FrontEnd

## Autores ✒️

- **Andrés Agudelo** - *andres.agudelo@cerok.com* - [Github](https://github.com/k0n3r)

## Licencia 📄

Este proyecto está bajo la Licencia (copyright) - [CEROK SAS](https://www.cerok.co/)

---

Ultima actualización: 2020-11-25
