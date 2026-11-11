# Despliegue de Aldea Tecnológica

Guía para publicar el sistema con HTTPS antes del evento (24–26 de noviembre de 2026).
Hay dos caminos:

- **Opción A: Railway.** Plataforma administrada: se conecta al repositorio de GitHub y publica sola cada cambio en `main`. No hay servidor que mantener.
- **Opción B: servidor Ubuntu 24.04.** Un servidor de la UPP, una VM de Azure o un VPS, con Nginx, PHP 8.3 y MySQL instalados a mano.

> El HTTPS es obligatorio: sin él los navegadores bloquean la cámara del pase de lista con QR.

---

## 1. Dónde publicarlo

| Opción | Costo aproximado | Ventajas | Desventajas |
|---|---|---|---|
| **Railway (Hobby)** | USD 5 al mes, que incluyen USD 5 de consumo; app + MySQL rondan USD 5–10 al mes | HTTPS y MySQL incluidos; se publica solo desde GitHub; sin servidor que actualizar | Requiere tarjeta; bloquea el correo SMTP (ver 3.1) |
| **Servidor de la UPP** | Gratis | Dominio institucional, confianza de los alumnos | Depende de que TI lo asigne a tiempo; hay que administrarlo |
| **Azure for Students** | Gratis (USD 100 de crédito, sin tarjeta) | VM Linux y MySQL gratis 12 meses | Configuración más larga; hay que administrar el servidor |
| **Hetzner Cloud** | ~€6.5 al mes + IVA, cobro por hora | Rápido y estable | Requiere tarjeta; hay que administrar el servidor |
| Hosting compartido | ~USD 3 al mes pagando varios años | Barato | Límites de CPU en los picos de inscripción y del pase de lista; no recomendado |

Precios consultados en octubre de 2026; confírmalos antes de contratar.

- La prueba gratis de Railway (USD 5, 30 días, sin tarjeta) sirve para probar, pero no alcanza a cubrir el evento: hay que pasar al plan Hobby antes del 24 de noviembre.
- El crédito de DigitalOcean del GitHub Student Pack terminó el 31 de julio de 2026.

En cualquier opción conviene pedir a TI el subdominio `aldea.upp.edu.mx`.

---

## 2. Qué pedir a TI de la UPP

- **DNS:** que `aldea.upp.edu.mx` apunte al sitio.
  - En Railway es un registro `CNAME`, más un `TXT` de verificación; Railway muestra los valores exactos al agregar el dominio.
  - En un servidor propio es un registro `A` hacia su IP pública.
- **Correo:** UPP usa Google Workspace. Lo que hay que pedir depende del camino:
  - **Railway Hobby:** registros DNS de un servicio de correo por API (ver 3.1).
  - **Railway Pro o servidor propio:** una cuenta dedicada (por ejemplo `aldea.tecnologica@upp.edu.mx`) con **contraseña de aplicación**, o el **SMTP relay** de Google Workspace.

Sin correo, el enlace «¿Olvidaste tu contraseña?» no llega a los alumnos.

---

## 3. Opción A: Railway

El repositorio ya trae `railway.json`, que configura:

- la compilación con Railpack;
- la revisión de salud en `/up`;
- el reinicio automático si la app falla.

Railpack detecta Laravel e instala las dependencias de PHP. Además compila los estilos con `npm run build`, guarda la caché y corre las migraciones al arrancar.

### 3.1 Correo en Railway

Los planes Free, Trial y Hobby de Railway **bloquean los puertos SMTP** (25, 465 y 587), así que Gmail por SMTP no funciona ahí. Hay tres salidas:

1. **Plan Pro** (USD 20 al mes, con USD 20 de consumo incluido): permite SMTP con la cuenta institucional. Se puede contratar solo para noviembre.
2. **Servicio de correo por API**, como Resend (gratis hasta 3,000 correos al mes). TI agrega unos registros DNS para enviar desde `@upp.edu.mx`, y la app necesita un cambio pequeño para usarlo.
3. **Sin correo:** se oculta «¿Olvidaste tu contraseña?» y la coordinación restablece contraseñas desde el panel. Esto también requiere un cambio en la app.

### 3.2 Crear el proyecto

