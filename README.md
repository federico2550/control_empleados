# FichAR - Sistema de Fichadas

Sistema web para que los empleados registren su **ingreso** y **egreso** con DNI y clave,
y un panel de administración para gestionar empleados y ver los movimientos.

## Instalación (XAMPP)

1. Copiar la carpeta del proyecto a `C:\xampp\htdocs\control_empleados`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Entrar a `http://localhost/phpmyadmin`, pestaña **Importar**, y cargar `database.sql`.
4. Abrir `http://localhost/control_empleados/`.

Usuario administrador inicial: **DNI 11111111 / clave admin123** (cambiarla desde Empleados).

## Estructura

| Archivo | Función |
|---|---|
| `config.php` | Conexión a la BD, sesión y funciones comunes |
| `login.php` | Ingreso con DNI y clave (redirige según el rol) |
| `panel.php` | Pantalla del empleado: último registro y botones Ingreso / Egreso |
| `registrar.php` | Guarda el movimiento (valida que se alternen ingreso y egreso) |
| `admin.php` | Administración: registrar empleados, movimientos con filtros y horas trabajadas, alta/baja y cambio de clave |
| `logout.php` | Cierra la sesión |
| `database.sql` | Script de creación de la base de datos |

## Reglas de negocio

- No se puede registrar un EGRESO sin un INGRESO previo, ni dos registros iguales seguidos.
- Los empleados desactivados no pueden iniciar sesión.
- Las claves se guardan encriptadas (`password_hash`).
- Las horas trabajadas se calculan emparejando cada INGRESO con el EGRESO siguiente.
