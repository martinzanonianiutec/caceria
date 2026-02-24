# Cacería Fotográfica (Scavenger Hunt)

Aplicación web para gestionar juegos de cacería fotográfica por equipos en tiempo real.

## Requisitos
- PHP 7.4+
- MySQL / MariaDB
- XAMPP o similar
- Conexión a Internet (para la generación de códigos QR vía API)

## Instalación
1. Clona o copia este repositorio en `htdocs/caceria`.
2. Importa la base de datos:
   - Crea una base de datos llamada `caceria`.
   - Ejecuta el script SQL ubicado en `sql/schema.sql`.
3. Configura la conexión en `includes/db.php` (usuario y contraseña de MySQL).

## Uso del Administrador
1. Ve a `http://localhost/caceria/admin/login.php`.
2. Credenciales por defecto:
   - **Usuario:** `admin`
   - **Contraseña:** `admin123`
3. Crea una partida, añade retos (challenges) y activa la partida cambiándola a estado "Open".
4. Comparte el QR o el link de unión con los jugadores.

## Flujo del Jugador
1. El jugador escanea el QR o ingresa al link `/join/<token>`.
2. Ingresa su nombre.
3. El sistema lo asigna automáticamente al equipo con menos jugadores.
4. El jugador ve su lista de retos y puede subir fotos para completarlos.

## Tecnologías Utilizadas
- **Backend:** PHP (PDO)
- **Base de Datos:** MySQL
- **Frontend:** HTML5, CSS3 (Vanilla), JavaScript (Opcional)
- **QR:** Google Chart API / QRServer API