1. Entra a [railway.com](https://railway.com) con la cuenta de GitHub dueña del repositorio y contrata el plan Hobby.
2. En **Workspace › Usage**, pon un límite de gasto (por ejemplo USD 15) para evitar sorpresas.
3. **New Project › Deploy from GitHub repo ›** `Camilohh12/Aldea_tech`.
4. En el mismo proyecto: **+ Create › Database › MySQL**.
5. En el servicio de la app: **Settings › Networking › Generate Domain**. Railway da una dirección `https://….up.railway.app` con candado.

### 3.3 Variables de la app

En tu computadora genera la llave de la app (cópiala completa, incluido `base64:`):

```bash
php artisan key:generate --show
```

En el servicio de la app: **Variables › Raw Editor**. Pega esto y reemplaza `APP_KEY`:

```env
APP_NAME="Aldea Tecnológica UPP"
APP_ENV=production
APP_KEY=base64:PEGAR-AQUI
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_MX
TRUSTED_PROXIES=*
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
RAILPACK_PHP_EXTENSIONS=gd
ALDEA_URL_REGLAMENTO=
ALDEA_URL_AVISO_PRIVACIDAD=
```

- `TRUSTED_PROXIES=*` es necesario: Railway atiende el HTTPS y le pasa la visita a la app.
- `RAILPACK_PHP_EXTENSIONS=gd` hace que el escudo y los logos de las carreras aparezcan en los PDF.
- Las variables de correo se agregan según la salida elegida en 3.1.

### 3.4 Publicar

1. Al guardar las variables, Railway compila y publica. En **Deployments › View logs** debe terminar sin errores, y `https://…/up` debe responder.
2. Crea el Super Admin desde tu computadora con la CLI de Railway:

   ```bash
   npm install -g @railway/cli
   railway login
   railway link
   railway ssh
   ```

   Dentro de la sesión (elige el servicio de la app si pregunta):

   ```bash
   php artisan db:seed --class=ProduccionSeeder --force
   ```

   No corras `DatabaseSeeder` ni `ModuloAlumnoSeeder` en producción: crean datos de prueba con contraseñas conocidas. Si alguien lo intenta con `APP_ENV=production`, se detienen con un error y no crean nada.

### 3.5 Dominio propio

Cuando TI pueda configurarlo:

1. **Settings › Networking › Custom Domain ›** `aldea.upp.edu.mx`.
2. Pásale a TI los registros `CNAME` y `TXT` que muestra Railway.
3. Cambia la variable `APP_URL` a `https://aldea.upp.edu.mx`. Hazlo en cuanto el dominio funcione: la app solo atiende el dominio de `APP_URL`, y con cualquier otro responde con un error 400.

### 3.6 Actualizaciones

Cada merge a `main` se publica solo. Durante el evento (23–27 de noviembre) **no hagan merge a `main`**, salvo correcciones urgentes ya probadas. Si hace falta, el despliegue automático se pausa en **Settings › Source**.

### 3.7 Respaldos

Railway no respalda la base por su cuenta en el plan Hobby. Hazlo desde tu computadora con el `mysqldump` de XAMPP:

1. En el servicio MySQL, ve a **Variables** y copia los datos de `MYSQL_PUBLIC_URL`: host, puerto y contraseña.
2. Corre lo siguiente:

   ```bash
   C:\xampp\mysql\bin\mysqldump.exe -h HOST -P PUERTO -u root -p --single-transaction railway > respaldo-aldea.sql
   ```

Haz uno diario antes del evento y varios al día durante el evento. Guárdalos fuera de tu computadora, por ejemplo en Drive.

---

## 4. Opción B: servidor Ubuntu (una sola vez)

Conéctate por SSH con un usuario que tenga `sudo` (no `root`).

### 4.1 Seguridad básica

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y ufw fail2ban unattended-upgrades
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo dpkg-reconfigure -plow unattended-upgrades
```

- Entra por SSH con llave, no con contraseña.
- En Azure, abre en el grupo de seguridad de red solo los puertos 22, 80 y 443.

Si el servidor tiene 1 GB de RAM, agrega 2 GB de swap:

```bash
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 4.2 Paquetes

```bash
sudo apt install -y nginx mysql-server git unzip certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 22 (Vite 7 no funciona con el Node 18 de Ubuntu)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

`php8.3-gd` es necesario para que el escudo y los logos aparezcan en los certificados, el reporte del tutor y el programa del evento.

### 4.3 Base de datos

```bash
sudo mysql_secure_installation
sudo mysql
```

Dentro de MySQL (cambia la contraseña por una larga):

```sql
CREATE DATABASE aldea_tecnologica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'aldea'@'localhost' IDENTIFIED BY 'CAMBIAR-contraseña-larga';
GRANT ALL PRIVILEGES ON aldea_tecnologica.* TO 'aldea'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Para que los respaldos no pidan contraseña, crea `~/.my.cnf` con permisos `600`:

```ini
[client]
user=aldea
password=CAMBIAR-contraseña-larga
```

### 4.4 Código

Si el repositorio es privado, agrega una *deploy key* (llave SSH de solo lectura) en GitHub › Settings › Deploy keys.

```bash
sudo mkdir -p /var/www/aldea && sudo chown $USER:www-data /var/www/aldea
git clone https://github.com/Camilohh12/Aldea_tech.git /var/www/aldea
cd /var/www/aldea

composer install --no-dev --optimize-autoloader
cp .env.production.example .env
nano .env                      # completa APP_URL, DB_PASSWORD, los datos de correo y las ligas ALDEA_URL_*
php artisan key:generate

php artisan migrate --force
php artisan db:seed --class=ProduccionSeeder --force   # pide correo y contraseña del Super Admin

npm ci && npm run build
php artisan optimize

sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

No corras `ModuloAlumnoSeeder` en producción: crea datos de prueba con contraseñas conocidas.

### 4.5 Nginx

Crea `/etc/nginx/sites-available/aldea`:

```nginx
server {
    listen 80;
    server_name aldea.upp.edu.mx;
    root /var/www/aldea/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    client_max_body_size 10M;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/aldea /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

### 4.6 HTTPS (Let's Encrypt, gratis)

Cuando el subdominio ya apunte a la IP del servidor:

```bash
sudo certbot --nginx -d aldea.upp.edu.mx --redirect
```

Certbot renueva el certificado solo. Para comprobarlo: `sudo certbot renew --dry-run`.

### 4.7 Respaldos automáticos

```bash
mkdir -p ~/respaldos
crontab -e
```

Agrega estas líneas: respaldo cada hora del 23 al 27 de noviembre, diario el resto del tiempo, y borrado de respaldos de más de 30 días.

```cron
0 * 23-27 11 * mysqldump --single-transaction aldea_tecnologica | gzip > ~/respaldos/aldea-$(date +\%F-\%H).sql.gz
30 3 * * *     mysqldump --single-transaction aldea_tecnologica | gzip > ~/respaldos/aldea-$(date +\%F).sql.gz
0 4 * * *      find ~/respaldos -name '*.sql.gz' -mtime +30 -delete
```

Copia de vez en cuando la carpeta `~/respaldos` fuera del servidor, por ejemplo a Drive.

### 4.8 Actualizar el servidor

Cada vez que se haga merge a `main`:

```bash
cd /var/www/aldea
bash scripts/desplegar.sh
```

El script:

1. respalda la base de datos;
2. pone el sitio en mantenimiento;
3. baja el código y actualiza dependencias;
4. corre las migraciones nuevas sin borrar datos;
5. compila los estilos, limpia la caché y vuelve a poner el sitio en línea.

Si se edita el `.env`, corre `php artisan optimize` para que tome los cambios.

---

## 5. Lista de verificación antes de anunciarlo

- [ ] La dirección del sitio abre con candado y `http://` redirige a `https://`.
- [ ] `/up` responde con una página verde.
- [ ] Una dirección inexistente muestra «Página no encontrada», no el detalle del error.
- [ ] El Super Admin inicia sesión y ve el panel.
- [ ] Están registrados los tutores, aulas, ponentes, responsables y actividades.
- [ ] Un alumno de prueba se registra y elige a su tutor; los errores salen en español.
- [ ] «¿Olvidaste tu contraseña?» manda el correo y el enlace funciona.
- [ ] Desde un celular, el responsable abre el pase de lista y la cámara lee un QR.
- [ ] El certificado y el reporte del tutor salen con el escudo.
- [ ] Al terminar las pruebas se borraron las cuentas y actividades de prueba.
