# 🚀 Sistema de Gestión "Aldea Tecnológica"

Plataforma web independiente (*Mobile First*) para la administración, registro de actividades, control de cupos y pase de lista en tiempo real para el evento **Aldea Tecnológica** (24, 25 y 26 de Noviembre).

---

## 🛠️ Stack Tecnológico

* **Framework Backend:** Laravel 11 (PHP 8.2+)
* **Base de Datos:** MySQL
* **Autenticación y Permisos:** Laravel Breeze + `spatie/laravel-permission`
* **Estilos Frontend:** Tailwind CSS
* **Generación de Reportes:** `barryvdh/laravel-dompdf` (Certificados PDF) y `maatwebsite/excel` (Listas Excel)

---

## 👥 Roles del Sistema

1. **Super Admin / Admin:** Control total, gestión de actividades, control de pase de lista y reportes globales en Excel.
2. **Tutor:** Monitoreo del grupo de tutorados y descarga de reportes de asistencia en PDF.
3. **Ponente / Responsable:** Escaneo móvil de QR o código para pase de lista en tiempo real.
4. **Alumno:** Consulta de catálogo, inscripción por cupo, horario personal y código de asistencia.
5. **Docente:** Consulta y supervisión académica.

---

## ⚙️ Guía de Instalación Local (Para Colaboradores)

Si acabas de clonar este repositorio, sigue estos pasos para montar el entorno local:

### 1. Clonar el repositorio e instalar dependencias
```bash
# Clonar el proyecto
git clone [https://github.com/Camilohh12/Aldea_tech.git](https://github.com/Camilohh12/Aldea_tech.git)
cd aldea-tecnologica

# Instalar dependencias de PHP y JavaScript
composer install
npm install
