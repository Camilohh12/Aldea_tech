# Despliegue de Aldea Tecnológica

Guía para publicar el sistema en un servidor con HTTPS antes del evento (24–26 de noviembre de 2026).
Sirve para un servidor de la UPP, una máquina virtual de Azure o un VPS: en todos los casos es un
servidor **Ubuntu 24.04** con Nginx, PHP 8.3 y MySQL.

> El HTTPS es obligatorio: sin él los navegadores bloquean la cámara del pase de lista con QR.

---

## 1. Dónde publicarlo

| Opción | Costo aproximado | Ventajas | Desventajas |
|---|---|---|---|
| **Servidor de la UPP** | Gratis | Dominio institucional (`aldea.upp.edu.mx`), confianza de los alumnos | Depende de que TI lo asigne a tiempo |
| **Azure for Students** | Gratis (USD 100 de crédito, sin tarjeta) | Infraestructura de Microsoft; VM Linux y MySQL gratis 12 meses | Configuración más larga; requiere ser alumno de tiempo completo con correo escolar |
| **Hetzner Cloud** | ~€6.5 al mes + IVA, cobro por hora | Rápido, estable, servidores en EE. UU.; se borra al terminar el evento | Requiere tarjeta |
| Hosting compartido | ~USD 3 al mes pagando varios años | Barato | Límites de CPU en los picos de inscripción y del pase de lista; no recomendado |

Precios consultados en octubre de 2026; confírmalos antes de contratar.
El crédito de DigitalOcean del GitHub Student Pack terminó el 31 de julio de 2026.

**Recomendación:**

1. Pedir a TI de la UPP el subdominio `aldea.upp.edu.mx` en cualquier caso, y preguntar si tienen un servidor o VM disponible.
2. Si no hay servidor: **Azure for Students** con una VM Ubuntu 24.04 tamaño **B2s** (2 vCPU, 4 GB). Cuesta unos USD 30 al mes, así que el crédito cubre el evento.
   Para que dure todo el año, la VM **B1s** es gratis, pero tiene 1 GB de RAM y requiere memoria swap.
3. Si prefieren pagar algo simple: Hetzner Cloud, del tamaño más chico con 4 GB de RAM.

Para el evento basta con **2 vCPU y 4 GB de RAM**.

---

## 2. Qué pedir a TI de la UPP

- **DNS:** un registro `A` de `aldea.upp.edu.mx` hacia la IP pública del servidor.
- **Correo:** UPP usa Google Workspace. Hace falta una cuenta dedicada (por ejemplo `aldea.tecnologica@upp.edu.mx`) y alguna de estas dos opciones:
  - una **contraseña de aplicación** de esa cuenta (requiere verificación en dos pasos, y que el administrador del dominio lo permita);
  - o que habiliten el **SMTP relay** de Google Workspace para la IP del servidor.

Sin correo, el enlace «¿Olvidaste tu contraseña?» no llega a los alumnos.

---

## 3. Preparar el servidor (una sola vez)

Conéctate por SSH con un usuario que tenga `sudo` (no `root`).

### 3.1 Seguridad básica

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

### 3.2 Paquetes

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

`php8.3-gd` es necesario para que el escudo aparezca en los certificados y en el reporte del tutor.

### 3.3 Base de datos

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

### 3.4 Código

Si el repositorio es privado, agrega una *deploy key* (llave SSH de solo lectura) en GitHub › Settings › Deploy keys.

```bash
sudo mkdir -p /var/www/aldea && sudo chown $USER:www-data /var/www/aldea
git clone https://github.com/Camilohh12/Aldea_tech.git /var/www/aldea
cd /var/www/aldea

composer install --no-dev --optimize-autoloader
cp .env.production.example .env
nano .env                      # completa APP_URL, DB_PASSWORD y los datos de correo
php artisan key:generate

php artisan migrate --force
php artisan db:seed --class=ProduccionSeeder --force   # pide correo y contraseña del Super Admin

npm ci && npm run build
php artisan optimize

sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

No corras `ModuloAlumnoSeeder` en producción: crea datos de prueba con contraseñas conocidas.

### 3.5 Nginx

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

### 3.6 HTTPS (Let's Encrypt, gratis)

Cuando el subdominio ya apunte a la IP del servidor:

```bash
sudo certbot --nginx -d aldea.upp.edu.mx --redirect
```

Certbot renueva el certificado solo. Para comprobarlo: `sudo certbot renew --dry-run`.

### 3.7 Respaldos automáticos

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

---

## 4. Actualizar el sitio

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

- [ ] `https://aldea.upp.edu.mx` abre con candado y `http://` redirige a `https://`.
- [ ] `https://aldea.upp.edu.mx/up` responde con una página verde.
- [ ] Una dirección inexistente muestra «Página no encontrada», no el detalle del error.
- [ ] El Super Admin inicia sesión y ve el panel.
- [ ] Están registrados los tutores, aulas, ponentes, responsables y actividades.
- [ ] Un alumno de prueba se registra y elige a su tutor; los errores salen en español.
- [ ] «¿Olvidaste tu contraseña?» manda el correo y el enlace funciona.
- [ ] Desde un celular, el responsable abre el pase de lista y la cámara lee un QR.
- [ ] El certificado y el reporte del tutor salen con el escudo.
- [ ] Al terminar las pruebas se borraron las cuentas y actividades de prueba.
