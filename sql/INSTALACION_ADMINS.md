# Instalación del Sistema de Gestión de Administradores

## Paso 1: Crear la tabla de administradores

Ejecuta el siguiente comando desde la línea de comandos de Windows:

```bash
cd C:\xampp
mysql -u root caceria < htdocs\caceria\sql\create_admins_table.sql
```

O puedes ejecutar manualmente el SQL en phpMyAdmin:
1. Abre http://localhost/phpmyadmin
2. Selecciona la base de datos `caceria`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `c:\xampp\htdocs\caceria\sql\create_admins_table.sql`
5. Click en "Continuar"

## Paso 2: Verificar la tabla

Verifica que la tabla se creó correctamente:

```bash
mysql -u root -e "USE caceria; DESCRIBE admins;"
```

Deberías ver las columnas: id, username, password, email, created_at, updated_at

## Paso 3: Verificar usuario inicial

Verifica que el admin inicial fue creado:

```bash
mysql -u root -e "USE caceria; SELECT id, username, email FROM admins;"
```

Deberías ver:
- Usuario: `admin`
- Email: `admin@caceria.local`
- Contraseña: `admin123`

## Paso 4: Probar el sistema

1. Navega a http://localhost/caceria/admin/login.php
2. Inicia sesión con:
   - Usuario: `admin`
   - Contraseña: `admin123`
3. Click en "JUGADORES" en el navbar superior
4. Prueba crear un nuevo administrador
5. Prueba editar un administrador
6. Prueba eliminar un administrador (no podrás eliminar el último)

## Notas de Seguridad

- Las contraseñas se almacenan con hash usando `password_hash()` de PHP
- No puedes eliminar tu propia cuenta
- No puedes eliminar el último administrador
- Se validan duplicados de username y email
- Las contraseñas deben tener mínimo 6 caracteres
