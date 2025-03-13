# Sistema de Extracción de Datos PDF a Excel con OCR

Una aplicación web en PHP que permite a los usuarios autenticados subir archivos PDF, seleccionar áreas de interés dentro del documento, extraer información estructurada mediante OCR (incluyendo reconocimiento de escritura manuscrita) y exportarla a formato Excel.

## Características

- **Autenticación y Seguridad**
  - Registro e inicio de sesión con hash de contraseñas (bcrypt)
  - Protección de rutas con sesiones PHP seguras
  - Niveles de acceso: Administrador y Usuario estándar
  - Recuperación de contraseña por correo electrónico

- **Gestión de PDFs**
  - Subida de archivos PDF con validación
  - Previsualización de PDFs en el navegador
  - Interfaz Drag & Drop para subir archivos

- **Selección de Áreas y Extracción de Datos**
  - Interfaz interactiva para seleccionar áreas dentro del PDF
  - Asociación de áreas seleccionadas a columnas del Excel
  - Guardado de configuraciones para documentos recurrentes

- **Reconocimiento Óptico de Caracteres (OCR)**
  - Integración con Tesseract OCR para reconocer texto manuscrito e impreso
  - Aplicación de filtros de imagen para mejorar el reconocimiento
  - Procesamiento optimizado

- **Exportación a Excel**
  - Generación de archivos XLSX o CSV con los datos extraídos
  - Configuración personalizada de nombres de columnas

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Extensiones PHP:
  - PDO
  - GD
  - mbstring
  - fileinfo
  - xml
  - zip
- Tesseract OCR (instalado en el servidor)
- Ghostscript (para procesamiento de PDFs)
- Composer para gestión de dependencias

## Tecnologías Utilizadas

- **Backend**
  - PHP con patrón MVC
  - MySQL como base de datos
  - TCPDF/FPDI para manipulación de PDFs
  - Tesseract OCR para reconocimiento de texto
  - PHPSpreadsheet para exportación a Excel

- **Frontend**
  - HTML, CSS, JavaScript
  - Bootstrap o Tailwind CSS
  - jQuery (opcional)
  - PDF.js para visualización de PDFs

## Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/usuario/pdf-to-excel.git
   cd pdf-to-excel
   ```

2. **Instalar dependencias de Composer**
   ```bash
   composer install
   ```

3. **Configuración del entorno**
   - Copiar el archivo `.env.example` a `.env`
   - Configurar las variables de entorno (base de datos, SMTP, etc.)
   ```bash
   cp .env.example .env
   ```

4. **Crear la base de datos**
   - Importar el archivo `database_schema.sql` en tu servidor MySQL
   
5. **Configurar permisos de directorios**
   ```bash
   chmod -R 775 public/uploads
   chmod -R 775 storage/logs
   ```

6. **Instalar Tesseract OCR en el servidor**
   - Para Ubuntu/Debian:
   ```bash
   sudo apt-get update
   sudo apt-get install tesseract-ocr
   sudo apt-get install tesseract-ocr-spa tesseract-ocr-eng
   ```
   - Para CentOS/RHEL:
   ```bash
   sudo yum install tesseract
   sudo yum install tesseract-langpack-spa tesseract-langpack-eng
   ```

7. **Instalar Ghostscript**
   - Para Ubuntu/Debian:
   ```bash
   sudo apt-get install ghostscript
   ```
   - Para CentOS/RHEL:
   ```bash
   sudo yum install ghostscript
   ```

8. **Configurar el servidor web**
   - Para Apache, asegúrate de que el módulo `mod_rewrite` esté habilitado
   - Establece el documento root a la carpeta `public`

## Configuración para Hostinger

La aplicación está diseñada para ser compatible con Hostinger. Asegúrate de:

1. **Versión de PHP**: Usar PHP 7.4 o superior en la configuración del hosting.
2. **Base de datos MySQL**: Crear la base de datos y el usuario desde el panel de control de Hostinger.
3. **URL Rewriting**: Habilitar `mod_rewrite` en la configuración de Apache (generalmente está habilitado por defecto en Hostinger).
4. **Subir archivos**: Usa FTP o el administrador de archivos del panel de control para subir los archivos.
5. **Instalación de dependencias**: Ejecuta Composer desde el terminal SSH si está disponible, o usa Composer localmente y sube la carpeta `vendor`.

## Uso

1. Registra una cuenta o inicia sesión.
2. Sube un archivo PDF desde el dashboard.
3. Visualiza el PDF y selecciona las áreas de interés usando la interfaz interactiva.
4. Asigna nombres de columna a cada área seleccionada.
5. Prueba la extracción OCR para verificar el reconocimiento.
6. Exporta los datos extraídos a Excel (XLSX) o CSV.

## Estructura del Proyecto

```
proyecto-pdf-excel/
├── app/                 # Lógica de la aplicación (MVC)
│   ├── Controllers/     # Controladores
│   ├── Models/          # Modelos de datos
│   └── Views/           # Vistas en PHP
├── core/                # Núcleo del framework
├── public/              # Recursos públicos
│   ├── css/             # Estilos CSS
│   ├── js/              # Scripts JavaScript
│   ├── uploads/         # Archivos subidos
│   └── index.php        # Punto de entrada
├── storage/             # Almacenamiento
│   └── logs/            # Registros de errores
├── vendor/              # Dependencias (Composer)
├── .env                 # Variables de entorno
├── .htaccess            # Configuración de Apache
├── composer.json        # Configuración de Composer
└── README.md            # Documentación
```

## Seguridad

- Todos los archivos subidos se validan para garantizar que sean PDFs legítimos.
- Las consultas a la base de datos utilizan PDO con consultas preparadas para prevenir inyección SQL.
- Las contraseñas se almacenan con hash bcrypt.
- Las sesiones están protegidas contra CSRF y fixation.

## Licencia

MIT

## Soporte

Para soporte, contacta a [lmonroy@tema.com.pe](mailto:lmonroy@tema.com.pe)